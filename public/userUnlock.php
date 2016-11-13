<?php
//
// Description
// -----------
// This method will unlock a user account, resetting the login_attempts to 0, 
// and remote the lock flag.
//
// Only sysadmins are able to unlock a user.
//
// Info
// ----
// publish:         no
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:             The ID of the user to unlock the account for.
//
// Returns
// -------
// <rsp stat="ok" />
//
function qruqsp_core_userUnlock($q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.userUnlock');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuoteRequestArg');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');
    $strsql = "UPDATE qruqsp_core_users "
        . "SET status = 1, login_attempts = 0 "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "'";
    $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', 0, 2, 'qruqsp_core_users', $args['user_id'], 'status', '1');

    return $rc;
}
?>
