<?php
//
// Description
// ===========
// This method will check the api_key in the arguments exists and is an active key.
//
// Arguments
// =========
// api_key:         
//
function qruqsp_core_checkAPIKey($q) {
    //
    // Required functions
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');

    if( !isset($q['request']['api_key']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.26', 'msg'=>'Internal Error', 'pmsg'=>"qruqsp_core_checkAPIKey called before qruqsp_core_init."));
    }

    $strsql = "SELECT api_key, status "
        . "FROM qruqsp_core_api_keys "
        . "WHERE api_key = '" . qruqsp_core_dbQuote($q, $q['request']['api_key']) . "' "
        . "AND status = 1 "
        . "AND (expiry_date = 0 OR UTC_TIMESTAMP() < expiry_date) "
        . "";
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'api_key');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    return $rc;
}
?>
