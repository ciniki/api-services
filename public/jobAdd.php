<?php
//
// Description
// ===========
// This method will add a new service job for a customer for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the service to.
// service_id:			The ID of the service to add.
// customer_id:			The ID of the customer to add the service to.
// subscription_id:		(optional) The subscription ID of the job, if it is associated with a service subscription.
// tracking_id:			(optional) The tracking ID of the job, used only for the business, not internal.
// status:				(optional) The status for the service, defaults to 10.
//	
//						10 - entered (default)
//						20 - started
//						30 - pending
//						50 - completed
//						60 - signed off
//						61 - skipped
//
// name:				The name for this job, typically the year or some portion of the date.
// pstart_date:			(optional) The date of the first day of the time period the job covers.
// pend_date:			(optional) The date of the last day of the time period the job covers.
// service_date:		(optional) The date of the job, or the last day of the time period the job covers.
// date_scheduled:		(optional) The date the job is scheduled for.
// date_started:		(optional) The date the job was started.
// date_due:			(optional) The date the job is due to be finished by.
// date_completed:		(optional) The date the job was completed on.
// date_signedoff:		(optional) The date the job was signed off.
//
// note:				(optional) A note to attach to the thread of notes.
//
//					
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_services_jobAdd($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'service_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Service'),
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
		'subscription_id'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Subscription'),
		'tracking_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tracking ID'),
		'status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'10', 'name'=>'Status',
			'validlist'=>array('10','20','30','50', '60','61')),
		'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
		'pstart_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Start Date'),
		'pend_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'End Date'),
		'service_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'default'=>'', 'name'=>'Service Date'),
		'date_scheduled'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'default'=>'', 'name'=>'Date Scheduled'),
		'date_started'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'default'=>'', 'name'=>'Date Started'),
		'date_due'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'default'=>'', 'name'=>'Date Due'),
		'date_completed'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'default'=>'', 'name'=>'Date Completed'),
		'date_signedoff'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'default'=>'', 'name'=>'Date Signed Off'),
		'note'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Note'),
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.jobAdd', $args['service_id'], $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Check if subscription belongs to business
	//
	if( isset($args['subscription_id']) && $args['subscription_id'] > 0 ) {
		$strsql = "SELECT ciniki_service_subscriptions.id "
			. "FROM ciniki_service_subscriptions "
			. "WHERE ciniki_service_subscriptions.id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
			. "AND ciniki_service_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'subscription');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['subscription']) || $rc['subscription']['id'] != $args['subscription_id'] ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'876', 'msg'=>'Invalid subscription'));
		}
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
	// Add the job to the database
	//
	$strsql = "INSERT INTO ciniki_service_jobs (uuid, business_id, subscription_id, "
		. "service_id, customer_id, tracking_id, name, service_date, status, "
		. "pstart_date, pend_date, "
		. "date_scheduled, date_started, date_due, date_completed, date_signedoff, "
		. "date_added, last_updated) VALUES ("
		. "UUID(), "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['service_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['tracking_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['service_date']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['pstart_date']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['pend_date']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['date_scheduled']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['date_started']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['date_due']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['date_completed']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['date_signedoff']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())"
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'862', 'msg'=>'Unable to add service'));
	}
	$job_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'subscription_id',
		'service_id',
		'customer_id',
		'tracking_id',
		'name',
		'pstart_date',
		'pend_date',
		'service_date',
		'status',
		'date_scheduled',
		'date_started',
		'date_due',
		'date_completed',
		'date_signedoff',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
				$args['business_id'], 1, 'ciniki_service_jobs', $job_id, $field, $args[$field]);
		}
	}

	//
	// Setup the tasks for this job, from the ciniki_service_tasks
	//
	$strsql = "SELECT id, step, name, duration "
		. "FROM ciniki_service_tasks "
		. "WHERE service_id = '" . ciniki_core_dbQuote($ciniki, $args['service_id']) . "' "
		. "ORDER BY step, name "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'task');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'855', 'msg'=>'Unable to add service', 'err'=>$rc['err']));
	}
	$tasks = $rc['rows'];
	foreach($tasks as $tid => $task) {
		$strsql = "INSERT INTO ciniki_service_job_tasks (job_id, task_id, "
			. "step, name, duration, status, "
			. "date_due, "
			. "date_added, last_updated) VALUES ("
			. "'" . ciniki_core_dbQuote($ciniki, $job_id) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $task['id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $task['step']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $task['name']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $task['duration']) . "', "
			. "'10', "
			. "'" . ciniki_core_dbQuote($ciniki, $args['date_due']) . "', "
			. "UTC_TIMESTAMP(), UTC_TIMESTAMP())"
			. "";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.services');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'861', 'msg'=>'Unable to add job', 'err'=>$rc['err']));
		}
		$task_id = $rc['insert_id'];
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
			$args['business_id'], 1, 'ciniki_service_job_tasks', $task_id, 'step', $task['step']);
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
			$args['business_id'], 1, 'ciniki_service_job_tasks', $task_id, 'name', $task['name']);
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
			$args['business_id'], 1, 'ciniki_service_job_tasks', $task_id, 'duration', $task['duration']);
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
			$args['business_id'], 1, 'ciniki_service_job_tasks', $task_id, 'status', '10');
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
			$args['business_id'], 1, 'ciniki_service_job_tasks', $task_id, 'date_due', $args['date_due']);
	}

	//
	// Check if there's a note to add
	//
	if( isset($args['note']) && $args['note'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddFollowup');
		$rc = ciniki_core_threadAddFollowup($ciniki, 'ciniki.services', 'ciniki_service_job_notes', 'job', $job_id, array(
			'user_id'=>$ciniki['session']['user']['id'],
			'job_id'=>$job_id,
			'content'=>$args['note'],
			));
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'866', 'msg'=>'Unable to update job'));
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

	return array('stat'=>'ok', 'id'=>$job_id);
}
?>
