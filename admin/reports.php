<?php
require_once '../includes/session.php';
require_once '../config/db.php';

require_admin();

$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to   = isset($_GET['date_to'])   ? $_GET['date_to']   : date('Y-m-d');

$date_from = $conn->real_escape_string($date_from);
$date_to   = $conn->real_escape_string($date_to);

$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(status = 'Pending') as pending,
        SUM(status = 'In Progress') as in_progress,
        SUM(status = 'Resolved') as resolved,
        SUM(status = 'Dismissed') as dismissed
    FROM complaints 
    WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
    AND is_archived = 0
")->fetch_assoc();

$cat_data = [];
$cat_q = $conn->query("
    SELECT category, COUNT(*) as total
    FROM complaints 
    WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
    AND is_archived = 0
    GROUP BY category
    ORDER BY total DESC
");
while ($row = $cat_q->fetch_assoc()) {
    $cat_data[] = $row;
}

$trend_labels = [];
$trend_data   = [];

for ($i = 5; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-$i months"));
    $month_end   = date('Y-m-t', strtotime("-$i months"));
    $month_name  = date('M Y', strtotime("-$i months"));

    $trend_labels[] = $month_name;

    $m_q = $conn->query("
        SELECT COUNT(*) as total 
        FROM complaints 
        WHERE DATE(created_at) BETWEEN '$month_start' AND '$month_end'
        AND is_archived = 0
    ");
    $trend_data[] = (int) $m_q->fetch_assoc()['total'];
}

$total = (int) ($stats['total'] ?? 0);
$resolved = (int) ($stats['resolved'] ?? 0);
$res_rate = $total > 0 ? round(($resolved / $total) * 100) : 0;

$avg_days = round($conn->query("
    SELECT AVG(DATEDIFF(resolved_at, created_at)) as avg_days
    FROM complaints 
    WHERE status = 'Resolved' 
    AND resolved_at IS NOT NULL
    AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'
")->fetch_assoc()['avg_days'] ?? 0, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Hustisyang Pambarangay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        :root { --primary-navy: #1A3A6C; --accent-gold: #F4C430; }
        .navbar-barangay { background-color: var(--primary-navy); border-bottom: 4px solid var(--accent-gold); padding: 8px 0; }
        .navbar-barangay .navbar-brand { display: flex; align-items: center; gap: 12px; color: #fff; font-family: Georgia, serif; font-weight: 700; text-decoration: none; }
        .navbar-barangay .navbar-brand img { height: 48px; width: 48px; }
        .navbar-barangay .brand-text { display: flex; flex-direction: column; line-height: 1.1; }
        .navbar-barangay .brand-text .main { font-size: 16px; color: #fff; }
        .navbar-barangay .brand-text .sub { font-size: 10px; color: var(--accent-gold); font-family: 'Segoe UI', sans-serif; letter-spacing: 1px; text-transform: uppercase; }
        .navbar-barangay .user-info { color: #fff; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .navbar-barangay .role-badge { background-color: var(--accent-gold); color: var(--primary-navy); padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .subnav-barangay { background: #fff; border-bottom: 1px solid #e3e6ea; }
        .subnav-barangay .nav-pills .nav-link { color: var(--primary-navy); font-weight: 500; border-radius: 6px; padding: 8px 16px; }
        .subnav-barangay .nav-pills .nav-link.active { background-color: var(--primary-navy); color: #fff; }
        .footer-barangay { background-color: var(--primary-navy); color: #cfd8e3; padding: 20px 0; margin-top: 40px; border-top: 4px solid var(--accent-gold); }
        .footer-barangay .gold { color: var(--accent-gold); }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .navbar-barangay, .subnav-barangay, .footer-barangay { display: none !important; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-barangay no-print">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <img src="../assets/img/logo.svg" alt="Barangay Logo">
            <div class="brand-text">
                <span class="main">Hustisyang Pambarangay</span>
                <span class="sub">Complaint Management System</span>
            </div>
        </a>
        <div class="user-info">
            <i class="bi bi-person-circle fs-5"></i>
            <div>
                <div><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <span class="role-badge"><?= strtoupper($_SESSION['role']) ?></span>
            </div>
            <span class="mx-2">|</span>
            <a href="../auth/logout.php" class="text-white text-decoration-none"
               onclick="return confirm('Are you sure you want to logout?')">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="subnav-barangay no-print">
    <div class="container">
        <ul class="nav nav-pills py-2 gap-2">
            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="complaints.php"><i class="bi bi-file-earmark-text"></i> Complaints</a></li>
            <li class="nav-item"><a class="nav-link" href="complainants.php"><i class="bi bi-people"></i> Complainants</a></li>
            <li class="nav-item"><a class="nav-link active" href="reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
            <?php if (is_admin()): ?>
            <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-person-gear"></i> Users</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a></li>
        </ul>
    </div>
</div>

<div class="container py-4">

    <div class="text-center mb-4 d-none d-print-block">
        <h3>Hustisyang Pambarangay</h3>
        <p class="mb-0">Complaint Report</p>
        <small><?= date('M d, Y', strtotime($date_from)) ?> to <?= date('M d, Y', strtotime($date_to)) ?></small>
        <hr>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <div>
            <h3 class="mb-0">Reports</h3>
            <small class="text-muted">Complaint statistics and analytics.</small>
        </div>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Print Report
        </button>
    </div>

    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Apply</button>
                </div>
                <div class="col-md-2">
                    <a href="reports.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted small">Total Complaints</div>
                    <h3 class="mb-0 fw-bold"><?= $total ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted small">Resolved</div>
                    <h3 class="mb-0 fw-bold text-success"><?= $resolved ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted small">Resolution Rate</div>
                    <h3 class="mb-0 fw-bold text-info"><?= $res_rate ?>%</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted small">Avg Days to Resolve</div>
                    <h3 class="mb-0 fw-bold text-warning"><?= $avg_days ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header py-3">
                    <strong><i class="bi bi-pie-chart"></i> Complaints by Category</strong>
                </div>
                <div class="card-body">
                    <?php if (count($cat_data) > 0): ?>
                        <canvas id="categoryChart" height="200"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0 py-5">No data available for this period.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header py-3">
                    <strong><i class="bi bi-bar-chart"></i> Monthly Trend (Last 6 Months)</strong>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header py-3">
                    <strong><i class="bi bi-list-check"></i> Complaints by Status</strong>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td><span class="badge-pill-dot badge-pending">Pending</span></td>
                                <td class="text-end fw-bold"><?= $stats['pending'] ?? 0 ?></td>
                            </tr>
                            <tr>
                                <td><span class="badge-pill-dot badge-progress">In Progress</span></td>
                                <td class="text-end fw-bold"><?= $stats['in_progress'] ?? 0 ?></td>
                            </tr>
                            <tr>
                                <td><span class="badge-pill-dot badge-resolved">Resolved</span></td>
                                <td class="text-end fw-bold"><?= $stats['resolved'] ?? 0 ?></td>
                            </tr>
                            <tr>
                                <td><span class="badge-pill-dot badge-dismissed">Dismissed</span></td>
                                <td class="text-end fw-bold"><?= $stats['dismissed'] ?? 0 ?></td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>Total</strong></td>
                                <td class="text-end fw-bold"><?= $stats['total'] ?? 0 ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header py-3">
                    <strong><i class="bi bi-tags"></i> Category Breakdown</strong>
                </div>
                <div class="card-body p-0">
                    <?php if (count($cat_data) > 0): ?>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end d-none d-md-table-cell">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cat_data as $cat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cat['category']) ?></td>
                                        <td class="text-end"><?= $cat['total'] ?></td>
                                        <td class="text-end d-none d-md-table-cell">
                                            <?= $total > 0 ? round(($cat['total'] / $total) * 100) : 0 ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0 py-5">No data.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <div class="text-center mt-5 d-none d-print-block">
        <hr>
        <small class="text-muted">
            Generated by: <?= htmlspecialchars($_SESSION['full_name']) ?> | 
            Date: <?= date('M d, Y h:i A') ?>
        </small>
    </div>

</div>

<footer class="footer-barangay no-print">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="../assets/img/logo.svg" alt="Logo" height="32">
                    <strong class="gold">Hustisyang Pambarangay</strong>
                </div>
                <small>Complaint Management System</small>
            </div>
            <div class="col-md-6 text-md-end">
                <small>&copy; <?= date('Y') ?> Barangay Hustisyang Pambarangay. All rights reserved.</small>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (count($cat_data) > 0): ?>
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($cat_data, 'category')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($cat_data, 'total')) ?>,
                backgroundColor: ['#1A3A6C', '#F4C430', '#dc3545', '#198754', '#6c757d', '#0dcaf0', '#fd7e14']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
<?php endif; ?>

    new Chart(document.getElementById('trendChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($trend_labels) ?>,
            datasets: [{
                label: 'Complaints',
                data: <?= json_encode($trend_data) ?>,
                backgroundColor: '#1A3A6C',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>