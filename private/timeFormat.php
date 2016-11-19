<?php
//
// Description
// -----------
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function qruqsp_core_timeFormat($q, $format='mysql') {

    if( $format == 'php' ) {
        return "H:i";
    }

    //
    // Check if the user is logged in, otherwise return 
    //
    if( isset($q['session']['user']['settings']['settings-time-format']) && $q['session']['user']['settings']['settings-time-format'] != '' ) {
        return $q['session']['user']['settings']['settings-time-format'];
    }

    //
    // This function does not return the standard response, because it will NEVER return an error.  
    // If a problem is encountered, the it will return the default date format.
    //
    return "%l:%i %p";
}
?>
