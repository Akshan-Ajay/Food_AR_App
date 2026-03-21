<?php
session_start();
include('includes/header.php');
?>

<h2>Welcome to Food AR App</h2>
<p>This is a demo web application for ordering food and viewing it in Augmented Reality.</p>

<div>
    <a href="admin/index.php">Admin Login</a> | 
    <a href="user/index.php">Customer Menu</a>
</div>

<?php include('includes/footer.php'); ?>
