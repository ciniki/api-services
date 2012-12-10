<?php
//
// Description
// -----------
// Search the service categories for a matching name.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to search.
// start_needle:	The string to search the categories for.
// limit:			(optional) The maximum number of results to return.
//					The default number to return is 15.
// 
// Returns
// -------
//
function ciniki_services_serviceSearchCategory($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search Word'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'services', 'private', 'checkAccess');
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.servicesSearchCategory', 0, 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Look for a matching category
	//
	$strsql = "SELECT category AS name "
		. "FROM ciniki_services "
		. "WHERE ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_services.status = 10 "
		. "AND (category LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "AND category <> '' "
			. ") "
		. "";
	$strsql .= "ORDER BY category "
		. "";
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	} else {
		$strsql .= "LIMIT 15 ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
		array('container'=>'categories', 'fname'=>'name', 'name'=>'category', 'fields'=>array('name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['categories']) ) {
		return array('stat'=>'ok', 'categories'=>array());
	}
	return array('stat'=>'ok', 'categories'=>$rc['categories']);
}
?>
