<?php
session_start();

// FIXED: Added an extra '../' to reach the includes folder from the feedback subfolder
include('../../includes/db.php'); 

if(!isset($_SESSION['customer_id'])){
    header("Location: /food_ar_app/user/index.php");
    exit();
}

$user_id = $_SESSION['customer_id'];
$today = new DateTime();
// Get date from 3 months ago
$last_quarter = $today->sub(new DateInterval('P3M'))->format('Y-m-d');

// Handle new feedback submission
if(isset($_POST['submit_feedback']) && isset($conn)){
    $category = $_POST['category'];
    $rating = intval($_POST['rating']);
    $comments = $_POST['comments'];

    $sql = "INSERT INTO Feedback (UserID, Category, Rating, Comments, CreatedAt) VALUES (?, ?, ?, ?, GETDATE())";
    $params = array($user_id, $category, $rating, $comments);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if($stmt){
        $success = "Thank you! Your feedback has been shared with our team.";

        // --- CREATE ADMIN NOTIFICATION ---
        $admin_id = 1; 
        $message = "New feedback: $category ($rating Stars)";
        $role = "Admin";
        $type = "Feedback";

        $sql_notify = "INSERT INTO Notifications (UserID, Message, TargetRole, NotificationType, CreatedAt)
                       VALUES (?, ?, ?, ?, GETDATE())";
        $params_notify = [$admin_id, $message, $role, $type];
        sqlsrv_query($conn, $sql_notify, $params_notify);
    } else {
        $error = "Error submitting feedback. Please try again.";
    }
}

