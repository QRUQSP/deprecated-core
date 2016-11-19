<?php
//
// Description
// -----------
// This function is optimized to retrieve detail information,
// in the form of key=value for a module.
//
// Arguments
// ---------
// qruqsp:          
//
function qruqsp_core_dbDetailsQueryDash(&$q, $table, $key, $key_value, $module, $container_name, $detail_key) {
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
    // Prepare and Execute Query
    //
    $strsql = "SELECT detail_key, detail_value FROM " . qruqsp_core_dbQuote($q, $table) . " "
        . "WHERE " . qruqsp_core_dbQuote($q, $key) . " = '" . qruqsp_core_dbQuote($q, $key_value) . "' ";
    if( $detail_key != '' ) {
        $strsql .= " AND detail_key like '" . qruqsp_core_dbQuote($q, $detail_key) . "-%'";
    }
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        error_log("sql error: " . mysqli_error($dh));
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.136', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // Check if any rows returned from the query
    //
    $rsp = array('stat'=>'ok', $container_name=>array());

    //
    // Build array of rows
    //
    while( $row = mysqli_fetch_row($result) ) {
        $rsp[$container_name][$row[0]] = $row[1];
    }

    mysqli_free_result($result);

    return $rsp;
}
?>
