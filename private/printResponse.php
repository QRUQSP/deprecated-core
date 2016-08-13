<?php
//
// Description
// ===========
// This function is a wrapper to format and output the response
// in the specified format.
//
// Arguments
// =========
// qruqsp:      The qruqsp internal variable.
// hash:        The hash structure to return as a response.
//
function qruqsp_core_printResponse($q, $hash) {

    if( !is_array($hash) ) {
        $rsp_hash = array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.25', 'msg'=>'Internal configuration error'));
    } else {
        $rsp_hash = $hash;
    }

    //
    // Currently only supporting JSON response format, more may be added in the future
    //
    header("Content-Type: text/plain; charset=utf-8");
    header("Cache-Control: no-cache, must-revalidate");
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'printHashToJSON');
    json_encode($hash);
}
?>
