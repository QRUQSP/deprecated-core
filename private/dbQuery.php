<?php
//
// Description
// -----------
// This function will query the database and return a hash of rows.
//
// Arguments
// ---------
// qruqsp:          The qruqsp data structure.
// strsql:          The SQL string to query the database.
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
//
function qruqsp_core_dbQuery(&$q, $strsql, $module) {
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
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.88', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    return array('stat'=>'ok', 'handle'=>$result);
}
?>
