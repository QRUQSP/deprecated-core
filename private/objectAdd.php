<?php
//
// Description
// -----------
// This function will add an object to the database.
//
// Arguments
// ---------
// qruqsp:
//
// Returns
// -------
//
function qruqsp_core_objectAdd(&$q, $station_id, $obj_name, $args, $tmsupdate=0x07) {
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
    // Check if UUID was passed
    //
    if( !isset($args['uuid']) || $args['uuid'] == '' ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUUID');
        $rc = qruqsp_core_dbUUID($q, $m);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.60', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
        }
        $args['uuid'] = $rc['uuid'];
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
    // Build the SQL string to insert object
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    $strsql = "INSERT INTO " . $o['table'] . " (uuid, station_id, ";
    $values = "'" . qruqsp_core_dbQuote($q, $args['uuid']) . "', "
        . "'" . qruqsp_core_dbQuote($q, $station_id) . "', ";
    foreach($o['fields'] as $field => $options) {
        $strsql .= $field . ', ';
        if( isset($args[$field]) ) {
            $values .= "'" . qruqsp_core_dbQuote($q, $args[$field]) . "', ";
        } elseif( isset($options['default']) ) {
            $args[$field] = $options['default'];
            $values .= "'" . qruqsp_core_dbQuote($q, $options['default']) . "', ";
        } else {
            if( ($tmsupdate&0x01) == 1 ) { qruqsp_core_dbTransactionRollback($q, $m); }
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.61', 'msg'=>'Missing object field: ' . $field));
        }
    }
    $strsql .= "date_added, last_updated) VALUES (" . $values . " UTC_TIMESTAMP(), UTC_TIMESTAMP())";

    //
    // Insert the object
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
    $rc = qruqsp_core_dbInsert($q, $strsql, $m);
    if( $rc['stat'] != 'ok' ) { 
        if( ($tmsupdate&0x01) == 1 ) { qruqsp_core_dbTransactionRollback($q, $m); }
        return $rc;
    }
    if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
        if( ($tmsupdate&0x01) == 1 ) { qruqsp_core_dbTransactionRollback($q, $m); }
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.62', 'msg'=>'Unable to add object'));
    }
    $insert_id = $rc['insert_id'];

    //
    // Add the history and check for foreign module ref table
    //
    if( isset($o['history_table']) ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectRefAdd');
        qruqsp_core_dbAddModuleHistory($q, $m, $o['history_table'], $station_id, 1, $o['table'], $insert_id, 'uuid', $args['uuid']);
        foreach($o['fields'] as $field => $options) {
            //
            // Some field we don't store the history for, like binary content of files
            //
            if( !isset($options['history']) || $options['history'] == 'yes' ) {
                qruqsp_core_dbAddModuleHistory($q, $m, $o['history_table'], $station_id,
                    1, $o['table'], $insert_id, $field, $args[$field]);
            }
            //
            // Check if this column is a reference to another modules object, 
            // and see if there should be a reference added
            //
            if( isset($options['ref']) && $options['ref'] != '' && $args[$field] != '' && $args[$field] > 0 ) {
                $rc = qruqsp_core_objectRefAdd($q, $station_id, $options['ref'], array(
                    'ref_id'=>$args[$field],        // The remote ID (other modules object id)
                    'object'=>$obj_name,            // The local object ref (this objects ref)
                    'object_id'=>$insert_id,        // The local object ID
                    'object_field'=>$field,         // The local object table field name of remote ID
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
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
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'updateModuleChangeDate');
        qruqsp_core_updateModuleChangeDate($q, $station_id, $pkg, $mod);
    }

    //
    // Add the change to the sync queue
    //
    if( ($tmsupdate&0x04) == 4 ) {
        $q['syncqueue'][] = array('push'=>$obj_name, 'args'=>array('id'=>$insert_id));
    }   

    return array('stat'=>'ok', 'id'=>$insert_id);
}
?>
