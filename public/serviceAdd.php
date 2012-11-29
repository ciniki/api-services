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
// business_id:		The ID of the business to add the service to.
// name:			The name of the service.
// category:		(optional) The category for the service.
// duration:		(optional) The length of time in minutes the service should take to complete.
// repeat_type:		(optional) If the service is repeatable, how should it repeat.
//
//					10 - Daily
//					20 - Weekly
//					30 - Monthly
//					40 - Yearly
//
// repeat_interval:	(optional) The interval between repeating the service.  
//					If the repeat_type is daily, and interval is 4, the service
//					will repeat every 4 weeks.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_services_serviceAdd($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'name'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No name specified'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No category specified'), 
		'status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'10', 'errmsg'=>'No status specified'),
        'duration'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'errmsg'=>'No duration specified'), 
        'repeat_type'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'errmsg'=>'No repeat specified'), 
        'repeat_interval'=>array('required'=>'no', 'default'=>'1', 'blank'=>'yes', 'errmsg'=>'No repeat interval specified'), 
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.add'); 
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
	// Add the service to the database
	//
	$strsql = "INSERT INTO ciniki_services (uuid, business_id, status, "
		. "name, category, type, "
		. "duration, repeat_type, repeat_interval, repeat_number, "
		. "date_added, last_updated) VALUES ("
		. "UUID(), "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['category']) . "', "
		. "0, "
		. "'" . ciniki_core_dbQuote($ciniki, $args['duration']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['repeat_type']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['repeat_interval']) . "', "
		. "0, "
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
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'status',
		'name',
		'category',
		'duration',
		'repeat_type',
		'repeat_interval',
		);
	foreach($changelog_fields as $field) {
		$insert_name = $field;
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
				$args['business_id'], 1, 'ciniki_services', $service_id, $name, $args[$field]);
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

	return array('stat'=>'ok', 'id'=>$service_id);
}
?>
