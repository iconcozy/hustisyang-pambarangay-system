<?php
require_once 'config/db.php';

// Generate fresh hash for 'admin123'
$new_hash = password_hash('admin123', PASSWORD_DEFAULT);

echo "<h2>Regenerating admin password...</h2>";
echo "<p>New hash: <code>" . $new_hash . "</code></p>";
echo "<p>Length: " . strlen($new_hash) . " characters</p>";

// Update admin account
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@hustisya.com'");
$stmt->bind_param("s", $new_hash);

if ($stmt->execute()) {
    echo "<h2 style='color:green'>✅ Password updated!</h2>";
    echo "<p>Affected rows: " . $stmt->affected_rows . "</p>";
} else {
    echo "<h2 style='color:red'>❌ Failed: " . $conn->error . "</h2>";
}

$stmt->close();
$conn->close();

echo "<hr>";
echo "<p><a href='auth/login.php'>→ Go to Login Page</a></p>";
?>