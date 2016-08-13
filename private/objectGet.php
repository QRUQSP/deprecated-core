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
function qruqsp_core_objectGet(&$q, $station_id, $obj_name, $oid) {
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryIDTree');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectLoad');

    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);

    //
    // Load the object file
    //
    $rc = qruqsp_core_objectLoad($q, $obj_name);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $o = $rc['object'];
    $m = "$pkg.$mod";

    qruqsp_core_loadMethod($q, 'qruqsp', 'businesses', 'private', 'intlSettings');
    $rc = qruqsp_businesses_intlSettings($q, $station_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
//  $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//  $intl_currency = $rc['settings']['intl-default-currency'];

    qruqsp_core_loadMethod($q, 'qruqsp', 'users', 'private', 'datetimeFormat');
    $datetime_format = qruqsp_users_datetimeFormat($q, 'php');

    // 
    // Build the query to get the object
    //
    $strsql = "SELECT id ";
    $fields = array();
    $utctotz = array();
    foreach($o['fields'] as $field => $options) {
        $strsql .= ", " . $field . " ";
        if( isset($options['type']) && $options['type'] == 'utcdatetime' ) {
            $utctotz[$field] = array('timezone'=>$intl_timezone, 'format'=>$datetime_format);
        }
    }
    $strsql .= "FROM " . $o['table'] . " "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $oid) . "' "
        . "AND station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "";
    $container = isset($o['o_container'])?$o['o_container']:'objects';
    $name = isset($o['o_name'])?$o['o_name']:'object';
    $rc = qruqsp_core_dbHashQueryIDTree($q, $strsql, $pkg . '.' . $mod, array(
        array('container'=>$container, 'fname'=>'id',
            'fields'=>array_keys($o['fields']),
            'utctotz'=>$utctotz,
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc[$container][$oid]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.76', 'msg'=>"Unable to load the " . lowercase($o['name']) . " you requested."));
    }
    $object = $rc[$container][$oid];

    $rsp = array('stat'=>'ok');
    $rsp[$name] = $object;

    return $rsp;
}
?>
