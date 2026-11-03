<?php
require_once '../includes/session.php';
require_once '../config/db.php';

require_admin();

$id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$restore = isset($_GET['restore']) && $_GET['restore'] === '1';

if ($id <= 0) {
    header("Location: complaints.php");
    exit();
}

if ($restore) {
    $conn->query("UPDATE complaints SET is_archived = 0 WHERE id = $id");
    header("Location: complaints.php?archived=1");
} else {
    $conn->query("UPDATE complaints SET is_archived = 1 WHERE id = $id");
    header("Location: complaints.php");
}
exit();
?>