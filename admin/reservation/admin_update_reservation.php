<?php
session_start();
include('C:/xampp/htdocs/food_ar_app/includes/db.php');
if(!isset($_SESSION['admin_id'])) exit();

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? 0;

if($action && $id){
    // Fetch reservation info for notification
    $sql_res = "SELECT UserID, SeatNumber FROM Reservations WHERE ReservationID=?";
    $stmt_res = sqlsrv_query($conn, $sql_res, [$id]);
    $res_row = sqlsrv_fetch_array($stmt_res, SQLSRV_FETCH_ASSOC);
    $customer_id = $res_row['UserID'] ?? 0;
    $seat = $res_row['SeatNumber'] ?? '';

    if(in_array($action, ['Accepted','Declined'])){
        $sql = "UPDATE Reservations SET Status=?, UpdatedAt=GETDATE() WHERE ReservationID=?";
        sqlsrv_query($conn, $sql, [$action, $id]);

        // --- Notify customer ---
        $message = "Your reservation for Seat $seat has been $action by Admin.";
        $role = "Customer";
        $type = "Reservation";
        $sql_notify = "INSERT INTO Notifications (UserID, Message, TargetRole, NotificationType)
                       VALUES (?, ?, ?, ?)";
        sqlsrv_query($conn, $sql_notify, [$customer_id, $message, $role, $type]);
        // --- End notification ---
    } elseif($action=='Delete'){
        $sql = "DELETE FROM Reservations WHERE ReservationID=?";
        sqlsrv_query($conn, $sql, [$id]);
        // Optionally notify customer about deletion
        $message = "Your reservation for Seat $seat has been deleted by Admin.";
        $sql_notify = "INSERT INTO Notifications (UserID, Message, TargetRole, NotificationType)
                       VALUES (?, ?, ?, ?)";
        sqlsrv_query($conn, $sql_notify, [$customer_id, $message, 'Customer', 'Reservation']);
    }
}
?>