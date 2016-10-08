<?php
//
// Description
// -----------
// This method will authenticate a user and return an auth_token
// to be used for future API calls.  Either a username or 
// email address can be used to authenticate.
//
// Info
// ----
// publish:         yes
//
// Arguments
// ---------
// api_key:
// email:           The email address to be authenticated.  The email
//                  address or username must be sent.
//
// username:        The username to be authenticated.  The username can be
//                  an email address or username, they are both unique
//                  in the database.
//
// password:        The password to be checked with the username.
//
// Example Return
// --------------
// <rsp stat="ok" station="3">
//      <auth token="0123456789abcdef0123456789abcdef" id="42" perms="1" avatar_id="192" />
// </rsp>
//
function qruqsp_core_auth(&$q) {
    if( (!isset($q['request']['args']['username'])
        || !isset($q['request']['args']['email']))
        && !isset($q['request']['args']['password']) 
        && !isset($q['request']['auth_token'])
        && !isset($q['request']['args']['user_selector'])
        && !isset($q['request']['args']['user_token'])
        ) {
        //
        // This return message should be cryptic so people
        // can't use the error code to determine what went wrong
        //
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.94', 'msg'=>'Invalid password'));
    }

    //
    // Check access 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.auth', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($q['request']['args']['user_selector']) && $q['request']['args']['user_selector'] != '' 
        && isset($q['request']['args']['user_token']) && $q['request']['args']['user_token'] != '' 
        ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'sessionTokenStart');
        $rc = qruqsp_core_sessionTokenStart($q, $q['request']['args']['user_selector'], $q['request']['args']['user_token']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $version = $rc['version'];
        $auth = $rc['auth'];
    } else if( isset($q['request']['auth_token']) && $q['request']['auth_token'] != '' ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'sessionOpen');
        $rc = qruqsp_core_sessionOpen($q);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $version = $rc['version'];
        $auth = $rc['auth'];
    } else {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'sessionStart');
        $rc = qruqsp_core_sessionStart($q, $q['request']['args']['username'], $q['request']['args']['password']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $version = $rc['version'];
        $auth = $rc['auth'];
    }

    //
    // Get any UI settings for the user
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDetailsQueryDash');
    $rc = qruqsp_core_dbDetailsQueryDash($q, 'qruqsp_core_user_details', 'user_id', $q['session']['user']['id'], 'qruqsp.core', 'settings', 'ui');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['settings']) ) {
        $auth['settings'] = $rc['settings'];
    } else {
        $auth['settings'] = array();
    }

    //
    // Check if a user token should be setup
    //
    if( isset($q['request']['args']['rm']) && $q['request']['args']['rm'] == 'yes' ) {
        $user_token = '';
        $chars = 'abcefghijklmnopqrstuvwxyzABCEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        for($i=0;$i<20;$i++) {
            $user_token .= substr($chars, rand(0, strlen($chars)-1), 1);
        }
        $user_token = sha1($user_token);

        //
        // Check for cookie user_selector
        //
        if( isset($q['request']['user_selector']) && $q['request']['user_selector'] != '' ) {
            $user_selector = $q['request']['user_selector'];
        } else {
            //
            // Create a new user token
            //
            qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUUID');
            $rc = qruqsp_core_dbUUID($q, 'qruqsp.core');
            if( $rc['stat'] == 'ok' ) { 
                $user_selector = $rc['uuid'];
            }
        }

        if( isset($user_selector) ) {
            $dt = new DateTime('now', new DateTimezone('UTC'));
            $dt->add(new DateInterval('P1M'));
            $strsql = "INSERT INTO qruqsp_core_user_tokens (user_id, selector, token, date_added, last_auth) VALUES ("
                . "'" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "', "
                . "'" . qruqsp_core_dbQuote($q, $user_selector) . "', "
                . "'" . qruqsp_core_dbQuote($q, hash('sha256', $user_token)) . "', "
                . "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
            qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
            $rc = qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
            if( $rc['stat'] != 'ok' ) {
                error_log('AUTH-ERR: ' . print_r($rc, true));
            } else {
                $auth['user_selector'] = $user_selector;
                $auth['user_token'] = $user_token;
            }
        }
    }

    //
    // If the user is not a sysadmin, check if they only have access to one station
    //
    if( ($q['session']['user']['perms'] & 0x01) == 0 ) {
        $strsql = "SELECT DISTINCT qruqsp_core_stations.id, name "
            . "FROM qruqsp_core_station_users, qruqsp_core_stations "
            . "WHERE qruqsp_core_station_users.user_id = '" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "' "
            . "AND qruqsp_core_station_users.status = 1 "
            . "AND qruqsp_core_station_users.station_id = qruqsp_core_stations.id "
            . "AND qruqsp_core_stations.status < 60 "  // Allow suspended station to be listed, so user can login and update billing/unsuspend
            . "ORDER BY qruqsp_core_stations.name "
            . "LIMIT 2"
            . "";
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
        $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'station');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['station']) ) {
            return array('stat'=>'ok', 'auth'=>$auth, 'station'=>$rc['station']['id']);
        }
    }   

    return array('stat'=>'ok', 'version'=>$version, 'auth'=>$auth);
}
?>
