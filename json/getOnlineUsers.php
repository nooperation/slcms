<?php

include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

if(!isset($_GET['publicToken']))
{
	http_response_code("500");
	LogAndEchoJson("Missing publicToken");
	die();
}

$publicToken = $_GET['publicToken'];
if(!ctype_xdigit($publicToken))
{
	http_response_code("500");
	LogAndEchoJson("Invalid publicToken");
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
	LogAndEchoJson("Failed to connect to database. See log for details.", $ex->getMessage());
	die();
}

$mapUrl = $db->GetServerAddress($publicToken);

if($mapUrl == null || $mapUrl == "")
{
	http_response_code("500");
	LogAndEchoJson("Failed to get map address. Try again.");
	die();
}

$contents = @file_get_contents($mapUrl . "?path=/Base/GetAgentList");
if($contents == "")
{
	http_response_code("500");
	LogAndEchoJson("Failed to contact map server. Try again.");
	die();
}

// TEMP: Replace 'Players' with 'data'
http_response_code("200");
echo substr_replace($contents, "data", 2, 7);;
