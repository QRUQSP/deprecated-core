<?php
//
// Description
// -----------
// Log a message, currently to error_log but could be changed in the future.
//
// Arguments
// ---------
// q:
//
function qruqsp_core_logMsg($q, $lvl, $msg) {

//  if( isset($_SERVER['argc']) ) {
    if( php_sapi_name() == 'cli' ) {
        error_log('[' . date('d/M/Y:H:i:s O') . '] ' . $msg);
    } else {
        error_log($msg);
    }

    return array('stat'=>'ok');
}
?>
