<?php
require_once '../includes/session.php';
require_once '../config/db.php';

require_admin();

$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$status        = isset($_GET['status']) ? $_GET['status'] : 'all';
$category      = isset($_GET['category']) ? $_GET['category'] : 'all';
$date_from     = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to       = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$show_archived = isset($_GET['archived']) && $_GET['archived'] === '1';

$valid_status = ['all', 'Pending', 'In Progress', 'Resolved', 'Dismissed'];
if (!in_array($status, $valid_status)) {
    $status = 'all';
}

$archived_filter = $show_archived ? 1 : 0;

$sql = "SELECT * FROM complaints WHERE is_archived = $archived_filter";

if (!empty($search)) {
    $escaped = $conn->real_escape_string($search);
    $sql .= " AND (reference_no LIKE '%$escaped%' 
                   OR title LIKE '%$escaped%' 
                   OR complainant_name LIKE '%$escaped%')";
}

if ($status !== 'all') {
    $escaped = $conn->real_escape_string($status);
    $sql .= " AND status = '$escaped'";
}

if ($category !== 'all') {
    $escaped = $conn->real_escape_string($category);
    $sql .= " AND category = '$escaped'";
}

if (!empty($date_from)) {
    $escaped = $conn->real_escape_string($date_from);
    $sql .= " AND DATE(created_at) >= '$escaped'";
}

if (!empty($date_to)) {
    $escaped = $conn->real_escape_string($date_to);
    $sql .= " AND DATE(created_at) <= '$escaped'";
}

$sql .= " ORDER BY 
            CASE status 
                WHEN 'Pending' THEN 1 
                WHEN 'In Progress' THEN 2 
                WHEN 'Resolved' THEN 3 
                ELSE 4 
            END,
            created_at DESC";

$result = $conn->query($sql);
$total = $result->num_rows;

$cat_list = $conn->query("SELECT DISTINCT category FROM complaints WHERE is_archived = $archived_filter ORDER BY category");

$counts_q = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(status = 'Pending') as pending,
        SUM(status = 'In Progress') as in_progress,
        SUM(status = 'Resolved') as resolved,
        SUM(status = 'Dismissed') as dismissed
    FROM complaints 
    WHERE is_archived = $archived_filter
");
$counts = $counts_q->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complaints - Hustisyang Pambarangay</title>
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
        .table td { vertical-align: middle; }
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
            <li class="nav-item"><a class="nav-link active" href="complaints.php"><i class="bi bi-file-earmark-text"></i> Complaints</a></li>
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

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0"><?= $show_archived ? 'Archived Complaints' : 'Complaints' ?></h3>
            <small class="text-muted">Showing <?= $total ?> result<?= $total != 1 ? 's' : '' ?></small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($show_archived): ?>
                <a href="complaints.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Active
                </a>
            <?php else: ?>
                <a href="?archived=1" class="btn btn-outline-secondary">
                    <i class="bi bi-archive"></i> View Archived
                </a>
            <?php endif; ?>
            <a href="export_complaints.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel"></i> Export CSV
            </a>
            <?php if (!$show_archived): ?>
                <a href="add_complaint.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Record Complaint
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <?php if ($show_archived): ?>
                    <input type="hidden" name="archived" value="1">
                <?php endif; ?>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select">
                        <option value="all">All Categories</option>
                        <?php while ($cat = $cat_list->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100" title="Filter"><i class="bi bi-funnel"></i></button>
                </div>
                <div class="col-md-1">
                    <a href="complaints.php<?= $show_archived ? '?archived=1' : '' ?>" class="btn btn-outline-secondary w-100" title="Reset"><i class="bi bi-x-circle"></i></a>
                </div>
            </form>
        </div>
    </div>

    <ul class="nav nav-pills mb-3 flex-wrap gap-2">
        <li class="nav-item"><a class="nav-link <?= $status === 'all' ? 'active text-white' : 'bg-white border' ?>" href="?status=all<?= $show_archived ? '&archived=1' : '' ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">All <span class="badge bg-secondary ms-1"><?= $counts['total'] ?? 0 ?></span></a></li>
        <li class="nav-item"><a class="nav-link <?= $status === 'Pending' ? 'active text-white' : 'bg-white border' ?>" href="?status=Pending<?= $show_archived ? '&archived=1' : '' ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Pending <span class="badge bg-warning text-dark ms-1"><?= $counts['pending'] ?? 0 ?></span></a></li>
        <li class="nav-item"><a class="nav-link <?= $status === 'In Progress' ? 'active text-white' : 'bg-white border' ?>" href="?status=In Progress<?= $show_archived ? '&archived=1' : '' ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">In Progress <span class="badge bg-info text-dark ms-1"><?= $counts['in_progress'] ?? 0 ?></span></a></li>
        <li class="nav-item"><a class="nav-link <?= $status === 'Resolved' ? 'active text-white' : 'bg-white border' ?>" href="?status=Resolved<?= $show_archived ? '&archived=1' : '' ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Resolved <span class="badge bg-success ms-1"><?= $counts['resolved'] ?? 0 ?></span></a></li>
        <li class="nav-item"><a class="nav-link <?= $status === 'Dismissed' ? 'active text-white' : 'bg-white border' ?>" href="?status=Dismissed<?= $show_archived ? '&archived=1' : '' ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Dismissed <span class="badge bg-danger ms-1"><?= $counts['dismissed'] ?? 0 ?></span></a></li>
    </ul>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if ($total > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Ref No.</th>
                                <th>Title</th>
                                <th class="d-none d-md-table-cell">Complainant</th>
                                <th class="d-none d-lg-table-cell">Category</th>
                                <th>Status</th>
                                <th class="d-none d-lg-table-cell">Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($c = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><code class="text-primary small"><?= htmlspecialchars($c['reference_no']) ?></code></td>
                                    <td><div class="fw-semibold small"><?= htmlspecialchars($c['title']) ?></div></td>
                                    <td class="d-none d-md-table-cell"><small><?= htmlspecialchars($c['complainant_name']) ?></small></td>
                                    <td class="d-none d-lg-table-cell"><small class="text-muted"><?= htmlspecialchars($c['category']) ?></small></td>
                                    <td>
                                        <?php
                                        $badge_class = 'badge-pill-dot badge-dismissed';
                                        if ($c['status'] === 'Pending')     $badge_class = 'badge-pill-dot badge-pending';
                                        if ($c['status'] === 'In Progress') $badge_class = 'badge-pill-dot badge-progress';
                                        if ($c['status'] === 'Resolved')    $badge_class = 'badge-pill-dot badge-resolved';
                                        ?>
                                        <span class="<?= $badge_class ?>"><?= $c['status'] ?></span>
                                    </td>
                                    <td class="d-none d-lg-table-cell"><small class="text-muted"><?= date('M d, Y', strtotime($c['created_at'])) ?></small></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="view_complaint.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                            <?php if (!$show_archived && $c['status'] === 'Resolved'): ?>
                                                <a href="archive_complaint.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Archive this complaint?')"><i class="bi bi-archive"></i></a>
                                            <?php endif; ?>
                                            <?php if ($show_archived): ?>
                                                <a href="archive_complaint.php?id=<?= $c['id'] ?>&restore=1" class="btn btn-sm btn-outline-success" onclick="return confirm('Restore this complaint?')"><i class="bi bi-arrow-counterclockwise"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-search empty-icon"></i>
                    <h5>No complaints found</h5>
                    <p>No complaints match your current filter.</p>
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