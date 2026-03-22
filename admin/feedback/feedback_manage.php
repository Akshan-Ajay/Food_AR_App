<?php
session_start();

// Admin only
if (!isset($_SESSION['admin_id'])) {
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

include('C:/xampp/htdocs/food_ar_app/includes/db.php');
$admin_name = $_SESSION['admin_name'] ?? "Admin";

// Handle Response submission
if (isset($_POST['feedback_id']) && isset($_POST['response'])) {
    $feedbackID = $_POST['feedback_id'];
    $response = $_POST['response'];

    $sqlUpdate = "UPDATE Feedback SET Response=?, UpdatedAt=GETDATE() WHERE FeedbackID=?";
    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, [$response, $feedbackID]);

    if ($stmtUpdate) {
        $sql_user = "SELECT UserID FROM Feedback WHERE FeedbackID=?";
        $stmt_user = sqlsrv_query($conn, $sql_user, [$feedbackID]);
        $row_user = sqlsrv_fetch_array($stmt_user, SQLSRV_FETCH_ASSOC);
        $customer_id = $row_user['UserID'] ?? 0;

        if ($customer_id) {
            $message = "Admin responded to your feedback (ID: $feedbackID).";
            $sql_notify = "INSERT INTO Notifications (UserID, Message, TargetRole, NotificationType)
                           VALUES (?, ?, ?, ?)";
            sqlsrv_query($conn, $sql_notify, [$customer_id, $message, 'Customer', 'Feedback']);
        }
    }
    header("Location: /food_ar_app/admin/feedback/feedback_manage.php");
    exit();
}

// Handle Filters
$filter_rating = $_GET['rating'] ?? '';
$filter_cat = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$conditions = [];
$params = [];

