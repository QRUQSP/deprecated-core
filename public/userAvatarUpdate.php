<?php
//
// Description
// -----------
// This method will set the avatar image ID for a user.  The image
// must already exist in the qruqsp images module.
//
// Arguments
// ---------
// api_key:
// auth_token:
// user_id:         The ID of the user to update the avatar image ID.
// image_id:        The ID of the image from the qruqsp images module to set as the users avatar.
// 
function qruqsp_core_userAvatarUpdate(&$q) {
    //
    // Check args
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Image'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.userAvatarUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Start transaction
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionStart');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionRollback');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionCommit');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
    $rc = qruqsp_core_dbTransactionStart($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) { 
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.119', 'msg'=>'Internal Error', 'err'=>$rc['err']));
    }   

    //
    // The image type will be checked in the insertFromUpload method to ensure it is an image
    // in a format we accept
    //

    //
    // Remove existing avatar
    //
    $strsql = "SELECT avatar_id "
        . "FROM qruqsp_core_users "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' ";
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'user');
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
        return $rc;
    }
    $avatar_id = $rc['user']['avatar_id'];

    if( $avatar_id > 0 ) {
        qruqsp_core_loadMethod($q, 'qruqsp', 'images', 'private', 'removeImage');
        $rc = qruqsp_images_removeImage($q, 0, $args['user_id'], $avatar_id);
        if( $rc['stat'] != 'ok' ) {
            qruqsp_core_dbTransactionRollback($q, 'qruqsp.core');
            return $rc;
        }
    }

    //
    // Update user with new image id
    //
    $strsql = "UPDATE qruqsp_core_users "
        . "SET avatar_id = '" . qruqsp_core_dbQuote($q, $args['image_id']) . "' "
        . ", last_updated = UTC_TIMESTAMP() "
        . "WHERE id = '" . qruqsp_core_dbQuote($q, $args['user_id']) . "' ";
    $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Update the session variable, if same user who's logged in
    //
    if( $q['session']['user']['id'] == $args['user_id'] ) {
        $q['session']['user']['avatar_id'] = $args['image_id'];
    }

    //
    // Commit the transaction
    //
    $rc = qruqsp_core_dbTransactionCommit($q, 'qruqsp.core');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.129', 'msg'=>'Unable to save avatar', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok', 'avatar_id'=>$args['image_id']);
}
?>
