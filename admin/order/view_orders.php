<?php
session_start();
include('C:/xampp/htdocs/food_ar_app/includes/db.php');

// Admin only
if (!isset($_SESSION['admin_id'])) {
    header("Location: /food_ar_app/admin/index.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin";

// ======= HANDLE STATUS UPDATES =======
if(isset($_GET['action'], $_GET['id'])){
    $orderID = intval($_GET['id']);
    $action = $_GET['action'];
    $validStatuses = ['Accepted','Declined','Completed'];

    if(in_array($action, $validStatuses)){
        $sql = "UPDATE Orders SET Status = ?, UpdatedAt = GETDATE() WHERE OrderID = ?";
        $params = [$action, $orderID];
        $stmt = sqlsrv_query($conn, $sql, $params);
        
        if($stmt){
            // Fetch UserID for notification
            $sql_user = "SELECT UserID FROM Orders WHERE OrderID = ?";
            $stmt_user = sqlsrv_query($conn, $sql_user, [$orderID]);
            $row_user = sqlsrv_fetch_array($stmt_user, SQLSRV_FETCH_ASSOC);
            $customer_id = $row_user['UserID'] ?? 0;

            if($customer_id){
                $message = "Your order #$orderID status has been updated to '$action'.";
                $sql_notify = "INSERT INTO Notifications (UserID, Message, TargetRole, NotificationType) VALUES (?, ?, ?, ?)";
                sqlsrv_query($conn, $sql_notify, [$customer_id, $message, 'Customer', 'Order']);
            }
            $_SESSION['success'] = "Order #$orderID updated to '$action'.";
        } else {
            $_SESSION['error'] = "Failed to update order #$orderID.";
        }
    }
    header("Location: /food_ar_app/admin/order/view_orders.php");
    exit();
}

// ======= FETCH ORDERS =======
$sql = "
    SELECT o.OrderID, o.OrderDate, o.Status, o.TotalAmount, p.PaymentStatus, u.FullName
    FROM Orders o
    JOIN Users u ON o.UserID = u.UserID
    LEFT JOIN Payments p ON o.OrderID = p.OrderID
    ORDER BY o.OrderDate DESC
";
$stmt = sqlsrv_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | Carrie's Cafe</title>
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
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #2980b9;
            --radius: 16px;
            --shadow: 0 10px 30px rgba(44,26,14,0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; min-height: 100vh; background: var(--bg); color: var(--text); }

        /* SIDEBAR */
        .sidebar { width: 260px; background: var(--sidebar); position: fixed; height: 100vh; z-index: 1000; display: flex; flex-direction: column; }
        .sidebar-logo { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-logo img { width: 140px; border-radius: 10px; margin-bottom: 8px; }
        .nav-section { color: rgba(255,255,255,0.3); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 2px; padding: 20px 25px 5px; }
        .sidebar nav a { 
            display: flex; align-items: center; gap: 15px; padding: 12px 25px; color: rgba(255,255,255,0.6); 
            text-decoration: none; font-size: 0.9rem; transition: 0.3s; border-left: 4px solid transparent;
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,0.05); color: #fff; border-left-color: var(--accent); }

        /* MAIN */
        .main { margin-left: 260px; flex: 1; display: flex; flex-direction: column; width: calc(100% - 260px); }
        .topbar { 
            background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); 
            padding: 15px 40px; display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 900; border-bottom: 1px solid var(--border);
        }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 700; }
        .user-pill { 
            background: #fff; border: 1px solid var(--border); border-radius: 50px; padding: 5px 15px 5px 5px;
            display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.85rem; text-decoration: none; color: var(--text);
        }
        .avatar { width: 32px; height: 32px; background: var(--sidebar); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        .content { padding: 40px; }

        /* DATA TABLE */
        .table-container { background: #fff; border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #fdfaf8; padding: 18px 20px; font-size: 0.75rem; text-transform: uppercase; color: #888; text-align: left; border-bottom: 1px solid var(--border); }
        td { padding: 18px 20px; border-bottom: 1px solid var(--border); font-size: 0.9rem; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #fefefe; }

        /* BADGES */
        .badge { padding: 6px 14px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
        .status-Pending { background: #fff8e1; color: #f57f17; }
        .status-Accepted { background: #e8f5e9; color: #2e7d32; }
        .status-Declined { background: #ffebee; color: #c62828; }
        .status-Completed { background: #e3f2fd; color: #1565c0; }
        
        .pay-status { font-size: 0.7rem; font-weight: 600; color: #999; text-transform: uppercase; display: block; margin-top: 4px; }
        .pay-paid { color: var(--success); }

        /* ACTIONS */
        .action-btns { display: flex; gap: 8px; justify-content: flex-start; }
        .btn { padding: 8px 12px; border-radius: 8px; text-decoration: none; font-size: 0.8rem; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-view { background: var(--bg); color: var(--text); border: 1px solid var(--border); }
        .btn-view:hover { background: var(--border); }
        .btn-accept { background: #e8f5e9; color: #2e7d32; }
        .btn-decline { background: #ffebee; color: #c62828; }
        .btn-complete { background: var(--sidebar); color: #fff; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 25px; font-size: 0.9rem; font-weight: 500; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

        .footer { text-align: center; padding: 40px; opacity: 0.4; font-size: 0.8rem; letter-spacing: 1px; }

        @media (max-width: 1000px) { .sidebar { display: none; } .main { margin-left: 0; width: 100%; } }
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
        <span class="page-title">Order Management</span>
        <a href="/food_ar_app/admin/index.php" class="user-pill">
            <div class="avatar"><?php echo strtoupper(substr($admin_name,0,2)); ?></div>
            <span>Logout</span>
        </a>
    </div>

    <div class="content">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order Details</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Total Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td>
                            <span style="font-weight:700; color:var(--sidebar);">#<?= $row['OrderID'] ?></span>
                            <div style="font-size:0.75rem; color:#999; margin-top:4px;">
                                <i class="far fa-clock"></i> <?= $row['OrderDate'] ? $row['OrderDate']->format('M d, H:i') : '' ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($row['FullName']) ?></div>
                        </td>
                        <td>
                            <span class="badge status-<?= $row['Status'] ?>"><?= $row['Status'] ?></span>
                            <span class="pay-status <?= ($row['PaymentStatus'] == 'Paid') ? 'pay-paid' : '' ?>">
                                <?= $row['PaymentStatus'] ?? 'Unpaid' ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight:700; color:var(--text);">LKR <?= number_format($row['TotalAmount'], 2) ?></span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="/food_ar_app/admin/order/order_view.php?id=<?= $row['OrderID'] ?>" class="btn btn-view" title="View Items">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if($row['Status'] == 'Pending'): ?>
                                    <a href="?id=<?= $row['OrderID'] ?>&action=Accepted" class="btn btn-accept" onclick="return confirm('Accept this order?')">Accept</a>
                                    <a href="?id=<?= $row['OrderID'] ?>&action=Declined" class="btn btn-decline" onclick="return confirm('Decline this order?')">Decline</a>
                                <?php elseif($row['Status'] == 'Accepted'): ?>
                                    <a href="?id=<?= $row['OrderID'] ?>&action=Completed" class="btn btn-complete" onclick="return confirm('Mark as Completed?')">Complete</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">© <?= date("Y"); ?> CARRIE'S CAFE & BAKERY SYSTEM</div>
    </div>
</div>

</body>
</html>
