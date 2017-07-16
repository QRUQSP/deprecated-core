<?php
//
// Description
// -----------
// This function will add a new station.  You must be a sys admin to be authorized to add a station.
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
function qruqsp_core_stationAdd(&$q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'plan_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Plan'), 
//        'payment_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Payment'), 
        'station-name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station Name'), 
        'station-permalink'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Sitename'), 
        'station-tagline'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Tagline'), 
        'station-category'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Category'), 
        'owner-name-first'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner First Name'), 
        'owner-name-last'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Last Name'), 
        'owner-name-display'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Display Name'), 
        'owner-email-address'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Email'), 
        'owner-username'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Username'), 
        'owner-password'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Password'), 
        'contact-person-name'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Contact'), 
        'contact-email-address'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Email'), 
        'contact-phone-number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Phone'), 
        'contact-cell-number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Cell'), 
        'contact-fax-number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Fax'), 
        'contact-address-street1'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address Line 1'), 
        'contact-address-street2'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address Line 2'), 
        'contact-address-city'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'City'), 
        'contact-address-province'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Province'), 
        'contact-address-postal'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Postal'), 
        'contact-address-country'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Country'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to station_id as owner, or sys admin
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $ac = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.stationAdd');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Load timezone settings
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'intlSettings');
    $rc = qruqsp_core_intlSettings($q, $q['config']['qruqsp.core']['master_station_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // If the permalink is not specified, then create
    //
    if( $args['station-permalink'] == '' ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'makePermalink');
        $args['station-permalink'] = qruqsp_core_makePermalink($args['station-name']);
    }

    //
    // Check the sitename is proper format
    //
    if( preg_match('/[^a-z0-9\-_]/', $args['station-sitename']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.140', 'msg'=>'Illegal characters in sitename.  It can only contain lowercase letters, numbers, underscores (_) or dash (-)'));
    }
    
    //
    // Load required functions
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbInsert');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectAdd');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');

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
    // Add the station to the database
    //
    $strsql = "INSERT INTO qruqsp_core_stations (uuid, name, category, sitename, tagline, status, date_added, last_updated) "
        . "VALUES ("
        . "UUID(), "
        . "'" . qruqsp_core_dbQuote($q, $args['station-name']) . "' "
        . ", '" . qruqsp_core_dbQuote($q, $args['station-category']) . "' "
        . ", '" . qruqsp_core_dbQuote($q, $args['station-sitename']) . "' "
        . ", '" . qruqsp_core_dbQuote($q, $args['station-tagline']) . "' "
        . ", 1 "
        . ", UTC_TIMESTAMP(), UTC_TIMESTAMP())";
    $rc = qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return $rc;
    }
    if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.144', 'msg'=>'Unable to add station'));
    }
    $station_id = $rc['insert_id'];
    qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $station_id, 
        1, 'qruqsp_core_stations', $station_id, 'name', $args['station-name']);
    qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $station_id, 
        1, 'qruqsp_core_stations', $station_id, 'tagline', $args['station-tagline']);
    qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $station_id, 
        1, 'qruqsp_core_stations', $station_id, 'sitename', $args['station-sitename']);
    qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $station_id, 
        1, 'qruqsp_core_stations', $station_id, 'status', '1');

    if( $station_id < 1 ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.145', 'msg'=>'Unable to add station'));
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
        'contact-fax-number',
        'contact-email-address',
        );
    $customer_address_args = array();
    foreach($q['request']['args'] as $arg_name => $arg_value) {
        if( in_array($arg_name, $allowed_keys) ) {
            $strsql = "INSERT INTO qruqsp_core_station_settings (station_id, detail_key, detail_value, date_added, last_updated) "
                . "VALUES ('" . qruqsp_core_dbQuote($q, $station_id) . "', "
                . "'" . qruqsp_core_dbQuote($q, $arg_name) . "', "
                . "'" . qruqsp_core_dbQuote($q, $arg_value) . "', "
                . "UTC_TIMESTAMP(), UTC_TIMESTAMP()) ";
            $rc = qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
            if( $rc['stat'] != 'ok' ) {
                qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
                return $rc;
            }
            qruqsp_core_dbAddModuleHistory($q, 'qruqsp.core', 'qruqsp_core_history', $station_id, 
                1, 'qruqsp_core_station_settings', $arg_name, 'detail_value', $arg_value);
        }
    }

    //
    // Check if user needs to be added
    //
    $user_id = 0;
    if( (isset($args['owner-username']) && $args['owner-username'] != '')
        || (isset($args['owner-email-address']) && $args['owner-email-address'] != '') ) {

        //
        // Check if user already exists
        //
        $strsql = "SELECT id, email, username "
            . "FROM qruqsp_core_users "
            . "WHERE username = '" . qruqsp_core_dbQuote($q, $args['owner-username']) . "' "
            . "OR email = '" . qruqsp_core_dbQuote($q, $args['owner-email-address']) . "' "
            . "";
        $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'user');
        if( $rc['stat'] != 'ok' ) {
            qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.146', 'msg'=>'Unable to lookup user'));
        }
        $user_id = 0;
        if( isset($rc['user']) ) {
            // User exists, check if email different
            if( $rc['user']['email'] != $args['owner-email-address'] ) {
                // Username matches, but email doesn't, they are trying to create a new account
                qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.147', 'msg'=>'Username already taken'));
            }
            else {
                $user_id = $rc['user']['id'];
            }
        } else {
            //
            // User doesn't exist, so can be created
            //
            if( !isset($args['owner-name-first']) || $args['owner-name-first'] == '' 
                || !isset($args['owner-name-last']) || $args['owner-name-last'] == '' 
                || !isset($args['owner-name-display']) || $args['owner-name-display'] == '' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.148', 'msg'=>'You must specify a first, last and display name'));
            }
            $strsql = "INSERT INTO qruqsp_users (uuid, date_added, email, username, firstname, lastname, display_name, "
                . "perms, status, timeout, password, temp_password, temp_password_date, last_updated) VALUES ("
                . "UUID(), "
                . "UTC_TIMESTAMP()" 
                . ", '" . qruqsp_core_dbQuote($q, $args['owner-email-address']) . "'" 
                . ", '" . qruqsp_core_dbQuote($q, $args['owner-username']) . "'" 
                . ", '" . qruqsp_core_dbQuote($q, $args['owner-name-first']) . "'" 
                . ", '" . qruqsp_core_dbQuote($q, $args['owner-name-last']) . "'" 
                . ", '" . qruqsp_core_dbQuote($q, $args['owner-name-display']) . "'" 
                . ", 0, 1, 0, "
                . "SHA1('" . qruqsp_core_dbQuote($q, $args['owner-password']) . "'), "
                . "SHA1('" . qruqsp_core_dbQuote($q, '') . "'), "
                . "UTC_TIMESTAMP(), "
                . "UTC_TIMESTAMP())";
            $rc = qruqsp_core_dbInsert($q, $strsql, 'qruqsp.users');
            if( $rc['stat'] != 'ok' ) { 
                qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.149', 'msg'=>'Unable to add owner'));
            } else {
                $user_id = $rc['insert_id'];
            }
        }
    }

    //
    // Add the station owner
    //
    if( $user_id > 0 ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUUID');
        $rc = qruqsp_core_dbUUID($q, 'qruqsp.core');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.150', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
        }
        $station_user_uuid = $rc['uuid'];
        
        $strsql = "INSERT INTO qruqsp_core_station_users (station_id, user_id, uuid, "
            . "permission_group, status, date_added, last_updated) VALUES ("
            . "'" . qruqsp_core_dbQuote($q, $station_id) . "' "
            . ", '" . qruqsp_core_dbQuote($q, $user_id) . "' "
            . ", '" . qruqsp_core_dbQuote($q, $station_user_uuid) . "' "
            . ", 'operators', 10, UTC_TIMESTAMP(), UTC_TIMESTAMP())";
        $rc = qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
        if( $rc['stat'] != 'ok' ) {
            qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.151', 'msg'=>'Unable to add qruqsp owner'));
        } 
    }

    //
    // Add the customer to the master station
    //
