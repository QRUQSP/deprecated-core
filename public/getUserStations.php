<?php
//
// Description
// -----------
// This function will return the list of stations which the user has access to.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
//
function qruqsp_core_getUserStations($q) {
    //
    // Check access to station_id as owner, or sys admin
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $ac = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.getUserStations');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    // 
    // Check the database for user and which stations they have access to.  If they
    // are a sysadmin, they have access to all stations.
    // Link to the station_users table to grab the groups the user belongs to for that station.
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    if( ($q['session']['user']['perms'] & 0x01) == 0x01 ) {
        //
        // Check if there is a debug file of action to do on login
        //
        if( file_exists($q['config']['qruqsp.core']['root_dir'] . '/loginactions.js') ) {
            $login_actions = file_get_contents($q['config']['qruqsp.core']['root_dir'] . '/loginactions.js'); 
        }

        $strsql = "SELECT qruqsp_core_stations.category, "
            . "qruqsp_core_stations.id, "
            . "qruqsp_core_stations.name "
            . "FROM qruqsp_core_stations "
            . "ORDER BY category, qruqsp_core_stations.status, qruqsp_core_stations.name "
            . "";
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = qruqsp_core_dbHashQueryArrayTree($q, $strsql, 'qruqsp.core', array(
            array('container'=>'categories', 'fname'=>'category', 'fields'=>array('name'=>'category')),
            array('container'=>'stations', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));

        if( isset($login_actions) && $login_actions != '' ) {
            $rc['loginActions'] = $login_actions;
        }

        return $rc;
    } else {
        $strsql = "SELECT DISTINCT qruqsp_core_stations.id, "
            . "qruqsp_core_stations.name "
            . "FROM qruqsp_core_station_users, qruqsp_core_stations "
            . "WHERE qruqsp_core_station_users.user_id = '" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "' "
            . "AND qruqsp_core_station_users.status = 10 "
            . "AND qruqsp_core_station_users.station_id = qruqsp_core_stations.id "
            . "AND qruqsp_core_stations.status < 60 "  // Allow suspended stations to be listed, so user can login and update billing/unsuspend
            . "ORDER BY qruqsp_core_stations.name ";
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = qruqsp_core_dbHashQueryArrayTree($q, $strsql, 'qruqsp.stations', array(
        array('container'=>'stations', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    return $rc;
}
?>
