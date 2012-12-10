<?php
//
// Description
// -----------
// This method will return the list of changes for a subscription field.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// subscription_id:		The ID of the subscription to get the history for.
// field:				The name of the field to get the history for.
//
// Returns
// -------
//
function ciniki_services_subscriptionHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'subscription_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subscription'), 
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
	$rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.subscriptionHistory', 0, 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( $args['field'] == 'service_date'
		|| $args['field'] == 'date_scheduled'
		|| $args['field'] == 'date_started'
		|| $args['field'] == 'date_due'
		|| $args['field'] == 'date_completed' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
		return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.services', 'ciniki_service_history', 
			$args['business_id'], 'ciniki_service_subscriptions', $args['subscription_id'], $args['field'], 'date');
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
		$args['business_id'], 'ciniki_service_subscriptions', $args['subscription_id'], $args['field']);
}
?>
