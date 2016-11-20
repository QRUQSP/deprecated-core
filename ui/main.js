//
// This class will display the main menu for the system and the list of stations,
// and display the menu for a station.
//
function qruqsp_core_main() {
    //
    // Store any login actions to be run on the station menu
    //
    this.loginActions = null;

    //
    // The panel to display the list of stations
    //
    this.stations = new Q.panel('Stations', 'qruqsp_core_main', 'stations', 'mc', 'narrow', 'sectioned', 'qruqsp.core.main.stations');
    this.stations.data = {};
    this.stations.curCategory = 0;
    this.stations.sections = {
        'categories':{'label':'Categories', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return Q.qruqsp_core_main.stations.data.categories != null && Q.qruqsp_core_main.stations.data.categories.length > 1 ? 'yes' : 'no';},
            },
        'search':{'label':'', 'autofocus':'yes', 'type':'livesearchgrid', 'livesearchcols':1,
            'visible':function() { return Q.qruqsp_core_main.stations.data.categories != null && Q.qruqsp_core_main.stations.data.categories.length > 1 ? 'yes' : 'no';},
            'hint':'Search',
            'noData':'No stations found',
            },
        'stations':{'label':'', 'type':'simplegrid', 'num_cols':1,
            },
    }
    this.stations.liveSearchCb = function(s, i, v) {
        if( v != '' ) {
            Q.api.getJSONBgCb('qruqsp.core.stationSearch', {'start_needle':v, 'limit':'15'},
                function(rsp) {
                    Q.qruqsp_core_main.stations.liveSearchShow(s, null, Q.gE(Q.qruqsp_core_main.stations.panelUID + '_' + s), rsp.stations);
                });
        }
        return true;
    }
    this.stations.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.stations.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'Q.qruqsp_core_main.station.open(\'Q.qruqsp_core_main.stations.show();\',\'' + d.id + '\');';
    }
    this.stations.cellValue = function(s, i, j, d) {
        switch(s) {
            case 'categories': return (d.name!=''?d.name:'Default') + ' <span class="count">' + d.stations.length + '</span>';
            case 'stations': return d.name;
        }
    }
    this.stations.switchCategory = function(i) {
        this.data.stations = this.data.categories[i];
        this.refreshSection('stations');
    }
    this.stations.rowFn = function(s, i, d) {
        switch (s) {
            case 'categories': return 'Q.qruqsp_core_main.stations.switchCategory(\'' + i + '\');';
            case 'stations': return 'Q.qruqsp_core_main.station.open(\'Q.qruqsp_core_main.stations.show();\',\'' + d.id + '\');';
        }
    }
    this.stations.open = function(cb) {
        var args = {};
        if( typeof(localStorage) !== 'undefined' && localStorage.getItem('lastStationID') > 0 ) {
            args['station_id'] = localStorage.getItem('lastStationID');
        }
        Q.api.getJSONCb('qruqsp.core.userStations', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                Q.api.err(rsp);
                return false;
            }
            var p = Q.qruqsp_core_main.stations;
            //
            // Check if no stations or categories
            //
            if( (rsp.categories == null && rsp.stations == null) 
                || (rsp.categories != null && rsp.categories.length < 1)
                || (rsp.stations != null && rsp.stations.length < 1) ) {
                alert('Error - no stations found');
                return false;
            } 
            //
            // Set the size for the station list
            //
            if( (Q.userPerms&0x01) == 0x01 && rsp.categories != null && rsp.categories.length > 1 ) {
                p.size = 'medium narrowaside';
            } else {
                p.size = 'narrow';
            }
            p.data = rsp;
            if( rsp.categories != null ) {
                p.data.stations = rsp.categories[p.curCategory].stations;
            } 
            p.refresh();
            p.show();

            //
            // Check if station to reopen or only 1 station
            //
            if( rsp != null && rsp.station != null ) {
                Q.qruqsp_core_main.station.open(cb,rsp.station.id, rsp);
//                return true;
            }

            //
            // Check if there is a set of login actions to perform
            //
            if( rsp.loginActions != null ) {
                Q.qruqsp_core_main.loginActions = rsp.loginActions;
            }
        });
        
    }
    this.stations.addLeftButton('logout', 'Logout', 'Q.logout();');
    if( Q.userID > 0 && (Q.userPerms&0x01) == 0x01 ) {
        this.stations.addLeftButton('bigboard', 'bigboard', 'Q.startApp(\'qruqsp.admin.bigboard\',null,\'Q.qruqsp_core_main.stations.show();\');');
    }
    this.stations.addButton('account', 'Account', 'Q.startApp(\'qruqsp.core.account\',null,\'Q.qruqsp_core_main.stations.show();\');');
    if( Q.userID > 0 && (Q.userPerms&0x01) == 0x01 ) {
        this.stations.addButton('admin', 'Admin', 'Q.startApp(\'qruqsp.admin.main\',null,\'Q.qruqsp_core_main.stations.show();\');');
    }

    //
    // The main menu panel for a station
    //
    this.station = new Q.panel('Station', 'qruqsp_core_main', 'station', 'mc', 'medium', 'sectioned', 'qruqsp.core.main.station');
    this.station.data = {};
    this.station.sections = {};
    this.station.liveSearchCb = function(s, i, value) {
        if( this.sections[s].search != null && value != '' ) {
            var sargs = (this.sections[s].search.args != null ? this.sections[s].search.args : []);
            sargs['station_id'] = Q.curStationID;
            sargs['start_needle'] = value;
            sargs['limit'] = 10;
            var container = this.sections[s].search.container;
            Q.api.getJSONBgCb(this.sections[s].search.method, sargs, function(rsp) {
                Q.qruqsp_core_main.station.liveSearchShow(s, null, Q.gE(Q.qruqsp_core_main.station.panelUID + '_' + s), rsp[container]);
            });
            return true;
        }
    }
    this.station.liveSearchResultClass = function(s, f, i, j, d) {
        if( this.sections[s].search != null ) {
            if( this.sections[s].search.cellClasses != null && this.sections[s].search.cellClasses[j] != null ) {
                return this.sections[s].search.cellClasses[j];
            }
        }
        return '';
    }
    this.station.liveSearchResultValue = function(s, f, i, j, d) {
        if( this.sections[s].search != null && this.sections[s].search.cellValues != null ) {
            return eval(this.sections[s].search.cellValues[j]);
        }
        return '';
    }
    this.station.liveSearchResultRowFn = function(s, f, i, j, d) { 
        if( this.sections[s].search != null ) {
            if( this.sections[s].search.edit != null ) {
                var args = '';
                for(var i in this.sections[s].search.edit.args) {
                    args += (args != '' ? ', ':'') + '\'' + i + '\':' + eval(this.sections[s].search.edit.args[i]);
                }
                return 'Q.startApp(\'' + this.sections[s].search.edit.method + '\',null,\'Q.qruqsp_core_main.station.reopen();\',\'mc\',{' + args + '});';
            } 
            return null;
        }
        return null;
    }
    this.station.liveSearchResultRowStyle = function(s, f, i, d) {
        if( this.sections[s].search.rowStyle != null ) {
            return eval(this.sections[s].search.rowStyle);
        }
        return '';
    }
    this.station.liveSearchSubmitFn = function(s, search_str) {
        if( this.sections[s].search != null && this.sections[s].search.submit != null ) {
            var args = {};
            for(var i in this.sections[s].search.submit.args) {
                args[i] = eval(this.sections[s].search.submit.args[i]);
            }
            Q.startApp(this.sections[s].search.submit.method,null,'Q.qruqsp_core_main.station.reopen();','mc',args);
        }
    }
    this.station.liveSearchResultCellFn = function(s, f, i, j, d) {
        if( this.sections[s].search != null ) {
            if( this.sections[s].search.cellFns != null && this.sections[s].search.cellFns[j] != null ) {
                return eval(this.sections[s].search.cellFns[j]);
            }
        }
        return '';
    }
    this.station.liveSearchResultCellColour = function(s, f, i, j, d) {
        if( this.sections[s].search != null ) {
            if( this.sections[s].search.cellColours != null && this.sections[s].search.cellColours[j] != null ) {
                return eval(this.sections[s].search.cellColours[j]);
            }
        }
        return '';
    }
    this.station.open = function(cb, sid, rsp) {
        if( sid != null ) { 
            //
            // Set the current station ID so it's available everywhere
            //
            Q.curStationID = sid; 

            //
            // (re)set the station object
            //
            delete Q.curStation;
            Q.curStation = {'id':sid};

            //
            // Check if this is the main menu for the user (only 1 station)
            //
            this.leftbuttons = {};
            this.rightbuttons = {};
            if( cb == null ) {
                this.addButton('account', 'Account', 'Q.startApp(\'qruqsp.core.account\',null,\'Q.qruqsp_core_main.station.reopen();\');');
                if( Q.userID > 0 && (Q.userPerms&0x01) == 0x01 ) {
                    this.addLeftButton('admin', 'Admin', 'Q.startApp(\'qruqsp.admin.main\',null,\'Q.qruqsp_core_main.station.reopen();\');');
                }
            } else {
                this.addClose('Back');
                if( typeof(Storage) !== 'undefined' ) {
                    localStorage.setItem('lastStationID', Q.curStationID);
                }
            }
            this.cb = cb;
        }
        if( Q.curStationID == null || Q.curStationID == 0 ) {
            alert("Invalid station");
            return false;
        }
        if( rsp != null ) {
            // Station details where loaded as part of userStations API Call
            this.setup(rsp);
        } else {
            Q.api.getJSONCb('qruqsp.core.userStationSettings', {'station_id':Q.curStationID}, Q.qruqsp_core_main.station.setup);
        }
    }
    // When returning from a module, issue reopen so it doesn't reset station details in Q.curStation
    this.station.reopen = function() {
        Q.api.getJSONCb('qruqsp.core.userStationSettings', {'station_id':Q.curStationID}, Q.qruqsp_core_main.station.setup);
    }
    this.station.setup = function(rsp) {
        if( rsp.stat != 'ok' ) {
            Q.api.err(rsp);
            return false;
        }
        Q.curStation = rsp.station;
        delete Q.curStation.stat;

        var p = Q.qruqsp_core_main.station;
        p.title = Q.curStation.name;

        //
        // Setup CSS
        //
        if( rsp.settings != null && rsp.settings.ui != null && rsp.settings.ui.css != null ) {
            Q.gE('station_colours').innerHTML = rsp.settings.ui.css;
        } else {
            Q.gE('station_colours').innerHTML = Q.defaultStationColours;
        }

        //
        // If admin, or station owner
        //
        if( Q.userID > 0 && ( (Q.userPerms&0x01) == 0x01 || Q.curStation.permissions.operators != null )) {
            p.addButton('settings', 'Settings', 'Q.startApp(\'qruqsp.core.settings\',null,\'Q.qruqsp_core_main.station.reopen();\');');
        }

        var c = 0;
        var join = -1;  // keep track of how many are already joined together
        p.sections = {};
        var menu_search = 0;

        //
        // Build the main menu from the items supplied
        //
        if( rsp.station.menu_items != null ) {
            // Get the number of search items
            for(var i in rsp.station.menu_items) {
                if( rsp.station.menu_items[i].search != null ) {
                    menu_search++
                }
            }
            if( menu_search < 2 ) {
                menu_search = 0;
            }
            for(var i in rsp.station.menu_items) {
                var item = {'label':rsp.station.menu_items[i].label};
                if( rsp.station.menu_items[i].edit != null ) {
                    var args = '';
                    if( rsp.station.menu_items[i].edit.args != null ) {
                        for(var j in rsp.station.menu_items[i].edit.args) {
                            args += (args != '' ? ', ':'') + '\'' + j + '\':' + eval(rsp.station.menu_items[i].edit.args[j]);
                        }
                        item.fn = 'Q.startApp(\'' + rsp.station.menu_items[i].edit.app + '\',null,\'Q.qruqsp_core_main.station.reopen();\',\'mc\',{' + args + '});';
                    } else {
                        item.fn = 'Q.startApp(\'' + rsp.station.menu_items[i].edit.app + '\',null,\'Q.qruqsp_core_main.station.reopen();\');';
                    }
                } else if( rsp.station.menu_items[i].fn != null ) {
                    item.fn = rsp.station.menu_items[i].fn;
                }
                if( rsp.station.menu_items[i].count != null ) {
                    item.count = rsp.station.menu_items[i].count;
                }
                if( rsp.station.menu_items[i].add != null && menu_search > 0 ) {
                    var args = '';
                    for(var j in rsp.station.menu_items[i].add.args) {
                        args += (args != '' ? ', ':'') + '\'' + j + '\':' + eval(rsp.station.menu_items[i].add.args[j]);
                    }
                    item.addFn = 'Q.startApp(\'' + rsp.station.menu_items[i].add.app + '\',null,\'Q.qruqsp_core_main.station.reopen();\',\'mc\',{' + args + '});';
                }

                if( rsp.station.menu_items[i].search != null && menu_search > 0 ) {
                    item.search = rsp.station.menu_items[i].search;
                    if( rsp.station.menu_items[i].id != null ) {
                        item.id = rsp.station.menu_items[i].id;
                    }
                    item.type = 'livesearchgrid';
                    item.searchlabel = item.label;
                    item.aside = 'yes';
                    item.label = '';
                    item.livesearchcols = item.search.cols;
                    item.noData = item.search.noData;
                    if( item.search.headerValues != null ) {
                        item.headerValues = item.search.headerValues;
                    }
                    if( rsp.station.menu_items[i].search.searchtype != null && rsp.station.menu_items[i].search.searchtype != '' ) {
                        item.livesearchtype = rsp.station.menu_items[i].search.searchtype;
                    }
                    p.sections[c++] = item;
                    menu_search = 1;
                }
                else if( rsp.station.menu_items[i].subitems != null ) {
                    item.aside = 'yes';
                    item.list = {};
                    for(var j in rsp.station.menu_items[i].subitems) {
                        var args = '';
                        for(var k in rsp.station.menu_items[i].subitems[j].edit.args) {
                            args += (args != '' ? ', ':'') + '\'' + k + '\':' + eval(rsp.station.menu_items[i].subitems[j].edit.args[k]);
                        }
                        item.list[j] = {'label':rsp.station.menu_items[i].subitems[j].label, 'fn':'Q.startApp(\'' + rsp.station.menu_items[i].subitems[j].edit.app + '\',null,\'Q.qruqsp_core_main.station.reopen();\',\'mc\',{' + args + '});'};
                    }
                    p.sections[c] = item;
                    menu_search = 0;
                    join = 0;
                    c++;
                    p.sections[c] = {'label':'Menu', 'aside':'yes', 'list':{}};
                }
                else if( join > -1 ) {
                    p.sections[c].list['item_' + i] = item;
                    join++;
                } else {
                    p.sections[c++] = {'label':'', 'aside':'yes', 'list':{'_':item}};
                }
                if( c > 4 && join < 0 ) {
                    join = 0;
                    p.sections[c] = {'label':' &nbsp; ', 'aside':'yes', 'list':{}};
                }
            }
        }

        //
        // Setup the auto split if long menu
        //
        if( join > 8 ) {
            p.sections[c].as = 'yes';
        }

        //
        // Check if we should autoopen the submenu when there is only one menu item.
        //
/*        if( autoopen == 'yes' && c == 1 
            && p.sections[0].list != null 
            && p.sections[0].list._ != null 
            && p.sections[0].list._.fn != null ) {
            p.autoopen = 'skipped';
            eval(p.sections[0].list._.fn);
        } else {
            p.autoopen = 'no';
        } */

        // Set size of menu based on contents
        if( menu_search == 1 ) {
            p.size = 'medium';
        } else {
            p.size = 'narrow';
        }

        p.refresh();
        p.show();

        //
        // Run login options
        //
        if( rsp.loginActions != null ) {
            eval(rsp.loginActions);
        } else if( Q.qruqsp_core_main.loginActions != null ) {
            eval(Q.qruqsp_core_main.loginActions);
        }
    }

    this.start = function(cb) {
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = Q.createContainer('mc', 'qruqsp_core_main', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 
    
        this.stations.open(cb);
    }
}
