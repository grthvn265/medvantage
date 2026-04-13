<?php
require '../../components/db.php';
require '../../components/audit_log.php';

// Validate input
$patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
$doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null;
$appointment_date = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
$appointment_time = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : null;
$reason = isset($_POST['reason']) ? $_POST['reason'] : null;
$status = 'Scheduled'; // New appointments are always Scheduled

// Extract start time from hourly range (e.g., "10:00-11:00" -> "10:00")
if ($appointment_time && strpos($appointment_time, '-') !== false) {
    $timeRange = explode('-', $appointment_time);
    $appointment_time = trim($timeRange[0]);
}

// Validate required fields
if (!$patient_id || !$doctor_id || !$appointment_date || !$appointment_time) {
    header('Location: ' . appUrl('/appointments?error=' . urlencode('Missing required fields')));
    exit;
}

try {
    // Validate patient exists and is active
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE patient_id = ? AND status = 'active'");
    $stmt->execute([$patient_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Patient not found or inactive");
    }

    // Validate doctor exists and is active
    $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE doctor_id = ? AND is_archived = 0");
    $stmt->execute([$doctor_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Doctor not found or archived");
    }

    // Check if date is blocked globally
    $stmt = $pdo->prepare("SELECT reason FROM blocked_dates WHERE blocked_date = ?");
    $stmt->execute([$appointment_date]);
    $blockedDate = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($blockedDate) {
        throw new Exception("Bookings not allowed on this date" . ($blockedDate['reason'] ? " ({$blockedDate['reason']})" : ""));
    }

    // Check doctor availability - validate day
    $dateObj = new DateTime($appointment_date);
    $dayOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$dateObj->format('w')];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM doctor_unavailable_days 
        WHERE doctor_id = ? AND day_of_week = ?
    ");
    $stmt->execute([$doctor_id, $dayOfWeek]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Doctor is not available on $dayOfWeek");
    }

    // Check doctor availability - validate time
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM doctor_available_times 
        WHERE doctor_id = ? AND TRIM(time_slot) = ?
    ");
    $stmt->execute([$doctor_id, $appointment_time]);
    $hasTimeSlot = $stmt->fetchColumn() > 0;

    // If no times are configured, doctor cannot be booked
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM doctor_available_times WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $hasAnyTimes = $stmt->fetchColumn() > 0;

    if (!$hasAnyTimes) {
        throw new Exception("No available time slots configured for this doctor");
    }

    if (!$hasTimeSlot) {
        throw new Exception("Doctor is not available at this time slot");
    }

    // Check for double-booking
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM appointments 
        WHERE doctor_id = ? 
        AND appointment_date = ? 
        AND appointment_time = ?
        AND status != 'Cancelled'
        AND is_archived = 0
    ");
    $stmt->execute([$doctor_id, $appointment_date, $appointment_time]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Doctor is already booked at this time");
    }

    // Insert appointment
    $stmt = $pdo->prepare("
        INSERT INTO appointments 
        (patient_id, doctor_id, appointment_date, appointment_time, reason, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $patient_id,
        $doctor_id,
        $appointment_date,
        $appointment_time,
        $reason,
        $status
    ]);

    $appointmentId = (int) $pdo->lastInsertId();
    logAudit($pdo, 'CREATE', 'appointments', $appointmentId, 'Created appointment');

    header('Location: ' . appUrl('/appointments?success=1'));
    exit;

} catch (Exception $e) {
    header('Location: ' . appUrl('/appointments?error=' . urlencode($e->getMessage())));
    exit;
}