<?php

include_once(dirname(__FILE__) . "./lib/SimStatsDatabase.php");
include_once(dirname(__FILE__) . "./lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "./lib/Utils.php");

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

$servers = $db->GetServers();
foreach($servers as $server)
{
	$numAgentsInRegion = @file_get_contents($server['address'] . "/population");
	if($numAgentsInRegion === false)
	{
		LogToFile("Failed to query server '" . $server['name'] . "' at: " . $server['address'] . "");
		continue;
	}

	$db->CreateStats($server['id'], $numAgentsInRegion);
	echo ("Server '" . $server['name'] . "' population = " . $numAgentsInRegion);
	//LogToFile("Server '" . $server['name'] . "' population = " . $numAgentsInRegion);
}

