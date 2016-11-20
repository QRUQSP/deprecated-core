<?php
//
// Description
// -----------
// This function will take a list of settings to be updated within the database.  The
// fields will be used for the contact information and station information
// on the Contact Page for the station.
//
// Arguments
// ---------
// api_key:
// auth_token:
// station_id:                  The ID of the station to get the settings for.
// station-name:                (optional) The name to set for the station.
// station-tagline:             (optional) The tagline for the website.  Used on website.
// contact-address-street1:     (optional) The address for the station.
// contact-address-street2:     (optional) The second address line for the station.
// contact-address-city:        (optional) The city for the station.
// contact-address-province:    (optional) The province for the station.
// contact-address-postal:      (optional) The postal code for the station.
// contact-address-country:     (optional) The county of the station.
// contact-person-name:         (optional) The contact person for the station.
// contact-phone-number:        (optional) The contact phone number for the station.  
// contact-cell-number:         (optional) The contact cell phone number for the station.  
// contact-fax-number:          (optional) The fax number for the station.
// contact-email-address:       (optional) The contact email address for the station.
//
function qruqsp_core_stationSettingsUpdate(&$q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'), 
        'station-name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Station Name'), 
        'station-category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        'station-permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'), 
        'station-tagline'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tagline'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to station_id as owner
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, $args['station_id'], 'qruqsp.core.stationSettingsUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check the permalink is proper format
    //
    if( isset($args['station-permalink']) && preg_match('/[^a-z0-9\-_]/', $args['station-permalink']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.143', 'msg'=>'Illegal characters in permalink.  It can only contain lowercase letters, numbers, underscores (_) or dash (-)'));
    }

    //
    // Turn off autocommit
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionStart');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionRollback');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionCommit');
    $rc = qruqsp_core_dbTransactionStart($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if name or tagline was specified
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');
    $strsql = "";
    if( isset($args['station-name']) && $args['station-name'] != '' ) {
        $strsql .= ", name = '" . qruqsp_core_dbQuote($q, $args['station-name']) . "'";
        qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 
            2, 'qruqsp_core_stations', '', 'name', $args['station-name']);
    }
    if( isset($args['station-permalink']) ) {
        $strsql .= ", permalink = '" . qruqsp_core_dbQuote($q, $args['station-permalink']) . "'";
        qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 
            2, 'qruqsp_core_stations', '', 'permalink', $args['station-permalink']);
    }
    if( isset($args['station-category']) ) {
        $strsql .= ", category = '" . qruqsp_core_dbQuote($q, $args['station-category']) . "'";
        qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 
            2, 'qruqsp_core_stations', '', 'category', $args['station-category']);
    }
    if( isset($args['station-tagline']) ) {
        $strsql .= ", tagline = '" . qruqsp_core_dbQuote($q, $args['station-tagline']) . "'";
        qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 
            2, 'qruqsp_core_stations', '', 'tagline', $args['station-tagline']);
    }
    //
    // Always update last_updated for sync purposes
    //
    $strsql = "UPDATE qruqsp_core_stations SET last_updated = UTC_TIMESTAMP()" . $strsql 
        . " WHERE id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' ";
    $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return $rc;
    }

    //
    // Allowed station detail keys 
    //
    $allowed_keys = array(
        'contact-address-street1',
        'contact-address-street2',
        'contact-address-city',
        'contact-address-province',
        'contact-address-postal',
        'contact-address-country',
        'contact-person-name',
        'contact-phone-number',
        'contact-cell-number',
        'contact-fax-number',
        'contact-email-address',
        'qruqsp-manage-css',
        'social-facebook-url',
        'social-twitter-station-name',
        'social-twitter-username',
        'social-flickr-url',
        'social-etsy-url',
        'social-pinterest-username',
        'social-tumblr-username',
        'social-youtube-url',
        'social-vimeo-url',
        'social-instagram-username',
        'social-linkedin-url',
        );
    foreach($q['request']['args'] as $arg_name => $arg_value) {
        if( in_array($arg_name, $allowed_keys) ) {
            $strsql = "INSERT INTO qruqsp_core_station_settings (station_id, detail_key, detail_value, date_added, last_updated) "
                . "VALUES ('" . qruqsp_core_dbQuote($q, $args['station_id']) . "'"
                . ", '" . qruqsp_core_dbQuote($q, $arg_name) . "'"
                . ", '" . qruqsp_core_dbQuote($q, $arg_value) . "'"
                . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                . "ON DUPLICATE KEY UPDATE detail_value = '" . qruqsp_core_dbQuote($q, $arg_value) . "' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "";
            $rc = qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
            if( $rc['stat'] != 'ok' ) {
                qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
                return $rc;
            }
            qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $args['station_id'], 
                2, 'qruqsp_core_station_settings', $arg_name, 'detail_value', $arg_value);
//            $q['syncqueue'][] = array('push'=>'qruqsp.core.stationsettings', 
//                'args'=>array('id'=>$arg_name));
        }
    }

    $rc = qruqsp_core_dbTransactionCommit($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
