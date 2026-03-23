<?php
session_start();

// Protect page: Admin only
if(!isset($_SESSION['admin_id'])){
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

include('C:/xampp/htdocs/food_ar_app/includes/db.php');

// Check if ID is passed
if(!isset($_GET['id']) || empty($_GET['id'])){
    header("Location: /food_ar_app/admin/dashboard.php");
    exit();
}

$menuItemID = (int) $_GET['id'];

// --------- FETCH FILE PATHS BEFORE DELETE ----------
$sqlCheck = "SELECT ImagePath, ARModelPath FROM MenuItems WHERE MenuItemID = ?";
$stmtCheck = sqlsrv_query($conn, $sqlCheck, [$menuItemID]);
if($stmtCheck === false) die(print_r(sqlsrv_errors(), true));

$row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
if(!$row){
    header("Location: /food_ar_app/admin/dashboard.php");
    exit();
}

// --------- DELETE FILES ----------
if(!empty($row['ImagePath'])){
    $imgFile = "food_ar_app/".$row['ImagePath'];
    if(file_exists($imgFile)){
        unlink($imgFile);
    }
}

if(!empty($row['ARModelPath'])){
    $arFile = "food_ar_app/".$row['ARModelPath'];
    if(file_exists($arFile)){
        unlink($arFile);
    }
}

// --------- DELETE RELATED CHILD RECORDS ----------

// 1. AR Models
$sqlDeleteAR = "DELETE FROM AR_Models WHERE MenuItemID = ?";
$stmtDeleteAR = sqlsrv_query($conn, $sqlDeleteAR, [$menuItemID]);
if($stmtDeleteAR === false) die(print_r(sqlsrv_errors(), true));

// 2. Order Items
$sqlDeleteItems = "DELETE FROM OrderItems WHERE MenuItemID = ?";
$stmtDeleteItems = sqlsrv_query($conn, $sqlDeleteItems, [$menuItemID]);
if($stmtDeleteItems === false) die(print_r(sqlsrv_errors(), true));

// 3. Customizations
$sqlDeleteCustom = "DELETE FROM Customizations WHERE MenuItemID = ?";
$stmtDeleteCustom = sqlsrv_query($conn, $sqlDeleteCustom, [$menuItemID]);
if($stmtDeleteCustom === false) die(print_r(sqlsrv_errors(), true));

// --------- DELETE MENU ITEM ----------
$sqlDeleteMenu = "DELETE FROM MenuItems WHERE MenuItemID = ?";
$stmtDeleteMenu = sqlsrv_query($conn, $sqlDeleteMenu, [$menuItemID]);
if($stmtDeleteMenu === false) die(print_r(sqlsrv_errors(), true));

// Redirect back to dashboard
header("Location: /food_ar_app/admin/dashboard.php");
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Delete Food — Carrie's Cafe & Bakery</title>  <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.3.0/model-viewer.min.js"></script>
<style>

/* =============================================
   Carrie's Cafe & Bakery — Global Styles
   ============================================= */
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --primary:   #c0392b;
  --accent:    #e67e22;
  --bg:        #fff8f2;
  --sidebar:   #2c1a0e;
  --text:      #3b2314;
  --card:      #ffffff;
  --border:    #f0ddd0;
  --success:   #27ae60;
  --warning:   #f39c12;
  --danger:    #e74c3c;
  --info:      #2980b9;
  --radius:    12px;
  --shadow:    0 4px 20px rgba(44,26,14,.10);
}

body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
}

/* ── Sidebar ── */
.sidebar {
  width: 260px;
  min-height: 100vh;
  background: var(--sidebar);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0;
  z-index: 100;
  padding-bottom: 24px;
}
.sidebar-logo {
  padding: 28px 24px 20px;
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.sidebar-logo .brand {
  font-family: 'Playfair Display', serif;
  color: #fff;
  font-size: 1.18rem;
  line-height: 1.3;
}
.sidebar-logo .sub {
  color: var(--accent);
  font-size: .72rem;
  letter-spacing: .06em;
  text-transform: uppercase;
  margin-top: 4px;
}
.sidebar nav { flex: 1; padding: 16px 0; }
.nav-section {
  color: rgba(255,255,255,.35);
  font-size: .68rem;
  letter-spacing: .1em;
  text-transform: uppercase;
  padding: 14px 24px 6px;
}
.sidebar nav a {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 24px;
  color: rgba(255,255,255,.72);
  text-decoration: none;
  font-size: .875rem;
  transition: all .2s;
  border-left: 3px solid transparent;
}
.sidebar nav a:hover,
.sidebar nav a.active {
  background: rgba(255,255,255,.08);
  color: #fff;
  border-left-color: var(--accent);
}
.sidebar nav a .icon { font-size: 1.1rem; width: 20px; text-align: center; }

/* ── Main content ── */
.main {
  margin-left: 260px;
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}
.topbar {
  background: var(--card);
  border-bottom: 1px solid var(--border);
  padding: 16px 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 50;
}
.topbar .page-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.4rem;
  color: var(--text);
}
.topbar .user-pill {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 40px;
  padding: 6px 16px 6px 8px;
  font-size: .85rem;
}
.avatar {
  width: 32px; height: 32px;
  background: var(--primary);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-weight: 600; font-size: .85rem;
}
.content { padding: 32px; flex: 1; }

