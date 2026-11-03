<?php
require_once '../includes/session.php';
require_once '../config/db.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: /hustisyang-pambarangay/admin/dashboard.php");
    exit();
}

$error = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {

        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_active = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->bind_param("ssi", $token, $expires, $user['id']);
            $update->execute();
            $update->close();

            $reset_link = "http://localhost/hustisyang-pambarangay/auth/reset_password.php?token=" . $token;

        } else {
            $error = "If that email is registered, a reset link will be generated.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Hustisyang Pambarangay</title>
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
            <h4>Forgot Password</h4>
            <p>Password Reset</p>
        </div>

        <div class="card-body-custom">

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($reset_link): ?>
                <div class="alert alert-success">
                    <h6><i class="bi bi-check-circle-fill"></i> Reset link generated</h6>
                    <p class="small mb-2">
                        <strong>Demo mode:</strong> In production, this link is sent via email.
                    </p>
                    <a href="<?= htmlspecialchars($reset_link) ?>" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-key"></i> Reset My Password
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!$reset_link): ?>
                <p class="text-muted small mb-3">
                    Enter your registered email address to generate a password reset link.
                </p>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-envelope"></i> Email Address
                        </label>
                        <input type="email" name="email" class="form-control" 
                               placeholder="Enter your registered email" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-send"></i> Send Reset Link
                    </button>
                </form>
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