<?php
session_start();
if(!isset($_SESSION['user']))
{
	header("Location: index.php");
	die();
}
$user = $_SESSION['user'];
$userId = $user['id'];
$userName = $user['name'];

include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");

try
{
	$db = new BaseServerDatabase();
	$db->ConnectToDatabase();
}
catch(Exception $ex)
{
	http_response_code("500");
	LogAndEchoJson("Failed to connect to database. See log for details.", $ex->getMessage());
	die();
}

$servers = $db->GetServersForUser($userId);

echo json_encode($servers);