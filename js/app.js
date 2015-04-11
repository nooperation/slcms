google.load("visualization", "1");

google.setOnLoadCallback(function() {
    angular.bootstrap(document.body, ['populationApp']);
});

var myApp = angular.module('populationApp', []);
var currentGraph = null;

myApp.controller('userPageController',['$http', '$timeout', function($http, $timeout){
    var base = this;
    var servers = [];

    this.isShown = function(publicToken) {
        return true; //selectedServer == serverId;
    };

    this.initUserPage = function(publicToken) {
        $timeout(function() {
            // Should be executed once all our objects have been rendered?
            $.getJSON("json/getServerStatus.php?publicToken=" + publicToken, function(data) {
                if(data) {
                    base.setServerResponding(publicToken);
                    base.showGraph(publicToken);
                }
                else {
                    base.setServerNotResponding(publicToken);
                }
            });
        });
    };

    this.setServerResponding = function(publicToken) {
        var contentsElement = $("#server_" + publicToken);

    };

    this.setServerNotResponding = function(publicToken) {
        var contentsElement = $("#server_" + publicToken);
        contentsElement.addClass('serverNotResponding');
    };

    this.toggleShown = function(publicToken) {
        var contentsElement = $("#contents_" + publicToken);

        if(contentsElement.is(":hidden")) {
            this.showGraph(publicToken);
            contentsElement.show();
        }
        else {
            contentsElement.hide();
        }
    };

    this.reloadTable = function(publicToken) {
        var tableElement =  $("#usersTable_" + publicToken).DataTable();

        if(tableElement && tableElement.json) {
            alert('reload');
            tableElement.ajax.reload();
        }
    };

    this.showGraph = function(publicToken) {
        this.loadTable(publicToken);
    };

    this.loadTable = function(publicToken) {

        $("#usersTable_" + publicToken).DataTable({
            "destroy": true,
            "order": [5, "desc"],
            "bSortClasses": false,
            "searching": false,
            "info": false,
            "paging": false,
            "ajax": "json/getOnlineUsers.php?publicToken=" + publicToken,
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
