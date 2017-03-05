<?php
//
// Description
// -----------
// This function will take a call sign and split into call sign and suffix
//
// Arguments
// ---------
//
function qruqsp_core_explodeCallSign(&$q, $call_sign) {

    $pieces = preg_split('#/-#', $call_sign);
    $call_sign = $pieces[0];
    $call_suffix = '';
    if( isset($pieces[1]) ) {
        $call_suffix = $pieces[1];
    }

    return array('stat'=>'ok', 'call_sign'=>$call_sign, 'call_suffix'=>$call_suffix);
}
?>
