<?php
//
// Description
// -----------
//
// Arguments
// ---------
// qruqsp:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
//
function qruqsp_core_dbGetModuleHistoryFkId(&$qruqsp, $module, $history_table, $station_id, $table_name, $table_key, $table_field, $fk_table, $fk_id_field, $fk_value_field) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
    //
    $rc = qruqsp_core_dbConnect($qruqsp, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $dh = $rc['dh'];

    //
    // Get the history log from qruqsp_core_change_logs table.
    //
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'datetimeFormat');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dateFormat');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbParseAge');

    $datetime_format = qruqsp_core_datetimeFormat($qruqsp);
    $date_format = qruqsp_core_dateFormat($qruqsp);
    $strsql = "SELECT user_id, "
        . "DATE_FORMAT(log_date, '" . qruqsp_core_dbQuote($qruqsp, $datetime_format) . "') as date, "
        . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age, "
        . "new_value as value, "
        . $fk_value_field . " AS fkidstr_value "
        . "FROM " . qruqsp_core_dbQuote($qruqsp, $history_table) . " "
        . "LEFT JOIN " . qruqsp_core_dbQuote($qruqsp, $fk_table) . " ON ("
            . "$history_table.new_value = " . qruqsp_core_dbQuote($qruqsp, $fk_table) . "." . qruqsp_core_dbQuote($qruqsp, $fk_id_field) . " "
            . " AND " . qruqsp_core_dbQuote($qruqsp, $fk_table) . ".station_id ='" . qruqsp_core_dbQuote($qruqsp, $station_id) . "' "
            . ") "
        . " WHERE $history_table.station_id ='" . qruqsp_core_dbQuote($qruqsp, $station_id) . "' "
        . " AND table_name = '" . qruqsp_core_dbQuote($qruqsp, $table_name) . "' "
        . " AND table_key = '" . qruqsp_core_dbQuote($qruqsp, $table_key) . "' "
        . " AND table_field = '" . qruqsp_core_dbQuote($qruqsp, $table_field) . "' "
        . " ORDER BY log_date DESC "
        . " ";
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.109', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // Check if any rows returned from the query
    //
    if( mysqli_num_rows($result) <= 0 ) {
        return array('stat'=>'ok', 'history'=>array(), 'users'=>array());
    }

    $rsp = array('stat'=>'ok', 'history'=>array());
    $user_ids = array();
    $num_history = 0;
    while( $row = mysqli_fetch_assoc($result) ) {
        $rsp['history'][$num_history] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 'value'=>$row['value']));
        $rsp['history'][$num_history]['action']['fkidstr_value'] = $row['fkidstr_value'];
        if( $row['user_id'] > 0 ) {
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
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.110', 'msg'=>'Unable to merge user information', 'err'=>$rc['err']));
    }
    $users = $rc['users'];

    //
    // Merge user list information into array
    //
    foreach($rsp['history'] as $k => $v) {
        if( isset($v['action']) && isset($v['action']['user_id']) && $v['action']['user_id'] > 0 
            && isset($users[$v['action']['user_id']]) && isset($users[$v['action']['user_id']]['display_name']) ) {
            $rsp['history'][$k]['action']['user_display_name'] = $users[$v['action']['user_id']]['display_name'];
        }
    }

    return $rsp;
}
?>
