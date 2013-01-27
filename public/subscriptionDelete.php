<?php
//
// Description
// -----------
// This method will remove a service subscription from a customer, but
// all jobs must be removed first.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the customer is attached to.
// subscription_id:		The ID of the subscription to be removed.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_services_subscriptionDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'subscription_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subscription'), 
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.subscriptionDelete', $args['subscription_id'], 0); 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Check to make sure all the jobs have been removed
	//
	$strsql = "SELECT COUNT(*) AS num_jobs FROM ciniki_service_jobs "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'jobs');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'879', 'msg'=>'Unable to locate job tasks.', 'err'=>$rc['err']));
	}
	if( !isset($rc['jobs']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'880', 'msg'=>'Unable to locate job tasks.'));
	}
	if( isset($rc['jobs']['num_jobs']) && $rc['jobs']['num_jobs'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'881', 'msg'=>'All jobs must be removed from the subscription before the subscription can be removed.'));
	}

	//
	// Get the uuid of the customer to be deleted
	//
	$strsql = "SELECT uuid FROM ciniki_service_subscriptions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'subscription');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['subscription']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'110', 'msg'=>'Unable to find existing subscription'));
	}
	$uuid = $rc['subscription']['uuid'];

	//
	// Remove the customer email address from the database.  It is still there in 
	// the ciniki_customer_history table.
	//
	$strsql = "DELETE FROM ciniki_service_subscriptions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' ";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return $rc;
	}
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', $args['business_id'], 
		3, 'ciniki_service_subscriptions', $args['subscription_id'], '*', '');

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
		'args'=>array('delete_uuid'=>$uuid, 'delete_id'=>$args['subscription_id']));

	return array('stat'=>'ok');
}
?>
