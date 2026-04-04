<?php
// Prevent any output before JSON header
ob_start();

try {
    require '../../components/db.php';
    
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');
    
    $doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
    $date = isset($_GET['date']) ? $_GET['date'] : null;
    $appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : null;

    if (!$doctor_id || !$date) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        exit;
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date format']);
        exit;
    }

    // Check if date is blocked for all bookings
    $stmt = $pdo->prepare("SELECT id, reason FROM blocked_dates WHERE blocked_date = ?");
    $stmt->execute([$date]);
    $blockedDate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($blockedDate) {
        echo json_encode([
            'available' => false,
            'message' => "Bookings not allowed on this date" . ($blockedDate['reason'] ? " ({$blockedDate['reason']})" : ""),
            'times' => []
        ]);
        exit;
    }

    // Get day of week (0=Sunday, 6=Saturday)
    $dateObj = new DateTime($date);
    $dayOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$dateObj->format('w')];

    // Check if doctor is unavailable on this day
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM doctor_unavailable_days 
        WHERE doctor_id = ? AND day_of_week = ?
    ");
    $stmt->execute([$doctor_id, $dayOfWeek]);
    $isUnavailable = $stmt->fetchColumn() > 0;

    if ($isUnavailable) {
        echo json_encode([
            'available' => false,
            'message' => "Doctor is not available on $dayOfWeek",
            'times' => []
        ]);
        exit;
    }

    // Get doctor's available times
    $stmt = $pdo->prepare("
        SELECT TRIM(time_slot) as time_slot FROM doctor_available_times 
        WHERE doctor_id = ? 
        ORDER BY time_slot ASC
    ");
    $stmt->execute([$doctor_id]);
    $availableTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // If no times set, doctor available all day
    if (empty($availableTimes)) {
        $availableTimes = ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
    }

    // Get booked times for this doctor on this date (excluding cancelled appointments and current appointment if editing)
    $query = "
        SELECT DISTINCT TRIM(appointment_time) as appointment_time FROM appointments 
        WHERE doctor_id = ? 
        AND appointment_date = ? 
        AND status != 'Cancelled'
    ";
    $params = [$doctor_id, $date];
    
    // If editing an appointment, exclude it from the booked times
    if ($appointment_id) {
        $query .= " AND appointment_id != ?";
        $params[] = $appointment_id;
    }
    
    $query .= " ORDER BY appointment_time ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookedTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Remove booked times from available times
    $availableTimes = array_values(array_diff($availableTimes, $bookedTimes));

    echo json_encode([
        'available' => true,
        'times' => $availableTimes,
        'dayOfWeek' => $dayOfWeek,
        'booked' => $bookedTimes
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

exit;
