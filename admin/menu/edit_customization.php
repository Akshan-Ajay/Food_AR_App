<?php
session_start();
// Adjust path if necessary for your local environment
include("C:/xampp/htdocs/food_ar_app/includes/db.php");

/* LOGIN PROTECTION (Admin Only) */
if (!isset($_SESSION['admin_id'])) {
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin";
$success = "";
$error = "";

/* GET CUSTOMIZATION ID */
if(!isset($_GET['id'])){
    header("Location: /food_ar_app/admin/menu/View_Customizations.php");
    exit();
}

$customID = intval($_GET['id']);

/* FETCH MENU ITEMS (For the dropdown) */
$menuItems = [];
$sql = "SELECT MenuItemID, FoodName, Category FROM MenuItems ORDER BY FoodName";
$stmt = sqlsrv_query($conn, $sql);
if($stmt){
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $menuItems[] = $row;
    }
}

/* FETCH CUSTOMIZATION DETAILS */
$sql = "SELECT * FROM Customizations WHERE CustomizationID=?";
$stmt = sqlsrv_query($conn, $sql, [$customID]);
$custom = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$custom){
    $error = "❌ Customization not found!";
}

/* HANDLE UPDATE LOGIC */
if(isset($_POST['update_ar']) && !$error){
    $menuItemID = intval($_POST['MenuItemID']);
    $category   = trim($_POST['Category']);
    $type       = trim($_POST['Type']); 
    $name       = trim($_POST['Name']);
    $price      = floatval($_POST['Price']);

    $glbPath = $custom['ModelGLB'];
    $usdzPath = $custom['ModelUSDZ'];

    // Define upload directories based on type
    $uploadSubDir = ($type === 'Base') ? "food_ar_app/uploads/models/" : "food_ar_app/uploads/customizations/";
    $fullPath = "C:/xampp/htdocs/" . $uploadSubDir;
    
    if(!is_dir($fullPath)) mkdir($fullPath, 0777, true);

    // GLB Handling
    if(!empty($_FILES['ModelGLB']['name'])){
        $filename = time().($type=='Base'? "_base" : "_cus") . ".glb";
        if(move_uploaded_file($_FILES['ModelGLB']['tmp_name'], $fullPath . $filename)){
            $glbPath = "/" . $uploadSubDir . $filename;
        }
    }

    // USDZ Handling
    if(!empty($_FILES['ModelUSDZ']['name'])){
        $filename = time().($type=='Base'? "_base" : "_cus") . ".usdz";
        if(move_uploaded_file($_FILES['ModelUSDZ']['tmp_name'], $fullPath . $filename)){
            $usdzPath = "/" . $uploadSubDir . $filename;
        }
    }

    $sql_upd = "UPDATE Customizations SET MenuItemID=?, Category=?, Type=?, Name=?, Price=?, ModelGLB=?, ModelUSDZ=? WHERE CustomizationID=?";
    $params = [$menuItemID ?: NULL, $category, $type, $name, $price, $glbPath, $usdzPath, $customID];
    $stmt_upd = sqlsrv_query($conn, $sql_upd, $params);

    if($stmt_upd) {
        $success = "✅ Asset details updated successfully!";
        // Refresh display variables
        $custom['MenuItemID'] = $menuItemID;
        $custom['Category'] = $category;
        $custom['Type'] = $type;
        $custom['Name'] = $name;
        $custom['Price'] = $price;
        $custom['ModelGLB'] = $glbPath;
        $custom['ModelUSDZ'] = $usdzPath;
    } else {
        $error = "❌ Database update failed!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Edit AR Asset — Carrie's Cafe</title>
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
        .sidebar-logo img { width: 140px; border-radius: 10px; margin-bottom: 10px; }
        .nav-section { color: rgba(255,255,255,0.3); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 2px; padding: 20px 25px 5px; }
        .sidebar nav a { 
            display: flex; align-items: center; gap: 15px; padding: 12px 25px; color: rgba(255,255,255,0.6); 
            text-decoration: none; font-size: 0.9rem; transition: var(--transition); border-left: 4px solid transparent;
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,0.05); color: #fff; border-left-color: var(--accent); }

        /* MAIN AREA */
        .main { margin-left: 260px; flex: 1; display: flex; flex-direction: column; width: calc(100% - 260px); }
        .topbar { 
            background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); 
            padding: 15px 40px; display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 900; border-bottom: 1px solid var(--border);
        }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; }

        .user-pill { 
            background: #fff; border: 1px solid var(--border); border-radius: 50px; padding: 5px 15px 5px 5px;
            display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.85rem; text-decoration: none; color: var(--text);
        }
        .avatar { width: 32px; height: 32px; background: var(--sidebar); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        /* CONTENT */
        .content { padding: 40px; }
        .card { background: var(--card); border-radius: var(--radius); padding: 40px; border: 1px solid var(--border); box-shadow: var(--shadow); max-width: 1100px; margin: 0 auto; }
        
        .grid-2 { display: grid; grid-template-columns: 1.2fr 1fr; gap: 40px; }

        /* FORM ELEMENTS */
        .form-group { margin-bottom: 24px; }
        label { display: block; font-size: 0.7rem; font-weight: 700; color: #888; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; }
        input, select { 
            width: 100%; padding: 14px 18px; border: 1px solid var(--border); border-radius: 12px; 
            font-size: 0.95rem; transition: var(--transition); outline: none; background: #fafafa;
        }
        input:focus, select:focus { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 4px rgba(230,126,34,0.05); }

        /* ASSET BOX */
        .asset-preview-card {
            background: #fdfdfd; border: 1px dashed var(--border); padding: 25px; border-radius: 14px; margin-bottom: 25px;
        }
        .file-pill { 
            display: inline-block; background: #fff4eb; color: var(--accent); padding: 6px 12px; 
            border-radius: 6px; font-size: 0.8rem; font-weight: 600; margin-top: 8px; border: 1px solid #ffe3cd;
            word-break: break-all;
        }

        /* BUTTONS */
        .btn { 
            display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 14px 30px; 
            border-radius: 12px; font-weight: 600; cursor: pointer; border: none; transition: var(--transition); text-decoration: none; font-size: 0.95rem;
        }
        .btn-primary { background: var(--sidebar); color: #fff; }
        .btn-primary:hover { background: #000; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { background: #fafafa; }

        .alert { padding: 18px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; font-size: 0.9rem; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

        @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } .sidebar { display:none; } .main { margin-left:0; width:100%; } }
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
        <span class="page-title">Modify AR Configuration</span>
        <a href="/food_ar_app/admin/index.php" class="user-pill">
        <div class="avatar"><?= strtoupper(substr($admin_name, 0, 2)) ?></div>
        <span>Logout 🔐</span>
</a>
    </div>

    <div class="content">
        <?php if($success): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>
        <?php if($error): ?> <div class="alert alert-error"><?= $error ?></div> <?php endif; ?>

        <div class="card">
            <div style="margin-bottom: 35px; border-bottom: 2px solid #fdf6f0; padding-bottom: 20px;">
                <h2 style="font-family:'Playfair Display', serif;">✏️ Update Asset: <span style="color:var(--accent)"><?= htmlspecialchars($custom['Name']) ?></span></h2>
                <p style="color: #999; font-size: 0.85rem; margin-top: 5px;">Manage 3D models and pricing for your AR menu experience.</p>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="grid-2">
                    <div>
                        <div class="form-group">
                            <label>Linked Food Item</label>
                            <select name="MenuItemID" id="menuSelect">
                                <option value="">-- No Specific Food (Category Base) --</option>
                                <?php foreach($menuItems as $item){ ?>
                                <option value="<?= $item['MenuItemID'] ?>" data-category="<?= htmlspecialchars($item['Category']) ?>" 
                                    <?= $custom['MenuItemID'] == $item['MenuItemID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($item['FoodName']) ?> (<?= htmlspecialchars($item['Category']) ?>)
                                </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Category Tag</label>
                            <input type="text" name="Category" id="categoryField" required value="<?= htmlspecialchars($custom['Category']) ?>" placeholder="e.g. Burger, Pizza...">
                        </div>

                        <div class="form-group">
                            <label>Component Type</label>
                            <select name="Type" required>
                                <option value="Base" <?= $custom['Type']=='Base'?'selected':'' ?>>Base AR (The Plate/Main Model)</option>
                                <option value="Ingredient" <?= $custom['Type']=='Ingredient'?'selected':'' ?>>Ingredient (Inside Add-on)</option>
                                <option value="Topping" <?= $custom['Type']=='Topping'?'selected':'' ?>>Topping (Visual Decoration)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Display Name</label>
                            <input type="text" name="Name" value="<?= htmlspecialchars($custom['Name']) ?>" placeholder="e.g. Extra Cheese">
                        </div>

                        <div class="form-group">
                            <label>Additional Surcharge (LKR)</label>
                            <input type="number" step="0.01" name="Price" value="<?= $custom['Price'] ?>" style="font-weight:700; color:var(--primary);">
                        </div>
                    </div>

                    <div>
                        <div class="asset-preview-card">
                            <h4 style="font-size: 0.8rem; text-transform:uppercase; margin-bottom: 20px; color: var(--sidebar);">📦 3D Asset Management</h4>
                            
                            <div class="form-group">
                                <label style="font-size: 0.65rem;">Android Model (.glb)</label>
                                <div class="file-pill"><?= $custom['ModelGLB'] ? basename($custom['ModelGLB']) : 'No model linked' ?></div>
                                <input type="file" name="ModelGLB" accept=".glb" style="margin-top: 15px; background: white;">
                            </div>

                            <div class="form-group" style="margin-top: 30px;">
                                <label style="font-size: 0.65rem;">iOS / Safari Model (.usdz)</label>
                                <div class="file-pill"><?= $custom['ModelUSDZ'] ? basename($custom['ModelUSDZ']) : 'No model linked' ?></div>
                                <input type="file" name="ModelUSDZ" accept=".usdz" style="margin-top: 15px; background: white;">
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 30px;">
                            <button type="submit" name="update_ar" class="btn btn-primary">💾 Save Asset Changes</button>
                            <a href="View_Customizations.php" class="btn btn-outline">Discard and Return</a>
                        </div>
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