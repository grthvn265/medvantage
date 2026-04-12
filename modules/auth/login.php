<?php
require '../../components/db.php';
require '../../components/audit_log.php';

if (isLoggedIn($pdo)) {
    $activeUser = getCurrentUser($pdo);
    header('Location: ' . appUrl(getLandingRouteForUser($pdo, $activeUser)));
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
        $activeUser = getCurrentUser($pdo);
        if ($activeUser) {
            logAudit(
                $pdo,
                'LOGIN',
                'auth',
                (int) $activeUser['user_id'],
                'User logged in: ' . $activeUser['username']
            );
        }

        $landingRoute = getLandingRouteForUser($pdo, $activeUser);
        $next = (string) ($_POST['next'] ?? '');

        if ($activeUser && $next !== '' && str_starts_with($next, '/')) {
            $nextModule = currentModuleKeyFromPath($next);
            if ($nextModule === null || userCanAccessModule($pdo, (int) $activeUser['user_id'], $activeUser['role_key'], $nextModule)) {
                header('Location: ' . appUrl($next));
                exit;
            }
        }

        if ($landingRoute !== '') {
            header('Location: ' . appUrl($landingRoute));
        } else {
            header('Location: ' . appUrl($next));
        }
        exit;
    }
}

$flashSuccess = consumeFlash('flash_success');
$nextPath = (string) ($_GET['next'] ?? '');
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
        :root {
            --mv-deep: #071f26;
            --mv-primary: #076767;
            --mv-primary-strong: #076767;
            --mv-accent: #076767;
            --mv-surface: #f4f8f8;
            --mv-text: #213547;
            --mv-muted: #6b7f86;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at 15% 20%, rgba(10, 125, 125, 0.18), transparent 35%),
                radial-gradient(circle at 80% 90%, rgba(131, 197, 190, 0.22), transparent 40%),
                #071f26;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 26px;
        }

        .auth-shell {
            width: 100%;
            max-width: 980px;
            border: 1px solid #076767;
            border-radius: 20px;
            box-shadow: 0 24px 55px rgba(7, 31, 38, 0.2);
            overflow: hidden;
            background: #fff;
            animation: card-reveal 0.6s ease;
        }

        .auth-aside {
            position: relative;
            background: linear-gradient(165deg, var(--mv-deep) 0%, var(--mv-primary) 62%, #076767 100%);
            color: #fff;
            min-height: 560px;
            padding: 42px 36px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .auth-aside::before,
        .auth-aside::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.22;
            pointer-events: none;
        }

        .auth-aside::before {
            width: 280px;
            height: 280px;
            background: #fff;
            top: -85px;
            right: -100px;
        }

        .auth-aside::after {
            width: 220px;
            height: 220px;
            background: var(--mv-accent);
            bottom: -90px;
            left: -75px;
        }

        .brand-mark {
            position: relative;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .brand-mark img {
            width: 42px;
            height: auto;
            object-fit: contain;
        }

        .brand-mark span {
            line-height: 1;
        }

        .aside-copy {
            position: relative;
            z-index: 2;
            max-width: 340px;
            animation: lift-in 0.7s ease;
        }

        .aside-copy h2 {
            font-size: clamp(1.65rem, 3vw, 2.3rem);
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 14px;
        }

        .aside-copy p {
            margin: 0;
            color: rgba(255, 255, 255, 0.86);
            line-height: 1.65;
        }

        .aside-badge {
            position: relative;
            z-index: 2;
            background: rgba(7, 31, 38, 0.28);
            border: 1px solid rgba(255, 255, 255, 0.24);
            border-radius: 14px;
            padding: 14px 16px;
            max-width: 320px;
            font-size: 0.92rem;
            color: rgba(255, 255, 255, 0.94);
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .aside-badge i {
            color: var(--mv-accent);
            margin-top: 2px;
        }

        .auth-main {
            background: linear-gradient(180deg, #ffffff 0%, var(--mv-surface) 100%);
            padding: 48px 38px;
            display: flex;
            align-items: center;
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 430px;
            margin: 0 auto;
            animation: lift-in 0.75s ease;
        }

        .auth-form-wrap h3 {
            color: var(--mv-text);
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .auth-form-wrap .subtext {
            color: var(--mv-muted);
            margin-bottom: 1.55rem;
        }

        .form-label {
            color: #35535b;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-control {
            border-radius: 10px;
            border-color: #cfdce1;
            padding: 11px 13px;
        }

        .form-control:focus {
            border-color: var(--mv-primary);
            box-shadow: 0 0 0 0.2rem rgba(10, 125, 125, 0.14);
        }

        .password-toggle {
            border-color: #cfdce1;
            color: #46676f;
            background: #fff;
        }

        .password-toggle:hover,
        .password-toggle:focus {
            background: #f3fbfb;
            color: var(--mv-primary-strong);
            border-color: var(--mv-primary);
        }

        .btn-med {
            background: linear-gradient(120deg, var(--mv-primary) 0%, var(--mv-primary-strong) 100%);
            border: none;
            color: #fff;
            border-radius: 10px;
            font-weight: 600;
            padding: 11px 14px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-med:hover,
        .btn-med:focus {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(10, 125, 125, 0.25);
        }

        .alert {
            border-radius: 10px;
        }

        @keyframes lift-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes card-reveal {
            from {
                opacity: 0;
                transform: translateY(12px) scale(0.985);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @media (max-width: 991.98px) {
            body {
                padding: 18px;
            }

            .auth-main {
                padding: 34px 24px;
            }

            .auth-aside {
                min-height: 280px;
                padding: 30px 24px;
            }

            .aside-copy h2 {
                font-size: 1.55rem;
            }
        }

        @media (max-width: 575.98px) {
            .auth-main {
                padding: 28px 18px;
            }

            .auth-form-wrap {
                max-width: 100%;
            }

            .brand-mark img {
                width: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <div class="row g-0">
            <div class="col-lg-5 auth-aside">
                <div class="brand-mark">
                    <img src="<?= htmlspecialchars(appUrl('/components/logo.png')) ?>" alt="MedVantage Logo">
                    <span>MedVantage</span>
                </div>

                <div class="aside-copy">
                    <h2>Patient Information System</h2>
                </div>
            </div>

            <div class="col-lg-7 auth-main">
                <div class="auth-form-wrap">
                    <h3>Welcome Back</h3>
                    <p class="subtext">Sign in to continue to your MedVantage dashboard.</p>

                    <?php if ($flashSuccess): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
                    <?php endif; ?>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <input type="hidden" name="next" value="<?= htmlspecialchars($nextPath) ?>">

                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" maxlength="60" required autofocus>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group">
                                <input type="password" id="password" name="password" class="form-control" required>
                                <button class="btn password-toggle" type="button" id="togglePassword" aria-label="Show password" aria-pressed="false">
                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-med w-100">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');
            const toggleIcon = document.getElementById('togglePasswordIcon');

            if (!passwordInput || !toggleButton || !toggleIcon) {
                return;
            }

            toggleButton.addEventListener('click', function () {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                toggleIcon.classList.toggle('bi-eye', !isHidden);
                toggleIcon.classList.toggle('bi-eye-slash', isHidden);
                toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                toggleButton.setAttribute('aria-pressed', String(isHidden));
            });
        })();
    </script>
</body>
</html>
