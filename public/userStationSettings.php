<?php
//
// Description
// -----------
// This method will return all the information about a station required when the user logs into the UI. 
//
// Arguments
// ---------
// api_key:
// auth_token:
//
function qruqsp_core_userStationSettings($q) {
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashIDQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQueryIDList');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryIDTree');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDetailsQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDetailsQueryHash');

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
    // Check access to station_id as owner, or sys admin
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, $args['station_id'], 'qruqsp.core.userStationSettings');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Setup the default return array
    //
    $rsp = array('stat'=>'ok', 'station'=>array(
        'id'=>$args['station_id'],
        'name'=>'',
        'modules'=>array(), 
        'permissions'=>array(), 
        'settings'=>array(), 
        'menu_items'=>array(), 
        'settings_menu_items'=>array(),
        ));

    //
    // Get the permission groups the user is a part of
    //
    $strsql = "SELECT permission_group AS name, 'yes' AS status "
        . "FROM qruqsp_core_station_users "
        . "WHERE qruqsp_core_station_users.station_id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' " 
        . "AND qruqsp_core_station_users.user_id = '" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "' "
        . "AND qruqsp_core_station_users.status = 10 "
        . "";
    $rc = qruqsp_core_dbQueryIDList($q, $strsql, 'qruqsp.core', 'permissions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['station']['permissions'] = $rc['permissions'];
    $q['station']['permissions'] = $rc['permissions'];

    //
    // Get the station name, and CSS
    //
    $strsql = "SELECT name "
        . "FROM qruqsp_core_stations "
        . "WHERE qruqsp_core_stations.id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' "
        . "";
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'station');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['station']) ) {
        $rsp['station']['name'] = $rc['station']['name'];
    }

    //
    // Get the list of modules for the station
    //
    $strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, flags, flags>>32 as flags2 "
        . "FROM qruqsp_core_station_modules "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' "
        . "AND (status = 1 OR status = 2) " // Added or mandatory
        . "";
    $rc = qruqsp_core_dbHashQueryIDTree($q, $strsql, 'qruqsp.core', array(
        array('container'=>'modules', 'fname'=>'name', 'fields'=>array('name', 'package', 'module', 'flags', 'flags2')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['modules']) ) {
        $rsp['station']['modules'] = $rc['modules'];
        $q['station']['modules'] = $rc['modules'];
        foreach($rsp['station']['modules'] as $mid => $module) {
            //
            // Check for uiSettings in other modules
            //
            $rc = qruqsp_core_loadMethod($q, $module['package'], $module['module'], 'hooks', 'uiSettings');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($q, $args['station_id'], array());
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['settings']) ) {
                    if( !isset($rsp['station']['settings'][$module['package']]) ) {
                        $rsp['station']['settings'][$module['package']] = array();
                    }
                    $rsp['station']['settings'][$module['package']][$module['module']] = $rc['settings'];
                }
                if( isset($rc['menu_items']) ) {
                    $rsp['station']['menu_items'] = array_merge($rsp['station']['menu_items'], $rc['menu_items']);
                }
                if( isset($rc['settings_menu_items']) ) {
                    $rsp['station']['settings_menu_items'] = array_merge($rsp['station']['settings_menu_items'], $rc['settings_menu_items']);
                }
            }

            //
            // FIXME: Move these into settings files for each module
            //
            if( isset($q['config']['qruqsp.web']['google.maps.api.key']) ) {
                $rsp['station']['settings']['googlemapsapikey'] = $q['config']['qruqsp.web']['google.maps.api.key'];
            }
        }
    }

    //
    // Load the station settings
    //
    $rc = qruqsp_core_dbDetailsQueryHash($q, 'qruqsp_core_station_details', 'station_id', $args['station_id'], 'qruqsp.core', 'settings', '');
    if( $rc['stat'] == 'ok' ) {
        foreach($rc['settings'] as $k => $v) {
            if( $k == 'ui' || $k == 'intl' ) {
                $rsp['station']['settings'][$k] = $v;
            }
        }
    }

    //
    // Sort the menu items based on priority
    //
    usort($rsp['station']['menu_items'], function($a, $b) {
        if( $a['priority'] == $b['priority'] ) {
            return 0;
        }
        return $a['priority'] > $b['priority'] ? -1 : 1;
    });

    //
    // Sort the setttings menu items based on priority
    //
    usort($rsp['station']['settings_menu_items'], function($a, $b) {
        if( $a['priority'] == $b['priority'] ) {
            return 0;
        }
        return $a['priority'] > $b['priority'] ? -1 : 1;
    });

    //
    // Check the menu_items duplicates
    //
    $prev_label = '';
    foreach($rsp['station']['menu_items'] as $iid => $item) {
        if( $item['label'] == $prev_label ) {
            unset($rsp['station']['menu_items'][$iid]);
        }
        $prev_label = $item['label'];
    }

    return $rsp;
}
?>
