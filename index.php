<?php
session_start();
if(!isset($_SESSION['user']))
{
	header("Location: login.php");
	die();
}

echo "INDEX";
?>

<a href="logout.php">Logout</a>