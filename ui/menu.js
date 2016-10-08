//
// This class will display the form to allow admins and station owners to 
// change the details of their station
//
function qruqsp_core_menu() {
    this.stations = null;

    this.init = function() {}

    this.start = function(cb) {
        //
        // Get the list of stations the user has access to
        //
        Q.api.getJSONCb('qruqsp.core.userStations', {}, function(r) {
            if( r.stat != 'ok' ) {
                Q.api.err(r);
                return false;
            }
            Q.qruqsp_core_menu.setupMenu(cb, r);
        });
    }

    this.setupMenu = function(cb, r) {
        if( (r.categories == null && r.stations == null) 
            || (r.categories != null && r.categories.length < 1)
            || (r.stations != null && r.stations.length < 1) ) {
            alert('Error - no stations found');
            return false;
        } else if( r.stations != null && r.stations.length == 1 ) {
            //
            // If only 1 station, then go direct to that menu
            //
            Q.startApp(Q.stationMenu,null,null,'mc',{'id':r.stations[0].id});
        } else {
            //
            // Create the app container if it doesn't exist, and clear it out
            // if it does exist.
            //
            var appContainer = Q.createContainer('mc', 'qruqsp_core_menu', 'yes');
            if( appContainer == null ) {
                alert('App Error');
                return false;
            } 
        
            // setup home panel as list of stations
            if( (Q.userPerms&0x01) == 0x01 && r.categories != null && r.categories.length > 1 ) {
                this.stations = new Q.panel('Stations', 'qruqsp_core_menu', 'stations',
                    'mc', 'medium narrowaside', 'sectioned', 'qruqsp.core.menu.stations');
                this.stations.data = {};
            } else {
                this.stations = new Q.panel('Stations', 'qruqsp_core_menu', 'stations',
                    'mc', 'narrow', 'sectioned', 'qruqsp.core.menu.stations');
                this.stations.data = {};
            }
            this.stations.curCategory = 0;
            this.stations.addButton('account', 'Account', 'Q.startApp(\'qruqsp.core.account\',null,\'Q.qruqsp_core_menu.stations.show();\');');
            if( Q.userID > 0 && (Q.userPerms&0x01) == 0x01 ) {
                this.stations.addButton('admin', 'Admin', 'Q.startApp(\'qruqsp.sysadmin.main\',null,\'Q.qruqsp_core_menu.stations.show();\');');
            }

            if( r.categories != null ) {
                if( r.categories.length > 1 ) {
                    this.stations.data = r;
                    this.stations.sections['categories'] = {'label':'Categories', 'aside':'yes', 'type':'simplegrid', 'num_cols':1};
                    this.stations.sections['_'] = {'label':'',
                        'autofocus':'yes', 'type':'livesearchgrid', 'livesearchcols':1,
                        'hint':'Search', 
                        'noData':'No items found',
                        'headerValues':null,
                        };
                    this.stations.sections['list'] = {'label':'', 'type':'simplegrid', 'num_cols':1};
                } else {
                    for(i in r.categories) {
                        this.stations.sections['_'+i] = {'label':r.categories[i].name, 'type':'simplelist'};
                        this.stations.data['_'+i] = r.categories[i].stations;
                    }
                }
                this.stations.sectionData = function(s) { 
                    if( s == 'list' ) {
                        return this.data.categories[this.curCategory].stations;
                    }
                    return this.data[s]; 
                }
            } else {
                this.stations.sections = {'_':{'label':'', 'as':'yes', 'list':{}}};
                this.stations.sections._.list = r.stations;
            }

            this.stations.listValue = function(s, i, d) { return d.name; }
            this.stations.listFn = function(s, i, d) { 
                return 'Q.startApp(Q.stationMenu,null,\'Q.qruqsp_core_menu.stations.show();\',\'mc\',{\'id\':' + d.id + '});';
            }
            this.stations.cellValue = function(s, i, j, d) {
                switch(s) {
                    case 'categories': return (d.name!=''?d.name:'Default') + ' <span class="count">' + d.stations.length + '</span>';
                    case 'list': return d.name;
                }
            };
            this.stations.switchCategory = function(i) {
                this.curCategory = i;
                this.refreshSection('list');
            };
            this.stations.rowFn = function(s, i, d) {
                switch (s) {
                    case 'categories': return 'Q.qruqsp_core_menu.stations.switchCategory(\'' + i + '\');';
                    case 'list': return 'Q.startApp(Q.stationMenu,null,\'Q.qruqsp_core_menu.stations.show();\',\'mc\',{\'id\':' + d.id + '});';
                }
            };
            this.stations.addLeftButton('logout', 'Logout', 'Q.logout();');
            if( Q.userID > 0 && (Q.userPerms&0x01) == 0x01 ) {
                this.stations.addLeftButton('bigboard', 'bigboard', 'Q.startApp(\'qruqsp.sysadmin.bigboard\',null,\'Q.qruqsp_core_menu.stations.show();\');');
            }

            // Add searching
            this.stations.liveSearchCb = function(s, i, v) {
                if( v != '' ) {
                    Q.api.getJSONBgCb('qruqsp.stations.searchStations', {'station_id':Q.curBusinessID, 'start_needle':v, 'limit':'15'},
                        function(rsp) {
                            Q.qruqsp_core_menu.stations.liveSearchShow(s, null, Q.gE(Q.qruqsp_core_menu.stations.panelUID + '_' + s), rsp.stations);
                        });
                }
                return true;
            };
            this.stations.liveSearchResultValue = function(s, f, i, j, d) {
                return d.name;
            };
            this.stations.liveSearchResultRowFn = function(s, f, i, j, d) {
                return 'Q.startApp(Q.stationMenu,null,\'Q.qruqsp_core_menu.stations.show();\',\'mc\',{\'id\':\'' + d.id + '\'});';
            };
            this.stations.liveSearchResultRowStyle = function(s, f, i, d) { return ''; };

            Q.menuHome = this.stations;
            
            if( typeof(localStorage) !== 'undefined' && localStorage.getItem('lastBusinessID') > 0 ) {
                Q.startApp(Q.stationMenu,null,'Q.qruqsp_core_menu.stations.show();','mc',{'id':localStorage.getItem('lastBusinessID')});
            } else {
                Q.menuHome.show();
            }

            //
            // Check if there is a set of login actions to perform
            //
            if( r.loginActions != null ) {
                eval(r.loginActions);
            }
        }
    }
}
