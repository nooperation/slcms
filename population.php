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

	<!--[if IE]><script type="text/javascript" src="js/excanvas.js"></script><![endif]-->
	<link rel="stylesheet" type="text/css" href="css/graph.css">

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
	</style>

</head>

<body ng-controller="graphList as graphListController"  onresize="redrawGraphs();" >
<div ng-repeat="server in graphListController.servers" on-load="graphListController.onGraphInit(server)" init-graph-directive>
	<div>
		<h2>{{server.shardName}} | {{server.serverName}}</h2>
	</div>
	<div id="graph_{{server.id}}"></div>
	<hr/>
</div>
</body>
</html>
