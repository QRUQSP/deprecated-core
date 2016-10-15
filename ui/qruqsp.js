//
// This file contains javascript functions which are generic for now, but may be moved
// into device specific files
//

window.Q = {
    'version':'161001.0928',
    'menus':{},
    'curMenu':'',
    'startMenu':'qruqsp.core.menu',
    'stationMenu':'qruqsp.core.station',
    'menuHome':null,
    'menuHistory':[],
    'masterStationID':0,
    'curStationID':0,
    'curStation':null,
    'curHelpUID':'',
    'loadCounter':0,
    'apps':{},
    'dropHooks':{},
    'userPerms':0,
    'panels':{},
    'expired':'no',
    'scroller':null,
    'startTime':0,
    'reauth_apiresume':null,
    'helpScroller':null}

Q.init = function(cfg) {
    Q.device = cfg.device;
    Q.browser = cfg.browser; 
    Q.engine = cfg.engine; 
    Q.touch = cfg.touch; 
    Q.size = cfg.size;
    Q.months = [
        {'shortname':'Jan'},
        {'shortname':'Feb'},
        {'shortname':'Mar'},
        {'shortname':'Apr'},
        {'shortname':'May'},
        {'shortname':'Jun'},
        {'shortname':'Jul'},
        {'shortname':'Aug'},
        {'shortname':'Sep'},
        {'shortname':'Oct'},
        {'shortname':'Nov'},
        {'shortname':'Dec'}
        ];
    Q.api.url = cfg.api_url;
    Q.api.key = cfg.api_key;
    Q.masterStationID = cfg.master_id;
    Q.manage_root_url = cfg.root_url;
    Q.themes_root_url = cfg.themes_root_url;
    if( cfg.start_menu != null && cfg.start_menu != '' ) {
        Q.startMenu = cfg.start_menu;
    }
    if( cfg.station_menu != null && cfg.station_menu != '' ) {
        Q.stationMenu = cfg.station_menu;
    }
    Q.defaultStationColours = Q.gE('station_colours').innerHTML;
    if( cfg.modules != null ) {
        Q.cfg = cfg.modules;
    } else {
        Q.cfg = {};
    }

    Q.qruqsp = {};
    Q.gridSorting = {};

    if( Q.device == 'hptablet' && Q.engine == 'webkit' ) {
        if (window.PalmSystem) window.PalmSystem.stageReady();
        window.PalmSystem.enableFullScreenMode(true);
    }

    document.addEventListener('dragover', function(e) { e.preventDefault(); }, false);   // Required for Chrome bug
    document.addEventListener('drop', Q.dropHandler, false);

    //
    // Check if username and password were passed to script, and auto-login
    //
    var uts = localStorage.getItem("_UTS");
    var utk = localStorage.getItem("_UTK");
    if( cfg.auth_token != null ) {
        Q.authToken(this, cfg.auth_token);
    } else if( uts != null && uts != '' && utk != null && utk != '' ) {
        Q.authUserToken(this, uts, utk);
    } else {
        if( Q.gE('m_recover').style.display != '' ) {
            Q.gE('m_login').style.display = ''; 
        }
    }
}

Q.preLoad = function(s) {
    var i = new Image;
    i.src=s;
}

Q.home = function() {
    if( Q.curHelpUID == 'qruqsp.core.main.stations' || Q.curHelpUID == 'qruqsp.core.main.station' ) {
        Q.qruqsp_core_main.stations.show(null);
    } else {
        Q.qruqsp_core_main.station.reopen();
    }
}

//
// This function will clear a DOM element of all children
//
// Arguments:
// i - The ID to look for in the DOM
//
Q.clr = function(i) {
    var e = null;
    if( typeof i == 'object' ) {
        e = i;
    } else  {
        e = Q.gE(i);
    }
    if( e != null && e.children != null ) {
        while( e.children.length > 0 ) {
            e.removeChild(e.children[0]);
        }
    }
    return e;
}

Q.show = function(i) {
    if( typeof i == 'object' ) {
        i.style.display = 'block';
    } else {
        Q.gE(i).style.display = 'block';
    }
}

Q.hide = function(i) {
    if( typeof i == 'object' ) {
        i.style.display = 'block';
    } else {
        Q.gE(i).style.display = 'none';
    }
}

// 
// This function will hide all children of 'i' except
// for the one with the id e
//
// Arguments:
// i = The ID of the element with the children to hide
// e = The ID of the child element to remain visible
//
Q.hideChildren = function(i,e) {

    if( typeof i == 'object' ) {
        var c = i.children;
    } else {
        var c = Q.gE(i).children;
    }
    for(var i=0;i < c.length; i++) {
        if( e != null && c[i].id == e ) {
            c[i].style.display = 'block';
        } else if( c[i].id != null && c[i].id == 'm_loading' ) {
            // Do nothing
        } else {
            c[i].style.display = 'none';
        }
    };
    window.scroll(0,0);
}

//
// This function will load the javascript for an App and issue the start method on that javascript.  
// The backFunction will be run when the App closes.
//
// qruqsp_startAppCallback = function(app, startFunction, callback) {
// Arguments:
// a - The application name 'mapp_stationOwners', etc...
// sF - The starting function, if different from .start().
// cB - The call back to issue when the app closes, this is used to return to another app instead of the menu.
//
Q.startModalApp = function(a, sF, cB) {
    Q.startApp(a, sF, cB);
}

//
// This function will load the javascript for an App and issue the start method on that javascript.  
// The backFunction will be run when the App closes.
//
// FIXME: Change this to create a window when an app is started, if running in windowed mode.
//
// qruqsp_startAppCallback = function(app, startFunction, callback) {
// Arguments:
// a - The application name 'mapp_stationOwners', etc...
// sF - The starting function, if different from .start().
// cB - The call back to issue when the app closes, this is used to return to another app instead of the menu.
// aP - The appPrefix to start with
// aG - The args to pass along to the function
//
Q.startApp = function(a, sF, cB, aP, args) {
    //
    // Set the default appPrefix to 'mc';
    //
    if( aP == null ) {
        aP = 'mc';
    }
    if( sF == null ) {
        sF = 'start';
    }

    var func = a;
    func = func.replace(/(.*)\.(.*)\.(.*)/, "$1_$2_$3");

    //
    // Check if the app is already loaded
    //
    if( Q[func] != null ) {
        //
        // If a start function was specified, othersize use start.
        //
        Q[func].start(cB, aP, args);
    } else {
        Q.startLoad();
        // Load Javascript
        var script = document.createElement('script');
        script.type = 'text/javascript';
        // Hack to get around cached data
        var d = new Date();
        var t = d.getTime();
        // qruqsp.users.prefs -> /qruqsp-mods/users/ui/prefs.js
        var src = a;
        script.src = src.replace(/(.*)\.(.*)\.(.*)/, "/$1-mods/$2/ui/$3.js") + "?t=" + t;

        //
        var done = false;
        var head = document.getElementsByTagName('head')[0];

        script.onerror = function() {
            Q.stopLoad();
            Q.alert("We had a problem communicating with the server. Please try again or if the problem persists check your network connection.");
        }

        // Attach handlers for all browsers
        script.onload = script.onreadystatechange = function() {
            Q.stopLoad();
            if(!done&&(!this.readyState||this.readyState==="loaded"||this.readyState==="complete")){
                done = true;
                
                // Attach the APP and run the start 
                eval('Q.' + func + ' = new ' + func + '();');
                // eval('Q.' + a + '.init();');
                if( Q[func].init != null ) {
                    Q[func].init();
                }
                Q[func].start(cB, aP, args);

                // Handle memory leak in IE
                script.onload = script.onreadystatechange = null;
                if(head&&script.parentNode){
                    head.removeChild( script );
                }    
            }    
        };

        head.appendChild(script);
    }
}

