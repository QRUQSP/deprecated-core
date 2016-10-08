<?php
//
// Description
// -----------
// This function will create a comma delimited list of integers
// for use in WHERE variable IN (list) sql statements.  This ensures
// that everything is safe and properly escaped.
//
// Arguments
// ---------
// qruqsp:
// arr:         The array of ID's which need to be escaped.
//
function qruqsp_core_dbQuoteIDs(&$q, $arr) {

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbConnect');
    $rc = qruqsp_core_dbConnect($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $str = '';
    $comma = '';
    foreach($arr as $i) {
        if( is_int($i) ) {
            $str .= $comma . mysqli_real_escape_string($rc['dh'], $i);
            $comma = ',';
        } else if( is_numeric($i) ) {
            $str .= $comma . mysqli_real_escape_string($rc['dh'], intval($i));
            $comma = ',';
        }
    }

    return $str;
}
?>
