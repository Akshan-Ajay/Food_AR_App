<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

include('C:/xampp/htdocs/food_ar_app/includes/db.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$id) exit("Invalid ID");

// Fetch customization
$sql = "SELECT * FROM Customizations WHERE CustomizationID = ?";
$stmt = sqlsrv_query($conn, $sql, [$id]);
$custom = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$custom) exit("Customization not found");

// Menu Items for dropdown
$sqlMenu = "SELECT MenuItemID, FoodName FROM MenuItems ORDER BY FoodName";
$stmtMenu = sqlsrv_query($conn, $sqlMenu);
$menuItems = [];
while($row = sqlsrv_fetch_array($stmtMenu, SQLSRV_FETCH_ASSOC)) $menuItems[] = $row;

// Handle Update
if(isset($_POST['update_custom'])){
    $menu_id = !empty($_POST['menu_item_id']) ? (int)$_POST['menu_item_id'] : null;
    $category = !empty($_POST['custom_category']) ? $_POST['custom_category'] : null;
    $type = $_POST['custom_type'];
    $name = $_POST['custom_name'];
    $price = $_POST['custom_price'];

    $sql = "UPDATE Customizations SET MenuItemID=?, Category=?, Type=?, Name=?, Price=? WHERE CustomizationID=?";
    $stmt = sqlsrv_query($conn, $sql, [$menu_id, $category, $type, $name, $price, $id]);

    if($stmt === false) die(print_r(sqlsrv_errors(), true));
    header("Location: customization.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Customization</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Inter',sans-serif;background:#203a43;color:white;padding:40px;}
input, select, button{padding:6px 10px;border-radius:6px;border:none;margin-bottom:10px;}
button{background:#2ecc71;color:white;cursor:pointer;}
button:hover{background:#27ae60;}
</style>
</head>
<body>
<h2>Edit Customization</h2>
<form method="post">
    <select name="menu_item_id">
        <option value="">-- Select Menu Item (optional) --</option>
        <?php foreach($menuItems as $m): ?>
        <option value="<?= $m['MenuItemID'] ?>" <?= $m['MenuItemID']==$custom['MenuItemID']?'selected':'' ?>><?= htmlspecialchars($m['FoodName']) ?></option>
        <?php endforeach; ?>
    </select><br>
    <input type="text" name="custom_category" placeholder="Category" value="<?= htmlspecialchars($custom['Category'] ?? '') ?>"><br>
    <select name="custom_type" required>
        <option value="Ingredient" <?= $custom['Type']=='Ingredient'?'selected':'' ?>>Ingredient</option>
        <option value="Topping" <?= $custom['Type']=='Topping'?'selected':'' ?>>Topping</option>
    </select><br>
    <input type="text" name="custom_name" placeholder="Name" value="<?= htmlspecialchars($custom['Name']) ?>" required><br>
    <input type="number" step="0.01" name="custom_price" placeholder="Price" value="<?= $custom['Price'] ?>" required><br>
    <button type="submit" name="update_custom">Update</button>
</form>
<a href="/food_ar_app/admin/menu/view_customizations.php" style="color:#ff6b6b;">Back to Customizations</a>
</body>
</html>