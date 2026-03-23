<?php
session_start();
include('../../includes/db.php');

header('Content-Type: application/json');

if(!isset($_SESSION['customer_id'])){
    echo json_encode([]);
    exit();
}

$customerID = $_SESSION['customer_id'];

$sql = "
SELECT TOP 10
    NotificationID,
    Message,
    IsRead,
    CreatedAt
FROM Notifications
WHERE UserID = ?
AND TargetRole = 'Customer'
ORDER BY CreatedAt DESC
";

$stmt = sqlsrv_query($conn, $sql, [$customerID]);

$notifications = [];

if($stmt !== false){

    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){

        $notifications[] = [
            "NotificationID" => $row["NotificationID"],
            "Message" => $row["Message"],
            "IsRead" => $row["IsRead"],
            "CreatedAt" => $row["CreatedAt"]
                ? $row["CreatedAt"]->format('Y-m-d H:i')
                : ""
        ];
    }
}

echo json_encode($notifications);