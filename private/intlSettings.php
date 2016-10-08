<?php
//
// Description
// -----------
// This function will return the intl settings for the station.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function qruqsp_core_intlSettings(&$q, $station_id) {

    //
    // Check the station settings cache
    //
    if( isset($q['station']['settings']['intl-default-locale']) 
        && isset($q['station']['settings']['intl-default-currency']) 
        && isset($q['station']['settings']['intl-default-timezone']) 
        ) {
        return array('stat'=>'ok', 'settings'=>$q['station']['settings']);    
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDetailsQueryDash');
    $rc = qruqsp_core_dbDetailsQueryDash($q, 'qruqsp_core_station_settings', 'station_id', $station_id, 'qruqsp.core', 'settings', 'intl');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Set the defaults if not found
    //
    if( !isset($rc['settings']) ) {
        $rc['settings'] = array();
    }
    if( !isset($rc['settings']['intl-default-locale']) ) {
        $rc['settings']['intl-default-locale'] = 'en_CA';
    }
    if( !isset($rc['settings']['intl-default-currency']) ) {
        $rc['settings']['intl-default-currency'] = 'CAD';
    }
    if( !isset($rc['settings']['intl-default-timezone']) ) {
        $rc['settings']['intl-default-timezone'] = 'America/Toronto';
    }
    if( !isset($rc['settings']['intl-default-distance-units']) ) {
        $rc['settings']['intl-default-distance-units'] = 'km';
    }

    //
    // Save the settings in the station cache
    //
    if( !isset($q['station']) ) {
        $q['station'] = array('settings'=>$rc['settings']);
    } elseif( !isset($q['station']['settings']) ) {
        $q['station']['settings'] = $rc['settings'];
    } else {
        if( !isset($q['station']['settings']['intl-default-locale']) ) {
            $q['station']['settings']['intl-default-locale'] = $rc['settings']['intl-default-locale'];
            $q['station']['settings']['intl-default-currency-fmt'] = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
        }
        if( !isset($q['station']['settings']['intl-default-currency']) ) {
            $q['station']['settings']['intl-default-currency'] = $rc['settings']['intl-default-currency'];
        }
        if( !isset($q['station']['settings']['intl-default-timezone']) ) {
            $q['station']['settings']['intl-default-timezone'] = $rc['settings']['intl-default-timezone'];
        }
        if( !isset($q['station']['settings']['intl-default-distance-units']) ) {
            $q['station']['settings']['intl-default-distance-units'] = $rc['settings']['intl-default-distance-units'];
        }
    }

    return $rc;
}
?>
