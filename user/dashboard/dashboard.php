<?php
session_start();
include('../../includes/db.php');

if(!isset($_SESSION['customer_id'])){
     header("Location: /food_ar_app/user/index.php");
    exit();
}

$sql_customer = "SELECT FullName FROM Users WHERE UserID = ?";
$stmt_customer = sqlsrv_query($conn,$sql_customer,[$_SESSION['customer_id']]);
$customer_info = sqlsrv_fetch_array($stmt_customer,SQLSRV_FETCH_ASSOC);

/* GET CAFE SETTINGS */
$sql_settings = "SELECT TOP 1 * FROM CafeSettings";
$stmt_settings = sqlsrv_query($conn, $sql_settings);

if($stmt_settings){
    $settings = sqlsrv_fetch_array($stmt_settings, SQLSRV_FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carrie's Cafe — Guest Dashboard</title>
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
    letter-spacing: 1px;
    padding: 8px 15px;
    border: 1px solid rgba(255, 107, 107, 0.2);
    border-radius: 5px;
    transition: 0.3s;
}

.logout-link:hover { background: rgba(255, 107, 107, 0.1); }

/* Mobile Menu Toggle Button */
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
    font-family: 'Inter', sans-serif;
    font-size: 10px;
    letter-spacing: 1px;
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
    transition: color 0.3s;
}

nav a:hover { color: var(--accent-gold); }

.logo-circle {
    width: 110px;
    height: 110px;
    background: var(--dark-espresso);
    border-radius: 50%; /* Makes the container a perfect circle */
    border: 3px solid var(--accent-gold);
    
    /* Centering and sizing the image within the container */
    display: flex;
    justify-content: center;
    align-items: center;
    
    /* Positioning over the nav bar and visual effect */
    margin: -35px 20px;
    z-index: 100;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    flex-shrink: 0;
    overflow: hidden; /* Important to clip the image to the circular shape */
}

.logo-circle img {
    /* Updated dimensions and object-fit for a proper circular image fill */
    width: 100%;
    height: 100%;
    object-fit: cover; /* Ensures the image covers the circle area, potentially cropping edges */
    display: block;
}

/* --- HERO SECTION --- */
.hero {
    height: 500px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    background: radial-gradient(circle, rgba(0,0,0,0.1), rgba(18,10,6,0.9));
}

.hero img.cup {
    width: 320px;
    filter: drop-shadow(0 20px 40px rgba(0,0,0,0.8));
    margin-bottom: 20px;
}

.hero h2 {
    font-family: 'Playfair Display', serif;
    font-size: 42px;
    font-style: italic;
    color: var(--accent-gold);
    margin: 0;
}

/* --- DASHBOARD CARDS --- */
.dashboard_cards {
    max-width: 1000px;
    margin: 40px auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
    padding: 0 20px;
}

.card {
    background: rgba(44, 27, 14, 0.7);
    padding: 40px 30px;
    text-align: center;
    border-radius: 15px;
    border: 1px solid rgba(212, 163, 115, 0.1);
    transition: 0.4s ease;
    backdrop-filter: blur(10px);
}

.card:hover {
    transform: translateY(-10px);
    border-color: var(--accent-gold);
    background: var(--dark-espresso);
}

.card img {
    width: 55px;
    margin-bottom: 20px;
    filter: sepia(1) brightness(1.2);
}

.card h3 { font-family: 'Playfair Display', serif; color: var(--accent-gold); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px; }

