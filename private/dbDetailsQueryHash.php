<?php
//
// Description
// -----------
// This function is optimized to retrieve detail information,
// from any of the table_details tables, and return it in
// a structured hash form.  This is useful for returning
// as XML through the API, or used internally.
//
// Arguments
// ---------
// qruqsp:          
// strsql:          The SQL string to query the database.
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
//
function qruqsp_core_dbDetailsQueryHash(&$q, $table, $key, $key_value, $detail_key, $module) {
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
        $strsql .= " AND detail_key LIKE '" . qruqsp_core_dbQuote($q, $detail_key) . ".%'";
    }
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.96', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // Check if any rows returned from the query
    //
    $rsp = array('stat'=>'ok', 'details'=>array());

    //
    // Build array of rows
    //
    while( $row = mysqli_fetch_row($result) ) {
        $split_key = preg_split('/\-/', $row[0]);
        $cur_key = &$rsp['details'];
        for($i=0;$i<count($split_key)-1;$i++) {
            if( !isset($cur_key[$split_key[$i]]) ) {
                $cur_key[$split_key[$i]] = array();
            }
            $cur_key = &$cur_key[$split_key[$i]];
        }
        $cur_key[$split_key[$i]] = $row[1];
    }

    mysqli_free_result($result);
    
    return $rsp;
}
?>
