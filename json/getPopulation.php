<?php

include_once(dirname(__FILE__) . "./../lib/SimStatsDatabase.php");
include_once(dirname(__FILE__) . "./../lib/Utils.php");

if(!isset($_GET['serverId']))
{
	die("Missing serverId");
}
$serverId = $_GET['serverId'];

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
	$stats = $db->GetStats($serverId, 20);
}
catch(Exception $ex)
{
	LogToFile("Failed to get population of server. See log for details.", $ex->getMessage());
	die();
}

// TODO: TEMP hack for testing. please remove!
for($i = 0; $i < sizeof($stats); ++$i)
{
	$stats[$i]['agentCount'] = (int)$stats[$i]['agentCount'];
}

echo json_encode($stats);