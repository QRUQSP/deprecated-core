<?php
//
// Description
// -----------
// This method will lock a user account, resetting the login_attempts to 0.
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:             The ID of the user to lock the account for.
//
function qruqsp_core_userLock($q) {
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
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.userLock');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuoteRequestArg');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');
    $strsql = "UPDATE qruqsp_core_users "
        . "SET status = 10, login_attempts = 0 "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "'";
    $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', 0, 2, 'qruqsp_core_users', $args['user_id'], 'status', '10');

    return $rc;
}
?>
