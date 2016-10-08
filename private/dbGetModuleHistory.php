<?php
//
// Description
// -----------
// This method retrieves the history elements for a module field.  The users display_name is 
// attached to each record as user_display_name.
//
// Arguments
// ---------
// qruqsp:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
//
//
function qruqsp_core_dbGetModuleHistory(&$qruqsp, $module, $history_table, $station_id, $table_name, $table_key, $table_field) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
    //
    $rc = qruqsp_core_dbConnect($qruqsp, $module);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $dh = $rc['dh'];

    //
    // Get the time information for station and user
    //
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'intlSettings');
    $rc = qruqsp_core_intlSettings($qruqsp, $station_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    date_default_timezone_set($intl_timezone);

    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'datetimeFormat');
    $datetime_format = qruqsp_core_datetimeFormat($qruqsp, 'php');

    //
    // Get the history log from qruqsp_core_change_logs table.
    //
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbQuoteList');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbParseAge');

    $strsql = "SELECT user_id, "
        . "log_date as date, "
        . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age, "
        . "action, "
        . "table_key, "
        . "new_value as value "
        . " FROM " . qruqsp_core_dbQuote($qruqsp, $history_table) . " "
        . " WHERE station_id ='" . qruqsp_core_dbQuote($qruqsp, $station_id) . "' "
        . " AND table_name = '" . qruqsp_core_dbQuote($qruqsp, $table_name) . "' ";
    if( is_array($table_key) ) {
        $strsql .= " AND table_key IN (" . qruqsp_core_dbQuoteList($qruqsp, $table_key) . ") ";
    } else {
        $strsql .= " AND table_key = '" . qruqsp_core_dbQuote($qruqsp, $table_key) . "' ";
    }
    $strsql .= " AND table_field = '" . qruqsp_core_dbQuote($qruqsp, $table_field) . "' "
        . " ORDER BY log_date DESC "
        . "";
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.107', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // Check if any rows returned from the query
    //
    if( mysqli_num_rows($result) <= 0 ) {
        return array('stat'=>'ok', 'history'=>array());
    }

    $rsp = array('stat'=>'ok', 'history'=>array());
    $user_ids = array();
    $num_history = 0;
    while( $row = mysqli_fetch_assoc($result) ) {
        $rsp['history'][$num_history] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 'value'=>$row['value']));
        if( is_array($table_key) ) {
            $rsp['history'][$num_history]['action']['key'] = $row['table_key'];
        }
        // Format the date
        $date = new DateTime($row['date'], new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($intl_timezone));
        $rsp['history'][$num_history]['action']['date'] = $date->format($datetime_format);

        if( $row['user_id'] != 0 ) {
            array_push($user_ids, $row['user_id']);
        }
        $rsp['history'][$num_history]['action']['age'] = qruqsp_core_dbParseAge($qruqsp, $row['age']);
        $num_history++;
    }

    mysqli_free_result($result);

    //
    // If there was no history, or user ids, then skip the user lookup and return
    //
    if( $num_history < 1 || count($user_ids) < 1 ) {
        return $rsp;
    }

    //
    // Get the list of users
    //
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'userListByID');
    $rc = qruqsp_core_userListByID($qruqsp, 'users', array_unique($user_ids), 'display_name');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.108', 'msg'=>'Unable to merge user information', 'err'=>$rc['err']));
    }
    $users = $rc['users'];

    //
    // Merge user list information into array
    //
    foreach($rsp['history'] as $k => $v) {
        if( isset($v['action']) && isset($v['action']['user_id']) && $v['action']['user_id'] != 0 
            && isset($users[$v['action']['user_id']]) && isset($users[$v['action']['user_id']]['display_name']) ) {
            $rsp['history'][$k]['action']['user_display_name'] = $users[$v['action']['user_id']]['display_name'];
        } 
        if( isset($v['action']) && isset($v['action']['user_id']) && $v['action']['user_id'] == 0 ) {
            $rsp['history'][$k]['action']['user_display_name'] = 'unknown';
        }
    }

    return $rsp;
}
?>
