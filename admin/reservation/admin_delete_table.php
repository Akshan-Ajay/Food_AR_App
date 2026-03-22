<?php
session_start();
include('C:/xampp/htdocs/food_ar_app/includes/db.php');

if(!isset($_SESSION['admin_id'])){
    exit("Unauthorized");
}

// Get table ID from POST
$tableID = $_POST['table_id'] ?? '';
if(!$tableID) exit("Invalid table ID");

// Delete seats first
$delSeats = sqlsrv_query($conn,
    "DELETE FROM TableSeats WHERE TableID=?",
    [$tableID]
);
if($delSeats === false){ die(print_r(sqlsrv_errors(), true)); }

// Delete table
$delTable = sqlsrv_query($conn,
    "DELETE FROM CafeTables WHERE TableID=?",
    [$tableID]
);
if($delTable === false){ die(print_r(sqlsrv_errors(), true)); }

echo "Deleted";
?>
