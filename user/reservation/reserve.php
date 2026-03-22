<?php
session_start();
include('../../includes/db.php');

if(!isset($_SESSION['customer_id'])){
    header("Location: /food_ar_app/user/index.php");
    exit();
}

$user_id = $_SESSION['customer_id'];

// Fetch customer info for consistency
$sql_customer = "SELECT FullName FROM Users WHERE UserID = ?";
$stmt_customer = sqlsrv_query($conn, $sql_customer, [$user_id]);
$customer_info = sqlsrv_fetch_array($stmt_customer, SQLSRV_FETCH_ASSOC);

// Fetch tables and seats dynamically
$tables = [];
$sql = "SELECT t.TableID, t.TableName, s.SeatNumber 
        FROM CafeTables t
        LEFT JOIN TableSeats s ON t.TableID = s.TableID
        ORDER BY t.TableID, s.SeatID";
$stmt = sqlsrv_query($conn, $sql);

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $tables[$row['TableName']][] = $row['SeatNumber'];
}

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
<title>Reserve Your Table — <?php echo $settings['CafeName']; ?></title>
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
    transition: color 0.3s;
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

/* --- HERO AREA (Reservation Specific) --- */
.hero_area {
    text-align:center;
    padding:60px 20px;
}

.hero_area h2 {
    font-family:'Playfair Display', serif;
    font-size:3rem;
    color: var(--accent-gold);
    margin:0;
}

