<?php

include_once(dirname(__FILE__) . "/../.private/config.php");

function GenerateRandomToken()
{
	return openssl_random_pseudo_bytes(32);
}

function LogAndEchoJson($message, $messagePrivate = null)
{
	echo json_encode(array('error' => $message));
	LogToFile($message, $messagePrivate);
}

function LogAndEcho($message, $messagePrivate = null)
{
	echo $message . "\r\n";

	LogToFile($message, $messagePrivate);
}

function LogToFile($message, $messagePrivate = null)
{
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