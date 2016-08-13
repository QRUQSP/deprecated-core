<?php
//
// Description
// -----------
// This function will retrieve an object to the database.
//
// Arguments
// ---------
// qruqsp:
// pkg:         The package the object is a part of.
// mod:         The module the object is a part of.
// obj:         The name of the object in the module.
// args:        The arguments passed to the API.
//
// Returns
// -------
//
function qruqsp_core_objectGetSubs(&$q, $station_id, $obj_name, $oid, $sub_obj_name) {
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryTree');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectLoad');
    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);
    list($s_pkg, $s_mod, $s_obj) = explode('.', $sub_obj_name);

    //
    // Load the object file
    //
    $rc = qruqsp_core_objectLoad($q, $obj_name);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $o = $rc['object'];
    $m = "$pkg.$mod";

    $rc = qruqsp_core_objectLoad($q, $sub_obj_name);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $s_o = $rc['object'];
    $s_m = "$pkg.$mod";


    // 
    // Build the query to get the object
    //
    $strsql = "SELECT id ";
    $fields = array();
    $obj_strsql = '';
    foreach($s_o['fields'] as $field => $options) {
        $strsql .= ", " . $field . " ";
        if( isset($options['ref']) && $options['ref'] == $obj_name ) {
            $obj_strsql = "AND $field = '" . qruqsp_core_dbQuote($q, $oid) . "' "; 
        }
    }
    $strsql .= "FROM " . $s_o['table'] . " "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . $obj_strsql
        . "";
    if( isset($s_o['listsort']) && $s_o['listsort'] != '' ) {
        $strsql .= "ORDER BY " . $s_o['listsort'] . " ";
    }
    $container = isset($s_o['o_container'])?$s_o['o_container']:'objects';
    $name = isset($s_o['o_name'])?$s_o['o_name']:'object';
    $rc = qruqsp_core_dbHashQueryTree($q, $strsql, $s_m, array(
        array('container'=>$container, 'fname'=>'id', 'name'=>$name,
            'fields'=>array_merge(array('id'), array_keys($s_o['fields']))),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp = array('stat'=>'ok');
    if( isset($rc[$container]) ) {
        $rsp[$container] = $rc[$container];
    } else {
        $rsp[$container] = array();
    }

    return $rsp;
}
?>
