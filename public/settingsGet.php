<?php
//
// Description
// -----------
// This method will turn the settings for a services module for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the ATDO settings for.
// 
// Returns
// -------
//
function ciniki_services_settingsGet($ciniki) {
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.getSettings', 0, 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

	//
	// Grab the settings for the business from the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_service_settings', 'business_id', $args['business_id'], 'ciniki.services', 'settings', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Return the response, including colour arrays and todays date
	//
	return $rc;
}
?>