/*    if( (isset($args['owner-name-first']) && $args['owner-name-first'] != '')
        || (isset($args['owner-name-last']) && $args['owner-name.last'] != '') 
        ) {
        //
        // Add the customer
        //
        $customer_args = array(
            'type'=>'1',
            'first'=>(isset($args['owner-name-first'])&&$args['owner-name-first']!='')?$args['owner-name-first']:'',
            'last'=>(isset($args['owner-name-last'])&&$args['owner-name-last']!='')?$args['owner-name-last']:'',
            'company'=>(isset($args['station-name'])&&$args['station-name']!='')?$args['station-name']:'',
            'email_address'=>(isset($args['owner-email-address'])&&$args['owner-email-address']!='')?$args['owner-email-address']:'',
            'flags'=>0x01,
            'address1'=>(isset($args['contact-address-street1'])&&$args['contact-address-street1']!='')?$args['contact-address-street1']:'',
            'address2'=>(isset($args['contact-address-street2'])&&$args['contact-address-street2']!='')?$args['contact-address-street2']:'',
            'city'=>(isset($args['contact-address-city'])&&$args['contact-address-city']!='')?$args['contact-address-city']:'',
            'province'=>(isset($args['contact-address-province'])&&$args['contact-address-province']!='')?$args['contact-address-province']:'',
            'postal'=>(isset($args['contact-address-postal'])&&$args['contact-address-postal']!='')?$args['contact-address-postal']:'',
            'country'=>(isset($args['contact-address-country'])&&$args['contact-address-country']!='')?$args['contact-address-country']:'',
            );
        if( isset($args['contact-phone-number']) && $args['contact-phone-number'] != '' ) {
            $customer_args['phone_label_1'] = 'Work';
            $customer_args['phone_number_1'] = $args['contact-phone-number'];
        }
        if( isset($args['contact-cell-number']) && $args['contact-cell-number'] != '' ) {
            $customer_args['phone_label_2'] = 'Cell';
            $customer_args['phone_number_2'] = $args['contact-cell-number'];
        }
        if( isset($args['contact-fax-number']) && $args['contact-fax-number'] != '' ) {
            $customer_args['phone_label_4'] = 'Fax';
            $customer_args['phone_number_4'] = $args['contact-fax-number'];
        }
        qruqsp_core_loadMethod($q, 'qruqsp', 'customers', 'hooks', 'customerAdd');
        $rc = qruqsp_customers_hooks_customerAdd($q, $q['config']['qruqsp.core']['master_station_id'], $customer_args);
        if( $rc['stat'] != 'ok' ) {
            qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
            return $rc;
        }
        $customer_id = $rc['id'];
    } */

    //
    // Check if a plan was specified and then setup for that plan
    //
    if( isset($args['plan_id']) && $args['plan_id'] > 0 ) {
        $strsql = "SELECT station_id, modules, monthly, trial_days "
            . "FROM qruqsp_core_station_plans "
            . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['plan_id']) . "' "
            . "AND station_id = '" . qruqsp_core_dbQuote($q, $q['config']['qruqsp.core']['master_station_id']) . "' "
            . "";
        $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'plan');
        if( $rc['stat'] != 'ok' ) {
            qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
            return $rc;
        }
        if( !isset($rc['plan']) ) {
            qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.152', 'msg'=>'Unable to find plan'));
        }
        $plan = $rc['plan'];

        $modules = preg_split('/,/', $plan['modules']);
        foreach($modules as $module) {
            list($pmod,$flags) = explode(':', $module);
            $mod = explode('.', $pmod);
            $strsql = "INSERT INTO qruqsp_station_modules (station_id, "
                . "package, module, status, flags, ruleset, date_added, last_updated, last_change) VALUES ("
                . "'" . qruqsp_core_dbQuote($q, $station_id) . "', "
                . "'" . qruqsp_core_dbQuote($q, $mod[0]) . "', "
                . "'" . qruqsp_core_dbQuote($q, $mod[1]) . "', "
                . "1, "
                . "'" . qruqsp_core_dbQuote($q, $flags) . "', "
                . "'', UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP())";
            $rc = qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
            if( $rc['stat'] != 'ok' ) {
                qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
                return $rc;
            }
            //
            // Check if there is an initialization script for the module when the station is enabled
            //
            $rc = qruqsp_core_loadMethod($q, $mod[0], $mod[1], 'private', 'moduleInitialize');
            if( $rc['stat'] == 'ok' ) {
                $fn = $mod[0] . '_' . $mod[1] . '_moduleInitialize';
                $rc = $fn($q, $station_id);
                if( $rc['stat'] != 'ok' ) {
                    qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
                    return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.153', 'msg'=>'Unable to initialize module ' . $mod[0] . '.' . $mod[1], 'err'=>$rc['err']));
                }
            }
        }

        //
        // Add the subscription plan
        //
        if( isset($args['payment_type']) && $args['payment_type'] == 'monthlypaypal' ) {
            $strsql = "INSERT INTO qruqsp_station_subscriptions (station_id, status, "
                . "signup_date, trial_start_date, trial_days, currency, "
                . "monthly, discount_percent, discount_amount, payment_type, payment_frequency, "
                . "date_added, last_updated) VALUES ("
                . "'" . qruqsp_core_dbQuote($q, $station_id) . "', "
                . "2, UTC_TIMESTAMP(), UTC_TIMESTAMP(), "
                . "' " . qruqsp_core_dbQuote($q, $plan['trial_days']) . "' "
                . ", 'CAD', "
                . "'" . qruqsp_core_dbQuote($q, $plan['monthly']) . "', "
                . "0, 0, 'paypal', 10, "
                . "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
            $rc = qruqsp_core_dbInsert($q, $strsql, 'qruqsp.core');
            if( $rc['stat'] != 'ok' ) {
                qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
                return $rc;
            } 
        }

        //
        // Add the yearly invoice to the master station
        //
        elseif( $args['payment_type'] == 'yearlycheque' && isset($customer_id) ) {
            $tz = new DateTimeZone($intl_timezone);
            $dt = new DateTime('now', $tz);
            $dt->add(new DateInterval('P' . $plan['trial_days'] . 'D'));
            $invoice_args = array(
                'source_id'=>'0',
                'status'=>'10',
                'customer_id'=>$customer_id,
                'invoice_number'=>'',
                'invoice_type'=>'12',
                'invoice_date'=>$dt->format('Y-m-d 12:00:00'),
                'items'=>array(array('description'=>'Web Hosting',
                    'quantity'=>'12',
                    'status'=>'0',
                    'flags'=>0,
                    'object'=>'qruqsp.core.station',
                    'object_id'=>$station_id,
                    'price_id'=>'0',
                    'code'=>'',
                    'shipped_quantity'=>'0',
                    'unit_amount'=>$plan['monthly'],
                    'unit_discount_amount'=>'0',
                    'unit_discount_percentage'=>'0',
                    'taxtype_id'=>'0',
                    'notes'=>'{{thismonth[\'M Y\']}} - {{lastmonth[\'M\']}} {{nextyear[\'Y\']}}',
                    )),
                );
            qruqsp_core_loadMethod($q, 'qruqsp', 'sapos', 'hooks', 'invoiceAdd');
            $rc = qruqsp_sapos_hooks_invoiceAdd($q, $q['config']['qruqsp.core']['master_station_id'], $invoice_args);
            if( $rc['stat'] != 'ok' ) {
                qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
                return $rc;
            } 
            if( !isset($rc['id']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.154', 'msg'=>'Unable to create invoice'));
            }
        }
    }

    //
    // Commit the changes
    //
    $rc = qruqsp_core_dbTransactionCommit($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
/*
    //
    // Send welcome email with login information
    //
    if( isset($args['owner-email-address']) && $args['owner-email-address'] != '' && $args['owner-username'] != '' ) {
        //
        // Load the station mail template
        //
        qruqsp_core_loadMethod($q, 'qruqsp', 'mail', 'private', 'loadStationTemplate');
        $rc = qruqsp_mail_loadStationTemplate($q, $q['config']['qruqsp.core']['master_station_id'], array('title'=>'Welcome'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $template = $rc['template'];
        $theme = $rc['theme'];

        //
        // Create the email
        //
        $subject = "Welcome to Ciniki";
        $manager_url = $q['config']['qruqsp.core']['manage.url'];
        $msg = "<tr><td style='" . $theme['td_body'] . "'>"
            . "<p style='" . $theme['p'] . "'>"
            . 'Thank you for choosing the Ciniki platform to manage your station. '
            . "Please save this email for future reference.  We've included some important information and links below."
            . "</p>\n\n<p style='" . $theme['p'] . "'>"
            . "To get started, you can login at <a style='" . $theme['a'] . "' href='$manager_url'>$manager_url</a> with your email address and the password shown below."
            . "</p>\n\n<p style='" . $theme['p'] . "'>"
            . "";
        $msg .= "<p style='" . $theme['p'] . "'>"
            . "Email: " . $args['owner-email-address'] . "<br/>\n"
            . "Username: " . $args['owner-username'] . "<br/>\n"
            . "Password: " . $args['owner-password'] . "<br/>\n"
            . "Ciniki Manager: <a style='" . $theme['a'] . "' href='$manager_url'>$manager_url</a><br/>\n"
            . "";
        if( isset($plan) && preg_match('/qruqsp\.web/', $plan['modules']) ) {
            $weburl = "http://" . $q['config']['qruqsp.web']['master.domain'] . '/' . $args['station-permalink'] . "<br/>\n";
            $msg .= "Your website: <a style='" . $theme['a'] . "' href='$weburl'>$weburl</a><br/>\n";
        }
        $msg .= "</p>\n\n";

        $htmlmsg = $template['html_header']
            . $msg
            . $template['html_footer']
            . "";
        $textmsg = $template['text_header']
            . strip_tags($msg)
            . $template['text_footer']
            . "";
        $q['emailqueue'][] = array('to'=>$args['owner-email-address'],
            'subject'=>$subject,
            'htmlmsg'=>$htmlmsg,
            'textmsg'=>$textmsg,
            );
    }
*/
    return array('stat'=>'ok', 'id'=>$station_id);
}
?>
