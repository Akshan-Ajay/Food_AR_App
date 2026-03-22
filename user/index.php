<?php 
session_start();
include('../includes/db.php');

$error = '';

if(isset($_POST['login'])){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Note: In a production app, use password_verify() instead of direct string comparison
    $sql = "SELECT UserID, Email FROM Users WHERE Email = ? AND PasswordHash = ? AND Role = 'Customer'";
    $params = array($email, $password);

    $stmt = sqlsrv_query($conn, $sql, $params);
    if($stmt === false){ die(print_r(sqlsrv_errors(), true)); }

    if($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $_SESSION['customer_id'] = $row['UserID'];
        $_SESSION['customer_email'] = $row['Email'];

        echo "<script>
            localStorage.setItem('loggedIn', 'true'); 
            window.location.href='./../user/dashboard/dashboard.php';
        </script>";
        exit();
    } else { $error = "Invalid email or password!"; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrie's Cafe | Premium Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { --bg-dark: #1a120b; --glass: rgba(26, 18, 11, 0.85); --accent: #d4a373; --text-main: #f5ebe0; --input-bg: rgba(255, 255, 255, 0.07); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('login_image.png'); background-size: cover; background-position: center; background-attachment: fixed; color: var(--text-main); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { width: 90%; max-width: 950px; display: flex; background: var(--glass); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 30px; overflow: hidden; box-shadow: 0 40px 100px rgba(0,0,0,0.8); }
        .brand-section { flex: 1; padding: 60px; display: flex; flex-direction: column; justify-content: center; align-items: center; background: rgba(0, 0, 0, 0.3); text-align: center; border-right: 1px solid rgba(255, 255, 255, 0.05); }
        .logo-wrap img { width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--accent); padding: 5px; object-fit: cover; background: var(--bg-dark); }
        .brand-section h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; color: var(--accent); margin-top: 20px; }
        .form-section { flex: 1.2; padding: 60px; }
        .form-section h2 { font-family: 'Playfair Display', serif; font-size: 1.8rem; margin-bottom: 30px; text-align: center; }
        .input-group { margin-bottom: 25px; position: relative; }
        .input-group label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--accent); margin-bottom: 8px; display: block; }
        .input-group input { width: 100%; padding: 15px 20px; background: var(--input-bg); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; color: white; outline: none; }
        .btn-submit { width: 100%; padding: 16px; background: var(--accent); color: #1a120b; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.4s; }
        .btn-submit:hover { background: #e5b383; transform: translateY(-2px); }
        .error-msg { background: rgba(255, 107, 107, 0.2); border: 1px solid #ff6b6b; color: #ff6b6b; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 0.85rem; }
        .reg-link { margin-top: 25px; text-align: center; font-size: 0.9rem; }
        .reg-link a { color: var(--accent); text-decoration: none; font-weight: 500; transition: 0.3s; }
        .reg-link a:hover { text-decoration: underline; opacity: 0.8; }
        @media (max-width: 850px) { .container { flex-direction: column; } }
        #loader{ position:fixed; top:0;left:0;width:100%;height:100%; background:rgba(0,0,0,0.85); display:none; justify-content:center; align-items:center; flex-direction:column; z-index:9999; }
    </style>
</head>
<body>

<div class="container">
    <div class="brand-section">
        <div class="logo-wrap"><img src="./../user/Lor.png" alt="Logo"></div>
        <h1>Carrie's Cafe</h1>
        <p style="opacity:0.7; font-size: 0.9rem; margin-top:10px;">Artisanal Brews & AR Experiences</p>
    </div>

    <div class="form-section">
        <h2>Welcome Back</h2>
        <?php if($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" id="password" required>
                <span onclick="toggleView()" style="position:absolute; right:15px; top:42px; cursor:pointer;">👁</span>
            </div>
            <button type="submit" name="login" class="btn-submit">Sign In</button>
            
            <div class="reg-link">
                <span style="opacity:0.6">Don't have an account?</span>
                <a href="register.php">Create Account</a>
            </div>
        </form>
    </div>
</div>

<div id="loader">Logging in...</div>

<script>
    function toggleView() {
        const p = document.getElementById('password');
        p.type = p.type === 'password' ? 'text' : 'password';
    }
    document.getElementById("loginForm").addEventListener("submit", () => {
        document.getElementById("loader").style.display = "flex";
    });
</script>
</body>
</html>