//
// Arguments:
// aI = appID the DOM element ID to remove
// mF = menuFlag, should the menu be shown after the app closes
//
Q.closeApp = function(aI, mF) {
    var a = Q.gE('mc_apps');
    a.removeChild(Q.gE(aI));
    if( mF == 'yes' ) {
        Q.menu.show(Q.menuHistory[Q.menuHistory.length-1]);
    }
}

//  
// This function will close all windows, and issue a reload
//  
Q.logout = function() {
    var uts = localStorage.getItem("_UTS", '');
    var utk = localStorage.getItem("_UTK", '');
    var c = '';
    if( uts != null && uts != '' && utk != null && utk != '' ) { 
        c = 'user_selector=' + encodeURIComponent(uts) + '&user_token=' + encodeURIComponent(utk);
    } 
    Q.gE('m_container').style.display = 'none';
    Q.gE('m_loading').style.display = '';
    Q.api.postJSONCb('qruqsp.core.logout', {}, c, function(rsp) {
        // Don't reset UTS, it's out computer ID
        localStorage.setItem('_UTK','');
        localStorage.setItem('_UTS','');
        Q.api.token = ''; 
        Q.userID = 0; 
        Q.userPerms = 0;

        // Clear any station data
        Q.stations = null;
        Q.curStationID = 0;

        //  
        // Issue a reload, which will reset all variables, and dump any open windows.
        //  
        Q.reload();
        // window.location.reload();
    });
}

//
// This function will authenticate a token
//
Q.authUserToken = function(e, s, t) {
    if( s != null && s != '' && t != null && t != '' ) {
        Q.api.postAuthCb('qruqsp.core.auth', {'format':'json'}, 'user_selector=' + encodeURIComponent(s) + '&user_token=' + encodeURIComponent(t), function(r) {
            if( r.stat == 'ok' ) {
                // Store the time this session started, used when expiring sessions.
                Q.startTime = Math.round(+new Date()/1000);
                Q.api.version = r.version;    // Set only when UI is loaded/first login
                Q.api.token = r.auth.token;
                Q.userID = r.auth.id;
                Q.avatarID = r.auth.avatar_id;
                Q.userPerms = r.auth.perms;
                Q.userSettings = r.auth.settings;

                if( Q.oldUserId == Q.userID ) {
                    Q.hide('m_login');
                    return true;
                }
                Q.oldUserId = 0;

                Q.hide('m_login');
                Q.loadAvatar();
                // If they only have access to one station, go direct to that menu
                if( r.station != null && r.station > 0 && Q.stationMenu != null ) {
                    Q.startApp(Q.stationMenu,null,null,'mc',{'id':r.station});
                } else {
                    Q.startApp(Q.startMenu);
                }
                
            } else {
                Q.gE('m_login').style.display = '';
            }
        });
    }
}
//
// This function will authenticate a token
//
Q.authToken = function(e, t) {
    if( t != null && t != '' ) {
        Q.api.postAuthCb('qruqsp.core.auth', {'format':'json'}, 'auth_token=' + encodeURIComponent(t), function(r) {
            if( r.stat == 'ok' ) {
                // Store the time this session started, used when expiring sessions.
                Q.startTime = Math.round(+new Date()/1000);
                Q.api.version = r.version;    // Set only when UI is loaded/first login
                Q.api.token = r.auth.token;
                Q.userID = r.auth.id;
                Q.avatarID = r.auth.avatar_id;
                Q.userPerms = r.auth.perms;
                Q.userSettings = r.auth.settings;

                if( Q.oldUserId == Q.userID ) {
                    Q.hide('m_login');
                    return true;
                }
                Q.oldUserId = 0;

                Q.hide('m_login');
                Q.loadAvatar();
                // If they only have access to one station, go direct to that menu
                if( r.station != null && r.station > 0 && Q.stationMenu != null ) {
                    Q.startApp(Q.stationMenu,null,null,'mc',{'id':r.station});
                } else {
                    Q.startApp(Q.startMenu);
                }
                
            }
        });
    }
}

//
// This function will authenticate the user against the qruqspAPI and get an auth_token
//
Q.auth = function(e, t) {
    if( t != null ) {
        Q.api.token = t;
        var c= '';
        Q.username = '';
    } else {
        Q.username = Q.gE('username').value;
        var c = 'username=' + encodeURIComponent(Q.gE('username').value) 
            + '&password=' + encodeURIComponent(Q.gE('password').value);
        Q.gE('username').value = '';
        Q.gE('password').value = '';
    }

    var rm = Q.gE('rm');
    if( rm != null && rm.checked == true && localStorage != null ) {
        c += '&rm=yes';
    }

    Q.api.postJSONCb('qruqsp.core.auth', {}, c, function(r) {
        if( r == null ) {
            return false;
        }
        if( r.stat != 'ok' ) {
            Q.api.err_alert(r);
            return false;
        }
        // Store the time this session started, used when expiring sessions.
        Q.startTime = Math.round(+new Date()/1000);
        Q.api.version = r.version;    // Set only when UI is loaded/first login
        Q.api.token = r.auth.token;
        var rm = Q.gE('rm');
        if( rm != null && rm.checked == true 
            && r.auth.user_selector != null && r.auth.user_selector != ''
            && r.auth.user_token != null && r.auth.user_token != '' ) {
            localStorage.setItem('_UTS', r.auth.user_selector);
            localStorage.setItem('_UTK', r.auth.user_token);
        }
        Q.userID = r.auth.id;
        Q.avatarID = r.auth.avatar_id;
        Q.userPerms = r.auth.perms;
        Q.userSettings = r.auth.settings;

        if( Q.oldUserId == Q.userID ) {
            Q.hide('m_login');
            return true;
        }
        Q.oldUserId = 0;

        Q.hide('m_login');
        Q.loadAvatar();
        // If they only have access to one station, go direct to that menu
        if( r.station != null && r.station > 0 && Q.stationMenu != null ) {
            Q.startApp(Q.stationMenu,null,null,'mc',{'id':r.station});
        } else {
            Q.startApp(Q.startMenu);
        }
    });

    return true;
}

