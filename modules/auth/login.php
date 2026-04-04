<?php
require '../../components/db.php';

if (isLoggedIn($pdo)) {
    header('Location: ' . appUrl('/modules/dashboard/index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } elseif (!attemptLogin($pdo, $username, $password)) {
        $error = 'Invalid credentials or inactive account.';
    } else {
        $next = (string) ($_POST['next'] ?? '');
        if ($next !== '' && str_starts_with($next, '/modules/')) {
            header('Location: ' . appUrl($next));
        } else {
            header('Location: ' . appUrl('/modules/dashboard/index.php'));
        }
        exit;
    }
}

$flashSuccess = consumeFlash('flash_success');
$nextPath = (string) ($_GET['next'] ?? '/modules/dashboard/index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedVantage Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #001f3f 0%, #006d77 50%, #83c5be 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 430px;
            border: 0;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(120deg, #0b3d5b, #0a9396);
            color: #fff;
            padding: 24px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header">
            <h4 class="mb-1"><i class="bi bi-shield-lock"></i> MedVantage</h4>
            <small>Secure Sign In</small>
        </div>
        <div class="card-body p-4">
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="next" value="<?= htmlspecialchars($nextPath) ?>">

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" maxlength="60" required autofocus>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </form>
        </div>
    </div>
</body>
</html>
