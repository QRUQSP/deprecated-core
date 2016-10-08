<?php
//
// Description
// -----------
// This function will check if an object exists.
//
// Arguments
// ---------
// qruqsp:
// station_id:      The ID of the station.
// object:          The name of the object to check.
// object_id:       The ID of the object to check for existence.
//
// Returns
// -------
//
function qruqsp_core_objectCheckExists(&$q, $station_id, $object, $object_id) {
    //
    // Break apart object
    //
    list($pkg, $mod, $obj) = explode('.', $object);

    //
    // Load the object file
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectLoad');
    $rc = qruqsp_core_objectLoad($q, $object);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $o = $rc['object'];
    $m = "$pkg.$mod";

    //
    // Query for the object id
    //
    $strsql = "SELECT id "
        . "FROM " . $o['table'] . " "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $object_id) . "' "
        . "AND station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "";
    $rc = qruqsp_core_dbHashQuery($q, $strsql, $m, 'object');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['object']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'qruqsp.core.63', 'msg'=>'Object does not exist'));
    }

    return array('stat'=>'ok');
}
?>
