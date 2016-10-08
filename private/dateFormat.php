<?php
//
// Description
// -----------
// This function will return the preferred data format for a user
// if they are logged in, otherwise the default date format.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function qruqsp_core_dateFormat($q, $format='mysql') {

    if( $format == 'php' ) {
        return "M j, Y";
    }

    //
    // Check if the user is logged in, otherwise return 
    //
    if( isset($q['session']['user']['settings']['settings-date-format']) && $q['session']['user']['settings']['settings-date-format'] != '' ) {
        return $q['session']['user']['settings']['settings-date-format'];
    }

    //
    // This function does not return the standard response, because it will NEVER return an error.  
    // If a problem is encountered, the it will return the default date format.
    //
    return "%b %e, %Y";
}
?>
