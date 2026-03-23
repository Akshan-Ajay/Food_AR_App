<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

include('C:/xampp/htdocs/food_ar_app/includes/db.php');

// Get POST data
$fullName = $_POST['FullName'] ?? '';
$email = $_POST['Email'] ?? '';
$date = $_POST['Date'] ?? '';
$time = $_POST['Time'] ?? '';
$seat = $_POST['SeatNumber'] ?? '';

if(!$fullName || !$email || !$date || !$time || !$seat){
    echo "Please fill all required fields.";
    exit;
}

// Determine seats to book
$seatsToBook = [];

// Full table selected
if(str_starts_with($seat, 'table_')){
    $tableID = str_replace('table_', '', $seat);
    $sqlSeats = "SELECT SeatNumber FROM TableSeats WHERE TableID = ?";
    $params = [$tableID];
    $stmt = sqlsrv_query($conn, $sqlSeats, $params);

    if($stmt === false){
        echo "Database error fetching seats.";
        exit;
    }

    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $seatsToBook[] = $row['SeatNumber'];
    }

} else {
    // Single seat selected
    $seatsToBook[] = $seat;
}

// Insert reservation for each seat
foreach($seatsToBook as $s){
    $sqlInsert = "INSERT INTO Reservations (SeatNumber, FullName, Email, ReservationDate, Status)
                  VALUES (?, ?, ?, ?, 'Accepted')";
    $paramsInsert = [$s, $fullName, $email, $date . ' ' . $time];
    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);

    if($stmtInsert === false){
        echo "Error adding reservation for seat $s.";
        exit;
    }
}

echo "OK";