<?php
/**
 * Marriage Records - View, Search, Edit, Delete
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Optional: Check authentication
// require_once '../includes/auth.php';
// if (!isLoggedIn()) {
//     header('Location: ../login.php');
//     exit;
// }

// Pagination settings
$records_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if ($records_per_page < 5 || $records_per_page > 100) {
    $records_per_page = 10;
}
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$search_query = '';
$params = [];

if (!empty($search)) {
    $search_query = " AND (
        registry_no LIKE :search OR
        husband_first_name LIKE :search OR
        husband_middle_name LIKE :search OR
        husband_last_name LIKE :search OR
        wife_first_name LIKE :search OR
        wife_middle_name LIKE :search OR
        wife_last_name LIKE :search OR
        date_of_marriage LIKE :search OR
        place_of_marriage LIKE :search OR
        husband_place_of_birth LIKE :search OR
        wife_place_of_birth LIKE :search
    )";
    $params[':search'] = "%{$search}%";
}

// Advanced filters
$filter_query = '';

// Marriage date range filter
if (!empty($_GET['marriage_date_from'])) {
    $filter_query .= " AND date_of_marriage >= :marriage_date_from";
    $params[':marriage_date_from'] = $_GET['marriage_date_from'];
}
if (!empty($_GET['marriage_date_to'])) {
    $filter_query .= " AND date_of_marriage <= :marriage_date_to";
    $params[':marriage_date_to'] = $_GET['marriage_date_to'];
}

// Registration date range filter
if (!empty($_GET['reg_date_from'])) {
    $filter_query .= " AND date_of_registration >= :reg_date_from";
    $params[':reg_date_from'] = $_GET['reg_date_from'];
}
if (!empty($_GET['reg_date_to'])) {
    $filter_query .= " AND date_of_registration <= :reg_date_to";
    $params[':reg_date_to'] = $_GET['reg_date_to'];
}

// Place filter
if (!empty($_GET['place'])) {
    $filter_query .= " AND place_of_marriage LIKE :place";
    $params[':place'] = "%{$_GET['place']}%";
}

// Sorting functionality
$allowed_sort_columns = [
    'registry_no',
    'husband_first_name',
    'wife_first_name',
    'date_of_marriage',
    'place_of_marriage',
    'date_of_registration',
    'created_at'
];

$sort_by = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort_columns)
    ? $_GET['sort_by']
    : 'created_at';

$sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC'
    ? 'ASC'
    : 'DESC';

// Get total records count
$count_sql = "SELECT COUNT(*) as total FROM certificate_of_marriage WHERE status = 'Active'" . $search_query . $filter_query;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch records
$sql = "SELECT * FROM certificate_of_marriage WHERE status = 'Active'"
    . $search_query
    . $filter_query
    . " ORDER BY {$sort_by} {$sort_order} LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$records = $stmt->fetchAll();

// Helper function to build query string for pagination/sorting
function build_query_string($exclude = []) {
    $params = $_GET;
    foreach ($exclude as $key) {
        unset($params[$key]);
    }
    return http_build_query($params);
}

// Helper function to get sort URL
function get_sort_url($column) {
    global $sort_by, $sort_order;
    $new_order = ($sort_by === $column && $sort_order === 'ASC') ? 'DESC' : 'ASC';
    $query = build_query_string(['sort_by', 'sort_order', 'page']);
    return '?sort_by=' . $column . '&sort_order=' . $new_order . ($query ? '&' . $query : '');
}

// Helper function to get sort icon
function get_sort_icon($column) {
    global $sort_by, $sort_order;
    if ($sort_by !== $column) {
        return 'chevrons-up-down';
    }
    return $sort_order === 'ASC' ? 'chevron-up' : 'chevron-down';
}

// Check if filters are active
$has_active_filters = !empty($_GET['marriage_date_from']) || !empty($_GET['marriage_date_to'])
    || !empty($_GET['reg_date_from']) || !empty($_GET['reg_date_to']) || !empty($_GET['place']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marriage Records - Civil Registry</title>

    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <?php include '../includes/sidebar_styles.php'; ?>

    <style>
        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #f5f5f5;
            color: #212529;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 72px;
            --sidebar-bg: #051f3a;
            --sidebar-item-hover: rgba(59, 130, 246, 0.1);
            --sidebar-item-active: rgba(59, 130, 246, 0.2);
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --accent-color: #3b82f6;
        }

        /* Mobile Header */
        .mobile-header {
            display: none;
            background: var(--sidebar-bg);
            color: var(--text-primary);
            padding: 16px 20px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .mobile-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mobile-header h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }

        #mobileSidebarToggle {
            background: none;
            border: none;
            color: var(--text-primary);
            cursor: pointer;
            padding: 8px;
        }

        /* Sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            color: var(--text-primary);
            z-index: 1000;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            transition: width 0.3s;
            overflow: hidden;
        }

        .sidebar-collapsed .sidebar {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.15);
            min-height: 64px;
        }

        .sidebar-header h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .sidebar-header h4 [data-lucide] {
            min-width: 28px;
            color: var(--accent-color);
        }

        .sidebar-collapsed .sidebar-header h4 span {
            display: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 12px 0;
            margin: 0;
            flex: 1;
            overflow-y: auto;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            margin: 2px 12px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 500;
            white-space: nowrap;
            position: relative;
        }

        .sidebar-menu li a:hover {
            background: var(--sidebar-item-hover);
            color: var(--text-primary);
            transform: translateX(3px);
        }

        .sidebar-menu li a.active {
            background: var(--sidebar-item-active);
            color: #b7ff9a;
            font-weight: 600;
        }

        .sidebar-menu li a.active::before {
            content: '';
            position: absolute;
            left: -12px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 22px;
            background: var(--accent-color);
            border-radius: 0 4px 4px 0;
        }

        .sidebar-menu li a [data-lucide] {
            min-width: 28px;
        }

        .sidebar-collapsed .sidebar-menu li a {
            justify-content: center;
            padding: 14px 10px;
        }

        .sidebar-collapsed .sidebar-menu li a span {
            display: none;
        }

        .sidebar-divider {
            border-top: 1px solid rgba(148, 163, 184, 0.15);
            margin: 12px 16px;
        }

        .sidebar-heading {
            padding: 14px 20px 8px;
            font-size: 10.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
        }

        .sidebar-collapsed .sidebar-heading {
            text-indent: -9999px;
        }

        /* Top Navbar */
        .top-navbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: 64px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            z-index: 100;
            transition: left 0.3s;
        }

        .sidebar-collapsed .top-navbar {
            left: var(--sidebar-collapsed-width);
        }

        #sidebarCollapse {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #374151;
            cursor: pointer;
            padding: 10px;
            margin-left: 20px;
            border-radius: 8px;
        }

        #sidebarCollapse:hover {
            background: #f3f4f6;
            color: var(--accent-color);
        }

        .top-navbar-info {
            margin-left: 16px;
        }

        .welcome-text {
            color: #6b7280;
            font-size: 13.5px;
            font-weight: 500;
        }

        /* User Profile Dropdown */
        .user-profile-dropdown {
            margin-left: auto;
            margin-right: 20px;
            position: relative;
        }

        .user-profile-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 12px 6px 6px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .user-profile-btn:hover {
            background: #f9fafb;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-color);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
        }

        .user-profile-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 13.5px;
            font-weight: 600;
            color: #111827;
        }

        .user-role {
            font-size: 11.5px;
            color: #6b7280;
        }

        .dropdown-arrow {
            color: #9ca3af;
        }

        /* Main Content */
        .content {
            margin-left: var(--sidebar-width);
            padding-top: 64px;
            min-height: 100vh;
            background: #f5f5f5;
            transition: margin-left 0.3s;
        }

        .sidebar-collapsed .content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .page-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .page-header {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #212529;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #0d6efd;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
        }

        .btn-success {
            background-color: #198754;
            color: #ffffff;
        }

        .btn-success:hover {
            background-color: #157347;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #000000;
        }

        .btn-danger {
            background-color: #dc3545;
            color: #ffffff;
        }

        .btn-danger:hover {
            background-color: #bb2d3b;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.75rem;
        }

        /* Search & Filter */
        .search-section {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .search-input:focus {
            outline: none;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .filter-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #6c757d;
            background: none;
            border: 1px solid #dee2e6;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .filter-toggle-btn:hover {
            background: #f8f9fa;
            border-color: #0d6efd;
            color: #0d6efd;
        }

        .filter-toggle-btn.active {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #ffffff;
        }

        .advanced-filters {
            display: none;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            margin-top: 15px;
        }

        .advanced-filters.show {
            display: block;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 0.8125rem;
            font-weight: 500;
            color: #495057;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #0d6efd;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Table */
        .table-container {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .records-table {
            width: 100%;
            border-collapse: collapse;
        }

        .records-table thead {
            background: #f8f9fa;
        }

        .records-table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            font-size: 0.8125rem;
            border-bottom: 2px solid #dee2e6;
        }

        .records-table th.sortable {
            cursor: pointer;
            user-select: none;
            transition: all 0.2s;
        }

        .records-table th.sortable:hover {
            background-color: #e9ecef;
            color: #0d6efd;
        }

        .records-table th.sortable a {
            display: flex;
            align-items: center;
            gap: 6px;
            color: inherit;
            text-decoration: none;
        }

        .records-table th.sortable.active {
            background-color: #e7f3ff;
            color: #0d6efd;
        }

        .sort-icon {
            opacity: 0.4;
            transition: opacity 0.2s;
        }

        .records-table th.sortable:hover .sort-icon,
        .records-table th.sortable.active .sort-icon {
            opacity: 1;
        }

        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .table-controls-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-controls-right {
            color: #6c757d;
            font-size: 0.8125rem;
        }

        .per-page-selector {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8125rem;
        }

        .per-page-selector select {
            padding: 6px 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.8125rem;
            cursor: pointer;
        }

        .per-page-selector select:focus {
            outline: none;
            border-color: #0d6efd;
        }

        .records-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            font-size: 0.8125rem;
        }

        .records-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            margin-top: 20px;
        }

        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            background: #ffffff;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .pagination-btn:hover:not(:disabled) {
            background: #f8f9fa;
            border-color: #0d6efd;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-btn.active {
            background: #0d6efd;
            color: #ffffff;
            border-color: #0d6efd;
        }

        .no-records {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        /* Alert */
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
            border-left: 3px solid #198754;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
            border-left: 3px solid #dc3545;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .mobile-header {
                display: block;
            }

            .top-navbar {
                display: none;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .content {
                margin-left: 0;
                padding-top: 70px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .search-form {
                flex-direction: column;
            }

            .table-container {
                overflow-x: auto;
            }

            .records-table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="mobile-header-content">
            <h4><i data-lucide="file-badge"></i> Civil Registry</h4>
            <button type="button" id="mobileSidebarToggle">
                <i data-lucide="menu"></i>
            </button>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i data-lucide="file-badge"></i> <span>Civil Registry</span></h4>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-heading">Overview</li>
            <li>
                <a href="../admin/dashboard.php" title="Dashboard">
                    <i data-lucide="layout-dashboard"></i> <span>Dashboard</span>
                </a>
            </li>

            <li class="sidebar-divider"></li>
            <li class="sidebar-heading">Certificates</li>
            <li>
                <a href="certificate_of_live_birth.php" title="Birth Certificates">
                    <i data-lucide="baby"></i> <span>Birth Certificates</span>
                </a>
            </li>
            <li>
                <a href="certificate_of_marriage.php" title="Marriage Certificates">
                    <i data-lucide="heart"></i> <span>Marriage Certificates</span>
                </a>
            </li>
            <li>
                <a href="#" title="Death Certificates">
                    <i data-lucide="cross"></i> <span>Death Certificates</span>
                </a>
            </li>

            <li class="sidebar-divider"></li>
            <li class="sidebar-heading">Management</li>
            <li>
                <a href="marriage_records.php" class="active" title="Marriage Records">
                    <i data-lucide="file-search"></i> <span>Marriage Records</span>
                </a>
            </li>
            <li>
                <a href="#" title="Reports">
                    <i data-lucide="bar-chart-3"></i> <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="#" title="Archives">
                    <i data-lucide="archive"></i> <span>Archives</span>
                </a>
            </li>

            <li class="sidebar-divider"></li>
            <li class="sidebar-heading">System</li>
            <li>
                <a href="#" title="Users">
                    <i data-lucide="users"></i> <span>Users</span>
                </a>
            </li>
            <li>
                <a href="#" title="Settings">
                    <i data-lucide="settings"></i> <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Top Navbar -->
    <div class="top-navbar" id="topNavbar">
        <button type="button" id="sidebarCollapse" title="Toggle Sidebar">
            <i data-lucide="menu"></i>
        </button>
        <div class="top-navbar-info">
            <span class="welcome-text">Welcome, Admin User</span>
        </div>

        <div class="user-profile-dropdown">
            <button class="user-profile-btn" type="button">
                <div class="user-avatar">AU</div>
                <div class="user-profile-info">
                    <span class="user-name">Admin User</span>
                    <span class="user-role">Administrator</span>
                </div>
                <i data-lucide="chevron-down" class="dropdown-arrow"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="page-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i data-lucide="heart"></i>
                    Marriage Records
                </h1>
                <a href="certificate_of_marriage.php" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    Add New Record
                </a>
            </div>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- Search & Filter Section -->
            <div class="search-section">
                <!-- Quick Search -->
                <form method="GET" action="" class="search-form" id="searchForm">
                    <input
                        type="text"
                        name="search"
                        class="search-input"
                        placeholder="Quick search by registry number, names, date, or place..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="search"></i>
                        Search
                    </button>
                    <button type="button" class="filter-toggle-btn <?php echo $has_active_filters ? 'active' : ''; ?>" onclick="toggleFilters()">
                        <i data-lucide="filter"></i>
                        Advanced Filters
                        <?php if ($has_active_filters): ?>
                            <span class="filter-badge">Active</span>
                        <?php endif; ?>
                    </button>
                    <?php if (!empty($search) || $has_active_filters): ?>
                    <a href="marriage_records.php" class="btn btn-warning">
                        <i data-lucide="x"></i>
                        Clear All
                    </a>
                    <?php endif; ?>
                </form>

                <!-- Advanced Filters -->
                <div class="advanced-filters <?php echo $has_active_filters ? 'show' : ''; ?>" id="advancedFilters">
                    <form method="GET" action="" id="filterForm">
                        <!-- Preserve search query -->
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>

                        <div class="filter-grid">
                            <!-- Marriage Date Range -->
                            <div class="filter-group">
                                <label for="marriage_date_from">Marriage Date From</label>
                                <input
                                    type="date"
                                    id="marriage_date_from"
                                    name="marriage_date_from"
                                    value="<?php echo htmlspecialchars($_GET['marriage_date_from'] ?? ''); ?>"
                                >
                            </div>

                            <div class="filter-group">
                                <label for="marriage_date_to">Marriage Date To</label>
                                <input
                                    type="date"
                                    id="marriage_date_to"
                                    name="marriage_date_to"
                                    value="<?php echo htmlspecialchars($_GET['marriage_date_to'] ?? ''); ?>"
                                >
                            </div>

                            <!-- Registration Date Range -->
                            <div class="filter-group">
                                <label for="reg_date_from">Registration Date From</label>
                                <input
                                    type="date"
                                    id="reg_date_from"
                                    name="reg_date_from"
                                    value="<?php echo htmlspecialchars($_GET['reg_date_from'] ?? ''); ?>"
                                >
                            </div>

                            <div class="filter-group">
                                <label for="reg_date_to">Registration Date To</label>
                                <input
                                    type="date"
                                    id="reg_date_to"
                                    name="reg_date_to"
                                    value="<?php echo htmlspecialchars($_GET['reg_date_to'] ?? ''); ?>"
                                >
                            </div>

                            <!-- Place Filter -->
                            <div class="filter-group">
                                <label for="place">Place of Marriage</label>
                                <input
                                    type="text"
                                    id="place"
                                    name="place"
                                    placeholder="Enter place..."
                                    value="<?php echo htmlspecialchars($_GET['place'] ?? ''); ?>"
                                >
                            </div>
                        </div>

                        <div class="filter-actions">
                            <button type="button" class="btn btn-warning" onclick="clearFilters()">
                                <i data-lucide="x"></i>
                                Clear Filters
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i data-lucide="check"></i>
                                Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Records Table -->
            <div class="table-container">
                <?php if (count($records) > 0): ?>
                <!-- Table Controls -->
                <div class="table-controls">
                    <div class="table-controls-left">
                        <div class="per-page-selector">
                            <label for="perPageSelect">Show</label>
                            <select id="perPageSelect" onchange="changePerPage(this.value)">
                                <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $records_per_page == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $records_per_page == 100 ? 'selected' : ''; ?>>100</option>
                            </select>
                            <span>entries</span>
                        </div>
                    </div>
                    <div class="table-controls-right">
                        Showing <?php echo number_format(($offset + 1)); ?> to <?php echo number_format(min($offset + $records_per_page, $total_records)); ?> of <?php echo number_format($total_records); ?> records
                    </div>
                </div>

                <table class="records-table">
                    <thead>
                        <tr>
                            <th class="sortable <?php echo $sort_by === 'registry_no' ? 'active' : ''; ?>">
                                <a href="<?php echo get_sort_url('registry_no'); ?>">
                                    Registry No.
                                    <i data-lucide="<?php echo get_sort_icon('registry_no'); ?>" class="sort-icon"></i>
                                </a>
                            </th>
                            <th class="sortable <?php echo $sort_by === 'husband_first_name' ? 'active' : ''; ?>">
                                <a href="<?php echo get_sort_url('husband_first_name'); ?>">
                                    Husband
                                    <i data-lucide="<?php echo get_sort_icon('husband_first_name'); ?>" class="sort-icon"></i>
                                </a>
                            </th>
                            <th class="sortable <?php echo $sort_by === 'wife_first_name' ? 'active' : ''; ?>">
                                <a href="<?php echo get_sort_url('wife_first_name'); ?>">
                                    Wife
                                    <i data-lucide="<?php echo get_sort_icon('wife_first_name'); ?>" class="sort-icon"></i>
                                </a>
                            </th>
                            <th class="sortable <?php echo $sort_by === 'date_of_marriage' ? 'active' : ''; ?>">
                                <a href="<?php echo get_sort_url('date_of_marriage'); ?>">
                                    Marriage Date
                                    <i data-lucide="<?php echo get_sort_icon('date_of_marriage'); ?>" class="sort-icon"></i>
                                </a>
                            </th>
                            <th class="sortable <?php echo $sort_by === 'place_of_marriage' ? 'active' : ''; ?>">
                                <a href="<?php echo get_sort_url('place_of_marriage'); ?>">
                                    Place
                                    <i data-lucide="<?php echo get_sort_icon('place_of_marriage'); ?>" class="sort-icon"></i>
                                </a>
                            </th>
                            <th class="sortable <?php echo $sort_by === 'date_of_registration' ? 'active' : ''; ?>">
                                <a href="<?php echo get_sort_url('date_of_registration'); ?>">
                                    Registration Date
                                    <i data-lucide="<?php echo get_sort_icon('date_of_registration'); ?>" class="sort-icon"></i>
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['registry_no'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($record['husband_first_name'] . ' ' . $record['husband_last_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['wife_first_name'] . ' ' . $record['wife_last_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($record['date_of_marriage'])); ?></td>
                            <td><?php echo htmlspecialchars($record['place_of_marriage']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($record['date_of_registration'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if (!empty($record['pdf_filename'])): ?>
                                    <a href="../uploads/<?php echo htmlspecialchars($record['pdf_filename']); ?>"
                                       target="_blank"
                                       class="btn btn-success btn-sm"
                                       title="View PDF">
                                        <i data-lucide="file-text"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="certificate_of_marriage.php?id=<?php echo $record['id']; ?>"
                                       class="btn btn-primary btn-sm"
                                       title="Edit">
                                        <i data-lucide="edit"></i>
                                    </a>
                                    <button onclick="deleteRecord(<?php echo $record['id']; ?>)"
                                            class="btn btn-danger btn-sm"
                                            title="Delete">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-records">
                    <i data-lucide="inbox" style="width: 48px; height: 48px; stroke: #adb5bd;"></i>
                    <p>No records found.</p>
                    <?php if (!empty($search) || $has_active_filters): ?>
                    <p>Try adjusting your search terms or filters.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php
                $base_query = build_query_string(['page']);
                $query_prefix = $base_query ? '&' . $base_query : '';
                ?>
                <a href="?page=1<?php echo $query_prefix; ?>"
                   class="pagination-btn <?php echo $current_page === 1 ? 'disabled' : ''; ?>">
                    <i data-lucide="chevrons-left"></i>
                </a>
                <a href="?page=<?php echo max(1, $current_page - 1); ?><?php echo $query_prefix; ?>"
                   class="pagination-btn <?php echo $current_page === 1 ? 'disabled' : ''; ?>">
                    <i data-lucide="chevron-left"></i>
                </a>

                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="?page=<?php echo $i; ?><?php echo $query_prefix; ?>"
                   class="pagination-btn <?php echo $i === $current_page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>

                <a href="?page=<?php echo min($total_pages, $current_page + 1); ?><?php echo $query_prefix; ?>"
                   class="pagination-btn <?php echo $current_page === $total_pages ? 'disabled' : ''; ?>">
                    <i data-lucide="chevron-right"></i>
                </a>
                <a href="?page=<?php echo $total_pages; ?><?php echo $query_prefix; ?>"
                   class="pagination-btn <?php echo $current_page === $total_pages ? 'disabled' : ''; ?>">
                    <i data-lucide="chevrons-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Sidebar functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
        const body = document.body;

        if (sidebarCollapse) {
            sidebarCollapse.addEventListener('click', function() {
                body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
            });
        }

        if (mobileSidebarToggle) {
            mobileSidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }

        // Restore sidebar state
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed && window.innerWidth > 768) {
            body.classList.add('sidebar-collapsed');
        }

        // Toggle advanced filters
        function toggleFilters() {
            const filters = document.getElementById('advancedFilters');
            const toggleBtn = document.querySelector('.filter-toggle-btn');
            filters.classList.toggle('show');

            // Store filter state in localStorage
            if (filters.classList.contains('show')) {
                localStorage.setItem('filtersExpanded', 'true');
            } else {
                localStorage.setItem('filtersExpanded', 'false');
            }
        }

        // Restore filter state on page load
        window.addEventListener('DOMContentLoaded', function() {
            const filtersExpanded = localStorage.getItem('filtersExpanded');
            const hasActiveFilters = <?php echo $has_active_filters ? 'true' : 'false'; ?>;

            if (filtersExpanded === 'true' || hasActiveFilters) {
                document.getElementById('advancedFilters').classList.add('show');
            }
        });

        // Clear all filters
        function clearFilters() {
            const searchParam = new URLSearchParams(window.location.search).get('search');
            let url = 'marriage_records.php';

            if (searchParam) {
                url += '?search=' + encodeURIComponent(searchParam);
            }

            window.location.href = url;
        }

        // Change records per page
        function changePerPage(perPage) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', perPage);
            url.searchParams.delete('page'); // Reset to page 1
            window.location.href = url.toString();
        }

        // Delete record function
        function deleteRecord(id) {
            if (!confirm('Are you sure you want to delete this marriage certificate? This action cannot be undone.')) {
                return;
            }

            fetch('../api/certificate_of_marriage_delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while deleting the record.');
            });
        }

        // Show alert function
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;

            const icon = type === 'success' ? 'check-circle' : 'alert-circle';

            alertDiv.innerHTML = `
                <i data-lucide="${icon}"></i>
                <span>${message}</span>
            `;

            alertContainer.innerHTML = '';
            alertContainer.appendChild(alertDiv);

            lucide.createIcons();

            window.scrollTo({ top: 0, behavior: 'smooth' });

            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>
