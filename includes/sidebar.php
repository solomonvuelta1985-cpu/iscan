<?php
/**
 * Reusable Sidebar Navigation Component
 * Purple-themed Material Design Sidebar
 */

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Get statistics for badges
$stats_for_sidebar = [
    'this_month_births' => 0,
    'this_month_marriages' => 0
];

try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM certificate_of_live_birth WHERE status = 'Active' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        $stats_for_sidebar['this_month_births'] = $stmt->fetch()['count'] ?? 0;

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM certificate_of_marriage WHERE status = 'Active' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        $stats_for_sidebar['this_month_marriages'] = $stmt->fetch()['count'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Sidebar Stats Error: " . $e->getMessage());
}

$user_name_sidebar = $_SESSION['full_name'] ?? 'Admin User';
$user_first_name_sidebar = explode(' ', $user_name_sidebar)[0];
$user_initials = strtoupper(substr($user_first_name_sidebar, 0, 1));
?>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Page Wrapper -->
<div class="page-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="fas fa-certificate" style="font-size: 20px;"></i>
            </div>
            <div class="brand-text">
                <div class="brand-title">iScan</div>
                <div class="brand-subtitle">Civil Registry</div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="nav-menu">
            <div class="nav-section-title">Main</div>
            <ul style="list-style: none;">
                <li class="nav-item">
                    <a href="../admin/dashboard_modern.php" class="nav-link <?php echo ($current_page == 'dashboard_modern.php' || $current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-home"></i></span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="certificate_of_live_birth.php" class="nav-link <?php echo $current_page == 'certificate_of_live_birth.php' ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-baby"></i></span>
                        <span class="nav-text">Birth Certificates</span>
                        <?php if ($stats_for_sidebar['this_month_births'] > 0): ?>
                            <span class="nav-badge"><?php echo $stats_for_sidebar['this_month_births']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="certificate_of_marriage.php" class="nav-link <?php echo $current_page == 'certificate_of_marriage.php' ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-ring"></i></span>
                        <span class="nav-text">Marriage Certificates</span>
                        <?php if ($stats_for_sidebar['this_month_marriages'] > 0): ?>
                            <span class="nav-badge"><?php echo $stats_for_sidebar['this_month_marriages']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <div class="nav-section-title" style="margin-top: 24px;">Management</div>
            <ul style="list-style: none;">
                <li class="nav-item">
                    <a href="marriage_records.php" class="nav-link <?php echo $current_page == 'marriage_records.php' ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-folder-open"></i></span>
                        <span class="nav-text">Records</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                        <span class="nav-text">Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-users"></i></span>
                        <span class="nav-text">Users</span>
                    </a>
                </li>
            </ul>

            <div class="nav-section-title" style="margin-top: 24px;">Settings</div>
            <ul style="list-style: none;">
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-cog"></i></span>
                        <span class="nav-text">Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/public/') !== false) ? 'logout.php' : '../public/logout.php'; ?>" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                        <span class="nav-text">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="topbar-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search...">
                </div>
            </div>
            <div class="topbar-right">
                <button class="topbar-btn">
                    <i class="fas fa-bell"></i>
                    <span class="badge-dot"></span>
                </button>
                <div class="user-menu">
                    <div class="user-avatar"><?php echo $user_initials; ?></div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($user_name_sidebar); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <i class="fas fa-chevron-down" style="color: var(--text-secondary); font-size: 12px;"></i>
                </div>
            </div>
        </header>

        <!-- Content wrapper - pages will inject content here -->
        <div class="dashboard-container">
