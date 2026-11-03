<?php
require_once '../includes/session.php';
require_once '../config/db.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: /hustisyang-pambarangay/admin/dashboard.php");
    exit();
}

$error = '';
$success = '';
$token_valid = false;
$user_data = null;

$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = "Invalid or missing reset token.";
} else {

    $stmt = $conn->prepare("
        SELECT id, full_name 
        FROM users 
        WHERE reset_token = ? 
        AND reset_expires > NOW() 
        AND is_active = 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $token_valid = true;
        $user_data = $result->fetch_assoc();
    } else {
        $error = "This reset link is invalid or has expired.";
    }
    $stmt->close();
}

if ($token_valid && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($password)) {
        $error = "Password is required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $update = $conn->prepare("
            UPDATE users 
            SET password = ?, reset_token = NULL, reset_expires = NULL 
            WHERE id = ?
        ");
        $update->bind_param("si", $hashed, $user_data['id']);

        if ($update->execute()) {
            $success = "Password reset successful. You may now <a href='login.php' class='alert-link'>login</a>.";
            $token_valid = false;
        } else {
            $error = "Failed to reset password. Please try again.";
        }
        $update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Hustisyang Pambarangay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        :root {
            --primary-navy: #1A3A6C;
            --accent-gold: #F4C430;
        }
        body {
            background: linear-gradient(135deg, #1A3A6C 0%, #142c52 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }
        .card-box {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            border-top: 6px solid var(--accent-gold);
        }
        .card-head {
            text-align: center;
            padding: 30px 30px 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .card-head img {
            height: 70px;
            width: 70px;
            margin-bottom: 10px;
        }
        .card-head h4 {
            color: var(--primary-navy);
            font-family: Georgia, serif;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .card-head p {
            color: #6c757d;
            font-size: 12px;
            margin-bottom: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .card-body-custom { padding: 30px; }
        .btn-primary {
            background-color: var(--primary-navy);
            border-color: var(--primary-navy);
            padding: 10px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #142c52;
            border-color: #142c52;
        }
        .form-control:focus {
            border-color: var(--primary-navy);
            box-shadow: 0 0 0 0.2rem rgba(26, 58, 108, 0.15);
        }
        .card-footer-custom {
            text-align: center;
            padding: 18px;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        .footer-barangay {
            color: #cfd8e3;
            text-align: center;
            padding: 20px 0;
            font-size: 12px;
        }
        .footer-barangay .gold { color: var(--accent-gold); }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="card-box">

        <div class="card-head">
            <img src="../assets/img/logo.svg" alt="Barangay Logo">
            <h4>Reset Password</h4>
            <p>Set New Password</p>
        </div>

        <div class="card-body-custom">

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if ($token_valid): ?>

                <p class="text-muted small mb-3">
                    Hello <strong><?= htmlspecialchars($user_data['full_name']) ?></strong>, please enter your new password.
                </p>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-lock"></i> New Password
                        </label>
                        <input type="password" name="password" class="form-control" 
                               placeholder="Minimum 6 characters" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-lock-fill"></i> Confirm New Password
                        </label>
                        <input type="password" name="confirm_password" class="form-control" 
                               placeholder="Re-enter password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-key"></i> Reset Password
                    </button>
                </form>

            <?php elseif (!$success): ?>

                <p class="text-muted small mb-0">
                    This password reset link is invalid or has expired.
                </p>
                <a href="forgot_password.php" class="btn btn-primary w-100 mt-3">
                    <i class="bi bi-arrow-clockwise"></i> Request New Link
                </a>

            <?php endif; ?>

        </div>

        <div class="card-footer-custom">
            <small class="text-muted">
                <a href="login.php" class="text-decoration-none" style="color: var(--primary-navy); font-weight: 600;">
                    <i class="bi bi-arrow-left"></i> Back to Login
                </a>
            </small>
        </div>

    </div>
</div>

<div class="footer-barangay">
    <strong class="gold">Hustisyang Pambarangay</strong> &copy; <?= date('Y') ?>
    <br>
    <small>Complaint Management System</small>
</div>

</body>
</html>