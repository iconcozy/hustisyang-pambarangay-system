<?php
require_once '../includes/session.php';
require_once '../config/db.php';

require_admin();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT 
            c.*,
            (SELECT COUNT(*) FROM complaints comp WHERE comp.complainant_id = c.id AND comp.is_archived = 0) AS total_complaints,
            (SELECT COUNT(*) FROM complaints comp WHERE comp.complainant_id = c.id AND comp.status = 'Pending' AND comp.is_archived = 0) AS pending_count
        FROM complainants c
        WHERE 1=1";

if (!empty($search)) {
    $esc = $conn->real_escape_string($search);
    $sql .= " AND (c.full_name LIKE '%$esc%' 
                   OR c.contact_number LIKE '%$esc%' 
                   OR c.address LIKE '%$esc%')";
}

$sql .= " ORDER BY c.created_at DESC";

$result = $conn->query($sql);
$total = $result->num_rows;

$stats = $conn->query("SELECT COUNT(*) AS total FROM complainants")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complainants - Hustisyang Pambarangay</title>
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
            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="complaints.php"><i class="bi bi-file-earmark-text"></i> Complaints</a></li>
            <li class="nav-item"><a class="nav-link active" href="complainants.php"><i class="bi bi-people"></i> Complainants</a></li>
            <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
            <?php if (is_admin()): ?>
            <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-person-gear"></i> Users</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a></li>
        </ul>
    </div>
</div>

<div class="container py-4">

    <div class="mb-3">
        <h3 class="mb-0">Complainants Directory</h3>
        <small class="text-muted">List of individuals who have filed complaints.</small>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Total Complainants</div>
                        <h3 class="mb-0 fw-bold"><?= $stats['total'] ?? 0 ?></h3>
                    </div>
                    <i class="bi bi-people-fill text-primary fs-2"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by name, contact, or address..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if ($total > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="d-none d-md-table-cell">Contact</th>
                                <th class="d-none d-lg-table-cell">Address</th>
                                <th>Complaints</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold small"><?= htmlspecialchars($r['full_name']) ?></div>
                                        <small class="text-muted d-md-none"><?= htmlspecialchars($r['contact_number']) ?></small>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <small><?= htmlspecialchars($r['contact_number']) ?></small>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <small class="text-muted"><?= htmlspecialchars($r['address']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= $r['total_complaints'] ?></span>
                                        <?php if ($r['pending_count'] > 0): ?>
                                            <span class="badge bg-warning text-dark">
                                                <?= $r['pending_count'] ?> pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-people empty-icon"></i>
                    <h5>No complainants</h5>
                    <p>
                        <?php if (!empty($search)): ?>
                            No results for "<strong><?= htmlspecialchars($search) ?></strong>".
                            <a href="complainants.php">Clear search</a>.
                        <?php else: ?>
                            No complainants have been recorded yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
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