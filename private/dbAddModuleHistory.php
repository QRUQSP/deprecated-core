<?php
//
// Description
// ===========
// This function will add a change log entry for a changed field. This will
// be entered in the qruqsp_core_change_logs table.
//
// Arguments
// =========
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
// user_id:         The user making the request
// station_id:
// table_name:      The table name that the data was inserted/replaced in.
// table_key:       The key to be able to get back to the row that was 
//                  changed in the table_name.
// table_field:     The field in the table_name that was updated.
// value:           The new value for the field.
//
function qruqsp_core_dbAddModuleHistory(&$q, $module, $history_table, $station_id, $action, $table_name, $table_key, $table_field, $value) {

    $strsql = "INSERT INTO " . qruqsp_core_dbQuote($q, $history_table) . " "
        . "(uuid, station_id, user_id, session, action, table_name, table_key, table_field, new_value, log_date) VALUES ("
        . "uuid(), "
        . "'" . qruqsp_core_dbQuote($q, $station_id) . "', "
        . "'" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "', "
        . "'" . qruqsp_core_dbQuote($q, $q['session']['change_log_id']) . "', "
        . "'" . qruqsp_core_dbQuote($q, $action) . "', "
        . "'" . qruqsp_core_dbQuote($q, $table_name) . "', "
        . "'" . qruqsp_core_dbQuote($q, $table_key) . "', "
        . "'" . qruqsp_core_dbQuote($q, $table_field) . "', "
        . "'" . qruqsp_core_dbQuote($q, $value) . "', "
        . "UTC_TIMESTAMP()"
        . ")";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
    return qruqsp_core_dbInsert($q, $strsql, $module);
}
?>
