<?php
require '../../components/db.php';

if (!isset($_GET['id'])) {
    header("Location: patients.php");
    exit;
}

$patient_id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient not found.");
}

// Compute Age
$dob = new DateTime($patient['date_of_birth']);
$today = new DateTime();
$age = $today->diff($dob)->y;
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Patient</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body { font-size: 16px; }
        .card { border-radius: 12px; }
        .info-label { font-weight: 600; }
        .section-title { font-weight: 700; font-size: 18px; }
    </style>
</head>
<body>

<?php include '../../components/sidebar.php'; ?>

<div class="content-wrapper p-4">
    <div class="container-fluid">

        <div class="card shadow-lg mt-4">
            <div class="card-body">

                <!-- DISPLAY MESSAGES -->
                <?php if (isset($_GET['updated'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>✓ Success!</strong> Patient information has been updated successfully.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>⚠️ Validation Error:</strong> <?= htmlspecialchars($_GET['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold">Patient Information</h4>
                    <div>
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal">
                            Edit Patient
                        </button>
                        <a href="patient_visit_history.php?id=<?= $patient_id ?>" class="btn btn-info">
                            View Visit History
                        </a>
                        <a href="patients.php" class="btn btn-secondary">Back</a>
                    </div>
                </div>

                <!-- BASIC INFORMATION -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="info-label">Full Name</div>
                        <div>
                            <?= $patient['last_name'] ?>,
                            <?= $patient['first_name'] ?>
                            <?= $patient['middle_initial'] ? $patient['middle_initial'].'.' : '' ?>
                            <?= $patient['suffix'] ?>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="info-label">Age</div>
                        <div><?= $age ?></div>
                    </div>

                    <div class="col-md-2">
                        <div class="info-label">Sex</div>
                        <div><?= $patient['sex'] ?></div>
                    </div>

                    <div class="col-md-4">
                        <div class="info-label">Status</div>
                        <div><span class="badge <?= $patient['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($patient['status']) ?></span></div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="info-label">Date of Birth</div>
                        <div><?= date('F d, Y', strtotime($patient['date_of_birth'])) ?></div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-label">Registered Date</div>
                        <div><?= date('F d, Y', strtotime($patient['registered_date'])) ?></div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="info-label">Address</div>
                    <div><?= $patient['address'] ?></div>
                </div>

                <hr>

                <!-- CONTACT INFO -->
                <div class="section-title mb-3">Contact Information</div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="info-label">Patient Contact Number</div>
                        <div><?= $patient['contact_number'] ?></div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-label">Email Address</div>
                        <div><?= $patient['email'] ?></div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-label">Emergency Contact Person</div>
                        <div><?= $patient['emergency_contact_person'] ?></div>
                    </div>

                    <div class="col-md-6 mt-3">
                        <div class="info-label">Emergency Contact Number</div>
                        <div><?= $patient['emergency_contact_number'] ?></div>
                    </div>

                    <div class="col-md-6 mt-3">
                        <div class="info-label">Emergency Email</div>
                        <div><?= $patient['emergency_email'] ?></div>
                    </div>
                </div>


                <hr>


            </div>
        </div>
    </div>
</div>


<!-- ================= EDIT MODAL ================= -->
<div class="modal fade" id="editModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editPatientForm">
                <input type="hidden" name="patient_id" value="<?= $patient['patient_id'] ?>">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Patient Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" id="closeEditModalBtn"></button>
                </div>

                <div class="modal-body">

                    <!-- Form Status Messages -->
                    <div id="editFormMessages"></div>

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" maxlength="100" minlength="2"
                                   pattern="[a-zA-Z\s'-]+" title="Only letters, spaces, hyphens and apostrophes allowed. Minimum 2 characters."
                                   value="<?= htmlspecialchars($patient['last_name']) ?>" required>
                            <small class="text-danger d-none" data-error="last_name"></small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" maxlength="100" minlength="2"
                                   pattern="[a-zA-Z\s'-]+" title="Only letters, spaces, hyphens and apostrophes allowed. Minimum 2 characters."
                                   value="<?= htmlspecialchars($patient['first_name']) ?>" required>
                            <small class="text-danger d-none" data-error="first_name"></small>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">M.I.</label>
                            <input type="text" name="middle_initial" class="form-control" maxlength="1"
                                   pattern="[a-zA-Z]" title="Single letter only"
                                   value="<?= htmlspecialchars($patient['middle_initial']) ?>">
                            <small class="text-danger d-none" data-error="middle_initial"></small>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Suffix</label>
                            <select name="suffix" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="Sr." <?= $patient['suffix']=='Sr.'?'selected':'' ?>>Sr. (Senior)</option>
                                <option value="Jr." <?= $patient['suffix']=='Jr.'?'selected':'' ?>>Jr. (Junior)</option>
                                <option value="I" <?= $patient['suffix']=='I'?'selected':'' ?>>I</option>
                                <option value="II" <?= $patient['suffix']=='II'?'selected':'' ?>>II</option>
                                <option value="III" <?= $patient['suffix']=='III'?'selected':'' ?>>III</option>
                                <option value="IV" <?= $patient['suffix']=='IV'?'selected':'' ?>>IV</option>
                                <option value="V" <?= $patient['suffix']=='V'?'selected':'' ?>>V</option>
                            </select>
                            <small class="text-danger d-none" data-error="suffix"></small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="form-control" id="editDobInput"
                                   value="<?= $patient['date_of_birth'] ?>" required>
                            <small class="text-danger d-none" data-error="date_of_birth"></small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Sex <span class="text-danger">*</span></label>
                            <select name="sex" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="Male" <?= $patient['sex']=='Male'?'selected':'' ?>>Male</option>
                                <option value="Female" <?= $patient['sex']=='Female'?'selected':'' ?>>Female</option>
                                <option value="Other" <?= $patient['sex']=='Other'?'selected':'' ?>>Other</option>
                            </select>
                            <small class="text-danger d-none" data-error="sex"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" maxlength="200"
                                   value="<?= htmlspecialchars($patient['address']) ?>">
                            <small class="text-danger d-none" data-error="address"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Patient Contact Number <span class="text-danger">*</span></label>
                            <input type="tel" name="contact_number" class="form-control" maxlength="11"
                                   pattern="09\d{9}" title="Exactly 11 digits starting with 09 required"
                                   inputmode="numeric" placeholder="09XXXXXXXXX"
                                   value="<?= htmlspecialchars($patient['contact_number']) ?>" required>
                            <small class="text-danger d-none" data-error="contact_number"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" maxlength="100"
                                   value="<?= htmlspecialchars($patient['email']) ?>">
                            <small class="text-danger d-none" data-error="email"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Emergency Contact Person</label>
                            <input type="text" name="emergency_contact_person" class="form-control" maxlength="100"
                                   pattern="[a-zA-Z\s'-]+" title="Letters only"
                                   value="<?= htmlspecialchars($patient['emergency_contact_person']) ?>">
                            <small class="text-danger d-none" data-error="emergency_contact_person"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Emergency Contact Number</label>
                            <input type="tel" name="emergency_contact_number" class="form-control" maxlength="11"
                                   pattern="09\d{9}" title="Exactly 11 digits starting with 09 required"
                                   inputmode="numeric" placeholder="09XXXXXXXXX"
                                   value="<?= htmlspecialchars($patient['emergency_contact_number']) ?>">
                            <small class="text-danger d-none" data-error="emergency_contact_number"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Emergency Email</label>
                            <input type="email" name="emergency_email" class="form-control" maxlength="100"
                                   value="<?= htmlspecialchars($patient['emergency_email']) ?>">
                            <small class="text-danger d-none" data-error="emergency_email"></small>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Update Patient</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>

            </form>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set max date for DOB input to today
    const today = new Date().toISOString().split('T')[0];
    const editDobInput = document.getElementById('editDobInput');
    if (editDobInput) {
        editDobInput.setAttribute('max', today);
    }

    // Format name field - capitalize each word when there are spaces
    function formatNameField(value) {
        if (!value) return '';
        
        // Preserve trailing spaces while typing
        const trailingSpaces = value.match(/\s+$/)?.[0] || '';
        const trimmed = value.trim();
        
        if (!trimmed) return trailingSpaces;
        
        const formatted = trimmed.split(/\s+/).map(word => {
            if (!word) return '';
            return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
        }).join(' ');
        
        return formatted + trailingSpaces;
    }

    // Real-time validation function for edit form
    function validateEditField(field) {
        let isValid = true;
        let errorMessage = '';
        const value = field.value.trim();
        const fieldName = field.name;

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
                
                if (dob > today) {
                    errorMessage = 'Date of birth cannot be in the future';
                    isValid = false;
                } else if (age === 0) {
                    errorMessage = 'Patient must be at least 1 year old';
                    isValid = false;
                } else if (age < 0 || age > 150) {
                    errorMessage = 'Please enter a valid date of birth';
                    isValid = false;
                }
            }
        }

        // Patient contact number validation
        if (fieldName === 'patient_number') {
            if (!value) {
                errorMessage = 'Patient contact number is required';
                isValid = false;
            } else if (!/^09\d{9}$/.test(value.replace(/\D/g, ''))) {
                errorMessage = 'Must be 11 digits starting with 09';
                isValid = false;
            }
        }

        // Emergency contact number validation (optional)
        if (fieldName === 'emergency_contact_number') {
            if (value && !/^09\d{9}$/.test(value.replace(/\D/g, ''))) {
                errorMessage = 'Must be 11 digits starting with 09';
                isValid = false;
            }
        }

        // Emergency contact person validation (optional)
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

        // Nationality validation (optional)
        if (fieldName === 'nationality') {
            if (value) {
                if (!/^[a-zA-Z\s]+$/.test(value)) {
                    errorMessage = 'Only letters and spaces allowed';
                    isValid = false;
                } else if (value.length > 50) {
                    errorMessage = 'Nationality must not exceed 50 characters';
                    isValid = false;
                }
            }
        }

        // Email address validation (optional)
        if (fieldName === 'email_address') {
            if (value) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(value)) {
                    errorMessage = 'Please enter a valid email address';
                    isValid = false;
                } else if (value.length > 100) {
                    errorMessage = 'Email address must not exceed 100 characters';
                    isValid = false;
                }
            }
        }

        // Suffix validation (optional)
        if (fieldName === 'suffix') {
            if (value) {
                const validSuffixes = ['Sr.', 'Jr.', 'I', 'II', 'III', 'IV', 'V'];
                if (!validSuffixes.includes(value)) {
                    errorMessage = 'Please select a valid suffix';
                    isValid = false;
                }
            }
        }

        // Middle initial validation (optional)
        if (fieldName === 'middle_initial') {
            if (value) {
                if (!/^[a-zA-Z]$/.test(value)) {
                    errorMessage = 'Middle initial must be a single letter';
                    isValid = false;
                }
            }
        }

        // Apply visual feedback
        if (isValid) {
            field.classList.remove('is-invalid');
            const errorSpan = document.querySelector(`#editPatientForm [data-error="${fieldName}"]`);
            if (errorSpan) {
                errorSpan.classList.add('d-none');
                errorSpan.textContent = '';
            }
        } else {
            field.classList.add('is-invalid');
            const errorSpan = document.querySelector(`#editPatientForm [data-error="${fieldName}"]`);
            if (errorSpan) {
                errorSpan.textContent = errorMessage;
                errorSpan.classList.remove('d-none');
            }
        }

        return isValid;
    }

    // Add real-time validation to edit form fields
    const editForm = document.getElementById('editPatientForm');
    if (editForm) {
        const fieldsToValidate = ['last_name', 'first_name', 'middle_initial', 'suffix', 'date_of_birth', 'nationality', 'patient_number', 'email_address', 'emergency_contact_person', 'emergency_contact_number'];
        const nameFields = ['last_name', 'first_name', 'emergency_contact_person', 'nationality'];
        
        fieldsToValidate.forEach(fieldName => {
            const field = editForm.querySelector(`[name="${fieldName}"]`);
            if (field) {
                // Validate and format on input/change
                field.addEventListener('input', function() {
                    // Format name fields (capitalize each word)
                    if (nameFields.includes(fieldName)) {
                        this.value = formatNameField(this.value);
                    }
                    // Uppercase middle initial
                    if (fieldName === 'middle_initial' && this.value) {
                        this.value = this.value.toUpperCase().charAt(0);
                    }
                    validateEditField(this);
                });
                
                field.addEventListener('change', function() {
                    // Format name fields
                    if (nameFields.includes(fieldName)) {
                        this.value = formatNameField(this.value);
                    }
                    // Uppercase middle initial
                    if (fieldName === 'middle_initial' && this.value) {
                        this.value = this.value.toUpperCase().charAt(0);
                    }
                    validateEditField(this);
                });
                
                // Validate on blur (when leaving field)
                field.addEventListener('blur', function() {
                    // Format name fields
                    if (nameFields.includes(fieldName)) {
                        this.value = formatNameField(this.value);
                    }
                    // Uppercase middle initial
                    if (fieldName === 'middle_initial' && this.value) {
                        this.value = this.value.toUpperCase().charAt(0);
                    }
                    validateEditField(this);
                });
            }
        });

        // Edit Patient Form AJAX Handler
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Clear previous errors
            clearEditFormErrors();
            document.getElementById('editFormMessages').innerHTML = '';

            const formData = new FormData(this);
            
            try {
                const response = await fetch('update_patient.php', {
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
                    document.getElementById('editFormMessages').innerHTML = successHtml;

                    // Close modal after 2 seconds and reload
                    setTimeout(() => {
                        document.getElementById('closeEditModalBtn').click();
                        location.reload();
                    }, 2000);
                } else if (data.errors) {
                    // Display field-level errors
                    let errorHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                    errorHtml += '<strong>✗ Validation Errors:</strong><br>';
                    
                    for (const [field, message] of Object.entries(data.errors)) {
                        // Add red border to field
                        const input = document.querySelector(`#editPatientForm [name="${field}"]`);
                        if (input) {
                            input.classList.add('is-invalid');
                        }

                        // Display error message
                        const errorSpan = document.querySelector(`#editPatientForm [data-error="${field}"]`);
                        if (errorSpan) {
                            errorSpan.textContent = message;
                            errorSpan.classList.remove('d-none');
                        }

                        errorHtml += `• ${message}<br>`;
                    }
                    errorHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    document.getElementById('editFormMessages').innerHTML = errorHtml;
                } else {
                    // Generic error
                    const errorHtml = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>✗ Error:</strong> ${data.message || 'An error occurred'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                    document.getElementById('editFormMessages').innerHTML = errorHtml;
                }
            } catch (error) {
                const errorHtml = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>✗ Error:</strong> Failed to submit form. Please check your connection.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                document.getElementById('editFormMessages').innerHTML = errorHtml;
                console.error(error);
            }
        });

        // Clear error styles when user starts typing
        editForm.addEventListener('input', function(e) {
            if (e.target.classList.contains('is-invalid')) {
                e.target.classList.remove('is-invalid');
                const errorSpan = document.querySelector(`#editPatientForm [data-error="${e.target.name}"]`);
                if (errorSpan) {
                    errorSpan.classList.add('d-none');
                }
            }
        });

        // Clear error styles when user changes select/dropdown
        editForm.addEventListener('change', function(e) {
            if (e.target.classList.contains('is-invalid')) {
                e.target.classList.remove('is-invalid');
                const errorSpan = document.querySelector(`#editPatientForm [data-error="${e.target.name}"]`);
                if (errorSpan) {
                    errorSpan.classList.add('d-none');
                }
            }
        });
    }
});

function clearEditFormErrors() {
    const form = document.getElementById('editPatientForm');
    if (!form) return;

    // Remove all invalid classes
    form.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });

    // Hide all error messages
    form.querySelectorAll('[data-error]').forEach(el => {
        el.classList.add('d-none');
    });
}
</script>