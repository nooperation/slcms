<?php

session_start();
if(isset($_SESSION['user']))
{
	header("Location: index.php");
	return;
}

include_once(dirname(__FILE__) . "/lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/lib/Utils.php");


function AttemptLogin($username, $password)
{
	try
	{
		$db = new BaseServerDatabase();
		$db->ConnectToDatabase();
	}
	catch(Exception $ex)
	{
		http_response_code("500");
		LogToFile("Login.php|Failed to connect to database: ", $ex->getMessage());
		return "Failed to connect to database.";
	}

	$user = $db->GetUser($username, $password);
	if($user != null)
	{
		$_SESSION['user'] = $user;

		if(isset($_SESSION['actionToRestoreOnLogin']))
		{
			header("Location: " . $_SESSION['actionToRestoreOnLogin']);
		}
		else
		{
			header("Location: index.php");
		}
		die();
	}

	return "Invalid login";
}

if(isset($_POST['username']) && isset($_POST['password']))
{
	$username = $_POST['username'];
	$password = $_POST['password'];

	$formError = AttemptLogin($username, $password);
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
	<meta charset="UTF-8">
	<title></title>
</head>
<body>
<div>
	<p>Login</p>
	<form action="login.php" method="post">
		<div><label for="username">Username:</label> <input type="text" name="username" id="username"></div>
		<div><label for="password">Password:</label> <input type="password" name="password" id="password"></div>
		<div><button type="submit">Login</button> </div>
	</form>
	<?php
	if(isset($formError))
	{
		echo "ERROR: " . $formError;
	}
	?>
	<a href="register.php">Register</a>
</div>
</body>
</html>