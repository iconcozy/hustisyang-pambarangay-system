<?php
require_once 'includes/session.php';

// If logged in, redirect to admin dashboard
if (is_logged_in()) {
    header("Location: admin/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hustisyang Pambarangay - Complaint Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
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
        .hero-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }
        .hero-content {
            text-align: center;
            color: #fff;
            max-width: 700px;
        }
        .hero-content img {
            height: 120px;
            width: 120px;
            margin-bottom: 20px;
            filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.4));
        }
        .hero-content h1 {
            font-family: Georgia, serif;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 8px;
        }
        .hero-content .tagline {
            color: var(--accent-gold);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 30px;
            font-weight: 600;
        }
        .hero-content p.lead {
            color: #cfd8e3;
            font-size: 15px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn-gold {
            background-color: var(--accent-gold);
            color: var(--primary-navy);
            border: none;
            padding: 12px 28px;
            font-weight: 700;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-gold:hover {
            background-color: #d4a820;
            color: var(--primary-navy);
            transform: translateY(-2px);
        }
        .btn-outline-light-custom {
            border: 2px solid #fff;
            color: #fff;
            padding: 10px 26px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-outline-light-custom:hover {
            background-color: #fff;
            color: var(--primary-navy);
            transform: translateY(-2px);
        }
        .footer-barangay {
            color: #a8b4c4;
            text-align: center;
            padding: 20px;
            font-size: 12px;
        }
        .footer-barangay .gold { color: var(--accent-gold); }
        .footer-barangay a { color: #cfd8e3; text-decoration: none; }
        .footer-barangay a:hover { color: var(--accent-gold); }
    </style>
</head>
<body>

<div class="hero-wrapper">
    <div class="hero-content">

        <img src="assets/img/logo.svg" alt="Barangay Logo">

        <h1>Hustisyang Pambarangay</h1>
        <div class="tagline">Complaint Management System</div>

        <p class="lead">
            Submit complaints online and track their status without going to the barangay hall.
        </p>

        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="complaint_form.php" class="btn btn-gold">
                <i class="bi bi-file-earmark-plus"></i> File a Complaint
            </a>
            <a href="track_complaint.php" class="btn btn-outline-light-custom">
                <i class="bi bi-search"></i> Track Complaint
            </a>
        </div>

    </div>
</div>

<div class="footer-barangay">
    <strong class="gold">Hustisyang Pambarangay</strong> &copy; <?= date('Y') ?>
    <br>
    <small>Complaint Management System</small>
    <br><br>
    <a href="auth/login.php">
        <i class="bi bi-shield-lock"></i> Staff Login
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>