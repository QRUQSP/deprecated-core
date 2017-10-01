<?php
//
// Description
// -----------
// This function will create a comma delimited list of strings
// for use in WHERE variable IN (list) sql statements.  This ensures
// that everything is safe and properly escaped.
//
// Arguments
// ---------
// q:
// items:             The array of strings which need to be escaped.
//
function qruqsp_core_dbQuoteList(&$q, $items) {

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbConnect');

    $rc = qruqsp_core_dbConnect($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $str = '';
    $comma = '';
    foreach($items as $i) {
        $str .= $comma . '\'' . mysqli_real_escape_string($rc['dh'], $i) . '\'';
        $comma = ',';
    }

    return $str;
}
?>
