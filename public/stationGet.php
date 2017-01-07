<?php
//
// Description
// ===========
// This method will return information about a station.
//
// Arguments
// ---------
// api_key:
// auth_token:
// station_id:         The ID of the station to get.
//
function qruqsp_core_stationGet($q) {
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
    // Make sure this module is activated, and
    // check permission to run this function for this station
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, $args['station_id'], 'qruqsp.core.stationGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load station settings
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'intlSettings');
    $rc = qruqsp_core_intlSettings($q, $args['station_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dateFormat');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'timeFormat');
    $date_format = qruqsp_core_dateFormat($q, 'php');
    $time_format = qruqsp_core_timeFormat($q, 'php');

    //
    // Return default for new station
    //
    if( $args['station_id'] == 0 ) {
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        $station = array('id'=>0,
            'name'=>'',
        );
    }

    //
    // Get the details for an existing station
    //
    else {
        $strsql = "SELECT qruqsp_core_stations.id, "
            . "qruqsp_core_stations.name, "
            . "qruqsp_core_stations.category "
            . "FROM qruqsp_core_stations "
            . "WHERE qruqsp_core_stations.id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' "
            . "";
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = qruqsp_core_dbHashQueryArrayTree($q, $strsql, 'qruqsp.qsl', array(
            array('container'=>'stations', 'fname'=>'id', 'fields'=>array('id', 'name', 'category')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.155', 'msg'=>'Station not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['stations'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.156', 'msg'=>'Unable to find station'));
        }
        $station = $rc['stations'][0];
    }

    return array('stat'=>'ok', 'station'=>$station);
}
?>
