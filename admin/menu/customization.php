<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

include('C:/xampp/htdocs/food_ar_app/includes/db.php');
$admin_name = $_SESSION['admin_name'];

// ---------- Add Customization ----------
if(isset($_POST['add_custom'])){
    $menu_id = !empty($_POST['menu_item_id']) ? (int)$_POST['menu_item_id'] : null;
    $category = !empty($_POST['custom_category']) ? $_POST['custom_category'] : null;
    $type = $_POST['custom_type'];
    $name = $_POST['custom_name'];
    $price = $_POST['custom_price'];

    $sql = "INSERT INTO Customizations (MenuItemID, Category, Type, Name, Price) VALUES (?, ?, ?, ?, ?)";
    $stmt = sqlsrv_query($conn, $sql, [$menu_id, $category, $type, $name, $price]);

    if($stmt === false) die(print_r(sqlsrv_errors(), true));
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// ---------- Fetch Data ----------
// Menu Items
$sqlMenu = "SELECT MenuItemID, FoodName FROM MenuItems ORDER BY FoodName";
$stmtMenu = sqlsrv_query($conn, $sqlMenu);
$menuItems = [];
while($row = sqlsrv_fetch_array($stmtMenu, SQLSRV_FETCH_ASSOC)){
    $menuItems[] = $row;
}

// Customizations
$sqlCustoms = "SELECT c.CustomizationID, c.MenuItemID, c.Category, c.Type, c.Name, c.Price, m.FoodName
               FROM Customizations c
               LEFT JOIN MenuItems m ON c.MenuItemID = m.MenuItemID
               ORDER BY c.CustomizationID DESC";
$stmtCustoms = sqlsrv_query($conn, $sqlCustoms);
$customs = [];
while($row = sqlsrv_fetch_array($stmtCustoms, SQLSRV_FETCH_ASSOC)){
    $customs[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Customizations | Cafe AR</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;}
body{background: linear-gradient(135deg,#0f2027,#203a43,#2c5364); color:white; min-height:100vh; padding:40px;}
input, select{padding:6px 10px; border-radius:6px; border:none; margin-right:10px;}
.btn{padding:6px 10px; border-radius:6px; text-decoration:none; font-size:12px; margin-right:5px; display:inline-block;}
.btn-submit{background:#3498db;color:white;} .btn-submit:hover{background:#2980b9;}
.btn-edit{background:#2ecc71;color:white;} .btn-edit:hover{background:#27ae60;}
.btn-delete{background:#e74c3c;color:white;} .btn-delete:hover{background:#c0392b;}
.table-wrapper{background:rgba(255,255,255,0.07);backdrop-filter:blur(20px);border-radius:20px;padding:20px;margin-top:20px;}
table{width:100%; border-collapse:collapse;}
th{background:rgba(255,255,255,0.1);padding:10px;text-align:left;}
td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.08);}
.section-title{font-size:20px;margin-bottom:15px;font-weight:600;}
</style>
</head>
<body>

<h2>🍽️ Customizations</h2>
<p>Welcome, <?= htmlspecialchars($admin_name); ?></p>

<!-- Add Customization -->
<form method="post">
    <select name="menu_item_id">
        <option value="">-- Select Menu Item (optional) --</option>
        <?php foreach($menuItems as $m): ?>
        <option value="<?= $m['MenuItemID'] ?>"><?= htmlspecialchars($m['FoodName']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" name="custom_category" placeholder="Category (if not menu item)">
    <select name="custom_type" required>
        <option value="Ingredient">Ingredient</option>
        <option value="Topping">Topping</option>
    </select>
    <input type="text" name="custom_name" placeholder="Name" required>
    <input type="number" step="0.01" name="custom_price" placeholder="Price" value="0.00" required>
    <button type="submit" name="add_custom" class="btn btn-submit">Add Customization</button>
</form>

<!-- Customizations Table -->
<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Menu Item</th>
                <th>Category</th>
                <th>Type</th>
                <th>Name</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $sn=1; foreach($customs as $c): ?>
            <tr>
                <td><?= $sn++; ?></td>
                <td><?= htmlspecialchars($c['FoodName'] ?? '-') ?></td>
                <td><?= htmlspecialchars($c['Category'] ?? '-') ?></td>
                <td><?= htmlspecialchars($c['Type']) ?></td>
                <td><?= htmlspecialchars($c['Name']) ?></td>
                <td>LKR <?= number_format($c['Price'],2) ?></td>
                <td>
                    <a href="food_ar_app/admin/menu/edit_custom.php?id=<?= $c['CustomizationID'] ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                    <a href="food_ar_app/admin/menu/delete_custom.php?id=<?= $c['CustomizationID'] ?>" class="btn btn-delete" onclick="return confirm('Delete this customization?');"><i class="fas fa-trash"></i> Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>