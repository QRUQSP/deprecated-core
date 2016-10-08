<?php
//
// Description
// -----------
// This function will return true or false based on the flags passed.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function qruqsp_core_checkModuleFlags($q, $module, $flags) {
    if( isset($q['station']['modules'][$module]['flags']) && ($q['station']['modules'][$module]['flags']&$flags) > 0 ) {
        return true;
    }
    return false;
}
?>
