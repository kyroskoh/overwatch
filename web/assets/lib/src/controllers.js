overwatchApp.controller('DashboardController', ['showLoading', 'isGranted', '$scope', 'overwatchApi', '$window', '$location', '$interval',
    function(showLoading, isGranted, $scope, overwatchApi, $window, $location, $interval)
    {

        $scope.groups = [];
        var fetchGroups = function() {
            overwatchApi.get(
                Routing.generate('overwatch_test_testgroupapi_getallgroups')
            ).success(function(groups){
                $scope.groups = groups;
                showLoading(false);
            });
        };
        var interval = $interval(fetchGroups, 60000);

        $scope.$on('$destroy', function() {
            $interval.cancel(interval);
        });

        $scope.isGranted = function(role, group) {
            if (typeof group === 'undefined') {
                return isGranted(role);
            }

            return isGranted(role, group.name);
        };

        $scope.shouldWarnOfTestAge = function() {
            var diffToAverage;
            var ageAverage = {
                total: 0,
                count: 0
            };

            angular.forEach($scope.groups, function(group) {
                angular.forEach(group.tests, function(test) {
                    if (test.result !== null && typeof test.result.createdAt !== 'undefined') {
                        this.total += test.result.createdAt;
                        this.count++;
                    }
                }, this);
            }, ageAverage);

            if (ageAverage.count === 0) {
                return false;
            }

            diffToAverage = (Date.now() / 1000) - (ageAverage.total / ageAverage.count);

            return (diffToAverage > (6 * 60 * 60));
        };

        $scope.removeTest = function(id) {
            if (!$window.confirm('Are you sure you want to remove this test? All historical data for this test will also be deleted.')) {
                return;
            }

            showLoading(true);
            overwatchApi.delete(Routing.generate('overwatch_test_testapi_deletetest', {'id': id}))
                .success(function(){
                    fetchGroups();
                })
            ;
        };

        $scope.removeGroup = function(id) {
            if (!$window.confirm('Are you sure you want to remove this group?')) {
                return;
            }

            showLoading(true);
            overwatchApi.delete(Routing.generate('overwatch_test_testgroupapi_deletegroup', {'id': id}))
                .success(function(){
                    fetchGroups();
                })
            ;
        };

        $scope.createGroup = function() {
            var name = $window.prompt("Please enter a name for the new group", "Untitled Group");
            if (name === null) {
                return;
            }

            showLoading(true);
            overwatchApi.post(Routing.generate('overwatch_test_testgroupapi_creategroup'), {'name': name})
                .success(function(){
                    fetchGroups();
                })
            ;
        };

        $scope.runTest = function(id) {
            showLoading(true);
            overwatchApi.post(Routing.generate('overwatch_test_testapi_runtest', {'id': id}), null)
                .success(function(){
                    $location.path("/test/" + id);
                })
            ;
        };

        fetchGroups();
    }
]);

overwatchApp.controller('EditGroupController', ['showLoading', '$scope', 'overwatchApi', '$routeParams', '$location', '$window', '$gravatar',
    function(showLoading, $scope, overwatchApi, $routeParams, $location, $window, $gravatar)
    {
        $scope.group = {};

        var fetchGroup = function() {
            overwatchApi.get(Routing.generate('overwatch_test_testgroupapi_getgroup', {id: $routeParams.id}))
                .success(function(group) {
                    $scope.group = group;
                    showLoading(false);
                })
            ;
        };

        $scope.userAvatar = function(email, size) {
            return $gravatar.fn(email, size);
        };

        $scope.removeUser = function(id) {
            if (!$window.confirm("Are you sure you want to remove this user from group '" + $scope.group.name + "'?")) {
                return;
            }

            showLoading(true);
            overwatchApi.delete(Routing.generate('overwatch_test_testgroupapi_removeuserfromgroup', {groupId: $scope.group.id, userId: id}))
                .success(function(){
                    fetchGroup();
                })
            ;
        };

        $scope.addUser = function() {
            var email = $window.prompt("Please enter the e-mail address of the user you wish to add to group '" + $scope.group.name + "'", currentUser.email);
            if (email === null) {
                return;
            }

            showLoading(true);
            overwatchApi.get(Routing.generate('overwatch_user_api_finduser', {email: email}))
                .success(function(user) {
                    overwatchApi.post(Routing.generate('overwatch_test_testgroupapi_addusertogroup', {groupId: $scope.group.id, userId: user.id}), {})
                        .success(function() {
                            fetchGroup();
                        })
                    ;
                })
                .error(function() {
                    showLoading(false);
                    $window.alert("Could not find user by e-mail address '" + email + "'. Please ensure that they are already registered.");
                })
            ;

        };

        $scope.renameGroup = function() {
            var name = $window.prompt("Please type a new name for this group", $scope.group.name);
            if (name === null || name === $scope.group.name) {
                return;
            }

            showLoading(true);
            overwatchApi.put(Routing.generate('overwatch_test_testgroupapi_updategroup', {id: $scope.group.id}), {name: name})
                .success(function(group) {
                    $scope.group = group;
                    currentUser.groups.push(group.name);
                    showLoading(false);
                })
            ;
        }

        fetchGroup();
    }
]);

