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

if(!isset($_GET['publicToken']))
{
	die("Missing publicToken");
}
$publicToken = @hex2bin($_GET["publicToken"]);
if(!$publicToken)
{
	http_response_code("500");
	LogAndEcho("Invalid publicToken");
	die();
}

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

$serverAddress = $db->GetServerAddress($publicToken);

$result = @file_get_contents($serverAddress . "?path=/Base/Confirm");

echo json_encode($result == "OK.");
