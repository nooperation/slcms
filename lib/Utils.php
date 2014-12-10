<?php

include_once(dirname(__FILE__) . "./../.private/config.php");

function LogToFile($message, $messagePrivate = null)
{
	echo $message . "\r\n";

	if(Config::$SimStatsLogFile)
	{
		if(!isset($_SERVER["REMOTE_ADDR"]))
		{
			$remoteAddress = "NA";
		}
		else
		{
			$remoteAddress = $_SERVER["REMOTE_ADDR"];
		}

		if($messagePrivate)
		{
			file_put_contents(Config::$SimStatsLogFile, "[" . date(DateTime::ISO8601) . "] " . $remoteAddress . " -> " . $message . " [PRIVATE: " . $messagePrivate . "]\n", FILE_APPEND);
		}
		else
		{
			file_put_contents(Config::$SimStatsLogFile, "[" . date(DateTime::ISO8601) . "] " . $remoteAddress . " -> " . $message . "\n", FILE_APPEND);
		}
	}
}