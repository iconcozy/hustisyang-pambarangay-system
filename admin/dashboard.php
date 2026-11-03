<?php
require_once '../includes/session.php';
require_once '../config/db.php';

require_admin();

$stats_q = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(status = 'Pending') as pending,
        SUM(status = 'In Progress') as in_progress,
        SUM(status = 'Resolved') as resolved,
        SUM(status = 'Dismissed') as dismissed
    FROM complaints 
    WHERE is_archived = 0
");
$stats = $stats_q->fetch_assoc();

$comp_q = $conn->query("SELECT COUNT(*) as total FROM complainants");
$total_complainants = $comp_q->fetch_assoc()['total'];

$month_q = $conn->query("
    SELECT COUNT(*) as total FROM complaints 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$this_month = $month_q->fetch_assoc()['total'];

$pending_q = $conn->query("
    SELECT * FROM complaints
    WHERE status = 'Pending' AND is_archived = 0
    ORDER BY created_at ASC
    LIMIT 5
");

$cat_q = $conn->query("
    SELECT category, COUNT(*) as total
    FROM complaints 
    WHERE is_archived = 0
    GROUP BY category
    ORDER BY total DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Hustisyang Pambarangay</title>
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
    </style>
</head>
<body>

<nav class="navbar navbar-barangay">
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

<div class="subnav-barangay">
    <div class="container">
        <ul class="nav nav-pills py-2 gap-2">
            <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="complaints.php"><i class="bi bi-file-earmark-text"></i> Complaints</a></li>
            <li class="nav-item"><a class="nav-link" href="complainants.php"><i class="bi bi-people"></i> Complainants</a></li>
            <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
            <?php if (is_admin()): ?>
            <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-person-gear"></i> Users</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a></li>
        </ul>
    </div>
</div>

<div class="container py-4">

    <div class="mb-4">
        <h3 class="mb-0">Dashboard</h3>
        <small class="text-muted">Overview of complaints and complainant records.</small>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Total Complaints</div>
                            <h2 class="mb-0 fw-bold"><?= $stats['total'] ?? 0 ?></h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded p-2"><i class="bi bi-file-earmark-text text-primary"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Complainants</div>
                            <h2 class="mb-0 fw-bold"><?= $total_complainants ?></h2>
                        </div>
                        <div class="bg-secondary bg-opacity-10 rounded p-2"><i class="bi bi-people text-secondary"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card stat-success border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">This Month</div>
                            <h2 class="mb-0 fw-bold text-success"><?= $this_month ?></h2>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded p-2"><i class="bi bi-calendar-month text-success"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card stat-warning border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Pending</div>
                            <h2 class="mb-0 fw-bold text-warning"><?= $stats['pending'] ?? 0 ?></h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded p-2"><i class="bi bi-hourglass-split text-warning"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge-pill-dot badge-pending">Pending</span>
                        <h4 class="mb-0"><?= $stats['pending'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge-pill-dot badge-progress">In Progress</span>
                        <h4 class="mb-0"><?= $stats['in_progress'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge-pill-dot badge-resolved">Resolved</span>
                        <h4 class="mb-0"><?= $stats['resolved'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <strong><i class="bi bi-exclamation-circle text-warning"></i> Pending Complaints</strong>
                    <a href="complaints.php?status=Pending" class="btn btn-sm btn-outline-primary">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($pending_q->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Ref No.</th>
                                        <th>Complainant</th>
                                        <th>Category</th>
                                        <th class="d-none d-md-table-cell">Date</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($p = $pending_q->fetch_assoc()): ?>
                                        <tr>
                                            <td><code class="text-primary small"><?= $p['reference_no'] ?></code></td>
                                            <td>
                                                <div class="fw-semibold small"><?= htmlspecialchars($p['complainant_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($p['title']) ?></small>
                                            </td>
                                            <td><small><?= htmlspecialchars($p['category']) ?></small></td>
                                            <td class="d-none d-md-table-cell">
                                                <small class="text-muted"><?= date('M d, Y', strtotime($p['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <a href="view_complaint.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> Handle
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-check-circle-fill empty-icon" style="color: #10B981; opacity: 0.6;"></i>
                            <h5>No pending complaints</h5>
                            <p>All complaints have been addressed.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-3">
                    <strong><i class="bi bi-bar-chart"></i> Top Categories</strong>
                </div>
                <div class="card-body">
                    <?php if ($cat_q->num_rows > 0): ?>
                        <?php while ($c = $cat_q->fetch_assoc()): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small"><?= htmlspecialchars($c['category']) ?></span>
                                <span class="badge bg-secondary"><?= $c['total'] ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<footer class="footer-barangay">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>