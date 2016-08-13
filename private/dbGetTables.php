<?php
//
// Description
// -----------
// This method will return the tables that are required for qruqsp and other packages installed.
//
// Arguments
// ---------
// qruqsp:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
//
function qruqsp_core_dbGetTables(&$q) {
    //
    // The following array is used but the upgrade process to tell what
    // tables are required, and their current versions
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'getModuleList');
    $rc = qruqsp_core_getModuleList($q);   
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Get the tables required for each module
    //
    $rsp = array();
    foreach($modules as $module) {
        $dir = $q['config']['qruqsp.core']['root_dir'] . '/' . $module['package'] . '-mods/' . $module['name'] . '/db/';
        if( !is_dir($dir) ) {
            continue;       // No tables
        }
        $dh = opendir($dir);
        while( false !== ($filename = readdir($dh))) {
            // Skip all files starting with ., and core
            // and other reserved named modules which should be always available
            if( $filename[0] == '.' ) {
                continue;
            }
            if( preg_match('/^(.*)\.schema$/', $filename, $matches) ) {
                $table = $matches[1];
                $rsp[$table] = array('package'=>$module['package'], 'module'=>$module['name'], 
                    'database_version'=>'-', 'schema_version'=>'-');
            }
        }

        closedir($dh);
    }
    
    return array('stat'=>'ok', 'tables'=>$rsp);
}
?>
