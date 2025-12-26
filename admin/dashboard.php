<?php
/**
 * Dashboard - Civil Registry Records Management System
 * Main admin dashboard with analytics and statistics
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Optional: Check if user is authenticated
// if (!isset($_SESSION['user_id'])) {
//     header('Location: ../public/login.php');
//     exit;
// }

$pdo = getPDO();

// Initialize statistics
$stats = [
    'total_births' => 0,
    'total_marriages' => 0,
    'total_deaths' => 0,
    'this_month_births' => 0,
    'this_month_marriages' => 0,
    'last_month_births' => 0,
    'last_month_marriages' => 0,
    'birth_trend' => 0,
    'marriage_trend' => 0
];

$recent_activities = [];
$monthly_chart_data = [];
$certificate_distribution = [];

try {
    // Get total birth certificates
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM certificate_of_live_birth WHERE status = 'Active'");
    $stats['total_births'] = $stmt->fetch()['count'] ?? 0;

    // Get total marriage certificates
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM certificate_of_marriage WHERE status = 'Active'");
    $stats['total_marriages'] = $stmt->fetch()['count'] ?? 0;

    // Get this month's birth certificates
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM certificate_of_live_birth WHERE status = 'Active' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $stats['this_month_births'] = $stmt->fetch()['count'] ?? 0;

    // Get this month's marriage certificates
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM certificate_of_marriage WHERE status = 'Active' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $stats['this_month_marriages'] = $stmt->fetch()['count'] ?? 0;

    // Get last month's statistics for trend
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM certificate_of_live_birth WHERE status = 'Active' AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
    $stats['last_month_births'] = $stmt->fetch()['count'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM certificate_of_marriage WHERE status = 'Active' AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
    $stats['last_month_marriages'] = $stmt->fetch()['count'] ?? 0;

    // Calculate trends
    $stats['birth_trend'] = $stats['last_month_births'] > 0
        ? round((($stats['this_month_births'] - $stats['last_month_births']) / $stats['last_month_births']) * 100)
        : 0;

    $stats['marriage_trend'] = $stats['last_month_marriages'] > 0
        ? round((($stats['this_month_marriages'] - $stats['last_month_marriages']) / $stats['last_month_marriages']) * 100)
        : 0;

    // Get monthly data for chart (last 6 months)
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_label = date('M', strtotime("-$i months"));

        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM certificate_of_live_birth WHERE status = 'Active' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
        $stmt->execute([$month]);
        $births = $stmt->fetch()['count'] ?? 0;

        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM certificate_of_marriage WHERE status = 'Active' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
        $stmt->execute([$month]);
        $marriages = $stmt->fetch()['count'] ?? 0;

        $monthly_chart_data[] = [
            'month' => $month_label,
            'births' => $births,
            'marriages' => $marriages
        ];
    }

    // Get recent activities (both births and marriages combined)
    $recent_births = $pdo->query("
        SELECT 'birth' as type, registry_no, CONCAT(child_first_name, ' ', child_last_name) as name, created_at
        FROM certificate_of_live_birth
        WHERE status = 'Active'
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll();

    $recent_marriages = $pdo->query("
        SELECT 'marriage' as type, registry_no, CONCAT(husband_first_name, ' ', husband_last_name, ' & ', wife_first_name, ' ', wife_last_name) as name, created_at
        FROM certificate_of_marriage
        WHERE status = 'Active'
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll();

    // Merge and sort recent activities
    $recent_activities = array_merge($recent_births, $recent_marriages);
    usort($recent_activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $recent_activities = array_slice($recent_activities, 0, 8);

    // Certificate distribution
    $certificate_distribution = [
        ['type' => 'Birth Certificates', 'count' => $stats['total_births']],
        ['type' => 'Marriage Certificates', 'count' => $stats['total_marriages']],
        ['type' => 'Death Certificates', 'count' => $stats['total_deaths']]
    ];

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}

$user_name = $_SESSION['full_name'] ?? 'Admin User';
$user_first_name = explode(' ', $user_name)[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Civil Registry System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        :root {
            /* Colors - Purple Theme */
            --color-primary: #9155fd;
            --color-primary-light: #b389ff;
            --color-primary-dark: #7367f0;
            --color-success: #56ca00;
            --color-warning: #ffb400;
            --color-danger: #ff4c51;
            --color-info: #16b1ff;

            /* Backgrounds */
            --bg-primary: #f5f5f9;
            --bg-card: #ffffff;
            --bg-surface: #fafafa;

            /* Text */
            --text-primary: #4b465c;
            --text-secondary: #6f6b7d;
            --text-disabled: #a8aaae;

            /* Borders */
            --border-color: #dbdade;
            --divider-color: #e7e7e9;

            /* Material Design Shadows */
            --shadow-1: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            --shadow-2: 0 3px 6px rgba(0, 0, 0, 0.15), 0 2px 4px rgba(0, 0, 0, 0.12);
            --shadow-3: 0 10px 20px rgba(0, 0, 0, 0.15), 0 3px 6px rgba(0, 0, 0, 0.10);
            --shadow-hover: 0 8px 16px rgba(145, 85, 253, 0.2);

            /* Spacing (8px grid) */
            --spacing-1: 8px;
            --spacing-2: 16px;
            --spacing-3: 24px;
            --spacing-4: 32px;

            /* Border Radius */
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --radius-full: 9999px;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #212529;
            font-size: 0.9rem;
            line-height: 1.6;
            padding: 20px;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .dashboard-header {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 30px;
            margin-bottom: 30px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-title h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 5px;
        }

        .header-title p {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background-color: #0d6efd;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
        }

        .btn-success {
            background-color: #198754;
            color: #ffffff;
        }

        .btn-success:hover {
            background-color: #157347;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(25, 135, 84, 0.3);
        }

        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 24px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .stat-card.blue::before { background-color: #0d6efd; }
        .stat-card.green::before { background-color: #198754; }
        .stat-card.purple::before { background-color: #6f42c1; }
        .stat-card.red::before { background-color: #dc3545; }
        .stat-card.orange::before { background-color: #fd7e14; }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-card.blue .stat-icon { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .stat-card.green .stat-icon { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
        .stat-card.purple .stat-icon { background-color: rgba(111, 66, 193, 0.1); color: #6f42c1; }
        .stat-card.red .stat-icon { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .stat-card.orange .stat-icon { background-color: rgba(253, 126, 20, 0.1); color: #fd7e14; }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 4px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .stat-trend {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .stat-trend.up {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        .stat-trend.down {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .stat-trend.neutral {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 24px;
        }

        .chart-header {
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 4px;
        }

        .chart-subtitle {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Recent Activity */
        .activity-section {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 24px;
        }

        .activity-header {
            margin-bottom: 20px;
        }

        .activity-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #212529;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-icon.birth {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }

        .activity-icon.marriage {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .activity-content {
            flex: 1;
        }

        .activity-name {
            font-weight: 600;
            color: #212529;
            margin-bottom: 4px;
        }

        .activity-meta {
            font-size: 0.8125rem;
            color: #6c757d;
        }

        .activity-time {
            font-size: 0.75rem;
            color: #adb5bd;
            white-space: nowrap;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 20px;
            }

            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 20px;
            }

            .chart-container {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-content">
                <div class="header-title">
                    <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($user_first_name); ?>! Here's your civil registry overview.</p>
                </div>
                <div class="header-actions">
                    <a href="../public/certificate_of_live_birth.php" class="btn btn-primary">
                        <i class="fas fa-baby"></i> New Birth Certificate
                    </a>
                    <a href="../public/certificate_of_marriage.php" class="btn btn-success">
                        <i class="fas fa-ring"></i> New Marriage Certificate
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <!-- Total Birth Certificates -->
            <div class="stat-card blue">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($stats['total_births']); ?></div>
                        <div class="stat-label">Total Birth Certificates</div>
                        <?php if ($stats['birth_trend'] != 0): ?>
                            <div class="stat-trend <?php echo $stats['birth_trend'] > 0 ? 'up' : 'down'; ?>">
                                <i class="fas fa-<?php echo $stats['birth_trend'] > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                <?php echo abs($stats['birth_trend']); ?>% from last month
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-baby"></i>
                    </div>
                </div>
            </div>

            <!-- Total Marriage Certificates -->
            <div class="stat-card red">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($stats['total_marriages']); ?></div>
                        <div class="stat-label">Total Marriage Certificates</div>
                        <?php if ($stats['marriage_trend'] != 0): ?>
                            <div class="stat-trend <?php echo $stats['marriage_trend'] > 0 ? 'up' : 'down'; ?>">
                                <i class="fas fa-<?php echo $stats['marriage_trend'] > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                <?php echo abs($stats['marriage_trend']); ?>% from last month
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-ring"></i>
                    </div>
                </div>
            </div>

            <!-- This Month Births -->
            <div class="stat-card green">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($stats['this_month_births']); ?></div>
                        <div class="stat-label">Births This Month</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>

            <!-- This Month Marriages -->
            <div class="stat-card purple">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($stats['this_month_marriages']); ?></div>
                        <div class="stat-label">Marriages This Month</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                </div>
            </div>

            <!-- Total Death Certificates (placeholder) -->
            <div class="stat-card orange">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo number_format($stats['total_deaths']); ?></div>
                        <div class="stat-label">Total Death Certificates</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-cross"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <!-- Monthly Trend Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Monthly Registration Trends</h3>
                    <p class="chart-subtitle">Last 6 months overview</p>
                </div>
                <div class="chart-container">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>

            <!-- Certificate Distribution Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Certificate Distribution</h3>
                    <p class="chart-subtitle">Total active certificates</p>
                </div>
                <div class="chart-container">
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="activity-section">
            <div class="activity-header">
                <h3 class="activity-title"><i class="fas fa-clock"></i> Recent Activity</h3>
            </div>

            <?php if (empty($recent_activities)): ?>
                <p style="text-align: center; color: #6c757d; padding: 40px 0;">No recent activity found.</p>
            <?php else: ?>
                <ul class="activity-list">
                    <?php foreach ($recent_activities as $activity): ?>
                        <li class="activity-item">
                            <div class="activity-icon <?php echo $activity['type']; ?>">
                                <i class="fas fa-<?php echo $activity['type'] === 'birth' ? 'baby' : 'ring'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-name"><?php echo htmlspecialchars($activity['name']); ?></div>
                                <div class="activity-meta">
                                    <?php echo ucfirst($activity['type']); ?> Certificate &bull; Registry #<?php echo htmlspecialchars($activity['registry_no']); ?>
                                </div>
                            </div>
                            <div class="activity-time">
                                <?php
                                    $time_diff = time() - strtotime($activity['created_at']);
                                    if ($time_diff < 3600) {
                                        echo floor($time_diff / 60) . ' mins ago';
                                    } elseif ($time_diff < 86400) {
                                        echo floor($time_diff / 3600) . ' hours ago';
                                    } else {
                                        echo floor($time_diff / 86400) . ' days ago';
                                    }
                                ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Monthly Trend Chart
        const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_chart_data, 'month')); ?>,
                datasets: [
                    {
                        label: 'Birth Certificates',
                        data: <?php echo json_encode(array_column($monthly_chart_data, 'births')); ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Marriage Certificates',
                        data: <?php echo json_encode(array_column($monthly_chart_data, 'marriages')); ?>,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Distribution Chart
        const distributionCtx = document.getElementById('distributionChart').getContext('2d');
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($certificate_distribution, 'type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($certificate_distribution, 'count')); ?>,
                    backgroundColor: [
                        '#0d6efd',
                        '#dc3545',
                        '#fd7e14'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>
