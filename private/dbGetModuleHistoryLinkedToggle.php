<?php
//
// Description
// -----------
// This function will get the history for an element that was in a linking table.  This was developed
// for getting the history from qruqsp_tax_type_rates.
//
// Arguments
// ---------
// qruqsp:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
//
//
function qruqsp_core_dbGetModuleHistoryLinkedToggle(&$q, $module, $history_table, $station_id, 
    $table_name, $table_fielda, $table_fielda_value, $table_fieldb, $table_fieldb_value) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
    //
    $rc = qruqsp_core_dbConnect($q, $module);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $dh = $rc['dh'];

    //
    // Get the history log from qruqsp_core_change_logs table.
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'datetimeFormat');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuoteList');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbParseAge');

    //
    // Get the list of table keys for fielda, or one side of the linking
    //
    $strsql = "SELECT DISTINCT table_key "
        . " FROM " . qruqsp_core_dbQuote($q, $history_table) . " "
        . " WHERE station_id ='" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . " AND table_name = '" . qruqsp_core_dbQuote($q, $table_name) . "' "
        . " AND table_field = '" . qruqsp_core_dbQuote($q, $table_fielda) . "' "
        . " AND new_value = '" . qruqsp_core_dbQuote($q, $table_fielda_value) . "' "
        . "";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQueryList');
    $rc = qruqsp_core_dbQueryList($q, $strsql, 'qruqsp.taxes', 'table_keys', 'table_key');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['table_keys']) || count($rc['table_keys']) < 1 ) {
        return array('stat'=>'ok', 'history'=>array());     
    }
    $fielda_keys = $rc['table_keys'];

    //
    // Get the list of table keys for fielda, or one side of the linking
    //
    $strsql = "SELECT DISTINCT table_key "
        . " FROM " . qruqsp_core_dbQuote($q, $history_table) . " "
        . " WHERE station_id ='" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . " AND table_name = '" . qruqsp_core_dbQuote($q, $table_name) . "' "
        . " AND table_field = '" . qruqsp_core_dbQuote($q, $table_fieldb) . "' "
        . " AND new_value = '" . qruqsp_core_dbQuote($q, $table_fieldb_value) . "' "
        . "";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQueryList');
    $rc = qruqsp_core_dbQueryList($q, $strsql, 'qruqsp.taxes', 'table_keys', 'table_key');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['table_keys']) || count($rc['table_keys']) < 1 ) {
        return array('stat'=>'ok', 'history'=>array());     
    }
    $fieldb_keys = $rc['table_keys'];

    $table_keys = array_intersect($fielda_keys, $fieldb_keys);

    //
    // Get all the entries, and return on or off
    //
    $date_format = qruqsp_core_datetimeFormat($q);
    $strsql = "SELECT user_id, "
        . "DATE_FORMAT(log_date, '" . qruqsp_core_dbQuote($q, $date_format) . "') as date, "
        . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age, "
        . "action, "
        . "table_key, "
        . "IF(table_field='*', 'no', 'yes') AS value "
        . " FROM " . qruqsp_core_dbQuote($q, $history_table) . " "
        . " WHERE station_id ='" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . " AND table_name = '" . qruqsp_core_dbQuote($q, $table_name) . "' "
        . " AND table_key IN (" . qruqsp_core_dbQuoteList($q, $table_keys) . ") "
        . " AND (table_field = '" . qruqsp_core_dbQuote($q, $table_fieldb) . "' OR table_field = '*') "
        . " ORDER BY log_date DESC "
        . "";
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.111', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
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
//      if( is_array($table_keys) ) {
            $rsp['history'][$num_history]['action']['key'] = $row['table_key'];
//      }
        if( $row['user_id'] != 0 ) {
            array_push($user_ids, $row['user_id']);
        }
        $rsp['history'][$num_history]['action']['age'] = qruqsp_core_dbParseAge($q, $row['age']);
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
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'userListByID');
    $rc = qruqsp_core_userListByID($q, 'users', array_unique($user_ids), 'display_name');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.112', 'msg'=>'Unable to merge user information', 'err'=>$rc['err']));
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
