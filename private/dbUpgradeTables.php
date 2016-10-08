<?php
//
// Description
// ===========
// This function checks all the database tables to see if they need upgraded, and runs the upgrade.
//
// FIXME: When the database is split between database installs, this file will need to be modified.
//
// Arguments
// ========
// q:
//
// Returns
// =======
//  <tables>
//      <table_name name='qruqsp_core_users' />
//  </tables>
//
function qruqsp_core_dbUpgradeTables(&$q) {
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbGetTables');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashIDQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpgradeTable');

    $rc = qruqsp_core_dbGetTables($q);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tables = $rc['tables'];

    // FIXME: If in multiple databases, this script will need to be updated.

    $strsql = "SHOW TABLE STATUS";
    $rc = qruqsp_core_dbHashIDQuery($q, $strsql, 'qruqsp.core', 'tables', 'Name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($rc['tables']) ) {
        foreach($rc['tables'] as $table_name => $table) {
            if( isset($tables[$table_name]) ) {
                if( preg_match('/(v[0-9]+\.[0-9]+)([^0-9]|$)/i', $table['Comment'], $matches) ) {
                    $tables[$table_name]['database_version'] = $matches[1];
                }
            }
        }
    }

    foreach($tables as $table_name => $table) {
        $schema = file_get_contents($q['config']['qruqsp.core']['root_dir'] . '/' . $table['package'] . '-mods/' . $table['module'] . "/db/$table_name.schema");
        if( preg_match('/comment=\'(v[0-9]+\.[0-9]+)\'/i', $schema, $matches) ) {
            $new_version = $matches[1];
            if( $new_version != $tables[$table_name]['database_version'] ) {
                $rc = qruqsp_core_dbUpgradeTable($q, $tables[$table_name]['package'], $tables[$table_name]['module'], $table_name, 
                    $tables[$table_name]['database_version'], $new_version);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
