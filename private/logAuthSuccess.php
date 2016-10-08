<?php
//
// Description
// -----------
// This function will log all success authentications via the API.
//
// Info
// ----
// Status:      beta
//
// Arguments
// ---------
// 
// Returns
// -------
//
function qruqsp_core_logAuthSuccess($ciniki) {

    qruqsp_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

    $ip_address = 'unknown';
    if( isset($_SERVER['REMOTE_ADDR']) ) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip_address = 'localhost';
    }
    if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '' ) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    $strsql = "INSERT INTO qruqsp_core_auth_log (user_id, api_key, ip_address, log_date, session_key "
        . ") VALUES ("
        . "'" . qruqsp_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
        . "'" . qruqsp_core_dbQuote($ciniki, $ciniki['request']['api_key']) . "', "
        . "'" . qruqsp_core_dbQuote($ciniki, $ip_address) . "', "
        . "UTC_TIMESTAMP(), "
        . "'" . qruqsp_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "' "
        . ")";
    return qruqsp_core_dbInsert($ciniki, $strsql, 'ciniki.core');  
}
?>
