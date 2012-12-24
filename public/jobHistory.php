<?php
//
// Description
// -----------
// This function will return the list of changes made to a job field.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// job_id:				The ID of the job to get the history for.
// field:				The field to get the changes for.
//
// Returns
// -------
//
function ciniki_services_jobHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'job_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Job'), 
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
	$rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.jobHistory', 0, 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( $args['field'] == 'pstart_date'
		|| $args['field'] == 'pend_date'
		|| $args['field'] == 'service_date'
		|| $args['field'] == 'date_scheduled'
		|| $args['field'] == 'date_started'
		|| $args['field'] == 'date_due'
		|| $args['field'] == 'date_completed' 
		|| $args['field'] == 'date_signedoff' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
		return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.services', 'ciniki_service_history', 
			$args['business_id'], 'ciniki_service_jobs', $args['job_id'], $args['field'], 'date');
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
		$args['business_id'], 'ciniki_service_jobs', $args['job_id'], $args['field']);
}
?>
