<?php
//
// Description
// -----------
// This method will search a field for the search string provided.
//
// Arguments
// ---------
// api_key:
// auth_token:
// start_needle:    The search string to search the field for.
//
// limit:           (optional) Limit the number of results to be returned. 
//                  If the limit is not specified, the default is 25.
// 
function qruqsp_core_stationCategorySearch($qruqsp) {
    //  
    // Find all the required and optional arguments
    //  
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($qruqsp, 'no', array(
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this stations
    //  
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'checkAccess');
    $rc = qruqsp_core_checkAccess($qruqsp, 0, 'qruqsp.core.stationCategorySearch'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Search for categories
    //
    $strsql = "SELECT DISTINCT category AS name "
        . "FROM qruqsp_core_stations "
        . "WHERE category like '" . qruqsp_core_dbQuote($qruqsp, $args['start_needle']) . "%' "
        . "AND category <> '' "
        . "ORDER BY category "
        . "";
    qruqsp_core_loadMethod($qruqsp, 'qruqsp', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = qruqsp_core_dbHashQueryArrayTree($qruqsp, $strsql, 'qruqsp.core', array(
        array('container'=>'results', 'fname'=>'name', 'fields'=>array('name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['results']) || !is_array($rc['results']) ) {
        return array('stat'=>'ok', 'results'=>array());
    }
    return array('stat'=>'ok', 'results'=>$rc['results']);
}
?>
