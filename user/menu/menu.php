<?php
session_start();
include('../../includes/db.php');

if(!isset($_SESSION['customer_id'])){
     header("Location: /food_ar_app/user/index.php");
    exit();
}

/* GET CAFE SETTINGS FOR FOOTER */
$sql_settings = "SELECT TOP 1 * FROM CafeSettings";
$stmt_settings = sqlsrv_query($conn, $sql_settings);
$settings = ($stmt_settings) ? sqlsrv_fetch_array($stmt_settings, SQLSRV_FETCH_ASSOC) : [];

// ---------- GET FILTER VALUES ----------
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$category   = isset($_GET['category']) ? trim($_GET['category']) : '';

// ---------- BUILD QUERY ----------
$sql = "SELECT * FROM MenuItems WHERE 1=1 ";
$params = [];

if($search !== ''){
    $sql .= " AND (FoodName LIKE ? OR Description LIKE ?) ";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if($category !== ''){
    $sql .= " AND Category = ? ";
    $params[] = $category;
}

$sql .= " ORDER BY FoodName ASC";
$stmt = sqlsrv_query($conn, $sql, $params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carrie's Cafe — Menu</title>
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
}

.logout-link {
    position: absolute;
    top: 50%;
    right: 30px;
    transform: translateY(-50%);
    color: #ff6b6b;
    text-decoration: none;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    padding: 8px 15px;
    border: 1px solid rgba(255, 107, 107, 0.2);
    border-radius: 5px;
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

.nav-left, .nav-right { display: flex; gap: 30px; align-items: center; }

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

/* --- TOAST NOTIFICATION --- */
#toast {
    visibility: hidden;
    min-width: 280px;
    background-color: #28a745; 
    color: #fff;
    text-align: center;
    border-radius: 50px;
    padding: 16px;
    position: fixed;
    z-index: 2000;
    left: 50%;
    bottom: 30px;
    transform: translateX(-50%);
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

#toast.show {
    visibility: visible;
    animation: fadein 0.5s, fadeout 0.5s 2.5s;
}

@keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
@keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }

/* --- SEARCH BOX --- */
.search-box {
    max-width: 1000px;
    margin: 60px auto 30px auto;
    padding: 0 20px;
    text-align: center;
}

.search-box form {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}

.search-box input, .search-box select {
    background: rgba(44, 27, 14, 0.7);
    border: 1px solid rgba(212, 163, 115, 0.2);
    color: white;
    padding: 12px 20px;
    border-radius: 30px;
    outline: none;
}

.search-box button {
    background: var(--accent-gold);
    color: var(--dark-espresso);
    border: none;
    padding: 12px 30px;
    border-radius: 30px;
    font-weight: bold;
    cursor: pointer;
}

/* --- MENU GRID --- */
.food-list {
    max-width: 1100px;
    margin: 40px auto;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
    padding: 0 20px;
}

.food-card {
    background: rgba(44, 27, 14, 0.6);
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid rgba(212, 163, 115, 0.1);
    transition: 0.4s;
    text-align: center;
    padding-bottom: 25px;
    display: flex;
    flex-direction: column;
}

.food-card:hover { transform: translateY(-10px); border-color: var(--accent-gold); }
.food-card img { width: 100%; height: 220px; object-fit: cover; }
.food-card h3 { font-family: 'Playfair Display'; color: var(--accent-gold); margin: 20px 0 5px; font-size: 24px; }
.price-tag { color: var(--cream); font-weight: 600; margin-bottom: 10px; display: block; font-size: 18px; }
.food-card p.description { font-size: 13px; color: var(--text-muted); padding: 0 25px; line-height: 1.6; margin-bottom: 15px; flex-grow: 1; }

.food-details-list {
    text-align: left;
    background: rgba(0, 0, 0, 0.2);
    margin: 0 20px 20px 20px;
    padding: 15px;
    border-radius: 10px;
    border-left: 3px solid var(--accent-gold);
}

.detail-item { font-size: 12px; margin-bottom: 8px; line-height: 1.4; }
.detail-item b { color: var(--accent-gold); text-transform: uppercase; font-size: 10px; letter-spacing: 1px; display: block; margin-bottom: 2px; }

