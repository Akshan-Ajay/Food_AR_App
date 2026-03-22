<?php
session_start();
// Adjust path if necessary for your local environment
include("C:/xampp/htdocs/food_ar_app/includes/db.php");

/* LOGIN PROTECTION */
if (!isset($_SESSION['admin_id'])) {
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin";

/* SAFE COUNT FUNCTION */
function fetchCount($conn, $sql) {
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return 0;
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['total'] ?? 0;
}

/* DASHBOARD COUNTS */
$orderCount = fetchCount($conn, "SELECT COUNT(*) AS total FROM Orders");
$resCount   = fetchCount($conn, "SELECT COUNT(*) AS total FROM Reservations");
$menuCount  = fetchCount($conn, "SELECT COUNT(*) AS total FROM MenuItems");

/* TODAY REVENUE */
$todayRevenue = 0;
$sqlRevenue = "
SELECT ISNULL(SUM(PaymentAmount),0) AS total
FROM Payments
WHERE PaymentStatus='Completed'
AND CAST(CreatedAt AS DATE)=CAST(GETDATE() AS DATE)
";
$stmtRev = sqlsrv_query($conn, $sqlRevenue);
if($stmtRev){
    $row = sqlsrv_fetch_array($stmtRev, SQLSRV_FETCH_ASSOC);
    $todayRevenue = $row['total'];
}

/* RECENT FOODS */
$foods = [];
$sqlFoods = "SELECT TOP 5 MenuItemID, FoodName, Category, Price FROM MenuItems ORDER BY CreatedAt DESC";
$stmtFoods = sqlsrv_query($conn, $sqlFoods);
while($row = sqlsrv_fetch_array($stmtFoods, SQLSRV_FETCH_ASSOC)){
    $foods[] = $row;
}

/* RECENT RESERVATIONS - FIXED QUERY */
$reservations = [];
$sqlRes = "
    SELECT TOP 5 
        ISNULL(r.FullName, u.FullName) AS DisplayName, 
        r.SeatNumber, 
        r.Status 
    FROM Reservations r
    LEFT JOIN Users u ON r.UserID = u.UserID
    ORDER BY r.ReservationDate DESC
";
$stmtRes = sqlsrv_query($conn, $sqlRes);
while($row = sqlsrv_fetch_array($stmtRes, SQLSRV_FETCH_ASSOC)){
    $reservations[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Premium Dashboard — Carrie's Cafe</title>
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

        /* USER AREA */
        .user-controls { display: flex; align-items: center; gap: 25px; }
        .notif-trigger { position: relative; font-size: 1.4rem; cursor: pointer; }
        .notif-badge { position: absolute; top: -5px; right: -5px; background: var(--primary); color: #fff; font-size: 10px; padding: 2px 5px; border-radius: 50%; border: 2px solid #fff; }
        .user-pill { 
            background: #fff; border: 1px solid var(--border); border-radius: 50px; padding: 5px 15px 5px 5px;
            display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.85rem; text-decoration: none; color: var(--text);
        }
        .avatar { width: 32px; height: 32px; background: var(--sidebar); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        /* CONTENT */
        .content { padding: 40px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; margin-bottom: 35px; }
        .stat-card { background: var(--card); border-radius: var(--radius); padding: 25px; box-shadow: var(--shadow); border: 1px solid var(--border); transition: var(--transition); }
        .stat-card:hover { transform: translateY(-5px); border-color: var(--accent); }
        .stat-card .label { font-size: 0.75rem; color: #888; text-transform: uppercase; font-weight: 600; }
        .stat-card .value { font-family: 'Playfair Display', serif; font-size: 2.2rem; margin: 10px 0; font-weight: 700; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 35px; }
        .card { background: var(--card); border-radius: var(--radius); padding: 30px; border: 1px solid var(--border); box-shadow: var(--shadow); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        
        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; padding: 12px; font-size: 0.7rem; color: #999; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        tbody td { padding: 12px; border-bottom: 1px solid #f9f4f0; font-size: 0.9rem; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge.accepted { background: #e8f5e9; color: #2e7d32; }
        .badge.pending { background: #fff8e1; color: #f57f17; }
        .badge.declined { background: #ffebee; color: #c62828; }

        /* ACTION TILES */
        .action-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .action-tile { 
            background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 25px; 
            text-decoration: none; color: var(--text); text-align: center; transition: var(--transition);
        }
        .action-tile:hover { background: var(--sidebar); color: #fff; transform: translateY(-5px); }
        .action-tile .icon { font-size: 2rem; display: block; margin-bottom: 10px; }

        /* NOTIFICATIONS */
        .notif-box { 
            display: none; position: absolute; right: 0; top: 45px; width: 320px; background: #fff; 
            border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid var(--border); 
            max-height: 400px; overflow-y: auto; z-index: 1001; 
        }
        .notif-item { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.85rem; }
        .notif-item.unread { background: #fffaf5; border-left: 4px solid var(--accent); }

        @media (max-width: 1024px) { .grid-4 { grid-template-columns: 1fr 1fr; } .sidebar { display:none; } .main { margin-left:0; width:100%; } }
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
        <span class="page-title">Dashboard</span>
        <div class="user-controls">
            <div class="notif-trigger" onclick="toggleNotifications()">
                🔔 <span id="notifCount" class="notif-badge">0</span>
                <div id="notifList" class="notif-box"></div>
            </div>
            <a href="/food_ar_app/admin/index.php" class="user-pill">
                <div class="avatar"><?php echo strtoupper(substr($admin_name,0,2)); ?></div>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="content">
        <div class="grid-4">
            <div class="stat-card">
                <div class="label">Total Orders</div>
                <div class="value"><?php echo $orderCount; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Daily Revenue</div>
                <div class="value" style="color:var(--primary);">LKR <?php echo number_format($todayRevenue,0); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Reservations</div>
                <div class="value"><?php echo $resCount; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Menu Size</div>
                <div class="value"><?php echo $menuCount; ?></div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <h3>Latest Additions</h3>
                    <a href="/food_ar_app/admin/menu/menu_manage.php" style="color:var(--accent); font-size:0.8rem; text-decoration:none;">View All</a>
                </div>
                <table>
                    <thead><tr><th>Name</th><th>Category</th><th>Price</th></tr></thead>
                    <tbody>
                        <?php foreach($foods as $f){ ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($f['FoodName']); ?></strong></td>
                            <td><?php echo htmlspecialchars($f['Category']); ?></td>
                            <td>LKR <?php echo number_format($f['Price'], 0); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Recent Bookings</h3>
                    <a href="/food_ar_app/admin/reservation/reservation_manage.php" style="color:var(--accent); font-size:0.8rem; text-decoration:none;">Manage</a>
                </div>
                <table>
                    <thead><tr><th>Customer</th><th>Seat</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($reservations as $r){ ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['DisplayName']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['SeatNumber']); ?></td>
                            <td><span class="badge <?php echo strtolower($r['Status']); ?>"><?php echo htmlspecialchars($r['Status']); ?></span></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <h3 style="margin-bottom:20px; font-family:'Playfair Display', serif;">Quick Management</h3>
        <div class="action-grid">
            <a href="/food_ar_app/admin/menu/add_food.php" class="action-tile">
                <span class="icon">➕</span><span>New Food</span>
            </a>
            <a href="/food_ar_app/admin/reservation/reservation_manage.php" class="action-tile">
                <span class="icon">📅</span><span>Reservations</span>
            </a>
            <a href="/food_ar_app/admin/order/view_orders.php" class="action-tile">
                <span class="icon">🛍️</span><span>Orders</span>
            </a>
            <a href="/food_ar_app/admin/feedback/feedback_manage.php" class="action-tile">
                <span class="icon">🌟</span><span>Feedback</span>
            </a>
        </div>
    </div>
</div>

<script>
let lastCount = 0;
async function loadNotifications() {
    try {
        const response = await fetch('/food_ar_app/admin/notification/fetch_admin_notifications.php');
        const data = await response.json();
        let html = `<div style="padding:15px; font-weight:bold; border-bottom:1px solid #eee;">Notifications</div>`;
        let unread = 0;
        data.forEach(n => {
            if (n.IsRead == 0) unread++;
            html += `<div class="notif-item ${n.IsRead == 0 ? 'unread' : ''}">
                <div style="font-weight:600">${n.FullName}</div>
                <div style="color:#666">${n.Message}</div>
            </div>`;
        });
        document.getElementById("notifList").innerHTML = html || '<div style="padding:20px;">No updates</div>';
        document.getElementById("notifCount").innerText = unread;
        document.getElementById("notifCount").style.display = unread > 0 ? 'block' : 'none';
        if (unread > lastCount) document.getElementById("notifSound").play();
        lastCount = unread;
    } catch (e) {}
}

function toggleNotifications() {
    const list = document.getElementById("notifList");
    list.style.display = list.style.display === "block" ? "none" : "block";
    if(list.style.display === "block") fetch('/food_ar_app/admin/notification/mark_notifications_read.php');
}

loadNotifications();
setInterval(loadNotifications, 5000);
</script>

</body>
</html>