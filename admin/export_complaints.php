<?php
require_once '../includes/session.php';
require_once '../config/db.php';

require_admin();

$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$status        = isset($_GET['status']) ? $_GET['status'] : 'all';
$category      = isset($_GET['category']) ? $_GET['category'] : 'all';
$date_from     = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to       = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$show_archived = isset($_GET['archived']) && $_GET['archived'] === '1';

$valid_status = ['all', 'Pending', 'In Progress', 'Resolved', 'Dismissed'];
if (!in_array($status, $valid_status)) {
    $status = 'all';
}

$archived_filter = $show_archived ? 1 : 0;

$sql = "SELECT * FROM complaints WHERE is_archived = $archived_filter";

if (!empty($search)) {
    $escaped = $conn->real_escape_string($search);
    $sql .= " AND (reference_no LIKE '%$escaped%' 
                   OR title LIKE '%$escaped%' 
                   OR complainant_name LIKE '%$escaped%')";
}

if ($status !== 'all') {
    $escaped = $conn->real_escape_string($status);
    $sql .= " AND status = '$escaped'";
}

if ($category !== 'all') {
    $escaped = $conn->real_escape_string($category);
    $sql .= " AND category = '$escaped'";
}

if (!empty($date_from)) {
    $escaped = $conn->real_escape_string($date_from);
    $sql .= " AND DATE(created_at) >= '$escaped'";
}

if (!empty($date_to)) {
    $escaped = $conn->real_escape_string($date_to);
    $sql .= " AND DATE(created_at) <= '$escaped'";
}

$sql .= " ORDER BY created_at DESC";

$result = $conn->query($sql);

$filename = 'complaints_export_' . date('Y-m