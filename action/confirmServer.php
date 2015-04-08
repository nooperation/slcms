<?php
// TODO: Encrypt communications
// TODO: Don't trust slHeader...

include_once(dirname(__FILE__) . "/../lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/../lib/PopulationDatabase.php");
include_once(dirname(__FILE__) . "/../lib/SecondlifeHeader.php");
include_once(dirname(__FILE__) . "/../lib/Utils.php");

if(!isset($_POST["authToken"]))
{
	http_response_code("500");
	LogAndEcho("Missing authToken");
	die();
}
$authToken = hex2bin($_POST["authToken"]);


if(!isset($_POST["serverType"]))
{
	http_response_code("500");
	LogAndEcho("Missing serverType");
	die();
}
$serverType = $_POST["serverType"];


$slHeader = new SecondlifeHeader($_SERVER);

if(!$slHeader->isSecondlifeRequest)
{
	http_response_code("500");
	LogAndEcho("Invalid secondlife header");
	die();
}

$db = null;

switch($serverType)
{
	case "Population Server":
		$db = new PopulationServerDatabase();
		break;
	case "Vendor Server":
		LogAndEcho("Vendor server not yet supported");
		die();
	case "Base Server":
		$db = new BaseServerDatabase();
		break;
	default:
		LogAndEcho("Invalid server type: " . $serverType);
		die();
}

try
{
	$db->ConnectToDatabase();
}
catch(Exception $ex)
{
	http_response_code("500");
	LogAndEcho("Failed to connect to database. See log for details.", $ex->getMessage());
	die();
}

try
{
	$db->InitServer($authToken);
}
catch(Exception $ex)
{
	http_response_code("500");
	LogAndEcho("Failed to confirm server. See log for details.", $ex->getMessage());
	die();
}
echo "OK.";

LogToFile("Confirmed server.");
LogToFile("  type = " . $serverType);
LogToFile("  shard = " . $slHeader->shard);
LogToFile("  region = " . $slHeader->region['name']);
LogToFile("  name  = " . $slHeader->objectName);
LogToFile("  owner = " . $slHeader->ownerName . " (" . $slHeader->ownerKey . ")");
LogToFile("");