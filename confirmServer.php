<?php
session_start();
include_once(dirname(__FILE__) . "/lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/lib/Utils.php");

if(!isset($_GET['authToken']))
{
	die("Missing authToken");
}
$authToken = @hex2bin($_GET["authToken"]);
if(!$authToken)
{
	http_response_code("500");
	LogAndEcho("Invalid authToken");
	die();
}

if(!isset($_SESSION['user']))
{
	$_SESSION['actionToRestoreOnLogin'] = 'confirmServer.php?authToken=' . $_GET['authToken'];
	header('Location: login.php');
	die();
}

$user = $_SESSION['user'];
$userId = $user['id'];
$userName = $user['name'];
unset($_SESSION['actionToRestoreOnLogin']);

try
{
	$db = new BaseServerDatabase();
	$db->ConnectToDatabase();
}
catch(Exception $ex)
{
	http_response_code("500");
	LogToFile("confirmServer.php|Failed to connect to database: ", $ex->getMessage());
	die("Failed to connect to database.");
}

function AttemptToClaimServer($authToken, $userId, $serverType)
{
	global $db;

	try
	{
		$result = $db->InitServer($authToken, $userId, $serverType);
	}
	catch(Exception $ex)
	{
		LogToFile("confirmServer.php|Failed to InitServer", $ex->getMessage());
		return false;
	}

	return $result;
}


echo "Confirming we can read from server...</br>";

$serverAddress = $db->GetServerAddressPrivate($authToken);
if(!$serverAddress)
{
	die("Failed to get server address!");
}

echo "Address: " . $serverAddress . "<br />";

$serverResponse = @file_get_contents($serverAddress . "?path=/Base/Confirm");
if($serverResponse == "OK.")
{
	if(@file_get_contents($serverAddress . "?path=/Base/InitComplete") == "OK.")
	{
		if(!AttemptToClaimServer($authToken, $userId, 'Base Server'))
		{
			http_response_code("500");
			die("Failed to claim server");
		}

		echo "<script>alert('Server successfully registered!');</script>";
		header("Location: index.php");
	}
}


echo "Failed to confirm server";