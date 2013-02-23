<?php
//
// Description
// ===========
// This method will add a new service configuation to a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the service to.
// type:				The type of the service.
//					
//						1 - Generic
//						10 - Tax Preparation
//
// name:				The name of the service.
// category:			(optional) The category for the service.
// duration:			(optional) The length of time in minutes the service should take to complete.
// repeat_type:			(optional) If the service is repeatable, how should it repeat.
//
//						10 - Daily **future**
//						20 - Weekly **future**
//						30 - Monthly
//						40 - Yearly
//
// repeat_interval:		(optional) The interval between repeating the service.  
//						If the repeat_type is daily, and interval is 4, the service
//						will repeat every 4 weeks.
//
// due_after_days:		(optional) The number of days after the service date the service will be due.
// due_after_months:	(optional) The number of months after the service date the service will be due.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_services_serviceAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type', 
			'validlist'=>array('1', '10')),
		'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Category'), 
		'status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'10', 'name'=>'Status'),
        'duration'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Duration'), 
        'repeat_type'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Repeat Type', 
			'validlist'=>array('0', '10', '20', '30', '40')), 
        'repeat_interval'=>array('required'=>'no', 'default'=>'1', 'blank'=>'yes', 'name'=>'Repeat Interval'), 
        'due_after_days'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Due After Days'), 
        'due_after_months'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Due After Months'), 
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.serviceAdd', 0, 0); 
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
	$strsql = "INSERT INTO ciniki_services (uuid, business_id, status, "
		. "name, category, type, "
		. "duration, repeat_type, repeat_interval, repeat_number, "
		. "due_after_days, due_after_months, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['category']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['type']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['duration']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['repeat_type']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['repeat_interval']) . "', "
		. "0, "
		. "'" . ciniki_core_dbQuote($ciniki, $args['due_after_days']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['due_after_months']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())"
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'835', 'msg'=>'Unable to add service'));
	}
	$service_id = $rc['insert_id'];

	//
	// Add the uuid to the history
	//
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
		$args['business_id'], 1, 'ciniki_services', $service_id, 'uuid', $uuid);

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'status',
		'name',
		'category',
		'type',
		'duration',
		'repeat_type',
		'repeat_interval',
		'due_after_days',
		'due_after_months',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
				$args['business_id'], 1, 'ciniki_services', $service_id, $field, $args[$field]);
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

	$ciniki['syncqueue'][] = array('push'=>'ciniki.services.service', 
		'args'=>array('id'=>$service_id));

	return array('stat'=>'ok', 'id'=>$service_id);
}
?>
