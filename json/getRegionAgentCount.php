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

$serverAddress = $db->GetServerAddress($publicToken);

$result = @file_get_contents($serverAddress . "?path=/Base/GetRegionAgentCount");
if($result === false)
{
	http_response_code("500");
	EchoJsonError("Failed to get agent count");
	die();
}

echo json_encode((int)$result);
