<?php
session_start();
include('C:/xampp/htdocs/food_ar_app/includes/db.php');
if(!isset($_SESSION['admin_id'])) exit();

$date = $_GET['date'] ?? '';
$date_only = date('Y-m-d', strtotime($date));

$reservations = [];

$sql = "
SELECT 
    r.ReservationID,
    r.SeatNumber,
    r.ReservationDate,
    r.Status,

    -- If user exists → take Users.FullName
    -- else → take walk-in FullName from Reservations table
    COALESCE(u.FullName, r.FullName) AS FullName,
    COALESCE(u.Email, r.Email) AS Email

FROM Reservations r
LEFT JOIN Users u ON r.UserID = u.UserID
WHERE CONVERT(date, r.ReservationDate) = ?
ORDER BY r.ReservationDate ASC
";

$stmt = sqlsrv_query($conn, $sql, [$date_only]);

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){

    // format datetime for JS table
    if($row['ReservationDate'] instanceof DateTime){
        $row['ReservationDate'] = $row['ReservationDate']->format('Y-m-d H:i');
    }

    $reservations[$row['SeatNumber']] = $row;
}

echo json_encode($reservations);
?>
