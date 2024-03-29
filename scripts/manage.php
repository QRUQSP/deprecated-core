<?php
//
// This script will build the index page required to load the javascript for qruqsp-manage
//

//
// Load the qruqsp config
//
global $qruqsp_root;
$qruqsp_root = dirname(__FILE__);
// Some systems don't follow symlinks like others
if( !file_exists($qruqsp_root . '/qruqsp-api.ini') ) {
    $qruqsp_root = dirname(dirname(dirname(dirname(__FILE__))));
}
$manage_root = $qruqsp_root . '/qruqsp-mods';
$themes_root = $qruqsp_root . '/qruqsp-mods/core/ui/themes';
$manage_js = "/qruqsp-mods/core/ui";
$manage_themes = "/qruqsp-mods/core/ui/themes";
//$start_container = 'm_login';
$start_container = 'm_loading';

require_once($qruqsp_root . '/qruqsp-mods/core/private/loadMethod.php');
require_once($qruqsp_root . '/qruqsp-mods/core/private/init.php');
$rc = qruqsp_core_init($qruqsp_root, 'manage');
if( $rc['stat'] != 'ok' ) {
    print "<html><head><title>Error</title></head>";
    print_error('There is currently a configuration problem, please try again later.');
    print "</html>";
    exit;
}
$q = $rc['q'];

//
// Check if this should be a recovery page for password
//
$temp_password = '';
if( preg_match('/^passwordreset=(.*)$/', $_SERVER['QUERY_STRING'], $matches) ) {
    $start_container = 'm_recover';
    $temp_password = $matches[1];
}
$email_address = '';
if( preg_match('/^email=(.*)\&p=(.*)$/', $_SERVER['QUERY_STRING'], $matches) ) {
    $start_container = 'm_recover';
    $email_address = urldecode($matches[1]);
    $temp_password = $matches[2];
}

//
// The station which stores the qruqsp-manage bugs
//
$master_id = $q['config']['qruqsp.core']['master_station_id'];

//
// Load the qruqsp-manage config file
//
$config = parse_ini_file($qruqsp_root . '/qruqsp-manage.ini', true);
if( $config == false || !isset($config['qruqsp.core']['api_key']) ) {
    print "<html><head><title>Error</title></head>";
    print_error('It appears that qruqsp-manage has not been installed.');
    print "</html>";
    exit;
}
$apikey = $config['qruqsp.core']['api_key'];
$manage_js = $config['qruqsp.core']['manage_root_url'] . "/core/ui";
$manage_themes = $config['qruqsp.core']['themes_root_url'];

//
// If SSL is turned off in the config, then this is a development machine,
// and don't need to worry, just use https for API.  If ssl is turned on, then
// check to make sure that index.php was called from https, if not redirect.
//
if( isset($q['config']) && isset($q['config']['qruqsp.core']) && isset($q['config']['qruqsp.core']['ssl']) 
    && $q['config']['qruqsp.core']['ssl'] == 'off' 
    && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on') ) {
    $apiurl = 'http://' . $_SERVER['HTTP_HOST'] . $config['qruqsp.core']['json_url'];
} else {
    //
    // Check if secure connection
    //
    if( (isset($_SERVER['HTTP_CLUSTER_HTTPS']) && $_SERVER['HTTP_CLUSTER_HTTPS'] == 'on') 
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ) )  {
        $apiurl = 'https://' . $_SERVER['HTTP_HOST'] . $config['qruqsp.core']['json_url'];
    } else {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit;
    }
}

