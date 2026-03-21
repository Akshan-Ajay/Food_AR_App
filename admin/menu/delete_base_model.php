<?php
session_start();
include("C:/xampp/htdocs/food_ar_app/includes/db.php");

if(!isset($_SESSION['admin_id'])){
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

$category = $_GET['category'] ?? '';

if(!$category){
    die("Category not specified.");
}

/* ---------- DELETE CHILD CUSTOMIZATIONS ---------- */
$sqlChild = "DELETE FROM Customizations WHERE Category = ?";
$paramsChild = array(array($category, SQLSRV_PARAM_IN));

$stmtChild = sqlsrv_prepare($conn, $sqlChild, $paramsChild);
if(!$stmtChild){
    die("Error preparing child delete: " . print_r(sqlsrv_errors(), true));
}

if(!sqlsrv_execute($stmtChild)){
    die("Error deleting child records: " . print_r(sqlsrv_errors(), true));
}

/* ---------- DELETE DEPENDENT MENU ITEMS ---------- */
$sqlMenu = "DELETE FROM MenuItems WHERE Category = ?";
$paramsMenu = array(array($category, SQLSRV_PARAM_IN));

$stmtMenu = sqlsrv_prepare($conn, $sqlMenu, $paramsMenu);
if(!$stmtMenu){
    die("Error preparing menu items delete: " . print_r(sqlsrv_errors(), true));
}

if(!sqlsrv_execute($stmtMenu)){
    die("Error deleting menu items: " . print_r(sqlsrv_errors(), true));
}

/* ---------- DELETE CATEGORY ---------- */
$sqlCat = "DELETE FROM CategoryARModels WHERE Category = ?";
$paramsCat = array(array($category, SQLSRV_PARAM_IN));

$stmtCat = sqlsrv_prepare($conn, $sqlCat, $paramsCat);
if(!$stmtCat){
    die("Error preparing category delete: " . print_r(sqlsrv_errors(), true));
}

if(!sqlsrv_execute($stmtCat)){
    die("Error deleting category: " . print_r(sqlsrv_errors(), true));
}

sqlsrv_close($conn);

header("Location: /food_ar_app/admin/menu/view_base_models.php");
exit();
?>