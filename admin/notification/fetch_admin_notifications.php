<?php
include("C:/xampp/htdocs/food_ar_app/includes/db.php");

header('Content-Type: application/json');

// Using a JOIN to get the name of the user who triggered the notification
$sql = "
    SELECT TOP 10 
        N.NotificationID,
        N.Message,
        N.IsRead,
        N.CreatedAt,
        U.FullName
    FROM Notifications N
    LEFT JOIN Users U ON N.UserID = U.UserID
    WHERE N.TargetRole = 'Admin'
    ORDER BY N.CreatedAt DESC
";

$stmt = sqlsrv_query($conn, $sql);
$notifications = [];

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $notifications[] = [
            "NotificationID" => $row["NotificationID"],
            "FullName"       => $row["FullName"] ?? "System",
            "Message"        => $row["Message"],
            "IsRead"         => $row["IsRead"],
            "CreatedAt"      => $row["CreatedAt"] ? $row["CreatedAt"]->format('M d, H:i') : ""
        ];
    }
}

echo json_encode($notifications);