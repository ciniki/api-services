<?php
//
// Description
// -----------
// This method will return the history for a service field.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// service_id:			The ID of the service to get the history for.
// field:				The field to get the history for.
//
// Returns
// -------
//
function ciniki_services_serviceHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'service_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Service'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'services', 'private', 'checkAccess');
	$rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.serviceHistory', $args['service_id'], 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
		$args['business_id'], 'ciniki_services', $args['service_id'], $args['field']);
}
?>