//
// This function will reauthenticate the user, used after session has expired
//
Q.reauth = function() {
    var c = 'username=' + Q.username
        + '&password=' + encodeURIComponent(Q.gE('reauthpassword').value);
    Q.gE('reauthpassword').value = '';

    Q.api.token = '';
    Q.api.postJSONCb('qruqsp.core.auth', {}, c, function(r) {
        if( r.stat != 'ok' ) {
            Q.api.err_alert(r);
            return false;
        }
        if( Q.api.version != r.version ) {
            alert("We've updated Ciniki!  Please logout and sign in again to ensure you are using the current version.");
        }
        Q.api.token = r.auth.token;
        Q.expired = 'no';
        Q.hide('m_relogin');
        Q.show('m_container');
        if( Q.reauth_apiresume != null ) {
            Q.api.resume(Q.reauth_apiresume);
        }
    });
    return false;
}

Q.reauthToken = function(s, t) {
    var c = 'user_selector=' + encodeURIComponent(s) + '&user_token=' + encodeURIComponent(t);

    Q.api.token = '';
    Q.api.postJSONCb('qruqsp.core.auth', {}, c, function(r) {
        if( r.stat != 'ok' ) {
            localStorage.setItem('_UTK', '');
            return false;
        }
        if( Q.api.version != r.version ) {
            alert("We've updated Ciniki!  Please logout and sign in again to ensure you are using the current version.");
        }
        Q.api.token = r.auth.token;
        Q.expired = 'no';
        if( Q.reauth_apiresume != null ) {
            Q.api.resume(Q.reauth_apiresume);
        }
    });
    return false;
}

//
// The startLoadSpinner and stopLoadSpinner functions will start and stop the
// spining logo in the upper left corner.  This is useful to let the user know
// the system is busy loading info.
//
Q.startLoad = function() {
    //
    // Increment the load counter so we can have multiple requests,
    // and the spinner won't stop until they are all complete.
    //
    if( Q.loadCounter < 0 ) {
        Q.loadCounter = 0;
    }
    Q.loadCounter += 1;
    Q.setHeight('m_loading', '0');
    Q.setHeight('m_loading', '100%');
    Q.show('m_loading');
}

Q.stopLoad = function() {
    Q.loadCounter -= 1;
    if( Q.loadCounter < 0 ) {
        Q.loadCounter = 0;
    }
    if( Q.loadCounter == 0 ) {
        Q.hide('m_loading');
    }
}

Q.setHTML = function(i, h) {
    Q.gE(i).innerHTML = h;
}

Q.setWidth = function(i, w) {
    Q.gE(i).style.width = w;
}

Q.setHeight = function(i, h) {
    Q.gE(i).style.height = h;
}

//
// t - the value to put inside
// c - the count, -1 if no count to be displayed
// j - javascript to attach to onclick
//
Q.addSectionLabel = function(t, c, j) {
    if( c != null && c >= 0 ) {
        t += ' <span class="count">' + c + '</span>';
    }
    var h = Q.aE('h2', null, null, t);
    if( j != null && j != '' ) {
        h.setAttribute('onclick', j);
    }
    return h;
}

//
// Arguments:
// aP - appPrefix, the prefix for the DIV containers, 'mc' or 'mh'
// aI - the app ID
// cF - clearFlag, specifies if the container is already found, should it be cleared?
//
Q.createContainer = function(aP, aI, cF) {
    //
    // FIXME: Replace this function with one that creates the container in a new draggable window "div"
    //
    var c = Q.gE(aI);
    if( c == null ) {
        c = Q.aE('div', aI, 'mapp');
        var a = Q.gE(aP + '_apps');
        a.appendChild(c);
    } else {
        if( cF == 'yes' ) {
            Q.clr(aI);
        }
    }

    return c;
}

//
// This function will submit the error information as a bug through the API
//
Q.submitErrBug = function() {
    var subject = 'UI Error at ' + Q.curHelpUID;
    var followup = '';

    // Get the list of errors
    strErrs = function(e) { 
        var c = e.code + ' - ' + e.msg;
        if( e.pmsg != null ) { c += ' [' + e.pmsg + ']'; }
        c += '\n';
        if( e.err != null ) { 
            var recursive = arguments.callee;
            c += recursive(e.err);
        }
        return c;
    };

    if( Q.api.curRC.stat != 'ok' && Q.api.curRC.err != null ) {
        followup += 'An error has occured while calling the API.\n\n';
        followup += 'Station ID: ' + Q.curStationID + '\n';
        followup += 'Station Name: ' + Q.curStation.name + '\n';
        followup += 'UI Panel: ' + Q.curHelpUID + '\n';
        if( Q.api.curRC.method != null ) {
            followup += 'API method: ' + Q.api.curRC.method + '\n';
        } else if( Q.api.lastCall.m != null ) {
            followup += 'API method: ' + Q.api.lastCall.m + '\n';
        } else {
            followup += 'API method: unknown\n';
        }
        followup += '\n';
        followup += 'API Errors:\n';
        followup += strErrs(Q.api.curRC.err);
    }

    if( Q.api.lastCall != null ) {
        followup += '\n';
        followup += 'API Function: ' + Q.api.lastCall.f + '\n';
        followup += 'API Method: ' + Q.api.lastCall.m + '\n';
        followup += 'API Parameters: \n';
        for(i in Q.api.lastCall.p) {
            followup += '    ' + i + '=' + Q.api.lastCall.p[i] + '\n';
        }
        var c = Q.api.lastCall.c.split('&');
        followup += 'API Post Content: \n'
        for(i in c) {
            followup += '    ' + c[i] + '\n';
        }
    }

    //
    // Submit the bug
    //
    Q.api.postJSONCb('qruqsp.bugs.bugAdd',
        {'station_id':Q.masterStationID, 'status':'1', 'source':'qruqsp-manage', 'source_link':Q.curHelpUID},
        'subject=' + encodeURIComponent(subject) + '&followup=' + encodeURIComponent(followup), function(rsp) {
            if( rsp.stat != 'ok' ) {
                alert("Now we had an error submitting the bug, please contact support.  " + "Error #" + rsp.err.code + ' -- ' + rsp.err.msg);
            } else {
                alert('The bug has been submitted');
            }

            Q.hide('m_error');
        });
}

