<?php
session_start();
include('../../includes/db.php');

if(!isset($_SESSION['customer_id'])){
    header("Location: /food_ar_app/user/index.php");
    exit();
}

/* ---------- FETCH ONLY CATEGORIES WITH BASE AR MODEL ---------- */
$sql = "SELECT Category 
        FROM CategoryARModels
        WHERE ModelGLB IS NOT NULL
        ORDER BY Category ASC";

$stmt = sqlsrv_query($conn, $sql);

if(!$stmt){
    die("Error fetching categories: " . print_r(sqlsrv_errors(), true));
}

$categories = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $categories[] = $row['Category'];
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
    <title>AR Categories — Carrie's Cafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --dark-espresso: #1e120a;
            --deep-roast: #2c1b0e;
            --accent-gold: #d4a373;
            --cream: #fdfaf8;
            --text-muted: #b09c89;
            --glass: rgba(44, 27, 14, 0.95);
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #120a06 url('https://www.transparenttextures.com/patterns/dark-leather.png');
            color: var(--cream);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* --- HEADER & NAV --- */
        header {
            background: var(--dark-espresso);
            border-bottom: 2px solid #3d2a1a;
            text-align: center;
            padding: 25px 0;
            position: relative;
        }
        header h1 { font-family: 'Playfair Display', serif; font-size: 28px; color: var(--accent-gold); margin: 0; }

        .logout-link {
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
            color: #ff6b6b;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 8px 15px;
            border: 1px solid rgba(255, 107, 107, 0.2);
            border-radius: 5px;
        }

        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            background: none;
            border: 1px solid var(--accent-gold);
            color: var(--accent-gold);
            padding: 5px 12px;
            cursor: pointer;
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10px;
            letter-spacing: 1px;
        }

        .nav_container { 
            background: var(--glass); 
            backdrop-filter: blur(10px); 
            padding: 10px 0; 
            border-bottom: 1px solid rgba(212, 163, 115, 0.1);
        }

        nav { max-width: 1000px; margin: auto; display: flex; justify-content: center; align-items: center; gap: 20px; }
        .nav-left, .nav-right { display: flex; gap: 30px; align-items: center; }
        
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
            width: 110px; height: 110px;
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
        .logo-circle img { width: 100%; height: 100%; object-fit: cover; }

        /* --- MAIN CONTENT --- */
        .main-content { flex: 1; padding: 60px 20px; max-width: 1000px; margin: auto; width: 100%; box-sizing: border-box; }
        
        .page-title {
            text-align: center;
            font-family: 'Playfair Display', serif;
            color: var(--accent-gold);
            font-size: clamp(28px, 5vw, 42px);
            margin-bottom: 10px;
        }
        .page-subtitle {
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 50px;
            letter-spacing: 1px;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 30px;
        }

        .category-card {
            background: rgba(44, 27, 14, 0.7);
            padding: 50px 30px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(212, 163, 115, 0.1);
            cursor: pointer;
            transition: 0.4s ease;
            backdrop-filter: blur(10px);
        }

        .category-card:hover {
            transform: translateY(-10px);
            border-color: var(--accent-gold);
            background: var(--dark-espresso);
        }

        .category-card h3 {
            color: var(--accent-gold);
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            margin: 0 0 12px 0;
        }

        .category-card p {
            font-size: 11px;
            color: var(--text-muted);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
        }

        /* --- FOOTER --- */
        .footer { background: var(--dark-espresso); border-top: 2px solid #3d2a1a; padding: 60px 20px 20px; margin-top: 80px; }
        .footer-container { max-width: 1000px; margin: auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; }
        .footer h3 { font-family: 'Playfair Display', serif; color: var(--accent-gold); margin-bottom: 15px; }
        .footer p { font-size: 13px; color: var(--cream); }
        .footer-bottom { margin-top: 50px; padding-top: 20px; border-top: 1px solid rgba(212,163,115,0.1); text-align: center; color: #6d5d50; font-size: 11px; }

        /* --- RESPONSIVE FIXES --- */
        @media (max-width: 768px) {
            header h1 { font-size: 22px; }
            .logout-link { right: 15px; font-size: 10px; padding: 5px 10px; }
            .menu-toggle { display: block; }

            /* Mobile Nav Logic */
            nav { display: none; flex-direction: column; padding: 20px 0; gap: 15px; }
            nav.active { display: flex; }
            .nav-left, .nav-right { flex-direction: column; gap: 15px; }
            .logo-circle { margin: 10px 0; order: -1; width: 85px; height: 85px; }

            .category-grid { grid-template-columns: 1fr; }
            .footer-container { grid-template-columns: 1fr; text-align: center; }
        }
    </style>
</head>
<body>

<header>
    <button class="menu-toggle" onclick="toggleMenu()">MENU</button>
    <h1>Carrie's Cafe</h1>
    <a href="/food_ar_app/user/index.php?logout=1" class="logout-link">Sign Out</a>
</header>

<div class="nav_container">
    <nav id="mainNav">
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

<div class="main-content">
    <h2 class="page-title">Interactive AR Menu</h2>
    <p class="page-subtitle">Select a category to customize and view your meal in 3D</p>

    <div class="category-grid">
        <?php foreach($categories as $cat): ?>
            <div class="category-card" onclick="window.location.href='customize.php?category=<?= urlencode($cat) ?>'">
                <h3><?= htmlspecialchars($cat) ?></h3>
                <p>Explore & Customize</p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<footer class="footer">
    <div class="footer-container">
        <div>
            <h3>Visit Us</h3>
            <p>📍 <?= htmlspecialchars($settings['Address'] ?? '123 Bakery Lane'); ?></p>
            <p>📞 <?= htmlspecialchars($settings['Phone'] ?? '(555) 012-3456'); ?></p>
            <p>✉ <?= htmlspecialchars($settings['Email'] ?? 'hello@carriescafe.com'); ?></p>
        </div>
        <div>
            <h3><?= htmlspecialchars($settings['CafeName'] ?? "Carrie's Cafe"); ?></h3>
            <p style="line-height:1.6;"><?= htmlspecialchars($settings['AboutText'] ?? 'Crafting memories through vintage aesthetics and modern flavors.'); ?></p>
        </div>
        <div>
            <h3>Service Hours</h3>
            <p><?= htmlspecialchars($settings['OpeningHours'] ?? 'Mon-Sun: 8am - 10pm'); ?></p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?= date("Y"); ?> All Rights Reserved · Experience the Craft</p>
    </div>
</footer>

<script>
    function toggleMenu() {
        document.getElementById("mainNav").classList.toggle("active");
    }
</script>

</body>
</html>