<?php

include_once(dirname(__FILE__) . "/../lib/SimStatsDatabase.php");
include_once(dirname(__FILE__) . "/../lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

if(!isset($_POST["queryUrl"]))
{
	http_response_code("500");
	LogToFile("No queryUrl sent");
	die();
}

if(!isset($_POST["password"]))
{
	http_response_code("500");
	LogToFile("No password sent");
	die();
}

$password = $_POST["password"];
$queryUrl = $_POST["queryUrl"];

$slHeader = new SecondlifeHeader($_SERVER);

try
{
	$db = new SimStatsDatabase();
	$db->ConnectToDatabase();
}
catch(Exception $ex)
{
	http_response_code("500");
	LogToFile("Failed to connect to database. See log for details.", $ex->getMessage());
	die();
}

try
{
	$shardId = $db->GetOrCreateShardId($slHeader->shard);
	$ownerId = $db->GetOrCreateUserId($slHeader->ownerKey, $slHeader->ownerName, $shardId);
	$dnsId = 0;

	$db->CreateOrUpdateServer($slHeader->objectName, $shardId, $ownerId, $queryUrl, $slHeader->objectKey, $password);
}
catch(Exception $ex)
{
	http_response_code("500");
	LogToFile("Failed to register server. See log for details.", $ex->getMessage());
	die();
}

echo "OK";
LogToFile("New or updated query server.");
LogToFile("  shard = " . $slHeader->shard . " [id = " . $shardId . "]");
LogToFile("  region = " . $slHeader->region);
LogToFile("  name  = " . $slHeader->objectName);
LogToFile("  owner = " . $slHeader->ownerName . " (" . $slHeader->ownerKey . ")");
LogToFile("  url = " . $queryUrl);
LogToFile("");