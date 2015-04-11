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


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Trotsdale Population</title>

	<!-- for mobile devices like android and iphone -->
	<meta content="True" name="HandheldFriendly" />
	<meta name="viewport" content="width=device-width initial-scale=1.0 maximum-scale=1.0 user-scalable=0"  />

	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
	<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.3.5/angular.min.js"></script>


	<script type="text/javascript" src="js/graph-min.js"></script>
	<script type="text/javascript" src="js/app.js"></script>

	<script type="text/javascript" src="./js/jquery.js"></script>
	<script type="text/javascript" src="./js/jquery.dataTables.min.js"></script>

	<link rel="stylesheet" type="text/css" href="./css/jquery.dataTables.min.css">
	<link rel="stylesheet" type="text/css" href="css/graph.css">
	<link rel="stylesheet" type="text/css" href="css/custom.css">


	<style type="text/css">
		html, body {
			font: 10pt arial;
			padding: 0;
			margin: 0;
			width: 100%;
			height: 100%;
		}

		div.graph-frame {
			border: none;
		}

		#info {
			position: absolute;
			z-index: 1;
			top: 0px;
			left: 0px;
		}

		td.child {
			padding: 0 !important;
			border: 5px solid #808080;
		}

		iframe {
			width: 100%;
			height: 100px;
		}

	</style>

</head>

<body ng-controller="userPageController as userPage">
<div>
	<a href="logout.php">Logout</a>
</div>

<div class="serverList">
	<div ng-repeat="server in userPage.servers"  ng-init="userPage.initUserPage(server.publicToken)">
		<div class="serverListItem" id="server_{{server.publicToken}}">
			<div class="serverHeader" id="header_{{server.publicToken}}">
				<h2 class="serverHeaderName">{{server.serverTypeName}} | Name: {{server.serverName}}</h2>
			</div>
			<div class="serverListItemDetails" id="details_{{server.publicToken}}">
				<table class="serverListItemDetailsTable" cellpadding="0" class="display" width="100%">
					<tbody>
						<tr><td class="serverListItemDetailsKey">ServerName</td><td class="serverListItemDetailsValue">{{server.serverName}}</td></tr>
						<tr><td class="serverListItemDetailsKey">Address</td><td class="serverListItemDetailsValue">{{server.address}}</td></tr>
						<tr><td class="serverListItemDetailsKey">UserName</td><td class="serverListItemDetailsValue"> {{server.userName}}</td></tr>
						<tr><td class="serverListItemDetailsKey">OwnerName</td><td class="serverListItemDetailsValue">{{server.ownerName}}</td></tr>
						<tr><td class="serverListItemDetailsKey">RegionName</td><td class="serverListItemDetailsValue">{{server.regionName}}</td></tr>
						<tr><td class="serverListItemDetailsKey">ShardName</td><td class="serverListItemDetailsValue">{{server.shardName}}</td></tr>
						<tr><td class="serverListItemDetailsKey">ServerTypeName</td><td class="serverListItemDetailsValue">{{server.serverTypeName}}</td></tr>
					</tbody>
				</table>
			</div>
<!--			<div class="serverListItemContentsToggler" id="contentsToggler_{{server.publicToken}}" ng-click="userPage.toggleShown(server.publicToken)">Toggle</div>-->
<!--			<div class="serverListItemContentsToggler" id="contentsToggler_{{server.publicToken}}" ng-click="userPage.reloadTable(server.publicToken)">Reload</div>-->
			<div class="serverListItemContents" id="contents_{{server.publicToken}}" >
				<div id="graph_{{server.publicToken}}" ></div>
				<div>
					<table id="usersTable_{{server.publicToken}}" cellpadding="0" class="display" width="100%">
						<thead>
						<tr>
							<th>Display Name</th>
							<th>Username</th>
							<th>Key</th>
							<th>Position</th>
							<th>Memory</th>
							<th>CPU</th>
						</tr>
						</thead>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>