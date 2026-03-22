<?php
session_start();
include('../../includes/db.php');

if(!isset($_SESSION['customer_id'])){
     header("Location: /food_ar_app/user/index.php");
    exit();
}

$user_id = $_SESSION['customer_id'];

/* ---------------- GET ORDERS ---------------- */
$sql = "SELECT O.OrderID, O.OrderDate, O.Status, O.TotalAmount
        FROM Orders O
        WHERE O.UserID = ?
        ORDER BY O.OrderDate DESC";

$stmt = sqlsrv_query($conn, $sql, [$user_id]);
if($stmt === false) die(print_r(sqlsrv_errors(), true));

$orders = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $orders[] = $row;
}

/* GET CAFE SETTINGS FOR FOOTER */
$sql_settings = "SELECT TOP 1 * FROM CafeSettings";
$stmt_settings = sqlsrv_query($conn, $sql_settings);
$settings = ($stmt_settings) ? sqlsrv_fetch_array($stmt_settings, SQLSRV_FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order History — Carrie's Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400;1,700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
<style>
:root{
    --dark-espresso: #1e120a;
    --deep-roast: #2c1b0e;
    --accent-gold: #d4a373;
    --cream: #fdfaf8;
    --text-muted: #b09c89;
    --glass: rgba(44, 27, 14, 0.95);
}

* { box-sizing: border-box; }

body {
    margin:0;
    font-family:'Inter', sans-serif;
    background:#120a06 url('https://www.transparenttextures.com/patterns/dark-leather.png');
    color: var(--cream);
    overflow-x: hidden;
}

/* --- HEADER --- */
header {
    background: var(--dark-espresso);
    border-bottom: 2px solid #3d2a1a;
    box-shadow: 0 4px 10px rgba(0,0,0,0.5);
    text-align: center;
    padding: 25px 0;
    position: relative;
}

header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    color: var(--accent-gold);
    margin: 0;
    letter-spacing: 1px;
}

.logout-link {
    position: absolute;
    top: 50%;
    right: 30px;
    transform: translateY(-50%);
    color: #ff6b6b;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    padding: 8px 15px;
    border: 1px solid rgba(255, 107, 107, 0.2);
    border-radius: 5px;
    transition: 0.3s;
}

/* --- NAVIGATION --- */
.nav_container {
    background: var(--glass);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(212, 163, 115, 0.1);
    padding: 10px 0;
}

nav {
    max-width: 1000px;
    margin: auto;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
}

.nav-left, .nav-right {
    display: flex;
    gap: 30px;
    align-items: center;
}

nav a {
    color: var(--cream);
    text-decoration: none;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 2px;
    font-weight: 600;
    transition: 0.3s;
}

nav a:hover { color: var(--accent-gold); }

.logo-circle {
    width: 110px;
    height: 110px;
    background: var(--dark-espresso);
    border-radius: 50%;
    border: 3px solid var(--accent-gold);
    
   
    display: flex;
    justify-content: center;
    align-items: center;
    

    margin: -35px 20px;
    z-index: 100;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    flex-shrink: 0;
    overflow: hidden; 
}

