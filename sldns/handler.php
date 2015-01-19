<?php

// TODO: DEBUG/DEVELOPMENT SCRIPT

require_once('lib/SldnsDatabase.php');

if(!isset($_GET['action']))
{
	die("DEBUG: Missing action");
}
if(!isset($_GET['password']))
{
	die("DEBUG: Missing password");
}

$action = $_GET['action'];
$password = $_GET['password'];

$db = new SldnsDatabase();
$db->ConnectToDatabase();

switch($action)
{
	case 'register':
	{
		RegisterDns($password);
		break;
	}
	case 'update':
	{
		if(!isset($_GET['name']))
		{
			die("DEBUG: Missing name");
		}
		if(!isset($_GET['newAddress']))
		{
			die("DEBUG: Missing newAddress");
		}

		if(!preg_match($_GET['name'], '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/'))
		{
			die("DEBUG: Invalid name");
		}

		$newAddress = urldecode($_GET['newAddress']);
		$name = $_GET['name'];

		UpdateDns($name, $password, $newAddress);
		break;
	}
	case 'delete':
	{
		if(!isset($_GET['name']) || empty($_GET['name']))
		{
			die("DEBUG: Missing name");
		}

		if(!preg_match($_GET['name'], '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/'))
		{
			die("DEBUG: Invalid name");
		}

		$name = $_GET['name'];

		break;
	}
}


function RegisterDns($password)
{
	global $db;

	$db->CreateDnsEntry($password);
}

function UpdateDns($name, $password, $newAddress)
{
	global $db;

	$db->UpdateDns($name, $password, $newAddress);
}

function DeleteDns($name, $password)
{
	global $db;

	$db->DeleteDns($name, $password);
}