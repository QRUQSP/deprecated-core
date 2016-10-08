<?php
//
// Description
// -----------
// This will start a new session base on a user token
//
// Arguments
// ---------
// qruqsp:
// username:        The username to authenticate with the password.
// password:        The password submitted to be used for authentication.
//
function qruqsp_core_sessionTokenStart(&$q, $selector, $token) {

    //
    // End any currently active sessions
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'sessionEnd');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'logAuthFailure');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'logAuthSuccess');
    qruqsp_core_sessionEnd($q);

    //
    // Verify api_key is specified
    //
    if( !isset($q['request']['api_key']) || $q['request']['api_key'] == '' ) {
        qruqsp_core_logAuthFailure($q, $selector, 49);
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.49', 'msg'=>'No api_key specified'));
    }

    //
    // Check username and password were passed to function
    //
    if( $token == '' ) {
        qruqsp_core_logAuthFailure($q, $selector, 55);
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.55', 'msg'=>'Invalid token'));
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');

    //
    // Check the token in the database
    //
    $strsql = "SELECT qruqsp_core_users.id, qruqsp_core_users.email, qruqsp_core_users.username, qruqsp_core_users.avatar_id, "
        . "qruqsp_core_users.perms, qruqsp_core_users.status, qruqsp_core_users.timeout, qruqsp_core_users.login_attempts, "
        . "qruqsp_core_users.display_name "
        . "FROM qruqsp_core_user_tokens, qruqsp_core_users "
        . "WHERE qruqsp_core_user_tokens.selector = '" . qruqsp_core_dbQuote($q, $selector) . "' "
        . "AND qruqsp_core_user_tokens.token = '" . qruqsp_core_dbQuote($q, hash('sha256', $token)) . "' "
        . "AND qruqsp_core_user_tokens.user_id = qruqsp_core_users.id "
        . "";
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'user');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_logAuthFailure($q, $selector, $rc['err']['code']);
        return $rc;
    }

    //
    // Perform an extra check to make sure only 1 row was found, other return error
    //
    if( $rc['num_rows'] != 1 ) {
        qruqsp_core_logAuthFailure($q, $selector, 50);
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.50', 'msg'=>'Invalid password'));
    }

    if( !isset($rc['user']) ) {
        qruqsp_core_logAuthFailure($q, $selector, 51);
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.51', 'msg'=>'Invalid password'));
    }
    if( $rc['user']['id'] <= 0 ) {
        qruqsp_core_logAuthFailure($q, $selector, 52);
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.52', 'msg'=>'Invalid password'));
    }
    $user = $rc['user'];

    // Check if the account should be locked
    if( $user['login_attempts'] > 7 && $user['status'] < 10 ) {
        $strsql = "UPDATE qruqsp_core_users SET status = 10 WHERE status = 1 AND id = '" . qruqsp_core_dbQuote($q, $rc['user']['id']) . "'";
        qruqsp_core_alertGenerate($q, 
            array('alert'=>'2', 'msg'=>'The account ' . $rc['user']['email'] . ' was locked.'));
        qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
        $user['status'] = 10;
    }
    // Check if the account is locked
    if( $user['status'] == 10 ) {
        qruqsp_core_logAuthFailure($q, $selector, 53);
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.53', 'msg'=>'Account locked'));
    }
    
    // Check if the account is deleted
    if( $user['status'] == 11 ) {
        qruqsp_core_logAuthFailure($q, $selector, 54);
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.54', 'msg'=>'Invalid password'));
    }

    // Check if the account is active
    if( $user['status'] < 1 || $user['status'] > 2 ) {
        qruqsp_core_logAuthFailure($q, $selector, 56);
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.56', 'msg'=>'Invalid password'));
    }

    unset($user['login_attempts']);

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDetailsQueryDash');
    // $rc = qruqsp_core_dbDetailsQueryHash($q, 'qruqsp_core_user_details', 'user_id', $user['id'], 'settings', 'qruqsp.core');
    $rc = qruqsp_core_dbDetailsQueryDash($q, 'qruqsp_core_user_details', 'user_id', $user['id'], 'qruqsp.core', 'settings', 'settings');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_logAuthFailure($q, $selector, $rc['err']['code']);
        return $rc;
    }
    error_log(print_r($rc, true));
    if( isset($rc['details']['settings']) && $rc['details']['settings'] != null ) {
        $user['settings'] = $rc['details']['settings'];
    }
    
    //
    // Default session timeout to 60 seconds, unless another is specified
    //
    $session_timeout = 60;
    if( isset($user['timeout']) && $user['timeout'] > 0 ) {
        $session_timeout = $user['timeout'];
    } elseif( isset($q['config']['core']['session_timeout']) && $q['config']['core']['session_timeout'] > 0 ) {
        $session_timeout = $q['config']['core']['session_timeout'];
    }
    
    //
    // Initialize the session variable within the qruqsp data structure
    //
    $q['session'] = array('init'=>'yes', 'api_key'=>$q['request']['api_key'], 'user'=>$user);
    
    //
    // Generate a random 32 character string as the session id.
    // FIXME: Check to make sure this is a secure enough method for generating a session id.
    // 
    date_default_timezone_set('UTC');
    $q['session']['auth_token'] = md5(date('Y-m-d-H-i-s') . rand());
  
    $q['session']['change_log_id'] = date('ymd.His') . '.' . substr($q['session']['auth_token'], 0, 6);

    //
    // Serialize the data for storage
    //
    $serialized_session_data = serialize($q['session']);

    $strsql = "INSERT INTO qruqsp_core_session_data "
        . "(auth_token, api_key, user_id, date_added, timeout, last_saved, session_key, session_data) "
        . " VALUES "
        . "('" . qruqsp_core_dbQuote($q, $q['session']['auth_token']) . "' "
        . ", '" . qruqsp_core_dbQuote($q, $q['session']['api_key']) . "' "
        . ", '" . qruqsp_core_dbQuote($q, $user['id']) . "' "
        . ", UTC_TIMESTAMP(), " . qruqsp_core_dbQuote($q, $session_timeout)
        . ", UTC_TIMESTAMP(), "
        . "'" . qruqsp_core_dbQuote($q, $q['session']['change_log_id']) . "', "
        . "'" . qruqsp_core_dbQuote($q, $serialized_session_data) . "')";

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
    $rc = qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_logAuthFailure($q, $selector, $rc['err']['code']);
        return $rc;
    }

    //
    // Update the last_login field for the user, and reset the login_attempts field.
    //
    $strsql = "UPDATE qruqsp_core_users SET login_attempts = 0, last_login = UTC_TIMESTAMP() WHERE id = '" . qruqsp_core_dbQuote($q, $user['id']) . "'";
    $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_logAuthFailure($q, $selector, $rc['err']['code']);
        return $rc;
    }

    //
    // Update the last_auth field for the user token
    //
    $strsql = "UPDATE qruqsp_core_user_tokens SET last_auth = UTC_TIMESTAMP() "
        . "WHERE user_id = '" . qruqsp_core_dbQuote($q, $user['id']) . "'"
        . "AND qruqsp_core_user_tokens.selector = '" . qruqsp_core_dbQuote($q, $selector) . "' "
        . "AND qruqsp_core_user_tokens.token = '" . qruqsp_core_dbQuote($q, hash('sha256', $token)) . "' "
        . "";
    $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_logAuthFailure($q, $selector, $rc['err']['code']);
        return $rc;
    }

    //
    // FIXME: Check for primary key violation, and choose new key
    //
    
    qruqsp_core_logAuthSuccess($q);

    $version_file = $q['config']['qruqsp.core']['root_dir'] . "/_versions.ini";
    if( is_file($version_file) ) {
        $version_info = parse_ini_file($version_file, true);
        $version = $version_info['package']['version'];
    } else {
        $version = '';
    }

    return array('stat'=>'ok', 'version'=>$version, 'auth'=>array('token'=>$q['session']['auth_token'], 'id'=>$user['id'], 'perms'=>$user['perms'], 'avatar_id'=>$user['avatar_id']));
}
?>
