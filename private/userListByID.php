<?php
//
// Description
// -----------
// This function will return the list of users based on a list of IDs.  The returned
// list will be sorted by ID for each lookup.
//
// Arguments
// ---------
// qruqsp:
// container_name:      The name of the container to store the user list in.
// ids:                 The user IDs to be looked up in the database.
//
// Returns
// -------
//
function qruqsp_core_userListByID($q, $container_name, $ids, $fields) {

    if( !is_array($ids) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.95', 'msg'=>'Invalid list of users'));
    }

    if( count($ids) < 1 ) {
        return array('stat'=>'ok', 'users'=>array());
    }

    //
    // Query for the station users
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuoteIDs');
    if( $fields == 'all' ) {
        $strsql = "SELECT id, email, callsign, display_name, perms "
            . "FROM qruqsp_core_users ";
    } elseif( $fields == 'display_name' ) {
        $strsql = "SELECT id, display_name "
            . "FROM qruqsp_core_users ";
    }
    $strsql .= "WHERE id IN (" . qruqsp_core_dbQuoteIDs($q, array_unique($ids)) . ") "
        . "ORDER BY id ";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashIDQuery');
    $rc = qruqsp_core_dbHashIDQuery($q, $strsql, 'qruqsp.users', $container_name, 'id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( in_array('-1', $ids) ) {
        $rc[$container_name]['-1'] = array('display_name'=>'Paypal IPN');
    }
    if( in_array('-2', $ids) ) {
        $rc[$container_name]['-2'] = array('display_name'=>'Website');
    }
    if( in_array('-3', $ids) ) {
        $rc[$container_name]['-3'] = array('display_name'=>'Ciniki Robot');
    }
    return $rc;
}
?>