overwatchApp.controller('AddTestController', ['showLoading', '$scope', 'overwatchApi', '$routeParams', '$location',
    function(showLoading, $scope, overwatchApi, $routeParams, $location)
    {
        $scope.title = "Add test";
        $scope.test = {};
        $scope.expectations = [];

        overwatchApi.get(Routing.generate('overwatch_expectation_api_getall'))
            .success(function(expectations) {
                $scope.expectations = expectations;
                showLoading(false);
            })
        ;

        $scope.save = function() {
            showLoading(true);
            overwatchApi.post(Routing.generate('overwatch_test_testapi_createtest', {id: $routeParams.id}), $scope.test)
                .success(function() {
                    $location.path('/');
                })
            ;
        }
    }
]);

overwatchApp.controller('ViewTestController', ['showLoading', 'isGranted', '$scope', 'overwatchApi', '$routeParams', '$interval', '$location', '$window', '$q',
    function(showLoading, isGranted, $scope, overwatchApi, $routeParams, $interval, $location, $window, $q)
    {
        $scope.test = {};
        $scope.test.results = {};
        $scope.lastRequestedResultSize = 0;

        $scope.loadResults = function(limit) {
            var testPromise = overwatchApi.get(Routing.generate('overwatch_test_testapi_gettest', {id: $routeParams.id}))
                .success(function(test) {
                    var results = $scope.test.results; // Back up the results
                    $scope.test = test;
                    $scope.test.results = results; // Restore them onto the test object
                })
            ;

            var resultPromise = overwatchApi.get(Routing.generate('overwatch_result_api_getresultsfortest', {id: $routeParams.id}) + '?pageSize=' + limit)
                .success(function(results) {
                    $scope.test.results = results;
                    $scope.lastRequestedResultSize = limit;
                })
            ;

            $q.all([testPromise, resultPromise]).then(function() {
                showLoading(false);
            });
        };

        $scope.loadOlderResults = function() {
            showLoading(true);
            $scope.loadResults($scope.lastRequestedResultSize + 10);
        }

        $scope.removeTest = function(id) {
            if (!$window.confirm('Are you sure you want to remove this test? All historical data for this test will also be deleted.')) {
                return;
            }

            showLoading(true);
            overwatchApi.delete(Routing.generate('overwatch_test_testapi_deletetest', {'id': id}))
                .success(function(){
                    $location.path('/');
                })
            ;
        };

        $scope.isGranted = function(role, group) {
            if (typeof group === 'undefined') {
                return isGranted(role);
            }

            return isGranted(role, group.name);
        };

        $scope.runTest = function(id) {
            showLoading(true);
            overwatchApi.post(Routing.generate('overwatch_test_testapi_runtest', {'id': id}), null)
                .success(function(){
                    $scope.loadResults($scope.lastRequestedResultSize);
                })
            ;
        };

        $scope.loadResults(10);

        var interval = $interval(function() {
            $scope.loadResults($scope.lastRequestedResultSize)
        }, 60000);

        $scope.$on('$destroy', function() {
            $interval.cancel(interval);
        });
    }
]);

overwatchApp.controller('EditTestController', ['showLoading', '$scope', 'overwatchApi', '$routeParams', '$location', '$q',
    function(showLoading, $scope, overwatchApi, $routeParams, $location, $q)
    {
        $scope.title = "Edit test";
        $scope.test = {};
        $scope.expectations = [];

        var expectationsPromise = overwatchApi.get(Routing.generate('overwatch_expectation_api_getall'))
            .success(function(expectations) {
                $scope.expectations = expectations;
            })
        ;

        var testPromise = overwatchApi.get(Routing.generate('overwatch_test_testapi_gettest', {id: $routeParams.id}))
            .success(function(test) {
                $scope.test = test;
            })
        ;

        $q.all([expectationsPromise, testPromise]).then(function(){
            showLoading(false);
        });

        $scope.save = function() {
            showLoading(true);
            overwatchApi.put(Routing.generate('overwatch_test_testapi_updatetest', {id: $routeParams.id}), $scope.test)
                .success(function() {
                    $location.path('/test/' + $routeParams.id);
                })
            ;
        }
    }
]);

