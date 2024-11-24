<?php
session_start(); // Start the session

// Destroy the session
session_unset();  
session_destroy(); 

// Redirect to login page
header("Location: login6.php");
exit;
?>
