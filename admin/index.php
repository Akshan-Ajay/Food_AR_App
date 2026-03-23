<?php
session_start();
include('../includes/db.php');

$error = '';

if(isset($_POST['email']) && isset($_POST['password'])){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $sql = "SELECT UserID, Email, FullName
            FROM Users
            WHERE Email = ?
              AND PasswordHash = ?
              AND Role = 'Admin'";

    $params = array($email, $password);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if($stmt === false){
        die(print_r(sqlsrv_errors(), true));
    }

    if($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $_SESSION['admin_id'] = $row['UserID'];
        $_SESSION['admin_email'] = $row['Email'];
        $_SESSION['admin_name'] = $row['FullName'];

        header("Location:./../admin/dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrie's Cafe — Admin Access</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #c0392b; 
            --accent: #e67e22;  /* Burnt Orange */
            --bg: #fdfaf8;      /* Creamy Dashboard BG */
            --sidebar: #1e120a; /* Deep Espresso Sidebar */
            --text: #3b2314;    /* Dark Coffee */
            --border: #ece0d1;
            --white: #ffffff;
            --shadow: 0 30px 60px rgba(30, 18, 10, 0.1);
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body { 
            height:100vh; 
            font-family:'Inter', sans-serif; 
            background: var(--bg); 
            color: var(--text); 
            overflow: hidden;
            display: flex;
        }

        .page-wrap { display: flex; width: 100%; height: 100vh; }

        /* --- LEFT PANEL: PRIMARY BRANDING --- */
        .left-panel { 
            flex: 1.2; 
            background: var(--sidebar); 
            padding: 80px; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            position: relative;
            color: var(--white);
        }

        .left-panel::after {
            content: '';
            position: absolute;
            bottom: 0; right: 0;
            width: 450px; height: 450px;
            background: radial-gradient(circle, rgba(230, 126, 34, 0.08), transparent 70%);
            pointer-events: none;
        }

        .logo-main {
            width: 160px; /* Slightly larger since it's the hero now */
            margin-bottom: 30px;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.4));
        }

        .brand-name { 
            font-family: 'Playfair Display', serif; 
            font-size: 56px; 
            font-weight: 900; 
            line-height: 1.1; 
            margin-bottom: 20px; 
        }

        .brand-name em { font-style: normal; color: var(--accent); }

        .feature-list { display: flex; flex-direction: column; gap: 25px; margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 40px; }
        .feature-item { display: flex; align-items: center; gap: 18px; }
        .feature-icon { 
            width: 44px; height: 44px; 
            background: rgba(255,255,255,0.03); 
            border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 12px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 18px; 
        }
        .feature-text-title { font-weight: 600; font-size: 15px; letter-spacing: 0.5px; }
        .feature-text-sub { font-size: 13px; color: rgba(255,255,255,0.5); margin-top: 2px; }

        /* --- RIGHT PANEL: LOGIN --- */
        .right-panel { 
            flex: 1; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            background: var(--bg);
            position: relative;
        }

        .login-card {
            width: 100%; 
            max-width: 420px;
            background: var(--white);
            padding: 60px;
            border-radius: 32px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            text-align: left; /* Switched to left for a cleaner input flow */
        }

        .login-header { margin-bottom: 40px; }
        .login-title { 
            font-family: 'Playfair Display', serif; 
            font-size: 32px; 
            font-weight: 700; 
            color: var(--sidebar);
            margin-bottom: 8px; 
        }
        .login-sub { font-size: 14px; color: #8b7361; font-weight: 400; }

        .form-group { margin-bottom: 22px; }
        .form-label { 
            display: block; 
            font-size: 10px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
            color: #a38f80; 
            margin-bottom: 8px; 
        }

        .form-input { 
            width: 100%; 
            background: #faf8f6; 
            border: 1px solid var(--border); 
            border-radius: 12px; 
            padding: 14px 18px; 
            color: var(--text); 
            font-size: 15px; 
            transition: all 0.3s ease; 
            outline: none;
        }

        .form-input:focus { 
            border-color: var(--accent); 
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(230, 126, 34, 0.06);
        }

        .btn-login { 
            width: 100%; 
            padding: 16px; 
            background: var(--sidebar); 
            color: var(--white); 
            font-weight: 600; 
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border: none; 
            border-radius: 12px; 
            cursor: pointer; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 12px;
        }

        .btn-login:hover { background: #000; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.15); }

        .login-alert { 
            background: #fff5f5; 
            border-radius: 10px;
            padding: 12px; 
            color: var(--primary); 
            font-size: 13px; 
            margin-bottom: 25px; 
            border: 1px solid rgba(192, 57, 43, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pw-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            opacity: 0.3;
            font-size: 18px;
            transition: opacity 0.2s;
        }
        .pw-toggle:hover { opacity: 1; }

        @media (max-width: 1024px) {
            .left-panel { display: none; }
            .login-card { box-shadow: none; border: none; background: transparent; }
        }
    </style>
</head>

<body>

<div class="page-wrap">
    <div class="left-panel">
        <img src="/food_ar_app/admin/Lor.png" alt="Carrie's Logo" class="logo-main">
        
        <div class="brand-area">
            <div class="brand-name">Carrie's <br><em>Cafe & Bakery</em></div>
        </div>

        <div class="feature-list">
            <div class="feature-item">
                <div class="feature-icon">📈</div>
                <div>
                    <div class="feature-text-title">Advanced Analytics</div>
                    <div class="feature-text-sub">Monitor your café performance in real-time.</div>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🕶️</div>
                <div>
                    <div class="feature-text-title">AR Asset Management</div>
                    <div class="feature-text-sub">Control interactive 3D menu visualizers.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="right-panel">
        <div class="login-card">
            <div class="login-header">
                <div class="login-title">Admin Access</div>
                <p class="login-sub">Welcome back. Please sign in to continue.</p>
            </div>

            <?php if($error): ?>
                <div class="login-alert">
                    <span>⚠️</span> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-input" name="email" placeholder="admin@carries.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div style="position:relative;">
                        <input type="password" class="form-input" name="password" id="adminPw" placeholder="••••••••" required>
                        <span class="pw-toggle" onclick="togglePassword()">👁️</span>
                    </div>
                </div>

                <button type="submit" class="btn-login">Sign In to Dashboard</button>
            </form>

            <div style="margin-top:40px; font-size:10px; color:#b5a499; letter-spacing:1px; text-transform:uppercase; font-weight:600;">
                &copy; 2026 Carrie's Cafe & Bakery · Admin Portal
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword(){
        const pwField = document.getElementById('adminPw');
        const toggleIcon = document.querySelector('.pw-toggle');
        if(pwField.type === 'password') {
            pwField.type = 'text';
            toggleIcon.innerText = '🙈';
        } else {
            pwField.type = 'password';
            toggleIcon.innerText = '👁️';
        }
    }
</script>

</body>
</html>