<?php

include_once(dirname(__FILE__) . "./lib/SimStatsDatabase.php");
include_once(dirname(__FILE__) . "./lib/SecondlifeHeader.php");

function LogToFile($message, $messagePrivate = null)
{
	echo $message . "\r\n";

	if(!isset($_SERVER["REMOTE_ADDR"]))
	{
		$remoteAddress = "NA";
	}
	else
	{
		$remoteAddress = $_SERVER["REMOTE_ADDR"];
	}

	if($messagePrivate)
	{
		file_put_contents("registerLog.log", "[" . date(DateTime::ISO8601) . "] " . $remoteAddress . " -> " . $message . " [PRIVATE: " . $messagePrivate . "]\n", FILE_APPEND);
	}
	else
	{
		file_put_contents("registerLog.log", "[" . date(DateTime::ISO8601) . "] " . $remoteAddress . " -> " . $message . "\n", FILE_APPEND);
	}
}

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
	$ownerId = $db->GetOrCreateUserId($slHeader->ownerName, $slHeader->ownerKey);

	$db->CreateOrUpdateServer($slHeader->region, $shardId, $ownerId, $queryUrl);
}
catch(Exception $ex)
{
	http_response_code("500");
	LogToFile("Failed to register server. See log for details.", $ex->getMessage());
	die();
}


LogToFile("New or updated query server.");
LogToFile("  shard = " . $slHeader->shard . " [id = " . $shardId . "]");
LogToFile("  region = " . $slHeader->region);
LogToFile("  owner = " . $slHeader->ownerName . " {" . $slHeader->ownerKey . "}");
LogToFile("  url = " . $queryUrl);
LogToFile("");