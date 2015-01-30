<?php

include_once(dirname(__FILE__) . "/../../lib/SimStatsDatabase.php");
include_once(dirname(__FILE__) . "/../../lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "/../../lib/Utils.php");

function UnpackAgentData($input)
{
	if($input == "")
	{
		return [];
	}
	$agentHistory = [];

	$fromServer = explode(",", $input);
	if(sizeof($fromServer) < 3 || sizeof($fromServer) % 2 == 0)
	{
		throw new Exception("Invalid agent history.");
	}

	$lastDate = $fromServer[0];

	for($i = 1; $i < sizeof($fromServer); $i += 2)
	{
		$currentDate = $fromServer[$i] + $lastDate;
		$lastDate = $currentDate;
		$agentCount = $fromServer[$i + 1];

		$agentHistory []= array("Date" => $currentDate, "Count" => $agentCount);
	}

	return $agentHistory;
}

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

$servers = $db->GetStatsServers();
foreach($servers as $server)
{
	if(!$server['enabled'])
	{
		continue;
	}

	$serverResponse = @file_get_contents($server['address'] . "/population");
	if($serverResponse === false)
	{
		// TODO: GHETTO.
		if(strstr($http_response_header[0], "404 Not Found"))
		{
			$db->SetStatsServerStatus($server['authToken'], false);
			LogToFile("Server '" . $server['name'] . "' returned status 404. Disabling server");
		}
		else
		{
			LogToFile("Failed to query server '" . $server['name'] . "' at: " . $server['address'] . "");
		}
		continue;
	}

	try
	{
		$agentCountHistory = UnpackAgentData($serverResponse);
	}
	catch(Exception $ex)
	{
		LogToFile("Server  '" . $server['name'] . "' sent bad data: " . $serverResponse . "");
	}

	foreach($agentCountHistory as $historyItem)
	{
		$db->CreatePopulation($server['id'], $historyItem['Date'], $historyItem['Count']);

		echo (date(DateTime::RFC1036, $historyItem["Date"]) . " Server '" . $server['name'] . "' population = " . $historyItem['Count'] . "\r\n");
	}

}

