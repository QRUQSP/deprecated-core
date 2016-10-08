<?php
//
// Description
// -----------
// This function will check if the user has access to a specified module and function.
//
// Arguments
// ---------
// qruqsp:
// station_id:         The station ID to check the session user against.
//
// Returns
// -------
// <rsp stat='ok' />
//
function qruqsp_core_checkAccess($q, $station_id, $method) {
    //
    // Methods which don't require authentication
    //
    $noauth_methods = array(
        'qruqsp.core.auth',
        'qruqsp.core.echoTest',
        'qruqsp.core.passwordRequestReset',
        'qruqsp.core.changeTempPassword',
        );
    if( in_array($method, $noauth_methods) ) {
        return array('stat'=>'ok');
    }

    //
    // Check the user is authenticated
    //
    if( !isset($q['session'])
        || !isset($q['session']['user'])
        || !isset($q['session']['user']['id'])
        || $q['session']['user']['id'] < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.90', 'msg'=>'User not authenticated'));
    }

    //
    // Check if the requested method is a public method
    //
    $public_methods = array(
        'qruqsp.core.getAddressCountryCodes',
        'qruqsp.core.parseDatetime',
        'qruqsp.core.parseDate',
        'qruqsp.core.logout',
        'qruqsp.core.changePassword',
        );
    if( in_array($method, $public_methods) ) {
        return array('stat'=>'ok');
    }

    //
    // If the user is a sysadmin, they have access to all functions
    //
    if( ($q['session']['user']['perms'] & 0x01) == 0x01 ) {
        return array('stat'=>'ok');
    }

    //
    // By default fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.91', 'msg'=>'Access denied'));
}
?>