//
// Dummy function to dump event info
//
Q.dumpEventInfo = function(event) {
    if (event === undefined) {
        event = window.event;
    }

    var firedOn = event.target ? event.target : event.srcElement;
    if (firedOn.tagName === undefined) {
        firedOn = firedOn.parentNode;
    }

    var info = ''
    if (firedOn.id == "source") {
        info += "<span style='color:#008000'>" + event.type + "</span>, ";
    }
    else {
        info += "<span style='color:#800000'>" + event.type + "</span>, ";
    }

    if (event.type == "dragover") {
            // the dragover event needs to be canceled in Google Chrome and Safari to allow firing the drop event
        if (event.preventDefault) {
            event.preventDefault ();
        }
    }
}

//
// Arguments:
// p - the panelRef of the panel to add the callback for
// c - the callback function
Q.addDropHook = function(p, c) {
    Q.dropHooks[p] = c;
}

//
// Arguments:
// p - the ID of the panel to add the callback for
// c - the callback function
Q.delDropHook = function(p) {
    if( Q.dropHooks[p] != null ) {
        delete Q.dropHooks[p];
    }
}

//
// Arguments:
// e - the event info
//
Q.dropHandler = function(e) {
    e.stopPropagation();
    e.preventDefault();

    //
    // Find active panel 
    //
    for(pRef in Q.dropHooks) {
        var p = eval(pRef);
        // Make sure panel and app are displayed, which means top panel that was dropped onto
        if( Q.gE(p.panelUID).style.display == 'block' && Q.gE(p.panelUID).parentNode.style.display == 'block' ) {
            s = null;
            // Find the section it was dropped into
            if( e.toElement != null ) {
                var parent = e.toElement.parentElement;
            } else {
                var parent = e.target.parentElement;
            }
            var ps = p.panelUID + '_section_';
            while(parent != null && parent.localName != 'form' && parent.localName != 'body') {
                if( parent.id.substr(0, ps.length) == ps ) {
                    s = parent.id.substr(ps.length);
                }
                parent = parent.parentElement;
            }
            eval('' + Q.dropHooks[pRef](e, p, s));
        }
    }
}

Q.setColourSwatchField = function(field, value) {
    var d = Q.gE(field);
    d.setAttribute('value', value);
    for(i in d.children) {
        if( d.children[i].getAttribute != null ) {
            if( d.children[i].getAttribute('name') == value ) {
                d.children[i].className = 'colourswatch selected';
            } else {
                if( d.children[i].className != 'colourswatch' ) {
                    d.children[i].className = 'colourswatch';
                }
            }
        }
    }
}

//
// Convert a timestamp in seconds to a time in 12 hour clock
//
Q.dateMake12hourTime = function(ts) {
    if( typeof ts == 'number' ) {
        var dt = new Date(ts * 1000);
    } else {
        dt = ts;
    }
    str = '';
    if( dt.getHours() == 0 ) {
        str += '12';
    } else if( dt.getHours() < 10 ) {
        str += '0' + dt.getHours();
    } else if( dt.getHours() > 21 ) {
        str += '0' + dt.getHours() - 12;
    } else if( dt.getHours() > 12 ) {
        str += '0' + dt.getHours() - 12;
    } else {
        str += '' + (dt.getHours());
    }
    str += ':';
    if( dt.getMinutes() < 10 ) {
        str += '0';
    }
    str += '' + dt.getMinutes();

    return str;
}

Q.dateMake12hourTime2 = function(ts) {
    if( typeof ts == 'number' ) {
        var dt = new Date(ts * 1000);
    } else {
        dt = ts;
    }
    str = '';
    if( dt.getHours() == 0 ) {
        str += '12';
    } else if( dt.getHours() < 10 ) {
        str += '0' + dt.getHours();
    } else if( dt.getHours() > 21 ) {
        str += '0' + dt.getHours() - 12;
    } else if( dt.getHours() > 12 ) {
        str += '0' + dt.getHours() - 12;
    } else {
        str += '' + (dt.getHours());
    }
    str += ':';
    if( dt.getMinutes() < 10 ) {
        str += '0';
    }
    str += '' + dt.getMinutes();

    if( dt.getHours() > 11 ) {
        str += ' pm';
    } else {
        str += ' am';
    }

    return str;
}

Q.daysInMonth = function(year, month) {
    var isLeap = ((year % 4) == 0 && ((year % 100) != 0 || (year % 400) == 0));
    return [31, (isLeap ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][month];
}

Q.dayOfWeek = function(d) {
    var days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
    return days[d.getDay()];
}

Q.monthOfYear = function(d) {
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return months[d.getMonth()];
}

Q.dateFormat = function(d) {
    if( typeof d == 'string' ) {
        if( d == '0000-00-00' ) {
            return '';
        }
        if( d.match(/[a-zA-Z]+ [0-9]+, [0-9]+/) ) {
            return d;
        }
        var p = d.split(/-/);
        d = new Date(p[0],p[1]-1,p[2]);
    }
    return Q.monthOfYear(d) + ' ' +  d.getDate() + ', ' + d.getFullYear();
}

Q.dateFormatWD = function(d) {
    var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    if( typeof d == 'string' ) {
        d = new Date(d);
    }
    return days[d.getDay()] + ' ' + Q.monthOfYear(d) + ' ' +  d.getDate() + ', ' + d.getFullYear();
}

Q.rgbToHex = function(rgb) {
    var hexDigits = ["0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f"];
    rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    if( rgb == null ) { return ''; }
    function hex(x) {
        return isNaN(x) ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x % 16];
    }
    return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
}

// Arguments:
// b - size in bytes, or by e (B,K,M,G,T,P)
// e - should be 0, unless in recurse
//
Q.humanBytes = function(b, e) {
    if( b == '' ) { return ''; }
    if( b == undefined ) { b = 0; }
    if( e == null ) { e = 0; }
    exts = ['B','K','M','G','T','P'];
    if( b > 1024 ) {
        return Q.humanBytes(b/1024, e+1);
    }
    else if( typeof b == 'number' && b != 0 ) {
        return b.toFixed(1) + exts[e];
    } 
    return b + exts[e];
}

