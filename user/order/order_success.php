<?php
session_start();
include('../../includes/db.php');

$order_id = $_GET['order_id'] ?? 0;
$method = $_GET['method'] ?? '';

/* GET CAFE SETTINGS FOR DYNAMIC FOOTER */
$sql_settings = "SELECT TOP 1 * FROM CafeSettings";
$stmt_settings = sqlsrv_query($conn, $sql_settings);
$settings = ($stmt_settings) ? sqlsrv_fetch_array($stmt_settings, SQLSRV_FETCH_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed — Carrie's Cafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --dark-espresso: #1e120a;
            --accent-gold: #d4a373;
            --cream: #fdfaf8;
            --text-muted: #b09c89;
            --glass: rgba(44, 27, 14, 0.95);
            --success-green: #28a745;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #120a06 url('https://www.transparenttextures.com/patterns/dark-leather.png');
            color: var(--cream);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- NAVIGATION --- */
        header {
            background: var(--dark-espresso);
            border-bottom: 2px solid #3d2a1a;
            text-align: center;
            padding: 25px 0;
        }
        header h1 { font-family: 'Playfair Display', serif; font-size: 28px; color: var(--accent-gold); margin: 0; }

        .nav_container { background: var(--glass); backdrop-filter: blur(10px); padding: 10px 0; }
        nav { max-width: 1000px; margin: auto; display: flex; justify-content: center; align-items: center; gap: 20px; }
        .nav-left, .nav-right { display: flex; gap: 30px; align-items: center; }
        nav a { color: var(--cream); text-decoration: none; text-transform: uppercase; font-size: 11px; letter-spacing: 2px; font-weight: 600; }
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

        /* --- SUCCESS CARD --- */
        .main-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
        }

        .success-card {
            background: rgba(44, 27, 14, 0.7);
            border: 1px solid rgba(212, 163, 115, 0.2);
            padding: 50px;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6);
        }

        .icon-box {
            font-size: 50px;
            color: var(--accent-gold);
            margin-bottom: 20px;
        }

        h2 { font-family: 'Playfair Display', serif; color: var(--accent-gold); font-size: 30px; margin-bottom: 10px; }
        .order-id-label { font-size: 14px; color: var(--text-muted); margin-bottom: 30px; text-transform: uppercase; letter-spacing: 2px; }
        
        .status-msg {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 40px;
            font-size: 15px;
            line-height: 1.6;
        }

        .btn-track {
            display: inline-block;
            background: var(--accent-gold);
            color: var(--dark-espresso);
            padding: 16px 35px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: 0.3s;
        }

        .btn-track:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(212, 163, 115, 0.2);
        }

        /* --- FOOTER --- */
        .footer { background: var(--dark-espresso); border-top: 2px solid #3d2a1a; padding: 50px 0 20px; margin-top: auto; }
        .footer-container { max-width: 1000px; margin: auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; padding: 0 20px; }
        .footer-bottom { margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); text-align: center; color: var(--text-muted); font-size: 12px; }
    </style>
</head>
<body>

<header>
    <h1>Carrie's Cafe</h1>
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
            <a href="/food_ar_app/user/order/orders.php">Orders</a>
            <a href="/food_ar_app/user/profile.php">Profile</a>
            <a href="/food_ar_app/user/order/cart.php">Cart</a>
        </div>
    </nav>
</div>

<div class="main-wrapper">
    <div class="success-card">
        <div class="icon-box">☕</div>
        <h2>Order Confirmed!</h2>
        <div class="order-id-label">Order #<?= htmlspecialchars($order_id) ?></div>
        
        <div class="status-msg">
            <?php if($method === 'cod'): ?>
                <p>We’ve received your order. Please have your cash ready upon delivery or when you arrive for pickup.</p>
            <?php else: ?>
                <p>Your payment has been successfully processed. Sit back and relax while we prepare your favorites.</p>
            <?php endif; ?>
        </div>

        <a href="/food_ar_app/user/order/orders.php" class="btn-track">Track My Order</a>
    </div>
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
            <p style="font-size:13px; line-height:1.6; color:var(--text-muted);"><?= $settings['AboutText'] ?? 'Crafting memories through vintage aesthetics and modern flavors.'; ?></p>
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