<?php
//
// Description
// -----------
// This method retrieves the history elements for a module field, where the field type is tags.  The users display_name is 
// attached to each record as user_display_name.
//
// Arguments
// ---------
// qruqsp:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
//
//
function qruqsp_core_dbGetModuleHistoryTags(&$q, $module, $history_table, $station_id, $table_name, $table_key, $table_field, $table_id_field, $tag_type) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
    //

    //
    // Get the history log from qruqsp_core_change_logs table.
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'datetimeFormat');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuoteList');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQueryList');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbParseAge');

    //
    // Get the list of table_keys we're interested in
    // This query is broken into three stages for speed
    //
    $strsql = "SELECT DISTINCT table_key "
        . "FROM $history_table "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "AND table_field = '" . qruqsp_core_dbQuote($q, $table_id_field) . "' "
        . "AND new_value = '" . qruqsp_core_dbQuote($q, $table_key) . "' "
        . "";
    $rc = qruqsp_core_dbQueryList($q, $strsql, $module, 'keys', 'table_key');  
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['keys']) || count($rc['keys']) == 0 ) {
        return array('stat'=>'ok', 'history'=>array());
    }
    $keys = $rc['keys'];

    //
    // Now get the subset of keys which are of the proper type
    //
    $strsql = "SELECT DISTINCT table_key "
            . "FROM $history_table "
            . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
            . "AND table_field = 'tag_type' "
            . "AND new_value = '" . qruqsp_core_dbQuote($q, $tag_type) . "' "
            . "AND table_key IN (" . qruqsp_core_dbQuoteList($q, $keys) . ") "
            . "";
    $rc = qruqsp_core_dbQueryList($q, $strsql, $module, 'keys', 'table_key');  
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['keys']) ) {
        return array('stat'=>'ok', 'history'=>array());
    }
    $keys = $rc['keys'];

    //
    // Finally get the history
    //
    $date_format = qruqsp_core_datetimeFormat($q);
    $strsql = "SELECT user_id, "
        . "DATE_FORMAT(log_date, '" . qruqsp_core_dbQuote($q, $date_format) . "') as date, "
        . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age, "
        . "action, "
        . "table_key, "
        . "table_field, "
        . "qruqsp_core_users.display_name AS user_display_name, "
        . "new_value as value "
        . "FROM " . qruqsp_core_dbQuote($q, $history_table) . " "
        . "LEFT JOIN qruqsp_core_users ON ($history_table.user_id = qruqsp_core_users.id) "
        . "WHERE station_id ='" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "AND table_name = '" . qruqsp_core_dbQuote($q, $table_name) . "' "
        . "AND (table_field = 'tag_name' OR table_field = '*') "
        . "AND table_key IN (" . qruqsp_core_dbQuoteList($q, $keys) . ") "
        . "ORDER BY $history_table.log_date ASC "
        . "";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    $rc = qruqsp_core_dbHashQuery($q, $strsql, $module, 'row');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Build the history based on additions (action:1) and deletions(action:3)
    //
    $tags = array();
    $history = array();
    foreach($rc['rows'] as $row) {
        if( $row['table_field'] == 'tag_name' ) {
            $tags[$row['table_key']] = $row['value'];
            $history[] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 
                'action'=>'1', 'value'=>$row['value'], 
                'age'=>qruqsp_core_dbParseAge($q, $row['age']), 
                'user_display_name'=>$row['user_display_name']));
        } elseif( $row['table_field'] == '*' && isset($tags[$row['table_key']]) ) {
            $history[] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 
                'action'=>'3', 'value'=>$tags[$row['table_key']], 
                'age'=>qruqsp_core_dbParseAge($q, $row['age']), 
                'user_display_name'=>$row['user_display_name']));
        }
    }

    return array('stat'=>'ok', 'history'=>array_reverse($history));
}
?>
