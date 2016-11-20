//
// This class will display the form to allow admins and station operators to 
// change the details of their station
//
function qruqsp_core_settings() {

    //
    // The main menu for the settings panel
    //
    this.menu = new Q.panel('Station Settings', 'qruqsp_core_settings', 'menu', 'mc', 'narrow', 'sectioned', 'qruqsp.core.settings.menu');
    this.menu.sections = {
        'general':{'label':'', 'aside':'yes', 'list':{
            'general':{'label':'Station Info', 'fn':'Q.qruqsp_core_settings.info.open(\'Q.qruqsp_core_settings.menu.open();\');'},
            }},
        'modules':{'label':'', 'aside':'yes', 'list':{
            }},
        'advanced':{'label':'Admin', 
            'visible':function() { return (Q.userID > 0 && ((Q.userPerms&0x01) == 0x01 || Q.curStation.permissions.resellers != null)) ? 'yes' : 'no'; },
            'list':{
                'modules':{'label':'Modules', 'fn':'Q.qruqsp_core_settings.modules.open(\'Q.qruqsp_core_settings.menu.open();\');'},
                'modulesflags':{'label':'Module Options', 'fn':'Q.qruqsp_core_settings.moduleflags.open(\'Q.qruqsp_core_settings.menu.open();\');'},
            }},
        'admin':{'label':'', 
            'visible':function() { return (Q.userID > 0 && (Q.userPerms&0x01) == 0x01) ? 'yes' : 'no'; },
            'list':{
//              'sync':{'label':'Syncronization', 'fn':'Q.startApp(\'qruqsp.stations.sync\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
//              'CSS':{'label':'CSS', 'fn':'Q.startApp(\'qruqsp.stations.css\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
//              'webdomains':{'label':'Domains', 'fn':'Q.startApp(\'qruqsp.stations.domains\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
//              'assets':{'label':'Image Assets', 'fn':'Q.startApp(\'qruqsp.stations.assets\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
            }},
    }
    this.menu.open = function(cb) {
        this.sections.modules.list = {};
//      this.size = (Q.userID > 0 && ((Q.userPerms&0x01) == 0x01 || Q.curStation.permissions.resellers != null)) ? 'narrow narrowaside' : 'narrow';
        // Add the settings menu items
        if( Q.curStation.settings_menu_items != null ) {
            for(var i in Q.curStation.settings_menu_items) {
                var item = {'label':Q.curStation.settings_menu_items[i].label};
                if( Q.curStation.settings_menu_items[i].edit != null ) {
                    var args = '';
                    if( Q.curStation.settings_menu_items[i].edit.args != null ) {
                        for(var j in Q.curStation.settings_menu_items[i].edit.args) {
                            args += (args != '' ? ', ':'') + '\'' + j + '\':' + eval(Q.curStation.settings_menu_items[i].edit.args[j]);
                        }
                        item.fn = 'Q.startApp(\'' + Q.curStation.settings_menu_items[i].edit.app + '\',null,\'Q.qruqsp_core_settings.menu.show();\',\'mc\',{' + args + '});';
                    } else {
                        item.fn = 'Q.startApp(\'' + Q.curStation.settings_menu_items[i].edit.app + '\',null,\'Q.qruqsp_core_settings.menu.show();\');';
                    }
                }
                this.sections.modules.list[i] = item;
            }
        }
        this.refresh();
        this.show(cb);
    }
    this.menu.addClose('Back');

    //
    // The station information
    //
    this.info = new Q.panel('Station Information', 'qruqsp_core_settings', 'info', 'mc', 'medium', 'sectioned', 'qruqsp.core.settings.info');
    this.info.data = {};
    this.info.sections = {
        'general':{'label':'General', 'fields':{
            'station-name':{'label':'Name', 'type':'text'},
            'station-category':{'label':'Category', 'active':function() { return ((Q.userPerms&0x01)==1?'yes':'no');}, 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'station-sitename':{'label':'Sitename', 'active':function() { return ((Q.userPerms&0x01)==1?'yes':'no');}, 'type':'text'},
            'station-tagline':{'label':'Tagline', 'type':'text'},
            }},
        'contact':{'label':'Contact', 'fields':{
            'contact-person-name':{'label':'Name', 'type':'text'},
            'contact-phone-number':{'label':'Phone', 'type':'text'},
            'contact-cell-number':{'label':'Cell', 'type':'text'},
            'contact-fax-number':{'label':'Fax', 'type':'text'},
            'contact-email-address':{'label':'Email', 'type':'text'},
            }},
        'address':{'label':'Address', 'fields':{
            'contact-address-street1':{'label':'Street', 'type':'text'},
            'contact-address-street2':{'label':'Street', 'type':'text'},
            'contact-address-city':{'label':'City', 'type':'text'},
            'contact-address-province':{'label':'Province', 'type':'text'},
            'contact-address-postal':{'label':'Postal', 'type':'text'},
            'contact-address-country':{'label':'Country', 'type':'text'},
            }}
        };
    this.info.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.core.stationSettingsHistory', 'args':{'station_id':Q.curStationID, 'field':i}};
    }
    this.info.fieldValue = function(s, i, d) { return this.data[i]; }
    this.info.liveSearchCb = function(s, i, value) {
        if( i == 'station-category' ) {
            Q.api.getJSONBgCb('qruqsp.core.stationCategorySearch', {'station_id':Q.curStationID, 'start_needle':value, 'limit':15}, function(rsp) {
                Q.qruqsp_core_settings.info.liveSearchShow(s, i, Q.gE(Q.qruqsp_core_settings.info.panelUID + '_' + i), rsp.results);
            });
        }
    };
    this.info.liveSearchResultValue = function(s, f, i, j, d) {
        if( f == 'station-category' ) { return d.name; }
        return '';
    };
    this.info.liveSearchResultRowFn = function(s, f, i, j, d) { 
        if( f == 'station-category' ) {
            return 'Q.qruqsp_core_settings.info.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.name) + '\');';
        }
    };
    this.info.updateField = function(s, fid, result) {
        Q.gE(this.panelUID + '_' + fid).value = unescape(result);
        this.removeLiveSearch(s, fid);
    }; 
    this.info.open = function(cb) {
        Q.api.getJSONCb('qruqsp.core.stationSettingsGet', {'station_id':Q.curStationID, 'keys':'station,contact'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                Q.api.err(rsp);
                return false;
            }
            var p = Q.qruqsp_core_settings.info;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    }
    this.info.save = function() {
        // Serialize the form data into a string for posting
        var c = this.serializeForm('no');
        if( c != '' ) {
            Q.api.postJSONCb('qruqsp.core.stationSettingsUpdate', {'station_id':Q.curStationID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    Q.api.err(rsp);
                    return false;
                }
                Q.qruqsp_core_settings.info.close();
            });
        } else {
            this.close();
        }
    }
    this.info.addButton('save', 'Save', 'Q.qruqsp_core_settings.info.save();');
    this.info.addClose('Cancel');

    //
    // The module edit panel
    //
    this.modules = new Q.panel('Modules', 'qruqsp_core_settings', 'modules', 'mc', 'medium', 'sectioned', 'qruqsp.core.settings.modules');
    this.modules.sections = {
        'modules':{'label':'', 'hidelabel':'yes', 'fields':{}},
    }
    this.modules.fieldValue = function(s, i, d) { return this.data[i].status; }
    this.modules.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.core.stationModuleHistory', 'args':{'station_id':Q.curStationID, 'field':i}};
    }
    this.modules.open = function(cb) {
        Q.api.getJSONCb('qruqsp.core.stationModuleList', {'station_id':Q.curStationID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                Q.api.err(rsp);
                return false;
            }
            var p = Q.qruqsp_core_settings.modules;
            p.data = {};
            p.sections.modules.fields = {};
            for(i in rsp.modules) {
                p.data[rsp.modules[i].package + '.' + rsp.modules[i].name] = rsp.modules[i];
                p.sections.modules.fields[rsp.modules[i].package + '.' + rsp.modules[i].name] = {
                    'id':rsp.modules[i].name, 'label':rsp.modules[i].label, 'type':'toggle', 'toggles':{'0':' Off ', '1':' On '},
                    };
            }
            p.show(cb);
        });
    }
    this.modules.save = function() {
        // Serialize the form data into a string for posting
        var c = this.serializeForm('no');
        if( c != '' ) {
            Q.api.postJSONCb('qruqsp.core.stationModulesUpdate', {'station_id':Q.curStationID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    Q.api.err(rsp);
                    return false;
                }
                Q.qruqsp_core_settings.modules.close();
            });
        } else {
            this.close();
        }
    }
    this.modules.addButton('save', 'Save', 'Q.qruqsp_core_settings.modules.save();');
    this.modules.addClose('Cancel');

    //
    // The panel to set module flags for a station
    //
    this.moduleflags = new Q.panel('Module Options', 'qruqsp_core_settings', 'moduleflags', 'mc', 'medium', 'sectioned', 'qruqsp.core.settings.moduleflags');
    this.moduleflags.data = {};
    this.moduleflags.fieldValue = function(s, i, d) { return this.data[i].flags; }
    // History needs to be fixed to display correctly.
