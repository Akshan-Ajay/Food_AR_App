<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: /food_ar_app/admin/index.php");
    exit();
}
include('C:/xampp/htdocs/food_ar_app/includes/db.php');

$admin_name = $_SESSION['admin_name'] ?? "Admin";

$tables = [];
$sql = "SELECT t.TableID, t.TableName, s.SeatID, s.SeatNumber 
        FROM CafeTables t
        LEFT JOIN TableSeats s ON t.TableID = s.TableID
        ORDER BY t.TableID, s.SeatID";
$stmt = sqlsrv_query($conn, $sql);

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $tables[$row['TableID']]['name'] = $row['TableName'];
    if($row['SeatNumber']){
        $tables[$row['TableID']]['seats'][] = $row['SeatNumber'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations | Carrie's Cafe</title>
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

        /* CARDS & FORMS */
        .grid-forms { display: grid; grid-template-columns: 1fr 1.5fr; gap: 25px; margin-bottom: 30px; }
        .card { background: var(--card); padding: 25px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); }
        .card h3 { font-family: 'Playfair Display', serif; margin-bottom: 20px; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        input, select { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--bg); font-size: 0.9rem; outline: none; transition: 0.3s; }
        input:focus, select:focus { border-color: var(--accent); background: #fff; }
        
        .btn-main { width: 100%; padding: 12px; border: none; border-radius: 10px; background: var(--sidebar); color: #fff; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-main:hover { background: var(--accent); transform: translateY(-2px); }

        /* FLOOR PLAN VISUALS */
        .tables-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .table-card { 
            background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; 
            text-align: center; transition: 0.3s; position: relative;
        }
        .table-card:hover { transform: translateY(-5px); box-shadow: var(--shadow); }
        .table-card strong { display: block; margin-bottom: 15px; font-family: 'Playfair Display', serif; font-size: 1.1rem; }
        
        .delete-btn { 
            background: #fff; border: 1px solid var(--border); color: var(--danger); 
            padding: 5px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 700; 
            cursor: pointer; margin-top: 15px; transition: 0.3s;
        }
        .delete-btn:hover { background: var(--danger); color: #fff; border-color: var(--danger); }

        .seat {
            width: 35px; height: 35px; line-height: 35px; display: inline-block; margin: 4px;
            border-radius: 50%; font-size: 0.75rem; font-weight: 700; border: 1px solid transparent;
        }
        .seat.available { background: #e8f5e9; color: #2e7d32; border-color: #c8e6c9; }
        .seat.pending { background: #fff8e1; color: #f57f17; border-color: #ffecb3; }
        .seat.accepted { background: #e3f2fd; color: #1565c0; border-color: #bbdefb; }
        .seat.booked { background: #ffebee; color: #c62828; border-color: #ffcdd2; }

        /* DATA TABLE */
        .table-container { background: #fff; border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #fdfaf8; padding: 15px; font-size: 0.75rem; text-transform: uppercase; color: #888; text-align: left; }
        td { padding: 15px; border-top: 1px solid var(--border); font-size: 0.9rem; }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .action-icon { cursor: pointer; padding: 5px; margin: 0 3px; border-radius: 5px; transition: 0.2s; border: none; font-size: 0.9rem; }
        .btn-check { background: #e8f5e9; color: #2e7d32; }
        .btn-x { background: #fff3e0; color: #e65100; }
        .btn-trash { background: #ffebee; color: #c62828; }

        @media (max-width: 1100px) { .grid-forms { grid-template-columns: 1fr; } .sidebar { display: none; } .main { margin-left: 0; width: 100%; } }
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
        <span class="page-title">Reservation Management</span>
        <a href="/food_ar_app/admin/index.php" class="user-pill">
            <div class="avatar"><?php echo strtoupper(substr($admin_name,0,2)); ?></div>
            <span>Logout</span>
        </a>
    </div>

    <div class="content">
        <div class="grid-forms">
            <div class="card">
                <h3><i class="fas fa-plus-circle"></i> Setup Floor</h3>
                <form id="addTableForm">
                    <input type="text" name="table_name" placeholder="Table Name (e.g. Garden 01)" required style="margin-bottom:15px;">
                    <input type="number" name="seat_count" placeholder="Seat Count" min="1" required style="margin-bottom:15px;">
                    <button class="btn-main">Add to Floor Plan</button>
                </form>
                <div id="table_msg" style="margin-top:10px; font-size:0.8rem;"></div>
            </div>

            <div class="card">
                <h3><i class="fas fa-walking"></i> Walk-in Guest</h3>
                <form id="walkinForm">
                    <div class="form-row">
                        <input type="text" name="FullName" placeholder="Guest Name" required>
                        <input type="email" name="Email" placeholder="Email Address" required>
                    </div>
                    <div class="form-row" style="grid-template-columns: 1fr 1fr 1.5fr;">
                        <input type="date" name="Date" id="walkin_date" value="<?= date('Y-m-d') ?>" required>
                        <input type="time" name="Time" id="walkin_time" value="12:00" required>
                        <select name="SeatNumber" id="walkin_seat" required>
                            <option value="">Select Seat/Table</option>
                            <?php foreach($tables as $tableID => $t): ?>
                                <option value="table_<?= $tableID ?>">Full Table: <?= $t['name'] ?></option>
                                <?php foreach($t['seats'] ?? [] as $seat): ?>
                                    <option value="<?= $seat ?>"><?= $seat ?> (<?= $t['name'] ?>)</option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-main" style="background:var(--accent);">Confirm Booking</button>
                </form>
                <div id="walkin_msg" style="margin-top:10px; font-size:0.8rem;"></div>
            </div>
        </div>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin-bottom:0;">Live Table Status</h3>
                <input type="date" id="reservation_date" value="<?= date('Y-m-d') ?>" style="width:180px;">
            </div>
            
            <div class="tables-grid">
                <?php foreach($tables as $tableID=>$t): ?>
                <div class="table-card" data-table-id="<?= $tableID ?>">
                    <strong><?= $t['name'] ?></strong>
                    <div style="display:flex; flex-wrap:wrap; justify-content:center;">
                        <?php foreach($t['seats'] ?? [] as $seat): ?>
                        <div class="seat available" data-seat="<?= $seat ?>"><?= substr($seat, -2) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <button class="delete-btn" onclick="deleteTable(<?= $tableID ?>)">
                        <i class="fas fa-trash-alt"></i> REMOVE TABLE
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="table-container">
            <table id="reservationTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Seat</th>
                        <th>Guest Details</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadReservations(){
    const dateVal = document.getElementById('reservation_date').value;
    if(!dateVal) return;

    const resp = await fetch('/food_ar_app/admin/reservation/admin_fetch_reservations.php?date='+encodeURIComponent(dateVal));
    const data = await resp.json();

    document.querySelectorAll('.seat').forEach(seat=>{
        seat.className = 'seat available';
        const key = seat.dataset.seat;
        if(data[key]){
            const res = data[key];
            if(res.Status=='Pending') seat.className = 'seat pending';
            else if(res.Status=='Accepted') seat.className = 'seat accepted';
            else if(res.Status=='Declined' || res.Status=='Booked') seat.className = 'seat booked';
        }
    });

    const tbody = document.querySelector('#reservationTable tbody');
    tbody.innerHTML='';
    for(const key in data){
        const r=data[key];
        const statusClass = r.Status.toLowerCase();
        const tr=document.createElement('tr');
        tr.innerHTML=`
            <td style="font-weight:600; color:#aaa;">#${r.ReservationID}</td>
            <td><b style="color:var(--accent);">${r.SeatNumber}</b></td>
            <td>
                <div style="font-weight:600;">${r.FullName ?? r.CustomerName ?? 'N/A'}</div>
                <div style="font-size:0.75rem; color:#999;">${r.Email ?? ''}</div>
            </td>
            <td>${r.ReservationDate}</td>
            <td><span class="status-badge seat ${statusClass}">${r.Status}</span></td>
            <td>
                ${r.Status=='Pending'? `
                    <button class="action-icon btn-check" onclick="updateStatus(${r.ReservationID},'Accepted')"><i class="fas fa-check"></i></button>
                    <button class="action-icon btn-x" onclick="updateStatus(${r.ReservationID},'Declined')"><i class="fas fa-times"></i></button>
                `:''}
                <button class="action-icon btn-trash" onclick="updateStatus(${r.ReservationID},'Delete')"><i class="fas fa-trash"></i></button>
            </td>`;
        tbody.appendChild(tr);
    }
}

async function updateStatus(id,action){
    if(action === 'Delete' && !confirm('Permanently remove this booking?')) return;
    const fd=new FormData();
    fd.append('id',id);
    fd.append('action',action);
    await fetch('/food_ar_app/admin/reservation/admin_update_reservation.php',{method:'POST',body:fd});
    loadReservations();
}

document.getElementById('walkinForm').addEventListener('submit', async e=>{
    e.preventDefault();
    const fd=new FormData(e.target);
    const r=await fetch('/food_ar_app/admin/reservation/admin_add_walkin.php',{method:'POST',body:fd});
    const text=await r.text();
    document.getElementById('walkin_msg').innerText=text;
    if(text.includes('OK')){ e.target.reset(); loadReservations(); }
});

document.getElementById('addTableForm').addEventListener('submit', async e=>{
    e.preventDefault();
    const fd=new FormData(e.target);
    const r=await fetch('/food_ar_app/admin/reservation/admin_add_table.php',{method:'POST',body:fd});
    const text=await r.text();
    if(text.includes('OK')) location.reload();
    else document.getElementById('table_msg').innerText=text;
});

async function deleteTable(id){
    if(!confirm('Delete this table and all associated seats?')) return;
    const fd=new FormData();
    fd.append('table_id',id);
    const r=await fetch('/food_ar_app/admin/reservation/admin_delete_table.php',{method:'POST',body:fd});
    const text=await r.text();
    if(text.includes('Deleted')) location.reload();
}

document.getElementById('reservation_date').addEventListener('change', loadReservations);
setInterval(loadReservations, 5000);
window.addEventListener('load', loadReservations);
</script>
</body>
</html>