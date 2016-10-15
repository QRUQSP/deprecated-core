<?php
//
// Description
// -----------
// This function is optimized to return a simple list where column 1 is the id
// and column 2 is the value.
// It was designed for returning uuid and last_updated lists: SELECT uuid, last_updated FROM qruqsp_customers;
//
// Arguments
// ---------
//
function qruqsp_core_dbQueryIDList(&$q, $strsql, $module, $container_name) {
    //
    // Check connection to database, and open if necessary
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
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.128', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }

    //
    // FIXME: If hash, then return all rows together as a hash
    //
    $rsp = array('stat'=>'ok');
    $rsp['num_rows'] = 0;

    //
    // Build array of rows
    //
    $rsp[$container_name] = array();
    while( $row = mysqli_fetch_row($result) ) {
        $rsp[$container_name][$row[0]] = $row[1];
        $rsp['num_rows']++;
    }

    mysqli_free_result($result);

    return $rsp;
}
?>
