<?php
//
// Description
// -----------
// This method will update the details for a service configuration.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the service belongs to.
// service_id:			The ID of the service to update.
// name:				(optional) The new name for the service.
// category:			(optional) The new category for the service.
// type:				(optional) The type of the service.
//					
//						1 - Generic
//						10 - Tax Preparation
//
// status:				(optional) The new status for the service.
//
//						10 - started
//						
// duration:			(optional) The new duration for the service.
// repeat_type:			(optional) The new type of repeat for the service.
//
//						10 - Daily **future**
//						20 - Weekly **future**
//						30 - Monthly
//						40 - Yearly
//
// repeat_interval:		(optional) The new repeat interval for the service.
// 
// due_after_days:		(optional) The number of days after the service date the service will be due.
// due_after_months:	(optional) The number of months after the service date the service will be due.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_services_serviceUpdate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'service_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Service'), 
		'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'category'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Category'), 
		'type'=>array('required'=>'no', 'blank'=>'no', 
			'validlist'=>array('1', '10'), 'name'=>'Type'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'), 
        'duration'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Duration'), 
        'repeat_type'=>array('required'=>'no', 
			'validlist'=>array('0', '10', '20', '30', '40'), 'name'=>'Repeat Type'), 
        'repeat_interval'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Repeat Interval'), 
        'due_after_days'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Due After Days'), 
        'due_after_months'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Due After Months'), 
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.serviceUpdate', $args['service_id'], 0); 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the order to the database
	//
	$strsql = "UPDATE ciniki_services SET last_updated = UTC_TIMESTAMP()";

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'name',
		'category',
		'type',
		'status',
		'duration',
		'repeat_type',
		'repeat_interval',
		'due_after_days',
		'due_after_months',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
				$args['business_id'], 2, 'ciniki_services', $args['service_id'], $field, $args[$field]);
		}
	}
	$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['service_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'836', 'msg'=>'Unable to update task'));
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

	return array('stat'=>'ok');
}
?>
