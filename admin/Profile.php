<?php
require_once '../includes/session.php';
require_once '../config/db.php';

require_admin();

$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_info') {

    $full_name      = trim($_POST['full_name']);
    $email          = trim($_POST['email']);
    $address        = trim($_POST['address']);
    $contact_number = trim($_POST['contact_number']);

    $errors = [];
    if (empty($full_name))                          $errors[] = "Full name is required.";
    if (empty($email))                              $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($address))                            $errors[] = "Address is required.";
    if (empty($contact_number))                     $errors[] = "Contact number is required.";

    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "Email is already used by another account.";
        }
        $check->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, address = ?, contact_number = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $full_name, $email, $address, $contact_number, $user_id);

        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email']     = $email;
            $message = "Profile updated successfully.";
            $msg_type = "success";
        } else {
            $message = "Update failed.";
            $msg_type = "danger";
        }
        $stmt->close();
    } else {
        $message = implode("<br>", array_map('htmlspecialchars', $errors));
        $msg_type = "danger";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'change_password') {

    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $errors = [];
    if (empty($current))   $errors[] = "Current password is required.";
    if (empty($new))       $errors[] = "New password is required.";
    if (strlen($new) < 6)  $errors[] = "New password must be at least 6 characters.";
    if ($new !== $confirm) $errors[] = "New passwords do not match.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (password_verify($current, $row['password'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);

            if ($stmt->execute()) {
                $message = "Password changed successfully.";
                $msg_type = "success";
            } else {
                $message = "Password update failed.";
                $msg_type = "danger";
            }
            $stmt->close();
        } else {
            $message = "Current password is incorrect.";
            $msg_type = "danger";
        }
    } else {
        $message = implode("<br>", array_map('htmlspecialchars', $errors));
        $msg_type = "danger";
    }
}

$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - Hustisyang Pambarangay</title>
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
            <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-person-gear"></i> Users</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a></li>
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

    <div class="mb-3">
        <h3 class="mb-0">My Profile</h3>
        <small class="text-muted">Update your personal information and password.</small>
    </div>

    <div class="row g-4">

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-3">
                    <strong><i class="bi bi-person"></i> Personal Information</strong>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_info">

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" 
                                   value="<?= ucfirst($user['role']) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" 
                                   value="<?= htmlspecialchars($user['address']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" 
                                   value="<?= htmlspecialchars($user['contact_number']) ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-3">
                    <strong><i class="bi bi-key"></i> Change Password</strong>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" 
                                   placeholder="Min 6 characters" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> 
                        Account created: <?= date('M d, Y', strtotime($user['created_at'])) ?>
                    </small>
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