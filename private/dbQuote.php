<?php
//
// Description
// -----------
// This function will escape a string to be used in an SQL query
//
// Info
// ----
// Status:          beta
//
// Arguments
// ---------
// qruqsp:
// str:             The string to escape.
//
function qruqsp_core_dbQuote(&$q, $str) {

//  if( is_array($str) ) {
//      error_log(print_r($str, true));
//  }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbConnect');

    $rc = qruqsp_core_dbConnect($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return mysqli_real_escape_string($rc['dh'], $str);
}
?>
