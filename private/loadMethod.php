<?php
//
// Description
// -----------
// This function will dynamically load a method into PHP.
//
// Arguments
// ---------
// qruqsp:      The qruqsp internal variable.
// package:     The package the method belongs to
// module:      The package module the method is part of.
// type:        The type of method (public, private).
// name:        The name of the function.
//
function qruqsp_core_loadMethod($q, $package, $module, $type, $name) {
    if( !file_exists($q['config']['qruqsp.core']['root_dir'] . '/' . $package . '-mods/' . $module . '/' . $type . '/' . $name . '.php') ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'qruqsp.core.13', 'msg'=>'Internal Error', 'pmsg'=>"Requested method '$package.$module.$type.$name' does not exist"));
    }

    require_once($q['config']['qruqsp.core']['root_dir'] . '/' . $package . '-mods/' . $module . '/' . $type . '/' . $name . '.php');

    return array('stat'=>'ok', 'function_call'=>$package . '_' . $module . '_' . ($type != 'private' ? $type . '_' : '') . $name);
}
?>
