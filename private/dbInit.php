<?php
//
// Description
// -----------
// This function will initialize the database structure for the $q variable.
//
// Info
// ----
// Status:      beta
//
// Arguments
// ---------
// qruqsp:
//
//
function qruqsp_core_dbInit(&$q) {

    $q['databases'] = array();

    if( !isset($q['config']['qruqsp.core']['database.names']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.14', 'msg'=>'Internal configuration error', 'pmsg'=>'missing core.database.name from config.'));
    }

    $databases = preg_split('/\s*\,\s*/', $q['config']['qruqsp.core']['database.names']);

    foreach($databases as $db) {
        $q['databases'][$db] = array();
    }

    //
    // Check if core database has been defined
    //
    if( !isset($q['databases']['qruqsp.core']) || !is_array($q['databases']['qruqsp.core']) ) {
        $q['databases']['qruqsp.core'] = array();
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbConnect');

    //
    // Connect to the core, we ALWAYS need this connection, might as well open it now
    // and verify it's working before going further in code
    //
    $rc = qruqsp_core_dbConnect($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
