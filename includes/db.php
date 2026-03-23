<?php
$serverName = "AKSHAN\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "CafeManagementAR",
    "Uid" => "sa",
    "PWD" => "YourStrongPassword",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}
?>
