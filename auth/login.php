<?php
require_once '../includes/session.php';
require_once '../config/db.php';

if (is_logged_in()) {
    header("Location: /hustisyang-pambarangay/admin/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ? AND is_active = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                header("Location: /hustisyang-pambarangay/admin/dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Hustisyang Pambarangay</title>
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
        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            border-top: 6px solid var(--accent-gold);
        }
        .login-header {
            text-align: center;
            padding: 32px 30px 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .login-header img {
            height: 80px;
            width: 80px;
            margin-bottom: 12px;
        }
        .login-header h4 {
            color: var(--primary-navy);
            font-family: Georgia, serif;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .login-header p {
            color: #6c757d;
            font-size: 13px;
            margin-bottom: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .login-body {
            padding: 30px;
        }
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
        .login-footer {
            text-align: center;
            padding: 20px;
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

<div class="login-wrapper">
    <div class="login-card">

        <div class="login-header">
            <img src="../assets/img/logo.svg" alt="Barangay Logo">
            <h4>Hustisyang Pambarangay</h4>
            <p>Administrator Login</p>
        </div>

        <div class="login-body">

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-envelope"></i> Email
                    </label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="Enter your email" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-lock"></i> Password
                    </label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Enter your password" required>
                </div>
                <div class="text-end mb-3">
                    <a href="forgot_password.php" class="text-decoration-none small" style="color: var(--primary-navy); font-weight: 600;">
                        <i class="bi bi-question-circle"></i> Forgot Password?
                    </a>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </form>

        </div>

        <div class="login-footer">
            <small class="text-muted">
                Authorized personnel only.
            </small>
        </div>

    </div>
</div>

<div class="footer-barangay">
    <strong class="gold">Hustisyang Pambarangay</strong> &copy; <?= date('Y') ?>
    <br>
    <small>Complaint Management System</small>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>