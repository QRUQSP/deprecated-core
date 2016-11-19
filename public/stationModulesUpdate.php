<?php
//
// Description
// -----------
// This function will return the list of modules available in the system,
// and which modules the requested station has access to.
//
// Arguments
// ---------
// api_key:
// auth_token:
// station_id:         The ID of the station to get the module list for.
//
function qruqsp_core_stationModulesUpdate($qruqsp) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($qruqsp, 'no', array(
        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to station_id as owner, or sys admin. 
    //
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($qruqsp, $args['station_id'], 'qruqsp.core.stationModulesUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbUpdate');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbHashIDQuery');
    $strsql = "SELECT CONCAT_WS('.', package, module) AS name, module, status "
        . "FROM qruqsp_core_station_modules "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($qruqsp, $args['station_id']) . "'"
        . "";  
    $rc = qruqsp_core_dbHashIDQuery($qruqsp, $strsql, 'qruqsp.core', 'modules', 'name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $station_modules = $rc['modules'];

    //  
    // Get the list of available modules
    //  
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'getModuleList');
    $rc = qruqsp_core_getModuleList($qruqsp);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $mod_list = $rc['modules'];

    //  
    // Start transaction
    //  
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbTransactionStart');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbTransactionRollback');
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbTransactionCommit');
    $rc = qruqsp_core_dbTransactionStart($qruqsp, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Find all the modules which are to change status
    //
    foreach($mod_list as $module) {
        $name = $module['package'] . '.' . $module['name'];
        if( isset($qruqsp['request']['args'][$name]) ) {
            $strsql = "INSERT INTO qruqsp_core_station_modules "
                . "(station_id, package, module, status, date_added, last_updated) "
                . "VALUES ('" . qruqsp_core_dbQuote($qruqsp, $args['station_id']) . "', "
                . "'" . qruqsp_core_dbQuote($qruqsp, $module['package']) . "', "
                . "'" . qruqsp_core_dbQuote($qruqsp, $module['name']) . "', "
                . "'" . qruqsp_core_dbQuote($qruqsp, $qruqsp['request']['args'][$name]) . "', "
                . "UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                . "ON DUPLICATE KEY UPDATE "
                    . "status = '" . qruqsp_core_dbQuote($qruqsp, $qruqsp['request']['args'][$name]) . "' "
                    . "";
            $rc = qruqsp_core_dbUpdate($qruqsp, $strsql, 'qruqsp.core');
            if( $rc['stat'] != 'ok' ) {
                qruqsp_core_dbTransactionRollback($qruqsp, 'qruqsp.core');
                return $rc;
            } 
            qruqsp_core_dbAddModuleHistory($qruqsp, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 
                2, 'qruqsp_core_station_modules', $name, 'status', $qruqsp['request']['args'][$name]);
        }
    }

    //
    // Update the last_updated date so changes will be sync'd
    //
    $strsql = "UPDATE qruqsp_core_stations SET last_updated = UTC_TIMESTAMP() "
        . "WHERE id = '" . qruqsp_core_dbQuote($qruqsp, $args['station_id']) . "' "
        . "";
    $rc = qruqsp_core_dbUpdate($qruqsp, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $rc = qruqsp_core_dbTransactionCommit($qruqsp, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    } 

    return array('stat'=>'ok');
}
?>
