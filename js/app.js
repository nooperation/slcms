google.load("visualization", "1");

google.setOnLoadCallback(function() {
    angular.bootstrap(document.body, ['populationApp']);
});

var myApp = angular.module('populationApp', []);
var currentGraph = null;

myApp.controller('graphListController',['$http', function($http){
    var base = this;
    var servers = [];
    var selectedServer = -1;

    this.showGraph = function(serverId) {
        if(selectedServer == serverId) {
            selectedServer = -1;
            currentGraph = null;
            return;
        }
        selectedServer = serverId;
        this.loadGraph(serverId);
        this.loadTable(serverId);
    };

    this.isShown = function(serverId) {
        return selectedServer == serverId;
    };

    this.loadGraph = function(serverId) {
        var context = this;
        var today = Math.round(new Date()/1000);
        var yesterday = today - 86400;

        // TODO: DOM elements haven't been added just yet... we're just hoping the http request takes enough time for all the graph divs to have evaluated by now
        $http.get('json/getPopulation.php?format=google&serverId=' + serverId + "&start=" + yesterday + "&end=" + today).success(function(data) {
            if(data.data.length > 0) {
                context.drawGraph(data, document.getElementById("graph_" + serverId));
            }
        });
    };

    this.drawGraph = function(data, element){
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

        currentGraph = graph;
    };

    this.loadTable = function(serverId) {
        var table = $("#usersTable_" + serverId).DataTable({
            "order": [5, "desc"],
            "bSortClasses": false,
            "searching": false,
            "info": false,
            "paging": false,
            "ajax": "json/getOnlineUsers.php?serverId=" + serverId,
            "columns": [
                { "data": "DisplayName" },
                { "data": "Username" },
                { "data": "Key" },
                { "data": "Pos" },
                { "data": "Memory" },
                { "data": "CPU" }
            ],
            "createdRow": function (row, data, index) {
                var memoryAmountInMib = data["Memory"] / 1024000.0;
                var cpuUsageInMs = data["CPU"] * 1000.0;

                var memoryUsageClass;
                var cpuUsageClass;

                if (memoryAmountInMib < 3.0) {
                    memoryUsageClass = "lowUsage";
                }
                else if (memoryAmountInMib < 5.0) {
                    memoryUsageClass = "mediumUsage";
                }
                else {
                    memoryUsageClass = "highUsage";
                }

                if (cpuUsageInMs < 0.25) {
                    cpuUsageClass = "lowUsage";
                }
                else if (cpuUsageInMs < 0.5) {
                    cpuUsageClass = "mediumUsage";
                }
                else {
                    cpuUsageClass = "highUsage";
                }

                $('td', row).eq(4).addClass(memoryUsageClass);
                $('td', row).eq(4).text(memoryAmountInMib.toFixed(2) + " MiB");
                $('td', row).eq(5).addClass(cpuUsageClass);
                $('td', row).eq(5).text(cpuUsageInMs.toFixed(2) + " MS");
            }
        });
    };




















    $http.get('json/getServers.php').success(function(data) {
        base.servers = data;
    })
    .error(function(data) {
        alert("Failed to load servers: " + data);
    });
}]);

redrawGraphs = function () {
    if(currentGraph != null) {
        currentGraph.redraw();
    }
};
