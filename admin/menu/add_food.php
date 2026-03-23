<?php
session_start();
include("C:/xampp/htdocs/food_ar_app/includes/db.php");

/* LOGIN PROTECTION */
if (!isset($_SESSION['admin_id'])) {
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin";

/* FORM PROCESSING LOGIC - UNCHANGED */
if ($_SERVER["REQUEST_METHOD"] === 'POST') {
    $foodName        = trim($_POST['FoodName'] ?? '');
    $category        = trim($_POST['Category'] ?? '');
    $price           = floatval($_POST['Price'] ?? 0);
    $description     = trim($_POST['Description'] ?? '');
    $ingredients     = trim($_POST['Ingredients'] ?? '');
    $portionSize     = trim($_POST['PortionSize'] ?? '');
    $nutritionalInfo = trim($_POST['NutritionalInfo'] ?? '');

    $uploadDir = "uploads/"; 
    $fullUploadDir = "C:/xampp/htdocs/food_ar_app/uploads/";
    if (!is_dir($fullUploadDir)) mkdir($fullUploadDir, 0777, true);

    $imagePath = $glbPath = $usdzPath = NULL;
    $errors = [];

    if (!empty($_FILES["FoodImage"]["name"])) {
        $ext = strtolower(pathinfo($_FILES["FoodImage"]["name"], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $imageName = time() . "_img." . $ext;
            if (move_uploaded_file($_FILES["FoodImage"]["tmp_name"], $fullUploadDir . $imageName)) {
                $imagePath = "uploads/" . $imageName;
            } else { $errors[] = "Image upload failed"; }
        }
    }

    if (!empty($_FILES["ARModelGLB"]["name"])) {
        $ext = strtolower(pathinfo($_FILES["ARModelGLB"]["name"], PATHINFO_EXTENSION));
        if ($ext === 'glb') {
            $glbName = time() . "_model.glb";
            if (move_uploaded_file($_FILES["ARModelGLB"]["tmp_name"], $fullUploadDir . $glbName)) {
                $glbPath = "uploads/" . $glbName;
            } else { $errors[] = "GLB upload failed"; }
        }
    }

    if (!empty($_FILES["ARModelUSDZ"]["name"])) {
        $ext = strtolower(pathinfo($_FILES["ARModelUSDZ"]["name"], PATHINFO_EXTENSION));
        if ($ext === 'usdz') {
            $usdzName = time() . "_model.usdz";
            if (move_uploaded_file($_FILES["ARModelUSDZ"]["tmp_name"], $fullUploadDir . $usdzName)) {
                $usdzPath = "uploads/" . $usdzName;
            } else { $errors[] = "USDZ upload failed"; }
        }
    }

    if (empty($foodName) || $price <= 0) { $errors[] = "Food name and price are required"; }

    if (empty($errors)) {
        $sql = "INSERT INTO MenuItems 
                (FoodName, Description, Price, Category, ImagePath, Ingredients, PortionSize, NutritionalInfo, ARModelGLB, ARModelUSDZ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$foodName,$description,$price,$category,$imagePath,$ingredients,$portionSize,$nutritionalInfo,$glbPath,$usdzPath];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            echo json_encode(['status'=>'success', 'message'=>'✅ Food item added successfully!']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'❌ Database error occurred!', 'errors'=>sqlsrv_errors()]);
        }
    } else {
        echo json_encode(['status'=>'error', 'message'=>implode(", ", $errors)]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Add Food — Carrie's Cafe</title>
    <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.3.0/model-viewer.min.js"></script>
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

        /* MAIN AREA */
        .main { margin-left: 260px; flex: 1; width: calc(100% - 260px); }
        .topbar { 
            background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); 
            padding: 15px 40px; display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 900; border-bottom: 1px solid var(--border);
        }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; }

        /* CONTENT */
        .content { padding: 40px; }
        .card { background: var(--card); border-radius: var(--radius); padding: 30px; border: 1px solid var(--border); box-shadow: var(--shadow); margin-bottom: 25px; }
        
        /* FORM STYLING */
        .grid-2 { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 40px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.75rem; font-weight: 700; color: #888; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        input, select, textarea { 
            width: 100%; padding: 12px 15px; border: 1px solid var(--border); border-radius: 10px; 
            font-size: 0.95rem; transition: var(--transition); outline: none; background: #fafafa;
        }
        input:focus, textarea:focus { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 4px rgba(230,126,34,0.05); }

        /* BUTTONS */
        .btn { 
            display: inline-flex; align-items: center; gap: 10px; padding: 12px 25px; 
            border-radius: 10px; font-weight: 600; cursor: pointer; border: none; transition: var(--transition); text-decoration: none; font-size: 0.85rem;
        }
        .btn-primary { background: var(--sidebar); color: #fff; }
        .btn-primary:hover { background: #000; transform: translateY(-2px); }
        .btn-accent { background: var(--accent); color: #fff; }
        .btn-accent:hover { background: #d35400; }
        .btn-outline { background: #fff; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }

        /* UPLOAD & PREVIEW */
        .upload-zone { 
            border: 2px dashed var(--border); border-radius: 12px; padding: 25px; 
            text-align: center; cursor: pointer; transition: var(--transition); background: #fdfdfd;
        }
        .upload-zone:hover { border-color: var(--accent); background: #fff8f2; }
        .upload-zone .icon { font-size: 2rem; margin-bottom: 10px; display: block; }

        .model-viewer-box {
            width: 100%; height: 200px; background: #1e120a; border-radius: var(--radius); 
            display: flex; flex-direction: column; align-items: center; justify-content: center; 
            color: rgba(255,255,255,0.4); position: relative; overflow: hidden; margin-bottom: 15px;
        }
        .orbit-ring { position: absolute; border-radius: 50%; border: 1.5px solid rgba(230,126,34,.2); animation: spin 8s linear infinite; }
        .orbit-ring:nth-child(1) { width: 150px; height: 60px; }
        .orbit-ring:nth-child(2) { width: 100px; height: 100px; animation-duration: 5s; animation-direction: reverse; }

        @keyframes spin { from { transform: rotateX(70deg) rotate(0deg) } to { transform: rotateX(70deg) rotate(360deg) } }

        .user-pill { 
            background: #fff; border: 1px solid var(--border); border-radius: 50px; padding: 5px 15px 5px 5px;
            display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.85rem; text-decoration: none; color: var(--text);
        }
        .avatar { width: 32px; height: 32px; background: var(--sidebar); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        @media (max-width: 1024px) { .grid-2 { grid-template-columns: 1fr; } .sidebar { display:none; } .main { margin-left:0; width:100%; } }
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
        <span class="page-title">Add Food Item</span>
        <a href="/food_ar_app/admin/index.php" class="user-pill">
            <div class="avatar"><?php echo strtoupper(substr($admin_name,0,2)); ?></div>
            <span>Logout 🔐</span>
        </a>
    </div>

    <div class="content">
        <div class="card" style="padding: 15px 30px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
                <h3 style="font-family:'Playfair Display', serif;">➕ New Product</h3>
                <div style="display:flex; gap:10px;">
                    <a href="add_customization.php" class="btn btn-accent">➕ Customization</a>
                    <a href="view_base_models.php" class="btn btn-accent">📦 Base Details</a>
                    <a href="View_Customizations.php" class="btn btn-accent">⚙️ Details</a>
                </div>
            </div>
        </div>

        <div class="card">
            <form id="addFoodForm" enctype="multipart/form-data">
                <div class="grid-2">
                    <div>
                        <div class="form-group">
                            <label>Food Name</label>
                            <input type="text" name="FoodName" placeholder="Enter dish name" required>
                        </div>
                        <div class="grid-2" style="grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Category</label>
                                <input type="text" name="Category" placeholder="e.g. Desserts">
                            </div>
                            <div class="form-group">
                                <label>Price (LKR)</label>
                                <input type="number" name="Price" step="0.01" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Portion Size</label>
                            <input type="text" name="PortionSize" placeholder="e.g. Regular / 500g">
                        </div>
                        <div class="form-group">
                            <label>Nutritional Info</label>
                            <textarea name="NutritionalInfo" placeholder="Calories, Allergens..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="Description" placeholder="Write a short description..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Ingredients</label>
                            <input type="text" name="Ingredients" placeholder="Wheat, Sugar, Milk...">
                        </div>
                    </div>

                    <div>
                        <div class="form-group">
                            <label>Food Image</label>
                            <div class="upload-zone" onclick="document.getElementById('imgInput').click()">
                                <span class="icon">🖼️</span>
                                <p id="imgLabel"><strong>Upload Image</strong><br><small>PNG, JPG, WebP</small></p>
                                <input type="file" id="imgInput" name="FoodImage" style="display:none" onchange="updateLabel(this, 'imgLabel')">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>3D AR Reality</label>
                            <div class="model-viewer-box">
                                <div class="orbit-ring"></div>
                                <div class="orbit-ring"></div>
                                <div style="font-size:3rem; z-index:1;">🧊</div>
                                <p style="z-index:1; margin-top:10px; font-size:0.75rem;">Model Preview</p>
                            </div>
                            <div class="upload-zone" style="background:#fafafa;">
                                <div style="margin-bottom:10px;">
                                    <label style="text-align:left;">GLB (Android)</label>
                                    <input type="file" name="ARModelGLB">
                                </div>
                                <div>
                                    <label style="text-align:left;">USDZ (iOS)</label>
                                    <input type="file" name="ARModelUSDZ">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:12px; margin-top:30px; border-top: 1px solid var(--border); padding-top:25px;">
                    <button type="submit" class="btn btn-primary">💾 Add Food Item</button>
                    <a href="/food_ar_app/admin/menu/menu_manage.php" class="btn btn-outline">Cancel</a>
                    <span id="message" style="margin-left:auto; align-self:center; font-weight:600;"></span>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updateLabel(input, id) {
    if (input.files && input.files[0]) {
        document.getElementById(id).innerHTML = "<strong>Selected:</strong><br>" + input.files[0].name;
    }
}

$('#addFoodForm').on('submit', function(e){
    e.preventDefault();
    const submitBtn = $(this).find('button[type="submit"]');
    submitBtn.prop('disabled', true).text('Processing...');
    
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
                $('#addFoodForm')[0].reset();
                document.getElementById('imgLabel').innerHTML = "<strong>Upload Image</strong><br><small>PNG, JPG, WebP</small>";
            } else {
                $('#message').css('color','red').text(res.message);
                console.log(res.errors || 'No error details.');
            }
            submitBtn.prop('disabled', false).html('💾 Add Food Item');
        },
        error: function(xhr){
            $('#message').css('color','red').text('Server error — check console.');
            submitBtn.prop('disabled', false).html('💾 Add Food Item');
        }
    });
});
</script>
</body>
</html>