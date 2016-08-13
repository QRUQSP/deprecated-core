<?php
//
// Description
// -----------
//
// Arguments
// ---------
// qruqsp:
// station_id:     The ID of the business the reference is for.
//
// args:            The arguments for adding the reference.
//
//                  object - The object that is referring to the object.
//                  object_id - The ID of the object that is referrign to the object.
//
// Returns
// -------
// <rsp stat="ok" id="45" />
//
function qruqsp_core_objectRefClear(&$q, $station_id, $obj_name, $args, $options=0) {
    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);

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

    if( !isset($args['object']) || $args['object'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.66', 'msg'=>'No reference object specified'));
    }
    if( !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.73', 'msg'=>'No reference object id specified'));
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDelete');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');


    //
    // Grab the uuid of the reference
    //
    $strsql = "SELECT id, uuid FROM " . $o['table'] . " "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "AND object = '" . qruqsp_core_dbQuote($q, $args['object']) . "' "
        . "AND object_id = '" . qruqsp_core_dbQuote($q, $args['object_id']) . "' "
        . "";
    $rc = qruqsp_core_dbHashQuery($q, $strsql, $m, 'ref');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'qruqsp.core.74', 'msg'=>'Reference does not exist'));
    }
    $refs = $rc['rows'];

    foreach($refs as $rowid => $ref) {
        $strsql = "DELETE FROM " . $o['table'] . " "
            . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
            . "AND id = '" . qruqsp_core_dbQuote($q, $ref['id']) . "' "
            . "";
        $rc = qruqsp_core_dbDelete($q, $strsql, $m);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.75', 'msg'=>'Unable to remove object reference', 'err'=>$rc['err'])); 
        }
        qruqsp_core_dbAddModuleHistory($q, $m, $o['history_table'], 
            $station_id, 3, $o['table'], $ref['id'], '*', '');
        $q['syncqueue'][] = array('push'=>"$pkg.$mod.ref",
            'args'=>array('delete_uuid'=>$ref['uuid'], 'delete_id'=>$ref['id']));
        
        //
        // FIXME: Add check for number of remaining references, possibly delete object
        //
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'businesses', 'private', 'updateModuleChangeDate');
    qruqsp_businesses_updateModuleChangeDate($q, $station_id, $pkg, $mod);

    return array('stat'=>'ok');
}
?>
