<?php
require_once 'C:/xampp/htdocs/food_ar_app/includes/db.php';

if(isset($_GET['category'])){
    $cat = $_GET['category'];
    $sql = "SELECT MenuItemID, FoodName FROM MenuItems WHERE Category=? ORDER BY FoodName ASC";
    $stmt = sqlsrv_query($conn, $sql, [$cat]);
    echo '<option value="">-- Select Food --</option>';
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        echo "<option value='".$row['MenuItemID']."'>".htmlspecialchars($row['FoodName'])."</option>";
    }
}

if(isset($_GET['food_id'])){
    $food_id = intval($_GET['food_id']);
    // Show existing ingredients and toppings
    $sql = "SELECT Type, Name FROM Customizations WHERE MenuItemID=? ORDER BY Type, Name";
    $stmt = sqlsrv_query($conn, $sql, [$food_id]);
    $output = "<h4>Existing Ingredients / Toppings:</h4><ul>";
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $output .= "<li>".htmlspecialchars($row['Type']).": ".htmlspecialchars($row['Name'])."</li>";
    }
    $output .= "</ul>";
    echo $output;
}