//
// TreeGrid sort is complicated, because it must figure out which rows to move
// based on what is blank or "attached together".
//
// tid:     table ID
// col:     The column number in the table to sort
// type:     The type of the column
// o:        The order to sort in, asc, or desc
// s:        The saveSort function to call to save settings
// d:        null, lookup table in document, otherwise sort the table in this object
Q.sortTreeGrid = function(tid, col, type, o, save, d) {
    // This function is called whenever a sortable table is first displayed, 
    // to check if there are any predisplay sort settings
    if( (col == null || type == null) && Q.gridSorting[tid] == null) {
        return false;
    }

    //
    // Sort example from http://www.kryogenix.org/code/browser/sorttable/sorttable.js
    //
    if( d == null ) {
        var t = Q.gE(tid);
        var tb = t.getElementsByTagName('tbody')[0];
    } else {
        var t = d;
        var tb = t.getElementsByTagName('tbody')[0];
    }

    var o = 'asc';
    if( col == null ) {
        col = Q.gridSorting[tid].col;
        o = Q.gridSorting[tid].order;
    }
    if( type == null ) {
        type = Q.gridSorting[tid].type;
    }

    if( type == 'text' || type == 'undefined' ) {
        var sorter_fn = function(a, b) {
            if( a == b ) return 0;
            if( a < b ) return -1;
            return 1;
        }
    } else if( type == 'date' ) {
        var sorter_fn = function(a, b) {
            if( a == b ) return 0;
            if( a < b ) return -1;
            return 1;
        }
    } else if( type == 'number' || type == 'size' || type == 'percent' ) {
        var sorter_fn = function(a, b) {
            aa = parseFloat(a.replace(/[^0-9.-]/g,''));
            if (isNaN(aa)) aa = 0;
            bb = parseFloat(b.replace(/[^0-9.-]/g,'')); 
            if (isNaN(bb)) bb = 0;
            return aa-bb;
        }
    }

    var s = 0;
    // Find the last entry in list, which might not be the last row
    for(l=(tb.children.length-1);l>s && tb.children[l].children[col].innerHTML == '' && tb.children[l].children[col].sort_value != '';l--);
    var swap = true;

    // Check if we are sorted the same column
    if( tb.last_sorted_col != null && tb.last_sorted_col == col ) {
        if( tb.last_sorted_order == 'asc' ) {
            o = 'desc';    
        } else {
            o = 'asc';
        }
        //
        // FIXME: Add quick swap if the grid is already sorted on the column
        //
        /*
        // If the same column, and already sorted, then just reverse it
        for(i=0;i<=Math.floor(l/2);i++) {
            var n = tb.children[l-i+1];
            tb.insertBefore(tb.children[l-i], tb.children[i]);
            if( i == 0 ) {
                tb.appendChild(tb.children[i+1]);
            } else {
                tb.insertBefore(tb.children[i+1], n);
            }
        }
        tb.last_sorted_order = o;
        return true;
        */
    }

    while(swap) {
        swap = false;
        //
        // Sort from the top, a is the top element, b is second element
        //
        for(i=s;i < l;i++) {
            // Skip blank entries
            if( tb.children[i].children[col].innerHTML == '' && tb.children[i].children[col].sort_value != '' ) { continue; }
            //
            // Find the next branch in the tree by skipping blank cells, offseta
            //
            for(oa=1;(i+oa)<l && tb.children[i+oa].children[col].innerHTML == '' && tb.children[i+oa].children[col].sort_value != '';oa++);
            // Check if this was the last element, only blank elements after this row
            if( i+oa > l ) { break; }
            a = tb.children[i].children[col].innerHTML;
            b = tb.children[i+oa].children[col].innerHTML;
            // Find the offset for the second element
            for(ob=1;(i+oa+ob)<tb.children.length && tb.children[i+oa+ob].children[col].innerHTML == '' && tb.children[i+oa+ob].children[col].sort_value != '';ob++);
            if( type == 'date' || type == 'size' || type == 'percent' ) {
                a = tb.children[i].children[col].sort_value;
                b = tb.children[i+oa].children[col].sort_value;
            }
            if( type == 'text' && a == '' && tb.children[i].children[col].sort_value != '' ) {
                a = tb.children[i].children[col].sort_value;
            }
            if( type == 'text' && b == '' && tb.children[i+oa].children[col].sort_value != '' ) {
                b = tb.children[i+oa].children[col].sort_value;
            }

            if( (o == 'asc' && sorter_fn(a, b) > 0) || (o == 'desc' && sorter_fn(b, a) > 0) ) {
                for(j=0;j<ob;j++) {
                    tb.insertBefore(tb.children[i+oa+j], tb.children[i+j]);
                }
                swap = true;
            }
        }
        l--;

        if( !swap) break;

        //
        // Sort from the bottom, a is the bottom element, b is top element
        //
        for(var i = l; i > s; i--) {
            // Skip blank entries
            if( tb.children[i].children[col].innerHTML == '' && tb.children[i].children[col].sort_value != '' ) { continue; }
            // Find blank cells after if any
            for(oa=1;(i+oa)<tb.children.length && tb.children[i+oa].children[col].innerHTML == '' && tb.children[i+oa].children[col].sort_value != '';oa++);
            for(ob=1;(i-ob)>=0 && tb.children[i-ob].children[col].innerHTML == '' && tb.children[i-ob].children[col].sort_value != '';ob++);
            // Check if we're back at the first element
            if( i-ob < s ) { break; }
            a = tb.children[i].children[col].innerHTML;
            b = tb.children[i-ob].children[col].innerHTML;
            if( type == 'date' || type == 'size' || type == 'percent' ) {
                a = tb.children[i].children[col].sort_value;
                b = tb.children[i-ob].children[col].sort_value;
            }
            if( type == 'text' && a == '' && tb.children[i].children[col].sort_value != '' ) {
                a = tb.children[i].children[col].sort_value;
            }
            if( type == 'text' && b == '' && tb.children[i-ob].children[col].sort_value != '' ) {
                b = tb.children[i-ob].children[col].sort_value;
            }
            if( (o == 'asc' && sorter_fn(b, a) > 0) || (o == 'desc' && sorter_fn(a, b) > 0) ) {
                for(j=0;j<oa;j++) {
                    tb.insertBefore(tb.children[i+j], tb.children[i-ob+j]);
                }
                swap = true;
            }
        }
        s++;
    }

    tb.last_sorted_col = col;
    tb.last_sorted_order = o;

    //
    // Save the sort order for the panel for next time
    //
    if( save != null ) {
        save(tid, col, type, o);
    } else {
        Q.gridSorting[tid] = {'col':col, 'type':type, 'order':o};
    }
}


