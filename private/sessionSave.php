<?php
//
// Description
// -----------
// This function will save an existing session.
//
// Arguments
// ---------
// qruqsp:
//
function qruqsp_core_sessionSave($q) {

    if( !isset($q['session']) || !is_array($q['session']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.40', 'msg'=>'Internal configuration error', 'pmsg'=>'$q[session] not set'));
    }

    //
    // Only save sessions which have all three specified
    //
    if( !isset($q['session']['api_key']) || $q['session']['api_key'] == '' 
        || !isset($q['session']['auth_token']) || $q['session']['auth_token'] == '' 
        || !isset($q['session']['user']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.41', 'msg'=>'Internal configuration error', 'pmsg'=>'Required session variables not set.'));
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
    // Don't check for timeout here, we want to be able to have session saved,
    // even if over the timeout, because the session was opened before the timeout.
    // Sessions are only open as long as it takes to run a method.
    // 
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    $strsql = "UPDATE qruqsp_core_session_data SET "
        . "session_data = '" . qruqsp_core_dbQuote($q, serialize($q['session'])) . "' "
        . ", last_saved = UTC_TIMESTAMP() "
        . "WHERE auth_token = '" . qruqsp_core_dbQuote($q, $q['session']['auth_token']) . "' "
        . "AND api_key = '" . qruqsp_core_dbQuote($q, $q['session']['api_key']) . "' "
        . "AND user_id = '" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "' "
        . "";

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
