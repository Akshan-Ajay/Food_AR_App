<?php
session_start();
// Adjust path for your environment
include('../../includes/db.php');

/* ADMIN PROTECTION (Matches Dashboard Logic) */
if (!isset($_SESSION['admin_id'])) {
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin";
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    die("Invalid order ID.");
}

/* FETCH ORDER (Admin View) */
$sql_order = "SELECT * FROM Orders WHERE OrderID = ?";
$stmt_order = sqlsrv_query($conn, $sql_order, [$order_id]);
if ($stmt_order === false) {
    die(print_r(sqlsrv_errors(), true));
}

$order = sqlsrv_fetch_array($stmt_order, SQLSRV_FETCH_ASSOC);
if (!$order) {
    die("Order not found.");
}

/* FETCH ITEMS */
$sql_items = "
    SELECT 
        oi.OrderItemID, oi.MenuItemID, oi.Category, oi.Quantity, 
        oi.Customization, oi.Price, mi.FoodName
    FROM OrderItems oi
    LEFT JOIN MenuItems mi ON oi.MenuItemID = mi.MenuItemID
    WHERE oi.OrderID = ?";
$stmt_items = sqlsrv_query($conn, $sql_items, [$order_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order['OrderID'] ?> — Carrie's Cafe</title>
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

        /* CONTENT */
        .content { padding: 40px; }
        
        /* INFO GRID */
        .order-meta-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 35px; }
        .info-card { background: var(--card); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); border: 1px solid var(--border); }
        .info-card label { display: block; font-size: 0.65rem; text-transform: uppercase; color: #999; font-weight: 700; letter-spacing: 1px; margin-bottom: 5px; }
        .info-card .val { font-family: 'Playfair Display', serif; font-size: 1.25rem; font-weight: 700; }

        /* STATUS BADGES */
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .badge.pending { background: #fff8e1; color: #f57f17; }
        .badge.completed { background: #e8f5e9; color: #2e7d32; }
        .badge.cancelled { background: #fdeded; color: #c0392b; }

        /* TABLE */
        .card { background: var(--card); border-radius: var(--radius); padding: 30px; border: 1px solid var(--border); box-shadow: var(--shadow); }
        .card-header { margin-bottom: 25px; border-bottom: 1px solid #f5eee6; padding-bottom: 15px; }
        .card-header h3 { font-family: 'Playfair Display', serif; font-size: 1.4rem; }

        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; padding: 15px; font-size: 0.7rem; color: #999; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--border); }
        tbody td { padding: 20px 15px; border-bottom: 1px solid #fcf9f6; font-size: 0.9rem; }
        
        .item-name { font-weight: 600; display: block; font-size: 1rem; }
        .item-sub { font-size: 0.75rem; color: #a38b7a; }
        .custom-note { font-style: italic; color: var(--accent); font-size: 0.85rem; }

        /* UTILS */
        .btn-back { text-decoration: none; color: var(--text); font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: var(--transition); }
        .btn-back:hover { color: var(--accent); transform: translateX(-5px); }

        @media (max-width: 1024px) { .sidebar { display:none; } .main { margin-left:0; width:100%; } .order-meta-grid { grid-template-columns: 1fr 1fr; } }
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
        <a href="/food_ar_app/admin/menu/menu_manage.php"><span class="icon">🍽️</span> Foods</a>
        <div class="nav-section">Payment</div>
        <a href="/food_ar_app/admin/payment/view_payments.php"><span class="icon">💳</span> Payments</a>
        <div class="nav-section">Reservation</div>
        <a href="/food_ar_app/admin/reservation/reservation_manage.php"><span class="icon">📋</span> Bookings</a>
        <div class="nav-section">Orders</div>
        <a href="/food_ar_app/admin/order/view_orders.php" class="active"><span class="icon">📦</span> Orders</a>
        <div class="nav-section">FEEDBACK</div>
        <a href="/food_ar_app/admin/feedback/feedback_manage.php"><span class="icon">💬</span> Feedback</a>
    </nav>
</aside>

<div class="main">
    <div class="topbar">
        <a href="/food_ar_app/admin/order/view_orders.php" class="btn-back">← Back to Orders</a>
        <span class="page-title">Order #<?= $order['OrderID'] ?></span>
        <div style="width: 100px;"></div> </div>

    <div class="content">
        <div class="order-meta-grid">
            <div class="info-card">
                <label>Reference</label>
                <div class="val">#<?= $order['OrderID'] ?></div>
            </div>
            <div class="info-card">
                <label>Status</label>
                <div class="val"><span class="badge <?= strtolower($order['Status']) ?>"><?= $order['Status'] ?></span></div>
            </div>
            <div class="info-card">
                <label>Placed On</label>
                <div class="val" style="font-size: 1rem;"><?= $order['OrderDate']->format('M d, Y • H:i') ?></div>
            </div>
            <div class="info-card">
                <label>Total Value</label>
                <div class="val" style="color: var(--primary);">LKR <?= number_format($order['TotalAmount'], 0) ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Bill of Items</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th>Category</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Customizations</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if($stmt_items && sqlsrv_has_rows($stmt_items)): 
                        while($item = sqlsrv_fetch_array($stmt_items, SQLSRV_FETCH_ASSOC)):
                            $item_name = $item['FoodName'] ?? ($item['Category'] ?? "Item #".$item['MenuItemID']);
                            $item_type = $item['MenuItemID'] ? 'Kitchen Order' : 'AR Visualization Unit';
                            
                            $cust_text = 'None';
                            if(!empty($item['Customization'])){
                                $cust = is_string($item['Customization']) ? json_decode($item['Customization'], true) : $item['Customization'];
                                if(is_array($cust)){
                                    $flat = [];
                                    array_walk_recursive($cust, function($a) use (&$flat) { $flat[] = $a; });
                                    $cust_text = implode(", ", $flat);
                                } else {
                                    $cust_text = $item['Customization'];
                                }
                            }
                    ?>
                    <tr>
                        <td>
                            <span class="item-name"><?= htmlspecialchars($item_name) ?></span>
                            <span class="item-sub"><?= $item_type ?></span>
                        </td>
                        <td><span style="font-weight:500;"><?= htmlspecialchars($item['Category'] ?? 'General') ?></span></td>
                        <td><strong>× <?= intval($item['Quantity']) ?></strong></td>
                        <td>LKR <?= number_format($item['Price'], 0) ?></td>
                        <td class="custom-note"><?= htmlspecialchars($cust_text) ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 50px; color: #ccc;">No items found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 30px; text-align: right; color: #999; font-size: 0.8rem;">
            System Reference: OrderID_<?= $order['OrderID'] ?>_CAFE
        </div>
    </div>
</div>

</body>
</html>
