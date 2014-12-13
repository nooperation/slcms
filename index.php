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

<body ng-controller="graphListController as graphList" onresize="redrawGraphs();" >
	<div class="serverList">
		<div ng-repeat="server in graphList.servers">
			<div class="server" id="server_{{server.id}}">
				<div class="serverHeader" id="header_{{server.id}}" ng-click="graphList.showGraph(server.id)">
					<h2 class="serverHeaderName">Shard: {{server.shardName}} | Region: {{server.serverName}} | Population: {{server.currentPopulation}}</h2>
				</div>
				<div class="serverContents" id="contents_{{server.id}}" ng-show="graphList.isShown(server.id)">
					<div id="graph_{{server.id}}" ></div>
					<div>
						<table id="usersTable_{{server.id}}" cellpadding="0" class="display" width="100%">
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
