<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['customer_id'])) {
    header("Location:./../user/index.php");
    exit();
}

$user_id = $_SESSION['customer_id'];

/* =========================
   FETCH USER DATA
========================= */
$sql = "SELECT FullName, Email, ContactNumber, Address, Username,
        Latitude, Longitude, ProfileImage
        FROM Users WHERE UserID = ?";

$stmt = sqlsrv_query($conn, $sql, [$user_id]);
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

/* =========================
   UPDATE PROFILE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullname = $_POST['fullname'];
    $contact  = $_POST['contact'];
    $address  = $_POST['address'];
    $lat      = $_POST['latitude'];
    $lng      = $_POST['longitude'];

    $imagePath = $user['ProfileImage'];

    /* IMAGE UPLOAD */
    if (!empty($_FILES['profile']['name'])) {
        $targetDir = "../uploads/";
        $filename = time() . "_" . basename($_FILES["profile"]["name"]);
        $targetFile = $targetDir . $filename;
        move_uploaded_file($_FILES["profile"]["tmp_name"], $targetFile);
        $imagePath = "uploads/" . $filename;
    }

    $updateSql = "UPDATE Users
                  SET FullName = ?, 
                      ContactNumber = ?, 
                      Address = ?, 
                      Latitude = ?, 
                      Longitude = ?, 
                      ProfileImage = ?, 
                      UpdatedAt = GETDATE()
                  WHERE UserID = ?";

    $params = [$fullname, $contact, $address, $lat, $lng, $imagePath, $user_id];
    sqlsrv_query($conn, $updateSql, $params);

    $_SESSION['success'] = "Profile updated successfully";
    header("Location: profile.php"); // Refresh to show new data
    exit();
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
<title>My Profile — Carrie's Cafe</title>
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
    display: flex; gap: 30px; align-items: center;
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

/* --- PROFILE CONTAINER --- */
.profile-wrapper {
    max-width: 800px;
    margin: 60px auto 100px;
    padding: 0 20px;
}

.profile-card {
    background: rgba(44, 27, 14, 0.6);
    border: 1px solid rgba(212, 163, 115, 0.1);
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.4);
}

.profile-header {
    text-align: center;
    margin-bottom: 40px;
}

.profile-img {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--accent-gold);
    box-shadow: 0 8px 20px rgba(0,0,0,0.5);
    margin-bottom: 20px;
}

.profile-card h2 {
    font-family: 'Playfair Display', serif;
    color: var(--accent-gold);
    font-size: 2rem;
    margin: 0;
}

/* --- FORM STYLING --- */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 20px;
}

.full-width { grid-column: span 2; }

label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--accent-gold);
    margin-bottom: 8px;
    font-weight: 600;
}

input {
    background: var(--dark-espresso);
    border: 1px solid rgba(212, 163, 115, 0.2);
    padding: 12px 15px;
    border-radius: 5px;
    color: var(--cream);
    font-family: 'Inter', sans-serif;
    transition: 0.3s;
}

input:focus {
    outline: none;
    border-color: var(--accent-gold);
    background: var(--deep-roast);
}

input.readonly {
    background: rgba(255,255,255,0.03);
    color: var(--text-muted);
    cursor: not-allowed;
    border-color: transparent;
}

.file-input {
    padding: 8px;
    font-size: 12px;
}

button.update-btn {
    grid-column: span 2;
    background: var(--accent-gold);
    color: var(--dark-espresso);
    padding: 16px;
    border: none;
    border-radius: 5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    cursor: pointer;
    transition: 0.3s;
    margin-top: 10px;
}

button.update-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(212, 163, 115, 0.3);
}

.alert-success {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
    padding: 15px;
    border-radius: 5px;
    text-align: center;
    margin-bottom: 25px;
    border: 1px solid #28a745;
    font-size: 14px;
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
    .form-grid { grid-template-columns: 1fr; }
    .full-width { grid-column: span 1; }
    button.update-btn { grid-column: span 1; }
    .nav-left, .nav-right { display: none; }
    .footer-container { grid-template-columns: 1fr; text-align: center; }
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
            <a href="./../user/dashboard/dashboard.php">Main</a>
            <a href="./../user/menu/menu.php">Menu</a>
            <a href="./../user/reservation/reserve.php">Reserve</a>
        </div>
        <div class="logo-circle">
            <img src="Lor.png" alt="Cafe Logo">
        </div>
        <div class="nav-right">
            <a href="./../user/order/orders.php">Orders</a>
            <a href="./../user/profile.php" style="color:var(--accent-gold)">Profile</a>
            <a href="./../user/order/cart.php">Cart</a>
        </div>
    </nav>
</div> 

<div class="profile-wrapper">
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert-success">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="profile-card">
        <div class="profile-header">
            <img src="../<?= $user['ProfileImage'] ?? '/food_ar_app/uploads/default.png' ?>" class="profile-img" alt="Profile">
            <h2>Account Details</h2>
        </div>

        <form method="POST" enctype="multipart/form-data" class="form-grid">
            <div class="form-group full-width">
                <label>Change Profile Picture</label>
                <input type="file" name="profile" class="file-input">
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" value="<?= htmlspecialchars($user['FullName']) ?>" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" value="<?= htmlspecialchars($user['Username']) ?>" readonly class="readonly">
            </div>

            <div class="form-group full-width">
                <label>Email Address</label>
                <input type="email" value="<?= htmlspecialchars($user['Email']) ?>" readonly class="readonly">
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact" value="<?= htmlspecialchars($user['ContactNumber']) ?>">
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($user['Address']) ?>">
            </div>

            <div class="form-group">
                <label>Latitude</label>
                <input type="text" name="latitude" value="<?= $user['Latitude'] ?>">
            </div>

            <div class="form-group">
                <label>Longitude</label>
                <input type="text" name="longitude" value="<?= $user['Longitude'] ?>">
            </div>

            <button type="submit" class="update-btn">Save Changes</button>
        </form>
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