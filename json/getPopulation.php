<?php

include_once(dirname(__FILE__) . "/../lib/PopulationDatabase.php");
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

if(!isset($_GET['publicToken']))
{
	die("Missing publicToken");
}

$publicToken = @hex2bin($_GET['publicToken']);
if(!$publicToken)
{
	http_response_code("500");
	LogAndEchoJson("Invalid publicToken");
	die();
}

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
	$db = new PopulationServerDatabase();
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
	$stats = $db->GetPopulation($publicToken, $start, $end);
}
catch(Exception $ex)
{
	http_response_code("500");
	LogAndEchoJson("Failed to get population of server. See log for details.", $ex->getMessage());
	die();
}

if($format == "google")
{
	$googleData = new DataSet("ServerNameHere");
	if(sizeof($stats) > 0)
	{
		for($i = 0; $i < sizeof($stats); ++$i)
		{
			if($i != 0)
			{
				$googleData->data [] = new DataSetData(((int)$stats[$i]['time'] * 1000) - 1, (int)$stats[$i - 1]['agentCount']);
			}
			$googleData->data [] = new DataSetData((int)$stats[$i]['time'] * 1000, (int)$stats[$i]['agentCount']);
		}
		$googleData->data [] = new DataSetData((int)time() * 1000, (int)$stats[sizeof($stats) - 1]['agentCount']);
	}
	echo json_encode($googleData);
}
else
{
	echo json_encode($stats);
}