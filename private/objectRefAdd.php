<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_core_objectRefAdd(&$q, $station_id, $obj_name, $args, $options=0x07) {
    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);

    //
    // Check for self referencing
    //
    if( "$pkg.$mod.ref" == $args['object']) {
        return array('stat'=>'ok');
    }

    //
    // Check if there is a reference table for module being referred to
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectLoad');
    $rc = qruqsp_core_objectLoad($q, $pkg . '.' . $mod . '.ref');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'ok');
    }

    $o = $rc['object'];
    $m = "$pkg.$mod";

    //
    // Check to make sure all variable required were passed
    //
    if( !isset($args['ref_id']) || $args['ref_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.77', 'msg'=>'No ref specified'));
    }
    if( !isset($args['object']) || $args['object'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.78', 'msg'=>'No object specified'));
    }
    if( !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.79', 'msg'=>'No object ID specified'));
    }
    if( !isset($args['object_field']) || $args['object_field'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.80', 'msg'=>'No object field specified'));
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectAdd');
    return qruqsp_core_objectAdd($q, $station_id, "$pkg.$mod.ref", $args, $options);
}
?>
