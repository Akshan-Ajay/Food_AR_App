<?php
session_start();
include("C:/xampp/htdocs/food_ar_app/includes/db.php");

/* LOGIN PROTECTION */
if (!isset($_SESSION['admin_id'])) {
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin";

/* FETCH MENU ITEMS */
$menuItems = [];
$sqlMenu = "SELECT MenuItemID, FoodName, Category, Price, ImagePath FROM MenuItems ORDER BY CreatedAt DESC";
$stmtMenu = sqlsrv_query($conn, $sqlMenu);
while($row = sqlsrv_fetch_array($stmtMenu, SQLSRV_FETCH_ASSOC)){
    $menuItems[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Menu Manage — Carrie's Cafe</title>
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
        
        .card { background: var(--card); border-radius: var(--radius); padding: 30px; border: 1px solid var(--border); box-shadow: var(--shadow); }
        
        /* ACTION BUTTONS HEADER */
        .card-header-actions {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px; border-bottom: 1px solid #f5eee6; padding-bottom: 20px;
        }

        .btn-group { display: flex; gap: 10px; }

        .btn { 
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; 
            border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; 
            text-decoration: none; transition: var(--transition); border: none;
        }
        .btn-primary { background: var(--sidebar); color: #fff; }
        .btn-primary:hover { background: #332216; transform: translateY(-2px); }
        .btn-outline { background: #fff; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { border-color: var(--accent); color: var(--accent); }
        .btn-danger { background: #fff1f0; color: var(--primary); }
        .btn-danger:hover { background: var(--primary); color: #fff; }

        /* TABLE */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; padding: 15px; font-size: 0.75rem; color: #999; text-transform: uppercase; border-bottom: 2px solid var(--bg); }
        tbody td { padding: 15px; border-bottom: 1px solid #fdfaf8; font-size: 0.95rem; vertical-align: middle; }
        tbody tr:hover { background: #fdfaf8; }

        .menu-img { width: 55px; height: 55px; border-radius: 12px; object-fit: cover; border: 2px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .price-tag { font-weight: 700; color: var(--sidebar); }
        .category-badge { background: #f5eee6; color: #8d6e63; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 500; }

        @media (max-width: 1024px) { .sidebar { display:none; } .main { margin-left:0; width:100%; } }
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
        <span class="page-title">Menu Management</span>
        <a href="/food_ar_app/admin/index.php" class="user-pill">
            <div class="avatar"><?php echo strtoupper(substr($admin_name,0,2)); ?></div>
            <span>Logout</span>
        </a>
    </div>

    <div class="content">
        <div class="card">
            <div class="card-header-actions">
                <h3 style="font-family:'Playfair Display', serif;">Catalog (<?php echo count($menuItems); ?> Items)</h3>
                <div class="btn-group">
                    <a href="add_food.php" class="btn btn-primary">➕ Add Item</a>
                    <a href="View_customizations.php" class="btn btn-outline">📦 Custom Data</a>
                    <a href="view_base_models.php" class="btn btn-outline">📦 Base Data</a>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Item Info</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($menuItems as $m){ ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <?php if($m['ImagePath']){ ?>
                                        <img src="/food_ar_app/<?php echo $m['ImagePath']; ?>" class="menu-img"/>
                                    <?php } else { ?>
                                        <div class="menu-img" style="background:#eee; display:flex; align-items:center; justify-content:center; font-size:0.6rem; color:#999;">No Image</div>
                                    <?php } ?>
                                    <div>
                                        <div style="font-weight:600; color:var(--sidebar);"><?php echo $m['FoodName']; ?></div>
                                        <div style="font-size:0.75rem; color:#999;">ID: #<?php echo $m['MenuItemID']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="category-badge"><?php echo $m['Category']; ?></span></td>
                            <td class="price-tag">LKR <?php echo number_format($m['Price'], 2); ?></td>
                            <td>
                                <div style="display:flex; gap:8px; justify-content: flex-end;">
                                    <a href="edit_food.php?id=<?php echo $m['MenuItemID']; ?>" class="btn btn-outline" style="padding: 6px 12px;">✏️ Edit</a>
                                    <a href="delete_food.php?id=<?php echo $m['MenuItemID']; ?>" class="btn btn-danger" style="padding: 6px 12px;" onclick="return confirm('Delete this item?')">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>