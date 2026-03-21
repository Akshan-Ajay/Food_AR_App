<?php
session_start();
include("C:/xampp/htdocs/food_ar_app/includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === 'POST') {
    $category = trim($_POST['Category'] ?? '');
    $basePrice = floatval($_POST['BasePrice'] ?? 0);

    $glbPath = $usdzPath = NULL;
    $uploadDir = "C:/xampp/htdocs/food_ar_app/uploads/models/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    if (!empty($_FILES["ModelGLB"]["name"])) {
        $glbName = time() . "_model.glb";
        $glbPath = $uploadDir . $glbName;
        move_uploaded_file($_FILES["ModelGLB"]["tmp_name"], $glbPath);
    }

    if (!empty($_FILES["ModelUSDZ"]["name"])) {
        $usdzName = time() . "_model.usdz";
        $usdzPath = $uploadDir . $usdzName;
        move_uploaded_file($_FILES["ModelUSDZ"]["tmp_name"], $usdzPath);
    }

    $sql = "INSERT INTO CategoryARModels (Category, BasePrice, ModelGLB, ModelUSDZ, CreatedAt, UpdatedAt)
            VALUES (?, ?, ?, ?, GETDATE(), GETDATE())";
    $params = [$category, $basePrice, $glbPath, $usdzPath];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        echo json_encode(['status'=>'success','message'=>'✅ Category added successfully!']);
    } else {
        echo json_encode(['status'=>'error','message'=>'❌ Database error occurred!','errors'=>sqlsrv_errors()]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Add Category — Admin Panel</title>
<style>
/* Reuse the Add Food CSS you provided */
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap');
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--primary:#c0392b;--accent:#e67e22;--bg:#fff8f2;--sidebar:#2c1a0e;--text:#3b2314;--card:#ffffff;--border:#f0ddd0;--success:#27ae60;--radius:12px;--shadow:0 4px 20px rgba(44,26,14,.10)}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex}
.sidebar{width:260px;min-height:100vh;background:var(--sidebar);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:100;padding-bottom:24px}
.sidebar-logo{padding:20px;border-bottom:1px solid rgba(255,255,255,.1);text-align:center;}
.sidebar-logo img{width:160px;margin-bottom:8px;border-radius:12px;}
.sidebar-logo .sub{color:var(--accent);font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;}
.sidebar nav{flex:1;padding:16px 0;}
.nav-section{color:rgba(255,255,255,.35);font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;padding:14px 24px 6px;}
.sidebar nav a{display:flex;align-items:center;gap:12px;padding:11px 24px;color:rgba(255,255,255,.72);text-decoration:none;font-size:.875rem;transition:all .2s;border-left:3px solid transparent}
.sidebar nav a:hover,.sidebar nav a.active{background:rgba(255,255,255,.08);color:#fff;border-left-color:var(--accent)}
.sidebar nav a .icon{font-size:1.1rem;width:20px;text-align:center}
.main{margin-left:260px;flex:1;display:flex;flex-direction:column;min-height:100vh}
.topbar{background:var(--card);border-bottom:1px solid var(--border);padding:16px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
.topbar .page-title{font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--text)}
.topbar .user-pill{display:flex;align-items:center;gap:10px;background:var(--bg);border:1px solid var(--border);border-radius:40px;padding:6px 16px 6px 8px;font-size:.85rem}
.avatar{width:32px;height:32px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;font-size:.85rem}
.content{padding:32px;flex:1}
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);padding:28px;margin-bottom:24px}
.card-title{font-family:'Playfair Display',serif;font-size:1.1rem;margin-bottom:20px;color:var(--text);display:flex;align-items:center;gap:10px}
.form-group{margin-bottom:20px}
label{display:block;font-size:.82rem;font-weight:600;color:var(--text);margin-bottom:7px;text-transform:uppercase;letter-spacing:.04em}
input,select,textarea{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:'Inter',sans-serif;font-size:.9rem;color:var(--text);background:#fff;transition:border-color .2s,box-shadow .2s;outline:none}
input:focus,select:focus,textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(192,57,43,.12)}
textarea{resize:vertical;min-height:100px}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;border:none;transition:all .2s;text-decoration:none}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:#a93226;transform:translateY(-1px)}
.btn-outline{background:transparent;border:1.5px solid var(--primary);color:var(--primary)}
.btn-outline:hover{background:var(--primary);color:#fff}
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
    <span class="page-title">Add AR Category</span>
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
      <div class="card-title">➕ Add New Category</div>
      <form id="addCategoryForm" enctype="multipart/form-data">
        <div class="form-group"><label>Category Name</label><input type="text" name="Category" required></div>
        <div class="form-group"><label>Base Price (LKR)</label><input type="number" name="BasePrice" step="0.01" required></div>
        <div class="form-group"><label>3D Model (.glb)</label><input type="file" name="ModelGLB"></div>
        <div class="form-group"><label>3D Model (.usdz)</label><input type="file" name="ModelUSDZ"></div>
        <div style="display:flex;gap:12px;margin-top:16px">
          <button type="submit" class="btn btn-primary">💾 Add Category</button>
          <a href="/food_ar_app/admin/dashboard.php" class="btn btn-outline">Cancel</a>
        </div>
        <div id="message"></div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$('#addCategoryForm').on('submit', function(e){
    e.preventDefault();
    var formData = new FormData(this);
    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(res){
            if(res.status==='success'){
                $('#message').css('color','green').text(res.message);
                $('#addCategoryForm')[0].reset();
            } else {
                $('#message').css('color','red').text(res.message);
                console.log(res.errors || 'No error details.');
            }
        },
        error: function(xhr){
            console.log(xhr.responseText);
            $('#message').css('color','red').text('Server error — check console.');
        }
    });
});
</script>
</body>
</html>