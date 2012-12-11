<?php
//
// Description
// -----------
// This method will return the history for a service task.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// task_id:				The ID of the task to get the history for.
// field:				The field to get the history for.
//
// Returns
// -------
//
function ciniki_services_taskHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'task_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Task'), 
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
	$rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.taskHistory', $args['task_id'], 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
		$args['business_id'], 'ciniki_service_tasks', $args['task_id'], $args['field']);
}
?>
