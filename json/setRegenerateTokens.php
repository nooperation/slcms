<?php

require_once(dirname(__FILE__) . "/../lib/RequireCredentials.php");
include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

if(!isset($_GET["mode"]))
{
	http_response_code("500");
	LogAndEchoJson("Missing mode");
	die();
}
if(!isset($_GET["publicToken"]))
{
	http_response_code("500");
	LogAndEchoJson("Missing publicToken");
	die();
}

$mode = $_GET["mode"];
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

try
{
	switch($mode)
	{
		case "both":
			die(json_encode($db->RegenerateServerTokens($publicToken, $userId)));
		case "public":
			die(json_encode($db->RegenerateServerPublicToken($publicToken, $userId)));
		case "private":
			die(json_encode($db->RegenerateServerAuthToken($publicToken, $userId)));
		default:
			http_response_code("500");
			LogAndEchoJson("Invalid mode");
			die();
	}
}
catch(Exception $ex)
{
	http_response_code("500");
	LogAndEchoJson("Failed to regenerate token(s). See log for details.", $ex->getMessage());
	die();
}

