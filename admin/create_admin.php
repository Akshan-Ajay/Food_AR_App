<?php
include('../includes/db.php');

$email = "admin1@cafe.com";
$password = "admin123";

// Create real secure hash
$hash = password_hash($password, PASSWORD_DEFAULT);

// Update admin password
$sql = "UPDATE Users SET PasswordHash = ? WHERE Email = ?";
$params = array($hash, $email);

$stmt = sqlsrv_query($conn, $sql, $params);

if($stmt){
    echo "Admin password updated successfully!";
} else {
    print_r(sqlsrv_errors());
}
?>
