<?php

include_once(dirname(__FILE__) . "/lib/SimStatsDatabase.php");
include_once(dirname(__FILE__) . "/lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "/lib/Utils.php");

if(!isset($_GET["queryUrl"]))
{
	http_response_code("500");
	LogToFile("No queryUrl sent");
	die();
}

$queryUrl = $_GET["queryUrl"];

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

	$db->CreateOrUpdateServer($slHeader->region, $shardId, $ownerId, $queryUrl);
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
LogToFile("  owner = " . $slHeader->ownerName . " (" . $slHeader->ownerKey . ")");
LogToFile("  url = " . $queryUrl);
LogToFile("");