overwatchApp.controller('ManageUsersController', ['showLoading', '$scope', 'overwatchApi', '$window', 'ModalService', '$gravatar',
    function(showLoading, $scope, overwatchApi, $window, ModalService, $gravatar)
    {
        $scope.users = [];
        $scope.updatedRoles = [];
        $scope.currentUserId = currentUser.id;

        var fetchUsers = function() {
            overwatchApi.get(Routing.generate('overwatch_user_api_getallusers'))
                .success(function(users) {
                    $scope.users = users;
                    showLoading(false);
                })
            ;
        };

        $scope.createUser = function() {
            var email = $window.prompt("Please type the new user's email address.", "");
            if (email === null) {
                return;
            }

            showLoading(true);
            overwatchApi.post(Routing.generate('overwatch_user_api_createuser', {'email': email}), {})
                .success(function(data) {
                    fetchUsers();
                })
            ;
        };

        $scope.userAvatar = function(email, size) {
            return $gravatar.fn(email, size);
        };

        $scope.updateRole = function(id) {
            ModalService.showModal({
              templateUrl: "/partials/roleDialog.html",
              controller: "RoleDialogController"
            }).then(function(modal) {
              modal.close.then(function(result) {
                if (result === 'CANCEL') {
                    return;
                }

                showLoading(true);
                overwatchApi.put(Routing.generate('overwatch_user_api_setuserrole', {id: id, role: result}), {})
                    .success(function() {
                        fetchUsers();
                    })
                ;
              });
            });
        };

        $scope.lockUser = function(id) {
            showLoading(true);
            overwatchApi.put(Routing.generate('overwatch_user_api_togglelockuser', {id: id}), {})
                .success(function() {
                    fetchUsers();
                })
            ;
        };

        $scope.removeUser = function(id) {
            if (!$window.confirm("Are you sure you want to permanently remove this user?")) {
                return;
            }

            showLoading(true);
            overwatchApi.delete(Routing.generate('overwatch_user_api_deleteuser', {id: id}))
                .success(function() {
                    fetchUsers();
                })
            ;
        };

        fetchUsers();
    }
]);

overwatchApp.controller('ManageAlertSettingsController', ['showLoading', '$scope', 'overwatchApi',
    function(showLoading, $scope, overwatchApi)
    {
        $scope.settings = [];

        var fetchSettings = function() {
            overwatchApi.get(Routing.generate('overwatch_user_api_getalertsettings'))
                .success(function(settings) {
                    $scope.settings = settings;
                    showLoading(false);
                })
            ;
        }

        $scope.isUsersSetting = function(id) {
            return (currentUser.alertSetting === id);
        };

        $scope.saveSetting = function(id) {
            showLoading(true);
            overwatchApi.put(Routing.generate('overwatch_user_api_updateuser'), {'alertSetting': id})
                .success(function() {
                    currentUser.alertSetting = id;
                    showLoading(false);
                })
            ;
        };

        fetchSettings();
    }
]);

overwatchApp.controller('RoleDialogController', ['$scope', 'close',
    function($scope, close)
    {
        $scope.close = function(result) {
     	  close(result);
        };
    }
]);

overwatchApp.controller('MyAccountController', ['showLoading', '$scope', 'overwatchApi',
    function(showLoading, $scope, overwatchApi)
    {
        $scope.telephoneNumber = currentUser.telephoneNumber;
        $scope.apiKeyFieldType = 'password';

        $scope.saveProfile = function() {
            showLoading(true);

            overwatchApi.put(Routing.generate('overwatch_user_api_updateuser'), {telephoneNumber: $scope.telephoneNumber})
                .then(function(response) {
                    currentUser.telephoneNumber = response.data.telephoneNumber;
                    showLoading(false);
                })
                .catch(function() {
                    showLoading(false);
                    alert('Sorry, there was an error saving your telephone number.');
                });
        };

        $scope.toggleAPIKeyVisibility = function() {
            var type = "password";

            if ($scope.apiKeyFieldType === "password") {
                type = "text";
            }

            $scope.apiKeyFieldType = type;
        }

        showLoading(false);
    }
]);
