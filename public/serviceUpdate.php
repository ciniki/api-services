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
// business_id:		The ID of the business the service belongs to.
// service_id:		The ID of the service to update.
// name:			(optional) The new name for the service.
// category:		(optional) The new category for the service.
// status:			(optional) The new status for the service.
// duration:		(optional) The new duration for the service.
// repeat_type:		(optional) The new type of repeat for the service.
//
//					10 - Daily
//					20 - Weekly
//					30 - Monthly
//					40 - Yearly
//
// repeat_interval:	(optional) The new repeat interval for the service.
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
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'service_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No ID specified'), 
		'name'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No name specified'),
        'category'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No category specified'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No status specified'), 
        'duration'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No duration specified'), 
        'repeat_type'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No repeat specified'), 
        'repeat_interval'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No repeat interval specified'), 
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.serviceUpdate'); 
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
		'status',
		'duration',
		'repeat_type',
		'repeat_interval',
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
