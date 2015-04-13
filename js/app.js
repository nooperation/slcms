google.load("visualization", "1");

google.setOnLoadCallback(function() {
    angular.bootstrap(document.body, ['populationApp']);
});

var myApp = angular.module('populationApp', []);
var currentGraph = null;

myApp.controller('userPageController',['$http', '$timeout', '$scope', function($http, $timeout, $scope){
    var base = this;
    var servers = [];

    this.isShown = function(publicToken) {
        return true; //selectedServer == serverId;
    };

    this.regeneratePublicToken = function(server) {
        var result = confirm("Regenerate public token for " + server.serverName + "?");
        if(!result) {
            return;
        }

        $.getJSON("json/setRegenerateTokens.php?mode=public&publicToken=" + server.publicToken).success(function(data) {
            $scope.$apply(function() {
                server.publicToken = data;
            });
        });
    };

    this.regeneratePrivateToken = function(server) {
        var result = confirm("Regenerate private token for " + server.serverName + "?");
        if(!result) {
            return;
        }

        $.getJSON("json/setRegenerateTokens.php?mode=private&publicToken=" + server.publicToken).success(function(data) {
            $scope.$apply(function() {
                server.authToken = data;
            });
        });
    };
    
    this.deleteServer = function(server) {
        var result = prompt("Type the name of the server to delete it:");
        if(result != server.serverName) {
            alert("Aborting");
            return;
        }

        $.getJSON("json/setDeleteServer.php?publicToken=" + server.publicToken).success(function(data) {
            $scope.$apply(function() {
                base.reloadServers();
            });
        });
    };

    this.setServerStatus = function(server, isEnabled) {
        $.getJSON("json/setServerStatus.php?publicToken=" + server.publicToken + "&enabled=" + isEnabled).success(function(data) {
            $scope.$apply(function() {
                server.enabled = data;
            });
        });
    };

    this.isCheckingServer = function(server) {
        return server.agentCount == undefined;
    };

    this.isServerResponding = function(server) {
        return server.agentCount == 'N/A';
    };

    this.initUserPage = function(server) {
        $timeout(function() {
            // Should be executed once all our objects have been rendered?
            $.getJSON("json/getRegionAgentCount.php?publicToken=" + server.publicToken).success(function(data) {
                $scope.$apply(function() {
                    server.agentCount = data;
                    base.loadTable(server);
                });
            }).fail(function(data){
                $scope.$apply(function() {
                    server.agentCount = 'N/A';
                });
            });
        });
    };

    this.loadTable = function(server) {
        var publicToken = server.publicToken;

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

    this.reloadServers = function() {
        $http.get('json/getServers.php').success(function(data) {
            base.servers = data;
        })
        .error(function(data) {
            alert("Failed to load servers: " + data);
        });
    };

    this.reloadServers();
}]);
