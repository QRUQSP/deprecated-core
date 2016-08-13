<?php
//
// Description
// ===========
// This function will upgrade the tables to the current versions in 
// the qruqsp-modules directory.
//
// Arguments
// =========
// q:
// package:             The name of the package the table is contained within.
// module:              The name of the module the table is contained within.
//                      **note** Unlike other db calls, this should not contain the package name.
// table:               The full name of the table to be upgraded.
// old_version:         The current version of the table within the database.
// new_version:         The new version of the table to be upgraded to.
//
function qruqsp_core_dbUpgradeTable(&$q, $package, $module, $table, $old_version, $new_version) {

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbConnect');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'logMsg');
    $rc = qruqsp_core_dbConnect($q, "$package.$module");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if the table exists
    //
    if( $old_version == '-' ) {
        $schema = file_get_contents($q['config']['qruqsp.core']['root_dir'] . '/' . $package . '-mods/' . $module . "/db/$table.schema");
        $rc = qruqsp_core_dbUpdate($q, $schema, "$package.$module");
        return $rc;
    }

    //
    // Check for upgrade files
    //
    $old_major = '';
    $old_minor = '';
    if( preg_match('/v([0-9]+)\.([0-9]+)$/', $old_version, $matches) ) {
        $old_major = $matches[1];
        $old_minor = $matches[2];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.20', 'msg'=>"Unrecognized old table version: $old_version"));
    }

    $new_major = '';
    $new_minor = '';
    if( preg_match('/v([0-9])+\.([0-9]+)$/', $new_version, $matches) ) {
        $new_major = $matches[1];
        $new_minor = $matches[2];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.21', 'msg'=>"Unrecognized new table version: $new_version"));
    }

    for($i=$old_major;$i<=$new_major;$i++) {
        //
        // Decide where to begin and end the minor number search.  This allows
        // for upgrades through major versions
        //
        if( $old_major == $new_major ) {
            $start_minor = $old_minor;
            $end_minor = $new_minor;
        } elseif( $i == $old_major ) {
            $start_minor = $old_minor;
            $end_minor = 99;
        } elseif( $i == $new_major ) {
            $start_minor = 0;
            $end_minor = $new_minor;
        }

        qruqsp_core_logMsg($q, 1, "Upgrading table $table from: $i.$start_minor to $i.$end_minor");
        for($j=$start_minor+1;$j<=$end_minor;$j++) {
            $filename = $q['config']['qruqsp.core']['root_dir'] . sprintf("/$package-mods/$module/db/$table.$i.%02d.upgrade", $j);
            if( file_exists($filename) ) {
                $schema = file_get_contents($filename);
                $sqls = preg_split('/;\s*$/m', $schema);
                foreach($sqls as $strsql) {
                    if( preg_match('/ALTER TABLE/', $strsql) 
                        || preg_match('/DROP INDEX/', $strsql)
                        || preg_match('/CREATE UNIQUE INDEX/', $strsql)
                        || preg_match('/CREATE INDEX/', $strsql)
                        || preg_match('/UPDATE /', $strsql)
                        ) {
                        $rc = qruqsp_core_dbUpdate($q, $strsql, "$package.$module");
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                    }
                }
            }
        }
    }

    return $rc;
}
?>
