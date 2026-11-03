<?php
require_once '../includes/session.php';
require_once '../config/db.php';

require_admin();

$errors  = [];
$success = '';
$ref_no  = '';

$categories = [
    'Noise Complaint',
    'Waste Management',
    'Public Disturbance',
    'Boundary Dispute',
    'Stray Animals',
    'Illegal Parking',
    'Others'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name        = trim($_POST['complainant_name']);
    $contact     = trim($_POST['complainant_contact']);
    $address     = trim($_POST['complainant_address']);
    $title       = trim($_POST['title']);
    $category    = $_POST['category'];
    $description = trim($_POST['description']);
    $evidence    = null;

    if (empty($name))              $errors[] = "Complainant name is required.";
    if (empty($contact))           $errors[] = "Contact number is required.";
    if (empty($address))           $errors[] = "Address is required.";
    if (empty($title))             $errors[] = "Complaint title is required.";
    if (strlen($title) > 150)      $errors[] = "Title is too long.";
    if (empty($category))          $errors[] = "Please select a category.";
    if (empty($description))       $errors[] = "Description is required.";
    if (strlen($description) < 10) $errors[] = "Description is too short.";

    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['evidence']['tmp_name'];
        $file_name = $_FILES['evidence']['name'];
        $file_size = $_FILES['evidence']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_ext, $allowed)) {
            $errors[] = "Invalid image type.";
        }
        if ($file_size > 5 * 1024 * 1024) {
            $errors[] = "Image is too large. Max 5MB.";
        }

        if (empty($errors)) {
            $new_name = 'evidence_' . time() . '_' . uniqid() . '.' . $file_ext;
            $upload_path = '../uploads/evidence/' . $new_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $evidence = $new_name;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (empty($errors)) {

        // Find or create complainant
        $check = $conn->prepare("SELECT id FROM complainants WHERE contact_number = ? LIMIT 1");
        $check->bind_param("s", $contact);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $complainant_id = $result->fetch_assoc()['id'];
            $conn->query("UPDATE complainants SET full_name = '$name', address = '$address' WHERE id = $complainant_id");
        } else {
            $insert = $conn->prepare("INSERT INTO complainants (full_name, contact_number, address) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $name, $contact, $address);
            $insert->execute();
            $complainant_id = $insert->insert_id;
            $insert->close();
        }
        $check->close();

        // Generate reference number
        $year = date('Y');
        $count = $conn->query("SELECT COUNT(*) as total FROM complaints WHERE YEAR(created_at) = $year")->fetch_assoc();
        $ref_no = 'KP-' . $year . '-' . str_pad($count['total'] + 1, 4, '0', STR_PAD_LEFT);

        // Insert complaint
        $stmt = $conn->prepare("
            INSERT INTO complaints 
                (reference_no, complainant_id, complainant_name, complainant_contact, complainant_address, 
                 title, category, description, evidence, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmt->bind_param("sisssssss", 
            $ref_no, $complainant_id, $name, $contact, $address, 
            $title, $category, $description, $evidence
        );

        if ($stmt->execute()) {
            $complaint_id = $stmt->insert_id;
            $stmt->close();

            $log = $conn->prepare("
                INSERT INTO complaint_logs (complaint_id, status, remarks, changed_by) 
                VALUES (?, 'Pending', 'Complaint recorded by staff.', ?)
            ");
            $log->bind_param("ii", $complaint_id, $_SESSION['user_id']);
            $log->execute();
            $log->close();

            $success = "Complaint recorded successfully.";
        } else {
            $errors[] = "Failed to save.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Complaint - Hustisyang Pambarangay</title>
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
        .section-heading { font-size: 12px; font-weight: 700; color: var(--primary-navy); text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 8px; border-bottom: 1px solid #e9ecef; margin-bottom: 16px; }
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
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <a href="complaints.php" class="btn btn-sm btn-light border mb-3">
                <i class="bi bi-arrow-left"></i> Back to Complaints
            </a>

            <?php if ($success): ?>
                <div class="alert alert-success shadow-sm">
                    <h5><i class="bi bi-check-circle-fill"></i> <?= $success ?></h5>
                    <hr>
                    <p class="mb-0">Reference Number: <strong class="text-primary fs-5"><?= htmlspecialchars($ref_no) ?></strong></p>
                    <div class="mt-3">
                        <a href="complaints.php" class="btn btn-sm btn-outline-success"><i class="bi bi-list-ul"></i> View All</a>
                        <a href="add_complaint.php" class="btn btn-sm btn-success"><i class="bi bi-plus-circle"></i> Record Another</a>
                    </div>
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

            <?php if (!$success): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="mb-1">Record New Complaint</h4>
                        <p class="text-muted small mb-4">Enter the complaint details as reported at the barangay hall.</p>

                        <form method="POST" enctype="multipart/form-data">

                            <div class="section-heading"><i class="bi bi-person"></i> Complainant Information</div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="complainant_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                    <input type="text" name="complainant_contact" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address <span class="text-danger">*</span></label>
                                    <input type="text" name="complainant_address" class="form-control" required>
                                </div>
                            </div>

                            <div class="section-heading"><i class="bi bi-file-earmark-text"></i> Complaint Details</div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-8">
                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" maxlength="150" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="category" class="form-select" required>
                                        <option value="">-- Select --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat ?>"><?= $cat ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea name="description" rows="5" class="form-control" required></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Evidence Photo <span class="text-muted">(optional)</span></label>
                                    <input type="file" name="evidence" class="form-control" accept="image/*">
                                    <small class="text-muted">JPG, PNG, GIF. Max 5MB.</small>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Complaint</button>
                                <a href="complaints.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
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