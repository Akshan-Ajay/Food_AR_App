<?php
session_start();
include('../includes/db.php');

if(!isset($_SESSION['customer_id'])){
    header("Location:./../user/index.php");
    exit();
}

$user_id = $_SESSION['customer_id'];
$menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 0;

// ---------- GET MENU ITEM ----------
$sql = "SELECT * FROM MenuItems WHERE MenuItemID=?";
$stmt = sqlsrv_query($conn, $sql, [$menu_id]);
if($stmt === false || !sqlsrv_has_rows($stmt)){
    die("Menu item not found.");
}
$menu_item = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// ---------- FETCH CUSTOMIZATIONS ----------
$customizations = ['Ingredient'=>[], 'Topping'=>[]];
$sql_cust = "SELECT Type, Name FROM Customizations WHERE MenuItemID=?";
$stmt_cust = sqlsrv_query($conn, $sql_cust, [$menu_id]);
while($row = sqlsrv_fetch_array($stmt_cust, SQLSRV_FETCH_ASSOC)){
    $customizations[$row['Type']][] = $row['Name'];
}

// ---------- HANDLE ADD TO CART ----------
if(isset($_POST['customize_submit'])){
    $quantity = intval($_POST['quantity']);
    $customization = [
        'ingredients' => $_POST['ingredients'] ?? [],
        'toppings' => $_POST['toppings'] ?? [],
        'notes' => $_POST['notes'] ?? ''
    ];
    $customization_json = json_encode($customization);

    if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$cart_key = $menu_id . '_' . md5($customization_json);

if(isset($_SESSION['cart'][$cart_key])){
    $_SESSION['cart'][$cart_key]['quantity'] += $quantity;
} else {
    $_SESSION['cart'][$cart_key] = [
        'name' => $menu_item['FoodName'],
        'price' => $menu_item['Price'],
        'quantity' => $quantity,
        'customization' => $customization_json
    ];
}

    header("Location:./../user/order/cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Customize <?= htmlspecialchars($menu_item['FoodName']) ?></title>
<style>
/* your styles here */
</style>
</head>
<body>
<div class="container">
<h2>Customize: <?= htmlspecialchars($menu_item['FoodName']) ?></h2>
<img src="../<?= htmlspecialchars($menu_item['ImagePath'] ?: 'images/default_food.png') ?>" alt="<?= htmlspecialchars($menu_item['FoodName']) ?>">

<form method="POST">
<div class="customization-group">
<label>Quantity:</label>
<input type="number" name="quantity" value="1" min="1">
</div>

<?php if(!empty($customizations['Ingredient'])): ?>
<div class="customization-group">
<label>Ingredients:</label>
<?php foreach($customizations['Ingredient'] as $ing): ?>
<label><input type="checkbox" name="ingredients[]" value="<?= htmlspecialchars($ing) ?>"> <?= htmlspecialchars($ing) ?></label>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if(!empty($customizations['Topping'])): ?>
<div class="customization-group">
<label>Toppings:</label>
<?php foreach($customizations['Topping'] as $top): ?>
<label><input type="checkbox" name="toppings[]" value="<?= htmlspecialchars($top) ?>"> <?= htmlspecialchars($top) ?></label>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="customization-group">
<label>Notes:</label>
<textarea name="notes"></textarea>
</div>

<button type="submit" name="customize_submit">Add to Cart</button>
</form>
</div>
</body>
</html>
