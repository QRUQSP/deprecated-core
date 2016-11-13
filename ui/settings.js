//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function qruqsp_core_settings() {

    this.menu = new Q.panel('Station Settings', 'qruqsp_core_settings', 'menu', 'mc', 'narrow', 'sectioned', 'qruqsp.businesses.settings.menu');
    this.menu.addClose('Back');

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
        
        // 
        // Clear old menu
        //
        this.menu.reset();

        //
        // Setup the Station Settings 
        //
        this.menu.sections = {
            '':{'label':'', 'aside':'yes', 'list':{
//                'info':{'label':'Station Info', 'fn':'Q.startApp(\'qruqsp.station.info\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
//                'users':{'label':'Owners & Employees', 'fn':'Q.startApp(\'qruqsp.businesses.users\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
//                'social':{'label':'Social Media', 'fn':'Q.startApp(\'qruqsp.businesses.social\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
//                'intl':{'label':'Localization', 'fn':'Q.startApp(\'qruqsp.businesses.intl\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
//                'billing':{'label':'Billing', 'fn':'Q.startApp(\'qruqsp.businesses.billing\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
                }}};

        //
        // Check for settings_menu_items
        //
        if( Q.curStation.settings_menu_items != null ) {
            this.menu.sections['modules'] = {'label':'', 'aside':'yes', 'list':{}};
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
                this.menu.sections.modules.list[i] = item;
            }
        }
    
        //
        // Advaned options for Sysadmins or resellers
        //
        if( Q.userID > 0 && ((Q.userPerms&0x01) == 0x01 || Q.curStation.permissions.resellers != null) ) {
            //
            // Setup the advanced section for resellers and admins
            //
            this.menu.sections['advanced'] = {'label':'Admin', 'list':{
                'modules':{'label':'Modules', 'fn':'Q.startApp(\'qruqsp.core.stationModules\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
                'moduleflags':{'label':'Module Flags', 'fn':'Q.startApp(\'qruqsp.core.stationModuleFlags\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
            }};
//            this.menu.size = 'narrow narrowaside';

            //
            // Setup the sysadmin only options
            //
            if( Q.userID > 0 && (Q.userPerms&0x01) == 0x01 ) {
                this.menu.sections['admin'] = {'label':'SysAdmin', 'list':{
//                    'sync':{'label':'Syncronization', 'fn':'Q.startApp(\'qruqsp.businesses.sync\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
//                    'CSS':{'label':'CSS', 'fn':'Q.startApp(\'qruqsp.businesses.css\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
//                    'webdomains':{'label':'Domains', 'fn':'Q.startApp(\'qruqsp.businesses.domains\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
//                    'assets':{'label':'Image Assets', 'fn':'Q.startApp(\'qruqsp.businesses.assets\', null, \'Q.qruqsp_core_settings.menu.show();\');'},
                    }};
            }
        }

        //
        // Show the settings menu
        //
        this.menu.show(cb);
    }
}