//    this.moduleflags.fieldHistoryArgs = function(s, i) {
//        return {'method':'qruqsp.core.stationModuleFlagsHistory', 'args':{'station_id':Q.curStationID, 'field':i}};
//    }
    this.moduleflags.open = function(cb) {
        Q.api.getJSONCb('qruqsp.core.stationModuleFlagsGet', {'station_id':Q.curStationID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                Q.api.err(rsp);
                return false;
            }
            var p = Q.qruqsp_core_settings.moduleflags;
            p.sections = {};
            //
            // Setup the list of modules into the form fields
            // 
            p.data = rsp.modules;   
            for(i in rsp.modules) {
                if( rsp.modules[i].available_flags != null ) {
                    var flags = {};
                    for(j in rsp.modules[i].available_flags) {
                        flags[rsp.modules[i].available_flags[j].flag.bit] =
                            {'name':rsp.modules[i].available_flags[j].flag.name};
                    }
                    p.sections[i] = { 'label':rsp.modules[i].proper_name, 'fields':{}};
                    p.sections[i].fields[i] = {'label':'', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':flags};
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.moduleflags.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            Q.api.postJSONCb('qruqsp.core.stationModuleFlagsUpdate', {'station_id':Q.curStationID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    Q.api.err(rsp);
                    return false;
                }
                Q.qruqsp_core_settings.moduleflags.close();
            });
        } else {
            this.close();
        }
    }
    this.moduleflags.addButton('save', 'Save', 'Q.qruqsp_core_settings.moduleflags.save();');
    this.moduleflags.addClose('Cancel');

    //
    // Start the app
    //
    this.start = function(cb, ap, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = Q.createContainer('mc', 'qruqsp_core_settings', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.menu.open(cb);
    }
}
