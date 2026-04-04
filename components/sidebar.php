<!-- MedVantage Sidebar Component - Complete -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="<?= htmlspecialchars(appUrl('/assets/archive-styling.css')) ?>" rel="stylesheet">

<style>
    /* Reset and Base Styles */
    html, body {
        margin: 0;
        padding: 0;
        height: 100%;
        font-family: 'Poppins', sans-serif;
    }

    body {
        overflow: hidden;
    }

    /* Sidebar */
    .sidebar {
        height: 100vh;
        width: 280px;
        position: fixed;
        top: 0;
        left: 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background: linear-gradient(180deg, #071f26, #0a7d7d);
        padding-top: 30px;
        transition: transform 0.3s ease;
        z-index: 1030;
        overflow-y: auto;
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
    }

    /* Scrollbar styling for sidebar */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    /* Collapsed sidebar */
    .sidebar.collapsed {
        transform: translateX(-100%);
    }

    /* Logo and Title */
    .sidebar .logo-title {
        text-align: center;
        margin-bottom: 30px;
    }

    .sidebar .logo-title img {
        width: 120px;
        margin-bottom: 10px;
    }

    .sidebar .logo-title h2 {
        font-size: 20px;
        font-weight: 600;
        color: white;
        letter-spacing: 1px;
        margin: 0;
        padding: 0;
    }

    /* Navigation Links */
    .sidebar .nav {
        flex: 1;
        overflow-y: auto;
        padding: 0;
        margin: 0;
    }

    .sidebar .nav-link {
        font-size: 16px;
        font-weight: 500;
        color: white;
        padding: 12px 20px;
        margin: 6px 15px;
        border-radius: 12px;
        transition: 0.3s ease;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        white-space: nowrap;
    }

    .sidebar .nav-link i {
        font-size: 18px;
        flex-shrink: 0;
    }

    .sidebar .nav-link:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateX(5px);
        color: white;
    }

    .sidebar .nav-link.active {
        background: white;
        color: #0f3d4c;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    /* Locked/Disabled Links */
    .sidebar .nav-link.locked {
        opacity: 0.65;
        cursor: not-allowed;
        background: rgba(255, 255, 255, 0.06);
    }

    .sidebar .nav-link.locked:hover {
        transform: none;
        background: rgba(255, 255, 255, 0.06);
    }

    /* Sidebar Footer */
    .sidebar-footer {
        padding: 12px 15px 20px;
        flex-shrink: 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-footer .nav-link {
        background-color: #dc3545;
        color: white !important;
        border-radius: 10px;
        font-weight: 600;
        justify-content: center;
        margin: 0;
        padding: 12px 20px;
    }

    .sidebar-footer .nav-link:hover {
        background-color: #bb2d3b;
        transform: none;
        color: white;
    }

    /* Sidebar Toggle Button */
    .sidebar-toggle-btn {
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1040;
        background: #0a7d7d;
        border: none;
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        display: none;
        font-size: 18px;
        transition: background 0.3s ease;
    }

    .sidebar-toggle-btn:hover {
        background: #076767;
    }

    /* Overlay (Mobile) */
    .overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        z-index: 1025;
    }

    /* Content Wrapper */
    .content-wrapper {
        margin-left: 280px;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        transition: margin-left 0.3s ease;
    }

    /* Content Scrollbar */
    .content-wrapper::-webkit-scrollbar {
        width: 6px;
    }

    .content-wrapper::-webkit-scrollbar-track {
        background: transparent;
    }

    .content-wrapper::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }

    .content-wrapper::-webkit-scrollbar-thumb:hover {
        background: #999;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .sidebar {
            width: 280px;
            transform: translateX(-100%);
        }

        .sidebar-toggle-btn {
            display: block;
            z-index: 1040;
        }

        body.sidebar-open .sidebar {
            transform: translateX(0);
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.3);
        }

        body.sidebar-open .overlay {
            display: block;
        }

        .content-wrapper {
            margin-left: 0;
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 250px;
        }

        .sidebar .logo-title img {
            width: 100px;
        }

        .sidebar .logo-title h2 {
            font-size: 18px;
        }

        .sidebar .nav-link {
            font-size: 15px;
            padding: 10px 15px;
            margin: 5px 10px;
        }

        .sidebar .nav-link i {
            font-size: 16px;
        }
    }

    @media (max-width: 480px) {
        .sidebar {
            width: 100%;
        }

        .sidebar .logo-title {
            margin-bottom: 20px;
        }

        .sidebar .logo-title h2 {
            font-size: 16px;
        }

        .sidebar-toggle-btn {
            padding: 6px 10px;
            font-size: 16px;
            top: 12px;
            left: 12px;
        }
    }
</style>

<!-- Sidebar Toggle Button (Mobile) -->
<button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle Sidebar">
    <i class="bi bi-list"></i>
</button>

<!-- Sidebar Overlay (Mobile) -->
<div class="overlay" id="sidebarOverlay"></div>

<?php
$sidebarUser = isset($pdo) ? getCurrentUser($pdo) : null;
$accessibleModules = isset($pdo) ? getAccessibleModulesForUser($pdo, $sidebarUser) : [];
$currentModule = currentModuleKeyFromPath();
?>

<!-- Sidebar Component -->
<div class="sidebar shadow-lg">
    <div>
        <!-- Logo and Title -->
        <div class="logo-title">
            <img src="<?= htmlspecialchars(appUrl('/components/logo.png')) ?>" alt="MedVantage Logo">
            <h2>MedVantage</h2>
        </div>

        <!-- Navigation Menu -->
        <ul class="nav flex-column">
            <?php
                foreach ($accessibleModules as $module) {
                    $isActive = ($currentModule === $module['module_key']);
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $isActive ? 'active' : ''; ?>"
                           href="<?php echo htmlspecialchars(routePathToUrl($module['route_path'])); ?>"
                           title="<?php echo htmlspecialchars($module['module_label']); ?>">
                            <i class="bi <?php echo htmlspecialchars($module['icon_class']); ?>"></i>
                            <span class="nav-label"><?php echo htmlspecialchars($module['module_label']); ?></span>
                        </a>
                    </li>
                    <?php
                }
            ?>
        </ul>
    </div>

    <div class="sidebar-footer text-white">
        <?php if ($sidebarUser): ?>
            <div class="small mb-2 text-center">
                <div class="fw-semibold"><?= htmlspecialchars($sidebarUser['full_name']) ?></div>
                <div class="opacity-75"><?= htmlspecialchars($sidebarUser['role_name']) ?></div>
            </div>
        <?php endif; ?>
        <a class="nav-link" href="<?= htmlspecialchars(appUrl('/modules/auth/logout.php')) ?>">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>

<!-- Sidebar JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');
        const navLinks = document.querySelectorAll('.sidebar .nav-link:not(.locked)');

        // Toggle sidebar on button click
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-open');
            });
        }

        // Close sidebar when overlay is clicked
        if (overlay) {
            overlay.addEventListener('click', function() {
                document.body.classList.remove('sidebar-open');
            });
        }

        // Close sidebar when a nav link is clicked (on mobile)
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    document.body.classList.remove('sidebar-open');
                }
            });
        });

        // Close sidebar on window resize (when going from mobile to desktop)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                document.body.classList.remove('sidebar-open');
            }
        });
    });
</script>