.btn-group { display: flex; justify-content: center; gap: 10px; padding: 0 20px; }
.btn { padding: 12px 20px; border-radius: 5px; text-decoration: none; font-size: 11px; font-weight: bold; text-transform: uppercase; flex: 1; transition: 0.3s; }
.ar-btn { background: #17a2b8; color: white; }
.add-cart-btn { background: var(--accent-gold); color: var(--dark-espresso); border: none; cursor: pointer; }
.add-cart-btn:hover { background: var(--cream); }

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

/* --- RESPONSIVE --- */
@media (max-width: 768px) {
    .menu-toggle { display: block; }
    nav { display: none; flex-direction: column; padding: 20px 0; }
    nav.active { display: flex; }
    .nav-left, .nav-right { flex-direction: column; gap: 15px; }
    .logo-circle { margin: 10px auto; order: -1; width: 80px; height: 80px; }
    .footer-container { grid-template-columns: 1fr; text-align: center; }
}
</style>
</head>
<body>

<header>
    <button class="menu-toggle" onclick="toggleMenu()">MENU</button>
    <h1>Carrie's Cafe Menu</h1>
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
            <img src="/food_ar_app/user/Lor.png" alt="Cafe Logo">
        </div>
        <div class="nav-right">
            <a href="/food_ar_app/user/order/orders.php">Orders</a>
            <a href="/food_ar_app/user/profile.php">Profile</a>
            <a href="/food_ar_app/user/order/cart.php">Cart</a>
        </div>
    </nav>
</div>

<div id="toast">Item added to cart!</div>

<div class="search-box">
    <form method="GET">
        <input type="text" name="search" placeholder="Search delicacies..." value="<?= htmlspecialchars($search) ?>">
        <select name="category">
            <option value="">All Categories</option>
            <?php 
            $cats = ['Pizza', 'Burger', 'Drinks', 'Salad', 'Sandwich', 'Dessert'];
            foreach($cats as $cat) {
                $sel = ($category == $cat) ? 'selected' : '';
                echo "<option value='$cat' $sel>$cat</option>";
            }
            ?>
        </select>
        <button type="submit">Filter</button>
    </form>
</div>

<div class="food-list">
<?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
    <div class="food-card">
        <img src="/food_ar_app3/<?= !empty($row['ImagePath']) ? htmlspecialchars($row['ImagePath']) : 'uploads/images/default_food.png' ?>" alt="Food">
        <h3><?= htmlspecialchars($row['FoodName']) ?></h3>
        <span class="price-tag">LKR <?= number_format($row['Price'], 2) ?></span>
        
        <p class="description"><?= htmlspecialchars($row['Description']) ?></p>

        <div class="food-details-list">
            <div class="detail-item">
                <b>Ingredients:</b> 
                <?= !empty($row['Ingredients']) ? htmlspecialchars($row['Ingredients']) : 'Fresh local ingredients' ?>
            </div>
            <div class="detail-item">
                <b>Portion:</b> 
                <?= !empty($row['PortionSize']) ? htmlspecialchars($row['PortionSize']) : 'Standard' ?>
            </div>
            <div class="detail-item">
                <b>Nutrition Details:</b> 
                <?= !empty($row['NutritionalInfo']) ? htmlspecialchars($row['NutritionalInfo']) : 'Healthy choice' ?>
            </div>
        </div>
        
        <div class="btn-group">
            <?php if(!empty($row['ARModelGLB'])): ?>
                <a href="/food_ar_app/user/menu/ar_view.php?menu_id=<?= $row['MenuItemID'] ?>" class="btn ar-btn">3D VIEW</a>
            <?php endif; ?>
    
            <form class="ajax-cart-form" style="flex:1; display:flex;">
                <input type="hidden" name="menu_id" value="<?= $row['MenuItemID'] ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn add-cart-btn">ADD TO CART</button>
            </form>
        </div>
    </div>
<?php } ?>
</div>

<footer class="footer">
    <div class="footer-container">
        <div>
            <h3>Visit Us</h3>
            <p style="font-size:13px;">📍 <?= $settings['Address'] ?? 'Location' ?></p>
        </div>
        <div>
            <h3><?= $settings['CafeName'] ?? 'Carrie\'s Cafe' ?></h3>
            <p style="font-size:13px; line-height:1.6;"><?= $settings['AboutText'] ?? '' ?></p>
        </div>
        <div>
            <h3>Service Hours</h3>
            <p style="font-size:13px;"><?= $settings['OpeningHours'] ?? '' ?></p>
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

document.querySelectorAll('.ajax-cart-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault(); 
        const formData = new FormData(this);

        fetch('../order/add_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            showToast("Success! Added to your cart.");
        })
        .catch(error => {
            console.error('Error:', error);
            showToast("Error adding to cart.");
        });
    });
});

function showToast(message) {
    const toast = document.getElementById("toast");
    toast.innerText = message;
    toast.className = "show";
    setTimeout(function(){ 
        toast.className = toast.className.replace("show", ""); 
    }, 3000);
}
</script>

</body>
</html>