/* --- RESERVATION INTERFACE --- */
.legend {
    display:flex;
    justify-content:center;
    flex-wrap: wrap;
    gap:15px;
    margin-bottom: 30px;
}
.legend span {
    padding:8px 15px;
    border-radius:5px;
    font-weight:600;
    font-size:11px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.legend .available { background:#28a745; color:#fff; }
.legend .booked { background:#dc3545; color:#fff; }
.legend .mine-accepted { background:#007bff; color:#fff; }
.legend .mine-pending { background:#ffc107; color:#000; }

#reservation_date {
    padding:12px 20px;
    font-family: 'Inter', sans-serif;
    border-radius:5px;
    border:1px solid var(--accent-gold);
    background: var(--deep-roast);
    color: var(--cream);
    margin-bottom: 40px;
}

.tables-container {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(280px,1fr));
    gap:25px;
    max-width:1100px;
    margin:0 auto 50px auto;
    padding:0 20px;
}

.table-card {
    background: rgba(44, 27, 14, 0.7);
    border-radius:15px;
    border: 1px solid rgba(212, 163, 115, 0.1);
    padding:25px;
    text-align:center;
    transition: 0.3s;
}

.table-card:hover { border-color: var(--accent-gold); transform: translateY(-5px); }

.table-name {
    font-family:'Playfair Display', serif;
    font-size:1.4rem;
    color: var(--accent-gold);
    display:block;
    margin-bottom:15px;
}

.seat {
    width:45px; height:45px;
    line-height:45px;
    display:inline-block;
    margin:5px;
    border-radius:50%;
    cursor:pointer;
    font-weight:bold;
    font-size: 12px;
    transition:0.2s;
}
.seat.available { background:rgba(40, 167, 69, 0.2); border: 1px solid #28a745; color:#28a745; }
.seat.available:hover { background:#28a745; color:#fff; }
.seat.booked { background:rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; color:#dc3545; cursor:not-allowed; }
.seat.mine-accepted { background:#007bff; color:#fff; cursor:not-allowed; }
.seat.mine-pending { background:#ffc107; color:#000; cursor:not-allowed; }
.seat.selected { background: var(--accent-gold) !important; color: var(--dark-espresso) !important; transform:scale(1.1); box-shadow: 0 0 15px var(--accent-gold); }

#reserveBtn {
    background: var(--accent-gold);
    color: var(--dark-espresso);
    padding:14px 40px;
    border-radius:5px;
    font-weight:700;
    text-transform: uppercase;
    letter-spacing: 1px;
    border:none;
    cursor:pointer;
    transition:0.3s;
    margin-bottom: 80px;
}
#reserveBtn:hover { background: var(--cream); transform: scale(1.05); }

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

/* --- MOBILE RESPONSIVE --- */
@media (max-width: 768px) {
    .menu-toggle { display: block; }
    nav { display: none; flex-direction: column; padding: 20px 0; }
    nav.active { display: flex; }
    .nav-left, .nav-right { flex-direction: column; gap: 15px; }
    .logo-circle { margin: 10px 0; order: -1; width: 80px; height: 80px; }
    .hero_area h2 { font-size: 2.2rem; }
    .footer-container { grid-template-columns: 1fr; text-align: center; }
}
</style>
</head>
<body>

<header>
    <button class="menu-toggle" onclick="toggleMenu()">MENU</button>
    <h1><?php echo $settings['CafeName']; ?> Reservation</h1>
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

<div class="hero_area">
    <h2>Secure Your Table</h2>
    <p style="color:var(--text-muted); letter-spacing: 2px; text-transform: uppercase; font-size: 12px;">Choose your preferred date and atmosphere</p>
</div>

<div class="legend">
    <span class="available">Available</span>
    <span class="booked">Booked</span>
    <span class="mine-accepted">Accepted</span>
    <span class="mine-pending">Pending</span>
</div>

<div style="text-align:center; margin-bottom:30px;">
    <input type="datetime-local" id="reservation_date">
</div>

<div class="tables-container">
<?php foreach($tables as $table_name => $seats_arr): ?>
    <div class="table-card">
        <span class="table-name"><?= htmlspecialchars($table_name) ?></span>
        <div class="seats-wrapper">
        <?php foreach($seats_arr as $seat): ?>
            <div class="seat available" data-seat="<?= $seat ?>"><?= $seat ?></div>
        <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<div style="text-align:center;">
    <button id="reserveBtn">Confirm Reservation</button>
</div>

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

<script>
function toggleMenu() {
    document.getElementById("mainNav").classList.toggle("active");
}

const seats = document.querySelectorAll('.seat');
const reservationDateInput = document.getElementById('reservation_date');
const reserveBtn = document.getElementById('reserveBtn');
const userId = <?= $user_id ?>;
let selectedSeats = new Set();

// Seat click toggle
seats.forEach(seat => {
    seat.addEventListener('click', () => {
        if(seat.classList.contains('booked') || seat.classList.contains('mine-accepted') || seat.classList.contains('mine-pending')) return;

        if(seat.classList.contains('selected')){
            seat.classList.remove('selected');
            selectedSeats.delete(seat.dataset.seat);
        } else {
            seat.classList.add('selected');
            selectedSeats.add(seat.dataset.seat);
        }
    });
});

async function loadReservations(){
    const dateVal = reservationDateInput.value;
    if(!dateVal) return;

    const resp = await fetch('customer_fetch_reservations.php?date='+encodeURIComponent(dateVal));
    const data = await resp.json();

    seats.forEach(seat => {
        seat.classList.remove('available','booked','mine-accepted','mine-pending','selected');
        const key = seat.dataset.seat;

        if(data[key]){
            const res = data[key];
            if(res.UserID == userId){
                seat.classList.add(res.Status=='Accepted' ? 'mine-accepted' : 'mine-pending');
            } else {
                seat.classList.add('booked');
            }
        } else {
            seat.classList.add('available');
            if(selectedSeats.has(key)) seat.classList.add('selected');
        }
    });
}

reserveBtn.addEventListener('click', async () => {
    if(selectedSeats.size === 0){ alert('Select at least one seat!'); return; }
    const dateVal = reservationDateInput.value;
    if(!dateVal){ alert('Select date/time!'); return; }

    const formData = new FormData();
    formData.append('date', dateVal);
    selectedSeats.forEach(seat => formData.append('seats[]', seat));

    const resp = await fetch('customer_reserve_seat.php',{ method:'POST', body: formData });
    const result = await resp.text();
    if(result.trim()==='OK'){
        alert('Reservation(s) submitted! Please wait for approval.');
        selectedSeats.clear();
        loadReservations();
    } else alert(result);
});

reservationDateInput.addEventListener('change', loadReservations);
setInterval(loadReservations, 10000);
window.addEventListener('load', loadReservations);
</script>

</body>
</html>