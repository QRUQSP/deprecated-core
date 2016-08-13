<?php
//
// Description
// -----------
// This function will remove any sessions for a user_id.  If a sysadmin
// or other user is removed from the database, this function should
// be called to remove any open sessions for the deleted user.
//
// Arguments
// ---------
// qruqsp:
// user_id:         The user to end the session for.
//
function qruqsp_core_sessionEndUser($q, $user_id) {

    //
    // Remove the session from the database
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDelete');

    $strsql = "DELETE FROM qruqsp_core_session_data WHERE user_id = '" . qruqsp_core_dbQuote($q, $user_id) . "'";
    $rc = qruqsp_core_dbDelete($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
