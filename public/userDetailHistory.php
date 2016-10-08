<?php
//
// Description
// -----------
// This function will get the history of a field from the qruqsp_core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:                 The user ID to get the history detail for.
// field:                   The detail key to get the history for.
//
// Returns
// -------
//
function qruqsp_core_userDetailHistory($q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.userDetailHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }


    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbGetModuleHistory');
    if( $args['field'] == 'user.callsign' ) {
        return qruqsp_core_dbGetModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', 0, 'qruqsp_core_users', $args['user_id'], 'callsign');
    } elseif( $args['field'] == 'user.display_name' ) {
        return qruqsp_core_dbGetModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', 0, 'qruqsp_core_users', $args['user_id'], 'display_name');
    } elseif( $args['field'] == 'callsign'
        || $args['field'] == 'display_name'
        || $args['field'] == 'username'
        || $args['field'] == 'email'
        || $args['field'] == 'timeout' 
        ) {
        return qruqsp_core_dbGetModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', 0, 'qruqsp_core_users', $args['user_id'], $args['field']);
    }

    return qruqsp_core_dbGetModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', 0, 'qruqsp_core_user_details', $args['user_id'], $args['field']);
}
?>
