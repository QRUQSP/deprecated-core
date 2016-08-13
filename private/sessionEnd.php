<?php
//
// Description
// -----------
// This function will start a new session, destroying the old
// one if it exists.
//
// Arguments
// ---------
// qruqsp:
//
function qruqsp_core_sessionEnd($q) {

    //
    // Remove the session from the database
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDelete');

    if( isset($q['session']['auth_token']) && $q['session']['auth_token'] != '' ) {
        $strsql = "DELETE FROM qruqsp_core_session_data "
            . "WHERE auth_token = '" . qruqsp_core_dbQuote($q, $q['session']['auth_token']) . "' ";
        $rc = qruqsp_core_dbDelete($q, $strsql, 'qruqsp.core');
        if( $rc['stat'] == 'ok' && $rc['num_affected_rows'] == 1 ) {
            // FIXME: Add code to track number of active sessions in users table, limit to X sessions.
        }
    }

    elseif( isset($q['request']['auth_token']) && $q['request']['auth_token'] != '' ) {
        $strsql = "DELETE FROM qruqsp_core_session_data "
            . "WHERE auth_token = '" . qruqsp_core_dbQuote($q, $q['request']['auth_token']) . "' ";
        $rc = qruqsp_core_dbDelete($q, $strsql, 'qruqsp.core');
        if( $rc['stat'] == 'ok' && $rc['num_affected_rows'] == 1 ) {
            // FIXME: Add code to track number of active sessions in users table, limit to X sessions.
        }
    }

    //
    // Take the opportunity to clear old sessions, don't care about return code
    // FIXME: This maybe should be moved to a cronjob
    //
    $strsql = "DELETE FROM qruqsp_core_session_data WHERE UTC_TIMESTAMP()-TIMESTAMP(last_saved) > timeout";
    qruqsp_core_dbDelete($q, $strsql, 'qruqsp.core');

    return array('stat'=>'ok');
}
?>
