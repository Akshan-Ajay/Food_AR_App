<?php
session_start();
include("C:/xampp/htdocs/food_ar_app/includes/db.php");

/* ---------- ADMIN LOGIN CHECK ---------- */
if(!isset($_SESSION['admin_id'])){
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin";
$category = $_GET['category'] ?? '';

if(!$category){
    die("Category not found.");
}

/* ---------- FETCH EXISTING DATA ---------- */
$sql = "SELECT * FROM CategoryARModels WHERE Category = ?";
$params = array($category);
$stmt = sqlsrv_query($conn, $sql, $params);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$row){
    die("Record not found.");
}

$success = "";
$error = "";

/* ---------- UPDATE DATA ---------- */
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $basePrice = $_POST['BasePrice'];
    $glbPath = $row['ModelGLB'];
    $usdzPath = $row['ModelUSDZ'];

    // Ensure directory exists
    $targetDir = "C:/xampp/htdocs/food_ar_app/uploads/models/";
    if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    /* Upload GLB */
    if(!empty($_FILES['ModelGLB']['name'])){
        $fileName = time()."_base.glb";
        $targetFile = $targetDir.$fileName;
        if(move_uploaded_file($_FILES["ModelGLB"]["tmp_name"], $targetFile)){
            $glbPath = "food_ar_app/uploads/models/".$fileName;
        }
    }

    /* Upload USDZ */
    if(!empty($_FILES['ModelUSDZ']['name'])){
        $fileName = time()."_base.usdz";
        $targetFile = $targetDir.$fileName;
        if(move_uploaded_file($_FILES["ModelUSDZ"]["tmp_name"], $targetFile)){
            $usdzPath = "food_ar_app/uploads/models/".$fileName;
        }
    }

    $updateSQL = "UPDATE CategoryARModels
                  SET BasePrice = ?, ModelGLB = ?, ModelUSDZ = ?, UpdatedAt = GETDATE()
                  WHERE Category = ?";

    $updateParams = array($basePrice, $glbPath, $usdzPath, $category);
    $updateStmt = sqlsrv_query($conn, $updateSQL, $updateParams);

    if($updateStmt){
        $success = "Base model for '$category' updated successfully.";
        // Refresh local row data to show new paths
        $row['BasePrice'] = $basePrice;
        $row['ModelGLB'] = $glbPath;
        $row['ModelUSDZ'] = $usdzPath;
    } else {
        $error = "Failed to update database.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Edit Base Model — Carrie's Cafe</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap');
        
        :root {
            --primary: #c0392b;
            --accent: #e67e22;
            --bg: #fdfaf8;
            --sidebar: #1e120a;
            --text: #3b2314;
            --card: #ffffff;
            --border: #ece0d1;
            --radius: 16px;
            --shadow: 0 10px 30px rgba(44,26,14,0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar { width: 260px; background: var(--sidebar); position: fixed; height: 100vh; z-index: 1000; }
        .sidebar-logo { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-logo img { width: 140px; border-radius: 10px; }
        .nav-section { color: rgba(255,255,255,0.3); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 2px; padding: 20px 25px 5px; }
        .sidebar nav a { 
            display: flex; align-items: center; gap: 15px; padding: 12px 25px; color: rgba(255,255,255,0.6); 
            text-decoration: none; font-size: 0.9rem; transition: var(--transition); border-left: 4px solid transparent;
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,0.05); color: #fff; border-left-color: var(--accent); }

        /* MAIN */
        .main { margin-left: 260px; flex: 1; width: calc(100% - 260px); }
        .topbar { 
            background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); 
            padding: 15px 40px; display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 900; border-bottom: 1px solid var(--border);
        }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; }

        .content { padding: 40px; }
        .card { background: var(--card); border-radius: var(--radius); padding: 35px; border: 1px solid var(--border); box-shadow: var(--shadow); max-width: 900px; margin: 0 auto; }
        
        .card-header { margin-bottom: 30px; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        .card-title { font-family: 'Playfair Display', serif; font-size: 1.4rem; color: var(--sidebar); }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }

        /* FORM ELEMENTS */
        .form-group { margin-bottom: 22px; }
        label { display: block; font-size: 0.75rem; font-weight: 700; color: #888; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        input { 
            width: 100%; padding: 12px 15px; border: 1px solid var(--border); border-radius: 10px; 
            font-size: 0.95rem; transition: var(--transition); outline: none; background: #fafafa;
        }
        input:focus { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 4px rgba(230,126,34,0.05); }
        input:disabled { background: #eee; cursor: not-allowed; color: #777; }

        /* AR PREVIEW BOX (Same as Edit Food) */
        .model-viewer-placeholder {
            width: 100%; height: 200px; background: #1e120a; border-radius: 12px; 
            display: flex; flex-direction: column; align-items: center; justify-content: center; 
            color: rgba(255,255,255,0.4); position: relative; overflow: hidden; margin-bottom: 20px;
        }
        .orbit-ring { position: absolute; border-radius: 50%; border: 1.5px solid rgba(230,126,34,0.2); animation: spin 8s linear infinite; }
        .orbit-ring:nth-child(1) { width: 160px; height: 70px; }
        @keyframes spin { from { transform: rotateX(70deg) rotate(0deg) } to { transform: rotateX(70deg) rotate(360deg) } }

        /* BUTTONS */
        .btn { 
            display: inline-flex; align-items: center; gap: 10px; padding: 12px 28px; 
            border-radius: 10px; font-weight: 600; cursor: pointer; border: none; transition: var(--transition); text-decoration: none; font-size: 0.9rem;
        }
        .btn-primary { background: var(--sidebar); color: #fff; }
        .btn-primary:hover { background: #000; transform: translateY(-2px); }
        .btn-outline { background: #fff; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { background: #fdfaf8; }

        .user-pill { 
            background: #fff; border: 1px solid var(--border); border-radius: 50px; padding: 5px 15px 5px 5px;
            display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.85rem; text-decoration: none; color: var(--text);
        }
        .avatar { width: 32px; height: 32px; background: var(--sidebar); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 25px; font-weight: 500; font-size: 0.9rem; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }

        @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } .sidebar { display:none; } .main { margin-left:0; width:100%; } }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="/food_ar_app/admin/Lor.png" alt="Logo">
        <div style="color:var(--accent); font-size:0.7rem; font-weight:700; margin-top:5px;">MANAGEMENT</div>
    </div>
    <nav>
        <div class="nav-section">Dashboard</div>
        <a href="/food_ar_app/admin/dashboard.php"><span class="icon">📊</span> Dashboard</a>
        <div class="nav-section">Foods</div>
        <a href="/food_ar_app/admin/menu/menu_manage.php" class="active"><span class="icon">🍽️</span> Foods</a>
        <div class="nav-section">Payment</div>
        <a href="/food_ar_app/admin/payment/view_payments.php"><span class="icon">💳</span> Payments</a>
        <div class="nav-section">Reservation</div>
        <a href="/food_ar_app/admin/reservation/reservation_manage.php"><span class="icon">📋</span> Bookings</a>
        <div class="nav-section">Orders</div>
        <a href="/food_ar_app/admin/order/view_orders.php"><span class="icon">📦</span> Orders</a>
        <div class="nav-section">FEEDBACK</div>
        <a href="/food_ar_app/admin/feedback/feedback_manage.php"><span class="icon">💬</span> Feedback</a>
    </nav>
</aside>

<div class="main">
    <div class="topbar">
        <span class="page-title">Edit AR Assets</span>
        <a href="/food_ar_app/admin/index.php" class="user-pill">
            <div class="avatar"><?= strtoupper(substr($admin_name, 0, 2)) ?></div>
            <span>Logout 🔐</span>
        </a>
    </div>

    <div class="content">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">✏️ Updating Category: <span style="color:var(--accent)"><?= htmlspecialchars($category) ?></span></h3>
            </div>

            <?php if($success): ?>
                <div class="alert alert-success">✅ <?= $success ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="grid-2">
                    <div>
                        <div class="form-group">
                            <label>Base Category (Locked)</label>
                            <input type="text" value="<?= htmlspecialchars($row['Category']); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label>Base Price (LKR)</label>
                            <input type="number" step="0.01" name="BasePrice" value="<?= $row['BasePrice']; ?>" required>
                        </div>

                        <div style="margin-top: 40px; display: flex; gap: 15px;">
                            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                            <a href="view_base_models.php" class="btn btn-outline">Cancel</a>
                        </div>
                    </div>

                    <div>
                        <div class="model-viewer-placeholder">
                            <div class="orbit-ring"></div>
                            <div style="font-size: 3rem; z-index: 1;">🧊</div>
                            <p style="z-index: 1; margin-top: 10px; font-size: 0.75rem;">
                                <?= $row['ModelGLB'] ? basename($row['ModelGLB']) : 'No 3D asset linked' ?>
                            </p>
                        </div>

                        <div class="form-group">
                            <label>Replace Android Model (.glb)</label>
                            <input type="file" name="ModelGLB" accept=".glb">
                        </div>

                        <div class="form-group">
                            <label>Replace iOS Model (.usdz)</label>
                            <input type="file" name="ModelUSDZ" accept=".usdz">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>