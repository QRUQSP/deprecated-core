<?php
//
// Description
// -----------
// This function will return a list of tags available for a stations module.
//
// Arguments
// ---------
// qruqsp:
// module:              The package.module the tag is located in.
// station_id:          The ID of the stations to get the available tags for.
// main_table:          The main table containing the station items.
// main_key_name:       The key field name in the main table.  This is used to link
//                      the main table with the tags table 'table'.
// table:               The database table that stores the tags.
// key_name:            The key field in the tags table that links back to the main table.
// type:                The type of the tag.  If passed as 0, then return all available tags an their type.
//
//                      0 - return all tags.
//                      1 - List
//                      2 - Category **future**
// 
// Returns
// -------
// <rsp stat="ok" />
//
function qruqsp_core_tagsList($q, $module, $station_id, $table, $type) {

    // Required functions
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');

    $strsql = "SELECT DISTINCT CONCAT_WS('-', tag_type, tag_name) AS fname, tag_type, tag_name "
        . "FROM $table "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "";
    if( $type > 0 ) {
        $strsql .= "AND $table.tag_type = '" . qruqsp_core_dbQuote($q, $type) . "' ";
    }
    $strsql .= "ORDER BY tag_name ";

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = qruqsp_core_dbHashQueryArrayTree($q, $strsql, $module, array(
        array('container'=>'tags', 'fname'=>'fname', 'fields'=>array('type'=>'tag_type', 'name'=>'tag_name')),
        ));

    return $rc;
}
?>
