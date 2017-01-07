<?php
//
// Description
// -----------
// This method will return the list of stations for the install.
//
// Arguments
// ---------
// api_key:
// auth_token:
// station_id:        The ID of the station to get Log Entry for.
//
function qruqsp_core_stationList($q) {
    //
    // Check access to station_id as owner, or sys admin.
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.stationList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of entries
    //
    $strsql = "SELECT qruqsp_core_stations.id, "
        . "qruqsp_core_stations.name "
        . "FROM qruqsp_core_stations "
        . "ORDER BY name "
        . "";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = qruqsp_core_dbHashQueryArrayTree($q, $strsql, 'qruqsp.core', array(
        array('container'=>'stations', 'fname'=>'id', 
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['stations']) ) {
        $stations = $rc['stations'];
        $station_ids = array();
        foreach($stations as $iid => $station) {
            $station_ids[] = $station['id'];
        }
    } else {
        $stations = array();
        $station_ids = array();
    }

    return array('stat'=>'ok', 'stations'=>$stations, 'nplist'=>$station_ids);
}
?>
