<?php
//
// Description
// -----------
// This function will add an object to the database.
//
// Arguments
// ---------
// q:
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
function qruqsp_core_objectUpdate(&$q, $station_id, $obj_name, $oid, $args, $tmsupdate=0x07) {
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
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');
    $strsql = "UPDATE " . $o['table'] . " SET last_updated = UTC_TIMESTAMP()";
    $num_fields = 0;
    foreach($o['fields'] as $field => $options) {
        if( isset($args[$field]) ) {
            $num_fields++;
            $strsql .= ", $field = '" . qruqsp_core_dbQuote($q, $args[$field]) . "' ";
        }
    }
    $strsql .= " WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "AND id = '" . qruqsp_core_dbQuote($q, $oid) . "' "
        . "";

    //
    // Nothing to update, ignore
    //
    if( $num_fields == 0 ) {
        return array('stat'=>'ok');
    }

    //
    // Update the object
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    $rc = qruqsp_core_dbUpdate($q, $strsql, $m);
    if( $rc['stat'] != 'ok' ) { 
        if( ($tmsupdate&0x01) == 1 ) { qruqsp_core_dbTransactionRollback($q, $m); }
        return $rc;
    }
    if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
        if( ($tmsupdate&0x01) == 1 ) { 
            qruqsp_core_dbTransactionRollback($q, $m); 
        }
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.59', 'msg'=>'Unable to update object'));
    }

    //
    // Add the history
    //
    if( isset($o['history_table']) ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectRefAdd');
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectRefClear');
        foreach($o['fields'] as $field => $options) {
            if( isset($args[$field]) && (!isset($options['history']) || $options['history'] == 'yes') ) {
                qruqsp_core_dbAddModuleHistory($q, $m, $o['history_table'], $station_id,
                    2, $o['table'], $oid, $field, $args[$field]);
            }
            //
            // Check if this column is a reference to another modules object, 
            // and see if there should be a reference updated
            //
            if( isset($options['ref']) && $options['ref'] != '' && isset($args[$field]) ) {
                //
                // Clear any old refs
                //
                $rc = qruqsp_core_objectRefClear($q, $station_id, $options['ref'], array(
                    'object'=>$obj_name,            // The local object ref (this objects ref)
                    'object_id'=>$oid,              // The local object ID
                    ));
                if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
                    return $rc;
                }
                //
                // Add the new ref 
                //
                if( $args[$field] != '' && $args[$field] > 0 ) {
                    $rc = qruqsp_core_objectRefAdd($q, $station_id, $options['ref'], array(
                        'ref_id'=>$args[$field],        // The remote ID (other modules object id)
                        'object'=>$obj_name,            // The local object ref (this objects ref)
                        'object_id'=>$oid,      // The local object ID
                        'object_field'=>$field,         // The local object table field name of remote ID
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.64', 'msg'=>'Unable to add reference to ' . $options['ref'], 'err'=>$rc['err']));
                    }
                }
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
        qruqsp_core_loadMethod($q, 'qruqsp', 'businesses', 'private', 'updateModuleChangeDate');
        qruqsp_businesses_updateModuleChangeDate($q, $station_id, $pkg, $mod);
    }

    //
    // Add the change to the sync queue
    //
    if( ($tmsupdate&0x04) == 4 ) {
        $q['syncqueue'][] = array('push'=>$obj_name, 'args'=>array('id'=>$oid));
    }   

    return array('stat'=>'ok');
}
?>
