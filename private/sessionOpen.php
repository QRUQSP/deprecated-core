<?php
//
// Description
// -----------
// This function will open an existing session.
//
// Arguments
// ---------
// qruqsp: 
//
function qruqsp_core_sessionOpen(&$q) {

    if( !isset($q['session']) || !is_array($q['session']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.32', 'msg'=>'Internal configuration error', 'pmsg'=>'$q["session"] not set'));
    }

    if( !isset($q['request']['auth_token']) 
        || !isset($q['request']['api_key']) 
        || $q['request']['api_key'] == ''
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.33', 'msg'=>'Internal configuration error', 'pmsg'=>'auth_token and/or api_key empty'));
    }

    if( $q['request']['auth_token'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.34', 'msg'=>'No auth_token specified'));
    }

    //
    // Check if a session is already started based on the auth_token
    // and api_key.
    //
    // A combination of the api_key and auth_token are used, so somebody
    // would have to guess an api_key and auth_token of somebody logged
    // in using that api_key, ie from the same application.  Adds an
    // extra layer of security for session.
    //

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    $strsql = "SELECT auth_token, api_key, user_id, date_added, "
        . "(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(last_saved)) as session_length, timeout, "
        . "session_data "
        . "FROM qruqsp_core_session_data "
        . "WHERE auth_token = '" . qruqsp_core_dbQuote($q, $q['request']['auth_token']) . "' "
        . "AND api_key = '" . qruqsp_core_dbQuote($q, $q['request']['api_key']) . "' "
        . "";

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'auth');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $version_file = $q['config']['qruqsp.core']['root_dir'] . "/_versions.ini";
    if( is_file($version_file) ) {
        $version_info = parse_ini_file($version_file, true);
        $version = $version_info['package']['version'];
    } else {
        $version = '';
    }
    if( $rc['num_rows'] != 1 ) {
        return array('stat'=>'fail', 'version'=>$version, 'err'=>array('code'=>'qruqsp.core.35', 'msg'=>'Session expired'));
    }
    $auth = array('token'=>$rc['auth']['auth_token'], 'id'=>$rc['auth']['user_id']);

    //
    // Check expiry
    //
    if( $rc['auth']['session_length'] > $rc['auth']['timeout'] ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'sessionEnd');
        qruqsp_core_sessionEnd($q);
//      $version_file = $q['config']['qruqsp.core']['root_dir'] . "/_versions.ini";
//      if( is_file($version_file) ) {
//          $version_info = parse_ini_file($version_file, true);
//          $version = $version_info['package']['version'];
//      } else {
//          $version = '';
//      }
        return array('stat'=>'fail', 'version'=>$version, 'err'=>array('code'=>'qruqsp.core.36', 'msg'=>'Session expired'));
    }

    //
    // Unserialize the session data
    //
    $q['session'] = unserialize($rc['auth']['session_data']);
    
    //
    // Check session variables for security.  If the values in the session
    // do not match the values passed from the client, then it could be a potential
    // security problem.
    // Reset the session variable before returning.
    //
    if( $q['session']['api_key'] != $q['request']['api_key'] ) {
        $q['session'] = array();
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.37', 'msg'=>'Access Denied', 'pmsg'=>'Security Problem: Request and session api_key do not match, possible security problem.'));
    } 
    elseif( $q['session']['auth_token'] != $q['request']['auth_token'] ) {
        $q['session'] = array();
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.38', 'msg'=>'Access Denied', 'pmsg'=>'Security Problem: Request and session auth_token do not match, possible security problem.'));
    } 
    elseif( $q['session']['user']['id'] != $rc['auth']['user_id'] ) {
        $q['session'] = array();
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.39', 'msg'=>'Access Denied', 'pmsg'=>'Security Problem: The user_id in the session data does not match the user_id assigned to session in the database.'));
    }

    $auth['perms'] = $q['session']['user']['perms'];
    $auth['avatar_id'] = $q['session']['user']['avatar_id'];

    //
    // Update session time, so timeout occurs from last action
    //

    //
    // If we get to this point, then the session was loaded successfully
    // and verified.
    //
    return array('stat'=>'ok', 'version'=>$version, 'auth'=>$auth); 
}
?>
