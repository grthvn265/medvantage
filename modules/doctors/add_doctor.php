<?php
header('Content-Type: application/json; charset=utf-8');

require '../../components/db.php';
require '../../components/audit_log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

/* ==============================
   VALIDATION HELPER FUNCTIONS
============================== */

function sanitizeText($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validatePhoneNumber($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    return (strlen($phone) === 11 && preg_match('/^09\d{9}$/', $phone));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateNameField($name, $maxLength = 100, $minLength = 1) {
    if (empty($name)) return false;
    if (strlen($name) < $minLength || strlen($name) > $maxLength) return false;
    return preg_match('/^[a-zA-Z\s\'-]+$/', $name);
}

function validateDateOfBirth($dob) {
    $date = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$date || $date->format('Y-m-d') !== $dob) return false;
    
    // Check year is 1920 or later
    if ((int)$date->format('Y') < 1920) return false;
    
    // Check if date doesn't exceed today
    $today = new DateTime();
    if ($date > $today) return false;
    
    // Check if age is at least 1 year old (not age 0)
    $age = $today->diff($date)->y;
    return ($age >= 1 && $age <= 150);
}

function formatName($name) {
    // Capitalize each word when there are spaces
    $words = array_filter(explode(' ', strtolower(trim($name))));
    return implode(' ', array_map(function($word) {
        return ucfirst($word);
    }, $words));
}

/* ==============================
   1. SANITIZE & VALIDATE INPUT
============================== */

$last_name = isset($_POST['last_name']) ? sanitizeText($_POST['last_name']) : '';
$first_name = isset($_POST['first_name']) ? sanitizeText($_POST['first_name']) : '';
$middle_initial = isset($_POST['middle_initial']) ? sanitizeText($_POST['middle_initial']) : '';
$suffix = isset($_POST['suffix']) ? sanitizeText($_POST['suffix']) : '';
$date_of_birth = isset($_POST['date_of_birth']) ? sanitizeText($_POST['date_of_birth']) : '';
$sex = isset($_POST['sex']) ? sanitizeText($_POST['sex']) : '';
$address = isset($_POST['address']) ? sanitizeText($_POST['address']) : '';
$contact_number = isset($_POST['contact_number']) ? sanitizeText($_POST['contact_number']) : '';
$email = isset($_POST['email']) ? sanitizeText($_POST['email']) : '';
$emergency_contact_person = isset($_POST['emergency_contact_person']) ? sanitizeText($_POST['emergency_contact_person']) : '';
$emergency_contact_number = isset($_POST['emergency_contact_number']) ? sanitizeText($_POST['emergency_contact_number']) : '';
$emergency_email = isset($_POST['emergency_email']) ? sanitizeText($_POST['emergency_email']) : '';
$unavailable_days = isset($_POST['unavailable_days']) ? $_POST['unavailable_days'] : [];
$available_times = isset($_POST['available_times']) ? $_POST['available_times'] : [];

// Calculate unavailable days from available days (inverse logic)
$all_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$available_days_selected = isset($_POST['available_days']) ? $_POST['available_days'] : [];
$unavailable_days = array_diff($all_days, $available_days_selected);

$errors = [];

/* ==============================
   2. VALIDATE REQUIRED FIELDS
============================== */

if (!validateNameField($last_name, 100, 2)) {
    $errors['last_name'] = "Last name is required with minimum 2 characters (letters, spaces, hyphens, apostrophes only)";
}

if (!validateNameField($first_name, 100, 2)) {
    $errors['first_name'] = "First name is required with minimum 2 characters (letters, spaces, hyphens, apostrophes only)";
}

if (!validateDateOfBirth($date_of_birth)) {
    $errors['date_of_birth'] = "Date of birth must be valid, not in future, and doctor must be at least 1 year old";
}

if (!in_array($sex, ['Male', 'Female', 'Other'])) {
    $errors['sex'] = "Sex selection is required";
}

/* ==============================
   3. VALIDATE OPTIONAL FIELDS
============================== */

if (!empty($middle_initial)) {
    if (!preg_match('/^[a-zA-Z]$/', $middle_initial)) {
        $errors['middle_initial'] = "Middle initial must be a single letter";
    }
}

if (!empty($suffix)) {
    $valid_suffixes = ['Sr.', 'Jr.', 'I', 'II', 'III', 'IV', 'V'];
    if (!in_array($suffix, $valid_suffixes)) {
        $errors['suffix'] = "Suffix must be a valid industry standard value (Sr., Jr., I, II, III, IV, V)";
    }
}

if (!empty($address)) {
    if (strlen($address) > 200) {
        $errors['address'] = "Address must not exceed 200 characters";
    }
}

/* ==============================
   4. VALIDATE PHONE NUMBERS
============================== */

if (!empty($contact_number)) {
    if (!validatePhoneNumber($contact_number)) {
        $errors['contact_number'] = "Contact number must be exactly 11 digits starting with 09";
    }
}

if (!empty($emergency_contact_number)) {
    if (!validatePhoneNumber($emergency_contact_number)) {
        $errors['emergency_contact_number'] = "Emergency contact number must be exactly 11 digits starting with 09";
    }
}

if (!empty($emergency_email)) {
    if (!validateEmail($emergency_email)) {
        $errors['emergency_email'] = "Emergency email must be in valid format";
    }
    if (strlen($emergency_email) > 100) {
        $errors['emergency_email'] = "Emergency email must not exceed 100 characters";
    }
}

/* ==============================
   5. VALIDATE EMAIL
============================== */

if (!empty($email)) {
    if (!validateEmail($email)) {
        $errors['email'] = "Email address must be in valid format";
    }
    if (strlen($email) > 100) {
        $errors['email'] = "Email address must not exceed 100 characters";
    }
}

/* ==============================
   6. VALIDATE CONTACT PERSON NAME
============================== */

if (!empty($emergency_contact_person)) {
    if (!validateNameField($emergency_contact_person, 100)) {
        $errors['emergency_contact_person'] = "Emergency contact person must contain only letters (max 100 characters)";
    }
}

/* ==============================
   7. VALIDATE UNAVAILABLE DAYS & AVAILABLE TIMES
============================== */

$valid_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
if (!empty($available_days_selected)) {
    foreach ($available_days_selected as $day) {
        if (!in_array($day, $valid_days)) {
            $errors['available_days'] = "Invalid day selection";
            break;
        }
    }
}

$valid_times = ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
if (!empty($available_times)) {
    foreach ($available_times as $time) {
        if (!in_array($time, $valid_times)) {
            $errors['available_times'] = "Invalid time slot selection";
            break;
        }
    }
}

/* ==============================
   8. RETURN ERRORS OR INSERT
============================== */

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

$pdo->beginTransaction();

try {
    // Insert doctor
    $stmt = $pdo->prepare("
        INSERT INTO doctors 
        (last_name, first_name, middle_initial, suffix, date_of_birth, sex, address, contact_number, email, emergency_contact_person, emergency_contact_number, emergency_email)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        formatName($last_name),
        formatName($first_name),
        !empty($middle_initial) ? strtoupper($middle_initial) : null,
        !empty($suffix) ? $suffix : null,
        $date_of_birth,
        $sex,
        !empty($address) ? $address : null,
        !empty($contact_number) ? preg_replace('/\D/', '', $contact_number) : null,
        !empty($email) ? $email : null,
        !empty($emergency_contact_person) ? formatName($emergency_contact_person) : null,
        !empty($emergency_contact_number) ? preg_replace('/\D/', '', $emergency_contact_number) : null,
        !empty($emergency_email) ? $emergency_email : null
    ]);

    $doctor_id = (int) $pdo->lastInsertId();

    // Insert unavailable days
    if (!empty($unavailable_days)) {
        $stmt = $pdo->prepare("
            INSERT INTO doctor_unavailable_days (doctor_id, day_of_week)
            VALUES (?, ?)
        ");

        foreach ($unavailable_days as $day) {
            $stmt->execute([$doctor_id, $day]);
        }
    }

    // Insert available times
    if (!empty($available_times)) {
        $stmt = $pdo->prepare("
            INSERT INTO doctor_available_times (doctor_id, time_slot)
            VALUES (?, ?)
        ");

        foreach ($available_times as $time) {
            $stmt->execute([$doctor_id, $time]);
        }
    }

    $pdo->commit();

    logAudit($pdo, 'CREATE', 'doctors', $doctor_id, 'Created doctor record');

    echo json_encode([
        'success' => true,
        'message' => 'Doctor added successfully'
    ]);
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again later.'
    ]);
    exit;
}