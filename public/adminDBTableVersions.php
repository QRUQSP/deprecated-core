<?php
//
// Description
// -----------
// This function will get the current table versions from the database comments sections
// and compare with what's in the .schema file.  If upgrades need to happen, then the
// qruqsp.core.upgradeDb API call be made.
//
// *alert* When the database is split between database installs, this file will need to be modified.
//
// Arguments
// ---------
// api_key:
// auth_token:
//
function qruqsp_core_adminDBTableVersions($q) {
    //
    // Check access restrictions to checkAPIKey
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.checkDbTableVersions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbGetTables');
    $rc = qruqsp_core_dbGetTables($q);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tables = $rc['tables'];

    $strsql = "SHOW TABLE STATUS";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashIDQuery');
    $rc = qruqsp_core_dbHashIDQuery($q, $strsql, 'qruqsp.core', 'tables', 'Name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    foreach($rc['tables'] as $table_name => $table) {
        if( isset($tables[$table_name]) ) {
            if( preg_match('/(v[0-9]+\.[0-9]+)([^0-9]|$)/i', $table['Comment'], $matches) ) {
                $tables[$table_name]['database_version'] = $matches[1];
            }
        }
    }
    
    foreach($tables as $table_name => $table) {
        $schema = file_get_contents($q['config']['core']['root_dir'] . '/' . $table['package'] . '-mods/' . $table['module']   . "/db/$table_name.schema");
        if( preg_match('/comment=\'(v[0-9]+\.[0-9]+)\'/i', $schema, $matches) ) {
            $tables[$table_name]['schema_version'] = $matches[1];
        }
    }

    return array('stat'=>'ok', 'tables'=>$tables);
}
?>
