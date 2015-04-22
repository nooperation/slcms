<?php

require_once(dirname(__FILE__) . "/../lib/RequireCredentials.php");
include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

if(!isset($_GET["publicToken"]))
{
	http_response_code("500");
	LogAndEchoJson("Missing publicToken");
	die();
}
if(!isset($_GET["enabled"]))
{
	http_response_code("500");
	LogAndEchoJson("Missing enabled");
	die();
}

$enabled = (bool)$_GET["enabled"];
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

if(!$db->SetServerStatus($publicToken, $userId, $enabled))
{
	http_response_code("500");
	LogAndEchoJson("Failed to set server status (likely already set).", $ex->getMessage());
	die();
}

echo json_encode($enabled);