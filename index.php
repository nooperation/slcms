<?php
	session_start();
	if(!isset($_SESSION['user']))
	{
		header("Location: login.php");
		die();
	}

	$user = $_SESSION['user'];
	$userId = $user['id'];
	$userName = $user['name'];

	include_once(dirname(__FILE__) . "/lib/BaseServerDatabase.php");

	try
	{
		$db = new BaseServerDatabase();
		$db->ConnectToDatabase();
	} catch(Exception $ex)
	{
		http_response_code("500");
		LogToFile("Login.php|Failed to connect to database: ", $ex->getMessage());
		die("Failed to connect to database.");
	}

	function AttemptToClaimServer($authToken, $userId, $serverType)
	{
		global $db;

		try
		{
			$result = $db->InitServer($authToken, $userId, $serverType);
		}
		catch(Exception $ex)
		{
			return false;
		}

		return !!$result;
	}

	if(isset($_POST['authToken']))
	{
		$authToken = $_POST['authToken'];
		if(!AttemptToClaimServer($authToken, $userId, 'Base Server'))
		{
			echo "Failed to claim server";
		}
		else
		{
			echo "Claimed server";
		}
	}

?>

<div>
	<a href="logout.php">Logout</a>
</div>


<div>
	Registered Servers:
	<?php

		$servers = $db->GetServersForUser($userId);
		if($servers)
		{
			foreach($servers as $server)
			{
				echo 'serverName: ' . $server['serverName'] . '<br/>';
				echo 'Address: ' . $server['address'] . '<br />';
				echo 'userName: ' . $server['userName'] . '<br />';
				echo 'ownerName: ' . $server['ownerName'] . '<br />';
				echo 'regionName: ' . $server['regionName'] . '<br />';
				echo 'shardName: ' . $server['shardName'] . '<br />';
				echo 'serverTypeName: ' . $server['serverTypeName'] . '<br />';
			}
			echo '<br />';
		}
	?>
</div>
<hr/>
<div>
	Register new server:
	<form method="post" action="index.php">
		<label for="authToken"></label><input name="authToken">
		<button type="submit">Submit</button>
	</form>
</div>