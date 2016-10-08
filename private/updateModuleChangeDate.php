<?php
//
// Description
// -----------
// This function will update the modules last change date.  This should happen
// whenever data is updated within a module, so when a sync happens, the
// last change date for the module is compared.
//
// Arguments
// ---------
// qruqsp:
// station_id:          The ID of the station to update the module for.
// package:             The package the module is contained within.
// module:              The module that needs to be updated.
//
// Returns
// -------
// <rsp stat='ok' />
//
function qruqsp_core_updateModuleChangeDate($q, $station_id, $package, $module) {

    //
    // If station_id is passed as zero, then don't updated the module last_change field
    //
    if( $station_id == 0 ) {
        return array('stat'=>'ok');
    }

    //
    // Update the module.  Assume the module has been added to the qruqsp_core_station_modules table,
    // if not run an insert.
    //
    $strsql = "UPDATE qruqsp_core_station_modules "
        . "SET last_change = UTC_TIMESTAMP() "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "AND package = '" . qruqsp_core_dbQuote($q, $package) . "' "
        . "AND module = '" . qruqsp_core_dbQuote($q, $module) . "' "
        . "";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    $rc = qruqsp_core_dbUpdate($q, $strsql, "$package.$module");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if a row was updated, if not, run an insert
    //
    if( isset($rc['num_affected_rows']) && $rc['num_affected_rows'] == 0 ) {
        $strsql = "INSERT INTO qruqsp_core_station_modules (station_id, package, module, "
            . "status, date_added, last_updated, last_change) VALUES ("
            . "'" . qruqsp_core_dbQuote($q, $station_id) . "', "
            . "'" . qruqsp_core_dbQuote($q, $package) . "', "
            . "'" . qruqsp_core_dbQuote($q, $module) . "', "
            . "2, UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP() "
            . ")";
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
        $rc = qruqsp_core_dbInsert($q, $strsql, "$package.$module");
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return $rc;
}
?>
