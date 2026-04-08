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
   1. SANITIZE INPUTS
============================== */

$patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;

if ($patient_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
    exit;
}

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

$errors = [];

/* ==============================
   2. VERIFY PATIENT EXISTS
============================== */

$stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Patient not found']);
    exit;
}

/* ==============================
   3. VALIDATE REQUIRED FIELDS
============================== */

if (!validateNameField($last_name, 100, 2)) {
    $errors['last_name'] = "Last name is required with minimum 2 characters (letters, spaces, hyphens, apostrophes only)";
}

if (!validateNameField($first_name, 100, 2)) {
    $errors['first_name'] = "First name is required with minimum 2 characters (letters, spaces, hyphens, apostrophes only)";
}

if (!validateDateOfBirth($date_of_birth)) {
    $errors['date_of_birth'] = "Date of birth must be valid, not in future, and patient must be at least 1 year old";
}

if (!in_array($sex, ['Male', 'Female', 'Other'])) {
    $errors['sex'] = "Sex selection is required";
}

/* ==============================
   4. VALIDATE OPTIONAL FIELDS
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

// Validate civil status (dropdown, optional)
if (!empty($address)) {
    if (strlen($address) > 200) {
        $errors['address'] = "Address must not exceed 200 characters";
    }
}

/* ==============================
   5. VALIDATE PHONE NUMBERS
============================== */

if (empty($contact_number)) {
    $errors['contact_number'] = "Patient contact number is required (11 digits starting with 09)";
} else {
    if (!validatePhoneNumber($contact_number)) {
        $errors['contact_number'] = "Patient contact number must be exactly 11 digits starting with 09";
    } else {
        // Check for duplicate (exclude current patient) only if format is valid
        $check = $pdo->prepare("SELECT patient_id FROM patients WHERE contact_number = ? AND patient_id != ?");
        $check->execute([preg_replace('/\D/', '', $contact_number), $patient_id]);
        if ($check->rowCount() > 0) {
            $errors['contact_number'] = "Patient contact number already exists";
        }
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
   6. VALIDATE EMAIL
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
   7. VALIDATE CONTACT PERSON NAME
============================== */

if (!empty($emergency_contact_person)) {
    if (!validateNameField($emergency_contact_person, 100)) {
        $errors['emergency_contact_person'] = "Emergency contact person must contain only letters (max 100 characters)";
    }
}

/* ==============================
   8. RETURN ERRORS OR UPDATE
============================== */

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE patients SET
            last_name = :last_name,
            first_name = :first_name,
            middle_initial = :middle_initial,
            suffix = :suffix,
            date_of_birth = :date_of_birth,
            sex = :sex,
            address = :address,
            contact_number = :contact_number,
            email = :email,
            emergency_contact_person = :emergency_contact_person,
            emergency_contact_number = :emergency_contact_number,
            emergency_email = :emergency_email
        WHERE patient_id = :patient_id
    ");

    $stmt->execute([
        ':last_name' => formatName($last_name),
        ':first_name' => formatName($first_name),
        ':middle_initial' => !empty($middle_initial) ? strtoupper($middle_initial) : null,
        ':suffix' => !empty($suffix) ? $suffix : null,
        ':date_of_birth' => $date_of_birth,
        ':sex' => $sex,
        ':address' => !empty($address) ? $address : null,
        ':contact_number' => !empty($contact_number) ? preg_replace('/\D/', '', $contact_number) : null,
        ':email' => !empty($email) ? $email : null,
        ':emergency_contact_person' => !empty($emergency_contact_person) ? formatName($emergency_contact_person) : null,
        ':emergency_contact_number' => !empty($emergency_contact_number) ? preg_replace('/\D/', '', $emergency_contact_number) : null,
        ':emergency_email' => !empty($emergency_email) ? $emergency_email : null,
        ':patient_id' => $patient_id
    ]);

    logAudit($pdo, 'UPDATE', 'patients', $patient_id, 'Updated patient record');

    echo json_encode([
        'success' => true,
        'message' => 'Patient updated successfully'
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again later.'
    ]);
    exit;
}