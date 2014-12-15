<?php

include_once(dirname(__FILE__) . "/../lib/SimStatsDatabase.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

class DataSet
{
	public $label;
	public $data;

	function __construct($label)
	{
		$this->label = $label;
		$this->data = array();
	}
}

class DataSetData
{
	public $date;
	public $value;

	function __construct($date, $value)
	{
		$this->date = $date;
		$this->value = $value;
	}
}

if(!isset($_GET['serverId']))
{
	die("Missing serverId");
}
$serverId = $_GET['serverId'];
$format = null;

$start = null;
$end = null;
if(isset($_GET['start']) && is_numeric($_GET['start']))
{
	$start = intval($_GET['start']);
}
if(isset($_GET['end']) && is_numeric($_GET['end']))
{
	$end = intval($_GET['end']);
}
if(isset($_GET['format']))
{
	$format = $_GET['format'];
}

try
{
	$db = new SimStatsDatabase();
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
	$stats = $db->GetStats($serverId, $start, $end);
	$serverName = $db->GetServerName($serverId);
}
catch(Exception $ex)
{
	http_response_code("500");
	LogAndEchoJson("Failed to get population of server. See log for details.", $ex->getMessage());
	die();
}

if($format == "google")
{
	$googleData = new DataSet($serverName);
	for($i = 0; $i < sizeof($stats); ++$i)
	{
		if($i != 0)
		{
			$googleData->data []= new DataSetData(((int)$stats[$i]['time'] * 1000) - 1, (int)$stats[$i-1]['agentCount']);
		}
		$googleData->data []= new DataSetData((int)$stats[$i]['time'] * 1000, (int)$stats[$i]['agentCount']);
	}
	$googleData->data []= new DataSetData((int)time() * 1000, (int)$stats[sizeof($stats) - 1]['agentCount']);

	echo json_encode($googleData);
}
else
{
	echo json_encode($stats);
}