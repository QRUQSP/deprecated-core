<?php
//
// Description
// -----------
// This function will add a new station.  You must be a sys admin to be authorized to add a station.
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
function qruqsp_core_stationAdd(&$q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station Name'), 
        'category'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Category'), 
        'permalink'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Sitename'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to station_id as owner, or sys admin
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $ac = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.stationAdd');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Load timezone settings
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'intlSettings');
    $rc = qruqsp_core_intlSettings($q, $q['config']['qruqsp.core']['master_station_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Check the permalink is proper format
    //
    if( preg_match('/[^a-z0-9\-_]/', $args['permalink']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.140', 'msg'=>'Illegal characters in permalink.  It can only contain lowercase letters, numbers, underscores (_) or dash (-)'));
    }

    //
    // Check to make sure the permalink
    //
    $strsql = "SELECT id "
        . "FROM qruqsp_core_stations "
        . "WHERE name = '" . qruqsp_core_dbQuote($q, $args['name']) . "' "
        . "OR permalink = '" . qruqsp_core_dbQuote($q, $args['permalink']) . "' "
        . "";
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'station');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.158', 'msg'=>'You already have an station with this name or sitename, please choose another name.'));
    }
    
    //
    // Load required functions
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectAdd');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');

    //
    // Turn off autocommit
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionStart');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionRollback');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionCommit');
    $rc = qruqsp_core_dbTransactionStart($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the station to the database
    //
    $strsql = "INSERT INTO qruqsp_core_stations (uuid, name, category, permalink, status, date_added, last_updated) "
        . "VALUES ("
        . "UUID(), "
        . "'" . qruqsp_core_dbQuote($q, $args['name']) . "' "
        . ", '" . qruqsp_core_dbQuote($q, $args['category']) . "' "
        . ", '" . qruqsp_core_dbQuote($q, $args['permalink']) . "' "
        . ", 1 "
        . ", UTC_TIMESTAMP(), UTC_TIMESTAMP())";
    $rc = qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return $rc;
    }
    if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.144', 'msg'=>'Unable to add station'));
    }
    $station_id = $rc['insert_id'];
    qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $station_id, 
        1, 'qruqsp_core_stations', $station_id, 'name', $args['name']);
    qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $station_id, 
        1, 'qruqsp_core_stations', $station_id, 'category', $args['category']);
    qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $station_id, 
        1, 'qruqsp_core_stations', $station_id, 'permalink', $args['permalink']);
    qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $station_id, 
        1, 'qruqsp_core_stations', $station_id, 'status', '1');

    if( $station_id < 1 ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.145', 'msg'=>'Unable to add station'));
    }

    //
    // Commit the changes
    //
    $rc = qruqsp_core_dbTransactionCommit($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok', 'id'=>$station_id);
}
?>
