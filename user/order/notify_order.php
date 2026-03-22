<?php

session_start();
include('../includes/db.php');

$userId = $_SESSION['UserID'];

$message = "Your order has been received and is being prepared.";

$sql = "INSERT INTO Notifications (UserID, Message) VALUES (?, ?)";
$params = [$userId, $message];

sqlsrv_query($conn, $sql, $params);

?>