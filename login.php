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

function AttemptRegister($username, $email, $password, $passwordConfirm)
{
	if($password != $passwordConfirm)
	{
		return "Passwords don't match";
	}

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

	if(!$db->IsUsernameAvailable($username))
	{
		return "Username already in use";
	}

	$user = $db->RegisterUser($username, $email, $password);
	if($user != null)
	{
		$userConfirm = $db->GetUser($username, $password);
		if($userConfirm != null)
		{
			$_SESSION['user'] = $userConfirm;

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

		LogToFile("Register.php|Failed to confirm registration after registering.");
		return "Failed to confirm registration.";
	}

	LogToFile("Register.php|Failed to register user");
	return "Failed to register user.";
}

if(isset($_POST['action']))
{
	$action = $_POST['action'];

	if($action == 'login')
	{
		if(isset($_POST['username']) && isset($_POST['password']))
		{
			$username = $_POST['username'];
			$password = $_POST['password'];

			$formError = AttemptLogin($username, $password);
		}
	}
	else if($action == 'register')
	{
		if(isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['passwordConfirm']))
		{
			$username = $_POST['username'];
			$email = $_POST['email'];
			$password = $_POST['password'];
			$passwordConfirm = $_POST['passwordConfirm'];

			$formError = AttemptRegister($username, $email, $password, $passwordConfirm);
		}
	}
}

?>

<!DOCTYPE HTML>
<html ng-app="loginApp">
	<head>
		<title>Simstats Login</title>

		<script type="text/javascript" src="http://www.google.com/jsapi"></script>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/angularjs/1.3.5/angular.min.js"></script>

		<script type="text/javascript" src="./js/loginApp.js"></script>
		<script type="text/javascript" src="./js/jquery.js"></script>

		<link rel="stylesheet" type="text/css" href="css/login.css">
	</head>
<body  ng-controller="loginPageController as loginPage" ng-init="currentPage='Login'">
	<div class="loginBox" ng-show="currentPage=='Login'">
		<h2>Login<br/>(Development)</h2>
		<?php
		if(isset($formError))
		{
			echo "<div class='errorBox'>$formError</div>";
		}
		?>
		<form action="" method="post">
			<input type="hidden" name="action" id="action" value="login">
			<input type="text" name="username" id="username" placeholder="Username" required>
			<div> <input type="password" name="password" id="password" placeholder="Password" required></div>
			<div class="loginSubmitBox"><button type="submit">Login</button> </div>
		</form>
		<div class="loginExtras"><a href="#" ng-click="currentPage='Register'">Register</a></div>
	</div>
	<div class="loginBox" ng-show="currentPage=='Register'">
		<h2>Register<br/>(Development)</h2>
		<?php
		if(isset($formError))
		{
			echo "<div class='errorBox'>$formError</div>";
		}
		?>
		<form action="" method="post">
			<input type="hidden" name="action" id="action" value="register">

			<div><input type="text" name="username" id="username" placeholder="Username" required></div>
			<div><input type="email" name="email" id="email" placeholder="E-Mail" required></div>
			<div><input type="password" name="password" id="password" placeholder="Password" required></div>
			<div><input type="password" name="passwordConfirm" id="passwordConfirm" placeholder="Password (again)" required></div>
			<div class="loginSubmitBox"><button type="submit">Register</button> </div>
		</form>
		<div class="loginExtras"><a href="#" ng-click="currentPage='Login'">Login</a></div>
	</div>
</body>
</html>