.logo-circle img {

    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* --- HERO AREA --- */
.hero_area {
    text-align:center;
    padding:80px 20px 60px;
}

.hero_area h1 {
    font-family:'Playfair Display', serif;
    font-size:3.5rem;
    color: var(--accent-gold);
    margin-bottom:10px;
}

.hero_area p {
    font-size:1.1rem;
    color: var(--text-muted);
    letter-spacing: 1px;
}

/* --- ORDERS CONTAINER --- */
.container {
    max-width:1000px;
    margin:0 auto 80px;
    padding:0 20px;
}

.order-card {
    background: rgba(44, 27, 14, 0.6);
    border-radius:15px;
    border: 1px solid rgba(212, 163, 115, 0.1);
    padding:30px;
    margin-bottom:30px;
    transition: 0.3s;
}

.order-card:hover { 
    background: rgba(44, 27, 14, 0.8);
    border-color: var(--accent-gold);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 1px solid rgba(212, 163, 115, 0.1);
    padding-bottom: 15px;
}

.order-card h3 {
    font-family:'Playfair Display', serif;
    font-size:1.4rem;
    color: var(--accent-gold);
    margin: 0;
}

.order-date {
    font-size: 12px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
}

table {
    width:100%;
    border-collapse: collapse;
}

th {
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    color: var(--accent-gold);
    letter-spacing: 1.5px;
    padding-bottom: 15px;
}

td { 
    padding: 12px 0;
    font-size: 14px;
    vertical-align: top;
    border-top: 1px solid rgba(255,255,255,0.05);
}

.items-list { line-height: 1.8; color: var(--cream); }
.total-cell { font-weight: 600; font-size: 18px; color: var(--accent-gold); }

/* Status Colors */
.status-Pending { color: #ffc107; }
.status-Accepted { color: #17a2b8; }
.status-Declined { color: #dc3545; }
.status-Completed { color: #28a745; }

.status-pill {
    padding: 4px 12px;
    border-radius: 20px;
    background: rgba(255,255,255,0.05);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

/* --- FOOTER --- */
.footer {
    background: var(--dark-espresso);
    padding: 60px 20px 20px;
    border-top: 2px solid #3d2a1a;
}

.footer-container {
    max-width: 1000px;
    margin: auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
}

.footer-bottom {
    text-align: center;
    margin-top: 50px;
    padding-top: 20px;
    border-top: 1px solid rgba(212, 163, 115, 0.1);
    font-size: 11px;
    color: #6d5d50;
}

@media(max-width:768px){
    .nav-left, .nav-right { display: none; }
    .logo-circle { margin: 10px 0; }
    .footer-container { grid-template-columns: 1fr; text-align: center; }
    .order-header { flex-direction: column; align-items: flex-start; gap: 10px; }
}
</style>
</head>
<body>

<header>
    <h1>Carrie's Cafe</h1>
    <a href="/food_ar_app/user/index.php?logout=1" class="logout-link">Sign Out</a>
</header>

<div class="nav_container">
    <nav>
        <div class="nav-left">
            <a href="/food_ar_app/user/dashboard/dashboard.php">Main</a>
            <a href="/food_ar_app/user/menu/menu.php">Menu</a>
            <a href="/food_ar_app/user/reservation/reserve.php">Reserve</a>
        </div>
        <div class="logo-circle">
            <img src="/food_ar_app/user/Lor.png" alt="Logo">
        </div>
        <div class="nav-right">
            <a href="/food_ar_app/user/order/orders.php" style="color:var(--accent-gold)">Orders</a>
            <a href="/food_ar_app/user/profile.php">Profile</a>
            <a href="/food_ar_app/user/order/cart.php">Cart</a>
        </div>
    </nav>
</div>

<div class="hero_area">
    <h1>Your Orders</h1>
    <p>Trace your journey through our finest flavors</p>
</div>

<div class="container">
<?php if(empty($orders)): ?>
    <div style="text-align:center; padding:50px; background:rgba(255,255,255,0.03); border-radius:15px;">
        <p style="color:var(--text-muted);">No orders found. Time to explore the <a href="/food_ar_app/user/menu/menu.php" style="color:var(--accent-gold);">menu</a>!</p>
    </div>
<?php endif; ?>

<?php foreach($orders as $o): ?>
    <div class="order-card">
        <div class="order-header">
            <h3>Order #<?= $o['OrderID'] ?></h3>
            <span class="order-date"><?= $o['OrderDate']->format('F d, Y — h:i A') ?></span>
        </div>
        <table>
            <thead>
                <tr>
                    <th width="20%">Status</th>
                    <th width="60%">Items Purchased</th>
                    <th width="20%" style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <span class="status-pill status-<?= $o['Status'] ?>">
                            <?= htmlspecialchars($o['Status']) ?>
                        </span>
                    </td>
                    <td class="items-list">
                        <?php
                        $item_sql = "SELECT OI.Quantity, OI.Price, ISNULL(M.FoodName, OI.Category) AS DisplayName
                                     FROM OrderItems OI
                                     LEFT JOIN MenuItems M ON OI.MenuItemID = M.MenuItemID
                                     WHERE OI.OrderID = ?";
                        $item_stmt = sqlsrv_query($conn, $item_sql, [$o['OrderID']]);
                        
                        if ($item_stmt === false) {
                            echo "Error loading items.";
                        } else {
                            while($it = sqlsrv_fetch_array($item_stmt, SQLSRV_FETCH_ASSOC)){
                                echo "• " . htmlspecialchars($it['DisplayName']) . " (" . $it['Quantity'] . ")<br>";
                            }
                        }
                        ?>
                    </td>
                    <td class="total-cell" style="text-align:right;">
                        LKR <?= number_format($o['TotalAmount'], 2) ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
</div>

<footer class="footer">
    <div class="footer-container">
        <div>
            <h3 style="color:var(--accent-gold); font-family:'Playfair Display';">Visit Us</h3>
            <p style="font-size:13px;">📍 <?= $settings['Address'] ?? '123 Bakery Lane'; ?></p>
            <p style="font-size:13px;">📞 <?= $settings['Phone'] ?? '(555) 012-3456'; ?></p>
        </div>
        <div>
            <h3 style="color:var(--accent-gold); font-family:'Playfair Display';"><?= $settings['CafeName'] ?? "Carrie's Cafe"; ?></h3>
            <p style="font-size:13px; line-height:1.6;"><?= $settings['AboutText'] ?? 'Crafting memories through vintage aesthetics and modern flavors.'; ?></p>
        </div>
        <div>
            <h3 style="color:var(--accent-gold); font-family:'Playfair Display';">Hours</h3>
            <p style="font-size:13px;"><?= $settings['OpeningHours'] ?? 'Mon-Sun: 8am - 10pm'; ?></p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?= date("Y"); ?> All Rights Reserved · Carrie's Cafe & Bakery</p>
    </div>
</footer>

</body>
</html>