<?php

include_once(dirname(__FILE__) . "/../lib/SimStatsDatabase.php");
include_once(dirname(__FILE__) . "/../lib/SecondlifeHeader.php");
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

$servers = $db->GetServers();
foreach($servers as $server)
{
	if(!$server['enabled'])
	{
		continue;
	}

	$numAgentsInRegion = @file_get_contents($server['address'] . "/population");
	if($numAgentsInRegion === false)
	{
		// TODO: GHETTO.
		if(strstr($http_response_header[0], "404 Not Found"))
		{
			$db->SetServerStatus($server['id'], false);
			LogToFile("Server '" . $server['name'] . "' returned status 404. Disabling server");
		}
		else
		{
			LogToFile("Failed to query server '" . $server['name'] . "' at: " . $server['address'] . "");
		}
		continue;
	}

	$db->CreateStats($server['id'], $numAgentsInRegion);
	echo ("Server '" . $server['name'] . "' population = " . $numAgentsInRegion . "\r\n");
}

