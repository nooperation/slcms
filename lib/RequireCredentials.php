<?php
session_start();
if(!isset($_SESSION['user']))
{
	header("Location: login.php");
	die();
}

$user = $_SESSION['user'];
$userId = $user['id'];
$userName = $user['name'];