<?php
session_start();
include('../../includes/db.php');

if(!isset($_SESSION['customer_id'])){
    header("Location: /food_ar_app/user/index.php");
    exit();
}

/* ---------- FETCH CUSTOMER INFO ---------- */
$sql_customer = "SELECT FullName FROM Users WHERE UserID = ?";
$stmt_customer = sqlsrv_query($conn, $sql_customer, [$_SESSION['customer_id']]);
$customer_info = sqlsrv_fetch_array($stmt_customer, SQLSRV_FETCH_ASSOC);

/* ---------- GET MENU ID ---------- */
$menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 0;
if(!$menu_id) die("No food selected");

/* ---------- FETCH FOOD ---------- */
$sql = "SELECT * FROM MenuItems WHERE MenuItemID=?";
$stmt = sqlsrv_query($conn, $sql, [$menu_id]);
if(!$stmt || !sqlsrv_has_rows($stmt)) die("Food not found");
$food = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

/* ---------- HANDLE FORM SUBMISSION ---------- */
if(isset($_POST['customize_submit'])){
    $quantity = intval($_POST['quantity']);
    
    // Notes removed as requested
    if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    if(isset($_SESSION['cart'][$menu_id])){
        $_SESSION['cart'][$menu_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$menu_id] = [
            'name' => $food['FoodName'],
            'price' => $food['Price'],
            'quantity' => $quantity
        ];
    }
    header("Location:/food_ar_app/user/order/cart.php");
    exit();
}

/* ---------- AR MODEL PATHS ---------- */
$ar_model_glb  = !empty($food['ARModelGLB'])  ? "/food_ar_app/" . $food['ARModelGLB']  : "";
$ar_model_usdz = !empty($food['ARModelUSDZ']) ? "/food_ar_app/" . $food['ARModelUSDZ'] : "";
$poster_image  = !empty($food['ImagePath'])   ? "/food_ar_app/" . $food['ImagePath']   : "";

/* GET CAFE SETTINGS */
$sql_settings = "SELECT TOP 1 * FROM CafeSettings";
$stmt_settings = sqlsrv_query($conn, $sql_settings);
$settings = ($stmt_settings) ? sqlsrv_fetch_array($stmt_settings, SQLSRV_FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($food['FoodName']) ?> — Carrie's Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400;1,700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
<script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>

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

body{
    margin:0;
    font-family: 'Inter', sans-serif;
    background: #120a06 url('https://www.transparenttextures.com/patterns/dark-leather.png');
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
}

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

/* --- CUSTOMIZE SECTION --- */
.container {
    max-width: 1000px;
    margin: 60px auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
}

.viewer-card {
    background: rgba(44, 27, 14, 0.7);
    border-radius: 15px;
    border: 1px solid rgba(212, 163, 115, 0.1);
    overflow: hidden;
    height: 500px;
}

model-viewer {
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, #2c1b0e 0%, #120a06 100%);
}

.form-card h2 {
    font-family: 'Playfair Display', serif;
    font-size: 42px;
    color: var(--accent-gold);
    margin: 0 0 10px 0;
}

.price-tag {
    font-size: 24px;
    color: var(--cream);
    margin-bottom: 25px;
    display: block;
}

.custom-box {
    background: rgba(30, 18, 10, 0.6);
    padding: 30px;
    border-radius: 15px;
    border: 1px solid rgba(212, 163, 115, 0.05);
}

label {
    display: block;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--accent-gold);
    margin-bottom: 10px;
    font-weight: 600;
}

input[type="number"] {
    width: 100%;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(212, 163, 115, 0.2);
    color: var(--cream);
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.btn-group { display: flex; gap: 15px; }

.btn {
    flex: 1;
    padding: 15px;
    border: none;
    border-radius: 5px;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 11px;
    cursor: pointer;
    transition: 0.3s;
}

/* Initially hidden, shown only on mobile via JS */
.btn-ar { 
    background: #17a2b8; 
    color: white; 
    display: none; 
}

.btn-cart { background: var(--accent-gold); color: var(--dark-espresso); }
.btn:hover { transform: translateY(-3px); }

/* --- FOOTER --- */
.footer {
    background: var(--dark-espresso);
    padding: 60px 20px 20px;
    border-top: 2px solid #3d2a1a;
    margin-top: 80px;
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

@media (max-width: 900px) {
    .container { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .menu-toggle { display: block; }
    nav { display: none; flex-direction: column; padding: 20px 0; gap: 15px; }
    nav.active { display: flex; }
    .nav-left, .nav-right { flex-direction: column; gap: 15px; }
    .logo-circle { margin: 10px 0; order: -1; width: 80px; height: 80px; }
    .footer-container { grid-template-columns: 1fr; text-align: center; }
}
</style>
</head>
<body>

<header>
    <button class="menu-toggle" onclick="toggleMenu()">MENU</button>
    <h1>The 3D Experience</h1>
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

<div class="container">
    <div class="viewer-card">
        <model-viewer id="modelViewer" 
            src="<?= $ar_model_glb ?>" 
            ios-src="<?= $ar_model_usdz ?>"
            poster="<?= $poster_image ?>"
            ar ar-modes="scene-viewer webxr quick-look" 
            camera-controls auto-rotate shadow-intensity="1">
        </model-viewer>
    </div>

    <div class="form-card">
        <h2><?= htmlspecialchars($food['FoodName']) ?></h2>
        <span class="price-tag">LKR <?= number_format($food['Price'], 2) ?></span>
        
        <p style="color:var(--text-muted); line-height:1.8; margin-bottom:30px; font-size:14px;">
            <?= htmlspecialchars($food['Description']) ?>
        </p>

        <form method="POST" class="custom-box">
            <label>Quantity</label>
            <input type="number" name="quantity" value="1" min="1">

            <div class="btn-group">
                <button type="button" id="arBtn" class="btn btn-ar" onclick="activateAR()">📱 Launch AR</button>
                <button type="submit" name="customize_submit" class="btn btn-cart">Confirm Order</button>
            </div>
        </form>
    </div>
</div>

<footer class="footer">
    <div class="footer-container">
        <div>
            <h3 style="color:var(--accent-gold); font-family:'Playfair Display';">Visit Us</h3>
            <p style="font-size:13px;">📍 <?= $settings['Address']; ?></p>
            <p style="font-size:13px;">📞 <?= $settings['Phone']; ?></p>
        </div>
        <div>
            <h3 style="color:var(--accent-gold); font-family:'Playfair Display';"><?= $settings['CafeName']; ?></h3>
            <p style="font-size:13px; line-height:1.6;"><?= $settings['AboutText']; ?></p>
        </div>
        <div>
            <h3 style="color:var(--accent-gold); font-family:'Playfair Display';">Hours</h3>
            <p style="font-size:13px;"><?= $settings['OpeningHours']; ?></p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?= date("Y"); ?> All Rights Reserved · Carrie's Cafe</p>
    </div>
</footer>

<script>
function toggleMenu() {
    document.getElementById("mainNav").classList.toggle("active");
}

// AR Visibility logic
function detectMobile() {
    return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
}

window.onload = function() {
    if(detectMobile()) {
        document.getElementById('arBtn').style.display = 'block';
    }
}

function activateAR() {
    const mv = document.getElementById('modelViewer');
    if(mv) mv.activateAR();
}
</script>

</body>
</html>