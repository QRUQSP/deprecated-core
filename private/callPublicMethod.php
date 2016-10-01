<?php
//
// Description
// -----------
// This function is a generic wrapper that can call any method.
// It takes an array as an argument, and that array must
// contain api_key, and method.  The format is optional, but 
// auth_token is required for most methods.
//
// api_key -    The key assigned to the client application.  This
//              will be verified in the qruqsp_core_api_keys module
//
// auth_token - The auth_token is assigned after authentication.  If
//              auth_token is blank, then only certain method calls are allowed.
//
// method -     The method to call.  This is a decimal notated
//
// format -     (optional) What is the requested format of the response.  This can be
//              xml, html, tmpl or hash.  If the request would like json, 
//              xml-rpc, rest or php_serial, then the format
//
// Arguments
// ---------
// qruqsp:
//
function qruqsp_core_callPublicMethod(&$q) {
    //
    // Check if the api_key is specified
    //
    if( !isset($q['request']['api_key']) || $q['request']['api_key'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.82', 'msg'=>'No api_key supplied'));
    }

    //
    // Check the API Key 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAPIKey');
    $rc = qruqsp_core_checkAPIKey($q);
    if( $rc['stat'] != 'ok' || $q['request']['method'] == 'qruqsp.core.checkAPIKey' ) { 
        return $rc;
    }

    //
    // FIXME: Log the last_access for the API key
    //

    //
    // Check if method has been specified
    //
    if( !isset($q['request']['method']) || $q['request']['method'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.83', 'msg'=>'No method supplied'));
    }

    //
    // Parse the method, and the function name.  
    //
    $method_filename = $q['config']['core']['root_dir'] . '/'
        . preg_replace('/([a-z]+)\.([a-z0-9]+)\./', '\1-mods/\2/public/', $q['request']['method']) . '.php';
    $method_function = preg_replace('/\./', '_', $q['request']['method']);

    //
    // FIXME: Log the request in the Action Log, update with output
    // at the end of this function if successful
    //
    // qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'actionLogEntry');
    // qruqsp-core-actionLogEntry($q);

    //
    // If the user has not authenticated, then only a small number of 
    // methods are available, and they must be listed here.
    //
    $no_auth_methods = array(
        'qruqsp.users.auth', 
        'qruqsp.users.passwordRequestReset',
        'qruqsp.users.changeTempPassword',
        'qruqsp.core.echoTest', 
        'qruqsp.core.getAddressCountryCodes'
        );

    //
    // Load the session if an auth_token was passed
    //
    if( isset($q['request']['auth_token']) && $q['request']['auth_token'] != '' ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'sessionOpen');
        $rc = qruqsp_core_sessionOpen($q);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    } 

    //
    // Check if the user needs to be authenticated for this
    //
    if( !in_array($q['request']['method'], $no_auth_methods) 
        && (!isset($q['session']) || !is_array($q['session']) 
        || !is_array($q['session']['user']) || !isset($q['session']['user']['id'])
        || $q['session']['user']['id'] <= 0)
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.84', 'msg'=>'Not authenticated'));
    }

    //
    // Check if the method exists, after we check for authentication,
    // because we don't want people to be able to figure out valid
    // function calls by probing.
    //
    if( $method_filename == '' || $method_function == '' || !file_exists($method_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.85', 'msg'=>'Method does not exist'));
    }

    //
    // Include the method function
    //
    require_once($method_filename);

    if( !is_callable($method_function) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.86', 'msg'=>'Method does not exist'));
    }

    // FIXME: Add failed requests to log

    $method_rc = $method_function($q);

    //
    // Log the request
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'logAPIRequest');
    $rc = qruqsp_core_logAPIRequest($q);
    if( $rc['stat'] != 'ok' ) {
        error_log('Failed to log API Request');
    }

    //
    // Check if the method returned binary data, and we should just exit
    //
    if( $method_rc['stat'] == 'binary' ) {
        exit;
    }

    //
    // Save the session if successful transaction
    //
    if( isset($q['session']['auth_token']) && $q['session']['auth_token'] != '' ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'sessionSave');
        $rc = qruqsp_core_sessionSave($q);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // FIXME: Update the action log with the results from the request
    //
    // qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'actionLogResult');
    // qruqsp-core-actionLogResult($q, );

    return $method_rc;
}
