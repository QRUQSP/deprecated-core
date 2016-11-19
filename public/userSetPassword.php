<?php
//
// Description
// -----------
// This method will set a users password.  Currently only accessible to sysadmins.
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:         The user to have the password set.
// password:        The new password to set for the user.
//
function qruqsp_core_userSetPassword($q) {
    
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        'password'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Password'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.userSetPassword');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Turn off autocommit
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionStart');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionRollback');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionCommit');
    $rc = qruqsp_core_dbTransactionStart($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the password, but only if the old one matches
    //
    $strsql = "UPDATE qruqsp_core_users "
        . "SET password = SHA1('" . qruqsp_core_dbQuote($q, $args['password']) . "') "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' "
        . "";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.115', 'msg'=>'Unable to set password.'));
    }
    
    error_log(print_r($rc, true));
    if( $rc['num_affected_rows'] < 1 ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.116', 'msg'=>'Unable to set password.'));
    }

    //
    // FIXME: Add log entry to track password changes
    //

    //
    // Commit the changes and return
    //
    $rc = qruqsp_core_dbTransactionCommit($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.117', 'msg'=>'Unable to set password.'));
    }

    return array('stat'=>'ok');
}
?>
