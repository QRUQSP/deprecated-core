<?php
//
// Description
// -----------
// This method returns the settings for a station.
//
// Arguments
// ---------
// api_key:
// auth_token:
// station_id:         The ID of the station to get the settings for.
// keys:               The comma delimited list of keys to lookup values for.
//
function qruqsp_core_stationSettingsGet($q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'), 
        'keys'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Keys'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to station_id as owner, or sys admin
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $ac = qruqsp_core_checkAccess($q, $args['station_id'], 'qruqsp.core.stationSettingsGet');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    // Split the keys, if specified
    if( isset($args['keys']) && $args['keys'] != '' ) {
        $detail_keys = preg_split('/,/', $args['keys']);
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.142', 'msg'=>'No keys specified'));
    }

    $rsp = array('stat'=>'ok', 'settings'=>array());

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDetailsQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDetailsQueryDash');
    foreach($detail_keys as $detail_key) {
        if( $detail_key == 'station' ) {
            $strsql = "SELECT name, category, permalink, tagline FROM qruqsp_core_stations "
                . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' ";
            $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'station');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $rsp['settings']['station-name'] = $rc['station']['name'];
            $rsp['settings']['station-category'] = $rc['station']['category'];
            $rsp['settings']['station-permalink'] = $rc['station']['permalink'];
            $rsp['settings']['station-tagline'] = $rc['station']['tagline'];
        } elseif( in_array($detail_key, array('contact', 'qruqsp', 'social')) ) {
            $rc = qruqsp_core_dbDetailsQueryDash($q, 'qruqsp_core_station_settings', 'station_id', $args['station_id'], 'qruqsp.core', 'settings', $detail_key);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $rc['settings'] != null ) {
                $rsp['settings'] += $rc['settings'];
            }
        }
    }

    return $rsp;
}
?>