?>
<?php
if( !isset($_SERVER['HTTP_USER_AGENT']) ) {
    print_unsupported();
}
//
// Default to a generic device and browser.  Try to find a better set
// from the USER_AGENT string.
//
$minimized = 'yes';     // Deliver minimized code if applicable
$device = 'generic';
$touch = 'no';
$browser = 'unsupported';
$size = 'normal';
$engine = 'generic';
if( preg_match('/Mozilla\/5.*iPad.*AppleWebKit\/(5|6).*KHTML, like Gecko.*Mobile\/.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'ipad';
    $touch = 'yes';
    $browser = 'safari';
    $engine = 'webkit';
}
elseif( preg_match('/Mozilla\/5.*Android.*Xoom .*AppleWebKit\/5.*KHTML, like Gecko.*Safari\/5.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'zoom';
    $touch = 'yes';
    $browser = 'safari';
    $engine = 'webkit';
}
elseif( preg_match('/Mozilla\/5.*iPhone.*AppleWebKit\/.*KHTML, like Gecko.*Mobile\/.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'iphone';
    $touch = 'yes';
    $browser = 'safari';
    $engine = 'webkit';
    $size = 'compact';
}
elseif( preg_match('/Mozilla\/5.*Android .*AppleWebKit\/.*KHTML, like Gecko.*Mobile.* Safari\/5.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'android';
    $touch = 'yes';
    $browser = 'safari';
    $engine = 'webkit';
    $size = 'compact';
}
// Mozilla/5.0 (Linux; U; Android 4.2.1; en-us; ASUS Transformer Pad TF300T Build/JOP40D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30
elseif( preg_match('/Mozilla\/5.*Android.*ASUS Transformer Pad.*AppleWebKit\/.*KHTML, like Gecko.* Safari\/5.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'android';
    $touch = 'yes';
    $browser = 'safari';
    $engine = 'webkit';
    $minimized = 'no';
}
// Mozilla/5.0 (hp-tablet; Linux; hpwOS/3.0.2; U; en-CA) AppleWebKit/534.6 (KHTML, like Gecko) wOSBrowser/234.40.1 Safari/534.6 TouchPad/1.0
elseif( preg_match('/Mozilla\/5.*hp-tablet;.*U;.*AppleWebKit\/.*KHTML, like Gecko.* Safari\/534.*TouchPad.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'hptablet';
    $touch = 'yes';
    $browser = 'safari';
    $engine = 'webkit';
    $start_container = 'm_login';
}

// Mozilla/5.0 (PlayBook; U; RIM Tablet OS 1.0.0; en-US) AppleWebKit/534.11+ (KHTML, like Gecko) Version/7.1.0.7 Safari/534.11+
// Blackberry Playbook
elseif( preg_match('/Mozilla\/5.*PlayBook.*U;.*AppleWebKit\/.*KHTML, like Gecko.* Safari\/5.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'blackberry';
    $touch = 'yes';
    $browser = 'safari';
    $engine = 'webkit';
}

// Blackberry Torch
elseif( preg_match('/Mozilla\/5.*BlackBerry.*U;.*AppleWebKit\/.*KHTML, like Gecko.*Mobile.* Safari\/5.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'blackberry';
    $touch = 'yes';
    $browser = 'safari';
    $engine = 'webkit';
    $size = 'compact';
}

// Blackberry 10
// Mozilla/5.0 (BB10; Touch) AppleWebKit/537.10+ (KHTML, like Gecko) Version/10.1.0.807 Mobile Safari/537.10+
elseif( preg_match('/Mozilla\/5.*BB10.*AppleWebKit\/.*KHTML, like Gecko.*Mobile.* Safari\/5.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'bb10';
    $touch = 'yes';
    $browser = 'safari';
    $engine = 'webkit';
    $size = 'compact';
}

// Chrome
elseif( preg_match('/Mozilla\/5.*AppleWebKit\/.* Chrome\/([0-9][0-9]).* Safari\/.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'generic';
    $touch = 'no';
    $browser = 'chrome';
    $engine = 'webkit';
    // Used for debugging compact version
    // $size = 'compact';
}
// Firefox
elseif( preg_match('/Mozilla\/5.*Gecko\/.* Firefox\/(4|5|6|7|8|9|[1-9][0-9]).*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'generic';
    $touch = 'no';
    $browser = 'firefox';
    $engine = 'gecko';
}
// Epiphany
elseif( preg_match('/Mozilla\/5.*Gecko\/.* Epiphany\/2.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'generic';
    $touch = 'no';
    $browser = 'epiphany';
    $engine = 'gecko';
}
// Safari
elseif( preg_match('/Mozilla\/5.* AppleWebKit\/.* Safari\/.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'generic';
    $touch = 'no';
    $browser = 'safari';
    $engine = 'webkit';
}
// IE 11 Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko
elseif( preg_match('/Mozilla\/5.* Trident\/7.*rv:11\..*like Gecko/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'generic';
    $touch = 'no';
    $browser = 'ie';
    $engine = 'trident';
}
// IE 8
elseif( preg_match('/Mozilla\/4.*MSIE 8.* Trident\/4.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
//  $device = 'generic';
//  $touch = 'no';
//  $browser = 'ie';
//  $engine = 'trident';
}
// Opera
elseif( preg_match('/Opera\/9.*Presto\/2.* Version\/11.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'generic';
    $touch = 'no';
    $browser = 'opera';
    $engine = 'presto';
}
// Custom App
elseif( preg_match('/Mozilla\/5.* .* AppleWebKit\/.* QRUQSPApp\/.*/', $_SERVER['HTTP_USER_AGENT']) == 1 ) {
    $device = 'generic';
    $touch = 'no';
    $browser = 'safari';
    $engine = 'webkit';
}

// print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
print '<!DOCTYPE html>';
if( file_exists("$manage_root/$device-$engine.manifest") ) {
    print "<html manifest='$device-$engine.manifest'>\n";
} else {
    print "<html>\n";
}

//
// FIXME: Check if size was passed on the argument line
//

?>
<head>

<meta content='text/html;charset=UTF-8' http-equiv='Content-Type'>
<meta content='UTF-8' http-equiv='encoding'>

<?php
if( isset($config['qruqsp.core']['site_title']) ) {
    print "<title>" . $config['qruqsp.core']['site_title'] . "</title>";
} else {
    print "<title>QRUQSP</title>\n";
}
$ts = time();
//
// Load device/browser specific javascript
//
// FIXME: Build minimizer and .js joiner
if( file_exists("$manage_root/core/ui/$device-$engine.min.js") ) {
    print "<script src='$manage_js/$device-$engine.min.js?ts=$ts' type='text/javascript'></script>\n";
//  print "<script src='$manage_js/colorPicker.js?ts=$ts' type='text/javascript'></script>\n";
} elseif( file_exists("$manage_root/core/ui/e-$engine.js") ) {
    print "<script src='$manage_js/qruqsp.js?ts=$ts' type='text/javascript'></script>\n";
    print "<script src='$manage_js/qruqsp_panels.js?ts=$ts' type='text/javascript'></script>\n";
    print "<script src='$manage_js/qruqspAPI.js?ts=$ts' type='text/javascript'></script>\n";
    print "<script src='$manage_js/colorPicker.js?ts=$ts' type='text/javascript'></script>\n";
    print "<script src='$manage_js/e-$engine.js?ts=$ts' type='text/javascript'></script>\n";
    if( $size == 'compact' ) {
        print "<script src='$manage_js/s-compact.js?ts=$ts' type='text/javascript'></script>\n";
    } else {
        print "<script src='$manage_js/s-normal.js?ts=$ts' type='text/javascript'></script>\n";
    }
    print "<script src='$manage_js/e-$engine.js?ts=$ts' type='text/javascript'></script>\n";
    if( file_exists("$manage_root/core/ui/d-$device.js") ) {
        print "<script src='$manage_js/d-$device.js?ts=$ts' type='text/javascript'></script>\n";
    }
} else {
    //
    // Include stylesheets and output error
    //
    print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/style.css' />\n";
    print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/s-normal.css' />\n";
    print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/e-webkit.css' />\n";
    print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/e-gecko.css' />\n";
    print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/e-trident.css' />\n";
    print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/e-presto.css' />\n";
    print "</head>";
    print_unsupported();
    print "</html>";
    exit;
}

//
// Output any device-engine specific headers
//
if( $device == 'ipad' && $engine == 'webkit' ) { ?>
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta id="apple_sbarstyle" name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <?php print "<link rel='apple-touch-icon' href='$manage_themes/default/img/icon.png'/>\n"; ?>
    <?php print "<link rel='apple-touch-startup-image' href='$manage_themes/default/img/icon.png'/>\n"; ?>
    <?php /* <script src='/qruqsp-manage/core/ui/webkitdragdrop.js' type='text/javascript'></script> */ ?>
<?php } elseif( ($device == 'ipad' || $device == 'xoom' || $device == 'hptablet' ) && $engine == 'webkit' ) { ?>
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <?php print "<link rel='apple-touch-icon' href='$manage_themes/default/img/icon.png'/>\n"; ?>
    <?php print "<link rel='icon' sizes='192x192' href='$manage_themes/default/img/icon192.png'/>\n"; ?>
    <?php print "<link rel='icon' sizes='128x128' href='$manage_themes/default/img/icon128.png'/>\n"; ?>
    <?php print "<link rel='apple-touch-startup-image' href='$manage_themes/default/img/icon.png'/>\n"; ?>
    <script src='/qruqsp-manage/core/ui/webkitdragdrop.js' type='text/javascript'></script>
    <?php // <script src='js/iscroll.js' type='text/javascript'></script> ?>
<?php } elseif( $device == 'iphone' && $engine == 'webkit' ) { ?>
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <?php print "<link rel='apple-touch-icon' href='$manage_themes/default/img/icon.png'/>\n"; ?>
<?php } elseif( $device == 'bb10' && $engine == 'webkit' ) { ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
<?php } elseif( ($device == 'blackberry' || $device == 'android') && $engine == 'webkit' ) { ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0, target-densitydpi=medium-dpi" />
    <?php // <script src='/qruqsp-manage/core/ui/iscroll.js' type='text/javascript'></script> ?>
<?php } 
print "<link rel='icon' href='$manage_themes/default/img/favicon.png' type='image/png' />\n";

//
// Check to see if a compile, minimized version exists
//
if( $minimized == 'yes' && file_exists("$themes_root/default/$device-$engine.min.css") ) {
    print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/$device-$engine.min.css?ts=$ts' />\n";
    // print "<link id='station_colours' rel='stylesheet' type='text/css' href='$manage_themes/default/colors.css' />\n";
} else {
    // Include the basic stylesheet
    print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/style.css?ts=$ts' />\n";
    // print "<link id='station_colours' rel='stylesheet' type='text/css' href='$manage_themes/default/colors.css' />\n";

    //
    // Decide which stylesheets to include
    //
    if( file_exists("$themes_root/default/e-$engine.css") ) {
        print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/e-$engine.css?ts=$ts' />\n";
    } 
    if( file_exists("$themes_root/default/s-$size.css") ) { 
        print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/s-$size.css?ts=$ts' />\n";
    }
    if( file_exists("$themes_root/default/d-$device.css") ) {
        print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/d-$device.css?ts=$ts' />\n";
    }
    if( file_exists("$themes_root/default/s-$size-$engine.css") ) { 
        print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/s-$size-$engine.css?ts=$ts' />\n";
    }
    if( file_exists("$themes_root/default/d-$device-$engine.css") ) { 
        print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/d-$device-$engine.css?ts=$ts' />\n";
    }
}
//
// Check for default station colours
//
if( file_exists("$themes_root/default/colors.css") ) {
    print "<style id='station_colours' type='text/css'>" . file_get_contents("$themes_root/default/colors.css") . "</style>";
} else {
    print "<style id='station_colours' type='text/css'></style>";
}

if( isset($config['qruqsp.core']['theme_additional_css']) ) {
    print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/" . $config['qruqsp.core']['theme_additional_css'] . "' media='screen' />\n";
}
print "<link rel='stylesheet' type='text/css' href='$manage_themes/default/print.css' media='print' />\n";

?>
</head>
<?php 
if( $browser == 'unsupported' ) { 
    // FIXME: Add logo and proper error page
    print_unsupported();
    print "</html>";
    exit;
} ?>
<?php
    //
    // Build the config information for the UI into a JSON wrapper
    //
    $manage_config = array(
        'device'=>$device,
        'browser'=>$browser,
        'engine'=>$engine,
        'touch'=>$touch,
        'size'=>$size,
        'api_url'=>$apiurl,
        'api_key'=>$apikey,
        'master_id'=>$master_id,
        'root_url'=>$config['qruqsp.core']['manage_root_url'],
        'themes_root_url'=>$config['qruqsp.core']['themes_root_url'],
        'start_menu'=>'qruqsp.core.main',
        'modules'=>array(),
        );
    //
    // Check if submiting form authorized user
    //
    if( isset($_POST['auth_token']) && $_POST['auth_token'] != '' ) {
        $manage_config['auth_token'] = $_POST['auth_token'];
    }
    if( isset($_POST['username']) && $_POST['username'] != ''
        && isset($_POST['password']) && $_POST['password'] != '' ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'sessionStart');
        $q['request']['api_key'] = $apikey;
        $rc = qruqsp_core_sessionStart($q, $_POST['username'], $_POST['password']);
        if( $rc['stat'] != 'ok' ) {
//          error_log(serialize($rc));
        } else {
            $manage_config['auth_token'] = $rc['auth']['token'];
        }
    }
        
    foreach($config as $module => $mod_config) {
        if( $module != 'qruqsp.core' ) {
            $manage_config['modules'][$module] = $mod_config;
        }
    }
    //
    // Check for override in config for the mainmenu
    //
    if( isset($config['qruqsp.core']['start_menu']) && $config['qruqsp.core']['start_menu'] != '' ) {
        $manage_config['start_menu'] = $config['qruqsp.core']['start_menu'];
    }
    if( isset($config['qruqsp.core']['station_menu']) && $config['qruqsp.core']['station_menu'] != '' ) {
        $manage_config['station_menu'] = $config['qruqsp.core']['station_menu'];
    }
    
?>
<body id="m_body" onresize="Q.resize();" onload='Q.init(<?php print json_encode($manage_config);?>);'>
<noscript>
Javscript must be enabled for this application to work.
</noscript>
<div id="m_loading" style="display:none;"><table><tr><td><?php print "<img src='$manage_themes/default/img/spinner.gif' />"; ?></td></table></div>
<?php if( $start_container == 'm_login') { ?>
<div id="m_login">
<?php } else { ?>
<div id="m_login" style="display:none;">
<?php } ?>
    <div id="mc_login">
        <div id="mcw_login" class="narrow">
            <?php print "<img id='logo' class='logo' src='$manage_themes/default/img/logo.png'>";?>
            <br/>
            <iframe src="/qruqsp-login.php" id="login_target" name="login_target" style="display:none;"></iframe>
            <form id="mc_login_form" name="mc_login_form" class="narrow" action="/qruqsp-login.php" onsubmit="Q.auth();" target="login_target" method="POST">
                <div class="section">
                    <h2>Email or Username</h2>
                    <table class="list noheader form" cellspacing='0' cellpadding='0'>
                        <tr class="textfield"><td class="input"><label style="display:none;" for="username">Email or Username</label><input id="username" type="text" maxlength="255" name="username" value=""></td></tr>
                    </table>
                    <h2>Password</h2>
                    <table class="list noheader form" cellspacing='0' cellpadding='0'>
                        <tr class="textfield"><td class="input"><label style="display:none;" for="password">Password</label><input id="password" type="password" maxlength="50" name="password" value=""></td></tr>
                    </table>

                </div>
                <input type="submit" value="Sign In" class="button"/>
                <table class="loginoptions list noheader form" cellspacing='0' cellpadding='0'>
                    <tr class='checkbox'>
                        <td class='checkbox'><input type="checkbox" id='rm' name='rm' value='yes' class='checkbox'/><label for='rm'>Remember me</label></td>
                        <td>
                            <p class="right link"><a href="" onClick="Q.hide('m_login'); Q.show('m_forgot'); return false;">Forgot Password</a></p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>
<?php if( $start_container == 'm_forgot' ) { ?>
<div id="m_forgot">
<?php } else { ?>
<div id="m_forgot" style="display:none;">
<?php } ?>
    <div id="mf_forgot">
        <div id="mc_content_wrap" class="narrow">
            <?php print "<img id='logo' class='logo' src='$manage_themes/default/img/logo.png'>";?>
            <br/>
            <form id="mf_reset" name="mf_reset" class="narrow" onsubmit="Q.pwdReset(); return false;" action="" method="POST">
                <div class="section">
                    <table class="list simplelist noheader border" cellspacing='0' cellpadding='0'>
                        <tr class="clickable"><td>Please enter your email address. You will receive a new temporary password by email.</td></tr>
                    </table>
                    <h2>Email</h2>
                    <table class="list noheader form" cellspacing='0' cellpadding='0'>
                        <tr class="textfield"><td class="input"><label style="display:none;" for="reset_email">Email</label><input id="reset_email" type="email" maxlength="255" name="reset_email"></td></tr>
                    </table>
                </div>
                <input type="submit" value="Get New Password" class="button"/>
                <br/><br/>
<?php if( $start_container == 'm_recover' ) { ?>
                <p class="right link"><a href="" onClick="Q.hide('m_forgot'); Q.show('m_recover'); return false;">Cancel</a></p>
<?php } else { ?>
                <p class="right link"><a href="" onClick="Q.hide('m_forgot'); Q.show('m_login'); return false;">Cancel</a></p>
<?php } ?>
            </form>
        </div>
    </div>
</div>
<?php if( $start_container == 'm_recover' ) { ?>
<div id="m_recover">
<?php } else { ?>
<div id="m_recover" style="display:none;">
<?php } ?>
    <div id="mr_content">
        <div id="mr_content_wrap" class="narrow">
            <?php print "<img id='logo' class='logo' src='$manage_themes/default/img/logo.png'>";?>
            <br/>
            <form id="mr_reset" name="mr_reset" class="narrow" onsubmit="Q.tempPassReset(); return false;" action="" method="POST">
                <input type='hidden' id='temp_password' value='<?php echo $temp_password;?>'/>
                <div class="section">
                    <table class="list simplelist noheader border" cellspacing='0' cellpadding='0'>
                        <tr class="clickable"><td>Please enter your email address and choose a new password.</td></tr>
                    </table>
                    <h2>Email</h2>
                    <table class="list noheader form" cellspacing='0' cellpadding='0'>
                        <tr class="textfield"><td class="input"><label style="display:none;" for="recover_email">Email</label><input id="recover_email" type="email" maxlength="255" name="recover_email" value="<?php echo $email_address;?>"></td></tr>
                    </table>
                    <h2>New Password</h2>
                    <table class="list noheader form" cellspacing='0' cellpadding='0'>
                        <tr class="textfield"><td class="input"><label style="display:none;" for="new_password">Password</label><input id="new_password" type="password" maxlength="255" name="new_password"></td></tr>
                    </table>
                    <h2>New Password Again</h2>
                    <table class="list noheader form" cellspacing='0' cellpadding='0'>
                        <tr class="textfield"><td class="input"><label style="display:none;" for="new_password_again">Again</label><input id="new_password_again" type="password" maxlength="255" name="new_password_again"></td></tr>
                    </table>
                </div>
                <input type="submit" value="Set Password" class="button"/>
                <br/><br/>
                <p class="right link"><a href="" onClick="Q.hide('m_recover'); Q.show('m_forgot'); return false;">Send New Activation</a></p>
            </form>
        </div>
    </div>
</div>
<div id="m_relogin" style="display:none;">
    <div id="mc_relogin">
        <div id="mcw_relogin" class="narrow">
            <?php print "<img id='logo' class='logo' src='$manage_themes/default/img/logo.png'>";?>
            <br/>
            <form id="mc_relogin" name="mc_relogin" class="narrow" onsubmit="Q.reauth();" action="/qruqsp-login.php" target="relogin_target" method="POST">
                <div class="section">
                    <p><br/></p>
                    <p>Session expired, please enter your password to verify your account.</p>
                    <p><br/></p>
<?php //                    <h2>Username</h2>
//                  <table class="list noheader form" cellspacing='0' cellpadding='0'>
//                      <tr class="textfield"><td class="input"><label style="display:none;" for="username">Username</label><input id="username" type="text" maxlength="255" name="username" value="readonly></td></tr>
//                  </table>
//                  <h2>Password</h2>
?>
                    <table class="list noheader form" cellspacing='0' cellpadding='0'>
                        <tr class="textfield"><td class="input"><label style="display:none;" for="reauthpassword">Password</label><input id="reauthpassword" type="password" maxlength="50" name="reauthpassword"></td></tr>
                    </table>
                </div>
                <input type="submit" value="Verify" class="button"/>
                <br/><br/>
                <p class="right link"><a href="" onClick="Q.reload(); return false;">Switch User</a></p>
            </form>
            <iframe style="margin:0px;padding:0px;border:0px;display:block;width:0px;height:0px;" name="relogin_target" id="relogin_target"></iframe>
        </div>
    </div>
</div>
<?php if( $start_container == 'm_error' ) { ?>
<div id="m_error">
<?php } else { ?>
<div id="m_error" style="display:none;">
<?php } ?>
    <div id="me_content">
        <div id="mc_content_wrap" class="medium">
            <p>Oops, we seem to have hit a snag.</p>
            <p><br/></p>
            <h2></h2>
            <table class="list noheader border" cellspacing='0' cellpadding='0'>
                <tbody id="me_error_list">
                </tbody>
            </table>
            <p><br/></p>
            <p>If you don't understand why you encountered the error, please click the Submit Bug button.</p>
            <p><br/></p>
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr><td><input type="submit" value="Close" class="button" onclick="Q.hide('m_error');"/></td>
                <td style="text-align:right;"><input type="submit" value="Submit Bug" class="button" onclick="Q.submitErrBug();"/></td></tr>
            </table>
        </div>
    </div>
</div>
<?php if( $start_container == 'm_alert' ) { ?>
<div id="m_alert">
<?php } else { ?>
<div id="m_alert" style="display:none;">
<?php } ?>
    <div id="m_alert_content">
        <div id="mc_content_wrap" class="narrow">
            <p><br/><br/></p>
            <table class="list noheader border" cellspacing='0' cellpadding='0'>
                <tbody id="m_alert_msg">
                </tbody>
            </table>
            <p><br/></p>
            <table class="list simplebuttons noheader border">
                <tr onclick="Q.hide('m_alert');Q.show('m_container');"><td class="button">Okay</td></tr>
            </table>
        </div>
    </div>
</div>
<?php if( $start_container == 'm_help' ) { ?>
<div id="m_help" class="guided-off">
<?php } else { ?>
<div id="m_help" style="display:none;" class="guided-off">
<?php } ?>
    <table id="mh_header" class="headerbar" cellspacing="0" cellpadding="0">
        <tr>
        <td id="mh_leftbuttons_0" class="leftbuttons hide"></td>
        <td id="mh_leftbuttons_1" class="leftbuttons hide"></td>
        <?php if( $size == 'compact' ) { ?>
            <td class="spacer"></td>
        <?php } else { ?>
            <td id="mh_title" class="title"></td>
        <?php } ?>
        <td id="mh_rightbuttons_1" class="rightbuttons hide"></td>
        <td id="mh_rightbuttons_0" class="rightbuttons hide"></td>
        </tr>
    </table>
    <?php if( $size == 'compact' ): ?>
        <table id="mh_subheader" class="subheaderbar" cellspacing="0" cellpadding="0">
        <tr>
            <td id="mh_title" class="title"></td>
        </tr>
        </table>
    <?php endif; ?>
    <div id="mh_content"><div id="mh_content_scroller" class="scrollable">
        <div id="mh_apps"></div>
    </div></div>
</div>
<div id="m_website" style="display:none;overflow:hidden;">
<table id="mw_header" class="headerbar" cellspacing="0" cellpadding="0">
    <tr>
    <td id="mw_home_button" class="homebutton" onClick="Q.showWebsite();"><div class="button home"><span class="faicon">&#xf060;</span><span class="label">Back</span></div></td>
    <td id="mw_leftbuttons_0" class="leftbuttons hide"></td>
    <td id="mw_leftbuttons_1" class="leftbuttons hide"></td>
    <?php if( $size == 'compact' ) { ?>
        <td class="spacer">&nbsp;</td>
    <?php } else { ?>
        <td id="mw_title" class="title"></td>
    <?php } ?>
    <td id="mw_rightbuttons_1" class="rightbuttons hide"></td>
    <td id="mw_rightbuttons_0" class="rightbuttons hide"></td>
    <td id="mw_help_button" class="helpbutton hide"></td>
    </tr>
</table>
<div id="mc_website"><iframe id="mc_website_iframe" src="" width="100%" height="100%" marginwidth="0" marginheight="0" frameborder="no" scrolling="yes" src=""></iframe></div>
</div>
<div id="m_pdf" style="display:none;overflow:hidden;">
<table id="mp_header" class="headerbar noprint" cellspacing="0" cellpadding="0">
    <tr>
    <td id="mp_home_button" class="homebutton" onClick="Q.showPDF();"><div class="button home"><span class="faicon">&#xf060;</span><span class="label">Back</span></div></td>
    <td id="mp_leftbuttons_0" class="leftbuttons hide"></td>
    <td id="mp_leftbuttons_1" class="leftbuttons hide"></td>
    <?php if( $size == 'compact' ) { ?>
        <td class="spacer">&nbsp;</td>
    <?php } else { ?>
        <td id="mp_title" class="title"></td>
    <?php } ?>
    <td id="mp_rightbuttons_1" class="rightbuttons hide"></td>
    <td id="mp_rightbuttons_0" class="rightbuttons hide"></td>
    <td id="mp_print_button" class="rightbuttons" onClick="Q.printPDF();"><div class="button home"><span class="faicon">&#xf02f;</span><span class="label">Print</span></div></td>
    </tr>
</table>
<div id="mc_pdf"><div id="mc_content_wrap" class="medium"><p>Use two fingers to scroll</p></div><iframe id="mc_pdf_iframe" src="" width="100%" height="100%" marginwidth="0" marginheight="0" frameborder="no" scrolling="yes" onload="Q.resize();"></iframe></div>
</div>
<?php if( $start_container == 'm_container' ) { ?>
<div id="m_container" class="s-<?php echo $size;?> help-off guided-off xhelp-off">
<?php } else { ?>
<div id="m_container" class="s-<?php echo $size;?> help-off guided-off xhelp-off" style="display:none;">
<?php } ?>
    <table id="mc_header" class="headerbar" cellspacing="0" cellpadding="0">
        <tr>
        <td id="mc_home_button" class="homebutton" onClick="Q.home();"><div class="button home"><span class="faicon">&#xf015;</span><span class="label">Home</span></div></td>
        <td id="mc_leftbuttons_0" class="leftbuttons hide"></td>
        <td id="mc_leftbuttons_1" class="leftbuttons hide"></td>
        <?php if( $size == 'compact' ) { ?>
            <td class="spacer">&nbsp;</td>
        <?php } else { ?>
            <td id="mc_leftbuttons_2" class="leftbuttons hide"></td>
            <td id="mc_title" class="title"></td>
            <td id="mc_rightbuttons_2" class="rightbuttons hide"></td>
        <?php } ?>
        <td id="mc_rightbuttons_1" class="rightbuttons hide"></td>
        <td id="mc_rightbuttons_0" class="rightbuttons hide"></td>
        <td id="mc_help_button" class="helpbutton" onClick="Q.toggleHelp(Q.curHelpUID);"><div class="button help"><span class="faicon">&#xf128;</span><span class="label">Help</span></div></td>
        </tr>
    </table>
    <?php if( $size == 'compact' ): ?>
        <table id="mc_subheader" class="subheaderbar" cellspacing="0" cellpadding="0">
        <tr>
            <td id="mc_title" class="title"></td>
        </tr>
        </table>
    <?php endif; ?>
    <div id="mc_content"><div id="mc_content_scroller" class="scrollable">
        <div id="mc_apps"></div>
    </div></div>
</div>
</body>
</html>
<?php
//
// Supporting functions required to generate index page
//
function print_unsupported() {
    if( isset($_SERVER['HTTP_USER_AGENT']) ) {
        error_log("Unsupported Browser: " . $_SERVER['HTTP_USER_AGENT']);
    } else {
        error_log("Unknown browser from: " . $_SERVER['REMOTE_ADDR']);
    }
?>
<body>
<div id="m_error">
    <div id="me_content">
        <div id="mc_content_wrap" class="medium">
            <p>I'm sorry but the web browser you're using is currently unsupported.  Please download a current version.  
                The following is a list of supported browsers.</p>
            <p>&nbsp;</p>
            <h2>Recommended Browsers</h2>
            <table class="list noheader border" cellspacing='0' cellpadding='0'>
                <tbody>
                    <tr><td>Firefox 4</td><td><a href="http://www.mozilla.com/">Download</a></td></tr>
                    <tr><td>Chrome</td><td><a href="http://www.google.com/chrome">Download</a></td></tr>
                </tbody>
            </table>
            <h2>Other Supported Browsers</h2>
            <table class="list noheader border" cellspacing='0' cellpadding='0'>
                <tbody id="me_error_list">
                    <tr><td>Safari 4</td><td><a href="http://www.apple.com/safari/">Download</a></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
<?php
}

function print_error($msg) {
?>
<body>
<div id="m_error">
    <div id="me_content">
        <div id="mc_content_wrap" class="medium">
            <p>Oops, we seem to have hit a snag.</p>
            <table class="list header border" cellspacing='0' cellpadding='0'>
                <thead>
                    <tr><th>Package</th><th>Code</th><th>Message</th></tr>
                </thead>
                <tbody>
                    <tr><td>???</td><td>???</td><td><?php echo $msg; ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
<?php
}

?>
