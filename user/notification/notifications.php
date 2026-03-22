<?php
session_start();
include('../includes/db.php');

if(!isset($_SESSION['customer_id'])){
    header("Location:./../user/index.php");
    exit();
}

$user_id = $_SESSION['customer_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Notifications</title>
    <style>
    body {
        font-family: "Open Sans", sans-serif;
        color: #0c0c0c;
        background-color: #f7f7f7;
        margin: 0;
        padding: 0;
    }

    .hero_area {
        position: relative;
        min-height: 25vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background: url('reserve-hero.png') center/cover no-repeat;
        text-align: center;
        padding: 40px 20px;
        color: #1a0202de;
    }

    .hero_area h1 {
        font-family: 'Dancing Script', cursive;
        font-size: 2.8rem;
        margin-bottom: 10px;
        text-shadow: 2px 2px 6px #00000080;
    }

    .notifications-container {
        max-width: 900px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .notification-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: transform 0.3s ease;
    }

    .notification-card:hover {
        transform: translateY(-5px);
    }

    .notification-message {
        font-size: 1rem;
        color: #1a0202de;
    }

    .notification-date {
        font-size: 0.85rem;
        color: #666;
        margin-left: 15px;
    }

    .notification-unread {
        background-color: #ffc107;
    }

    .mark-read-btn {
        background-color: #1a0202de;
        color: #fff;
        border: none;
        padding: 8px 15px;
        border-radius: 25px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .mark-read-btn:hover {
        background-color: #ffbe33;
        color: #1a0202de;
    }

    @media(max-width:768px){
        .hero_area h1 { font-size: 2.2rem; }
        .notification-card { flex-direction: column; align-items: flex-start; }
        .notification-date { margin-left: 0; margin-top: 8px; }
        .mark-read-btn { margin-top: 8px; }
    }
    </style>
</head>
<body>

<div class="hero_area">
    <h1>Your Notifications</h1>
    <p>Stay updated with your orders and reservations</p>
</div>

<div class="notifications-container" id="notificationsContainer">
    <!-- Notifications will load here -->
</div>

<script>
async function loadNotifications(){
    const resp = await fetch('fetch_notifications.php');
    const data = await resp.json();
    const container = document.getElementById('notificationsContainer');
    container.innerHTML = '';

    data.forEach(notif => {
        const card = document.createElement('div');
        card.classList.add('notification-card');
        if(notif.IsRead == 0) card.classList.add('notification-unread');

        const message = document.createElement('div');
        message.classList.add('notification-message');
        message.innerHTML = notif.Message;

        const date = document.createElement('div');
        date.classList.add('notification-date');
        date.innerHTML = new Date(notif.CreatedAt).toLocaleString();

        const btn = document.createElement('button');
        btn.classList.add('mark-read-btn');
        btn.innerHTML = 'Mark as Read';
        btn.onclick = async () => {
            await fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'notificationID=' + notif.NotificationID
            });
            loadNotifications();
        };

        card.appendChild(message);
        card.appendChild(date);
        card.appendChild(btn);

        container.appendChild(card);
    });
}

// Load notifications on page load and refresh every 5 seconds
loadNotifications();
setInterval(loadNotifications, 5000);
</script>

</body>
</html>