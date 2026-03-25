<?php
session_start();
include('C:/xampp/htdocs/food_ar_app/includes/db.php'); // SQLSRV connection

// Admin protection
if (!isset($_SESSION['admin_id'])) {
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

// Get order ID
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
if ($order_id <= 0) die("Invalid order ID");

// Valid statuses
$valid_status = ['Pending', 'Accepted', 'Declined', 'Completed'];

if (isset($_POST['status']) && in_array($_POST['status'], $valid_status)) {
    $new_status = $_POST['status'];

    $sql = "UPDATE Orders 
            SET Status = ?, UpdatedAt = GETDATE() 
            WHERE OrderID = ?";

    $stmt = sqlsrv_query($conn, $sql, [$new_status, $order_id]);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    header("Location: /food_ar_app/admin/order/order_view.php?id=$order_id&msg=Order+updated+successfully");
    exit();
} else {
    die("Invalid status value");
}
?>
