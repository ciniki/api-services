<?php
//
// Description
// ===========
// This method will return the list of repeating/subscription services for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the task list for.
// 
// Returns
// -------
// <services>
// 		<service id="1" name="Service Name" />
// </services>
//
function ciniki_services_servicesRepeating($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.servicesRepeating', 0, 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

	$strsql = "SELECT ciniki_services.id, "
		. "ciniki_services.name, ciniki_services.category, "
		. "ciniki_services.repeat_type "
		. "FROM ciniki_services "
		. "WHERE ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_services.repeat_type > 0 "
		. "";
	$strsql .= "ORDER BY ciniki_services.category, ciniki_services.name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
		array('container'=>'services', 'fname'=>'id', 'name'=>'service',
			'fields'=>array('id', 'category', 'name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['services']) ) {
		return array('stat'=>'ok', 'services'=>array());
	}
	return array('stat'=>'ok', 'services'=>$rc['services']);
}
?>
