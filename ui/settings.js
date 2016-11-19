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
