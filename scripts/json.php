<?php
//
// Description
// -----------
// The json.php file is the entry point for the API through the REST protocol.
//

//
// Initialize QRUQSP by including the qruqsp_api.php
//
$start_time = microtime(true);
global $qruqsp_root;
$qruqsp_root = dirname(__FILE__);
if( !file_exists($qruqsp_root . '/qruqsp-api.ini') ) {
    $qruqsp_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($qruqsp_root . '/qruqsp-mods/core/private/loadMethod.php');
require_once($qruqsp_root . '/qruqsp-mods/core/private/init.php');
require_once($qruqsp_root . '/qruqsp-mods/core/private/checkSecureConnection.php');
require_once($qruqsp_root . '/qruqsp-mods/core/private/callPublicMethod.php');
require_once($qruqsp_root . '/qruqsp-mods/core/private/printHashToJSON.php');
require_once($qruqsp_root . '/qruqsp-mods/core/private/printResponse.php');
require_once($qruqsp_root . '/qruqsp-mods/core/private/syncQueueProcess.php');
require_once($qruqsp_root . '/qruqsp-mods/core/private/checkModuleFlags.php');

$rc = qruqsp_core_init($qruqsp_root, 'json');
if( $rc['stat'] != 'ok' ) {
    header("Content-Type: text/xml; charset=utf-8");
    qruqsp_core_printHashToJSON($rc);
    exit;
}

//
// Setup the $qruqsp variable to hold all things qruqsp.  
//
$q = $rc['qruqsp'];

//
// Ensure the connection is over SSL
//
$rc = qruqsp_core_checkSecureConnection($q);
if( $rc['stat'] != 'ok' ) {
    qruqsp_core_printResponse($q, $rc);
    exit;
}

//
// Parse arguments
//
require_once($qruqsp_root . '/qruqsp-mods/core/private/parseRestArguments.php');
$rc = qruqsp_core_parseRestArguments($q);
if( $rc['stat'] != 'ok' ) {
    qruqsp_core_printResponse($q, $rc);
    exit;
}

//
// Once the REST specific stuff is done, pass the control to
// qruqsp.core.callPublicMethod()
//
$rc = qruqsp_core_callPublicMethod($q);

//
// Check if there is a sync queue to process or email queue to process
//
if( (isset($q['syncqueue']) && count($q['syncqueue']) > 0) 
    || (isset($q['fbrefreshqueue']) && count($q['fbrefreshqueue']) > 0) 
    || (isset($q['smsqueue']) && count($q['smsqueue']) > 0) 
    || (isset($q['emailqueue']) && count($q['emailqueue']) > 0) 
    ) {
    if( $rc['stat'] != 'exit' ) {
        ob_start();
        if(isset($_SERVER['HTTP_ACCEPT_ENCODING']) 
            && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            ob_start("ob_gzhandler"); // Inner buffer when output is apache mod-deflate is enabled
            qruqsp_core_printResponse($q, $rc);
            ob_end_flush();
        } else {
            qruqsp_core_printResponse($q, $rc);
        }
        header("Connection: close");
        $contentlength = ob_get_length();
        header("Content-Length: $contentlength");
        ob_end_flush();
        ob_end_flush();
        flush();
        session_write_close();
        while(ob_get_level()>0) ob_end_clean();
    }

    // Run sms queue
    if( isset($q['smsqueue']) && count($q['smsqueue']) > 0 ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'smsQueueProcess');
        qruqsp_core_smsQueueProcess($q);
    } 
    // Run email queue
    if( isset($q['emailqueue']) && count($q['emailqueue']) > 0 ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'emailQueueProcess');
        qruqsp_core_emailQueueProcess($q);
    } 
    // Run facebook refresh queue
    if( isset($q['fbrefreshqueue']) && count($q['fbrefreshqueue']) > 0 ) {
//          FIXME: Facebook is blocking requests direct to this script
//        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'fbRefreshQueueProcess');
//        qruqsp_core_fbRefreshQueueProcess($q);
    } 
    // Run sync queue
    if( isset($q['syncqueue']) && count($q['syncqueue']) > 0 ) {
        if( isset($q['syncbusinesses']) && count($q['syncbusinesses']) > 0 ) {
            foreach($q['syncbusinesses'] as $business_id) {
                qruqsp_core_syncQueueProcess($q, $business_id);
            }
        } elseif( isset($q['request']['args']['business_id']) ) {
            qruqsp_core_syncQueueProcess($q, $q['request']['args']['business_id']);
        } 
    }
} else {
    //
    // Output the result in requested format
    //
    if( $rc['stat'] != 'exit' ) {
        qruqsp_core_printResponse($q, $rc);
    }
}

//
// Capture errors in the database for easy review
//
if( $rc['stat'] == 'fail' ) {
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbLogError');
    qruqsp_core_dbLogError($q, $rc['err']);
}
if( isset($q['config']['qruqsp.core']['microtime']) 
    && $q['config']['qruqsp.core']['microtime'] == 'yes') {
    $end_time = microtime(true);
    error_log("PROF: microtime $end_time - $start_time = " . ($end_time - $start_time));
}

exit;

?>
