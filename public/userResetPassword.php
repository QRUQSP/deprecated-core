<?php
//
// Description
// -----------
// This function will reset a users password, if the calling user is a sysadmin.
// The new password will be set and emailed to the user.  The sysadmin does not
// have a chance to see the password.
//
// Info
// ----
// publish:         no
// 
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:         The user to have the password reset.
//
// Returns
// -------
// <stat='ok' />
//
function qruqsp_core_userResetPassword(&$q) {
    
    // FIXME: Require sysadmin password to verify user before allowing a reset.

    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.resetPassword');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //  
    // Create a random password for the user
    //  
    $password = ''; 
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    for($i=0;$i<8;$i++) {
        $password .= substr($chars, rand(0, strlen($chars)-1), 1); 
    }

    //
    // Get the username for the account
    //
    $strsql = "SELECT username, email "
        . "FROM qruqsp_core_users "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' ";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'user');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.130', 'msg'=>'Unable to reset password.', 'err'=>$rc['err']));
    }
    if( !isset($rc['user']) || !isset($rc['user']['username']) || !isset($rc['user']['email']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.131', 'msg'=>'Unable to reset password.'));
    }
    $user = $rc['user'];

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
    // Update the password, but only if the old one matches
    //
    $strsql = "UPDATE qruqsp_core_users SET password = SHA1('" . qruqsp_core_dbQuote($q, $password) . "') "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' ";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.132', 'msg'=>'Unable to reset password.'));
    }

    if( $rc['num_affected_rows'] < 1 ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.133', 'msg'=>'Unable to reset password.'));
    }

    //
    // FIXME: Add log entry to track password changes
    //

    //
    // Email the user with the new password
    //
    if( $user['email'] != '' 
        && isset($q['config']['core']['system.email']) && $q['config']['core']['system.email'] != '' ) {
        $subject = "Ciniki - Password reset";
        $msg = "The password for you account has been reset, please login and change your password.\n"
            . "\n"
            . "https://" . $_SERVER['SERVER_NAME'] . "/\n"
            . "Username: " . $user['username'] . "\n"
            . "Temporary Password: $password   ** Please change this immediately **\n"
            . "\n"
            . "\n";
        //
        // The from address can be set in the config file.
        //
        $q['emailqueue'][] = array('user_id'=>$args['user_id'],
            'subject'=>$subject,
            'textmsg'=>$msg,
            );
//      $headers = 'From: "' . $q['config']['core']['system.email.name'] . '" <' . $q['config']['core']['system.email'] . ">\r\n" .
//              'Reply-To: "' . $q['config']['core']['system.email.name'] . '" <' . $q['config']['core']['system.email'] . ">\r\n" .
//              'X-Mailer: PHP/' . phpversion();
//      mail($user['email'], $subject, $msg, $headers, '-f' . $q['config']['core']['system.email']);
    }

    //
    // Commit the changes and return
    //
    $rc = qruqsp_core_dbTransactionCommit($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.134', 'msg'=>'Unable to reset password.'));
    }

    return array('stat'=>'ok');
}
?>
