<?php
//
// Description
// -----------
// This method will get detail values for a user.  These values are used in the UI.
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:             The ID of the user to get the details for.
// keys:                The comma delimited list of keys to lookup values for.
//
function qruqsp_core_userDetails($q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        'keys'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Keys'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.getDetails');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDetailsQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDetailsQueryDash');

    // Split the keys, if specified
    $detail_keys = preg_split('/,/', $args['keys']);

    $rsp = array('stat'=>'ok', 'details'=>array());

    foreach($detail_keys as $detail_key) {
        if( $detail_key == 'user' ) {
            $strsql = "SELECT callsign,  display_name "
                . "FROM qruqsp_core_users "
                . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' ";
            $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'user');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $rsp['details']['user.callsign'] = $rc['user']['callsign'];
            $rsp['details']['user.display_name'] = $rc['user']['display_name'];
        } 
        elseif( in_array($detail_key, array('ui','settings')) ) {
            $rc = qruqsp_core_dbDetailsQueryDash($q, 'qruqsp_core_user_details', 'user_id', $args['user_id'], 'qruqsp.core', 'details', $detail_key);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            if( $rc['details'] != null ) {
                $rsp['details'] += $rc['details'];
            }
        }
    }

    //
    // Check if the user has access to calendars in any busines
    //
    $strsql = "SELECT 'num_stations', "
        . "COUNT(qruqsp_core_station_modules.station_id) AS num_stations "
        . "FROM qruqsp_core_station_users, qruqsp_core_station_modules "
        . "WHERE qruqsp_core_station_users.user_id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' "
        . "AND qruqsp_core_station_users.station_id = qruqsp_core_station_modules.station_id "
        . "AND qruqsp_core_station_users.status = 10 "              // Active user
        . "AND qruqsp_core_station_modules.package = 'qruqsp' "     // Package qruqsp
        . "AND qruqsp_core_station_modules.module = 'calendars' "   // calendars module
        . "AND qruqsp_core_station_modules.status = 1 "             // active module
        . "";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbCount');
    $rc = qruqsp_core_dbCount($q, $strsql, 'qruqsp.core', 'count');
    if( $rc['stat'] == 'ok' && $rc['count']['num_stations'] > 0 ) {
        if( !isset($rsp['details']['ui-calendar-view']) ) {
            $rsp['details']['ui-calendar-view'] = 'mw';
        }
        if( !isset($rsp['details']['ui-calendar-remember-date']) ) {
            $rsp['details']['ui-calendar-remember-date'] = 'yes';
        }
    } else {
        if( isset($rsp['details']['ui-calendar-view']) ) {
            unset($rsp['details']['ui-calendar-view']);
        }
        if( isset($rsp['details']['ui-calendar-remember-date']) ) {
            unset($rsp['details']['ui-calendar-remember-date']);
        }
    }

    return $rsp;
}
?>
