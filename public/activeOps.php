<?php
//
// Description
// -----------
// This method will get active operators
//
// Arguments
// ---------
// api_key:
// auth_token:
//      no other arguments are required
//
function qruqsp_core_activeOps($q) {
    //
    // Check access 
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($q, 0, 'qruqsp.core.activeOps');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryArrayTree');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'datetimeFormat');
    $datetime_format = qruqsp_core_datetimeFormat($q,'php');

    $strsql = "SELECT qruqsp_core_users.id, "
        . "MAX(a1.log_date) AS log_date, "
        . "qruqsp_core_users.callsign, "
        . "qruqsp_core_users.display_name "
        . "FROM qruqsp_core_users "
        . "LEFT JOIN qruqsp_core_auth_log AS a1 ON (qruqsp_core_users.id = a1.user_id ) "
        . "GROUP BY qruqsp_core_users.id "
        . "ORDER BY MAX(a1.log_date) DESC ";
    
    if( isset($args['limit']) && $args['limit'] != '' && is_numeric($args['limit'])) {
        $strsql .= "LIMIT " . qruqsp_core_dbQuote($q, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }

    $rc = qruqsp_core_dbHashQueryArrayTree($q, $strsql, 'qruqsp.core', array(
        array('container'=>'users', 'fname'=>'id', 'fields'=>array('id', 'log_date', 'callsign', 'display_name'), 'utctotz'=>array('timezone'=>'UTC', 'format'=>$datetime_format)),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.core.99', 'msg'=>'unable to get user list', 'err'=>$rc['err']));
    }
    if(!isset($rc['users'])) { 
        return array('stat'=>'ok', 'users'=>array());
    }
    return array('stat'=>'ok', 'users'=>$rc['users']);
}
?>
