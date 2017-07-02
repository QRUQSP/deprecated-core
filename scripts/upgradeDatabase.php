<?php
//
// Description
// -----------
// This script checks for and upgrades the database. This is done as the last part of an upgrade.
//

//
// Initialize Ciniki by including the qruqsp_api.php
//
global $qruqsp_root;
$qruqsp_root = dirname(__FILE__);
if( !file_exists($qruqsp_root . '/qruqsp-api.ini') ) {
    $qruqsp_root = dirname(dirname(dirname(dirname(__FILE__))));
    }
    // loadMethod is required by all function to ensure the functions are dynamically loaded
    require_once($qruqsp_root . '/qruqsp-mods/core/private/loadMethod.php');
    require_once($qruqsp_root . '/qruqsp-mods/core/private/init.php');
    require_once($qruqsp_root . '/qruqsp-mods/core/private/checkModuleFlags.php');

    $rc = qruqsp_core_init($qruqsp_root, 'rest');
    if( $rc['stat'] != 'ok' ) {
        error_log("unable to initialize core");
    exit(1);
}

//
// Setup the $qruqsp variable to hold all things qruqsp.
//
$q = $rc['q'];
$q['session']['user']['id'] = -4;  // Setup to Ciniki Robot

print "Upgrading tables\n";
qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpgradeTables');
$rc = qruqsp_core_dbUpgradeTables($q);
if( $rc['stat'] != 'ok' ) {
    print_r($rc['err']);
}

exit(0);
?>
