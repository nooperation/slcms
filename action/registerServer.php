<?php
// TODO: Encrypt communications
// TODO: Don't trust slHeader...

include_once(dirname(__FILE__) . "/lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/lib/Utils.php");

if(!isset($_POST["address"]))
{
	http_response_code("500");
	LogToFile("Missing address");
	die();
}
$address = $_POST["address"];

$slHeader = new SecondlifeHeader($_SERVER);

if(!$slHeader->isSecondlifeRequest)
{
	http_response_code("500");
	LogToFile("Invalid secondlife header");
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
	LogToFile("Failed to connect to database. See log for details.", $ex->getMessage());
	die();
}

try
{
	$shardId = $this->GetOrCreateShardId($slHeader->shard);
	$ownerId = $this->GetOrCreateUserId($slHeader->ownerKey, $slHeader->ownerName, $shardId);
	$regionId = $this->GetOrCreateRegionId($slHeader->region['name'], $shardId);

	$authToken = $db->RegisterServer($shardId, $regionId, $ownerId, null, $address, $slHeader->objectKey, $slHeader->objectName, true, $slHeader->localPosition['x'], $slHeader->localPosition['y'], $slHeader->localPosition['z']);
}
catch(Exception $ex)
{
	http_response_code("500");
	LogToFile("Failed to register server. See log for details.", $ex->getMessage());
	die();
}

echo "Your auth token is: " . $authToken;

LogToFile("New Base Server.");
LogToFile("  shard = " . $slHeader->shard);
LogToFile("  region = " . $slHeader->region['name']);
LogToFile("  name  = " . $slHeader->objectName);
LogToFile("  owner = " . $slHeader->ownerName . " (" . $slHeader->ownerKey . ")");
LogToFile("  url = " . $address);
LogToFile("");