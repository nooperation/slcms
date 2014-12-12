google.load("visualization", "1");

google.setOnLoadCallback(function() {
    angular.bootstrap(document.body, ['populationApp']);
});

var myApp = angular.module('populationApp', []);
var graphs = [];

myApp.controller('graphList',['$http', function($http){
    var base = this;
    var servers = [];

    $http.get('json/getServers.php').success(function(data) {
        graphs = [];
        base.servers = data;
    })
    .error(function(data) {
        alert("Failed to load servers: " + data);
    });
}]);

myApp.directive('initGraphDirective',['$http', function($http) {
    return function(scope, element, attrs) {
        var serverId = scope.server.id;

        var today = Math.round(new Date()/1000);
        var yesterday = today - 86400;

        // TODO: DOM elements haven't been added just yet... we're just hoping the http request takes enough time for all the graph divs to have evaluated by now
        $http.get('json/getPopulation.php?format=google&serverId=' + serverId + "&start=" + yesterday + "&end=" + today).success(function(data) {

            if(data.data.length > 0) {
                drawGraph(data, document.getElementById("graph_" + serverId));
            }
        })
    };
}]);

function redrawGraphs() {
    for(var i = 0; i < graphs.length; ++i) {
        graphs[i].redraw();
    }
}

function drawGraph(data, element){

    // specify options
    var options = {
        "width": "100%",
        "min": data.data[0].date,
        "max": data.data[data.data.length-1].date,
        "lines": [{
            color: "#97C2FC", width: 2, legend: false
        }],
        "tooltip": function (point) {
            return new Date(point.date) + '<br />Players: ' + point.value;
        }
    };

    // Instantiate our graph object.
    var graph = new links.Graph(element);

    // Draw our graph with the created data and options
    graph.draw([data], options);

    graphs.push(graph);
}
