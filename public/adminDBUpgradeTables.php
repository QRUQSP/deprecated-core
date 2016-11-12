<?php
//
// Description
// -----------
// This function will check for and upgrade any tables which are out of date.
//
// Arguments
// ---------
// api_key:
// auth_token:
//
function qruqsp_core_adminDBUpgradeTables($q) {
    //
    // Check access restrictions to monitorChangeLogs
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.upgradeDb');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpgradeTables');
    return qruqsp_core_dbUpgradeTables($q);
}
?>
