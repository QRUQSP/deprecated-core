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
function qruqsp_core_stationModuleFlagsGet($q) {
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
    $ac = qruqsp_core_checkAccess($q, $args['station_id'], 'qruqsp.core.stationModuleFlagsGet');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashIDQuery');
    $strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, flags "
        . "FROM qruqsp_core_station_modules "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' "
        . "AND status > 0 "
        . "ORDER BY name "
        . "";   
    $rc = qruqsp_core_dbHashIDQuery($q, $strsql, 'qruqsp.core', 'modules', 'name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['modules']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.141', 'msg'=>'No station found'));
    }
    $station_modules = $rc['modules'];

    //
    // Add core flags here if any in the future
    //
//    if( !isset($station_modules['qruqsp.core']) ) {
//        $station_modules['qruqsp.core'] = array('name'=>'Core', 
//            'package'=>'qruqsp', 'module'=>'core', 'flags'=>'0');
//    }

    //
    // Check for the name and flags available for each module
    //
    foreach($station_modules as $mid => $module) {
        //
        // Check for info file
        //
        $station_modules[$mid]['proper_name'] = $module['name'];
        $info_filename = $q['config']['qruqsp.core']['root_dir'] . '/' . $module['package'] . '-mods/' . $module['module'] . '/_info.ini';
        if( file_exists($info_filename) ) {
            $info = parse_ini_file($info_filename);
            if( isset($info['name']) && $info['name'] != '' ) {
                $station_modules[$mid]['proper_name'] = $info['name'];
            } 
        }
        
        //
        // Check if flags file exists
        //
        $rc = qruqsp_core_loadMethod($q, $module['package'], $module['module'], 'private', 'flags');
        if( $rc['stat'] == 'ok' ) {
            $fn = $module['package'] . '_' . $module['module'] . '_flags';
            $rc = $fn($q, $station_modules);
            if( count($rc['flags']) > 0 ) {
                $station_modules[$mid]['available_flags'] = $rc['flags'];
            }
        }
    }

    return array('stat'=>'ok', 'modules'=>$station_modules);
}
?>
