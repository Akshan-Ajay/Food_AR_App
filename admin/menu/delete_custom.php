<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: /food_ar_app/admin/index.php");
    exit();
}
include('C:/xampp/htdocs/food_ar_app/includes/db.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id){
    $sql = "DELETE FROM Customizations WHERE CustomizationID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);
}
header("Location: /food_ar_app/admin/menu/customization.php");
exit();