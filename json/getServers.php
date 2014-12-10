<?php

include_once(dirname(__FILE__) . "/../lib/SimStatsDatabase.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

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

$servers = $db->GetServersForFrontend();
echo json_encode($servers);