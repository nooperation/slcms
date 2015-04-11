<?php

session_start();
if(isset($_SESSION['user']))
{
	header("Location: index.php");
	die();
}
$user = $_SESSION['user'];
$userId = $user['id'];
$userName = $user['name'];

include_once(dirname(__FILE__) . "/lib/BaseServerDatabase.php");
include_once(dirname(__FILE__) . "/lib/Utils.php");


function AttemptRegister($username, $password, $passwordConfirm)
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


	$user = $db->RegisterUser($username, $password);
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

if(isset($_POST['username']) && isset($_POST['password']) && isset($_POST['passwordConfirm']))
{
	$username = $_POST['username'];
	$password = $_POST['password'];
	$passwordConfirm = $_POST['passwordConfirm'];

	$formError = AttemptRegister($username, $password, $passwordConfirm);
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

	<p>Register</p>
	<?php
	if(isset($formError))
	{
		echo '<div class="error">Error: ' . $formError. '</div>';
	}
	?>
	<form action="register.php" method="post">
		<div><label for="username">Username:</label> <input type="text" name="username" id="username"></div>
		<div><label for="password">Password:</label> <input type="password" name="password" id="password"></div>
		<div><label for="passwordConfirm">Password (confirm):</label> <input type="password" name="passwordConfirm" id="passwordConfirm"></div>
		<div><button type="submit">Register</button> </div>
	</form>
	<a href="login.php">Login</a>
</div>
</body>
</html>