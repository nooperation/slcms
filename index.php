<?php
	require_once(dirname(__FILE__) . "/lib/RequireCredentials.php");
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Trotsdale Population</title>

	<!-- for mobile devices like android and iphone -->
	<meta content="True" name="HandheldFriendly" />
	<meta name="viewport" content="width=device-width initial-scale=1.0 maximum-scale=1.0 user-scalable=0"  />

	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/angularjs/1.3.5/angular.min.js"></script>


	<script type="text/javascript" src="js/graph-min.js"></script>
	<script type="text/javascript" src="js/app.js"></script>

	<script type="text/javascript" src="./js/jquery.js"></script>
	<script type="text/javascript" src="./js/jquery.dataTables.min.js"></script>

	<link rel="stylesheet" type="text/css" href="./css/jquery.dataTables.min.css">
	<link rel="stylesheet" type="text/css" href="css/graph.css">
	<link rel="stylesheet" type="text/css" href="css/custom.css">

</head>

<body ng-controller="userPageController as userPage">
<div>
	<a href="logout.php">Logout</a>
</div>

<div class="serverList">
	<div ng-repeat="server in userPage.servers"  ng-init="userPage.initUserPage(server)">
		<div class="serverListItem" id="server_{{server.publicToken}}" ng-class="{'serverDisabled' : !server.enabled, 'serverNotResponding': userPage.isServerResponding(server), 'serverBeingChecked' : userPage.isCheckingServer(server)}">
			<div class="serverHeader" id="header_{{server.publicToken}}">
				<h2 class="serverHeaderName">Name: {{server.serverName}} | Region: {{server.regionName}} | Count: {{server.agentCount}}</h2>
			</div>
			<div class="serverListItemOptions" id="options_{{server.publicToken}}">
				<button ng-click="userPage.setServerStatus(server, 0)" ng-disabled="!server.enabled">Disable</button> |
				<button ng-click="userPage.setServerStatus(server, 1)" ng-disabled="server.enabled">Enable</button> |
				<button ng-click="userPage.regeneratePublicToken(server)">Regenerate public token</button> |
				<button ng-click="userPage.regeneratePrivateToken(server)">Regenerate private token</button> |
				<button ng-click="userPage.deleteServer(server)">Delete</button>
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
						<tr><td class="serverListItemDetailsKey">ObjectKey</td><td class="serverListItemDetailsValue">{{server.objectKey}}</td></tr>
						<tr><td class="serverListItemDetailsKey">Location</td><td class="serverListItemDetailsValue">secondlife://{{server.regionName}}/{{server.positionX}}/{{server.positionY}}/{{server.positionZ}}</td></tr>
						<tr><td class="serverListItemDetailsKey">PublicToken</td><td class="serverListItemDetailsValue">{{server.publicToken}}</td></tr>
						<tr><td class="serverListItemDetailsKey">PrivateToken</td><td class="serverListItemDetailsValue">{{server.authToken}}</td></tr>
					</tbody>
				</table>
			</div>
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