<?php
//
// Description
// -----------
// This function will load the object definition.
//
// Arguments
// ---------
// qruqsp:
// pkg:         The package the object is a part of
// mod:         The module in the package
// obj:         The object in the module.
//
// Returns
// -------
//
function qruqsp_core_objectLoad(&$q, $obj_name) {
    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);

    if( isset($q['objects'][$pkg][$mod][$obj]) ) {
        return array('stat'=>'ok', 'pkg'=>$pkg, 'mod'=>$mod, 'object'=>$q['objects'][$pkg][$mod][$obj]);
    }

    //
    // Load the objects for this module
    //
    $method_filename = $q['config']['qruqsp.core']['root_dir'] . "/$pkg-mods/$mod/private/objects.php";
    $method_function = "{$pkg}_{$mod}_objects";
    if( !file_exists($method_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.68', 'msg'=>'Unable to load object definition: ' . $pkg . '.' . $mod . '.' . $obj));
    }

    require_once($method_filename);
    if( !is_callable($method_function) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.69', 'msg'=>'Unable to load object definition: ' . $pkg . '.' . $mod . '.' . $obj));
    }

    $rc = $method_function($q);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.70', 'msg'=>'Unable to load object definition: ' . $pkg . '.' . $mod . '.' . $obj));
    }
    if( !isset($rc['objects']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.71', 'msg'=>'Unable to load object definition: ' . $pkg . '.' . $mod . '.' . $obj));
    }
    $objects = $rc['objects'];

    if( !isset($objects[$obj]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.72', 'msg'=>'Unable to load object definition: ' . $pkg . '.' . $mod . '.' . $obj));
    }

    //
    // Store the loaded object, so it only needs to be loaded once
    //
    if( !isset($q['objects']) ) {
        $q['objects'] = array($pkg=>array($mod=>$objects));
    } elseif( !isset($q['objects'][$pkg]) ) {
        $q['objects'][$pkg] = array($mod=>$objects);
    } elseif( !isset($q['objects'][$pkg][$mod]) ) {
        $q['objects'][$pkg][$mod] = $objects;
    }
    
    return array('stat'=>'ok', 'pkg'=>$pkg, 'mod'=>$mod, 'object'=>$objects[$obj]);
}
?>
