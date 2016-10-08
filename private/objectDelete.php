<?php
//
// Description
// -----------
// This function will add an object to the database.
//
// Arguments
// ---------
// qruqsp:
// pkg:         The package the object is a part of.
// mod:         The module the object is a part of.
// obj:         The name of the object in the module.
// args:        The arguments passed to the API.
// tmsupdate:   The default is yes, and it will create a transaction,
//              update the module last_change date, and insert
//              into the sync queue.
//              
//              0x01 - run in a transaction
//              0x02 - update the module last change date
//              0x04 - Insert into sync queue
//
// Returns
// -------
//
function qruqsp_core_objectDelete(&$q, $station_id, $obj_name, $oid, $ouuid, $tmsupdate=0x07) {
    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);

    //
    // Load the object file
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectLoad');
    $rc = qruqsp_core_objectLoad($q, $obj_name);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $o = $rc['object'];
    $m = "$pkg.$mod";

    //
    // If the object uuid is not specified, lookup in the table first
    //
    if( $ouuid == NULL ) {
        $strsql = "SELECT uuid "
            . "FROM " . $o['table'] . " "
            . " WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
            . "AND id = '" . qruqsp_core_dbQuote($q, $oid) . "' "
            . "";
        $rc = qruqsp_core_dbHashQuery($q, $strsql, $m, 'object');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['object']) || !isset($rc['object']['uuid']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.65', 'msg'=>'Unable to lookup UUID for ' . $obj_name));
        }
        $ouuid = $rc['object']['uuid'];
    }

    //
    // Start transaction
    //
    if( ($tmsupdate&0x01) == 1 ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionStart');
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionRollback');
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionCommit');
        $rc = qruqsp_core_dbTransactionStart($q, $m);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Build the SQL string to update object
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectRefClear');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');
    $strsql = "DELETE FROM " . $o['table'] . " "
        . " WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "AND id = '" . qruqsp_core_dbQuote($q, $oid) . "' "
        . "";

    //
    // Delete the object
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDelete');
    $rc = qruqsp_core_dbDelete($q, $strsql, $m);
    if( $rc['stat'] != 'ok' ) { 
        if( ($tmsupdate&0x01) == 1 ) { qruqsp_core_dbTransactionRollback($q, $m); }
        return $rc;
    }
    if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
        if( ($tmsupdate&0x01) == 1 ) { qruqsp_core_dbTransactionRollback($q, $m); }
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.67', 'msg'=>'Unable to delete object'));
    }

    qruqsp_core_dbAddModuleHistory($q, $m, $o['history_table'], $station_id,
        3, $o['table'], $oid, '*', ''); 

    //
    // Check if any fields are references to other modules
    //
    foreach($o['fields'] as $field => $options) {
        //
        // Check if this column is a reference to another modules object, 
        // and see if there should be a reference added
        //
        if( isset($options['ref']) && $options['ref'] != '' ) {
            $rc = qruqsp_core_objectRefClear($q, $station_id, $options['ref'], array(
                'object'=>$obj_name,            // The local object ref (this objects ref)
                'object_id'=>$oid,      // The local object ID
                ));
            if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
                return $rc;
            }
        }
    }

    //
    // Commit the transaction
    //
    if( ($tmsupdate&0x01) == 1 ) {
        $rc = qruqsp_core_dbTransactionCommit($q, $m);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Update the last change date of the module
    //
    if( ($tmsupdate&0x02) == 2 ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'updateModuleChangeDate');
        qruqsp_core_updateModuleChangeDate($q, $station_id, $pkg, $mod);
    }

    //
    // Add the change to the sync queue
    //
    if( ($tmsupdate&0x04) == 4 ) {
        $q['syncqueue'][] = array('push'=>$obj_name, 'args'=>array('delete_uuid'=>$ouuid, 'delete_id'=>$oid));
    }

    return array('stat'=>'ok');
}
?>
