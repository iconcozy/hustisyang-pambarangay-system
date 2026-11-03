<?php
require_once '../includes/session.php';
require_once '../config/db.php';

require_super_admin();

$message  = '';
$msg_type = '';

if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $target_id = (int) $_GET['toggle'];

    if ($target_id === $_SESSION['user_id']) {
        $message = "You cannot deactivate your own account.";
        $msg_type = "warning";
    } else {
        $check = $conn->query("SELECT is_active, full_name FROM users WHERE id = $target_id");
        if ($check->num_rows > 0) {
            $user_row = $check->fetch_assoc();
            $new_status = $user_row['is_active'] ? 0 : 1;
            $action_word = $new_status ? 'activated' : 'deactivated';

            $conn->query("UPDATE users SET is_active = $new_status WHERE id = $target_id");

            $message = "Account for <strong>" . htmlspecialchars($user_row['full_name']) . "</strong> has been $action_word.";
            $msg_type = "success";
        }
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $target_id = (int) $_GET['delete'];

    if ($target_id === $_SESSION['user_id']) {
        $message = "You cannot delete your own account.";
        $msg_type = "warning";
    } else {
        $check = $conn->query("SELECT full_name FROM users WHERE id = $target_id");
        if ($check->num_rows > 0) {
            $user_row = $check->fetch_assoc();
            $conn->query("DELETE FROM users WHERE id = $target_id");
            $message = "Account for <strong>" . htmlspecialchars($user_row['full_name']) . "</strong> has been deleted.";
            $msg_type = "success";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {

    $full_name      = trim($_POST['full_name']);
    $email          = trim($_POST['email']);
    $password       = $_POST['password'];
    $role           = $_POST['role'];
    $address        = trim($_POST['address']);
    $contact_number = trim($_POST['contact_number']);

    $errors = [];
    if (empty($full_name))                          $errors[] = "Full name is required.";
    if (empty($email))                              $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($password))                           $errors[] = "Password is required.";
    if (strlen($password) < 6)                      $errors[] = "Password must be at least 6 characters.";
    if (!in_array($role, ['admin', 'staff']))       $errors[] = "Invalid role.";
    if (empty($address))                            $errors[] = "Address is required.";
    if (empty($contact_number))                     $errors[] = "Contact number is required.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email is already registered.";
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, address, contact_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $full_name, $email, $hashed, $role, $address, $contact_number);

        if ($stmt->execute()) {
            $message = "Account for <strong>" . htmlspecialchars($full_name) . "</strong> has been created.";
            $msg_type = "success";
        } else {
            $message = "Failed to create account.";
            $msg_type = "danger";
        }
        $stmt->close();
    } else {
        $message = implode("<br>", array_map('htmlspecialchars', $errors));
        $msg_type = "danger";
    }
}

$users_q = $conn->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM complaints c WHERE c.assigned_to = u.id AND c.is_archived = 0) AS assigned_count
    FROM users u
    ORDER BY u.role ASC, u.full_name ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Accounts - Hustisyang Pambarangay</title>
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
            <li class="nav-item"><a class="nav-link" href="complainants.php"><i class="bi bi-people"></i> Complainants</a></li>
            <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
            <?php if (is_admin()): ?>
            <li class="nav-item"><a class="nav-link active" href="users.php"><i class="bi bi-person-gear"></i> Users</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a></li>
        </ul>
    </div>
</div>

<div class="container py-4">

    <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">User Accounts</h3>
            <small class="text-muted">Manage admin and staff accounts.</small>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus"></i> Add User
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if ($users_q->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="d-none d-md-table-cell">Email</th>
                                <th class="d-none d-lg-table-cell">Contact</th>
                                <th>Role</th>
                                <th>Assigned</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($u = $users_q->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold small">
                                            <?= htmlspecialchars($u['full_name']) ?>
                                            <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-info text-dark" style="font-size: 9px;">You</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted d-md-none"><?= htmlspecialchars($u['email']) ?></small>
                                    </td>
                                    <td class="d-none d-md-table-cell"><small><?= htmlspecialchars($u['email']) ?></small></td>
                                    <td class="d-none d-lg-table-cell"><small><?= htmlspecialchars($u['contact_number']) ?></small></td>
                                    <td>
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge bg-primary">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Staff</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= $u['assigned_count'] ?></span></td>
                                    <td>
                                        <?php if ($u['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <?php if ($u['is_active']): ?>
                                                <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning"
                                                   onclick="return confirm('Deactivate this account?')">
                                                    <i class="bi bi-person-x"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm btn-outline-success"
                                                   onclick="return confirm('Activate this account?')">
                                                    <i class="bi bi-person-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Delete this account?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
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
                    <i class="bi bi-people empty-icon"></i>
                    <h5>No users</h5>
                    <p>Start by adding an admin or staff account.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add User Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address <span class="text-danger">*</span></label>
                        <input type="text" name="address" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Create Account</button>
                </div>
            </form>
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