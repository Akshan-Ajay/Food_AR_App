<?php
include("C:/xampp/htdocs/food_ar_app/includes/db.php");

// Only update notifications that haven't been read yet to save processing power
$sql = "UPDATE Notifications 
        SET IsRead = 1 
        WHERE TargetRole = 'Admin' AND IsRead = 0";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => sqlsrv_errors()]);
} else {
    echo json_encode(["status" => "success"]);
}