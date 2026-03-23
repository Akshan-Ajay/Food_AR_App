<?php
session_start();
include('../../includes/db.php');

if(!isset($_SESSION['customer_id'])){
     header("Location: /food_ar_app/user/index.php");
    exit();
}

/* ---------- GET CATEGORY FROM URL ---------- */
$category = isset($_GET['category']) ? $_GET['category'] : '';
if(!$category){
    die("No category selected.");
}

/* ---------- FETCH CATEGORY 3D MODEL + BASE PRICE ---------- */
$sqlModel = "SELECT BasePrice, ModelGLB, ModelUSDZ FROM CategoryARModels WHERE Category = ?";
$paramsModel = [$category];
$stmtModel = sqlsrv_query($conn, $sqlModel, $paramsModel);
$model = sqlsrv_fetch_array($stmtModel, SQLSRV_FETCH_ASSOC);

$basePrice = $model['BasePrice'] ?? 0;

// Convert Windows paths to web paths
$glb = $model['ModelGLB'] ? "/food_ar_app/uploads/models/" . basename($model['ModelGLB']) : '';
$usdz = $model['ModelUSDZ'] ? "/food_ar_app/uploads/models/" . basename($model['ModelUSDZ']) : '';

/* ---------- FETCH CUSTOMIZATIONS FOR CATEGORY ---------- */
$sqlCustom = "SELECT * FROM Customizations WHERE Category = ?";
$paramsCustom = [$category];
$stmtCustom = sqlsrv_query($conn, $sqlCustom, $paramsCustom);
$customizations = [];
while($row = sqlsrv_fetch_array($stmtCustom, SQLSRV_FETCH_ASSOC)){
    $row['ModelGLB'] = $row['ModelGLB'] ? "/food_ar_app/uploads/customizations/" . basename($row['ModelGLB']) : $glb;
    $row['ModelUSDZ'] = $row['ModelUSDZ'] ? "/food_ar_app/uploads/customizations/" . basename($row['ModelUSDZ']) : $usdz;
    $customizations[] = $row;
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
    <title><?= htmlspecialchars($category) ?> Customize — Carrie's Cafe</title>
    <script type="module" src="https://cdn.jsdelivr.net/npm/@google/model-viewer/dist/model-viewer.min.js"></script>
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
            position: absolute; right: 30px; top: 50%; transform: translateY(-50%); 
            color: #ff6b6b; text-decoration: none; font-size: 11px; font-weight: 600; 
            text-transform: uppercase; border: 1px solid rgba(255,107,107,0.2); 
            padding: 5px 12px; border-radius: 4px;
        }

        .menu-toggle {
            display: none; background: none; border: 1px solid var(--accent-gold);
            color: var(--accent-gold); padding: 5px 12px; cursor: pointer;
            position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
            font-size: 10px;
        }

        .nav_container { background: var(--glass); backdrop-filter: blur(10px); padding: 10px 0; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(212,163,115,0.1); }
        nav { max-width: 1000px; margin: auto; display: flex; justify-content: center; align-items: center; gap: 20px; }
        .nav-left, .nav-right { display: flex; gap: 30px; align-items: center; }
        nav a { color: var(--cream); text-decoration: none; text-transform: uppercase; font-size: 11px; letter-spacing: 2px; font-weight: 600; transition: 0.3s; }
        nav a:hover { color: var(--accent-gold); }

        .logo-circle {
            width: 110px; height: 110px;
            background: var(--dark-espresso); border-radius: 50%;
            border: 3px solid var(--accent-gold);
            display: flex; justify-content: center; align-items: center;
            margin: -35px 20px; z-index: 100;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            flex-shrink: 0; overflow: hidden; 
        }
        .logo-circle img { width: 100%; height: 100%; object-fit: cover; }

        /* --- AR CUSTOMIZER LAYOUT --- */
        .main-content {
            flex: 1;
            padding: 40px 20px;
            max-width: 1200px;
            margin: auto;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            box-sizing: border-box;
        }

        /* MODEL VIEW */
        .model-viewer-wrapper {
            background: rgba(44, 27, 14, 0.4);
            border-radius: 20px;
            border: 1px solid rgba(212, 163, 115, 0.1);
            position: sticky;
            top: 100px; /* Sticks during scroll on desktop */
            height: 600px;
            overflow: hidden;
            box-shadow: inset 0 0 50px rgba(0,0,0,0.5);
        }
        model-viewer { width: 100%; height: 100%; --poster-color: transparent; }

        /* CONFIG PANEL */
        .config-panel {
            background: rgba(44, 27, 14, 0.6);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(212, 163, 115, 0.1);
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
        }

        .config-panel h2 { font-family: 'Playfair Display', serif; color: var(--accent-gold); margin: 0 0 10px 0; font-size: 32px; }
        .base-price-tag { font-size: 14px; color: var(--text-muted); margin-bottom: 25px; letter-spacing: 1px; }

        .customization-list { flex: 1; margin-bottom: 25px; }

        .custom-item {
            background: rgba(0,0,0,0.3);
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 12px;
            border: 1px solid rgba(212,163,115,0.05);
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .custom-item:hover { border-color: var(--accent-gold); background: rgba(212, 163, 115, 0.05); }
        .custom-item.selected { border-color: var(--accent-gold); background: rgba(212, 163, 115, 0.15); box-shadow: 0 0 15px rgba(212,163,115,0.1); }
        .custom-item input { accent-color: var(--accent-gold); transform: scale(1.3); pointer-events: none; }
        .custom-label { flex: 1; font-size: 15px; font-weight: 500; }
        .custom-price { color: var(--accent-gold); font-size: 14px; font-weight: bold; }

        /* PRICE & ACTIONS */
        .total-box {
            background: var(--dark-espresso);
            border: 1px solid rgba(212,163,115,0.2);
            padding: 20px;
            border-radius: 15px;
            margin-top: auto;
        }
        .total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .total-price { font-size: 26px; font-family: 'Playfair Display', serif; color: var(--accent-gold); }

        .btn-group { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-group.single-btn { grid-template-columns: 1fr; }
        
        .btn {
            padding: 16px; border-radius: 8px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1.5px;
            cursor: pointer; transition: 0.3s; text-align: center;
            border: none; font-size: 11px;
        }
        .btn-gold { background: var(--accent-gold); color: var(--dark-espresso); }
        .btn-outline { background: transparent; border: 1px solid var(--accent-gold); color: var(--accent-gold); }

        /* --- RESPONSIVE --- */
        @media (max-width: 900px) {
            .main-content { grid-template-columns: 1fr; padding: 20px; }
            .model-viewer-wrapper { position: relative; top: 0; height: 350px; margin-bottom: 20px; }
            header h1 { font-size: 20px; }
            .menu-toggle { display: block; }
            .logout-link { display: none; } /* Hide on mobile to save space */
            
            nav { display: none; flex-direction: column; padding: 20px 0; }
            nav.active { display: flex; }
            .nav-left, .nav-right { flex-direction: column; gap: 10px; }
            .logo-circle { order: -1; margin: 10px auto; width: 80px; height: 80px; }
            
            .total-box { position: sticky; bottom: 20px; z-index: 100; box-shadow: 0 -10px 30px rgba(0,0,0,0.5); }
        }

        /* --- FOOTER --- */
        .footer { background: var(--dark-espresso); border-top: 2px solid #3d2a1a; padding: 60px 20px 20px; margin-top: 80px; }
        .footer-container { max-width: 1000px; margin: auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; }
        .footer h3 { font-family: 'Playfair Display', serif; color: var(--accent-gold); margin-bottom: 15px; }
        .footer p { font-size: 13px; color: var(--cream); }
        .footer-bottom { margin-top: 50px; padding-top: 20px; border-top: 1px solid rgba(212,163,115,0.1); text-align: center; color: #6d5d50; font-size: 11px; }
        @media (max-width: 768px) { .footer-container { grid-template-columns: 1fr; text-align: center; } }
    </style>
</head>
<body>

<header>
    <button class="menu-toggle" onclick="toggleMenu()">MENU</button>
    <h1>Carrie's Cafe</h1>
    <a href="/food_ar_app/user/index.php?logout=1" class="logout-link">Logout</a>
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
    <div class="model-viewer-wrapper">
        <?php if($glb || $usdz): ?>
            <model-viewer 
                id="arModel"
                src="<?= htmlspecialchars($glb) ?>" 
                ios-src="<?= htmlspecialchars($usdz) ?>" 
                alt="<?= htmlspecialchars($category) ?> 3D model"
                auto-rotate camera-controls ar
                ar-modes="webxr scene-viewer quick-look"
                shadow-intensity="1"
                environment-image="neutral"
                exposure="1.2">
                <button slot="ar-button" style="display:none;"></button>
            </model-viewer>
        <?php else: ?>
            <div style="display:flex; height:100%; align-items:center; justify-content:center; color:var(--text-muted);">
                Model unavailable.
            </div>
        <?php endif; ?>
    </div>

    <div class="config-panel">
        <h2><?= htmlspecialchars($category) ?></h2>
        <p class="base-price-tag">BASE SELECTION • LKR <?= number_format($basePrice,2) ?></p>

        <div class="customization-list">
            <?php foreach($customizations as $c): ?>
                <div class="custom-item" onclick="toggleCustomization('cust<?= $c['CustomizationID'] ?>')">
                    <input type="checkbox" class="custom-checkbox" 
                           data-price="<?= $c['Price'] ?? 0 ?>"
                           data-glb="<?= htmlspecialchars($c['ModelGLB']) ?>"
                           data-usdz="<?= htmlspecialchars($c['ModelUSDZ']) ?>"
                           id="cust<?= $c['CustomizationID'] ?>">
                    <div class="custom-label"><?= htmlspecialchars($c['Name']) ?></div>
                    <div class="custom-price">+<?= number_format($c['Price'],0) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="total-box">
            <div class="total-row">
                <span style="color:var(--text-muted); text-transform:uppercase; font-size:11px; letter-spacing:1px;">Total Estimate</span>
                <span class="total-price">LKR <span id="totalPrice"><?= number_format($basePrice,2) ?></span></span>
            </div>

            <div class="btn-group" id="btnGroup">
                <button class="btn btn-outline" id="viewARBtn">View in AR</button>
                <form method="POST" action="../order/add_to_cart.php" style="width:100%">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                    <input type="hidden" name="customization_json" id="customizationJson" value="">
                    <input type="hidden" name="total_price" id="totalPriceInput" value="<?= $basePrice ?>">
                    <button type="submit" class="btn btn-gold" style="width:100%">Add to Cart</button>
                </form>
            </div>
        </div>
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
const basePrice = parseFloat(<?= $basePrice ?>);
const checkboxes = document.querySelectorAll('.custom-checkbox');
const totalPriceElem = document.getElementById('totalPrice');
const modelViewer = document.getElementById('arModel');
const customizationJsonInput = document.getElementById('customizationJson');
const totalPriceInput = document.getElementById('totalPriceInput');

function toggleMenu() {
    document.getElementById("mainNav").classList.toggle("active");
}

function checkDeviceForAR() {
    const arBtn = document.getElementById('viewARBtn');
    const group = document.getElementById('btnGroup');
    const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    
    if (isMobile) {
        arBtn.style.display = 'block';
    } else {
        arBtn.style.display = 'none';
        group.classList.add('single-btn');
    }
}

function toggleCustomization(id) {
    const cb = document.getElementById(id);
    cb.checked = !cb.checked;
    handleCheckboxClick(cb);
}

function handleCheckboxClick(clicked) {
    checkboxes.forEach(cb => { 
        if(cb !== clicked) cb.checked = false; 
        cb.closest('.custom-item').classList.remove('selected');
    });

    if(clicked.checked) {
        clicked.closest('.custom-item').classList.add('selected');
        modelViewer.src = clicked.dataset.glb;
        modelViewer.iosSrc = clicked.dataset.usdz;
    } else {
        modelViewer.src = "<?= htmlspecialchars($glb) ?>";
        modelViewer.iosSrc = "<?= htmlspecialchars($usdz) ?>";
    }
    updatePrice();
    updateCustomizationJson();
}

function updatePrice() {
    let total = basePrice;
    checkboxes.forEach(cb => { if(cb.checked) total += parseFloat(cb.dataset.price); });
    totalPriceElem.textContent = total.toLocaleString(undefined, {minimumFractionDigits: 2});
    totalPriceInput.value = total.toFixed(2);
}

function updateCustomizationJson() {
    const selected = [];
    checkboxes.forEach(cb => { if(cb.checked) selected.push(cb.id); });
    customizationJsonInput.value = JSON.stringify(selected);
}

document.getElementById('viewARBtn').addEventListener('click', () => {
    if (modelViewer.canActivateAR) {
        modelViewer.activateAR();
    } else {
        alert("Please use a mobile device with AR capabilities.");
    }
});

window.addEventListener('load', () => {
    checkDeviceForAR();
    updatePrice();
    updateCustomizationJson();
});
</script>

</body>
</html>