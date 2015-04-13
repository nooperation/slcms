<?php

require_once(dirname(__FILE__) . "/../lib/RequireCredentials.php");
include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

if(!isset($_GET['publicToken']))
{
	http_response_code("500");
	LogAndEchoJson("Missing publicToken");
	die();
}
$publicToken = @hex2bin($_GET["publicToken"]);
if(!$publicToken)
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

$result = @file_get_contents($serverAddress . "?path=/Base/Confirm");

echo json_encode($result == "OK.");