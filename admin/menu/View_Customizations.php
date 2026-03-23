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

/* HANDLE DELETE */
if(isset($_GET['delete_id'])){
    $deleteID = intval($_GET['delete_id']);
    $delSql = "DELETE FROM Customizations WHERE CustomizationID=?";
    $delStmt = sqlsrv_query($conn, $delSql, [$deleteID]);
    if($delStmt){
        $success = "✅ Customization deleted successfully!";
    } else {
        $error = "❌ Failed to delete customization.";
    }
}

/* FETCH CUSTOMIZATIONS WITH MENU ITEM NAME */
$sql = "SELECT c.CustomizationID, c.Name AS CustomName, c.Type, c.Price, c.Category, 
               m.FoodName 
        FROM Customizations c
        LEFT JOIN MenuItems m ON c.MenuItemID = m.MenuItemID
        ORDER BY c.Category, c.Type, c.Name";
$stmt = sqlsrv_query($conn, $sql);
$customizations = [];
if($stmt){
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $customizations[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>View Customizations — Carrie's Cafe</title>
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
        
        /* TABLE STYLING */
        .table-container { width: 100%; overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { 
            background: #fafafa; padding: 15px; font-size: 0.75rem; 
            text-transform: uppercase; letter-spacing: 1px; color: #888;
            border-bottom: 2px solid var(--border);
        }
        td { padding: 18px 15px; border-bottom: 1px solid var(--border); font-size: 0.95rem; vertical-align: middle; }
        tr:hover { background: #fdfaf8; }

        /* BADGES */
        .type-badge {
            padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .badge-topping { background: #fff3e0; color: #e67e22; border: 1px solid #ffe0b2; }
        .badge-ingredient { background: #e3f2fd; color: #1976d2; border: 1px solid #bbdefb; }

        /* BUTTONS */
        .btn { 
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; 
            border-radius: 8px; font-weight: 600; cursor: pointer; border: none; 
            transition: var(--transition); text-decoration: none; font-size: 0.85rem;
        }
        .btn-primary { background: var(--sidebar); color: #fff; }
        .btn-primary:hover { background: #000; transform: translateY(-2px); }
        .btn-danger { background: #fff; border: 1px solid #ffcdd2; color: #c62828; }
        .btn-danger:hover { background: #c62828; color: #fff; }
        .btn-outline { border: 1.5px solid var(--border); color: var(--text); background: #fff; }
        .btn-outline:hover { border-color: var(--accent); color: var(--accent); }

        .user-pill { 
            background: #fff; border: 1px solid var(--border); border-radius: 50px; padding: 5px 15px 5px 5px;
            display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.85rem; text-decoration: none; color: var(--text);
        }
        .avatar { width: 32px; height: 32px; background: var(--sidebar); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 25px; font-weight: 500; font-size: 0.9rem; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

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
        <span class="page-title">Customizations</span>
        <a href="/food_ar_app/admin/index.php" class="user-pill">
            <div class="avatar"><?= strtoupper(substr($admin_name, 0, 2)) ?></div>
            <span>Logout 🔐</span>
        </a>
    </div>

    <div class="content">
        <?php if($success): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>
        <?php if($error): ?> <div class="alert alert-error"><?= $error ?></div> <?php endif; ?>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom: 25px; border-bottom: 1px solid var(--border); padding-bottom: 20px;">
                <h2 style="font-family:'Playfair Display', serif;">⚙️ AR Customizations</h2>
                <div style="display:flex; gap:10px;">
                    <a href="add_customization.php" class="btn btn-primary">➕ Add Customization</a>
                    <a href="view_base_models.php" class="btn btn-outline">📦 Base Data</a>
                </div>
            </div>

            <div class="table-container">
                <?php if(count($customizations) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Linked Food</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($customizations as $c): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--accent);"><?= htmlspecialchars($c['Category']) ?></td>
                            <td style="color: #666; font-size: 0.85rem;"><?= htmlspecialchars($c['FoodName'] ?? 'General') ?></td>
                            <td>
                                <span class="type-badge badge-<?= strtolower($c['Type']) ?>">
                                    <?= htmlspecialchars($c['Type']) ?>
                                </span>
                            </td>
                            <td style="font-weight: 500;"><?= htmlspecialchars($c['CustomName']) ?></td>
                            <td><span style="font-size: 0.75rem; color: #aaa;">LKR</span> <?= number_format($c['Price'], 2) ?></td>
                            <td style="text-align: right;">
                                <div style="display:inline-flex; gap:8px;">
                                    <a href="edit_customization.php?id=<?= $c['CustomizationID'] ?>" class="btn btn-outline" style="padding: 6px 12px;">✏️</a>
                                    <a href="?delete_id=<?= $c['CustomizationID'] ?>" 
                                       class="btn btn-danger" 
                                       style="padding: 6px 12px;"
                                       onclick="return confirm('Delete this customization? This cannot be undone.');">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #888;">
                        <span style="font-size: 3rem;">⚙️</span>
                        <p style="margin-top: 10px;">No customizations found. Start by adding a new one.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>