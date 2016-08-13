<?php
//
// Description
// -----------
// This function will verify the station is active, the module is enabled and the user has permission.
// This function will typically be called by the private/checkAccess.php file in each module.
//
// Arguments
// ---------
// q:
// station_id:          The ID of the station to check the session user against.
// args:                The args
//
// Returns
// -------
// <rsp stat='ok' />
//
function qruqsp_core_checkModuleAccess(&$q, $station_id, $args) {

    if( !isset($args['package']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.1', 'msg'=>'Internal Error'));
    }
    if( !isset($args['module']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.2', 'msg'=>'Internal Error'));
    }
    if( !isset($args['groups']) || !is_array($args['groups']) || count($args['groups']) < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.3', 'msg'=>'Internal Error'));
    }
    $package = $args['package'];
    $module = $args['module'];

    //
    // Get the active modules for the station
    //
    $strsql = "SELECT qruqsp_core_stations.status AS station_status, "
        . "qruqsp_core_station_modules.status AS module_status, "
        . "qruqsp_core_station_modules.package, qruqsp_core_station_modules.module, "
        . "CONCAT_WS('.', qruqsp_core_station_modules.package, qruqsp_core_station_modules.module) AS module_id, "
        . "flags, (flags&0xFFFFFFFF00000000)>>32 as flags2, "
        . "FROM qruqsp_core_stations, qruqsp_core_station_modules "
        . "WHERE qruqsp_core_stations.id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "AND qruqsp_core_stations.id = qruqsp_core_station_modules.station_id "
        // Get the optional and mandatory modules
        . "AND (qruqsp_core_station_modules.status = 1 || qruqsp_core_station_modules.status = 2) "
        . "";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashIDQuery');
    $rc = qruqsp_core_dbHashIDQuery($q, $strsql, 'qruqsp.core', 'modules', 'module_id');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.12', 'msg'=>'Internal Error', 'err'=>$rc['err']));
    }

    if( !isset($rc['modules']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.4', 'msg'=>'No modules enabled'));
    }

    $q['station']['modules'] = $rc['modules'];

    if( !isset($rc['modules'][$package . '.' . $module]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.5', 'msg'=>"Module '$package.$module' disabled"));
    }

    //
    // Check if the station is not active
    //
    if( isset($rc['modules'][$package . '.' . $module]['station_status']) && $rc['modules'][$package . '.' . $module]['station_status'] != 1 ) {
        if( $rc['modules'][$package . '.' . $module]['station_status'] == 50 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.6', 'msg'=>'Station suspended'));
        } elseif( $rc['modules'][$package . '.' . $module]['station_status'] == 60 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.7', 'msg'=>'Station deleted'));
        }
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.8', 'msg'=>'Station inactive'));
    }

    //
    // Check if module is not active
    //
    if( isset($rc['modules'][$package . '.' . $module]['module_status']) 
        && $rc['modules'][$package . '.' . $module]['module_status'] != 1 
        && $rc['modules'][$package . '.' . $module]['module_status'] != 2 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.9', 'msg'=>'Module disabled'));
    }

    //
    // Check if the user is a sysadmin and if sysadmins are ok
    //
    if( isset($args['sysadmins']) && $args['sysadmins'] == 'yes' && ($q['session']['user']['perms']&0x01) == 0x01 ) {
        return array('stat'=>'ok');
    }

    //
    // Check if the user has permission for the station
    //
    $strsql = "SELECT station_id, user_id "
        . "FROM qruqsp_core_station_users "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "AND user_id = '" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "' "
        . "AND status = 10 "
        . "AND permission_group IN 'operators' "
        . "";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'user');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.10', 'msg'=>'Access denied.'));
    }
    //
    // Double check the user is the rows returned.
    //
    if( isset($rc['rows']) && isset($rc['rows'][0])
        && $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $q['session']['user']['id'] ) {
        return array('stat'=>'ok');
    }

    //
    // Return fail by default
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.11', 'msg'=>'Access denied'));
}
?>
