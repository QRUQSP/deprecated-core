<?php
//
// Description
// -----------
// This method will clear all session data for a user, logging them out of the system.
//
// Arguments
// ---------
//
// Example Return
// --------------
//
function qruqsp_core_logout($q) {
    //
    // Check access 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.logout');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'sessionEnd');

    $rc = qruqsp_core_sessionEnd($q);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($q['request']['args']['user_selector']) && $q['request']['args']['user_selector'] != '' 
        && isset($q['request']['args']['user_token']) && $q['request']['args']['user_token'] != '' 
        ) {
        $strsql = "DELETE FROM qruqsp_core_user_tokens "
            . "WHERE selector = '" . qruqsp_core_dbQuote($q, $q['request']['args']['user_selector']) . "' "
            . "";
        $rc = qruqsp_core_dbDelete($q, $strsql, 'qruqsp.core');
        if( $rc['stat'] == 'ok' && $rc['num_affected_rows'] == 1 ) {
            // FIXME: Add code to track number of active sessions in users table, limit to X sessions.
        }
    }

    return array('stat'=>'ok');
}
?>
