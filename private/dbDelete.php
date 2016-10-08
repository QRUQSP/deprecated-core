<?php
//
// Description
// -----------
// This function will run an delete query against the database. 
// This function is a placeholder and just a passthrough to dbUpdate
//
// Arguments
// ---------
// q:
// strsql:              The SQL string that will delete row(s) from a table.
// module:              The name of the module for the transaction, which should include the 
//                      package in dot notation.  Example: qruqsp.artcatalog
//
function qruqsp_core_dbDelete(&$q, $strsql, $module) {
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    return qruqsp_core_dbUpdate($q, $strsql, $module);
}
?>
