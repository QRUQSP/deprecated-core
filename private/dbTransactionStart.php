<?php
//
// Description
// -----------
// This function will start a new transaction, or turn off autocommit for the database.
//
// *note* All transaction control should be managed by the 
// public API method not any of the private ones.
//
// *alert* Currently all tables are in the same database,
// and there's no independence between Commit.  If you commit
// for one module, it will commit for all.  The $module variable
// is for the future.
//
// Arguments
// ---------
// qruqsp:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: qruqsp.artcatalog
//
function qruqsp_core_dbTransactionStart(&$q, $module) {

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbConnect');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuery');

    $rc = qruqsp_core_dbConnect($q, $module);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return qruqsp_core_dbQuery($q, "START TRANSACTION", $module);
}
?>
