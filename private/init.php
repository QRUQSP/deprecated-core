<?php
//
// Description
// ===========
// This function will initialize the $q variable which must be passed to all qruqsp function.  This function
// must be called before any others.
//
// This function will also:
// - load config
// - init database
//
// Arguments
// =========
// config_file:         The path to the config file must be passed.
//
function qruqsp_core_init($qruqsp_root, $output_format) {

    //
    // Initialize the qruqsp structure, and setup the return value
    // to include the stat.
    //
    $q = array();

    //
    // Load the config
    //
    require_once($qruqsp_root . '/qruqsp-mods/core/private/loadCinikiConfig.php');
    if( qruqsp_core_loadCinikiConfig($q, $qruqsp_root) == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.58', 'msg'=>'Internal configuration error'));
    }

    //
    // Initialize the object variable.  This stores all the object information as loaded, so no need to load again.
    //
    $q['objects'] = array();
    
    //
    // Initialize the station variable.  This is used to store settings for the station.
    //
    $q['station'] = array('settings'=>array(), 'modules'=>array(), 'user'=>array('perms'=>0));

    //
    // Initialize the request variables
    //
    $q['request'] = array();
    $q['request']['api_key'] = '';
    $q['request']['auth_token'] = '';
    $q['request']['method'] = '';
    $q['request']['args'] = array();

    //
    // Initialize the response variables, 
    // default to respond with xml.
    //
    $q['response'] = array();
    $q['response']['format'] = $output_format;

    $q['emailqueue'] = array();
    $q['fbrefreshqueue'] = array();
    $q['syncqueue'] = array();
    if( isset($q['config']['qruqsp.core']['sync.log_lvl']) ) {
        $q['syncloglvl'] = $q['config']['qruqsp.core']['sync.log_lvl'];
    } else {
        $q['syncloglvl'] = 0;
    }
    $q['synclogfile'] = '';

    //
    // Initialize Database
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInit');
    $rc = qruqsp_core_dbInit($q);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Initialize Session
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'sessionInit');
    $rc = qruqsp_core_sessionInit($q);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok', 'q'=>$q);
}
?>
