<?php
require '../../components/db.php';

// Check if viewing archived doctors
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';

// Fetch doctors
if (!$show_archived) {
    $stmt = $pdo->query("SELECT * FROM doctors WHERE is_archived = 0 ORDER BY last_name ASC");
} else {
    $stmt = $pdo->query("SELECT * FROM doctors WHERE is_archived = 1 ORDER BY last_name ASC");
}
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Doctors Information</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { font-size: 16px; }
        .table th { font-size: 16px; }
        .table td { font-size: 15px; }
        .btn-sm { padding: 6px 12px; }
        
        /* Form validation styling */
        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        #doctorsTable {
    table-layout: fixed;
    width: 100%;
}

#doctorsTable td {
    word-wrap: break-word;
    overflow-wrap: break-word;
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

<?php if (isset($_GET['archived'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        ✓ Doctor archived successfully
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['restored'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        ✓ Doctor restored successfully
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['permanently_deleted'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        ✓ Doctor permanently deleted
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow mt-4">
<div class="card-body">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold">Doctors Information</h4>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex">
            <select name="show_archived" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="0" <?= !$show_archived ? 'selected' : '' ?>>Active Doctors</option>
                <option value="1" <?= $show_archived ? 'selected' : '' ?>>Archived Doctors</option>
            </select>
        </form>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
            + Add Doctor
        </button>
    </div>
</div>

<table id="doctorsTable" class="table table-striped table-hover table-bordered align-middle">
<thead class="table-dark">
<tr>
    <th>Doctor ID</th>
    <th>Name</th>
    <th>Contact</th>
    <th>Email</th>
    <th>Registered Date</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php foreach ($doctors as $doctor): 
    $fullName = $doctor['last_name'] . ', ' . $doctor['first_name'];
    if($doctor['middle_initial']) $fullName .= ' '.$doctor['middle_initial'].'.';
    if($doctor['suffix']) $fullName .= ', '.$doctor['suffix'];
?>
<tr>
    <td><?= $doctor['doctor_id'] ?></td>
    <td><?= "Dr. ". $fullName ?></td>
    <td><?= $doctor['contact_number'] ?></td>
    <td><?= $doctor['email'] ?></td>
    <td><?= date('M d, Y', strtotime($doctor['created_at'])) ?></td>
    <td>
        <?php if (!$show_archived): ?>
        <a href="view_doctor.php?id=<?= $doctor['doctor_id'] ?>" class="btn btn-info btn-sm">View</a>
        <a href="doctor_archive_handler.php?action=archive&id=<?= $doctor['doctor_id'] ?>" 
           class="btn btn-secondary btn-sm"
           onclick="return confirm('Archive this doctor?')">
           Archive
        </a>
        <?php else: ?>
        <a href="doctor_archive_handler.php?action=restore&id=<?= $doctor['doctor_id'] ?>" 
           class="btn btn-info btn-sm"
           onclick="return confirm('Are you sure you want to restore this doctor?');">
           Restore
        </a>
        <a href="doctor_archive_handler.php?action=permanently_delete&id=<?= $doctor['doctor_id'] ?>" 
           class="btn btn-danger btn-sm"
           onclick="return confirm('Permanently delete? This cannot be undone.')">
           Delete
        </a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

</div>
</div>

</div>
</div>


<!-- ================= ADD DOCTOR MODAL ================= -->

<div class="modal fade" id="addDoctorModal">
<div class="modal-dialog modal-lg">
<div class="modal-content">

<form id="addDoctorForm">

<div class="modal-header">
    <h5 class="modal-title">Add Doctor</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<!-- Success Message -->
<div id="doctorFormMessages"></div>

<div class="row g-3">

<div class="col-md-4">
    <label class="form-label">Last Name <span class="text-danger">*</span></label>
    <input type="text" name="last_name" class="form-control" maxlength="100" minlength="2"
           pattern="[a-zA-Z\s'-]+" title="Only letters, spaces, hyphens and apostrophes allowed. Minimum 2 characters."
           required>
    <small class="text-danger d-none" data-error="last_name"></small>
</div>

<div class="col-md-4">
    <label class="form-label">First Name <span class="text-danger">*</span></label>
    <input type="text" name="first_name" class="form-control" maxlength="100" minlength="2"
           pattern="[a-zA-Z\s'-]+" title="Only letters, spaces, hyphens and apostrophes allowed. Minimum 2 characters."
           required>
    <small class="text-danger d-none" data-error="first_name"></small>
</div>

<div class="col-md-2">
    <label class="form-label">M.I.</label>
    <input type="text" name="middle_initial" class="form-control" maxlength="1"
           pattern="[a-zA-Z]" title="Single letter only">
    <small class="text-danger d-none" data-error="middle_initial"></small>
</div>

<div class="col-md-2">
    <label class="form-label">Suffix</label>
    <select name="suffix" class="form-select">
        <option value="">-- Select --</option>
        <option value="Sr.">Sr. (Senior)</option>
        <option value="Jr.">Jr. (Junior)</option>
        <option value="I">I</option>
        <option value="II">II</option>
        <option value="III">III</option>
        <option value="IV">IV</option>
        <option value="V">V</option>
    </select>
    <small class="text-danger d-none" data-error="suffix"></small>
</div>

<div class="col-md-3">
    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
    <input type="date" name="date_of_birth" class="form-control" id="dobInput" required>
    <small class="text-danger d-none" data-error="date_of_birth"></small>
</div>

<div class="col-md-3">
    <label class="form-label">Sex <span class="text-danger">*</span></label>
    <select name="sex" class="form-select" required>
        <option value="">-- Select --</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
    </select>
    <small class="text-danger d-none" data-error="sex"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Address</label>
    <input type="text" name="address" class="form-control" maxlength="200">
    <small class="text-danger d-none" data-error="address"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Doctor Contact Number <span class="text-danger">*</span></label>
    <input type="tel" name="contact_number" class="form-control" maxlength="11" 
           pattern="09\d{9}" title="Exactly 11 digits starting with 09 required"
           inputmode="numeric" placeholder="09XXXXXXXXX" required>
    <small class="text-danger d-none" data-error="contact_number"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Email Address</label>
    <input type="email" name="email" class="form-control" maxlength="100">
    <small class="text-danger d-none" data-error="email"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Emergency Contact Person</label>
    <input type="text" name="emergency_contact_person" class="form-control" maxlength="100"
           pattern="[a-zA-Z\s'-]+" title="Letters only">
    <small class="text-danger d-none" data-error="emergency_contact_person"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Emergency Contact Number</label>
    <input type="tel" name="emergency_contact_number" class="form-control" maxlength="11"
           pattern="09\d{9}" title="Exactly 11 digits starting with 09 required"
           inputmode="numeric" placeholder="09XXXXXXXXX">
    <small class="text-danger d-none" data-error="emergency_contact_number"></small>
</div>

<div class="col-md-6">
    <label class="form-label">Emergency Email</label>
    <input type="email" name="emergency_email" class="form-control" maxlength="100">
    <small class="text-danger d-none" data-error="emergency_email"></small>
</div>

<hr class="mt-4">

<div class="col-12">
    <h6>Days Available</h6>
</div>

<?php
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
foreach ($days as $day):
?>
<div class="col-md-3">
    <div class="form-check">
        <input type="checkbox" name="available_days[]" value="<?= $day ?>" class="form-check-input" id="available_<?= $day ?>" checked>
        <label class="form-check-label" for="available_<?= $day ?>"><?= $day ?></label>
    </div>
</div>
<?php endforeach; ?>

<small class="text-danger d-none" data-error="available_days"></small>

<hr class="mt-4">

<div class="col-12">
    <h6>Time Available (Per Hour)</h6>
</div>

<?php
$times = ['10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00'];
foreach ($times as $time):
?>
<div class="col-md-3">
    <div class="form-check">
        <input type="checkbox" name="available_times[]" value="<?= $time ?>" class="form-check-input" id="time_<?= str_replace(':', '', $time) ?>">
        <label class="form-check-label" for="time_<?= str_replace(':', '', $time) ?>"><?= date("g:i A", strtotime($time)) ?></label>
    </div>
</div>
<?php endforeach; ?>

<small class="text-danger d-none" data-error="available_times"></small>

</div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeDoctorModalBtn">Close</button>
    <button type="submit" class="btn btn-primary">Save Doctor</button>
</div>

</form>

</div>
</div>
</div>


<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#doctorsTable').DataTable({
        pageLength: 10,
        lengthMenu: [10, 30, 50],
        columnDefs: [{ orderable: false, targets: 3 }]
    });

    // Format name field - capitalize each word when there are spaces
    function formatNameField(value) {
        return value
            .toLowerCase()
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }

    // Real-time validation function for doctor form
    function validateDoctorField(field) {
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

    // Add real-time validation and formatting to form fields
    const form = document.getElementById('addDoctorForm');
    
    // Set max date for DOB input to today and min date to 1920-01-01
    const today = new Date().toISOString().split('T')[0];
    const minDate = '1920-01-01';
    const dobInput = form.querySelector('[name="date_of_birth"]');
    if (dobInput) {
        dobInput.setAttribute('max', today);
        dobInput.setAttribute('min', minDate);
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
                validateDoctorField(this);
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
                validateDoctorField(this);
            });

            // Validate on change
            field.addEventListener('change', function() {
                validateDoctorField(this);
            });
        }
    });

    // Suffix validation
    const suffixField = form.querySelector('[name="suffix"]');
    if (suffixField) {
        suffixField.addEventListener('change', function() {
            validateDoctorField(this);
        });
    }

    // Handle Add Doctor Form Submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Clear previous errors
        clearDoctorFormErrors();
        document.getElementById('doctorFormMessages').innerHTML = '';

        const formData = new FormData(this);
        
        try {
            const response = await fetch('add_doctor.php', {
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
                document.getElementById('doctorFormMessages').innerHTML = successHtml;

                // Reset form
                form.reset();

                // Close modal and reload after 2 seconds
                setTimeout(() => {
                    document.getElementById('closeDoctorModalBtn').click();
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

function clearDoctorFormErrors() {
    // Remove all invalid classes
    document.querySelectorAll('#addDoctorForm .is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });

    // Hide all error messages
    document.querySelectorAll('#addDoctorForm [data-error]').forEach(el => {
        el.classList.add('d-none');
    });
}
</script>

</body>
</html>