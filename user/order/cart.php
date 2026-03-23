<?php
session_start();
include('../../includes/db.php');

if(!isset($_SESSION['customer_id'])){
    header("Location: /food_ar_app/user/index.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$cart = $_SESSION['cart'] ?? [];
$empty_cart = empty($cart);

// 1. Get Customer Location
$sql_user = "SELECT Latitude, Longitude FROM Users WHERE UserID=?";
$stmt_user = sqlsrv_query($conn, $sql_user, [$customer_id]);
$user = sqlsrv_fetch_array($stmt_user, SQLSRV_FETCH_ASSOC);

$user_lat = $user['Latitude'] ?? null;
$user_lon = $user['Longitude'] ?? null;

// Cafe Location (Fixed)
$cafe_lat = 6.9271;
$cafe_lon = 79.8612;

function calculateDistance($lat1,$lon1,$lat2,$lon2){
    $earth_radius = 6371;
    $dLat = deg2rad($lat2-$lat1);
    $dLon = deg2rad($lon2-$lon1);
    $a = sin($dLat/2)*sin($dLat/2) +
         cos(deg2rad($lat1))*cos(deg2rad($lat2))*
         sin($dLon/2)*sin($dLon/2);
    $c = 2*atan2(sqrt($a),sqrt(1-$a));
    return $earth_radius*$c;
}

$delivery_fee = 0;
if($user_lat && $user_lon){
    $distance = calculateDistance($cafe_lat,$cafe_lon,$user_lat,$user_lon);
    $rate_per_km = 50;
    $delivery_fee = round($distance * $rate_per_km,2);
}

// Remove item
if(isset($_GET['remove'])){
    $remove_id = $_GET['remove'];
    if(isset($_SESSION['cart'][$remove_id])){
        unset($_SESSION['cart'][$remove_id]);
        header("Location: cart.php");
        exit();
    }
}

$total = 0;
foreach($cart as $item) $total += floatval($item['price']) * intval($item['quantity']);

/* ---------------- PLACE ORDER LOGIC ---------------- */
if(isset($_POST['place_order']) && !$empty_cart){
    $payment_type = $_POST['payment_type'] ?? '';
    $delivery_type = $_POST['delivery_type'] ?? '';
    
    $final_delivery_fee = ($delivery_type === 'Delivery') ? floatval($_POST['applied_delivery_fee'] ?? $delivery_fee) : 0;
    $grand_total = $total + $final_delivery_fee;

    if(!$delivery_type || !$payment_type){
        echo "<script>alert('Please select delivery and payment type');</script>";
    } 
    elseif($payment_type === 'Cash on Delivery') {
        $sql_order = "INSERT INTO Orders (UserID, Status, TotalAmount, CreatedAt, UpdatedAt) 
                      OUTPUT INSERTED.OrderID VALUES (?, 'Pending', ?, GETDATE(), GETDATE())";
        $stmt = sqlsrv_query($conn, $sql_order, [$customer_id, $grand_total]);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $order_id = $row['OrderID'];

        $msg = "New order placed. Order ID: " . $order_id;
        $sql_notify = "INSERT INTO Notifications (UserID, Message, TargetRole, NotificationType) VALUES (1, ?, 'Admin', 'Order')";
        sqlsrv_query($conn, $sql_notify, [$msg]);

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
        sqlsrv_query($conn, $sql_pay, [$order_id, $customer_id, $grand_total, 'Cash on Delivery']);

        unset($_SESSION['cart']);
        header("Location: /food_ar_app/user/order/order_success.php?order_id=$order_id&method=cod");
        exit();
    } 
    else {
        $_SESSION['checkout_data'] = [
            'delivery_type' => $delivery_type,
            'payment_type' => $payment_type,
            'delivery_fee' => $final_delivery_fee,
            'total' => $total
        ];
        header("Location: /food_ar_app/user/order/make_payment.php");
        exit();
    }
}

$sql_settings = "SELECT TOP 1 * FROM CafeSettings";
$stmt_settings = sqlsrv_query($conn, $sql_settings);
$settings = ($stmt_settings) ? sqlsrv_fetch_array($stmt_settings, SQLSRV_FETCH_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Basket — Carrie's Cafe</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
<style>
:root{
    --dark-espresso: #1e120a;
    --deep-roast: #2c1b0e;
    --accent-gold: #d4a373;
    --cream: #fdfaf8;
    --text-muted: #b09c89;
    --glass: rgba(44, 27, 14, 0.95);
}

body{
    margin:0;
    font-family:'Inter', sans-serif;
    background:#120a06 url('https://www.transparenttextures.com/patterns/dark-leather.png');
    color: var(--cream);
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

header {
    background: var(--dark-espresso);
    border-bottom: 2px solid #3d2a1a;
    box-shadow: 0 4px 10px rgba(0,0,0,0.5);
    text-align: center;
    padding: 25px 0;
    position: relative;
}
header h1 { font-family: 'Playfair Display', serif; font-size: 28px; color: var(--accent-gold); margin: 0; }

.nav_container { background: var(--glass); backdrop-filter: blur(10px); padding: 10px 0; border-bottom: 1px solid rgba(212, 163, 115, 0.1); }
nav { max-width: 1000px; margin: auto; display: flex; justify-content: center; align-items: center; gap: 20px; }
.nav-left, .nav-right { display: flex; gap: 30px; align-items: center; }
nav a { color: var(--cream); text-decoration: none; text-transform: uppercase; font-size: 11px; letter-spacing: 2px; font-weight: 600; }
.logo-circle { width: 110px; height: 110px; background: var(--dark-espresso); border-radius: 50%; border: 3px solid var(--accent-gold); display: flex; justify-content: center; align-items: center; margin: -35px 20px; z-index: 100; box-shadow: 0 10px 25px rgba(0,0,0,0.5); flex-shrink: 0; overflow: hidden; }
.logo-circle img { width: 100%; height: 100%; object-fit: cover; }

.container { max-width: 1000px; margin: 60px auto 100px; padding: 0 20px; flex: 1; }
.cart-card { background: rgba(44, 27, 14, 0.6); border: 1px solid rgba(212, 163, 115, 0.1); border-radius: 15px; padding: 30px; backdrop-filter: blur(10px); }

table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
th { text-align: left; font-size: 11px; text-transform: uppercase; color: var(--accent-gold); letter-spacing: 1.5px; padding-bottom: 15px; border-bottom: 1px solid rgba(212, 163, 115, 0.1); }
td { padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }

.qty-input { background: var(--dark-espresso); border: 1px solid var(--accent-gold); color: white; border-radius: 5px; padding: 5px; width: 60px; text-align: center; }
.remove-link { color: #ff6b6b; text-decoration: none; font-size: 12px; font-weight: 600; text-transform: uppercase; }

.checkout-section { display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px; margin-top: 20px; }
.options-group { background: rgba(0,0,0,0.2); padding: 20px; border-radius: 10px; margin-bottom: 15px; }
.options-group h4 { margin-top: 0; color: var(--accent-gold); font-family: 'Playfair Display'; }

.total-box { text-align: right; }
.total-box h2 { font-family: 'Playfair Display'; font-size: 2.5rem; color: var(--accent-gold); margin: 10px 0; }

.btn-place { background: var(--accent-gold); color: var(--dark-espresso); border: none; padding: 15px 40px; border-radius: 5px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; cursor: pointer; width: 100%; transition: 0.3s; text-decoration: none; display: inline-block; text-align: center; }
.btn-place:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(212, 163, 115, 0.3); }

.hidden { display: none; }

/* --- FOOTER --- */
.footer { background: var(--dark-espresso); border-top: 2px solid #3d2a1a; padding: 50px 0 20px; margin-top: auto; text-align: center; }
.footer-container { max-width: 1000px; margin: auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; padding: 0 20px; text-align: left; }
.footer-bottom { margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); color: var(--text-muted); font-size: 12px; }
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

<div class="container">
<?php if($empty_cart): ?>
    <div class="cart-card" style="text-align:center; padding:80px;">
        <h2 style="font-family:'Playfair Display';">Your basket is empty</h2>
        <p style="color:var(--text-muted); margin-bottom: 30px;">It seems you haven't added any treats yet.</p>
        <a href="/food_ar_app/user/menu/menu.php" class="btn-place" style="width:auto;">Browse Menu</a>
    </div>
<?php else: ?>
    <div class="cart-card">
        <h2 style="font-family:'Playfair Display'; color:var(--accent-gold); margin-bottom:30px;">Review Your Selection</h2>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Customization</th>
                    <th>Price</th>
                    <th style="text-align:center;">Qty</th>
                    <th>Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($cart as $key=>$item): 
                    $subtotal = floatval($item['price'])*intval($item['quantity']);
                ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($item['name']??'Unknown Item') ?></td>
                    <td style="font-size:12px; color:var(--text-muted);">
                        <?php
                            if(!empty($item['customization'])){
                                $cust = is_string($item['customization'])? json_decode($item['customization'],true) : $item['customization'];
                                if(is_array($cust)){
                                    $flat=[];
                                    foreach($cust as $v) $flat = array_merge($flat,is_array($v)?$v:[$v]);
                                    echo implode(", ",$flat);
                                } else { echo "Standard"; }
                            } else { echo "Standard"; }
                        ?>
                    </td>
                    <td>LKR <?= number_format($item['price'],2) ?></td>
                    <td style="text-align:center;">
                        <input type="number" min="1" class="qty-input" value="<?= intval($item['quantity']) ?>" onchange="updateQuantity('<?= $key ?>', this)">
                    </td>
                    <td style="font-weight:600;">LKR <?= number_format($subtotal,2) ?></td>
                    <td style="text-align:right;"><a href="cart.php?remove=<?= urlencode($key) ?>" class="remove-link">Remove</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form method="POST" id="orderForm">
            <input type="hidden" name="applied_delivery_fee" id="appliedFeeInput" value="<?= $delivery_fee ?>">
            
            <div class="checkout-section">
                <div class="options-wrapper">
                    <div class="options-group">
                        <h4>Fulfillment</h4>
                        <label style="display:block; margin-bottom:12px; cursor:pointer;">
                            <input type="radio" name="delivery_type" value="Takeaway" onclick="updateTotal()" required> Takeaway (Free)
                        </label>
                        <label style="display:block; cursor:pointer;">
                            <input type="radio" name="delivery_type" value="Delivery" onclick="findMyLocation()"> 
                            <span id="deliveryLabel">Delivery (+LKR <?= number_format($delivery_fee, 2) ?>)</span>
                        </label>
                    </div>

                    <div class="options-group hidden" id="paymentOptions">
                        <h4>Payment Method</h4>
                        <label style="display:block; margin-bottom:12px; cursor:pointer;">
                            <input type="radio" name="payment_type" value="Online Payment" required> Secure Online Payment
                        </label>
                        <label style="display:block; cursor:pointer;">
                            <input type="radio" name="payment_type" value="Cash on Delivery"> Cash on Delivery
                        </label>
                    </div>
                </div>

                <div class="total-box">
                    <p style="color:var(--text-muted); text-transform:uppercase; letter-spacing:2px; font-size:12px; margin:0;">Grand Total</p>
                    <h2 id="totalPrice">LKR <?= number_format($total,2) ?></h2>
                    <button type="submit" name="place_order" class="btn-place">Finalize Order</button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>
</div>

<footer class="footer">
    <div class="footer-container">
        <div>
            <h3 style="color:var(--accent-gold); font-family:'Playfair Display';">Visit Us</h3>
            <p style="font-size:13px;">📍 <?= $settings['Address'] ?? '123 Bakery Lane, Colombo'; ?></p>
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
let deliveryFee = <?= $delivery_fee ?>;
const totalElem = document.getElementById('totalPrice');
const paymentDiv = document.getElementById('paymentOptions');
const appliedFeeInput = document.getElementById('appliedFeeInput');

// Store unit prices from PHP for JS recalculations
const unitPrices = {
    <?php foreach($cart as $key=>$item): ?>
    "<?= $key ?>": <?= floatval($item['price']) ?>,
    <?php endforeach; ?>
};

function updateQuantity(key, inputElem){
    let qty = parseInt(inputElem.value);
    if(qty < 1){ qty = 1; inputElem.value = 1; }
    
    // Update Subtotal for the row visually
    let row = inputElem.closest('tr');
    let newSubtotal = unitPrices[key] * qty;
    row.querySelector('td:nth-child(5)').textContent = 'LKR ' + newSubtotal.toLocaleString(undefined, {minimumFractionDigits: 2});
    
    updateTotal();
}

function updateTotal(){
    let newTotal = 0;
    document.querySelectorAll('tbody tr').forEach(tr => {
        // Strip out "LKR " and any commas to calculate
        let subVal = tr.querySelector('td:nth-child(5)').textContent.replace('LKR ', '').replace(',', '');
        newTotal += parseFloat(subVal) || 0;
    });

    const deliveryInput = document.querySelector('input[name="delivery_type"]:checked');
    if(deliveryInput){
        if(deliveryInput.value === 'Delivery') newTotal += deliveryFee;
        paymentDiv.classList.remove('hidden');
    }

    totalElem.textContent = 'LKR ' + newTotal.toLocaleString(undefined, {minimumFractionDigits: 2});
}

function findMyLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((position) => {
            fetch(`get_delivery_fee.php?lat=${position.coords.latitude}&lon=${position.coords.longitude}`)
                .then(res => res.text())
                .then(newFee => {
                    deliveryFee = parseFloat(newFee);
                    appliedFeeInput.value = deliveryFee;
                    document.getElementById('deliveryLabel').textContent = `Delivery (+LKR ${deliveryFee.toFixed(2)})`;
                    updateTotal();
                });
        }, () => alert("Location access denied."));
    }
    updateTotal();
}
</script>

</body>
</html>