//
// This app is for sysadmins to change the details of the install
//
function qruqsp_core_admin() {
    //
    // The main account panel
    //
    this.menu = new Q.panel('System Admin', 'qruqsp_core_admin', 'menu', 'mc', 'narrow', 'sectioned', 'qruqsp.core.admin.menu');
    this.menu.sections = {
        'stations':{'label':'Stations', 'list':{
            'add':{'label':'Add Station', 'fn':'Q.qruqsp_core_admin.stations.open(\'Q.qruqsp_core_admin.menu.show();\');'},
        }},
        'users':{'label':'Users', 'list':{
            'sysadmins':{'label':'Sys Admins', 'fn':'Q.qruqsp_core_admin.sysadmins.open(\'Q.qruqsp_core_admin.menu.show();\');'},
            'locked':{'label':'Locked Users', 'fn':'Q.qruqsp_core_admin.lockedusers.open(\'Q.qruqsp_core_admin.menu.show();\');'},
            'all':{'label':'All Users', 'fn':'Q.qruqsp_core_admin.users.open(\'Q.qruqsp_core_admin.menu.show();\');'},
        }},
        'system':{'label':'System', 'list':{
            'database':{'label':'Table Versions', 'fn':'Q.qruqsp_core_admin.dbtables.open(\'Q.qruqsp_core_admin.menu.show();\');'},
//            'code':{'label':'Code Versions', 'fn':'Q.qruqsp_core_admin.code.open(\'Q.qruqsp_core_admin.menu.show();\');'},
//            'modules':{'label':'Module Usage', 'fn':'Q.qruqsp_core_admin.modules.open(\'Q.qruqsp_core_admin.menu.show();\');'},
        }},
    }
    this.menu.addClose('Back');

    //
    // Create the panel for the database table information
    //
    this.dbtables = new Q.panel('Table Versions', 'qruqsp_core_admin', 'dbtables', 'mc', 'medium', 'sectioned', 'qruqsp.core.admin.dbtables');
    this.dbtables.sections = {
        '_':{'label':'', 'type':'simplegrid', 'num_cols':3, 
            'headerValues':['Table', 'Database', 'Current'],
            },
        };
    this.dbtables.sectionData = function(s) { return this.data; }
    this.dbtables.cellClass = function(s, i, j, d) {
        if( d.database_version != d.schema_version ) {
            return 'alert';
        }
        return null;
    }
    this.dbtables.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return i;
            case 1: return this.data[i].database_version;
            case 2: return this.data[i].schema_version;
        }
        return '';
    }
    this.dbtables.open = function(cb) {
        Q.api.getJSONCb('qruqsp.core.adminDBTableVersions', {}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                Q.api.err(rsp);
                return false;
            }
            var count = 0;
            var p = Q.qruqsp_core_admin.dbtables;
            p.data = {};
            //
            // Add the tables which need upgrading first, so they appear at the top of the list
            //
            for(i in rsp.tables) {
                // outdated tables
                if( rsp.tables[i].database_version != rsp.tables[i].schema_version ) {
                    p.data[i] = rsp.tables[i];
                    count++;
                }
            }
            for(i in rsp.tables) {
                // Current tables
                if( rsp.tables[i].database_version == rsp.tables[i].schema_version ) {
                    p.data[i] = rsp.tables[i];
                    count++;
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.dbtables.upgrade = function() {
        Q.api.getJSONCb('qruqsp.core.adminDBUpgradeTables', {}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                alert("Error: #" + rsp.err.code + ' - ' + rsp.err.msg);
                return false;
            }
            Q.qruqsp_core_admin.dbtables.open();
        });
    }
    this.dbtables.addButton('update', 'Upgrade', 'Q.qruqsp_core_admin.dbtables.upgrade();');
    this.dbtables.addClose('Back');

    //
    // The function to start this app
    //
    this.start = function(cb, ap, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = Q.createContainer('mc', 'qruqsp_core_admin', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.menu.show(cb);
    }
}
