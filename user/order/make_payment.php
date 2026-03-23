<?php
session_start();
include('../../includes/db.php');

// 1. Safety Check: If session data is missing, redirect back to cart
if(!isset($_SESSION['checkout_data']) || !isset($_SESSION['customer_id'])){
    header("Location: /food_ar_app/user/order/cart.php");
    exit();
}

$checkout = $_SESSION['checkout_data'];
$cart = $_SESSION['cart'] ?? [];
$customer_id = $_SESSION['customer_id'];

// 2. Handle accidental COD landings
if($checkout['payment_type'] === 'Cash on Delivery'){
    header("Location: /food_ar_app/user/order/cart.php"); 
    exit();
}

// 3. Calculate Totals
$subtotal = 0;
foreach($cart as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}
$grand_total = $subtotal + floatval($checkout['delivery_fee']);

// 4. Process Confirmation
if(isset($_POST['confirm_payment'])){
    $sql_order = "INSERT INTO Orders (UserID, Status, TotalAmount, CreatedAt, UpdatedAt)
                  OUTPUT INSERTED.OrderID VALUES (?, 'Pending', ?, GETDATE(), GETDATE())";
    $stmt = sqlsrv_query($conn, $sql_order, [$customer_id, $grand_total]);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $order_id = $row['OrderID'];

    foreach($cart as $key => $item){
        $menu_id = (strpos($key, 'menu_') === 0) ? intval(str_replace('menu_', '', $key)) : null;
        $category = ($menu_id === null) ? $item['name'] : null;
        $cust = is_array($item['customization']) ? json_encode($item['customization']) : $item['customization'];

        $sql_item = "INSERT INTO OrderItems (OrderID, MenuItemID, Quantity, Customization, Price, Category) 
                     VALUES (?, ?, ?, ?, ?, ?)";
        sqlsrv_query($conn, $sql_item, [$order_id, $menu_id, $item['quantity'], $cust, $item['price'], $category]);
    }

    $sql_pay = "INSERT INTO Payments (OrderID, UserID, PaymentAmount, PaymentMethod, PaymentStatus, CreatedAt, UpdatedAt)
                VALUES (?, ?, ?, ?, 'Pending', GETDATE(), GETDATE())";
    sqlsrv_query($conn, $sql_pay, [$order_id, $customer_id, $grand_total, $checkout['payment_type']]);

    header("Location: /food_ar_app/user/order/payment_gateway.php?order_id=$order_id&amount=$grand_total");
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
    <title>Confirm Your Order — Carrie's Cafe</title>
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
        }

        /* --- HEADER & NAV (Dashboard Style) --- */
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

        /* --- MAIN CONTENT --- */
        .main-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 80px 20px;
        }

        .confirm-card {
            background: rgba(44, 27, 14, 0.6);
            border: 1px solid rgba(212, 163, 115, 0.1);
            padding: 40px;
            border-radius: 20px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }

        h2 { font-family: 'Playfair Display', serif; color: var(--accent-gold); margin-bottom: 30px; }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(212, 163, 115, 0.2);
            font-size: 22px;
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            color: var(--accent-gold);
        }

        .btn-confirm {
            background: var(--accent-gold);
            color: var(--dark-espresso);
            border: none;
            padding: 18px;
            width: 100%;
            border-radius: 5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            margin-top: 30px;
            transition: 0.3s;
        }

        .btn-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(212, 163, 115, 0.3);
        }

        .back-link {
            display: block;
            margin-top: 20px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
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
            <a href="/food_ar_app/user/order/cart.php" style="color:var(--accent-gold)">Cart</a>
        </div>
    </nav>
</div>

<div class="main-wrapper">
    <div class="confirm-card">
        <h2>Secure Checkout</h2>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 30px;">Review your final total before proceeding.</p>

        <div class="summary-row">
            <span>Subtotal</span>
            <span>LKR <?= number_format($subtotal, 2) ?></span>
        </div>

        <div class="summary-row">
            <span><?= $checkout['delivery_type'] ?> Fee</span>
            <span>LKR <?= number_format($checkout['delivery_fee'], 2) ?></span>
        </div>

        <div class="summary-row">
            <span>Payment Method</span>
            <span style="color: var(--cream);"><?= $checkout['payment_type'] ?></span>
        </div>

        <div class="total-row">
            <span>Grand Total</span>
            <span>LKR <?= number_format($grand_total, 2) ?></span>
        </div>

        <form method="POST">
            <button type="submit" name="confirm_payment" class="btn-confirm">
                Confirm & Pay
            </button>
        </form>

        <a href="cart.php" class="back-link">← Return to Cart</a>
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