<?php
//
// Description
// -----------
// This method echos back the arguments sent.  This function is
// for simple testing, similar to ping in network tests.
//
// Arguments
// ---------
// api_key:         
//
// Returns
// -------
//
function qruqsp_core_echoTest($q) {
    //
    // Check access restrictions to checkAPIKey
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.echoTest');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok', 'request'=>$q['request']);
}
?>
