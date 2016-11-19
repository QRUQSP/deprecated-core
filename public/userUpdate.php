<?php
//
// Description
// -----------
// This method will update details for a user.
//
// Info
// ----
// publish:         yes
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_core_userUpdate(&$q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        'callsign'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Call Sign'), 
        'license'=>array('required'=>'no', 'blank'=>'no', 'name'=>'License'), 
        'display_name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Display Name'), 
        'username'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Username'), 
        'email'=>array('required'=>'no', 'blank'=>'no', 'name'=>'email'), 
        'timeout'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Timeout'), 
        'perms'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permissions'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.userUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
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
    // Check username does not already exist
    //
    if( isset($args['username']) && $args['username'] != '' ) {
        $strsql = "SELECT id "
            . "FROM qruqsp_core_users "
            . "WHERE username = '" . qruqsp_core_dbQuote($q, $args['username']) . "' "
            . "AND id <> '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' "
            . "";
        $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'user');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num_rows']) && $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.3', 'msg'=>'Username already taken'));
        }
    }

    //
    // Check the email doesn't exist
    //
    if( isset($args['email']) && $args['email'] != '' ) {
        $strsql = "SELECT id "
            . "FROM qruqsp_core_users "
            . "WHERE email = '" . qruqsp_core_dbQuote($q, $args['email']) . "' "
            . "AND id <> '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' "
            . "";
        $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'user');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num_rows']) && $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.106', 'msg'=>'Email already taken'));
        }
    }

    //
    // Check if name or tagline was specified
    //
    $fields = array('callsign', 'license', 'display_name', 'username', 'email', 'timeout', 'perms');

    $strsql = "";
    foreach($fields as $field) {
        if( isset($args[$field]) ) {
            $strsql .= ", $field = '" . qruqsp_core_dbQuote($q, $args[$field]) . "' ";
            qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', 0, 2, 'qruqsp_core_users', 
                $args['user_id'], $field, $args[$field]);
        }
    }

    //
    // Always update at least the last_updated field so it will be transfered with sync
    //
    $strsql = "UPDATE qruqsp_core_users SET last_updated = UTC_TIMESTAMP()" . $strsql 
        . " WHERE id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' ";
    $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.118', 'msg'=>'Unable to update user', 'err'=>$rc['err']));
    }
    
    //
    // Allowed user detail keys 
    //
    $allowed_keys = array(
        'settings.time_format',
        'settings.date_format',
        'settings.datetime_format',
        'ui-history-date-display',
        );
    foreach($q['request']['args'] as $arg_name => $arg_value) {
        if( in_array($arg_name, $allowed_keys) ) {
            $strsql = "INSERT INTO qruqsp_core_user_details (user_id, detail_key, detail_value, date_added, last_updated) "
                . "VALUES ('" . qruqsp_core_dbQuote($q, $args['user_id']) . "', "
                . "'" . qruqsp_core_dbQuote($q, $arg_name) . "', "
                . "'" . qruqsp_core_dbQuote($q, $arg_value) . "', "
                . "UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                . "ON DUPLICATE KEY UPDATE detail_value = '" . qruqsp_core_dbQuote($q, $arg_value) . "' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "";
            $rc = qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
            if( $rc['stat'] != 'ok' ) {
                qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
                return $rc;
            }
            qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', 0, 2, 'qruqsp_user_details', $args['user_id'], $arg_name, $arg_value);
        }
    }

    //
    // Commit the database changes
    //
    $rc = qruqsp_core_dbTransactionCommit($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.135', 'msg'=>'Unable to update user detail', 'err'=>$rc['err']));
    }

    //
    // Update the last_change date in the station modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'updateModuleChangeDate');

    //
    // Update the last_updated of the user
    //
    $strsql = "UPDATE qruqsp_core_users SET last_updated = UTC_TIMESTAMP() "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' "
        . "";
    $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of stations this user is part of, and replicate that user for that station
    //
    $strsql = "SELECT id, station_id "
        . "FROM qruqsp_core_station_users "
        . "WHERE qruqsp_core_station_users.user_id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' "
        . "";
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'station');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $stations = $rc['rows'];
    foreach($stations as $rid => $row) {
        qruqsp_core_updateModuleChangeDate($q, $row['station_id'], 'qruqsp', 'core');
        $q['syncqueue'][] = array('push'=>'qruqsp.core.stationuser', 'args'=>array('id'=>$row['id']));
    }

    return array('stat'=>'ok');
}
?>
