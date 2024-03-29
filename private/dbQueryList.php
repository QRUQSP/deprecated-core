<?php
//
// Description
// -----------
// This function will return a simple array.  Only one column will be returned.
//
// Arguments
// ---------
// qruqsp:          The qruqsp data structure.
// strsql:          The SQL string to query the database.
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
// container_name:  The name of the xml/hash tag to return the data under, 
//                  when there is only one row returned.
// colname:         The column from the query to put in the list
//
function qruqsp_core_dbQueryList(&$q, $strsql, $module, $container_name, $colname) {
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
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        error_log("SQLERR: " . mysqli_error($dh) . " -- '$strsql'");
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.97', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // Check if any rows returned from the query
    //
    $rsp = array('stat'=>'ok');
    $rsp['num_rows'] = 0;

    //
    // Build array of rows
    //
    $rsp[$container_name] = array();
    while( $row = mysqli_fetch_assoc($result) ) {
        array_push($rsp[$container_name], $row[$colname]);
        $rsp['num_rows']++;
    }

    return $rsp;
}
?>
