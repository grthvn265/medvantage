<?php
include '../../components/db.php';

// Check if viewing archived patients
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';

// Fetch patients
if (!$show_archived) {
    $stmt = $pdo->query("SELECT * FROM patients WHERE status = 'active' ORDER BY last_name ASC");
} else {
    $stmt = $pdo->query("SELECT * FROM patients WHERE status = 'archive' ORDER BY last_name ASC");
}
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Information System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body {
            font-size: 16px;
        }
        .table th {
            font-size: 16px;
        }
        .table td {
            font-size: 15px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }
        .card {
            border-radius: 12px;
        }
        #patientsTable {
            table-layout: fixed;
            width: 100%;
        }
        #patientsTable td {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>
<body>

<?php include '../../components/sidebar.php'; ?>

<div class="content-wrapper p-4">
    <div class="container-fluid">
        <div class="card shadow mt-4">
            <div class="card-body">

                <!-- DISPLAY MESSAGES -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>✓ Success!</strong> Patient information has been saved successfully.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>✓ Deleted!</strong> Patient has been deleted successfully.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['archived'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        ✓ Patient archived successfully
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['restored'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        ✓ Patient restored successfully
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['permanently_deleted'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        ✓ Patient permanently deleted
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>⚠️ Error:</strong> <?= htmlspecialchars($_GET['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold">Patients Information</h4>
                    <div class="d-flex gap-2">
                        <form method="GET" class="d-flex">
                            <select name="show_archived" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="0" <?= !$show_archived ? 'selected' : '' ?>>Active Patients</option>
                                <option value="1" <?= $show_archived ? 'selected' : '' ?>>Archived Patients</option>
                            </select>
                        </form>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                            + Add Patient
                        </button>
                    </div>
                </div>

                <table id="patientsTable" class="table table-striped table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Age</th>
                            <th>Sex</th>
                            <th>Registered Date</th>
                            <th width="220">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $row): 
                            
                            $dob = new DateTime($row['date_of_birth']);
                            $today = new DateTime();
                            $age = $today->diff($dob)->y;

                            $fullName = $row['last_name'] . ', ' . $row['first_name'];
                            if (!empty($row['middle_initial'])) {
                                $fullName .= ' ' . $row['middle_initial'] . '.';
                            }
                            if (!empty($row['suffix'])) {
                                $fullName .= ', ' . $row['suffix'];
                            }
                        ?>
                        <tr>
                            <td><?= $row['patient_id'] ?></td>
                            <td><?= $fullName ?></td>
                            <td><?= $age ?></td>
                            <td><?= $row['sex'] ?></td>
                            <td><?= date('M d, Y', strtotime($row['registered_date'])) ?></td>
                            <td>
                                <?php if (!$show_archived): ?>
                                    <a href="view_patient.php?id=<?= $row['patient_id'] ?>" class="btn btn-info btn-sm">View</a>
                                
                                <a href="patient_archive_handler.php?action=archive&id=<?= $row['patient_id'] ?>" 
                                   class="btn btn-secondary btn-sm"
                                   onclick="return confirm('Archive this patient?');">
                                   Archive
                                </a>
                                <?php else: ?>
                                <a href="patient_archive_handler.php?action=restore&id=<?= $row['patient_id'] ?>" 
                                   class="btn btn-info btn-sm">
                                   Restore
                                </a>
                                <a href="patient_archive_handler.php?action=permanently_delete&id=<?= $row['patient_id'] ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Permanently delete? This cannot be undone.');"
                                   style="white-space: nowrap;">
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

<!-- Add Patient Modal -->
<div class="modal fade" id="addPatientModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addPatientForm">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" id="closeModalBtn"></button>
                </div>
                <div class="modal-body">

                    <!-- Form Status Messages -->
                    <div id="formMessages"></div>

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
                            <label class="form-label">Patient Contact Number <span class="text-danger">*</span></label>
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

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Patient</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    $('#patientsTable').DataTable({
        pageLength: 10,
        lengthMenu: [10, 30, 50],
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: 4 }
        ]
    });

    // Set max date for DOB input to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('dobInput').setAttribute('max', today);

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

    // Real-time validation function
    function validateField(field) {
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
            const errorSpan = document.querySelector(`[data-error="${fieldName}"]`);
            if (errorSpan) {
                errorSpan.classList.add('d-none');
                errorSpan.textContent = '';
            }
        } else {
            field.classList.add('is-invalid');
            const errorSpan = document.querySelector(`[data-error="${fieldName}"]`);
            if (errorSpan) {
                errorSpan.textContent = errorMessage;
                errorSpan.classList.remove('d-none');
            }
        }

        return isValid;
    }

    // Add real-time validation to form fields
    const form = document.getElementById('addPatientForm');
    const fieldsToValidate = ['last_name', 'first_name', 'middle_initial', 'suffix', 'date_of_birth', 'nationality', 'patient_number', 'email_address', 'emergency_contact_person', 'emergency_contact_number'];
    
    const nameFields = ['last_name', 'first_name', 'emergency_contact_person', 'nationality'];
    
    fieldsToValidate.forEach(fieldName => {
        const field = form.querySelector(`[name="${fieldName}"]`);
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
                validateField(this);
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
                validateField(this);
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
                validateField(this);
            });
        }
    });

    // Add Patient Form AJAX Handler
    document.getElementById('addPatientForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        // Clear previous errors
        clearFormErrors();
        document.getElementById('formMessages').innerHTML = '';

        const formData = new FormData(this);
        
        try {
            const response = await fetch('add_patient.php', {
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
                document.getElementById('formMessages').innerHTML = successHtml;

                // Reset form
                document.getElementById('addPatientForm').reset();

                // Close modal after 2 seconds
                setTimeout(() => {
                    document.getElementById('closeModalBtn').click();
                    location.reload();
                }, 2000);
            } else if (data.errors) {
                // Display field-level errors
                let errorHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                errorHtml += '<strong>✗ Validation Errors:</strong><br>';
                
                for (const [field, message] of Object.entries(data.errors)) {
                    // Add red border to field
                    const input = document.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                    }

                    // Display error message
                    const errorSpan = document.querySelector(`[data-error="${field}"]`);
                    if (errorSpan) {
                        errorSpan.textContent = message;
                        errorSpan.classList.remove('d-none');
                    }

                    errorHtml += `• ${message}<br>`;
                }
                errorHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                document.getElementById('formMessages').innerHTML = errorHtml;
            } else {
                // Generic error
                const errorHtml = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>✗ Error:</strong> ${data.message || 'An error occurred'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                document.getElementById('formMessages').innerHTML = errorHtml;
            }
        } catch (error) {
            const errorHtml = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>✗ Error:</strong> Failed to submit form. Please check your connection.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.getElementById('formMessages').innerHTML = errorHtml;
            console.error(error);
        }
    });

    // Clear error styles when user starts typing
    document.getElementById('addPatientForm').addEventListener('input', function(e) {
        if (e.target.classList.contains('is-invalid')) {
            e.target.classList.remove('is-invalid');
            const errorSpan = document.querySelector(`[data-error="${e.target.name}"]`);
            if (errorSpan) {
                errorSpan.classList.add('d-none');
            }
        }
    });

    // Clear error styles when user changes select/dropdown
    document.getElementById('addPatientForm').addEventListener('change', function(e) {
        if (e.target.classList.contains('is-invalid')) {
            e.target.classList.remove('is-invalid');
            const errorSpan = document.querySelector(`[data-error="${e.target.name}"]`);
            if (errorSpan) {
                errorSpan.classList.add('d-none');
            }
        }
    });
});

function clearFormErrors() {
    // Remove all invalid classes
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });

    // Hide all error messages
    document.querySelectorAll('[data-error]').forEach(el => {
        el.classList.add('d-none');
    });
}
</script>

</body>
</html>