.card a {
    display: inline-block;
    color: var(--dark-espresso);
    background: var(--accent-gold);
    padding: 8px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

/* --- PROMO SECTION --- */
.promo-section {
    background: rgba(30, 18, 10, 0.6);
    max-width: 1000px;
    margin: 0 auto 80px auto;
    padding: 40px;
    border-radius: 20px;
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 50px;
    border: 1px solid rgba(212, 163, 115, 0.05);
}

.promo-left { display: flex; gap: 25px; align-items: center; }

.circle-img {
    width: 150px; height: 150px;
    border-radius: 50%;
    border: 4px solid var(--accent-gold);
    object-fit: cover;
}

.btn-read {
    display: inline-block;
    background: var(--cream);
    color: var(--dark-espresso);
    padding: 10px 20px;
    text-decoration: none;
    font-size: 11px;
    font-weight: bold;
    margin-top: 15px;
    border-radius: 4px;
}

.promo-right { display: flex; flex-direction: column; gap: 20px; }

.item {
    display: flex;
    gap: 15px;
    align-items: center;
    background: rgba(255,255,255,0.03);
    padding: 15px;
    border-radius: 12px;
}

.small-circle { width: 50px; height: 50px; border-radius: 50%; border: 2px solid var(--accent-gold); }

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

/* --- RESPONSIVE ADJUSTMENTS --- */
@media (max-width: 900px) {
    .dashboard_cards { grid-template-columns: repeat(2, 1fr); }
    .promo-section { grid-template-columns: 1fr; gap: 30px; }
    .hero h2 { font-size: 32px; }
}

@media (max-width: 768px) {
    header h1 { font-size: 22px; }
    .logout-link { right: 15px; font-size: 10px; padding: 5px 10px; }
    .menu-toggle { display: block; }

    /* Collapsible Mobile Nav */
    nav { 
        display: none; 
        flex-direction: column; 
        padding: 20px 0; 
        gap: 15px;
    }
    nav.active { display: flex; }
    .nav-left, .nav-right { flex-direction: column; gap: 15px; }
    .logo-circle { margin: 10px 0; order: -1; width: 80px; height: 80px; }

    .hero { height: auto; padding: 60px 20px; }
    .hero img.cup { width: 200px; }

    .dashboard_cards { grid-template-columns: 1fr; }
    
    .promo-left { flex-direction: column; text-align: center; }
    .footer-container { grid-template-columns: 1fr; gap: 30px; text-align: center; }
}

/* NOTIFICATION BOX */
#notifList {
    background: var(--dark-espresso) !important;
    border: 1px solid var(--accent-gold);
    color: var(--cream) !important;
    box-shadow: 0 15px 40px rgba(0,0,0,0.8);
    max-width: 90vw; /* Responsive width for mobile */
}
</style>
</head>

<body>

<header>
    <button class="menu-toggle" onclick="toggleMenu()">MENU</button>
    <h1>Welcome to Carrie's Cafe</h1>
    <a href="/food_ar_app3/user/index.php?logout=1" class="logout-link">Sign Out</a>
</header>

<div class="nav_container">
    <nav id="mainNav">
        <div class="nav-left">
            <a href="/food_ar_app/user/dashboard/dashboard.php">Main</a>
            <a href="/food_ar_app/user/menu/menu.php">Menu</a>
            <a href="/food_ar_app/user/reservation/reserve.php">Reserve</a>
        </div>

        <div class="logo-circle">
            <img src="/food_ar_app/user/Lor.png" alt="Cafe Logo">
        </div>

        <div class="nav-right">
            <a href="/food_ar_app/user/order/orders.php">Orders</a>
            <a href="/food_ar_app/user/profile.php">Profile</a>
            <a href="/food_ar_app/user/order/cart.php">Cart</a>
        </div>
    </nav>
</div>

<section class="hero">
    <img src="/food_ar_app/user/login_image.png" alt="Coffee Cup" class="cup">
    <h2>The Art of Perfect Brewing</h2>
    <p style="font-size:12px;letter-spacing:4px;color:var(--accent-gold); text-transform:uppercase; margin-top:15px;">
        Organic Ingredients • Freshly Roasted • Crafted with Passion
    </p>
</section>

<section class="dashboard_cards">
    <div class="card">
        <img src="https://cdn-icons-png.flaticon.com/512/924/924514.png" alt="Menu">
        <h3>Menu</h3>
        <p>Discover our curated collection of signature blends and artisan pastries.</p>
        <a href="/food_ar_app/user/menu/menu.php">Explore</a>
    </div>

    <div class="card">
        <img src="https://cdn-icons-png.flaticon.com/512/3063/3063822.png" alt="AR">
        <h3>AR View</h3>
        <p>Experience the future. Visualize your desserts in 3D before you order.</p>
        <a href="/food_ar_app/user/menu/customize_ar.php">Launch AR</a>
    </div>

    <div class="card">
        <img src="https://cdn-icons-png.flaticon.com/512/2734/2734033.png" alt="Reserve">
        <h3>Reserve</h3>
        <p>Skip the queue. Secure your preferred table for an intimate dining experience.</p>
        <a href="/food_ar_app/user/reservation/reserve.php">Book Now</a>
    </div>
