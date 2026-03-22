<?php
session_start();
include('../../includes/db.php');

$order_id = $_GET['order_id'] ?? 0;
$amount = $_GET['amount'] ?? 0;

if(!$order_id || !$amount) {
    die("Invalid payment request. Please go back to your cart.");
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
    <title>Secure Payment — Carrie's Cafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --dark-espresso: #1e120a;
            --deep-roast: #2c1b0e;
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

        /* --- HEADER & NAV --- */
        header {
            background: var(--dark-espresso);
            border-bottom: 2px solid #3d2a1a;
            text-align: center;
            padding: 25px 0;
        }
        header h1 { font-family: 'Playfair Display', serif; font-size: 28px; color: var(--accent-gold); margin: 0; }

        .nav_container { background: var(--glass); backdrop-filter: blur(10px); padding: 10px 0; position: sticky; top: 0; z-index: 1000; }
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

        /* --- PAYMENT CONTENT --- */
        .main-content {
            flex: 1;
            padding: 60px 20px;
            display: flex;
            justify-content: center;
        }

        .payment-container {
            max-width: 450px;
            width: 100%;
            background: rgba(44, 27, 14, 0.6);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
            border: 1px solid rgba(212, 163, 115, 0.1);
        }

        h2 { font-family: 'Playfair Display', serif; color: var(--accent-gold); text-align: center; margin-top: 0; }

        .order-info {
            display: flex;
            justify-content: space-between;
            background: rgba(0,0,0,0.3);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px dashed var(--accent-gold);
            font-size: 14px;
        }

        /* Virtual Card Visual */
        .visual-card {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #4a3423 0%, #2c1b0e 100%);
            border-radius: 15px;
            margin-bottom: 25px;
            padding: 25px;
            box-sizing: border-box;
            position: relative;
            box-shadow: 0 10px 20px rgba(0,0,0,0.4);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .chip { width: 45px; height: 35px; background: #d4a373; border-radius: 5px; opacity: 0.8; }
        .card-number-display { font-size: 1.3rem; letter-spacing: 3px; font-family: 'Courier New', monospace; color: white; }
        .card-bottom { display: flex; justify-content: space-between; text-transform: uppercase; font-size: 0.75rem; }

        /* Form Styling */
        label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: var(--accent-gold); text-transform: uppercase; letter-spacing: 1px; }
        input { 
            width: 100%; padding: 12px; margin-bottom: 20px; 
            background: rgba(0,0,0,0.3); border: 1px solid #3d2a1a; 
            border-radius: 5px; color: white; box-sizing: border-box;
            transition: border-color 0.3s;
        }
        input:focus { outline: none; border-color: var(--accent-gold); }
        .row { display: flex; gap: 15px; }

        button.pay-btn {
            width: 100%; padding: 18px;
            background: var(--success-green); color: white;
            border: none; border-radius: 5px;
            font-size: 1rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 2px;
            cursor: pointer; transition: 0.3s; margin-top: 10px;
        }
        button.pay-btn:hover { background: #218838; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3); }

        .secure-note { text-align: center; font-size: 0.7rem; margin-top: 20px; color: var(--text-muted); letter-spacing: 1px; }

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
            <a href="/food_ar_app/user/order/cart.php" style="color:var(--accent-gold)">Cart</a>
        </div>
    </nav>
</div>

<div class="main-content">
    <div class="payment-container">
        <h2>Secure Checkout</h2>

        <div class="order-info">
            <span>Order #<?= htmlspecialchars($order_id) ?></span>
            <span style="font-weight:bold; color:var(--accent-gold);">LKR <?= number_format($amount, 2) ?></span>
        </div>

        <div class="visual-card">
            <div class="chip"></div>
            <div class="card-number-display" id="display_number">**** **** **** ****</div>
            <div class="card-bottom">
                <div>
                    <small style="opacity:0.6">Card Holder</small><br>
                    <span id="display_name" style="letter-spacing:1px;">YOUR NAME</span>
                </div>
                <div>
                    <small style="opacity:0.6">Expires</small><br>
                    <span id="display_expiry">MM/YY</span>
                </div>
            </div>
        </div>

        <form action="payment_success.php" method="POST">
            <input type="hidden" name="order_id" value="<?= $order_id ?>">

            <label>Cardholder Name</label>
            <input type="text" name="card_name" id="input_name" placeholder="Name on Card" required>

            <label>Card Number</label>
            <input type="text" name="card_number" id="input_number" placeholder="0000 0000 0000 0000" maxlength="19" required>

            <div class="row">
                <div style="flex:2;">
                    <label>Expiry Date</label>
                    <input type="text" name="expiry" id="input_expiry" placeholder="MM/YY" maxlength="5" required>
                </div>
                <div style="flex:1;">
                    <label>CVV</label>
                    <input type="password" name="cvv" placeholder="***" maxlength="3" required>
                </div>
            </div>

            <button type="submit" class="pay-btn">Complete Payment</button>
        </form>

        <div class="secure-note">
            🔒 256-BIT SSL ENCRYPTED TRANSACTION
        </div>
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

<script>
    const inputNumber = document.getElementById('input_number');
    const inputName = document.getElementById('input_name');
    const inputExpiry = document.getElementById('input_expiry');

    const displayName = document.getElementById('display_name');
    const displayNumber = document.getElementById('display_number');
    const displayExpiry = document.getElementById('display_expiry');

    inputNumber.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '');
        val = val.replace(/(.{4})/g, '$1 ').trim();
        e.target.value = val;
        displayNumber.textContent = val || "**** **** **** ****";
    });

    inputName.addEventListener('input', (e) => {
        displayName.textContent = e.target.value.toUpperCase() || "YOUR NAME";
    });

    inputExpiry.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '');
        if (val.length >= 2) val = val.substring(0,2) + '/' + val.substring(2,4);
        e.target.value = val;
        displayExpiry.textContent = val || "MM/YY";
    });
</script>

</body>
</html>