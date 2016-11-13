<?php
//
// Description
// -----------
// This function will dynamically load a method into PHP.
//
// Arguments
// ---------
// q:           
// package:     The package the method belongs to
// module:      The package module the method is part of.
// type:        The type of method (public, private).
// name:        The name of the function.
//
function qruqsp_core_hookExec($q, $business_id, $package, $module, $name, $args) {
    $type = 'hooks';
    if( !file_exists($q['config']['qruqsp.core']['root_dir'] . '/' . $package . '-mods/' . $module . '/' . $type . '/' . $name . '.php') ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'qruqsp.core.137', 'msg'=>'Internal Error', 'pmsg'=>'Requested method does not exist'));
    }

    require_once($q['config']['qruqsp.core']['root_dir'] . '/' . $package . '-mods/' . $module . '/' . $type . '/' . $name . '.php');

    $fn = $package . '_' . $module . '_hooks_' . $name;

    return $fn($q, $business_id, $args);
}
?>
