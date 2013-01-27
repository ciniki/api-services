<?php
//
// Description
// ===========
// This method will add a new service subscription to a customer.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the service to.
// customer_id:			The ID of the customer to add the service to.
// service_id:			The ID of the service to add.
// status:				(optional) The status for the service, defaults to 10.
//	
//						10 - active
//						60 - deleted
//
// date_started:		The date the service started for the customer.
// date_ended:			(optional) The date the service ended for the customer.
//					
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_services_subscriptionAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
		'service_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Service'),
		'status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'10', 'name'=>'Status',
			'validlist'=>array('10','60')),
		'date_started'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Start Date'),
		'date_ended'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'default'=>'', 'name'=>'End Date'),
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.subscriptionAdd', $args['service_id'], $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get a new UUID
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	$rc = ciniki_core_dbUUID($ciniki, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$uuid = $rc['uuid'];

	//
	// Add the service to the database
	//
	$strsql = "INSERT INTO ciniki_service_subscriptions (uuid, business_id, "
		. "service_id, customer_id, status, "
		. "date_started, date_ended, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['service_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['date_started']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['date_ended']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())"
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'838', 'msg'=>'Unable to add service'));
	}
	$subscription_id = $rc['insert_id'];

	//
	// Add the uuid to the history
	//
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
		$args['business_id'], 1, 'ciniki_service_subscriptions', $subscription_id, 'uuid', $uuid);

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'service_id',
		'customer_id',
		'status',
		'date_started',
		'date_ended',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
				$args['business_id'], 1, 'ciniki_service_subscriptions', $subscription_id, $field, $args[$field]);
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'services');

	$ciniki['syncqueue'][] = array('push'=>'ciniki.services.subscription', 
		'args'=>array('id'=>$subscription_id));

	return array('stat'=>'ok', 'id'=>$subscription_id);
}
?>