// Fetch feedback history with safety check
$feedbacks = [];
if(isset($conn)) {
    $sql_history = "SELECT Category, Rating, Comments, Response, CreatedAt 
                    FROM Feedback 
                    WHERE UserID = ? AND CreatedAt >= ? 
                    ORDER BY CreatedAt DESC";
    $stmt_history = sqlsrv_query($conn, $sql_history, array($user_id, $last_quarter));
    
    if($stmt_history){
        while($row = sqlsrv_fetch_array($stmt_history, SQLSRV_FETCH_ASSOC)){
            $feedbacks[] = $row;
        }
    }

    /* GET CAFE SETTINGS FOR FOOTER */
    $sql_settings = "SELECT TOP 1 * FROM CafeSettings";
    $stmt_settings = sqlsrv_query($conn, $sql_settings);
    $settings = ($stmt_settings) ? sqlsrv_fetch_array($stmt_settings, SQLSRV_FETCH_ASSOC) : [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback — Carrie's Cafe</title>
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
        .logout-link { position: absolute; right: 30px; top: 50%; transform: translateY(-50%); color: #ff6b6b; text-decoration: none; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }

        .nav_container { background: var(--glass); backdrop-filter: blur(10px); padding: 10px 0; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(212, 163, 115, 0.1); }
        nav { max-width: 1000px; margin: auto; display: flex; justify-content: center; align-items: center; gap: 20px; }
        .nav-left, .nav-right { display: flex; gap: 30px; align-items: center; }
        nav a { color: var(--cream); text-decoration: none; text-transform: uppercase; font-size: 11px; letter-spacing: 2px; font-weight: 600; transition: 0.3s; }
        nav a:hover, nav a.active { color: var(--accent-gold); }
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

        /* --- CONTENT --- */
        .main-content {
            flex: 1;
            max-width: 800px;
            margin: 60px auto;
            padding: 0 20px;
            width: 100%;
            box-sizing: border-box;
        }

        .feedback-card {
            background: rgba(44, 27, 14, 0.6);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(212, 163, 115, 0.1);
        }

        .feedback-card h2 { font-family: 'Playfair Display', serif; color: var(--accent-gold); font-size: 32px; margin-top: 0; }
        
        label { display: block; margin-top: 20px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--accent-gold); font-weight: 600; }
        
        select, textarea {
            width: 100%;
            padding: 15px;
            margin-top: 8px;
            background: rgba(0,0,0,0.2);
            border: 1px solid rgba(212, 163, 115, 0.2);
            color: var(--cream);
            border-radius: 8px;
            font-family: inherit;
            box-sizing: border-box;
        }

        .btn-gold {
            width: 100%;
            background: var(--accent-gold);
            color: var(--dark-espresso);
            border: none;
            padding: 18px;
            margin-top: 30px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            border-radius: 5px;
            transition: 0.3s;
        }
        .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(212, 163, 115, 0.3); }

        /* --- HISTORY --- */
        .history-section { margin-top: 60px; }
        .history-section h3 { font-family: 'Playfair Display', serif; color: var(--accent-gold); border-bottom: 1px solid #3d2a1a; padding-bottom: 10px; }

        .feedback-item {
            background: rgba(255,255,255,0.03);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 3px solid var(--accent-gold);
        }

        .stars { color: var(--accent-gold); font-size: 18px; }
        .admin-reply {
            background: rgba(212, 163, 115, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-style: italic;
            font-size: 14px;
        }

        /* --- FOOTER --- */
        .footer { background: var(--dark-espresso); border-top: 2px solid #3d2a1a; padding: 50px 0 20px; margin-top: 60px; }
        .footer-container { max-width: 1000px; margin: auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; padding: 0 20px; }
        .footer h3 { color: var(--accent-gold); font-family: 'Playfair Display', serif; margin-bottom: 15px; }
        .footer p { font-size: 13px; color: var(--text-muted); line-height: 1.6; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center; }
        .alert-success { background: rgba(40, 167, 69, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .alert-error { background: rgba(220, 53, 69, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
    </style>
</head>
<body>

<header>
    <h1>Carrie's Cafe</h1>
    <a href="/food_ar_app/user/index.php?logout=1" class="logout-link">Logout</a>
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
            <a href="#" class="active">Feedback</a>
        </div>
    </nav>
</div>

<div class="main-content">
    <div class="feedback-card">
        <h2>Your Experience</h2>
        <p style="color:var(--text-muted); font-size: 14px; margin-bottom: 30px;">We value your thoughts. Tell us how we're doing.</p>

        <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert alert-error'>$error</div>"; ?>
        
        <form method="POST">
            <label>Category</label>
            <select name="category" required>
                <option value="Food">Food & Pastries</option>
                <option value="Service">Customer Service</option>
                <option value="AR Experience">AR Customization Tool</option>
                <option value="Ambiance">Cafe Atmosphere</option>
            </select>

            <label>Rating</label>
            <select name="rating" required>
                <option value="5">★★★★★ — Exceptional</option>
                <option value="4">★★★★☆ — Great</option>
                <option value="3">★★★☆☆ — Good</option>
                <option value="2">★★☆☆☆ — Fair</option>
                <option value="1">★☆☆☆☆ — Poor</option>
            </select>

            <label>Detailed Comments</label>
            <textarea name="comments" rows="5" placeholder="Share your experience with us..." required></textarea>

            <button type="submit" name="submit_feedback" class="btn-gold">Submit Feedback</button>
        </form>
    </div>

    <div class="history-section">
        <h3>Past Contributions</h3>
        <?php if(empty($feedbacks)): ?>
            <p style="color:var(--text-muted); font-style:italic;">No feedback recorded in the last 3 months.</p>
        <?php endif; ?>

        <?php foreach($feedbacks as $fb): ?>
            <div class="feedback-item">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <span style="font-weight:600; text-transform:uppercase; font-size:12px; letter-spacing:1px; color:var(--accent-gold);">
                            <?= htmlspecialchars($fb['Category']) ?>
                        </span>
                        <div class="stars"><?= str_repeat('★', $fb['Rating']) . str_repeat('☆', 5 - $fb['Rating']) ?></div>
                    </div>
                    <small style="color:var(--text-muted);">
                        <?php 
                        if ($fb['CreatedAt'] instanceof DateTime) {
                            echo $fb['CreatedAt']->format('M d, Y');
                        } else {
                            echo date('M d, Y', strtotime($fb['CreatedAt']));
                        }
                        ?>
                    </small>
                </div>
                <p style="margin: 15px 0; line-height:1.6; font-size:15px;"><?= htmlspecialchars($fb['Comments']) ?></p>

                <?php if(!empty($fb['Response'])): ?>
                    <div class="admin-reply">
                        <strong style="color:var(--accent-gold); font-style:normal;">Management Reply:</strong><br>
                        <?= htmlspecialchars($fb['Response']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<footer class="footer">
    <div class="footer-container">
        <div>
            <h3>Visit Us</h3>
            <p>📍 <?= $settings['Address'] ?? '123 Bakery Lane, Colombo'; ?></p>
        </div>
        <div>
            <h3><?= $settings['CafeName'] ?? "Carrie's Cafe"; ?></h3>
            <p><?= $settings['AboutText'] ?? 'Artisan flavors meets AR technology.'; ?></p>
        </div>
        <div>
            <h3>Hours</h3>
            <p><?= $settings['OpeningHours'] ?? 'Daily: 8am - 10pm'; ?></p>
        </div>
    </div>
    <div style="text-align:center; margin-top:40px; border-top:1px solid rgba(255,255,255,0.05); padding-top:20px;">
        <small style="color:var(--text-muted); opacity:0.5;">&copy; <?= date('Y') ?> Carrie's Cafe & Bakery. All rights reserved.</small>
    </div>
</footer>

</body>
</html>