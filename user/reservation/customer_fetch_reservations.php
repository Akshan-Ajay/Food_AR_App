<?php
session_start();
include('../../includes/db.php');

$date = $_GET['date'] ?? '';
if(!$date) exit(json_encode([]));

$date_only = date('Y-m-d', strtotime($date));

$reservations = [];

$sql = "SELECT SeatNumber, Status, UserID
        FROM Reservations
        WHERE CONVERT(date, ReservationDate) = ?
        AND Status IN ('Pending','Accepted')";

$stmt = sqlsrv_query($conn,$sql,[$date_only]);

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $reservations[$row['SeatNumber']] = $row;
}

echo json_encode($reservations);
?>
