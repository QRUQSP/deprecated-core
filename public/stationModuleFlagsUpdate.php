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
// MODULE_NAME:         The name of the module, and the value if it's On or Off.
//
function qruqsp_core_stationModuleFlagsUpdate($q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to station_id as owner, or sys admin. 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $ac = qruqsp_core_checkAccess($q, $args['station_id'], 'qruqsp.core.stationModuleFlagsUpdate');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashIDQuery');
    $strsql = "SELECT CONCAT_WS('.', package, module) AS name, module, status "
        . "FROM qruqsp_core_station_modules "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "'";  
    $rc = qruqsp_core_dbHashIDQuery($q, $strsql, 'qruqsp.core', 'modules', 'name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $station_modules = $rc['modules'];

    //  
    // Get the list of available modules
    //  
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'getModuleList');
    $rc = qruqsp_core_getModuleList($q);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $mod_list = $rc['modules'];

    //  
    // Start transaction
    //  
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionStart');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionRollback');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionCommit');
    $rc = qruqsp_core_dbTransactionStart($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Find all the modules which are to change status
    //
    foreach($mod_list as $module) {
        $name = $module['package'] . '.' . $module['name'];
        
        if( isset($q['request']['args'][$name]) ) {
            //
            // Add the module if it doesn't exist
            //
            if( !isset($station_modules[$name]) ) {
                $strsql = "INSERT INTO qruqsp_core_station_modules (station_id, package, module, "
                    . "status, flags, date_added, last_updated) VALUES ("
                    . "'" . qruqsp_core_dbQuote($q, $args['station_id']) . "' "
                    . ", '" . qruqsp_core_dbQuote($q, $module['package']) . "' "
                    . ", '" . qruqsp_core_dbQuote($q, $module['name']) . "' "
                    . ", '2'"
                    . ", '" . qruqsp_core_dbQuote($q, $q['request']['args'][$name]) . "' "
                    . ", UTC_TIMESTAMP(), UTC_TIMESTAMP() "
                    . ")";
                $rc = qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
                if( $rc['stat'] != 'ok' ) {
                    qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
                    return $rc;
                } 
                qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', 
                    $args['station_id'], 1, 'qruqsp_core_station_modules', $name, 'status', '2');
                qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', 
                    $args['station_id'], 1, 'qruqsp_core_station_modules', $name, 'flags', 
                    $q['request']['args'][$name]);
            } 
            //
            // Update the existing module
            //
            else {
                $strsql = "UPDATE qruqsp_core_station_modules SET "
                    . "flags = '" . qruqsp_core_dbQuote($q, $q['request']['args'][$name]) . "', "
                    . "last_updated = UTC_TIMESTAMP() "
                    . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' "
                    . "AND package = '" . qruqsp_core_dbQuote($q, $module['package']) . "' "
                    . "AND module = '" . qruqsp_core_dbQuote($q, $module['name']) . "' "
                    . "";
                $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
                if( $rc['stat'] != 'ok' ) {
                    qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
                    return $rc;
                } 
                qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', 
                    $args['station_id'], 2, 'qruqsp_core_station_modules', $name, 'flags', 
                    $q['request']['args'][$name]);
            }
        }
    }

    $rc = qruqsp_core_dbTransactionCommit($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    } 

    return array('stat'=>'ok');
}
?>
