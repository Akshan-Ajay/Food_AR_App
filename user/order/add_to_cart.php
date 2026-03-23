<?php
session_start();
include('../../includes/db.php');

if(!isset($_SESSION['customer_id'])){
     header("Location: /food_ar_app/user/index.php");
    exit();
}

// ---------- CASE 1: From Menu grid (POST with menu_id) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu_id'])) {
    $menu_id = intval($_POST['menu_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    // Fetch menu item details
    $sql = "SELECT FoodName, Price FROM MenuItems WHERE MenuItemID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$menu_id]);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));
    $item = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$item) die("Menu item not found.");

    $cart_key = 'menu_' . $menu_id;

    if(isset($_SESSION['cart'][$cart_key])){
        $_SESSION['cart'][$cart_key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$cart_key] = [
            'name' => $item['FoodName'],
            'price' => floatval($item['Price']),
            'quantity' => $quantity,
            'customization' => []
        ];
    }

    // Redirect back to menu with a success flag
    header("Location:../menu/menu.php?added=1");
    exit();
}

// ---------- CASE 2: From AR / customization page (POST with category) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category'])) {
    $category = trim($_POST['category']);
    $customization_json = $_POST['customization_json'] ?? '[]';
    $customization = json_decode($customization_json, true) ?? [];
    $total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;

    if (!$category) die("No category selected.");

    $cart_key = $category . '_' . md5(json_encode($customization));

    if(isset($_SESSION['cart'][$cart_key])){
        $_SESSION['cart'][$cart_key]['quantity'] += 1;
        $_SESSION['cart'][$cart_key]['price'] = $total_price;
        $_SESSION['cart'][$cart_key]['customization'] = $customization;
    } else {
        $_SESSION['cart'][$cart_key] = [
            'name' => $category,
            'price' => $total_price,
            'quantity' => 1,
            'customization' => $customization
        ];
    }

    header("Location:/food_ar_app/user/order/cart.php");
    exit();
}

// Fallback
die("Invalid request.");
?>
