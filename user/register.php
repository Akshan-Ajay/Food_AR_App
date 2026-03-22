<?php 
session_start();
include('../includes/db.php');

$message = "";
$error = "";

if(isset($_POST['register'])){
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $password = trim($_POST['password']);

    if(!$fullname || !$username || !$email || !$password){
        $error = "Please fill all required fields.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO Users (FullName, ContactNumber, Address, Username, PasswordHash, Role, Email) VALUES (?, ?, ?, ?, ?, 'Customer', ?)";
        $params = [$fullname, $phone, $address, $username, $hashedPassword, $email];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if($stmt){
            $message = "Registration successful! You can now login.";
        } else {
            $error = "Registration failed. Username or email may already exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Carrie's Cafe | Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #1a120b;
            --glass: rgba(26, 18, 11, 0.85);
            --accent: #d4a373;
            --text-main: #f5ebe0;
            --input-bg: rgba(255, 255, 255, 0.07);
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('login_image.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            /* Prevents the steam from causing scrollbars */
            overflow-x: hidden; 
            position: relative;
        }

        /* Container */
        .container {
            position: relative;
            z-index: 10; /* Higher than steam */
            width: 100%;
            max-width: 950px;
            display: flex;
            background: var(--glass);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 40px 100px rgba(0,0,0,0.8);
        }

        .brand-section {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: rgba(0, 0, 0, 0.3);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .logo-wrap img {
            width: 80px; height: 80px;
            border-radius: 50%;
            border: 2px solid var(--accent);
            padding: 5px;
            object-fit: cover;
            margin-bottom: 20px;
        }

        .brand-section h1 { font-family: 'Playfair Display', serif; font-size: 2rem; color: var(--accent); }

        .form-section { flex: 1.5; padding: 50px; }
        .form-section h2 { font-family: 'Playfair Display', serif; font-size: 1.8rem; margin-bottom: 25px; text-align: center; }

        .grid-form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }

        .input-group label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 5px;
            display: block;
        }

        .input-group input {
            width: 100%;
            padding: 15px;
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            outline: none;
            transition: 0.3s;
        }

        .input-group input:focus { border-color: var(--accent); background: rgba(255, 255, 255, 0.12); }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: var(--accent);
            color: var(--bg-dark);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }

        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 0.85rem; }
        .error { background: rgba(255, 107, 107, 0.2); border: 1px solid #ff6b6b; color: #ff6b6b; }
        .success { background: rgba(144, 238, 144, 0.2); border: 1px solid #90ee90; color: #90ee90; }

        /* Fixed Steam CSS */
        #steam-container {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        .steam {
            position: absolute;
            background: white;
            border-radius: 50%;
            filter: blur(10px);
            opacity: 0;
        }

        @media (max-width: 850px) {
            .container { flex-direction: column; border-radius: 20px; }
            .grid-form { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .brand-section { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.05); }
        }
    </style>
</head>
<body>

<div id="steam-container"></div>

<div class="container">
    <div class="brand-section">
        <div class="logo-wrap"><img src="./../user/Lor.png" alt="Logo"></div>
        <h1>Create Account</h1>
        <p>Join our community for exclusive AR dining features.</p>
    </div>

    <div class="form-section">
        <?php if($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST">
            <div class="grid-form">
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" placeholder="John Doe" required>
                </div>
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="johndoe88" required>
                </div>
                <div class="input-group full-width">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="john@example.com" required>
                </div>
                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="0712345678">
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="input-group full-width">
                    <label>Home Address</label>
                    <input type="text" name="address" placeholder="123 Coffee Lane, Colombo">
                </div>
            </div>

            <button type="submit" name="register" class="btn-submit">Create Account</button>

            <p style="text-align:center; margin-top:20px; font-size:0.8rem; opacity:0.7;">
                Already have an account? <a href="index.php" style="color:var(--accent); text-decoration:none; font-weight:600;">Sign In</a>
            </p>
        </form>
    </div>
</div>

<script>
const container = document.getElementById('steam-container');

function createSteam() {
    const steam = document.createElement('div');
    steam.className = 'steam';
    const size = Math.random() * 40 + 20;
    
    steam.style.width = size + 'px';
    steam.style.height = size + 'px';
    steam.style.left = Math.random() * 100 + 'vw';
    steam.style.bottom = '-60px';
    
    container.appendChild(steam);

    steam.animate([
        { transform: 'translateY(0) scale(1)', opacity: 0 },
        { opacity: 0.15, offset: 0.2 },
        { transform: `translateY(-110vh) translateX(${Math.random() * 200 - 100}px) scale(3)`, opacity: 0 }
    ], {
        duration: Math.random() * 4000 + 7000,
        easing: 'ease-out'
    }).onfinish = () => steam.remove();
}

// Slightly slower interval to save CPU
setInterval(createSteam, 800);
</script>

</body>
</html>