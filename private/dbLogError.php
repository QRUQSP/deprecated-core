<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
function qruqsp_core_dbLogError(&$q, $err) {

    //
    // Don't log if password passed
    //
    if( isset($q['request']['method']) 
        && (
            $q['request']['method'] == 'qruqsp.users.auth' 
            || $q['request']['method'] == 'qruqsp.users.setPassword' 
            || $q['request']['method'] == 'qruqsp.users.resetPassword' 
            || $q['request']['method'] == 'qruqsp.users.changePassword' 
            || $q['request']['method'] == 'qruqsp.users.changeTempPassword' 
            )
        ) { 
        return array('stat'=>'ok');
    }

    //
    // Don't log if session expired error
    //
    $ignore_err_codes = array(5, 27, 37);
    if( isset($err['code']) && in_array($err['code'], $ignore_err_codes) ) {
        return array('stat'=>'ok');
    }

    $station_id = 0;
    if( isset($q['request']['args']['station_id']) ) {
        $station_id = $q['request']['args']['station_id'];
    }
    $strsql = "INSERT INTO qruqsp_core_error_logs ("
        . "status, station_id, user_id, "
        . "session_key, method, "
        . "request_array, session_array, err_array, "
        . "log_date) VALUES ("
        . "10, "
        . "'" . qruqsp_core_dbQuote($q, $station_id) . "', "
        . "'" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "', "
        . "'" . qruqsp_core_dbQuote($q, $q['session']['change_log_id']) . "', "
        . "'" . qruqsp_core_dbQuote($q, $q['request']['method']) . "', "
        . "'" . qruqsp_core_dbQuote($q, serialize($q['request'])) . "', "
        . "'" . qruqsp_core_dbQuote($q, serialize($q['session'])) . "', "
        . "'" . qruqsp_core_dbQuote($q, serialize($err)) . "', "
        . "UTC_TIMESTAMP()"
        . ")";

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
    return qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
}
?>
