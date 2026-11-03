<?php
require_once 'config/db.php';

$ref_no = isset($_GET['ref']) ? trim($_GET['ref']) : '';
$complaint = null;
$logs = null;
$error = '';

if (!empty($ref_no)) {

    $stmt = $conn->prepare("SELECT * FROM complaints WHERE reference_no = ? AND is_archived = 0");
    $stmt->bind_param("s", $ref_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $complaint = $result->fetch_assoc();

        $log_stmt = $conn->prepare("
            SELECT status, remarks, created_at 
            FROM complaint_logs 
            WHERE complaint_id = ? AND is_internal = 0
            ORDER BY created_at ASC
        ");
        $log_stmt->bind_param("i", $complaint['id']);
        $log_stmt->execute();
        $logs = $log_stmt->get_result();
        $log_stmt->close();
    } else {
        $error = "No complaint found with that reference number.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Complaint - Hustisyang Pambarangay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <style>
        :root { --primary-navy: #1A3A6C; --accent-gold: #F4C430; }
        .navbar-barangay { background-color: var(--primary-navy); border-bottom: 4px solid var(--accent-gold); padding: 8px 0; }
        .navbar-barangay .navbar-brand { display: flex; align-items: center; gap: 12px; color: #fff; font-family: Georgia, serif; font-weight: 700; text-decoration: none; }
        .navbar-barangay .navbar-brand img { height: 48px; width: 48px; }
        .navbar-barangay .brand-text { display: flex; flex-direction: column; line-height: 1.1; }
        .navbar-barangay .brand-text .main { font-size: 16px; color: #fff; }
        .navbar-barangay .brand-text .sub { font-size: 10px; color: var(--accent-gold); font-family: 'Segoe UI', sans-serif; letter-spacing: 1px; text-transform: uppercase; }
        .footer-barangay { background-color: var(--primary-navy); color: #cfd8e3; padding: 20px 0; margin-top: 40px; border-top: 4px solid var(--accent-gold); }
        .footer-barangay .gold { color: var(--accent-gold); }
        .timeline { position: relative; padding-left: 30px; }
        .timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #dee2e6; }
        .timeline-item { position: relative; padding-bottom: 20px; }
        .timeline-item::before { content: ''; position: absolute; left: -24px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: var(--primary-navy); border: 2px solid #fff; box-shadow: 0 0 0 2px var(--primary-navy); }
    </style>
</head>
<body>

<nav class="navbar navbar-barangay">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/img/logo.svg" alt="Barangay Logo">
            <div class="brand-text">
                <span class="main">Hustisyang Pambarangay</span>
                <span class="sub">Complaint Management System</span>
            </div>
        </a>
        <a href="index.php" class="btn btn-sm btn-light">
            <i class="bi bi-arrow-left"></i> Back to Home
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="mb-1">Track Your Complaint</h3>
                    <p class="text-muted small mb-3">
                        Enter your reference number to check the status of your complaint.
                    </p>

                    <form method="GET">
                        <div class="input-group">
                            <input type="text" name="ref" class="form-control" 
                                   placeholder="e.g. KP-2026-0001" 
                                   value="<?= htmlspecialchars($ref_no) ?>" required>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Track
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($complaint): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <code class="text-primary"><?= htmlspecialchars($complaint['reference_no']) ?></code>
                                <h5 class="mb-0 mt-1"><?= htmlspecialchars($complaint['title']) ?></h5>
                            </div>
                            <?php
                            $badge_class = 'badge-pill-dot badge-dismissed';
                            if ($complaint['status'] === 'Pending')     $badge_class = 'badge-pill-dot badge-pending';
                            if ($complaint['status'] === 'In Progress') $badge_class = 'badge-pill-dot badge-progress';
                            if ($complaint['status'] === 'Resolved')    $badge_class = 'badge-pill-dot badge-resolved';
                            ?>
                            <span class="<?= $badge_class ?>"><?= $complaint['status'] ?></span>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Category</small>
                                <strong><?= htmlspecialchars($complaint['category']) ?></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Date Submitted</small>
                                <strong><?= date('M d, Y', strtotime($complaint['created_at'])) ?></strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Description</small>
                            <p><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
                        </div>

                        <?php if (!empty($complaint['evidence'])): ?>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-2">Evidence</small>
                                <img src="uploads/evidence/<?= htmlspecialchars($complaint['evidence']) ?>" 
                                     class="img-fluid rounded border" style="max-height: 250px; cursor: pointer;"
                                     onclick="window.open(this.src, '_blank')">
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($complaint['resolution'])): ?>
                            <div class="alert alert-success">
                                <h6><i class="bi bi-check-circle-fill"></i> Resolution</h6>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($complaint['resolution'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header py-3">
                        <strong><i class="bi bi-clock-history"></i> Status Timeline</strong>
                    </div>
                    <div class="card-body">
                        <?php if ($logs && $logs->num_rows > 0): ?>
                            <div class="timeline">
                                <?php while ($log = $logs->fetch_assoc()): ?>
                                    <div class="timeline-item">
                                        <div class="d-flex justify-content-between">
                                            <strong class="small"><?= htmlspecialchars($log['status']) ?></strong>
                                            <small class="text-muted">
                                                <?= date('M d, Y h:i A', strtotime($log['created_at'])) ?>
                                            </small>
                                        </div>
                                        <?php if (!empty($log['remarks'])): ?>
                                            <p class="text-muted small mb-0 mt-1">
                                                <?= nl2br(htmlspecialchars($log['remarks'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small mb-0">No timeline entries yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<footer class="footer-barangay">
    <div class="container text-center">
        <strong class="gold">Hustisyang Pambarangay</strong> &copy; <?= date('Y') ?>
        <br>
        <small>Complaint Management System</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>