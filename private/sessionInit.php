<?php
//
// Description
// -----------
// This function will initialize the session variable, but will not
// open an existing or start a new session.
//
// Arguments
// ---------
// qruqsp:
//
function qruqsp_core_sessionInit(&$q) {

    $q['session'] = array();

    //
    // Set default session variables
    //
    $q['session']['api_key'] = '';
    $q['session']['auth_token'] = '';
    $q['session']['change_log_id'] = '';

    //
    // Create a structure to store the user information
    //
    $q['session']['user'] = array('id'=>0, 'perms'=>0);

    return array('stat'=>'ok');
}
?>