if ($filter_rating !== '') {
    $conditions[] = "f.Rating = ?";
    $params[] = $filter_rating;
}
if ($filter_cat !== '') {
    $conditions[] = "f.Category = ?";
    $params[] = $filter_cat;
}
if ($search !== '') {
    $conditions[] = "(u.FullName LIKE ? OR f.Comments LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Fetch Global Stats
$sql_stats = "SELECT COUNT(*) as Total, AVG(CAST(Rating AS FLOAT)) as AvgRating FROM Feedback";
$res_stats = sqlsrv_query($conn, $sql_stats);
$stats = sqlsrv_fetch_array($res_stats, SQLSRV_FETCH_ASSOC);

// Fetch Filtered Feedback
$sql = "
    SELECT f.*, u.FullName, u.Email
    FROM Feedback f
    JOIN Users u ON f.UserID = u.UserID
    $where_clause
    ORDER BY f.CreatedAt DESC
";
$stmt = sqlsrv_query($conn, $sql, $params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management — Carrie's Cafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
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
        .sidebar-logo img { width: 140px; border-radius: 10px; margin-bottom: 10px; }
        .nav-section { color: rgba(255,255,255,0.3); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 2px; padding: 20px 25px 5px; }
        .sidebar nav a { 
            display: flex; align-items: center; gap: 15px; padding: 12px 25px; color: rgba(255,255,255,0.6); 
            text-decoration: none; font-size: 0.9rem; transition: var(--transition); border-left: 4px solid transparent;
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,0.05); color: #fff; border-left-color: var(--accent); }

        /* MAIN AREA */
        .main { margin-left: 260px; flex: 1; display: flex; flex-direction: column; width: calc(100% - 260px); }
        .topbar { 
            background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); 
            padding: 15px 40px; display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 900; border-bottom: 1px solid var(--border);
        }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; }

        /* USER & NOTIF */
        .user-controls { display: flex; align-items: center; gap: 25px; }
        .notif-trigger { position: relative; font-size: 1.4rem; cursor: pointer; }
        .notif-badge { position: absolute; top: -5px; right: -5px; background: var(--primary); color: #fff; font-size: 10px; padding: 2px 5px; border-radius: 50%; border: 2px solid #fff; }
        .user-pill { 
            background: #fff; border: 1px solid var(--border); border-radius: 50px; padding: 5px 15px 5px 5px;
            display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.85rem; text-decoration: none; color: var(--text);
        }
        .avatar { width: 32px; height: 32px; background: var(--sidebar); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        .notif-box { 
            display: none; position: absolute; right: 0; top: 45px; width: 320px; background: #fff; 
            border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid var(--border); 
            max-height: 400px; overflow-y: auto; z-index: 1001; 
        }
        .notif-item { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.85rem; text-align: left; }
        .notif-item.unread { background: #fffaf5; border-left: 4px solid var(--accent); }

        /* CONTENT */
        .content { padding: 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 35px; }
        .stat-card { background: var(--card); border-radius: var(--radius); padding: 25px; box-shadow: var(--shadow); border: 1px solid var(--border); transition: var(--transition); }
        .stat-card .label { font-size: 0.75rem; color: #888; text-transform: uppercase; font-weight: 600; }
        .stat-card .value { font-family: 'Playfair Display', serif; font-size: 2.2rem; margin: 10px 0; font-weight: 700; }

        /* FILTER BAR */
        .filter-card { background: var(--card); padding: 25px; border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 35px; box-shadow: var(--shadow); }
        .filter-form { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; flex: 1; min-width: 150px; }
        .filter-group label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #999; letter-spacing: 1px; }
        .filter-form input, .filter-form select { 
            padding: 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); outline: none; font-size: 0.9rem;
        }
        .btn-filter { background: var(--sidebar); color: #fff; border: none; padding: 11px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: var(--transition); }
        .btn-filter:hover { background: var(--accent); }

        /* TABLE */
        .card-table { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #fdfaf8; text-align: left; padding: 15px 20px; font-size: 0.7rem; color: #999; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--border); }
        tbody td { padding: 20px; border-bottom: 1px solid #f9f4f0; font-size: 0.9rem; vertical-align: top; }
        .stars { color: #ffc107; font-size: 1.1rem; }
        
        /* RESPONSE STYLES */
        .response-box { background: #fcf8f5; padding: 15px; border-radius: 12px; border-left: 4px solid var(--accent); font-size: 0.85rem; margin-top: 8px; }
        .response-form textarea { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border); outline: none; font-size: 0.85rem; resize: vertical; margin-bottom: 10px; }
        .btn-submit-res { background: var(--sidebar); color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; }

        @media (max-width: 1024px) { .sidebar { display:none; } .main { margin-left:0; width:100%; } }
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
        <a href="/food_ar_app/admin/menu/menu_manage.php"><span class="icon">🍽️</span> Foods</a>
        <div class="nav-section">Payment</div>
        <a href="/food_ar_app/admin/payment/view_payments.php"><span class="icon">💳</span> Payments</a>
        <div class="nav-section">Reservation</div>
        <a href="/food_ar_app/admin/reservation/reservation_manage.php"><span class="icon">📋</span> Bookings</a>
        <div class="nav-section">Orders</div>
        <a href="/food_ar_app/admin/order/view_orders.php"><span class="icon">📦</span> Orders</a>
        <div class="nav-section">FEEDBACK</div>
        <a href="/food_ar_app/admin/feedback/feedback_manage.php" class="active"><span class="icon">💬</span> Feedback</a>
    </nav>
</aside>

<audio id="notifSound" src="/food_ar_app/admin/sounds/notify.mp3" preload="auto"></audio>

<div class="main">
    <div class="topbar">
        <span class="page-title">Feedback Management</span>
        <div class="user-controls">
            <div class="notif-trigger" onclick="toggleNotifications()">
                🔔 <span id="notifCount" class="notif-badge">0</span>
                <div id="notifList" class="notif-box"></div>
            </div>
            <a href="/food_ar_app/admin/index.php" class="user-pill">
                <div class="avatar"><?php echo strtoupper(substr($admin_name, 0, 2)); ?></div>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="content">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Average Rating</div>
                <div class="value" style="color:var(--accent);"><?= number_format($stats['AvgRating'], 1) ?> <small style="font-size:1rem; color:#ccc;">/ 5.0</small></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Reviews</div>
                <div class="value"><?= $stats['Total'] ?></div>
            </div>
        </div>

        <div class="filter-card">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Search Comments</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Keywords...">
                </div>
                <div class="filter-group">
                    <label>Rating</label>
                    <select name="rating">
                        <option value="">All Ratings</option>
                        <option value="5" <?= $filter_rating == '5' ? 'selected' : '' ?>>5 Stars</option>
                        <option value="4" <?= $filter_rating == '4' ? 'selected' : '' ?>>4 Stars</option>
                        <option value="1" <?= $filter_rating == '1' ? 'selected' : '' ?>>1 Star (Critical)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        <option value="Food" <?= $filter_cat == 'Food' ? 'selected' : '' ?>>Food</option>
                        <option value="Service" <?= $filter_cat == 'Service' ? 'selected' : '' ?>>Service</option>
                    </select>
                </div>
                <button type="submit" class="btn-filter">Apply Filter</button>
                <a href="feedback_manage.php" style="color:var(--primary); font-size:0.8rem; text-decoration:none; font-weight:600;">Reset</a>
            </form>
        </div>

        <div class="card-table">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Category</th>
                        <th>Rating</th>
                        <th>Feedback</th>
                        <th style="width: 30%;">Admin Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fb = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td>
                            <div style="font-weight:700; color:var(--text);"><?= htmlspecialchars($fb['FullName']) ?></div>
                            <div style="font-size:0.75rem; color:#999; margin-top:4px;"><?= $fb['CreatedAt']->format('M d, Y') ?></div>
                        </td>
                        <td><span style="font-size:0.7rem; font-weight:800; color:var(--accent);"><?= strtoupper($fb['Category']) ?></span></td>
                        <td class="stars"><?= str_repeat('★', $fb['Rating']) . str_repeat('☆', 5 - $fb['Rating']) ?></td>
                        <td style="line-height:1.6; color:#555;">
                            <em>"<?= htmlspecialchars($fb['Comments']) ?>"</em>
                        </td>
                        <td>
                            <?php if ($fb['Response']): ?>
                                <div class="response-box">
                                    <strong style="display:block; margin-bottom:5px; color:var(--sidebar);">Our Response:</strong>
                                    <?= htmlspecialchars($fb['Response']) ?>
                                </div>
                            <?php else: ?>
                                <form method="post">
                                    <input type="hidden" name="feedback_id" value="<?= $fb['FeedbackID'] ?>">
                                    <textarea name="response" placeholder="Type your reply..." required></textarea>
                                    <button type="submit" class="btn-submit-res">Submit Response</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align:center; padding:40px; color:#bbb; font-size:0.8rem;">
            © <?= date("Y"); ?> Carrie's Cafe & Bakery System • All Rights Reserved
        </div>
    </div>
</div>

<script>
let lastCount = 0;
async function loadNotifications() {
    try {
        const response = await fetch('/food_ar_app/admin/notification/fetch_admin_notifications.php');
        const data = await response.json();
        let html = `<div style="padding:15px; font-weight:bold; border-bottom:1px solid #eee;">Recent Updates</div>`;
        let unread = 0;
        data.forEach(n => {
            if (n.IsRead == 0) unread++;
            html += `<div class="notif-item ${n.IsRead == 0 ? 'unread' : ''}">
                <div style="font-weight:600">${n.FullName}</div>
                <div style="color:#666">${n.Message}</div>
            </div>`;
        });
        document.getElementById("notifList").innerHTML = html || '<div style="padding:20px;">No new notifications</div>';
        document.getElementById("notifCount").innerText = unread;
        document.getElementById("notifCount").style.display = unread > 0 ? 'block' : 'none';
        
        if (unread > lastCount) {
            const sound = document.getElementById("notifSound");
            if(sound) sound.play().catch(e => console.log("Audio play blocked"));
        }
        lastCount = unread;
    } catch (e) {}
}

function toggleNotifications() {
    const list = document.getElementById("notifList");
    list.style.display = list.style.display === "block" ? "none" : "block";
    if(list.style.display === "block") {
        fetch('/food_ar_app/admin/notification/mark_notifications_read.php');
    }
}

loadNotifications();
setInterval(loadNotifications, 5000);
</script>

</body>
</html>