// tid:     table ID
// col:     The column number in the table to sort
// type:     The type of the column
// o:        The order to sort in, asc, or desc
// s:        The saveSort function to call to save settings
// d:        null, lookup table in document, otherwise sort the table in this object
Q.sortGrid = function(tid, col, type, o, save, d) {
    // This function is called whenever a sortable table is first displayed, 
    // to check if there are any predisplay sort settings
    if( (col == null || type == null) && Q.gridSorting[tid] == null) {
        return false;
    }

    //
    // Sort example from http://www.kryogenix.org/code/browser/sorttable/sorttable.js
    //
    if( d == null ) {
        var t = Q.gE(tid);
        var tb = t.getElementsByTagName('tbody')[0];
    } else {
        var t = d;
        var tb = t.getElementsByTagName('tbody')[0];
    }

    if( tb == null || tb.children == null || tb.children.length == 0 || tb.children.length == 1 ) {
        return true;
    }

    var o = 'asc';
    if( col == null ) {
        col = Q.gridSorting[tid].col;
        o = Q.gridSorting[tid].order;
    }
    if( type == null ) {
        type = Q.gridSorting[tid].type;
    }

    if( type == 'text' || type == 'alttext' || type == 'undefined' ) {
        var sorter_fn = function(a, b) {
            if( a == b ) return 0;
            if( a < b ) return -1;
            return 1;
        }
    } else if( type == 'date' || type == 'size' ) {
        var sorter_fn = function(a, b) {
            if( a == b ) return 0;
            if( a < b ) return -1;
            return 1;
        }
    } else if( type == 'number' || type == 'altnumber' ) {
        var sorter_fn = function(a, b) {
            if(isNaN(a)) {aa = parseFloat(a.replace(/[^0-9.-]/g,''));} else {aa = a;}
            if(isNaN(aa)) aa = 0;
            if(isNaN(b)) {bb = parseFloat(b.replace(/[^0-9.-]/g,'')); } else {bb = b;}
            if(isNaN(bb)) bb = 0;
            return aa-bb;
        }
    }

    var s = 0;
    // Last entry in list
    if( tb.children != null && tb.children.length > 1 ) {
        var l = tb.children.length - 1;
    } else {
        var l = 0;
    }
    var swap = true;

    // Check if we are sorted the same column
    if( tb.last_sorted_col != null && tb.last_sorted_col == col ) {
        if( tb.last_sorted_order == 'asc' ) {
            o = 'desc';    
        } else {
            o = 'asc';
        }
        // If the same column, and already sorted, then just reverse it
        for(i=0;i<=Math.floor(l/2);i++) {
            var n = tb.children[l-i+1];
            tb.insertBefore(tb.children[l-i], tb.children[i]);
            if( i == 0 ) {
                tb.appendChild(tb.children[i+1]);
            } else {
                tb.insertBefore(tb.children[i+1], n);
            }
        }
        tb.last_sorted_order = o;
        return true;
    }

    while(swap) {
        swap = false;
        for(i=s;i < l;i++) {
            a = tb.children[i].children[col].innerHTML;
            b = tb.children[i+1].children[col].innerHTML;
            var sva = tb.children[i].children[col].sort_value;
            var svb = tb.children[i+1].children[col].sort_value;
            if( type == 'date' || type == 'size' || type == 'altnumber' || type == 'alttext' ) {
                a = sva;
                b = svb;
            }
            if( type == 'text' && a == '' && sva != null && sva != '' && sva != undefined) {
                a = sva;
            }
            if( type == 'text' && b == '' && svb != null && svb != '' && svb != undefined) {
                b = svb;
            }
            if( a == null ) { a = ''; }
            if( b == null ) { b = ''; }

            if( sorter_fn(a, b) > 0 ) {
                tb.insertBefore(tb.children[i+1], tb.children[i]);
                swap = true;
            }
        }
        l--;

        if( !swap) break;

        for(var i = l; i > s; i--) {
            a = tb.children[i].children[col].innerHTML;
            b = tb.children[i-1].children[col].innerHTML;
            var sva = tb.children[i].children[col].sort_value;
            var svb = tb.children[i-1].children[col].sort_value;
            if( type == 'date' || type == 'size' || type == 'altnumber' || type == 'alttext' ) {
                a = sva;
                b = svb;
            }
            if( type == 'text' && a == '' && sva != null && sva != '' ) {
                a = sva;
            }
            if( type == 'text' && b == '' && svb != null && svb != '' ) {
                b = svb;
            }
            if( sorter_fn(a,b) < 0 ) {
                tb.insertBefore(tb.children[i], tb.children[i-1]);
                swap = true;
            }
        }
        s++;
    }

    tb.last_sorted_col = col;
    tb.last_sorted_order = o;

    //
    // Save the sort order for the panel for next time
    //
    if( save != null ) {
        save(tid, col, type, o);
    } else {
        Q.gridSorting[tid] = {'col':col, 'type':type, 'order':o};
    }
}

Q.loadAvatar = function() {
    //
    // Only load an avatar if the user has uploaded one
    //
    if( Q.avatarID > 0 ) {
        var l = Q.gE('mc_home_button');
        l.className = 'homebutton avatar';
        var i = Q.aE('img',null,'homebutton avatar');
        i.src = Q.api.getBinaryURL('qruqsp.core.avatarGet', {'user_id':Q.userID, 'version':'thumbnail', 'maxlength':'100', 'refresh':Math.random()});
        Q.clr(l);
        l.appendChild(i);
    } else {
        var l = Q.gE('mc_home_button');
        Q.clr(l);
        l.innerHTML = '<div class="button home"><span class="faicon">&#xf015;</span><span class="label">Home</span></div>';
    }
}

Q.reload = function() {
    var newHref = window.location.href;
    window.location.href = newHref;
}

Q.pwdReset = function() {
    var c = 'email=' + encodeURIComponent(Q.gE('reset_email').value);

    var r = Q.api.postJSONCb('qruqsp.core.passwordRequestReset', {}, c, function(r) {
        if( r.stat != 'ok' ) {
            Q.api.err_alert(r);
            return false;
        }
        alert("An email has been sent to you with a new password.");
        Q.hide('m_forgot');
        Q.show('m_login');
    });
    return true;
}

Q.tempPassReset = function() {
    var email = encodeURIComponent(Q.gE('recover_email').value);
    var temppwd = encodeURIComponent(Q.gE('temp_password').value);
    var newpwd1 = encodeURIComponent(Q.gE('new_password').value);
    var newpwd2 = encodeURIComponent(Q.gE('new_password_again').value);

    if( newpwd1 != newpwd2 ) { 
        alert("The password's do not match.  Please enter them again");
        return false;
    }   
    if( newpwd1.length < 8 ) { 
        alert("Passwords must be at least 8 characters long");
        return false;
    }   
    var c = 'temppassword=' + temppwd + '&newpassword=' + newpwd1;
    var rsp = Q.api.postJSONCb('qruqsp.core.changeTempPassword', {'email':email}, c, function(rsp) {
        if( rsp.stat != 'ok' ) { 
            Q.api.err_alert(rsp);
            return false;
        }   
        alert("Your password was changed, you can now login.");
        // Redirect    to the main login page
        var newHref = window.location.href.split("?")[0];
        window.location.href = newHref;
    });
    return false;
}

