<?php
require '../../components/db.php';

$currentUser = getCurrentUser($pdo);
if (!$currentUser || $currentUser['role_key'] !== 'super_admin') {
    header('Location: ' . appUrl('/modules/dashboard/index.php?denied=1'));
    exit;
}

$modules = getEnabledModules($pdo, true);

$rolesStmt = $pdo->query("SELECT role_id, role_name, role_key FROM roles WHERE role_key <> 'super_admin' ORDER BY role_name ASC");
$roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

$usersStmt = $pdo->query("
    SELECT
        u.user_id,
        u.username,
        u.full_name,
        u.email,
        u.is_active,
        u.last_login_at,
        u.created_at,
        r.role_key,
        r.role_name,
        GROUP_CONCAT(am.module_label ORDER BY am.sort_order SEPARATOR ', ') AS modules
    FROM users u
    INNER JOIN roles r ON r.role_id = u.role_id
    LEFT JOIN user_module_access uma ON uma.user_id = u.user_id
    LEFT JOIN app_modules am ON am.module_id = uma.module_id AND am.is_enabled = 1
    GROUP BY u.user_id, u.username, u.full_name, u.email, u.is_active, u.last_login_at, u.created_at, r.role_key, r.role_name
    ORDER BY u.created_at DESC
");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$flashSuccess = consumeFlash('flash_success');
$flashError = consumeFlash('flash_error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounts Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .module-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            background: #e9f5f5;
            color: #095c5c;
            font-size: 12px;
            margin: 2px;
        }
        .checklist-card {
            border: 1px solid #dce6eb;
            border-radius: 12px;
            padding: 12px;
            max-height: 220px;
            overflow-y: auto;
            background: #fcfdff;
        }
        .input-error {
            box-shadow: 0 0 0.3rem rgba(220,53,69,0.45) !important;
            border-color: #dc3545 !important;
        }
        .password-toggle-btn {
            border: 1px solid #ced4da;
        }
    </style>
</head>
<body>
<?php include '../../components/sidebar.php'; ?>

<div class="content-wrapper p-4">
    <div class="container-fluid">
        <div class="card shadow mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Accounts Directory</h4>
                    <button id="createAccountBtn" type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="bi bi-person-plus"></i> Create Account
                    </button>
                </div>

                <?php if ($flashSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
                <?php endif; ?>

                <?php if ($flashError): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
                <?php endif; ?>

                <table id="usersTable" class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Modules</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th width="160">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <?= htmlspecialchars($user['full_name']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars((string) $user['email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($user['role_name']) ?></td>
                            <td>
                                <?php if ($user['role_key'] === 'super_admin'): ?>
                                    <span class="module-badge">All modules</span>
                                <?php else: ?>
                                    <?php
                                    $moduleList = array_filter(array_map('trim', explode(',', (string) $user['modules'])));
                                    ?>
                                    <?php foreach ($moduleList as $moduleLabel): ?>
                                        <span class="module-badge"><?= htmlspecialchars($moduleLabel) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) $user['is_active'] === 1): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $user['last_login_at'] ? htmlspecialchars(date('M d, Y h:i A', strtotime($user['last_login_at']))) : '<span class="text-muted">Never</span>' ?>
                            </td>
                            <td>
                                <?php if ($user['role_key'] !== 'super_admin' && $user['username'] !== 'owner'): ?>
                                    <form method="POST" action="toggle_user_status.php" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= (int) $user['is_active'] ?>">
                                        <button type="submit" class="btn btn-sm <?= (int) $user['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                onclick="return confirm('Update account status?')">
                                            <?= (int) $user['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Protected</span>
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

<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="create_user.php" id="createUserForm">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" maxlength="150" minlength="2"
                                   pattern="[A-Za-z][A-Za-z\s'\-]{1,149}"
                                   title="Use letters, spaces, apostrophes, and hyphens only (minimum 2 characters)."
                                   required>
                            <small class="text-danger d-none" data-error="full_name"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" maxlength="150" required>
                            <small class="text-danger d-none" data-error="email"></small>
                            <small class="d-none" id="emailAvailabilityStatus"></small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" maxlength="30" minlength="4"
                                   pattern="[A-Za-z0-9_.]{4,30}"
                                   title="Use 4-30 characters: letters, numbers, underscore, or dot."
                                   required>
                            <small class="text-danger d-none" data-error="username"></small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="createPassword" class="form-control" minlength="8" maxlength="72"
                                       pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,72}"
                                       title="8-72 chars with uppercase, lowercase, number, and special character."
                                       required>
                                <button type="button" class="btn btn-outline-secondary password-toggle-btn" id="toggleCreatePassword" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-danger d-none" data-error="password"></small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= (int) $role['role_id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-danger d-none" data-error="role_id"></small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Module Access Checklist</label>
                            <div class="checklist-card">
                                <div class="row">
                                    <?php foreach ($modules as $module): ?>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-check">
                                                <input class="form-check-input" type="checkbox" name="module_ids[]" value="<?= (int) $module['module_id'] ?>">
                                                <span class="form-check-label">
                                                    <i class="bi <?= htmlspecialchars($module['icon_class']) ?>"></i>
                                                    <?= htmlspecialchars($module['module_label']) ?>
                                                </span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <small class="text-danger d-none" id="moduleChecklistError"></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(function () {
        $('#usersTable').DataTable({
            pageLength: 10,
            order: [[0, 'asc']]
        });

        function setFieldError(fieldName, message) {
            const field = $('[name="' + fieldName + '"]');
            const error = $('[data-error="' + fieldName + '"]');

            if (field.length) {
                field.addClass('input-error');
            }

            if (error.length) {
                error.text(message).removeClass('d-none');
            }
        }

        function clearFieldError(fieldName) {
            const field = $('[name="' + fieldName + '"]');
            const error = $('[data-error="' + fieldName + '"]');

            if (field.length) {
                field.removeClass('input-error');
            }

            if (error.length) {
                error.text('').addClass('d-none');
            }
        }

        function validateField(fieldName) {
            const fullName = ($('[name="full_name"]').val() || '').trim();
            const email = ($('[name="email"]').val() || '').trim();
            const username = ($('[name="username"]').val() || '').trim();
            const password = $('[name="password"]').val() || '';
            const roleId = $('[name="role_id"]').val();

            const namePattern = /^[A-Za-z][A-Za-z\s'\-]{1,149}$/;
            const usernamePattern = /^[A-Za-z0-9_.]{4,30}$/;
            const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,72}$/;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            clearFieldError(fieldName);

            if (fieldName === 'full_name' && !namePattern.test(fullName)) {
                setFieldError('full_name', 'Full name must be at least 2 characters and use letters/spaces only.');
                return false;
            }

            if (fieldName === 'email' && (!emailPattern.test(email) || email.length > 150)) {
                setFieldError('email', 'Enter a valid email address (max 150 characters).');
                return false;
            }

            if (fieldName === 'username' && !usernamePattern.test(username)) {
                setFieldError('username', 'Username must be 4-30 chars and use letters, numbers, underscore, or dot.');
                return false;
            }

            if (fieldName === 'password' && !passwordPattern.test(password)) {
                setFieldError('password', 'Use 8-72 chars with uppercase, lowercase, number, and special character.');
                return false;
            }

            if (fieldName === 'role_id' && !roleId) {
                setFieldError('role_id', 'Role is required.');
                return false;
            }

            return true;
        }

        function setEmailAvailabilityStatus(message, statusClass) {
            const statusEl = $('#emailAvailabilityStatus');
            statusEl.removeClass('d-none text-success text-danger text-muted');
            statusEl.addClass(statusClass || 'text-muted');
            statusEl.text(message || '');

            if (!message) {
                statusEl.addClass('d-none');
            }
        }

        async function checkEmailAvailability() {
            const email = ($('[name="email"]').val() || '').trim();
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(email) || email.length > 150) {
                setEmailAvailabilityStatus('', '');
                return false;
            }

            setEmailAvailabilityStatus('Checking email availability.....', 'text-muted');

            try {
                const response = await fetch('check_email_availability.php?email=' + encodeURIComponent(email), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    setEmailAvailabilityStatus(data.message || 'Unable to validate email availability.', 'text-danger');
                    setFieldError('email', data.message || 'Unable to validate email availability.');
                    return false;
                }

                if (!data.available) {
                    setEmailAvailabilityStatus(data.message || 'Email is already in use.', 'text-danger');
                    setFieldError('email', data.message || 'Email is already in use.');
                    return false;
                }

                clearFieldError('email');
                setEmailAvailabilityStatus(data.message || 'Email is available.', 'text-success');
                return true;
            } catch (error) {
                setEmailAvailabilityStatus('Unable to validate email availability.', 'text-danger');
                setFieldError('email', 'Unable to validate email availability.');
                return false;
            }
        }

        function validateModuleChecklist() {
            if ($('input[name="module_ids[]"]:checked').length === 0) {
                $('#moduleChecklistError').text('Select at least one module for this account.').removeClass('d-none');
                return false;
            }

            $('#moduleChecklistError').text('').addClass('d-none');
            return true;
        }

        function validateCreateAccountForm() {
            const fields = ['full_name', 'email', 'username', 'password', 'role_id'];
            let isValid = true;

            fields.forEach(function (fieldName) {
                if (!validateField(fieldName)) {
                    isValid = false;
                }
            });

            if (!validateModuleChecklist()) {
                isValid = false;
            }

            return isValid;
        }

        const createBtn = document.getElementById('createAccountBtn');
        const modalEl = document.getElementById('createUserModal');
        if (createBtn && modalEl && window.bootstrap) {
            createBtn.addEventListener('click', function () {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            });
        }

        const toggleBtn = document.getElementById('toggleCreatePassword');
        const passwordInput = document.getElementById('createPassword');
        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function () {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                toggleBtn.innerHTML = isHidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            });
        }

        let allowFormSubmit = false;

        $('#createUserForm').on('submit', async function (event) {
            if (allowFormSubmit) {
                return true;
            }

            event.preventDefault();

            if (!validateCreateAccountForm()) {
                return;
            }

            const isEmailAvailable = await checkEmailAvailability();
            if (!isEmailAvailable) {
                return;
            }

            allowFormSubmit = true;
            this.submit();
        });

        $('[name="email"]').on('blur', async function () {
            if (validateField('email')) {
                await checkEmailAvailability();
            } else {
                setEmailAvailabilityStatus('', '');
            }
        });

        $('[name="email"]').on('input', function () {
            setEmailAvailabilityStatus('', '');
        });

        $('#createUserForm input, #createUserForm select').on('input change blur', function () {
            const fieldName = $(this).attr('name');
            if (fieldName && fieldName !== 'module_ids[]') {
                validateField(fieldName);
            }
        });

        $('input[name="module_ids[]"]').on('change', function () {
            validateModuleChecklist();
        });
    });
</script>
</body>
</html>
