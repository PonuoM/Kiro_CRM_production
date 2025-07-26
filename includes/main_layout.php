<?php
/**
 * Main Layout with ShadCN UI Style Sidebar
 * Modern UI for main pages (non-admin)
 */

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/permissions.php';

// These will be set by the calling page
$currentUser = '';
$currentRole = '';
$menuItems = [];

function renderMainLayout($pageTitle, $content, $additionalCSS = '', $additionalJS = '') {
    $currentUser = $GLOBALS['currentUser'] ?? '';
    $currentRole = $GLOBALS['currentRole'] ?? '';
    $menuItems = $GLOBALS['menuItems'] ?? [];
    
    // Use relative paths from pages directory
    $basePath = '';
    
    // Group menu items by category
    $dashboardItems = [];
    $customerItems = [];
    $adminItems = [];
    $systemItems = [];
    
    foreach ($menuItems as $item) {
        if (strpos($item['url'], 'dashboard') !== false) {
            $dashboardItems[] = $item;
        } elseif (strpos($item['url'], 'customer') !== false || strpos($item['url'], 'daily_tasks') !== false || strpos($item['url'], 'call_history') !== false || strpos($item['url'], 'order_history') !== false) {
            $customerItems[] = $item;
        } elseif (strpos($item['url'], 'admin/') !== false) {
            $adminItems[] = $item;
        } else {
            $systemItems[] = $item;
        }
    }
    
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Kiro CRM</title>
    
    <!-- Noto Sans Thai Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* ShadCN-inspired Color System */
            --background: #ffffff;
            --foreground: #0f172a;
            --card: #ffffff;
            --card-foreground: #0f172a;
            --popover: #ffffff;
            --popover-foreground: #0f172a;
            --primary: #76BC43;
            --primary-foreground: #ffffff;
            --secondary: #f1f5f9;
            --secondary-foreground: #475569;
            --muted: #f8fafc;
            --muted-foreground: #64748b;
            --accent: #f1f5f9;
            --accent-foreground: #0f172a;
            --destructive: #ef4444;
            --destructive-foreground: #ffffff;
            --border: #e2e8f0;
            --input: #e2e8f0;
            --ring: #76BC43;
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Thai', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--background);
            color: var(--foreground);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background-color: var(--card);
            border-right: 1px solid var(--border);
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--primary) 0%, #5da832 100%);
            color: white;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-brand:hover {
            color: white;
            text-decoration: none;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--muted-foreground);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0 1rem;
            margin-bottom: 0.5rem;
        }

        .nav-item {
            margin: 0.125rem 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--foreground);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            font-weight: 500;
            position: relative;
        }

        .nav-link:hover {
            background-color: var(--accent);
            color: var(--foreground);
            text-decoration: none;
        }

        .nav-link.active {
            background-color: var(--primary);
            color: var(--primary-foreground);
        }

        .nav-link .nav-icon {
            width: 1.25rem;
            height: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background-color: var(--muted);
        }

        .main-header {
            background-color: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .main-body {
            padding: 2rem;
        }

        /* User Info */
        .user-info {
            padding: 1rem;
            border-top: 1px solid var(--border);
            margin-top: auto;
            background-color: var(--muted);
        }

        .user-avatar {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, #5da832 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .user-details {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--foreground);
            margin: 0;
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--muted-foreground);
            margin: 0;
        }

        /* Page Header */
        .page-header {
            background-color: var(--card);
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--foreground);
            margin: 0 0 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-description {
            color: var(--muted-foreground);
            margin: 0;
            font-size: 1.1rem;
        }

        /* Cards */
        .card {
            background-color: var(--card);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }

        .card-header {
            padding: 1.5rem 1.5rem 0 1.5rem;
            border-bottom: none;
            background: transparent;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--card-foreground);
            margin: 0 0 0.5rem 0;
        }

        /* Buttons */
        .btn {
            font-family: 'Noto Sans Thai', sans-serif;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--primary-foreground);
        }

        .btn-primary:hover {
            background-color: #5da832;
            border-color: #5da832;
            color: var(--primary-foreground);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
            background-color: transparent;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--primary-foreground);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .main-header {
                padding: 1rem;
            }

            .main-body {
                padding: 1rem;
            }
        }

        /* Mobile Toggle Button */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--foreground);
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .sidebar-toggle {
                display: block;
            }
        }

        /* Custom scrollbar for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 2px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--muted-foreground);
        }
    </style>
    
    <?php echo $additionalCSS; ?>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <a href="<?php echo $basePath; ?>dashboard.php" class="sidebar-brand">
                <i class="fas fa-chart-line"></i>
                <span>Kiro CRM</span>
            </a>
        </div>

        <!-- Sidebar Navigation -->
        <div class="sidebar-nav">
            <!-- Dashboard Section -->
            <?php if (!empty($dashboardItems)): ?>
            <div class="nav-section">
                <div class="nav-section-title">หน้าหลัก</div>
                <?php foreach ($dashboardItems as $item): ?>
                <div class="nav-item">
                    <a href="<?php echo $basePath; ?><?php echo $item['url']; ?>" class="nav-link">
                        <div class="nav-icon">
                            <i class="<?php echo $item['icon'] ?? 'fas fa-tachometer-alt'; ?>"></i>
                        </div>
                        <span><?php echo $item['title']; ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Customer Management Section -->
            <?php if (!empty($customerItems)): ?>
            <div class="nav-section">
                <div class="nav-section-title">จัดการลูกค้า</div>
                <?php foreach ($customerItems as $item): ?>
                <div class="nav-item">
                    <a href="<?php echo $basePath; ?><?php echo $item['url']; ?>" class="nav-link">
                        <div class="nav-icon">
                            <i class="<?php echo $item['icon'] ?? 'fas fa-users'; ?>"></i>
                        </div>
                        <span><?php echo $item['title']; ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Admin Tools Section -->
            <?php if (!empty($adminItems)): ?>
            <div class="nav-section">
                <div class="nav-section-title">เครื่องมือผู้ดูแล</div>
                <?php foreach ($adminItems as $item): ?>
                <div class="nav-item">
                    <a href="<?php echo $item['url']; ?>" class="nav-link">
                        <div class="nav-icon">
                            <i class="<?php echo $item['icon'] ?? 'fas fa-cog'; ?>"></i>
                        </div>
                        <span><?php echo $item['title']; ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- System Section -->
            <?php if (!empty($systemItems)): ?>
            <div class="nav-section">
                <div class="nav-section-title">ระบบ</div>
                <?php foreach ($systemItems as $item): ?>
                <div class="nav-item">
                    <a href="<?php echo $basePath; ?><?php echo $item['url']; ?>" class="nav-link">
                        <div class="nav-icon">
                            <i class="<?php echo $item['icon'] ?? 'fas fa-chart-line'; ?>"></i>
                        </div>
                        <span><?php echo $item['title']; ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- User Info -->
        <div class="user-info">
            <div class="d-flex align-items-center gap-3">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($currentUser, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <p class="user-name"><?php echo htmlspecialchars($currentUser); ?></p>
                    <p class="user-role"><?php echo htmlspecialchars($currentRole); ?></p>
                </div>
                <a href="logout.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Main Header -->
        <header class="main-header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="h4 mb-0"><?php echo htmlspecialchars($pageTitle); ?></h1>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted">ยินดีต้อนรับ, <?php echo htmlspecialchars($currentUser); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <div class="main-body">
            <?php echo $content; ?>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
            }
        });

        // Set active navigation item
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && currentPath.includes(href.replace('.php', ''))) {
                    link.classList.add('active');
                }
            });
        });
    </script>
    
    <?php echo $additionalJS; ?>
</body>
</html>
    <?php
    return ob_get_clean();
}
?>