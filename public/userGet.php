<?php
//
// Description
// -----------
// This method will get the details for a user.
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:             The ID of the user to get the details for.
//
function qruqsp_core_userGet($q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access, should only be accessible by sysadmin
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.userGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDetailsQuery');

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'datetimeFormat');
    $datetime_format = qruqsp_core_datetimeFormat($q);

    //
    // Get all the information form qruqsp_core table
    //
    $strsql = "SELECT id, avatar_id, email, username, perms, status, timeout, "
        . "callsign, license, display_name, login_attempts, "
        . "DATE_FORMAT(date_added, '" . qruqsp_core_dbQuote($q, $datetime_format) . "') AS date_added, "
        . "DATE_FORMAT(last_updated, '" . qruqsp_core_dbQuote($q, $datetime_format) . "') AS last_updated, "
        . "DATE_FORMAT(last_login, '" . qruqsp_core_dbQuote($q, $datetime_format) . "') AS last_login, "
        . "DATE_FORMAT(last_pwd_change, '" . qruqsp_core_dbQuote($q, $datetime_format) . "') AS last_pwd_change "
        . "FROM qruqsp_core_users "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' "
        . "";
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'user');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['user']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.98', 'msg'=>'Unable to find user'));
    }
    $user = $rc['user'];

    //
    // Get all the stations the user is a part of
    //
    $strsql = "SELECT qruqsp_core_stations.id, "
        . "qruqsp_core_stations.name "
        . "FROM qruqsp_core_station_users, qruqsp_core_stations "
        . "WHERE qruqsp_core_station_users.user_id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' "
        . "AND qruqsp_core_station_users.station_id = qruqsp_core_stations.id "
        . "AND qruqsp_core_station_users.status = 10 "
        . "";
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'station');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $user['stations'] = $rc['rows'];

    //
    // Get all the settings for the user
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDetailsQueryDash');
    $rc = qruqsp_core_dbDetailsQueryDash($q, 'qruqsp_core_user_details', 'user_id', $args['user_id'], 'qruqsp.core', 'details', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($rc['details']) ) {
        $user['details'] = $rc['details'];
    }

    return array('stat'=>'ok', 'user'=>$user);
}
?>
