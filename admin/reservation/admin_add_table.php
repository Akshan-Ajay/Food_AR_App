<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

include('C:/xampp/htdocs/food_ar_app/includes/db.php');

// Get POST data
$tableName = trim($_POST['table_name'] ?? '');
$seatCount = (int)($_POST['seat_count'] ?? 0);

// Validate input
if(!$tableName || $seatCount < 1){
    exit("Invalid input");
}

// Check if table already exists
$check = sqlsrv_query($conn, "SELECT TableID FROM CafeTables WHERE TableName = ?", [$tableName]);
if(sqlsrv_has_rows($check)){
    exit("Table name already exists!");
}

// Insert table
$insertTable = sqlsrv_query($conn, "INSERT INTO CafeTables (TableName) VALUES (?)", [$tableName]);
if($insertTable === false){
    exit("Error creating table");
}

// Get new TableID
$getTable = sqlsrv_query($conn, "SELECT TableID FROM CafeTables WHERE TableName = ?", [$tableName]);
$row = sqlsrv_fetch_array($getTable, SQLSRV_FETCH_ASSOC);
$tableID = $row['TableID'];

// Insert seats
for($i=1; $i <= $seatCount; $i++){
    // Seat format: TableName_S1, TableName_S2, etc.
    $seatNumber = $tableName . "_S" . $i;
    sqlsrv_query($conn, "INSERT INTO TableSeats (TableID, SeatNumber) VALUES (?, ?)", [$tableID, $seatNumber]);
}

echo "OK";