Q.toggleSection = function(e, s) {
    var f = Q.gE(s);
    if( f == null ) {return false; }
    var b = null;
    if( e.childNodes[0].className == 'icon' ) { b = e.childNodes[0]; }
    if( f.style.display == 'none' ) {
        f.style.display = 'block';
        if( b != null ) { b.innerHTML = '-'; }
    } else {
        f.style.display = 'none';
        if( b != null ) { b.innerHTML = '+'; }
    }
}

Q.gE = function(i) {
    return document.getElementById(i);
}

//
// This is a shortcut to creating new elements
//
// Arguments:
// t = The type of element to create
// i = The id of the element
// c = The class of the element
// h = The innerHTML if specified of the element
// f = The onclick function for the element if supplied
//
Q.aE = function(t, i, c, h, f) {
    var e = document.createElement(t);
    if( i != null ) { e.setAttribute('id', i); }
    if( c != null ) { e.className = c; }
    if( h != null ) { e.innerHTML = h; }
    if( f != null && f != '' ) { e.setAttribute('onclick', f); }
    return e;
}

//
// Arguments
// i  - The id to assign to the element, if not null
// c  - The class to assign to the element, if not null, but can be blank
//
Q.addTable = function(i, c) {
    var t = Q.aE('table', i, c);
    t.cellPadding = 0;
    t.cellSpacing = 0;

    return t;
}

Q.strtotime = function(text, now) {
    // Convert string representation of date and time to a timestamp
    //
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/strtotime
    // +   original by: Caio Ariede (http://caioariede.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: David
    // +   improved by: Caio Ariede (http://caioariede.com)
    // +   bugfixed by: Wagner B. Soares
    // +   bugfixed by: Artur Tchernychev
    // +   improved by: A. Matías Quezada (http://amatiasq.com)
    // +   improved by: preuter
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // %        note 1: Examples all have a fixed timestamp to prevent tests to fail because of variable time(zones)
    // *     example 1: strtotime('+1 day', 1129633200);
    // *     returns 1: 1129719600
    // *     example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200);
    // *     returns 2: 1130425202
    // *     example 3: strtotime('last month', 1129633200);
    // *     returns 3: 1127041200
    // *     example 4: strtotime('2009-05-04 08:30:00');
    // *     returns 4: 1241418600
    var parsed, match, year, date, days, ranges, len, times, regex, i;

    if (!text) {
        return null;
    }

    // Unecessary spaces
    text = text.trim()
        .replace(/\s{2,}/g, ' ')
        .replace(/[\t\r\n]/g, '')
        .toLowerCase();

    if (text === 'now' || text === 'today') {
        return now === null || isNaN(now) ? new Date().getTime() / 1000 | 0 : now | 0;
    }
    if (!isNaN(parsed = Date.parse(text))) {
        return parsed / 1000 | 0;
    }
    if( text === 'yesterday' ) {
        return (new Date().getTime()/1000) - 86400;
    }
    if( text === 'tomorrow' ) {
        return (new Date().getTime()/1000) + 86400;
    }

    match = text.match(/^(\d{2,4})-(\d{2})-(\d{2})(?:\s(\d{1,2}):(\d{2})(?::\d{2})?)?(?:\.(\d+)?)?$/);
    if (match) {
        year = match[1] >= 0 && match[1] <= 69 ? +match[1] + 2000 : match[1];
        return new Date(year, parseInt(match[2], 10) - 1, match[3],
            match[4] || 0, match[5] || 0, match[6] || 0, match[7] || 0) / 1000;
    }

    date = now ? new Date(now * 1000) : new Date();
    days = {
        'sun': 0,
        'mon': 1,
        'tue': 2,
        'wed': 3,
        'thu': 4,
        'fri': 5,
        'sat': 6
    };
    ranges = {
        'yea': 'FullYear',
        'mon': 'Month',
        'day': 'Date',
        'hou': 'Hours',
        'min': 'Minutes',
        'sec': 'Seconds'
    };


    times = '(years?|months?|weeks?|days?|hours?|minutes?|min|seconds?|sec' +
        '|sunday|sun\\.?|monday|mon\\.?|tuesday|tue\\.?|wednesday|wed\\.?' +
        '|thursday|thu\\.?|friday|fri\\.?|saturday|sat\\.?)';
    regex = '([+-]?\\d+\\s' + times + '|' + '(last|next)\\s' + times + ')(\\sago)?';

    match = text.match(new RegExp(regex, 'gi'));
    if (!match) {
        return false;
    }

    for (i = 0, len = match.length; i < len; i++) {
        if (!Q.strtotime_process(match[i])) {
            return false;
        }
    }

    return (date.getTime() / 1000);
}

Q.strtotime_lastNext = function(type, range, modifier) {
    var diff, day = days[range];

    if (typeof day !== 'undefined') {
        diff = day - date.getDay();

        if (diff === 0) {
            diff = 7 * modifier;
        }
        else if (diff > 0 && type === 'last') {
            diff -= 7;
        }
        else if (diff < 0 && type === 'next') {
            diff += 7;
        }

        date.setDate(date.getDate() + diff);
    }
}

Q.strtotime_process = function(val) {
    var splt = val.split(' '), // Todo: Reconcile this with regex using \s, taking into account browser issues with split and regexes
        type = splt[0],
        range = splt[1].substring(0, 3),
        typeIsNumber = /\d+/.test(type),
        ago = splt[2] === 'ago',
        num = (type === 'last' ? -1 : 1) * (ago ? -1 : 1);

    if (typeIsNumber) {
        num *= parseInt(type, 10);
    }

    if (ranges.hasOwnProperty(range) && !splt[1].match(/^mon(day|\.)?$/i)) {
        return date['set' + ranges[range]](date['get' + ranges[range]]() + num);
    }
    if (range === 'wee') {
        return date.setDate(date.getDate() + (num * 7));
    }

    if (type === 'next' || type === 'last') {
        Q.strtotime_lastNext(type, range, num);
    }
    else if (!typeIsNumber) {
        return false;
    }
    return true;
}

// This function replaces API to qruqsp.core.parseDate
Q.parseDate = function(dt) {
    var pd = new Date((Q.strtotime(dt))*1000);
    var I = pd.getHours()%12;
    var m = pd.getMinutes();
    var r = {'year':pd.getFullYear(),
        'month':pd.getMonth()+1,
        'day':pd.getDate(),
        'time':(I===0?12:I) + ':' + (m>9?m:'0'+m) + ' ' + (pd.getHours() > 11?'PM':'AM'),
        };
    return r;
}

