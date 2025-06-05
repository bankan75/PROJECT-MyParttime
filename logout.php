<?php
require_once 'includes/config.php';

// Logout user
$auth->logout();

// Redirect to login page
header("Location: login.php");
exit;
?>