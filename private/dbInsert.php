<?php
//
// Description
// -----------
// This function will run an insert query against the database. 
//
// Arguments
// ---------
// qruqsp:
// strsql:          The SQL statement to execute which will INSERT a row into the database.
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
//
function qruqsp_core_dbInsert(&$q, $strsql, $module) {
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
        //
        // Error a different code if a duplicate key problem
        //
        if( mysqli_errno($dh) == 1062 || mysqli_errno($dh) == 1022 ) {
            return array('stat'=>'exists', 'err'=>array('code'=>'qruqsp.core.15', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh), 'dberrno'=>mysqli_errno($dh), 'sql'=>$strsql));
        } else {
            error_log("SQLERR: " . mysqli_error($dh) . " -- '$strsql'");
        }
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.16', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh), 'dberrno'=>mysqli_errno($dh), 'sql'=>$strsql));
    }

    //
    // Check if any rows returned from the query
    //
    $rsp = array('stat'=>'ok');
    $rsp['num_affected_rows'] = mysqli_affected_rows($dh);
    $rsp['insert_id'] = mysqli_insert_id($dh);

    unset($rc);

    return $rsp;
}
?>
