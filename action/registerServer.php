<?php
// TODO: Encrypt communications
// TODO: Don't trust slHeader...

include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

if(!isset($_POST["address"]))
{
	http_response_code("500");
	LogAndEcho("Missing address");
	die();
}
$address = $_POST["address"];

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
	$authToken = $db->RegisterServer($slHeader->shard, $slHeader->ownerKey, $slHeader->ownerName, $slHeader->objectKey,$slHeader->objectName, $slHeader->region['name'], $address, $slHeader->localPosition['x'], $slHeader->localPosition['y'], $slHeader->localPosition['z']);
}
catch(Exception $ex)
{
	http_response_code("500");
	LogAndEcho("Failed to register server. See log for details.", $ex->getMessage());
	die();
}
echo "OK. Your auth token is: " . bin2hex($authToken);

LogToFile("New Base Server.");
LogToFile("  shard = " . $slHeader->shard);
LogToFile("  region = " . $slHeader->region['name']);
LogToFile("  name  = " . $slHeader->objectName);
LogToFile("  owner = " . $slHeader->ownerName . " (" . $slHeader->ownerKey . ")");
LogToFile("  url = " . $address);
LogToFile("");