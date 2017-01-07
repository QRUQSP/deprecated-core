<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
function qruqsp_core_stationUpdate(&$q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
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
    $rc = qruqsp_core_checkAccess($q, $args['station_id'], 'qruqsp.core.stationUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($args['name']) ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'makePermalink');
        $args['permalink'] = qruqsp_core_makePermalink($q, $args['name']);
    }

    //
    // Get the existing station
    //
    $strsql = "SELECT id, name, category, permalink "
        . "FROM qruqsp_core_stations "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' "
        . "";
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'station');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['station']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.157', 'msg'=>'Station not found'));
    }
    $station = $rc['station'];

    //
    // Start transaction
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionStart');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionRollback');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionCommit');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');
    $rc = qruqsp_core_dbTransactionStart($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the station in the database
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');
    $strsql = "";
    $fields = array('name', 'category', 'permalink');
    foreach($fields as $field) {
        if( isset($args[$field]) && $args[$field] != $station[$field] ) {
            $strsql .= ", $field = '" . qruqsp_core_dbQuote($q, $args[$field]) . "'";
            qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 
                2, 'qruqsp_core_stations', '', $field, $args[$field]);
        }
    }

    //
    // Always update last_updated for sync purposes
    //
    if( $strsql != '' ) {
        $strsql = "UPDATE qruqsp_core_stations SET last_updated = UTC_TIMESTAMP()" . $strsql 
            . " WHERE id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' ";
        $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
        if( $rc['stat'] != 'ok' ) {
            qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = qruqsp_core_dbTransactionCommit($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the station modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'updateModuleChangeDate');
    qruqsp_core_updateModuleChangeDate($q, $args['station_id'], 'qruqsp', 'core');

    return array('stat'=>'ok');
}
?>
