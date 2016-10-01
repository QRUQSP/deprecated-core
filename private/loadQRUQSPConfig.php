<?php
//
// Description
// -----------
// This function will load the config file
// and check for the core root dir variable is set.
//
// Info
// ----
// Status:          beta
// 
// Arguments
// ---------
// qruqsp:          The qruqsp data structure
// qruqsp_root:     The root directory for the qruqsp code.  This is where the config file will be.
// 
//
//
function qruqsp_core_loadQRUQSPConfig(&$q, $qruqsp_root) {
    
    $config_file = $qruqsp_root . "/qruqsp-api.ini";

    if( is_file($config_file) ) {
        $q['config'] = parse_ini_file($config_file, true);
    } else {
        return false;
    }

    if( $q['config'] == false ) {
        return false;
    }

    if( !isset($q['config']['qruqsp.core']) || !isset($q['config']['qruqsp.core']['root_dir']) ) {
        return false;
    }

    $q['config']['core'] = $q['config']['qruqsp.core'];

    return true;
}
?>
