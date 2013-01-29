<?php
//
// Description
// -----------
// This function will go through the history of the ciniki.customers module and add missing history elements.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_services_historyFix($ciniki) {
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
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'services', 'private', 'checkAccess');
	$rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.historyFix', 0, 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFixTableHistory');

	//
	// Check for items that are missing a add value in history
	//
	$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.services', $args['business_id'],
		'ciniki_services', 'ciniki_service_history', 
		array('uuid', 'status', 'name', 'category', 'type', 'duration', 
			'repeat_type', 'repeat_interval', 'repeat_number', 'due_after_days', 'due_after_months'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check ciniki_service_tasks for missing history
	//
	$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.services', $args['business_id'],
		'ciniki_service_tasks', 'ciniki_service_history', 
		array('uuid', 'service_id','step','name', 'description', 'instructions', 
			'duration', 'billable_hours'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Check ciniki_service_subscriptions for missing history
	//
	$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.services', $args['business_id'],
		'ciniki_service_subscriptions', 'ciniki_service_history', 
		array('uuid', 'service_id','customer_id','status', 'date_started', 'date_ended'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Check ciniki_service_jobs for missing history
	//
	$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.services', $args['business_id'],
		'ciniki_service_jobs', 'ciniki_service_history', 
		array('uuid', 'subscription_id', 'service_id','customer_id','project_id', 'invoice_id', 
			'name', 'pstart_date', 'pend_date', 'service_date', 'status', 
			'date_scheduled', 'date_started', 'date_due', 'date_completed', 'date_signedoff', 
			'efile_number', 'invoice_amount', 'tax1_name', 'tax1_amount', 'tax2_name', 'tax2_amount'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check ciniki_service_job_hours for missing history
	//
	$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.services', $args['business_id'],
		'ciniki_service_job_hours', 'ciniki_service_history', 
		array('uuid', 'job_id', 'task_id','user_id','date_started','hours','notes'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check ciniki_service_job_notes for missing history
	//
	$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.services', $args['business_id'],
		'ciniki_service_job_notes', 'ciniki_service_history', 
		array('uuid', 'job_id', 'task_id','user_id','content'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check ciniki_service_job_tasks for missing history
	//
	$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.services', $args['business_id'],
		'ciniki_service_job_tasks', 'ciniki_service_history', 
		array('uuid', 'job_id', 'task_id','step','name','duration','status', 'description',
			'date_scheduled', 'date_started', 'date_due', 'date_completed'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check ciniki_service_job_users for missing history
	//
	$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.services', $args['business_id'],
		'ciniki_service_job_users', 'ciniki_service_history', 
		array('uuid', 'job_id', 'user_id', 'perms'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check for items missing a UUID
	//
	$strsql = "UPDATE ciniki_service_history SET uuid = UUID() WHERE uuid = ''";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Remote any entries with blank table_key, they are useless we don't know what they were attached to
	//
	$strsql = "DELETE FROM ciniki_service_history WHERE table_key = ''";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}


	return array('stat'=>'ok');
}
?>
