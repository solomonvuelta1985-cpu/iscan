<?php
/**
 * Dashboard - Certificate of Live Birth Management
 * Main landing page for viewing and managing records
 */

// Include configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Optional: Check authentication
// require_once '../includes/auth.php';
// if (!isLoggedIn()) {
//     header('Location: ../login.php');
//     exit;
// }

// Fetch statistics
try {
    $stats_query = "SELECT * FROM vw_certificate_statistics";
    $stats = $pdo->query($stats_query)->fetch();
} catch (PDOException $e) {
    error_log("Statistics Error: " . $e->getMessage());
    $stats = [
        'total_records' => 0,
        'active_records' => 0,
        'archived_records' => 0,
        'single_births' => 0,
        'twin_births' => 0,
        'triplet_births' => 0,
        'today_registrations' => 0,
        'this_month_registrations' => 0
    ];
}

// Fetch recent records
try {
    $records_query = "SELECT * FROM vw_active_certificates LIMIT 50";
    $records_stmt = $pdo->query($records_query);
    $records = $records_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Records Fetch Error: " . $e->getMessage());
    $records = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Certificate of Live Birth</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #212529;
            font-size: clamp(0.85rem, 2.3vw, 0.9rem);
            line-height: 1.5;
            padding: clamp(15px, 3vw, 20px);
        }

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            padding: clamp(20px, 4vw, 30px);
        }

        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #dee2e6;
        }

        .page-title {
            font-size: clamp(1.5rem, 4vw, 1.75rem);
            font-weight: 600;
            color: #212529;
            margin-bottom: 8px;
        }

        .page-subtitle {
            font-size: clamp(0.95rem, 2.5vw, 1rem);
            color: #6c757d;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 20px;
            transition: box-shadow 0.2s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .stat-card.blue { border-left: 4px solid #0d6efd; }
        .stat-card.red { border-left: 4px solid #dc3545; }
        .stat-card.green { border-left: 4px solid #198754; }
        .stat-card.yellow { border-left: 4px solid #ffc107; }
        .stat-card.purple { border-left: 4px solid #6f42c1; }

        .stat-number {
            font-size: clamp(1.5rem, 4vw, 1.75rem);
            font-weight: 600;
            color: #212529;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: clamp(0.85rem, 2.3vw, 0.9rem);
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Action Section */
        .action-section {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }

        .btn {
            padding: clamp(8px, 2vw, 10px) clamp(15px, 3vw, 18px);
            border-radius: 4px;
            font-size: clamp(0.85rem, 2.3vw, 0.9rem);
            font-weight: 500;
            border: 1px solid;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
        }

        .btn-success {
            background-color: #198754;
            border-color: #198754;
            color: #ffffff;
        }

        .btn-success:hover {
            background-color: #157347;
        }

        /* Table */
        .table-container {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #f8f9fa;
        }

        thead th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            font-size: clamp(0.85rem, 2.3vw, 0.875rem);
        }

        tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            font-size: clamp(0.85rem, 2.3vw, 0.875rem);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        .btn-info {
            background-color: #0dcaf0;
            border-color: #0dcaf0;
            color: #000000;
        }

        .btn-info:hover {
            background-color: #31d2f2;
        }

        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000000;
        }

        .btn-warning:hover {
            background-color: #ffca2c;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #ffffff;
        }

        .btn-danger:hover {
            background-color: #bb2d3b;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #adb5bd;
        }

        @media (max-width: 768px) {
            .action-section {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            table {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-dashboard"></i>
                Certificate of Live Birth - Dashboard
            </h1>
            <p class="page-subtitle">Civil Registry Records Management System</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-number"><?php echo number_format($stats['total_records'] ?? 0); ?></div>
                <div class="stat-label">
                    <i class="fas fa-file-alt"></i>
                    Total Records
                </div>
            </div>

            <div class="stat-card green">
                <div class="stat-number"><?php echo number_format($stats['active_records'] ?? 0); ?></div>
                <div class="stat-label">
                    <i class="fas fa-check-circle"></i>
                    Active Records
                </div>
            </div>

            <div class="stat-card yellow">
                <div class="stat-number"><?php echo number_format($stats['today_registrations'] ?? 0); ?></div>
                <div class="stat-label">
                    <i class="fas fa-calendar-day"></i>
                    Today's Registrations
                </div>
            </div>

            <div class="stat-card purple">
                <div class="stat-number"><?php echo number_format($stats['this_month_registrations'] ?? 0); ?></div>
                <div class="stat-label">
                    <i class="fas fa-calendar-alt"></i>
                    This Month
                </div>
            </div>

            <div class="stat-card red">
                <div class="stat-number"><?php echo number_format($stats['archived_records'] ?? 0); ?></div>
                <div class="stat-label">
                    <i class="fas fa-archive"></i>
                    Archived
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-section">
            <a href="../public/certificate_of_live_birth.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add New Certificate
            </a>
            <a href="../public/certificate_of_live_birth.html" class="btn btn-success">
                <i class="fas fa-file-alt"></i>
                HTML Version
            </a>
        </div>

        <!-- Records Table -->
        <div class="table-container">
            <?php if (empty($records)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No records found</h3>
                    <p>Start by adding a new certificate of live birth.</p>
                    <br>
                    <a href="../public/certificate_of_live_birth.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add First Record
                    </a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Registry No.</th>
                            <th>Mother's Name</th>
                            <th>Father's Name</th>
                            <th>Type of Birth</th>
                            <th>Date Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['registry_no']); ?></td>
                            <td><?php echo htmlspecialchars($record['mother_full_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['father_full_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['type_of_birth']); ?></td>
                            <td><?php echo format_datetime($record['date_of_registration']); ?></td>
                            <td>
                                <a href="../public/certificate_of_live_birth.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
