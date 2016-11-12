//
// This class will display the form to allow admins and station owners to 
// change the details of their station
//
function qruqsp_core_account() {
    //
    // The main account panel
    //
    this.menu = new Q.panel('My Account', 'qruqsp_core_account', 'menu', 'mc', 'narrow', 'sectioned', 'qruqsp.core.account.menu');
    this.menu.sections = {
        '_':{'label':'', 'list':{
            'myinfo':{'label':'My Information', 'fn':'Q.qruqsp_core_account.info.open(\'Q.qruqsp_core_account.menu.show();\');'},
            'prefs':{'label':'Preferences', 'fn':'Q.qruqsp_core_account.prefs.open(\'Q.qruqsp_core_account.menu.show();\');'},
//            'avatar':{'label':'Avatar', 'fn':'Q.qruqsp_core_account.avatar.open(\'Q.qruqsp_core_account.menu.show();\');'},
            'password':{'label':'Change Password', 'fn':'Q.qruqsp_core_account.chgpwd.open(\'Q.qruqsp_core_account.menu.show();\');'},
        }},
    }
    this.menu.addClose('Back');

    //
    // The account settings
    //
    this.info = new Q.panel('My Info', 'qruqsp_core_account', 'info', 'mc', 'narrow', 'sectioned', 'qruqsp.core.account.info');
    this.info.sections = {
        'name':{'label':'Name', 'fields':{
            'user.callsign':{'label':'Callsign', 'type':'text'},
            'user.display_name':{'label':'Display', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'Q.qruqsp_core_account.info.save();'},
            }},
    };
    this.info.fieldValue = function(s, i, d) { return this.data[i]; }
    this.info.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.core.userDetailHistory', 'args':{'user_id':Q.userID, 'field':i}};
    }
    this.info.open = function(cb) {
        Q.api.getJSONCb('qruqsp.core.userDetails', {'user_id':Q.userID, 'keys':'user'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                Q.api.err(rsp);
                return false;
            }
            var p = Q.qruqsp_core_account.info;
            p.data = rsp.details;
            p.show(cb);
        });
    }
    this.info.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            Q.api.postJSONCb('qruqsp.core.userDetailsUpdate', {'user_id':Q.userID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    Q.api.err(rsp);
                    return false;
                }
                Q.qruqsp_core_account.info.close();
            });
        }
        this.close();
    }
    this.info.addButton('save', 'Save', 'Q.qruqsp_core_account.info.save();');
    this.info.addClose('Cancel');

    //
    // The user preferences panel
    //
    this.prefs = new Q.panel('My Preferences', 'qruqsp_core_account', 'prefs', 'mc', 'medium', 'sectioned', 'qruqsp.core.account.prefs');
    this.prefs.sections = {
        '_ui':{'label':'Interface Preferences', 'fields':{
            'ui-history-date-display':{'label':'History Date', 'type':'select', 'options':{
                'age':'10 days ago',
                'datetime':'Sep 9, 2012 8:40am',
                'datetimeage':'Sep 9, 2012 8:40am (10 days ago)',
                }},
            }},
//        '_calendar':{'label':'Calendar Options', 'fields':{
//            'ui-calendar-view':{'label':'Default View', 'type':'toggle', 'default':'mw', 'toggles':{'day':'Day', 'mw':'Month'}},
//            'ui-calendar-remember-date':{'label':'Remember Date', 'type':'toggle', 'default':'yes', 'toggles':{'no':'No', 'yes':'Yes'}},
//            }},
        '_prefs':{'label':'Preferences', 'fields':{
            'settings-time-format':{'label':'Time', 'type':'select', 'options':{
                '%l:%i %p':'1:00 pm',
                '%H:%i':'13:00',
                }},
            'settings-date-format':{'label':'Date', 'type':'select', 'options':{
                '%a %b %e, %Y':'Mon Jan 1, 2011',
                '%b %e, %Y':'Jan 1, 2011',
                '%Y-%m-%d':'2010-12-31',
                }},
            'settings-datetime-format':{'label':'Date and Time', 'type':'select', 'options':{
                '%b %e, %Y %l:%i %p':'Jan 1, 2011 1:00 pm',
                '%a %b %e, %Y %l:%i %p':'Mon Jan 1, 2011 1:00 pm',
                '%Y-%m-%d %H:%i':'2010-12-31 00:01',
                }},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'Q.qruqsp_core_account.prefs.save();'},
            }},
    };
    this.prefs.fieldValue = function(s, i, d) { return this.data[i]; }
    this.prefs.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.core.userDetailHistory', 'args':{'user_id':Q.userID, 'field':i}};
    }   
    this.prefs.open = function(cb) {
        Q.api.getJSONCb('qruqsp.core.userDetails', {'user_id':Q.userID, 'keys':'ui,settings'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                Q.api.err(rsp);
                return false;
            }
            var p = Q.qruqsp_core_account.prefs;
            p.data = rsp.details;
//            p.sections._calendar.fields['ui-calendar-view'].active = (rsp.details['ui-calendar-view'] != null?'yes':'no');
//            p.sections._calendar.fields['ui-calendar-remember-date'].active = (rsp.details['ui-calendar-remember-date'] != null?'yes':'no');
            p.refresh();
            p.show(cb);
        });
    }
    this.prefs.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            Q.api.postJSONCb('qruqsp.core.userDetailsUpdate', {'user_id':Q.userID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    Q.api.err(rsp);
                    return false;
                }
                Q.qruqsp_core_account.prefs.close();
            });
        } else {
            this.close();
        }
    }
    this.prefs.addButton('save', 'Save', 'Q.qruqsp_core_account.prefs.save();');
    this.prefs.addClose('Cancel');

    //
    // The avatar panel
    //
    this.avatar = new Q.panel('My Avatar', 'qruqsp_core_account', 'avatar', 'mc', 'narrow', 'sectioned', 'qruqsp.core.account.avatar');
    this.avatar.data = {'image_id':0};
    this.avatar.sections = {
        '_image':{'label':'Upload Photo', 'type':'imageform', 'fields':{
            'image_id':{'label':'', 'hidelabel':'yes', 'controls':'all', 'type':'image_id'},
            }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'Q.qruqsp_core_account.avatar.save();'},
            }},
    };
    this.avatar.fieldValue = function(s, i, d) { return this.data[i]; }
    this.avatar.addDropImageAPI = 'qruqsp.images.addUserImage';
    this.avatar.addDropImage = function(iid) {
        this.setFieldValue('image_id', iid);
        return true;
    };
    this.avatar.deleteImage = function(fid) {
        this.setFieldValue('image_id', 0);
        return true;
    };
    this.avatar.open = function(cb) {
        Q.curBusinessID = 0;
        Q.api.getJSONCb('qruqsp.core.userGet', {'user_id':Q.userID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                Q.api.err(rsp);
                return false;
            }
            var p = Q.qruqsp_core_account.avatar;
            p.data.image_id = rsp.user.avatar_id;
            p.refresh();
            p.show(cb);
        });
    }
    this.avatar.save = function() {
        var c = this.serializeForm('no');  
        if( c != '' ) {
            Q.api.postJSONCb('qruqsp.core.userAvatarUpdate', {'user_id':Q.userID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    Q.api.err(rsp);
                    return false;
                }
                Q.avatarID = rsp.avatar_id;
                Q.loadAvatar();
                Q.qruqsp_core_account.avatar.close();
            });
        } else {
            Q.qruqsp_core_account.avatar.close();
        }
    }
    this.avatar.addClose('Cancel');

    //
    // The change password panel
    //
    this.chgpwd = new Q.panel('Change Password', 'qruqsp_core_account', 'chgpwd', 'mc', 'narrow', 'sectioned', 'qruqsp.core.account.chgpwd');
    this.chgpwd.data = null;
    this.chgpwd.sections = { 
        '_oldpassword':{'label':'Current', 'fields':{
            'oldpassword':{'label':'Old Password', 'hidelabel':'yes', 'hint':'Old Password', 'type':'password'},
            }},
        '_newpassword':{'label':'New Password', 'fields':{
            'newpassword1':{'label':'New Password', 'hidelabel':'yes', 'hint':'New Password', 'type':'password'},
            'newpassword2':{'label':'Re-type New Password', 'hidelabel':'yes', 'hint':'Re-type New Password', 'type':'password'}
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'Q.qruqsp_core_account.chgpwd.save();'},
            }},
        }
    this.chgpwd.open = function(cb) {
        this.show(cb);
    }
    this.chgpwd.fieldValue = function(s, i, d) { return ''; }
    this.chgpwd.save = function() {
        var oldpassword = encodeURIComponent(document.getElementById(this.panelUID + '_oldpassword').value);
        var newpassword1 = encodeURIComponent(document.getElementById(this.panelUID + '_newpassword1').value);
        var newpassword2 = encodeURIComponent(document.getElementById(this.panelUID + '_newpassword2').value);

        if( newpassword1 != newpassword2 ) {
            alert("The password's do not match.  Please enter them again");
            return false;
        }
        if( newpassword1.length < 8 ) {
            alert("Passwords must be at least 8 characters long");
            return false;
        }
        // Make sure the password is not included in the URL, where it will be saved in log files
        var c = 'oldpassword=' + oldpassword + '&newpassword=' + newpassword1;
        Q.api.postJSONCb('qruqsp.core.changePassword', {}, c, function(rsp) {
            if( rsp.stat != 'ok' ) {
                Q.api.err(rsp);
                return false;
            }
            alert("Your password was changed, you must now re-login.");
            Q.logout();
        });
    }
    this.chgpwd.addButton('save', 'Save', 'Q.qruqsp_core_account.chgpwd.save();');
    this.chgpwd.addClose('Cancel');

    this.start = function(cb, ap, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = Q.createContainer('mc', 'qruqsp_core_account', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.menu.show(cb);
    }
}
