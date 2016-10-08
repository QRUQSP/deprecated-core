<?php
//
// Description
// -----------
// This function will fetch the list of changes for a field from the qruqsp_core_change_logs, and
// reformat the output for the specified format.
//
// Arguments
// ---------
// qruqsp:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
//
function qruqsp_core_dbGetModuleHistoryReformat(&$q, $module, $history_table, $station_id, $table_name, $table_key, $table_field, $format) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
    //
    $rc = qruqsp_core_dbConnect($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $dh = $rc['dh'];

    //
    // Get the history log from qruqsp_core_change_logs table.
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'datetimeFormat');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dateFormat');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'timeFormat');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbParseAge');

    //
    // Load station intl settings
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'intlSettings');
    $rc = qruqsp_core_intlSettings($q, $station_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];
    $date_format = qruqsp_core_dateFormat($q);
    $time_format = qruqsp_core_timeFormat($q);
    $php_date_format = qruqsp_core_dateFormat($q, 'php');
    $php_time_format = qruqsp_core_timeFormat($q, 'php');
    $php_datetime_format = qruqsp_core_datetimeFormat($q, 'php');

    $strsql = "SELECT user_id, "
        . "log_date as date, "
        . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) AS age, "
        . "new_value as value ";
    if( $format == 'date' ) {
        $strsql .= ", DATE_FORMAT(new_value, '" . qruqsp_core_dbQuote($q, $date_format) . "') AS formatted_value ";
    } elseif( $format == 'time' ) {
        $strsql .= ", TIME_FORMAT(new_value, '" . qruqsp_core_dbQuote($q, $time_format) . "') AS formatted_value ";
    } elseif( $format == 'datetime' ) {
        $strsql .= ", DATE_FORMAT(new_value, '" . qruqsp_core_dbQuote($q, $datetime_format) . "') AS formatted_value ";
    }
    $strsql .= " FROM $history_table "
        . " WHERE station_id ='" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . " AND table_name = '" . qruqsp_core_dbQuote($q, $table_name) . "' "
        . " AND table_key = '" . qruqsp_core_dbQuote($q, $table_key) . "' "
        . " AND table_field = '" . qruqsp_core_dbQuote($q, $table_field) . "' "
        . " ORDER BY log_date DESC "
        . "";
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.113', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // Check if any rows returned from the query
    //
    if( mysqli_num_rows($result) <= 0 ) {
        return array('stat'=>'ok', 'history'=>array(), 'users'=>array());
    }

    $rsp = array('stat'=>'ok', 'history'=>array(), 'users'=>array());
    $user_ids = array();
    $num_history = 0;
    while( $row = mysqli_fetch_assoc($result) ) {
        $rsp['history'][$num_history] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 'value'=>$row['value']));
        if( $format == 'utcdate' && $row['value'] != '' ) {
            $date = new DateTime($row['value'], new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone($intl_timezone));
            $rsp['history'][$num_history]['action']['value'] = $date->format($php_date_format);
        }
        elseif( $format == 'utctime' && $row['value'] != '' ) {
            $date = new DateTime($row['value'], new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone($intl_timezone));
            $rsp['history'][$num_history]['action']['value'] = $date->format($php_time_format);
        }
        elseif( $format == 'utcdatetime' && $row['value'] != '' ) {
            $date = new DateTime($row['value'], new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone($intl_timezone));
            $rsp['history'][$num_history]['action']['value'] = $date->format($php_datetime_format);
        }
        elseif( $format == 'date' || $format == 'time' || $format == 'datetime' ) {
            $rsp['history'][$num_history]['action']['formatted_value'] = $row['formatted_value'];
        }
        elseif( $format == 'currency' ) {
            if( isset($row['value']) == '' ) {
                $row['value'] = 0.00;
            }
            if( isset($row['value'][0]) && $row['value'][0] == '$' ) {
                $row['value'] = substr($row['value'], 1, strlen($row['value']));
            }
            $rsp['history'][$num_history]['action']['value'] = numfmt_format_currency(
                $intl_currency_fmt, $row['value'], $intl_currency);
        }
        // Format the date
        $date = new DateTime($row['date'], new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($intl_timezone));
        $rsp['history'][$num_history]['action']['date'] = $date->format($php_datetime_format);

//      if( $row['user_id'] > 0 ) {
            array_push($user_ids, $row['user_id']);
//      }
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
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.114', 'msg'=>'Unable to merge user information', 'err'=>$rc['err']));
    }
    $users = $rc['users'];

    //
    // Merge user list information into array
    //
    foreach($rsp['history'] as $k => $v) {
        if( isset($v['action']) && isset($v['action']['user_id']) //&& $v['action']['user_id'] > 0 
            && isset($users[$v['action']['user_id']]) && isset($users[$v['action']['user_id']]['display_name']) ) {
            $rsp['history'][$k]['action']['user_display_name'] = $users[$v['action']['user_id']]['display_name'];
        }
    }

    return $rsp;
}
?>
