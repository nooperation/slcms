<?php

include_once(dirname(__FILE__) . "/../../lib/PopulationDatabase.php");
include_once(dirname(__FILE__) . "/../../lib/Utils.php");

try
{
	$db = new PopulationServerDatabase();
	$db->ConnectToDatabase();
}
catch(Exception $ex)
{
	http_response_code("500");
	LogAndEchoJson("Failed to connect to database. See log for details.", $ex->getMessage());
	die();
}

$servers = $db->GetPopulationServersForFrontend();
echo json_encode($servers);