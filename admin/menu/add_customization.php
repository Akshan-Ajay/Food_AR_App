<?php
session_start();
include("C:/xampp/htdocs/food_ar_app/includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin";
$success = "";
$error = "";

/* FETCH MENU ITEMS */
$menuItems = [];
$sql = "SELECT MenuItemID, FoodName, Category FROM MenuItems ORDER BY FoodName";
$stmt = sqlsrv_query($conn, $sql);
if($stmt){
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $menuItems[] = $row;
    }
}

/* HANDLE ADD / UPDATE BASE CATEGORY OR CUSTOMIZATION */
if(isset($_POST['add_ar'])) {
    $menuItemID = intval($_POST['MenuItemID']);
    $category   = trim($_POST['Category']);
    $type       = trim($_POST['Type']); 
    $name       = trim($_POST['Name']);
    $price      = floatval($_POST['Price']);

    $glbPath = $usdzPath = NULL;
    // Note: Adjusted to use absolute paths for move_uploaded_file consistency
    $baseDir = "C:/xampp/htdocs/food_ar_app/uploads/";
    $uploadSubDir = ($type === 'Base') ? "models/" : "customizations/";
    $fullUploadPath = $baseDir . $uploadSubDir;
    
    if(!is_dir($fullUploadPath)) mkdir($fullUploadPath, 0777, true);

    if(!empty($_FILES['ModelGLB']['name'])){
        $filename = time().($type=='Base'? "_base" : "_cus") . ".glb";
        $glbPath = "food_ar_app/uploads/" . $uploadSubDir . $filename;
        move_uploaded_file($_FILES['ModelGLB']['tmp_name'], $baseDir . $uploadSubDir . $filename);
    }

    if(!empty($_FILES['ModelUSDZ']['name'])){
        $filename = time().($type=='Base'? "_base" : "_cus") . ".usdz";
        $usdzPath = "food_ar_app/uploads/" . $uploadSubDir . $filename;
        move_uploaded_file($_FILES['ModelUSDZ']['tmp_name'], $baseDir . $uploadSubDir . $filename);
    }

    if($type === 'Base'){
        $checkSql = "SELECT COUNT(*) AS cnt FROM CategoryARModels WHERE Category=?";
        $checkStmt = sqlsrv_query($conn, $checkSql, [$category]);
        $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

        if($row['cnt'] > 0){
            $sql = "UPDATE CategoryARModels SET ModelGLB=?, ModelUSDZ=?, BasePrice=?, UpdatedAt=GETDATE() WHERE Category=?";
            $params = [$glbPath, $usdzPath, $price, $category];
        } else {
            $sql = "INSERT INTO CategoryARModels (Category, BasePrice, ModelGLB, ModelUSDZ, CreatedAt, UpdatedAt) 
                    VALUES (?, ?, ?, ?, GETDATE(), GETDATE())";
            $params = [$category, $price, $glbPath, $usdzPath];
        }
        $stmt = sqlsrv_query($conn, $sql, $params);

    } else {
        if(empty($name)) $error = "❌ Customization name is required!";
        else {
            $checkSql = "SELECT COUNT(*) AS cnt FROM CategoryARModels WHERE Category=?";
            $checkStmt = sqlsrv_query($conn, $checkSql, [$category]);
            $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
            if($row['cnt'] == 0) $error = "❌ Category does not exist in Base Models!";
            else {
                $sql = "INSERT INTO Customizations (MenuItemID, Category, Type, Name, Price, ModelGLB, ModelUSDZ) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $params = [$menuItemID ?: NULL, $category, $type, $name, $price, $glbPath, $usdzPath];
                $stmt = sqlsrv_query($conn, $sql, $params);
            }
        }
    }

    if($stmt && !$error) $success = "✅ AR Model configuration saved!";
    else if(!$error) $error = "❌ Database error: Check constraints.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Manage Customizations — Carrie's Cafe</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap');
        
        :root {
            --primary: #c0392b;
            --accent: #e67e22;
            --bg: #fdfaf8;
            --sidebar: #1e120a;
            --text: #3b2314;
            --card: #ffffff;
            --border: #ece0d1;
            --radius: 16px;
            --shadow: 0 10px 30px rgba(44,26,14,0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar { width: 260px; background: var(--sidebar); position: fixed; height: 100vh; z-index: 1000; }
        .sidebar-logo { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-logo img { width: 140px; border-radius: 10px; }
        .nav-section { color: rgba(255,255,255,0.3); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 2px; padding: 20px 25px 5px; }
        .sidebar nav a { 
            display: flex; align-items: center; gap: 15px; padding: 12px 25px; color: rgba(255,255,255,0.6); 
            text-decoration: none; font-size: 0.9rem; transition: var(--transition); border-left: 4px solid transparent;
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,0.05); color: #fff; border-left-color: var(--accent); }

        /* MAIN */
        .main { margin-left: 260px; flex: 1; width: calc(100% - 260px); }
        .topbar { 
            background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); 
            padding: 15px 40px; display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 900; border-bottom: 1px solid var(--border);
        }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; }

        .content { padding: 40px; }
        .card { background: var(--card); border-radius: var(--radius); padding: 30px; border: 1px solid var(--border); box-shadow: var(--shadow); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        
        /* FORMS */
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.75rem; font-weight: 700; color: #888; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        input, select, textarea { 
            width: 100%; padding: 12px 15px; border: 1px solid var(--border); border-radius: 10px; 
            font-size: 0.95rem; transition: var(--transition); outline: none; background: #fafafa;
        }
        input:focus, select:focus { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 4px rgba(230,126,34,0.05); }

        /* BUTTONS */
        .btn { 
            display: inline-flex; align-items: center; gap: 10px; padding: 12px 25px; 
            border-radius: 10px; font-weight: 600; cursor: pointer; border: none; transition: var(--transition); text-decoration: none; font-size: 0.85rem;
        }
        .btn-primary { background: var(--sidebar); color: #fff; width: 100%; justify-content: center; margin-top: 10px; }
        .btn-primary:hover { background: #000; transform: translateY(-2px); }
        .btn-accent { background: var(--accent); color: #fff; }
        .btn-accent:hover { background: #d35400; }

        .user-pill { 
            background: #fff; border: 1px solid var(--border); border-radius: 50px; padding: 5px 15px 5px 5px;
            display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.85rem; text-decoration: none; color: var(--text);
        }
        .avatar { width: 32px; height: 32px; background: var(--sidebar); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 25px; font-weight: 500; font-size: 0.9rem; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

        @media (max-width: 1024px) { .grid-2 { grid-template-columns: 1fr; } .sidebar { display:none; } .main { margin-left:0; width:100%; } }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="/food_ar_app/admin/Lor.png" alt="Logo">
        <div style="color:var(--accent); font-size:0.7rem; font-weight:700; margin-top:5px;">MANAGEMENT</div>
    </div>
    <nav>
        <div class="nav-section">Dashboard</div>
        <a href="/food_ar_app/admin/dashboard.php"><span class="icon">📊</span> Dashboard</a>
        <div class="nav-section">Foods</div>
        <a href="/food_ar_app/admin/menu/menu_manage.php" class="active"><span class="icon">🍽️</span> Foods</a>
        <div class="nav-section">Payment</div>
        <a href="/food_ar_app/admin/payment/view_payments.php"><span class="icon">💳</span> Payments</a>
        <div class="nav-section">Reservation</div>
        <a href="/food_ar_app/admin/reservation/reservation_manage.php"><span class="icon">📋</span> Bookings</a>
        <div class="nav-section">Orders</div>
        <a href="/food_ar_app/admin/order/view_orders.php"><span class="icon">📦</span> Orders</a>
        <div class="nav-section">FEEDBACK</div>
        <a href="/food_ar_app/admin/feedback/feedback_manage.php"><span class="icon">💬</span> Feedback</a>
    </nav>
</aside>

<div class="main">
    <div class="topbar">
        <span class="page-title">AR Customizations</span>
        <a href="/food_ar_app/admin/index.php" class="user-pill">
            <div class="avatar"><?= strtoupper(substr($admin_name,0,2)) ?></div>
            <span>Logout 🔐</span>
        </a>
    </div>

    <div class="content">
        <?php if($success): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>
        <?php if($error): ?> <div class="alert alert-error"><?= $error ?></div> <?php endif; ?>

        <div class="card" style="padding: 15px 30px; margin-bottom: 25px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
                <h3 style="font-family:'Playfair Display', serif;">🛠️ Configuration</h3>
                <div style="display:flex; gap:10px;">
                    <a href="view_base_models.php" class="btn btn-accent">📦 Base Data</a>
                    <a href="View_Customizations.php" class="btn btn-accent">⚙️ Custom Data</a>
                </div>
            </div>
        </div>

        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <div class="grid-2">
                    <div>
                        <div class="form-group">
                            <label>Linked Food Item (Optional for Base)</label>
                            <select name="MenuItemID" id="menuSelect">
                                <option value="">-- Manual Category Entry --</option>
                                <?php foreach($menuItems as $item){ ?>
                                <option value="<?= $item['MenuItemID'] ?>" data-category="<?= htmlspecialchars($item['Category']) ?>">
                                    <?= htmlspecialchars($item['FoodName']) ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Category Identifier</label>
                            <input type="text" name="Category" id="categoryField" placeholder="e.g. Burger, Pizza" required>
                        </div>

                        <div class="form-group">
                            <label>AR Logic Type</label>
                            <select name="Type" required>
                                <option value="Base">Base Model (Foundational Category)</option>
                                <option value="Ingredient">Selectable Ingredient</option>
                                <option value="Topping">Add-on Topping</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Price Offset (LKR)</label>
                            <input type="number" step="0.01" name="Price" placeholder="0.00">
                        </div>
                    </div>

                    <div>
                        <div class="form-group">
                            <label>Customization Name</label>
                            <input type="text" name="Name" placeholder="e.g. Extra Cheese (Required for Toppings)">
                        </div>

                        <div class="form-group" style="background: #fafafa; padding: 20px; border-radius: 12px; border: 1px solid var(--border);">
                            <label style="margin-bottom: 15px; color: var(--sidebar);">Upload 3D Assets</label>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="font-size: 0.65rem;">Android Asset (.glb)</label>
                                <input type="file" name="ModelGLB">
                            </div>

                            <div>
                                <label style="font-size: 0.65rem;">iOS Asset (.usdz)</label>
                                <input type="file" name="ModelUSDZ">
                            </div>
                        </div>

                        <button type="submit" name="add_ar" class="btn btn-primary">
                            💾 Save AR Configuration
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-fill category when a food item is selected
document.getElementById("menuSelect").addEventListener("change", function(){
    var cat = this.options[this.selectedIndex].getAttribute("data-category");
    document.getElementById("categoryField").value = cat ? cat : "";
});
</script>

</body>
</html>