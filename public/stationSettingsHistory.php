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
// station_id:         The ID of the station to get the history for.
// field:              The detail key to get the history for.
//
function qruqsp_core_stationSettingsHistory($q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
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
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $ac = qruqsp_core_checkAccess($q, $args['station_id'], 'qruqsp.core.stationSettingsHistory');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbGetModuleHistory');
    if( $args['field'] == 'station-name' ) {
        return qruqsp_core_dbGetModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 'qruqsp_core_stations', '', 'name');
    } elseif( $args['field'] == 'station-category' ) {
        return qruqsp_core_dbGetModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 'qruqsp_core_stations', '', 'category');
    } elseif( $args['field'] == 'station-permalink' ) {
        return qruqsp_core_dbGetModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 'qruqsp_core_stations', '', 'permalink');
    } elseif( $args['field'] == 'station-tagline' ) {
        return qruqsp_core_dbGetModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 'qruqsp_stations', '', 'tagline');
    }

    return qruqsp_core_dbGetModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 
        'qruqsp_core_station_settings', $args['field'], 'detail_value');
}
?>
