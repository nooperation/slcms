var loginApp = angular.module('loginApp', []);

loginApp.controller('loginPageController',['$http', '$timeout', '$scope', function($http, $timeout, $scope) {
    this.foo = "Hello!";
}]);