<?php
require_once '../includes/session.php';
require_once '../config/db.php';

require_admin();

$admin_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: complaints.php");
    exit();
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $new_status  = $_POST['status'];
    $remarks     = trim($_POST['remarks']);
    $resolution  = trim($_POST['resolution']);
    $is_internal = isset($_POST['is_internal']) ? 1 : 0;
    $assigned_to = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? (int) $_POST['assigned_to'] : null;

    $valid_status = ['Pending', 'In Progress', 'Resolved', 'Dismissed'];
    if (!in_array($new_status, $valid_status)) {
        $errors[] = "Invalid status selected.";
    }
    if (empty($remarks)) {
        $errors[] = "Remarks are required.";
    }
    if ($new_status === 'Resolved' && empty($resolution)) {
        $errors[] = "Resolution is required when marking as Resolved.";
    }

    if (empty($errors)) {

        $assigned_sql = $assigned_to !== null ? $assigned_to : 'NULL';
        $assigned_at = $assigned_to ? "NOW()" : "assigned_at";

        if ($new_status === 'Resolved') {
            $update = $conn->prepare("
                UPDATE complaints 
                SET status = ?, resolution = ?, resolved_at = NOW(), 
                    assigned_to = $assigned_sql, assigned_at = $assigned_at
                WHERE id = ?
            ");
            $update->bind_param("ssi", $new_status, $resolution, $id);
        } else {
            $update = $conn->prepare("
                UPDATE complaints 
                SET status = ?, assigned_to = $assigned_sql, assigned_at = $assigned_at
                WHERE id = ?
            ");
            $update->bind_param("si", $new_status, $id);
        }

        if ($update->execute()) {
            $update->close();

            $log = $conn->prepare("
                INSERT INTO complaint_logs (complaint_id, status, remarks, changed_by, is_internal) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $log->bind_param("issii", $id, $new_status, $remarks, $admin_id, $is_internal);
            $log->execute();
            $log->close();

            $success = "Complaint updated successfully.";
        } else {
            $errors[] = "Update failed.";
        }
    }
}

$complaint = $conn->query("SELECT * FROM complaints WHERE id = $id")->fetch_assoc();

if (!$complaint) {
    header("Location: complaints.php");
    exit();
}

$logs = $conn->query("
    SELECT cl.*, u.full_name AS admin_name
    FROM complaint_logs cl
    LEFT JOIN users u ON cl.changed_by = u.id
    WHERE cl.complaint_id = $id
    ORDER BY cl.created_at DESC
");

$staff = $conn->query("SELECT id, full_name, role FROM users WHERE is_active = 1 ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complaint Details - Hustisyang Pambarangay</title>
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
        .timeline { position: relative; padding-left: 30px; }
        .timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #dee2e6; }
        .timeline-item { position: relative; padding-bottom: 20px; }
        .timeline-item::before { content: ''; position: absolute; left: -24px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: var(--primary-navy); border: 2px solid #fff; box-shadow: 0 0 0 2px var(--primary-navy); }
        .info-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #94A3B8; margin-bottom: 2px; }
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

    <a href="complaints.php" class="btn btn-sm btn-light border mb-3">
        <i class="bi bi-arrow-left"></i> Back to Complaints
    </a>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle-fill"></i> <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm">
            <strong><i class="bi bi-exclamation-triangle-fill"></i> Please fix:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-lg-7">

            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <code class="text-primary"><?= htmlspecialchars($complaint['reference_no']) ?></code>
                            <h4 class="mb-0 mt-1"><?= htmlspecialchars($complaint['title']) ?></h4>
                        </div>
                        <?php
                        $badge_class = 'badge-pill-dot badge-dismissed';
                        if ($complaint['status'] === 'Pending')     $badge_class = 'badge-pill-dot badge-pending';
                        if ($complaint['status'] === 'In Progress') $badge_class = 'badge-pill-dot badge-progress';
                        if ($complaint['status'] === 'Resolved')    $badge_class = 'badge-pill-dot badge-resolved';
                        ?>
                        <span class="<?= $badge_class ?> fs-6"><?= $complaint['status'] ?></span>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="info-label">Category</div>
                            <strong><i class="bi bi-tag"></i> <?= htmlspecialchars($complaint['category']) ?></strong>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Date Recorded</div>
                            <strong><i class="bi bi-calendar"></i> <?= date('M d, Y h:i A', strtotime($complaint['created_at'])) ?></strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="info-label">Description</div>
                        <p><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
                    </div>

                    <?php if (!empty($complaint['evidence'])): ?>
                        <div class="mb-3">
                            <div class="info-label mb-2">Evidence</div>
                            <img src="../uploads/evidence/<?= htmlspecialchars($complaint['evidence']) ?>" 
                                 alt="Evidence" class="img-fluid rounded border" 
                                 style="max-height: 350px; cursor: pointer;"
                                 onclick="window.open(this.src, '_blank')">
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header py-3">
                    <strong><i class="bi bi-person-badge"></i> Complainant Information</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-label">Name</div>
                            <strong><?= htmlspecialchars($complaint['complainant_name']) ?></strong>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Contact Number</div>
                            <strong><?= htmlspecialchars($complaint['complainant_contact']) ?></strong>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="info-label">Address</div>
                            <span><?= htmlspecialchars($complaint['complainant_address']) ?></span>
                        </div>

                        <?php if ($complaint['assigned_to']): 
                            $assigned = $conn->query("SELECT full_name, role FROM users WHERE id = " . (int) $complaint['assigned_to'])->fetch_assoc();
                        ?>
                            <div class="col-12 mt-3 pt-3 border-top">
                                <div class="info-label">Assigned To</div>
                                <strong><i class="bi bi-person-check-fill text-success"></i> <?= htmlspecialchars($assigned['full_name'] ?? 'Unknown') ?></strong>
                                <small class="text-muted">
                                    (<?= ucfirst($assigned['role'] ?? '') ?>)
                                    <?php if ($complaint['assigned_at']): ?>
                                        — <?= date('M d, Y h:i A', strtotime($complaint['assigned_at'])) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-5">

            <div class="card border-0 shadow-sm">
                <div class="card-header py-3">
                    <strong><i class="bi bi-pencil-square"></i> Update Complaint</strong>
                </div>
                <div class="card-body">
                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label">Assign To</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- Unassigned --</option>
                                <?php while ($s = $staff->fetch_assoc()): ?>
                                    <option value="<?= $s['id'] ?>" <?= $complaint['assigned_to'] == $s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['full_name']) ?> (<?= ucfirst($s['role']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Pending"     <?= $complaint['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="In Progress" <?= $complaint['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Resolved"    <?= $complaint['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="Dismissed"   <?= $complaint['status'] === 'Dismissed' ? 'selected' : '' ?>>Dismissed</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Remarks <span class="text-danger">*</span></label>
                            <textarea name="remarks" rows="3" class="form-control" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Resolution <span class="text-muted">(required if Resolved)</span></label>
                            <textarea name="resolution" rows="3" class="form-control"><?= htmlspecialchars($complaint['resolution'] ?? '') ?></textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_internal" id="is_internal" value="1">
                            <label class="form-check-label small" for="is_internal">
                                <i class="bi bi-lock-fill"></i> Internal note
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Save Update
                        </button>

                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header py-3">
                    <strong><i class="bi bi-clock-history"></i> History</strong>
                </div>
                <div class="card-body">
                    <?php if ($logs->num_rows > 0): ?>
                        <div class="timeline">
                            <?php while ($log = $logs->fetch_assoc()): ?>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between">
                                        <strong class="small"><?= htmlspecialchars($log['status']) ?></strong>
                                        <small class="text-muted"><?= date('M d, h:i A', strtotime($log['created_at'])) ?></small>
                                    </div>
                                    <?php if (!empty($log['remarks'])): ?>
                                        <p class="text-muted small mb-1 mt-1"><?= nl2br(htmlspecialchars($log['remarks'])) ?></p>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        by <em><?= htmlspecialchars($log['admin_name'] ?? 'System') ?></em>
                                        <?php if ($log['is_internal']): ?>
                                            <span class="badge bg-secondary ms-1" style="font-size: 9px;">Internal</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No history yet.</p>
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