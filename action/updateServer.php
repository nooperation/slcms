<?php
// TODO: Encrypt communications
// TODO: Don't trust slHeader...
ini_set('html_errors', 0);
include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

if(!isset($_POST["address"]))
{
	http_response_code("500");
	LogToFile("Missing address");
	die();
}
if(!isset($_POST["authToken"]))
{
	http_response_code("500");
	LogToFile("Missing authToken");
	die();
}

$address = $_POST["address"];

$authToken = @hex2bin($_POST["authToken"]);
if(!$authToken)
{
	http_response_code("500");
	LogAndEcho("Invalid authToken");
	die();
}

$slHeader = new SecondlifeHeader($_SERVER);
if(!$slHeader->isSecondlifeRequest)
{
	http_response_code("500");
	LogAndEcho("Invalid secondlife header");
	die();
}

try
{
	$db = new BaseServerDatabase();
	$db->ConnectToDatabase();
}
catch(Exception $ex)
{
	http_response_code("500");
	LogAndEcho("Failed to connect to database. See log for details.", $ex->getMessage());
	die();
}

try
{
	$rowsAffected = $db->UpdateServer($authToken, $address, $slHeader->objectName, $slHeader->shard, $slHeader->region['name'], $slHeader->localPosition['x'], $slHeader->localPosition['y'], $slHeader->localPosition['z'], true);
}
catch(Exception $ex)
{
	http_response_code("500");
	LogAndEcho("Failed to update server. See log for details.", $ex->getMessage());
	die();
}

if($rowsAffected == 0)
{
	http_response_code("500");
	LogAndEcho("Failed to update server: 0 rows affected");
	die();
}

echo "OK. Server up to date";

LogToFile("Updated Base Server.");
LogToFile("  shard = " . $slHeader->shard);
LogToFile("  region = " . $slHeader->region['name']);
LogToFile("  name  = " . $slHeader->objectName);
LogToFile("  owner = " . $slHeader->ownerName . " (" . $slHeader->ownerKey . ")");
LogToFile("  url = " . $address);
LogToFile("");