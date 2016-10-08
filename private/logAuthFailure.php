<?php
//
// Description
// -----------
// This function will log any failed attempts to authenticated through the API.
//
// Arguments
// ---------
// qruqsp:          
// username:            The username to log the failure against.
// err_code:            The authentication failure error code.
//
// Returns
// -------
//
function qruqsp_core_logAuthFailure($qruqsp, $username, $err_code) {

    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbInsert');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbUpdate');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbHashQuery');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'alertGenerate');

    //
    // Add the login attempt to the log table
    //
    $ip_address = 'unknown';
    if( isset($_SERVER['REMOTE_ADDR']) ) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }
    if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '' ) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    $strsql = "INSERT INTO qruqsp_core_auth_failures (username, api_key, ip_address, log_date, code"
        . ") VALUES ("
        . "'" . qruqsp_core_dbQuote($qruqsp, $username) . "', "
        . "'" . qruqsp_core_dbQuote($qruqsp, $qruqsp['request']['api_key']) . "', "
        . "'" . qruqsp_core_dbQuote($qruqsp, $ip_address) . "', "
        . "UTC_TIMESTAMP(), "
        . "'" . qruqsp_core_dbQuote($qruqsp, $err_code) . "')";
    
    qruqsp_core_dbInsert($qruqsp, $strsql, 'qruqsp.core');

    //
    // Update the login attempts if the username is valid.
    // 
    if( $username != '' ) {
        //
        // Check for that username and see if there is a match
        //
        if( preg_match('/\@/', $username) ) {
            $strsql = "SELECT id, email, username, callsign, status, login_attempts, date_added, last_login, last_pwd_change "
                . "FROM qruqsp_core_users "
                . "WHERE email = '" . qruqsp_core_dbQuote($qruqsp, $username) . "' ";
        } else {
            $strsql = "SELECT id, email, username, callsign, status, login_attempts, date_added, last_login, last_pwd_change "
                . "FROM qruqsp_core_users "
                . "WHERE username = '" . qruqsp_core_dbQuote($qruqsp, $username) . "' ";
        }

        $rc = qruqsp_core_dbHashQuery($qruqsp, $strsql, 'qruqsp.core', 'user');
        if( $rc['stat'] == 'ok' && isset($rc['user'])) {
            //
            // Check if account should be locked
            //
            if( $rc['user']['status'] < 10 && $rc['user']['login_attempts'] > 6 ) {
                $strsql = "UPDATE qruqsp_core_users SET status = 10 "
                    . "WHERE id = '" . qruqsp_core_dbQuote($qruqsp, $rc['user']['id']) . "' "
                    . "AND status < 10";
                qruqsp_core_dbUpdate($qruqsp, $strsql, 'qruqsp.core');
                qruqsp_core_alertGenerate($qruqsp, 
                    array('alert'=>'2', 'msg'=>"Account '$username' locked"), null);
            }

            $strsql = "UPDATE qruqsp_core_users SET login_attempts = login_attempts + 1 "
                . "WHERE username = '" . qruqsp_core_dbQuote($qruqsp, $username) . "' "
                . "AND login_attempts < 100 ";
            qruqsp_core_dbUpdate($qruqsp, $strsql, 'qruqsp.core');
        }
    }

    return array('stat'=>'ok');
}
?>