</section>

<section class="promo-section">
    <div class="promo-left">
        <img src="https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=300&q=80" class="circle-img">
        <div>
            <h2 style="font-family:'Playfair Display'; font-size: 28px; margin:0;">Guest Alerts</h2>
            <p style="font-size:14px; color: var(--text-muted); margin:10px 0">
                Welcome, <strong><?php echo htmlspecialchars($customer_info['FullName']); ?></strong>.<br>
                You have (<span id="notifCount" style="color:var(--accent-gold)">0</span>) new alerts.
            </p>
            <a href="javascript:void(0)" class="btn-read" onclick="toggleNotifications()">OPEN NOTIFICATIONS</a>
            <a href="/food_ar_app/user/feedback/feedback.php" class="btn-read" style="background:var(--accent-gold); color:var(--dark-espresso);">GIVE FEEDBACK</a>
        </div>
    </div>

    <div class="promo-right">
        <div class="item">
            <img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=100&q=80" class="small-circle">
            <div>
                <h4 style="margin:0;color:var(--accent-gold); font-size:14px;">TODAY'S SPECIAL</h4>
                <p style="font-size:11px; color: var(--text-muted);">Try our signature burnt caramel cappuccino today.</p>
            </div>
        </div>
        <div class="item">
            <img src="https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=100&q=80" class="small-circle">
            <div>
                <h4 style="margin:0;color:var(--accent-gold); font-size:14px;">AR PREVIEW</h4>
                <p style="font-size:11px; color: var(--text-muted);">New interactive 3D models added to the bakery section.</p>
            </div>
        </div>
    </div>
</section>

<div id="notifList" style="display:none;position:fixed;bottom:30px;right:30px;padding:25px;border-radius:15px;width:320px;z-index:1000;"></div>

<script>
// Toggle for mobile menu
function toggleMenu() {
    document.getElementById("mainNav").classList.toggle("active");
}

function toggleNotifications(){
    let box=document.getElementById("notifList");
    box.style.display=(box.style.display==="block")?"none":"block";
}

async function loadNotifications(){
    try{
        const res=await fetch('/food_ar_app/user/notification/get_customer_notifications.php');
        const data=await res.json();
        let html="<h3 style='font-family:Playfair Display; color:var(--accent-gold); margin-top:0;'>Inbox</h3>";
        data.forEach(n=>{
            html+=`<div style="border-bottom:1px solid rgba(212,163,115,0.1);padding:10px 0; font-size:12px;">${n.Message}</div>`;
        });
        document.getElementById("notifList").innerHTML=html;
        document.getElementById("notifCount").innerText=data.length;
    }catch(e){ console.log("Notification error"); }
}
setInterval(loadNotifications, 10000);
loadNotifications();
</script>

<footer class="footer">
    <div class="footer-container">
        <div>
            <h3 style="color:var(--accent-gold); font-family:'Playfair Display';">Visit Us</h3>
            <p style="font-size:13px;">📍 <?php echo $settings['Address']; ?></p>
            <p style="font-size:13px;">📞 <?php echo $settings['Phone']; ?></p>
            <p style="font-size:13px;">✉ <?php echo $settings['Email']; ?></p>
        </div>
        <div>
            <h3 style="color:var(--accent-gold); font-family:'Playfair Display';"><?php echo $settings['CafeName']; ?></h3>
            <p style="font-size:13px; line-height:1.6;"><?php echo $settings['AboutText']; ?></p>
        </div>
        <div>
            <h3 style="color:var(--accent-gold); font-family:'Playfair Display';">Service Hours</h3>
            <p style="font-size:13px;"><?php echo $settings['OpeningHours']; ?></p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?php echo date("Y"); ?> All Rights Reserved · Experience the Craft</p>
    </div>
</footer>

</body>
</html>