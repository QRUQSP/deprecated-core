<?php
//
// Description
// -----------
// This function will get the history of a field from the qruqsp_core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Arguments
// ---------
// api_key:
// auth_token:
// station_id:         The ID of the station to get the details for.
// key:                 The detail key to get the history for.
//
function qruqsp_core_stationModuleFlagsHistory($qruqsp) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($qruqsp, 'no', array(
        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to station_id as owner, or sys admin
    //
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($qruqsp, $args['station_id'], 'qruqsp.core.stationModuleFlagsHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbGetModuleHistory');
    return qruqsp_core_dbGetModuleHistory($qruqsp, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 
        'qruqsp_core_station_modules', $args['field'], 'flags');
}
?>
