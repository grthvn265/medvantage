<?php
require '../../components/db.php';

if (!isset($_GET['id'])) {
    header("Location: doctors.php");
    exit;
}

$doctor_id = (int) $_GET['id'];

// Fetch doctor
$stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    die("Doctor not found.");
}

// Fetch unavailable days
$stmt = $pdo->prepare("SELECT day_of_week FROM doctor_unavailable_days WHERE doctor_id = ?");
$stmt->execute([$doctor_id]);
$unavailable_days = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Calculate available days (inverse of unavailable days)
$all_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$available_days = array_diff($all_days, $unavailable_days);

// Fetch available times
// Fetch available times (FORMAT TIME to match checkbox values)
$stmt = $pdo->prepare("SELECT time_slot FROM doctor_available_times WHERE doctor_id = ?");
$stmt->execute([$doctor_id]);

$available_times = array_map(function($time) {
    return date('H:i', strtotime($time));
}, $stmt->fetchAll(PDO::FETCH_COLUMN));

$fullName = $doctor['last_name'] . ', ' . $doctor['first_name'];
if ($doctor['middle_initial']) $fullName .= ' '.$doctor['middle_initial'].'.';
if ($doctor['suffix']) $fullName .= ', '.$doctor['suffix'];

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$times = ['10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Doctor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-size: 16px; }
        .card { border-radius: 12px; }
        .section-title { font-weight: 600; margin-top: 20px; }
        
        /* Form validation styling */
        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .form-control.is-invalid:focus,
        .form-select.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        
        [data-error]:not(.d-none) {
            display: block !important;
            animation: slideDown 0.2s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

<?php include '../../components/sidebar.php'; ?>

<div class="content-wrapper p-4">
<div class="container-fluid">

<div class="card shadow-lg mt-4">
<div class="card-body">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold">Doctor Information</h4>
    <div>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal">
            Edit Doctor
        </button>
        <a href="doctors.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <p><strong>Name:</strong> Dr. <?= $fullName ?></p>
        <p><strong>Date of Birth:</strong> <?= $doctor['date_of_birth'] ? date('M d, Y', strtotime($doctor['date_of_birth'])) : 'N/A' ?></p>
        <p><strong>Sex:</strong> <?= $doctor['sex'] ?: 'N/A' ?></p>
        <p><strong>Address:</strong> <?= $doctor['address'] ?: 'N/A' ?></p>
    </div>
    <div class="col-md-6">
        <p><strong>Contact Number:</strong> <?= $doctor['contact_number'] ?: 'N/A' ?></p>
        <p><strong>Email:</strong> <?= $doctor['email'] ?: 'N/A' ?></p>
        <p><strong>Registered Date:</strong> <?= date('M d, Y', strtotime($doctor['created_at'])) ?></p>
        <p><strong>Status:</strong> <span class="badge <?= $doctor['is_archived'] ? 'bg-secondary' : 'bg-success' ?>"><?= $doctor['is_archived'] ? 'Archived' : 'Active' ?></span></p>
    </div>
</div>

<?php if ($doctor['emergency_contact_person'] || $doctor['emergency_contact_number'] || $doctor['emergency_email']): ?>
<div class="section-title">Emergency Contact Information</div>
<div class="row mb-4">
    <div class="col-md-6">
        <p><strong>Emergency Contact Person:</strong> <?= $doctor['emergency_contact_person'] ?: 'N/A' ?></p>
        <p><strong>Emergency Contact Number:</strong> <?= $doctor['emergency_contact_number'] ?: 'N/A' ?></p>
    </div>
    <div class="col-md-6">
        <p><strong>Emergency Email:</strong> <?= $doctor['emergency_email'] ?: 'N/A' ?></p>
    </div>
</div>
<?php endif; ?>

<div class="section-title">Days Available</div>
<?php if ($available_days): ?>
    <ul>
        <?php foreach ($available_days as $day): ?>
            <li><?= $day ?></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p class="text-danger">Not available any days</p>
<?php endif; ?>

<div class="section-title">Time Available</div>
<?php if ($available_times): ?>
    <ul>
        <?php foreach ($available_times as $time): 
            $hour = (int)substr($time, 0, 2);
            $startTime = date('g:ia', strtotime($time));
            $endHour = str_pad($hour + 1, 2, '0', STR_PAD_LEFT);
            $endTime = date('g:ia', strtotime($endHour . ':00'));
            $timeRange = $startTime . ' - ' . $endTime;
            ?>
            <li><?= $timeRange ?></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p class="text-danger">No available time slots set</p>
<?php endif; ?>

</div>
</div>

</div>
</div>

<!-- ================= EDIT MODAL ================= -->

<div class="modal fade" id="editModal">
<div class="modal-dialog modal-lg">
<div class="modal-content">

<form id="editDoctorForm">
<input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">

<div class="modal-header">
    <h5 class="modal-title">Edit Doctor</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<!-- Success Message -->
<div id="doctorEditFormMessages"></div>

<div class="row g-3">

<div class="col-md-4">
    <label class="form-label">Last Name <span class="text-danger">*</span></label>
    <input type="text" name="last_name" class="form-control" maxlength="100" minlength="2"
           pattern="[a-zA-Z\s'-]+" title="Only letters, spaces, hyphens and apostrophes allowed. Minimum 2 characters."
           value="<?= $doctor['last_name'] ?>" required>
    <small class="text-danger d-none" data-error="last_name"></small>
</div>

<div class="col-md-4">
    <label class="form-label">First Name <span class="text-danger">*</span></label>
    <input type="text" name="first_name" class="form-control" maxlength="100" minlength="2"
           pattern="[a-zA-Z\s'-]+" title="Only letters, spaces, hyphens and apostrophes allowed. Minimum 2 characters."
           value="<?= $doctor['first_name'] ?>" required>
    <small class="text-danger d-none" data-error="first_name"></small>
</div>

<div class="col-md-2">
    <label class="form-label">M.I.</label>
    <input type="text" name="middle_initial" class="form-control" maxlength="1"
           pattern="[a-zA-Z]" title="Single letter only"
           value="<?= $doctor['middle_initial'] ?>">
    <small class="text-danger d-none" data-error="middle_initial"></small>
</div>

<div class="col-md-2">
    <label class="form-label">Suffix</label>
    <select name="suffix" class="form-select">
        <option value="">-- Select --</option>
        <option value="Sr." <?= $doctor['suffix'] === 'Sr.' ? 'selected' : '' ?>>Sr. (Senior)</option>
        <option value="Jr." <?= $doctor['suffix'] === 'Jr.' ? 'selected' : '' ?>>Jr. (Junior)</option>
        <option value="I" <?= $doctor['suffix'] === 'I' ? 'selected' : '' ?>>I</option>
        <option value="II" <?= $doctor['suffix'] === 'II' ? 'selected' : '' ?>>II</option>
        <option value="III" <?= $doctor['suffix'] === 'III' ? 'selected' : '' ?>>III</option>
        <option value="IV" <?= $doctor['suffix'] === 'IV' ? 'selected' : '' ?>>IV</option>
        <option value="V" <?= $doctor['suffix'] === 'V' ? 'selected' : '' ?>>V</option>
    </select>
    <small class="text-danger d-none" data-error="suffix"></small>
</div>

<div class="col-md-3">
    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
    <input type="date" name="date_of_birth" class="form-control" id="editDobInput"
           value="<?= $doctor['date_of_birth'] ?>" required>
    <small class="text-danger d-none" data-error="date_of_birth"></small>
</div>

<div class="col-md-3">
    <label class="form-label">Sex <span class="text-danger">*</span></label>
    <select name="sex" class="form-select" required>
        <option value="">-- Select --</option>
        <option value="Male" <?= $doctor['sex'] === 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= $doctor['sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
        <option value="Other" <?= $doctor['sex'] === 'Other' ? 'selected' : '' ?>>Other</option>
    </select>
    <small class="text-danger d-none" data-error="sex"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Address</label>
    <input type="text" name="address" class="form-control" maxlength="200"
           value="<?= $doctor['address'] ?>">
    <small class="text-danger d-none" data-error="address"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Doctor Contact Number <span class="text-danger">*</span></label>
    <input type="tel" name="contact_number" class="form-control" maxlength="11" 
           pattern="09\d{9}" title="Exactly 11 digits starting with 09 required"
           inputmode="numeric" placeholder="09XXXXXXXXX"
           value="<?= $doctor['contact_number'] ?>" required>
    <small class="text-danger d-none" data-error="contact_number"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Email Address</label>
    <input type="email" name="email" class="form-control" maxlength="100"
           value="<?= $doctor['email'] ?>">
    <small class="text-danger d-none" data-error="email"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Emergency Contact Person</label>
    <input type="text" name="emergency_contact_person" class="form-control" maxlength="100"
           pattern="[a-zA-Z\s'-]+" title="Letters only"
           value="<?= $doctor['emergency_contact_person'] ?>">
    <small class="text-danger d-none" data-error="emergency_contact_person"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Emergency Contact Number</label>
    <input type="tel" name="emergency_contact_number" class="form-control" maxlength="11"
           pattern="09\d{9}" title="Exactly 11 digits starting with 09 required"
           inputmode="numeric" placeholder="09XXXXXXXXX"
           value="<?= $doctor['emergency_contact_number'] ?>">
    <small class="text-danger d-none" data-error="emergency_contact_number"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Emergency Email</label>
    <input type="email" name="emergency_email" class="form-control" maxlength="100"
           value="<?= $doctor['emergency_email'] ?>">
    <small class="text-danger d-none" data-error="emergency_email"></small>
</div>

<hr class="mt-4">

<div class="col-12">
    <h6>Days Available</h6>
</div>

<?php foreach ($days as $day): ?>
<div class="col-md-3">
    <div class="form-check">
        <input type="checkbox" name="available_days[]" value="<?= $day ?>"
               class="form-check-input" id="edit_available_<?= $day ?>"
               <?= in_array($day, $available_days) ? 'checked' : '' ?>>
        <label class="form-check-label" for="edit_available_<?= $day ?>"><?= $day ?></label>
    </div>
</div>
<?php endforeach; ?>

<small class="text-danger d-none" data-error="available_days"></small>

<hr class="mt-4">

<div class="col-12">
    <h6>Time Available (Per Hour)</h6>
</div>

<?php foreach ($times as $time): 
    $hour = (int)substr($time, 0, 2);
    $startTime = date('g:ia', strtotime($time));
    $endHour = str_pad($hour + 1, 2, '0', STR_PAD_LEFT);
    $endTime = date('g:ia', strtotime($endHour . ':00'));
    $timeRange = $startTime . ' - ' . $endTime;
    ?>
<div class="col-md-3">
    <div class="form-check">
        <input type="checkbox" name="available_times[]" value="<?= $time ?>"
               class="form-check-input" id="edit_time_<?= str_replace(':', '', $time) ?>"
               <?= in_array($time, $available_times) ? 'checked' : '' ?>>
        <label class="form-check-label" for="edit_time_<?= str_replace(':', '', $time) ?>"><?= $timeRange ?></label>
    </div>
</div>
<?php endforeach; ?>

<small class="text-danger d-none" data-error="available_times"></small>

</div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeDoctorEditModalBtn">Close</button>
    <button type="submit" class="btn btn-warning">Update Doctor</button>
</div>

</form>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format name field - capitalize each word when there are spaces
    function formatNameField(value) {
        return value
            .toLowerCase()
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }

    // Real-time validation function for edit form
    function validateEditDoctorField(field) {
        const fieldName = field.name;
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Last name validation
        if (fieldName === 'last_name') {
            if (!value) {
                errorMessage = 'Last name is required';
                isValid = false;
            } else if (value.length < 2) {
                errorMessage = 'Last name must be at least 2 characters';
                isValid = false;
            } else if (!/^[a-zA-Z\s'-]+$/.test(value)) {
                errorMessage = 'Only letters, spaces, hyphens, and apostrophes allowed';
                isValid = false;
            }
        }

        // First name validation
        if (fieldName === 'first_name') {
            if (!value) {
                errorMessage = 'First name is required';
                isValid = false;
            } else if (value.length < 2) {
                errorMessage = 'First name must be at least 2 characters';
                isValid = false;
            } else if (!/^[a-zA-Z\s'-]+$/.test(value)) {
                errorMessage = 'Only letters, spaces, hyphens, and apostrophes allowed';
                isValid = false;
            }
        }

        // Middle initial validation
        if (fieldName === 'middle_initial') {
            if (value && !/^[a-zA-Z]$/.test(value)) {
                errorMessage = 'Middle initial must be a single letter';
                isValid = false;
            }
        }

        // Suffix validation
        if (fieldName === 'suffix') {
            if (value) {
                const validSuffixes = ['Sr.', 'Jr.', 'I', 'II', 'III', 'IV', 'V'];
                if (!validSuffixes.includes(value)) {
                    errorMessage = 'Please select a valid suffix';
                    isValid = false;
                }
            }
        }

        // Date of birth validation
        if (fieldName === 'date_of_birth') {
            if (value) {
                const dob = new Date(value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                if (dob.getFullYear() < 1920) {
                    errorMessage = 'Date of birth cannot be before 1920';
                    isValid = false;
                } else if (dob > today) {
                    errorMessage = 'Date of birth cannot be in the future';
                    isValid = false;
                } else if (age < 18) {
                    errorMessage = 'Doctor must be at least 18 years old';
                    isValid = false;
                } else if (age > 100) {
                    errorMessage = 'Please enter a valid date of birth';
                    isValid = false;
                }
            }
        }

        // Sex validation
        if (fieldName === 'sex') {
            if (!value) {
                errorMessage = 'Sex selection is required';
                isValid = false;
            }
        }

        // Address validation
        if (fieldName === 'address') {
            if (value && value.length > 200) {
                errorMessage = 'Address must not exceed 200 characters';
                isValid = false;
            }
        }

        // Contact number validation
        if (fieldName === 'contact_number') {
            if (!value) {
                errorMessage = 'Doctor contact number is required';
                isValid = false;
            } else if (!/^09\d{9}$/.test(value.replace(/\D/g, ''))) {
                errorMessage = 'Must be 11 digits starting with 09';
                isValid = false;
            }
        }

        // Email validation
        if (fieldName === 'email') {
            if (value) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(value)) {
                    errorMessage = 'Please enter a valid email address';
                    isValid = false;
                } else if (value.length > 100) {
                    errorMessage = 'Email must not exceed 100 characters';
                    isValid = false;
                }
            }
        }

        // Emergency contact person validation
        if (fieldName === 'emergency_contact_person') {
            if (value) {
                if (!/^[a-zA-Z\s'-]+$/.test(value)) {
                    errorMessage = 'Only letters, spaces, hyphens, and apostrophes allowed';
                    isValid = false;
                } else if (value.length > 100) {
                    errorMessage = 'Emergency contact person must not exceed 100 characters';
                    isValid = false;
                }
            }
        }

        // Emergency contact number validation
        if (fieldName === 'emergency_contact_number') {
            if (value && !/^09\d{9}$/.test(value.replace(/\D/g, ''))) {
                errorMessage = 'Must be 11 digits starting with 09';
                isValid = false;
            }
        }

        // Emergency email validation
        if (fieldName === 'emergency_email') {
            if (value) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(value)) {
                    errorMessage = 'Emergency email must be in valid format';
                    isValid = false;
                } else if (value.length > 100) {
                    errorMessage = 'Emergency email must not exceed 100 characters';
                    isValid = false;
                }
            }
        }

        // Display validation result
        if (isValid) {
            field.classList.remove('is-invalid');
            const errorSpan = form.querySelector(`[data-error="${fieldName}"]`);
            if (errorSpan) {
                errorSpan.classList.add('d-none');
            }
        } else if (value) {
            field.classList.add('is-invalid');
            const errorSpan = form.querySelector(`[data-error="${fieldName}"]`);
            if (errorSpan) {
                errorSpan.textContent = errorMessage;
                errorSpan.classList.remove('d-none');
            }
        } else {
            field.classList.remove('is-invalid');
            const errorSpan = form.querySelector(`[data-error="${fieldName}"]`);
            if (errorSpan) {
                errorSpan.classList.add('d-none');
            }
        }

        return isValid;
    }

    // Add real-time validation and formatting to edit form fields
    const form = document.getElementById('editDoctorForm');
    
    // Set max date for DOB input to today and min date to 1920-01-01
    const today = new Date().toISOString().split('T')[0];
    const minDate = '1920-01-01';
    const editDobInput = form.querySelector('[name="date_of_birth"]');
    if (editDobInput) {
        editDobInput.setAttribute('max', today);
        editDobInput.setAttribute('min', minDate);
    }
    
    const fieldsToValidate = ['last_name', 'first_name', 'middle_initial', 'suffix', 'date_of_birth', 'sex', 'address', 'contact_number', 'email', 'emergency_contact_person', 'emergency_contact_number', 'emergency_email'];
    const nameFields = ['last_name', 'first_name', 'emergency_contact_person'];

    // Apply real-time validation to all fields
    fieldsToValidate.forEach(fieldName => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            // Validate and format on input
            field.addEventListener('input', function() {
                // Format name fields
                if (nameFields.includes(fieldName)) {
                    this.value = formatNameField(this.value);
                }
                // Uppercase middle initial
                if (fieldName === 'middle_initial' && this.value) {
                    this.value = this.value.toUpperCase().charAt(0);
                }
                // Real-time validation
                validateEditDoctorField(this);
            });

            // Validate and format on blur
            field.addEventListener('blur', function() {
                // Format name fields
                if (nameFields.includes(fieldName)) {
                    this.value = formatNameField(this.value);
                }
                // Uppercase middle initial
                if (fieldName === 'middle_initial' && this.value) {
                    this.value = this.value.toUpperCase().charAt(0);
                }
                // Real-time validation
                validateEditDoctorField(this);
            });

            // Validate on change
            field.addEventListener('change', function() {
                validateEditDoctorField(this);
            });
        }
    });

    // Suffix validation
    const suffixField = form.querySelector('[name="suffix"]');
    if (suffixField) {
        suffixField.addEventListener('change', function() {
            validateEditDoctorField(this);
        });
    }

    // Handle Edit Doctor Form Submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Clear previous errors
        clearDoctorEditFormErrors();
        document.getElementById('doctorEditFormMessages').innerHTML = '';

        const formData = new FormData(this);
        
        try {
            const response = await fetch('update_doctor.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Show success message
                const successHtml = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>✓ Success!</strong> ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                document.getElementById('doctorEditFormMessages').innerHTML = successHtml;

                // Close modal and reload after 2 seconds
                setTimeout(() => {
                    document.getElementById('closeDoctorEditModalBtn').click();
                    location.reload();
                }, 2000);
            } else if (data.errors) {
                // Display field-level errors only (no banner)
                for (const [field, message] of Object.entries(data.errors)) {
                    // Add red border to field
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                    }

                    // Display error message below field
                    const errorSpan = form.querySelector(`[data-error="${field}"]`);
                    if (errorSpan) {
                        errorSpan.textContent = message;
                        errorSpan.classList.remove('d-none');
                    }
                }
            }
        } catch (error) {
            console.error('Form submission error:', error);
        }
    });

    // Clear error styles when user starts typing
    form.addEventListener('input', function(e) {
        if (e.target.classList.contains('is-invalid')) {
            e.target.classList.remove('is-invalid');
            const errorSpan = form.querySelector(`[data-error="${e.target.name}"]`);
            if (errorSpan) {
                errorSpan.classList.add('d-none');
            }
        }
    });

    // Clear error styles when user changes select/checkbox
    form.addEventListener('change', function(e) {
        if (e.target.classList.contains('is-invalid')) {
            e.target.classList.remove('is-invalid');
            const errorSpan = form.querySelector(`[data-error="${e.target.name}"]`);
            if (errorSpan) {
                errorSpan.classList.add('d-none');
            }
        }
    });
});

function clearDoctorEditFormErrors() {
    // Remove all invalid classes
    document.querySelectorAll('#editDoctorForm .is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });

    // Hide all error messages
    document.querySelectorAll('#editDoctorForm [data-error]').forEach(el => {
        el.classList.add('d-none');
    });
}
</script>

</body>
</html>