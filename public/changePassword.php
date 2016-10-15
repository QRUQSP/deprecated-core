<?php
//
// Description
// -----------
// This method will change a users password.  The user must provide their old password
// as verification to change to a new password.
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// oldpassword:     The old password for the user.  Done as a security measure so,
//                  if somebody hijacks the session, they can't change the password.
//
// newpassword:     The new password for the user.
//
function qruqsp_core_changePassword($q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'oldpassword'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Old Password'), 
        'newpassword'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'New Password'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    if( strlen($args['newpassword']) < 8 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.100', 'msg'=>'New password must be longer than 8 characters.'));
    }

    //
    // Check access 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.changePassword');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check old password
    //
    $strsql = "SELECT id, email "
        . "FROM qruqsp_core_users "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "' "
        . "AND password = SHA1('" . qruqsp_core_dbQuote($q, $args['oldpassword']) . "') ";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'user');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Perform an extra check to make sure only 1 row was found, other return error
    //
    if( $rc['num_rows'] != 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.101', 'msg'=>'Invalid old password'));
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
    $strsql = "UPDATE qruqsp_core_users SET password = SHA1('" . qruqsp_core_dbQuote($q, $args['newpassword']) . "'), "
        . "last_updated = UTC_TIMESTAMP(), "
        . "last_pwd_change = UTC_TIMESTAMP() "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "' "
        . "AND password = SHA1('" . qruqsp_core_dbQuote($q, $args['oldpassword']) . "') ";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.102', 'msg'=>'Unable to update password.'));
    }

    if( $rc['num_affected_rows'] < 1 ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.103', 'msg'=>'Unable to change password.'));
    }

    $rc = qruqsp_core_dbTransactionCommit($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.104', 'msg'=>'Unable to update password.'));
    }

    return array('stat'=>'ok');
}
?>
