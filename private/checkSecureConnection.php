<?php
//
// Description
// ===========
// This function will check to make sure the request is coming over an SSL connection or being run from the command line.
//
// Arguments
// =========
// q:
//
//
function qruqsp_core_checkSecureConnection(&$q) {

    //
    // The HTTP_CLUSTER_HTTPS setting is used by rackspace to let the script know it's running
    // behind a HTTPS cluster connection.
    //
    if( isset($_SERVER['HTTP_CLUSTER_HTTPS']) && $_SERVER['HTTP_CLUSTER_HTTPS'] == 'on') {
        return array('stat'=>'ok');
    }

    //
    // If the connection was to port 443
    //
    if( isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ) {
        return array('stat'=>'ok');
    }

    if( php_sapi_name() == 'cli' ) {
        return array('stat'=>'ok');
    }

    //
    // If the override has been set in the config, then don't worry about the check.
    // *note* This is good for testing, but should never be used in production
    //
    if( isset($q['config']['core']['ssl']) && $q['config']['core']['ssl'] == 'off' ) {
        return array('stat'=>'ok');
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.28', 'msg'=>'Unsecure connection'));
}
?>
