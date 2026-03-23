<?php
session_start();
include('../includes/db.php');

$customer_id = $_SESSION['customer_id'];

$lat = $_POST['lat'];
$lon = $_POST['lon'];

$sql = "UPDATE Users SET Latitude=?, Longitude=? WHERE UserID=?";
$params = [$lat,$lon,$customer_id];

sqlsrv_query($conn,$sql,$params);
?>
