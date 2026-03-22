<?php
session_start();
include('../../includes/db.php');
if(!isset($_SESSION['customer_id'])) exit('Unauthorized');

$user_id = $_SESSION['customer_id'];
$date = $_POST['date'] ?? '';
$seats = $_POST['seats'] ?? [];

if(!$date || empty($seats)) exit('Invalid input');

$reservation_date_sql = str_replace('T',' ',$date).":00";

foreach($seats as $seat){
    // Check if already booked
    $sql_check="SELECT * FROM Reservations WHERE SeatNumber=? AND ReservationDate=? AND Status='Accepted'";
    $stmt_check=sqlsrv_query($conn,$sql_check,[$seat,$reservation_date_sql]);
    if(sqlsrv_has_rows($stmt_check)) continue; // skip already booked

    // Insert reservation
    $sql_insert="INSERT INTO Reservations (UserID, SeatNumber, ReservationDate, Status) VALUES (?,?,?, 'Pending')";
    sqlsrv_query($conn,$sql_insert,[$user_id,$seat,$reservation_date_sql]);

    // Optional: create admin notification
    $sql_user = "SELECT FullName FROM Users WHERE UserID=?";
    $stmt_user = sqlsrv_query($conn, $sql_user, [$user_id]);
    $row_user = sqlsrv_fetch_array($stmt_user, SQLSRV_FETCH_ASSOC);
    $customer_name = $row_user['FullName'] ?? "Customer";

    $message = "$customer_name reserved Seat $seat";
    $sql_notify = "INSERT INTO Notifications (UserID, Message, TargetRole, NotificationType) VALUES (?,?,?,?)";
    sqlsrv_query($conn, $sql_notify, [1, $message, "Admin", "Reservation"]);
}

echo 'OK';
?>