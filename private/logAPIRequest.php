<?php
//
// Description
// -----------
// This function will add a request to the api logs.
//
function qruqsp_core_logAPIRequest($q) {
    //
    // Log a API request 
    //
    $strsql = "INSERT INTO qruqsp_core_api_logs (uuid, user_id, station_id, session_key, method, action, ip_address, "
        . "log_date ) VALUES (uuid(), "
        . "";
    if( isset($q['session']['user']['id']) ) {
        $strsql .= "'" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "', ";
    } else {
        $strsql .= "0, ";
    }
    if( isset($q['request']['args']['station_id']) ) {
        $strsql .= "'" . qruqsp_core_dbQuote($q, $q['request']['args']['station_id']) . "', ";
    } else {
        $strsql .= "0, ";
    }
    if( isset($q['session']['change_log_id']) ) {
        $strsql .= "'" . qruqsp_core_dbQuote($q, $q['session']['change_log_id']) . "', ";
    } else {
        $strsql .= "'', ";
    }
    $strsql .= "'" . qruqsp_core_dbQuote($q, $q['request']['method']) . "', "
        . "";
    if( isset($q['request']['action']) ) {
        $strsql .= "'" . qruqsp_core_dbQuote($q, $q['request']['action']) . "', ";
    } else {
        $strsql .= "'', ";
    }
    if( isset($_SERVER['REMOTE_ADDR']) ) {
        $strsql .= "'" . qruqsp_core_dbQuote($q, $_SERVER['REMOTE_ADDR']) . "', ";
    } else {
        $strsql .= "'localhost', ";
    }
    $strsql .= "UTC_TIMESTAMP())";

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
    return qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
}
?>
