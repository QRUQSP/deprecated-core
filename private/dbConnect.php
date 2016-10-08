<?php
//
// Description
// -----------
// This function will check for an open connection to the database, 
// and if not return a new connection.
//
// Arguments
// ---------
// qruqsp:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
//
function qruqsp_core_dbConnect(&$q, $module) {
    // 
    // Check for required $q variables
    //
    if( !is_array($q['config']['qruqsp.core']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.18', 'msg'=>'Internal Error', 'pmsg'=>'$q variable not defined'));
    }

    //
    // Get the database name for the module specified.  If the
    // module does not have a database specified, then open
    // the default core database
    //
    $database_name = '';
    if( isset($q['config'][$module]['database']) ) {
        $database_name = $q['config'][$module]['database'];
    } elseif( isset($q['config']['qruqsp.core']['database']) ) {
        $database_name = $q['config']['qruqsp.core']['database'];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.19', 'msg'=>'Internal Error', 'pmsg'=>'database name not default for requested module'));
    }

    //
    // Check if database connection is already open
    //
    if( isset($q['databases'][$database_name]['connection']) && is_object($q['databases'][$database_name]['connection']) ) {
        return array('stat'=>'ok', 'dh'=>$q['databases'][$database_name]['connection']);
    }

    //
    // Check if database has been specified in config file, and setup in the databases array.
    //
    if( !is_array($q['databases'][$database_name]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.22', 'msg'=>'Internal Error', 'pmsg'=>'database name not specified in config.ini'));
    }

    //
    // Get connection information
    //
    if( !isset($q['config']['qruqsp.core']['database.' . $database_name . '.hostname'])
        || !isset($q['config']['qruqsp.core']['database.' . $database_name . '.username'])
        || !isset($q['config']['qruqsp.core']['database.' . $database_name . '.password'])
        || !isset($q['config']['qruqsp.core']['database.' . $database_name . '.database'])
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.23', 'msg'=>'Internal configuration error', 'pmsg'=>"database credentials not specified for the module '$module'"));
    }

    //
    // Open connection to the database requested, and ensure a new connection is opened (TRUE).
    //
    $q['databases'][$database_name]['connection'] = mysqli_connect(
        $q['config']['qruqsp.core']['database.' . $database_name . '.hostname'],
        $q['config']['qruqsp.core']['database.' . $database_name . '.username'],
        $q['config']['qruqsp.core']['database.' . $database_name . '.password'], 
        $q['config']['qruqsp.core']['database.' . $database_name . '.database']);

    if( $q['databases'][$database_name]['connection'] == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.24', 'msg'=>'Database error', 'pmsg'=>"Unable to connect to database '$database_name' for '$module'"));
    }

    return array('stat'=>'ok', 'dh'=>$q['databases'][$database_name]['connection']);
}
?>
