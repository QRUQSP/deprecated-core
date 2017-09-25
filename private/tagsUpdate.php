<?php
//
// Description
// -----------
// This function will update a list of tags.
//
// Arguments
// ---------
// qruqsp:
// module:              The package.module the tag is located in.
// object:              The object used to push changes in sync.
// table:               The database table that stores the tags.
// key_name:            The name of the ID field that links to the item the tag is for.
// key_value:           The value for the ID field.
// type:                The type of the tag. 
//
//                      0 - unknown
//                      1 - List
//                      2 - Category **future**
//
// list:                The array of tag names to add.
// 
// Returns
// -------
//
function qruqsp_core_tagsUpdate(&$q, $object, $station_id, $key_name, $key_value, $type, $list) {
    //
    // All arguments are assumed to be un-escaped, and will be passed through dbQuote to
    // ensure they are safe to insert.
    //

    // Required functions
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashIDQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDelete');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUUID');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectLoad');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'makePermalink');

    //
    // Don't worry about autocommit here, it's taken care of in the calling function
    //
    
    //
    // Load the object definition
    //
    $rc = qruqsp_core_objectLoad($q, $object);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $obj = $rc['object'];
    $module = $rc['pkg'] . '.' . $rc['mod'];

    //
    // Get the existing list of tags for the item
    //
    $strsql = "SELECT id, uuid, $key_name, tag_type AS type, tag_name AS name "
        . "FROM " . $obj['table'] . " "
        . "WHERE $key_name = '" . qruqsp_core_dbQuote($q, $key_value) . "' "
        . "AND tag_type = '" . qruqsp_core_dbQuote($q, $type) . "' "
        . "AND station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "";
    $rc = qruqsp_core_dbHashIDQuery($q, $strsql, $module, 'tags', 'name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tags']) || $rc['num_rows'] == 0 ) {
        $dbtags = array();
    } else {
        $dbtags = $rc['tags'];
    }

    //
    // Delete tags no longer used
    //
    foreach($dbtags as $tag_name => $tag) {
        if( !in_array($tag_name, $list, true) ) {
            //
            // The tag does not exist in the new list, so it should be deleted.
            //
            $strsql = "DELETE FROM " . $obj['table'] . " "
                . "WHERE id = '" . qruqsp_core_dbQuote($q, $tag['id']) . "' "
                . "AND station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
                . "";
            $rc = qruqsp_core_dbDelete($q, $strsql, $module);
            if( $rc['stat'] != 'ok' ) { 
                return $rc;
            }
            qruqsp_core_dbAddModuleHistory($q, $module, $obj['history_table'], $station_id,
                3, $obj['table'], $tag['id'], '*', '');

            //
            // Sync push delete
            //
            $q['syncqueue'][] = array('push'=>$object, 
                'args'=>array('delete_uuid'=>$tag['uuid'], 'delete_id'=>$tag['id']));
        }
    }

    //
    // Add new tags lists
    //
    foreach($list as $tag) {
        if( $tag != '' && !array_key_exists($tag, $dbtags) ) {
            //
            // Get a new UUID
            //
            $rc = qruqsp_core_dbUUID($q, $module);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $uuid = $rc['uuid'];

            if( isset($obj['fields']['permalink']) ) {
                //
                // Make the permalink
                //
                $permalink = qruqsp_core_makePermalink($q, $tag);

                // 
                // Setup the SQL statement to insert the new thread
                //
                $strsql = "INSERT INTO " . $obj['table'] . " (uuid, station_id, $key_name, tag_type, tag_name, "
                    . "permalink, date_added, last_updated) VALUES ("
                    . "'" . qruqsp_core_dbQuote($q, $uuid) . "', "
                    . "'" . qruqsp_core_dbQuote($q, $station_id) . "', "
                    . "'" . qruqsp_core_dbQuote($q, $key_value) . "', "
                    . "'" . qruqsp_core_dbQuote($q, $type) . "', "
                    . "'" . qruqsp_core_dbQuote($q, $tag) . "', "
                    . "'" . qruqsp_core_dbQuote($q, $permalink) . "', "
                    . "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
            } else {
                // 
                // Setup the SQL statement to insert the new thread
                //
                $strsql = "INSERT INTO " . $obj['table'] . " (uuid, station_id, $key_name, tag_type, tag_name, "
                    . "date_added, last_updated) VALUES ("
                    . "'" . qruqsp_core_dbQuote($q, $uuid) . "', "
                    . "'" . qruqsp_core_dbQuote($q, $station_id) . "', "
                    . "'" . qruqsp_core_dbQuote($q, $key_value) . "', "
                    . "'" . qruqsp_core_dbQuote($q, $type) . "', "
                    . "'" . qruqsp_core_dbQuote($q, $tag) . "', "
                    . "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
            }
            $rc = qruqsp_core_dbInsert($q, $strsql, $module);
            // 
            // Only return the error if it was not a duplicate key problem.  Duplicate key error
            // just means the tag name is already assigned to the item.
            //
            if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'qruqsp.core.73' ) {
                return $rc;
            }
            if( isset($rc['insert_id']) ) {
                $tag_id = $rc['insert_id'];
                //
                // Add history
                //
                qruqsp_core_dbAddModuleHistory($q, $module, $obj['history_table'], $station_id,
                    1, $obj['table'], $tag_id, 'uuid', $uuid);
                qruqsp_core_dbAddModuleHistory($q, $module, $obj['history_table'], $station_id,
                    1, $obj['table'], $tag_id, $key_name, $key_value);
                qruqsp_core_dbAddModuleHistory($q, $module, $obj['history_table'], $station_id,
                    1, $obj['table'], $tag_id, 'tag_type', $type);
                qruqsp_core_dbAddModuleHistory($q, $module, $obj['history_table'], $station_id,
                    1, $obj['table'], $tag_id, 'tag_name', $tag);
                //
                // Sync push
                //
                $q['syncqueue'][] = array('push'=>$module . '.' . $object, 'args'=>array('id'=>$tag_id));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
