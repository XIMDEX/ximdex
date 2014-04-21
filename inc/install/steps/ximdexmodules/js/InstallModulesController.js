/**
 *  \details &copy; 2011  Open Ximdex Evolution SL [http://www.ximdex.org]
 *
 *  Ximdex a Semantic Content Management System (CMS)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  See the Affero GNU General Public License for more details.
 *  You should have received a copy of the Affero GNU General Public License
 *  version 3 along with Ximdex (see LICENSE file).
 *
 *  If not, visit http://gnu.org/licenses/agpl-3.0.html.
 *
 *  @author Ximdex DevTeam <dev@ximdex.com>
 *  @version $Revision$
 */

ximdexInstallerApp.factory('installerService', function($http) {
//   this.installPath = "/";	
   return {

        sendAction: function(method, extraParams) {
             //return the promise directly.
             var params = "method="+method;
             if (extraParams)
             	params +="&"+extraParams;
             return $http({method: 'POST',
						    url: '',
						    data: params,
						    headers: {'Content-Type': 'application/x-www-form-urlencoded'}})
                       .success(function(response) {
                            //resolve the promise as the data
                            return response;
                        });
        }
   }
});

ximdexInstallerApp.controller('InstallModulesController', ["$timeout", '$scope', 'installerService', "$q", "$window",
 function($timeout, $scope, installerService, $q, $window) {

 	$scope.modules = {};
    $scope.loaded = false;
    $scope.message = "";
    $scope.error = false;
    $scope.foundModuleError=false;

    installerService.sendAction("getModulesLikeJson").then(function(response) {
        if (response.data.error){
            $scope.error = true;
            $scope.message = response.data.message;
        }
        $scope.modules = response.data;
        $scope.loaded = true;
    });

    $scope.processForm = function(){
    	$scope.loading = true;
    	var index = 0;
    	$scope.installModule(0);
	};

	$scope.installModule = function(index){
		if ($scope.modules.length > index){
			module = $scope.modules[index];
			$scope.modules[index]["state"] = "installing";
			installerService.sendAction("installModule","module="+module.name).then(function(response) {
		        $scope.modules[index]["processed"]=true;
		        $scope.modules[index]["state"]=response.data.result;
                if (response.data.result.toLowerCase() == "error")
                    $scope.foundModuleError = true;
		        index++;
		        $scope.installModule(index);
		    });
		}else{
			$scope.loading = false;
            
            installerService.sendAction("loadNextAction").then(function(response) {
                if (!$scope.foundModuleError)
                    $timeout(function(){location.reload();},1000);
		    });
		}
	}


}]);

ximdexInstallerApp.directive('uiLadda', [function () {
    return {
    	scope: {
    		state: '=ximState'
    	},
        link: function postLink(scope, element, attrs) {
        	var Ladda = window.Ladda, 
        	ladda = Ladda.create(element[0]);            
            scope.$watch('state', function(newVal, oldVal){
               newVal && ladda.start() || ladda.stop();
            });
        }
    };
}]);