Q.formatAddress = function(addr) {
    var a = '';
    if( addr.name != null && addr.name != '' ) {
        a += addr.name + '<br/>';
    }
    if( addr.address1 != null && addr.address1 != '' ) {
        a += addr.address1 + '<br/>';
    }
    if( addr.address2 != null && addr.address2 != '' ) {
        a += addr.address2 + '<br/>';
    }
    var a3 = '';
    if( addr.city != null && addr.city != '' ) {
        a3 += addr.city;
    }
    if( addr.province != null && addr.province != '' ) {
        if( a3 != '' ) { a3 += ' '; }
        a3 += addr.province;
    }
    if( addr.postal != null && addr.postal != '' ) {
        if( a3 != '' ) { a3 += '  '; }
        a3 += addr.postal;
    }
    if( a3 != '' ) {
        a += a3 + '<br/>';
    }
    if( addr.country != null && addr.country != '' ) {
        a += addr.country + '<br/>';
    }
    if( addr.phone != null && addr.phone != '' ) {
        a += 'Phone: ' + addr.phone + '<br/>';
    }
    return a;
}

Q.linkEmail = function(v) {
    if( typeof(v) == 'string' ) {
        v = v.replace(/(\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)/, "<a class=\"mailto\" href=\"mailto:$1\" onclick=\"event.stopPropagation();\">$1</a>");
    }

    return v;
}

Q.hyperlink = function(v) {
    if( typeof(v) == 'string' ) {
        v = '<a class="website" target="blank_" href="' + v + '" onclick="event.stopPropagation();">' + v + '</a>';
    }

    return v;
}

Q.formatHtml = function(c) {
    return c.replace(/\n/, '<br/>');
}

Q.length = function(o) {
    if( o == null ) {
        return 0;
    }
    if( o.keys ) {
        return o.keys.length;
    }
    var l = 0;
    for(var i in o) {
        if( o.hasOwnProperty(i) ) {
            l++;
        }
    }
    return l;
}

// n = name, v = value, d = days
Q.cookieSet = function(n,v,d) {
    if(d) { var date = new Date(); date.setTime(date.getTime()+(d*24*60*60*1000));var expires="; expires="+date.toUTCString();}
    else{var expires ='';}
    // Path cannot be manager for IE11
    document.cookie = n+'='+v+expires+'; path=/';
}

Q.cookieGet = function(n) {
    var ne = n+'=';
    var ca = document.cookie.split(';');
    for(var i=0;i<ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(ne) == 0) return c.substring(ne.length,c.length);
    }
    return null;
}

Q.showWebsite = function(url) {
    var e1 = Q.gE('m_website');
    var e2 = Q.gE('m_container');
    var iframe = Q.gE('mc_website_iframe');
    var d = iframe.contentWindow.document;
    d.open();
    d.write("<html><body style='background:#fff;'><div style='height: 100%; width: 100%; position:fixed; top:0px; left:0px; background: #fff; opacity: .5;'><table width='100%' style='width:100%;height:100%;border-collapse:separate;text-align:center;'><tbody style='vertical-align: middle;'><tr><td><img src='/qruqsp-mods/core/ui/themes/default/img/spinner.gif'></td></tr></tbody></table></div></body></html>");
    d.close();
    if( e1.style.display == 'block' ) {
        e1.style.display = 'none';
        e2.style.display = 'block';
    } else {
        e2.style.display = 'none';
        e1.style.display = 'block';
        // Force links to keep within iframe
        iframe.onload = function() {
            var a=this.contentWindow.document.getElementsByTagName("a");
            for(var i=0;i<a.length;i++) {
                a[i].onclick=function() {
                    iframe.src = this.getAttribute('href');
                    return false;
                }
            }
        };
        var url = '/preview/' + Q.curStation.modules['qruqsp.web'].settings.sitename + url;
        iframe.src = url;
    }
    Q.resize();
}

Q.showPDF = function(m, p) {
    if( Q.device == 'ipad' && window.navigator != null && window.navigator.standalone == true ) {
        var e1 = Q.gE('m_pdf');
        var e2 = Q.gE('m_container');
        var iframe = Q.gE('mc_pdf_iframe');
        iframe.src = "about:blank";
        if( e1.style.display == 'block' ) {
            e1.style.display = 'none';
            e2.style.display = 'block';
        } else {
            var d = iframe.contentWindow.document;
            d.open();
            d.write("<html><body style='background:#fff;'><div style='height: 100%; width: 100%; position:fixed; top:0px; left:0px; background: #fff; opacity: .5;'><table width='100%' style='width:100%;height:100%;border-collapse:separate;text-align:center;'><tbody style='vertical-align: middle;'><tr><td><img src='/qruqsp-mods/core/ui/themes/default/img/spinner.gif'></td></tr></tbody></table></div></body></html>");
            d.close();
            e2.style.display = 'none';
            e1.style.display = 'block';
            var url = Q.api.getUploadURL(m, p);
            iframe.src = url;
        }
        Q.resize();
    } else {
        Q.api.openPDF(m, p);
    }
}

Q.printPDF = function() {
    window.print();
}

Q.alert = function(msg) {
    var e = Q.gE('m_alert_msg');
    e.innerHTML = '<td>' + msg + '</td>';
    Q.show('m_alert');
    Q.hide('m_container');
}

Q.modFlags = function(m) {
    if( Q.curStation != null && Q.curStation.modules != null && Q.curStation.modules[m] != null && Q.curStation.modules[m].flags != null ) {
        return Q.curStation.modules[m].flags;
    }
    return 0;
}
Q.modFlags2 = function(m) {
    if( Q.curStation != null && Q.curStation.modules != null && Q.curStation.modules[m] != null && Q.curStation.modules[m].flags2 != null ) {
        return Q.curStation.modules[m].flags2;
    }
    return 0;
}

Q.modOn = function(m) {
    if( Q.curStation != null && Q.curStation.modules != null && Q.curStation.modules[m] != null ) {
        return true;
    }
    return false;
}

Q.modFlagOn = function(m, f) {
    if( f > 0xFFFFFFFF ) {
        f = f.toString(16);
        f = f.substr(0, f.length-8);
        return (Q.modFlags2(m)&f)==f?true:false;
    }
    return (Q.modFlags(m)&f)==f?true:false;
}

Q.modFlagSet = function(m, f) {
    if( f > 0xFFFFFFFF ) {
        f = f.toString(16);
        f = f.substr(0, f.length-8);
        return (Q.modFlags2(m)&f)==f?'yes':'no';
    }
    return (Q.modFlags(m)&f)==f?'yes':'no';
}

Q.modFlagAny = function(m, f) {
    if( f > 0xFFFFFFFF ) {
        f2 = f.toString(16);
        f2 = f2.substr(0, f2.length-8);
        return ((Q.modFlags2(m)&f2)>0 || (Q.modFlags(m)&f))?'yes':'no';
    }
    return (Q.modFlags(m)&f)>0?'yes':'no';
}
