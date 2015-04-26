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

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html ng-app="loginApp">
	<head>
		<title>Simstats Login</title>

		<script type="text/javascript" src="http://www.google.com/jsapi"></script>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/angularjs/1.3.5/angular.min.js"></script>

		<script type="text/javascript" src="./js/loginApp.js"></script>
		<script type="text/javascript" src="./js/jquery.js"></script>

		<link rel="stylesheet" type="text/css" href="css/login.css">
	</head>
<body  ng-controller="loginPageController as loginPage">
<div class="loginBox">
	<h2>Login to SitenameHere</h2>
	<form action="login.php" method="post">
		<div><label for="username">Username</label><div></div><input type="text" name="username" id="username"></div>
		<div><label for="password">Password</div></label><div> <input type="password" name="password" id="password"></div>
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