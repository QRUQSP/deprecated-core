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
function qruqsp_core_stationModuleList($q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'), 
        'plans'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Plans'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to station_id as owner, or sys admin. 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, $args['station_id'], 'qruqsp.core.stationModuleList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashIDQuery');
    $strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, status "
        . "FROM qruqsp_core_station_modules "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' "
        . "ORDER BY name "
        . "";   
    $rc = qruqsp_core_dbHashIDQuery($q, $strsql, 'qruqsp.core', 'modules', 'name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['modules']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.139', 'msg'=>'No station found'));
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

    $modules = array();
    $count = 0;
    foreach($mod_list as $module) {
        if( $module['label'] != '' && $module['installed'] == 'Yes' && (!isset($module['optional']) || $module['optional'] == 'yes') ) {
            $modules[$count] = array('label'=>$module['label'], 'package'=>$module['package'], 'name'=>$module['name'], 'status'=>'0');
            if( isset($station_modules[$module['package'] . '.' . $module['name']]) 
                && $station_modules[$module['package'] . '.' . $module['name']]['status'] == 1 ) {
                $modules[$count]['status'] = '1';
            }
            $count++;
        }
    }

    $rsp = array('stat'=>'ok', 'modules'=>$modules);
    
/*    //
    // Get the list of available plans for the station
    // 
    if( isset($args['plans']) && $args['plans'] == 'yes' ) {
        if( $args['station_id'] == '0' ) {
            $args['station_id'] = $q['config']['qruqsp.core']['master_station_id'];
        }
        //
        // Query the database for the plan
        //
        $strsql = "SELECT id, name, monthly, trial_days "
            . "FROM qruqsp_core_station_plans "
            . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' "
            . "ORDER BY sequence "
            . "";

        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryTree');
        $rc = qruqsp_core_dbHashQueryTree($q, $strsql, 'qruqsp.stations', array(
            array('container'=>'plans', 'fname'=>'id', 'name'=>'plan', 'fields'=>array('id', 'name', 'monthly')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['plans']) ) {
            $rsp['plans'] = $rc['plans'];
        }
    } */

    return $rsp;
}
?>
