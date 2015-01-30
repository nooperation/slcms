<?php

include_once(dirname(__FILE__) . "/../../lib/SimStatsDatabase.php");
include_once(dirname(__FILE__) . "/../../lib/Utils.php");

if(!isset($_GET['serverId']))
{
	die("Missing serverId");
}
$uuid = $_GET['serverId'];

try
{
	$db = new SimStatsDatabase();
	$db->ConnectToDatabase();
}
catch(Exception $ex)
{
	http_response_code("500");
	LogAndEchoJson("Failed to connect to database. See log for details.", $ex->getMessage());
	die();
}

$mapUrl = $db->GetStatsServerAddress($uuid);

if($mapUrl == null || $mapUrl == "")
{
	LogToFile("Failed to get map address. Try again.");
	echo "null";
	return;
}

$contents = @file_get_contents($mapUrl . "/agents");
if($contents == "")
{
	LogToFile("Failed to contact map server. Try again.");
	echo "null";
	return;
}

// TEMP: Replace 'Players' with 'data'
echo substr_replace($contents, "data", 2, 7);;