/* ── Card ── */
.card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 28px;
  margin-bottom: 24px;
}
.card-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.1rem;
  margin-bottom: 20px;
  color: var(--text);
  display: flex; align-items: center; gap: 10px;
}

/* ── Form elements ── */
.form-group { margin-bottom: 20px; }
label {
  display: block;
  font-size: .82rem;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 7px;
  text-transform: uppercase;
  letter-spacing: .04em;
}
input, select, textarea {
  width: 100%;
  padding: 11px 14px;
  border: 1.5px solid var(--border);
  border-radius: 8px;
  font-family: 'Inter', sans-serif;
  font-size: .9rem;
  color: var(--text);
  background: #fff;
  transition: border-color .2s, box-shadow .2s;
  outline: none;
}
input:focus, select:focus, textarea:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(192,57,43,.12);
}
textarea { resize: vertical; min-height: 100px; }

/* ── Buttons ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border-radius: 8px;
  font-size: .875rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all .2s;
  text-decoration: none;
}
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: #a93226; transform: translateY(-1px); }
.btn-accent  { background: var(--accent);  color: #fff; }
.btn-accent:hover  { background: #d35400; }
.btn-success { background: var(--success); color: #fff; }
.btn-danger  { background: var(--danger);  color: #fff; }
.btn-info    { background: var(--info);    color: #fff; }
.btn-outline {
  background: transparent;
  border: 1.5px solid var(--primary);
  color: var(--primary);
}
.btn-outline:hover { background: var(--primary); color: #fff; }
.btn-sm { padding: 6px 14px; font-size: .8rem; }

/* ── Table ── */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: .875rem; }
thead th {
  background: var(--bg);
  color: var(--text);
  font-weight: 600;
  padding: 12px 14px;
  text-align: left;
  border-bottom: 2px solid var(--border);
  font-size: .78rem;
  text-transform: uppercase;
  letter-spacing: .05em;
}
tbody td {
  padding: 13px 14px;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
tbody tr:hover { background: #fdf6f0; }

/* ── Badges ── */
.badge {
  display: inline-block;
  padding: 3px 11px;
  border-radius: 20px;
  font-size: .75rem;
  font-weight: 600;
}
.badge-success  { background: #d5f5e3; color: #1e8449; }
.badge-warning  { background: #fef9e7; color: #b7770d; }
.badge-danger   { background: #fde8e8; color: #c0392b; }
.badge-info     { background: #d6eaf8; color: #1a5276; }
.badge-neutral  { background: #f0f0f0; color: #555; }

/* ── Upload zone ── */
.upload-zone {
  border: 2px dashed var(--border);
  border-radius: var(--radius);
  padding: 36px 24px;
  text-align: center;
  cursor: pointer;
  transition: border-color .2s, background .2s;
}
.upload-zone:hover {
  border-color: var(--primary);
  background: #fdf0ec;
}
.upload-zone .icon { font-size: 2.2rem; margin-bottom: 10px; }
.upload-zone p { color: #888; font-size: .875rem; }
.upload-zone strong { color: var(--primary); cursor: pointer; }

/* ── Grid helpers ── */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; }
.grid-4 { display: grid; grid-template-columns: repeat(4,1fr); gap: 20px; }

/* ── Stat card ── */
.stat-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 22px 24px;
  box-shadow: var(--shadow);
}
.stat-card .label { font-size: .78rem; color: #999; text-transform: uppercase; letter-spacing: .06em; }
.stat-card .value { font-family: 'Playfair Display', serif; font-size: 2rem; color: var(--text); margin: 6px 0 2px; }
.stat-card .change { font-size: .8rem; color: var(--success); }

/* ── Floor map ── */
.floor-map {
  display: grid;
  grid-template-columns: repeat(4, 100px);
  gap: 16px;
  padding: 24px;
  background: #f5ece4;
  border-radius: var(--radius);
}
.table-node {
  width: 90px; height: 90px;
  border-radius: 50%;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  font-size: .78rem; font-weight: 600;
  cursor: pointer; transition: transform .15s;
  border: 3px solid transparent;
}
.table-node:hover { transform: scale(1.07); }
.table-node.available  { background: #d5f5e3; border-color: #2ecc71; color: #1a6b3c; }
.table-node.occupied   { background: #fde8e8; border-color: #e74c3c; color: #922b21; }
.table-node.pending    { background: #fef9e7; border-color: #f39c12; color: #9a6209; }
.table-node.confirmed  { background: #d6eaf8; border-color: #2980b9; color: #1a5276; }
.table-node.rect { border-radius: 10px; }

/* ── 3D Model viewer placeholder ── */
.model-viewer-box {
  width: 100%;
  height: 280px;
  background: linear-gradient(135deg, #2c1a0e 0%, #4a2c1a 100%);
  border-radius: var(--radius);
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  color: rgba(255,255,255,.7);
  font-size: .875rem;
  border: 2px dashed rgba(255,255,255,.2);
  position: relative;
  overflow: hidden;
}
.model-viewer-box .orbit-ring {
  position: absolute;
  border-radius: 50%;
  border: 1.5px solid rgba(230,126,34,.3);
  animation: spin 8s linear infinite;
}
.model-viewer-box .orbit-ring:nth-child(1) { width: 180px; height: 80px; }
.model-viewer-box .orbit-ring:nth-child(2) { width: 120px; height: 120px; animation-duration: 5s; animation-direction: reverse; }
.model-viewer-box .model-icon { font-size: 3rem; z-index: 1; }
.model-viewer-box p { z-index: 1; margin-top: 10px; }
@keyframes spin { from { transform: rotateX(70deg) rotate(0deg); } to { transform: rotateX(70deg) rotate(360deg); } }

/* ── Responsive ── */
@media (max-width: 900px) {
  .sidebar { width: 200px; }
  .main { margin-left: 200px; }
  .grid-4 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
  .sidebar { display: none; }
  .main { margin-left: 0; }
  .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
}


/* ── Page-specific styles ── */
/* delete_food.css — Carrie's Cafe & Bakery */
.filter-bar { display:flex; gap:12px; flex-wrap:wrap; }

</style>
</head>
<body>
<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="/food_ar_app/admin/Lor.png" alt="Carrie's Cafe & Bakery">
    <div class="sub">Admin Panel</div>

  <nav>
    <div class="nav-section">Dashboard</div>
    <a href="/food_ar_app/admin/dashboard.php" class="active"><span class="icon">📊</span>Dashboard</a>

    <div class="nav-section">Foods</div>
<a href="/food_ar_app/admin/menu/menu_manage.php"><span class="icon">🍽️</span>Foods</a>

    <div class="nav-section">Payment</div>
  <a href="/food_ar_app/admin/payment/view_payments.php"><span class="icon">💳</span>Payment History</a>

    <div class="nav-section">Reservation</div>
    <a href="/food_ar_app/admin/reservation/reservation_manage.php"><span class="icon">📋</span>Reservations</a>

    <div class="nav-section">Orders</div>
  <a href="/food_ar_app/admin/order/view_orders.php"><span class="icon">📦</span>Orders</a>

    <div class="nav-section">FEEDBACK</div>
 <a href="/food_ar_app/admin/feedback/feedback_manage.php"><span class="icon">💬</span>Feedback</a>

  </nav>
</aside>
<div class="main">
  <div class="topbar">
    <span class="page-title">Delete Food</span>
    <div>
      <a href="/food_ar_app/admin/index.php" class="user-pill" style="text-decoration:none; color:inherit;" >
      <div class="avatar" >
        <?php echo strtoupper(substr($admin_name,0,2)); ?>
      </div>
      <span class="icon">🔐</span>Logout</a>
        </a>
    </div>
  </div>
  <div class="content">
    <div class="card">
      <div class="card-title">🗑️ Delete Food Item</div>
      <p style="color:#888;font-size:.875rem;margin-bottom:20px">Permanently removes the menu item and its linked AR model reference.</p>
      <div class="filter-bar">
        <input type="text" placeholder="🔍 Search food name…" style="flex:1"/>
        <select style="width:180px">
          <option>All Categories</option>
          <option>Starters</option>
          <option>Main Course</option>
          <option>Desserts</option>
          <option>Beverages</option>
          <option>Bakery</option>
        </select>
      </div>
    </div>
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>AR Model</th><th>Action</th></tr></thead>
          <tbody>
            <tr><td>MN-001</td><td>Grilled Salmon</td><td>Main Course</td><td>LKR 1,850</td><td><span class="badge badge-success">✓ Linked</span></td><td><button class="btn btn-danger btn-sm">🗑️ Delete</button></td></tr>
            <tr><td>MN-002</td><td>Chocolate Lava Cake</td><td>Desserts</td><td>LKR 650</td><td><span class="badge badge-success">✓ Linked</span></td><td><button class="btn btn-danger btn-sm">🗑️ Delete</button></td></tr>
            <tr><td>MN-003</td><td>Caesar Salad</td><td>Starters</td><td>LKR 750</td><td><span class="badge badge-neutral">No Model</span></td><td><button class="btn btn-danger btn-sm">🗑️ Delete</button></td></tr>
            <tr><td>MN-004</td><td>Mango Smoothie</td><td>Beverages</td><td>LKR 450</td><td><span class="badge badge-neutral">No Model</span></td><td><button class="btn btn-danger btn-sm">🗑️ Delete</button></td></tr>
            <tr><td>MN-005</td><td>Beef Burger</td><td>Main Course</td><td>LKR 1,200</td><td><span class="badge badge-success">✓ Linked</span></td><td><button class="btn btn-danger btn-sm">🗑️ Delete</button></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>