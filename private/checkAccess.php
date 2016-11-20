<?php
//
// Description
// -----------
// This function will check if the user has access to a specified module and function.
//
// Arguments
// ---------
// qruqsp:
// station_id:         The station ID to check the session user against.
//
// Returns
// -------
// <rsp stat='ok' />
//
function qruqsp_core_checkAccess($q, $station_id, $method) {
    //
    // Methods which don't require authentication
    //
    $noauth_methods = array(
        'qruqsp.core.auth',
        'qruqsp.core.echoTest',
        'qruqsp.core.passwordRequestReset',
        'qruqsp.core.changeTempPassword',
        );
    if( in_array($method, $noauth_methods) ) {
        return array('stat'=>'ok');
    }

    //
    // Check the user is authenticated
    //
    if( !isset($q['session'])
        || !isset($q['session']['user'])
        || !isset($q['session']['user']['id'])
        || $q['session']['user']['id'] < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.90', 'msg'=>'User not authenticated'));
    }

    //
    // Check if the requested method is a public method
    //
    $public_methods = array(
        'qruqsp.core.getAddressCountryCodes',
        'qruqsp.core.parseDatetime',
        'qruqsp.core.parseDate',
        'qruqsp.core.logout',
        'qruqsp.core.changePassword',
        'qruqsp.core.userStations',
        'qruqsp.core.userStationSettings',
        );
    if( in_array($method, $public_methods) ) {
        return array('stat'=>'ok');
    }

    //
    // If the user is a sysadmin, they have access to all functions
    //
    if( ($q['session']['user']['perms']&0x01) == 0x01 ) {
        return array('stat'=>'ok');
    }

    //
    // Check if the user is an operator of the station
    //
    if( $station_id > 0 ) {
        //
        // Get the list of permission_groups the user is a part of
        //
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
        $strsql = "SELECT permission_group "
            . "FROM qruqsp_core_station_users "
            . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
            . "AND user_id = '" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "' "
            . "AND status = 10 "    // Active user
            . "";
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQueryList');
        $rc = qruqsp_core_dbQueryList($q, $strsql, 'qruqsp.core', 'groups', 'permission_group');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['groups']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.138', 'msg'=>'Access denied'));
        }
        $groups = $rc['groups'];

        //
        // The list of methods available to an operator
        //
        $operator_methods = array(
            'qruqsp.core.stationSettingsUpdate',
            'qruqsp.core.stationSettingsGet',
            'qruqsp.core.stationSettingsHistory',
            );
        
        //
        // Check if the user is an operator and if the requested method is in the operator methods
        //
        if( in_array($method, $operator_methods) && in_array('operators', $groups) ) {
            return array('stat'=>'ok');
        }
    }

    //
    // By default fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.91', 'msg'=>'Access denied'));
}
?>
