<?php

include_once(dirname(__FILE__) . "/../../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../../lib/Utils.php");

if(!isset($_GET['publicToken']))
{
	die("Missing publicToken");
}
$publicToken = $_GET['publicToken'];

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

$mapUrl = $db->GetServerAddress($publicToken);

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
