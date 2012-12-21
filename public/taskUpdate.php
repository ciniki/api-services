<?php
//
// Description
// -----------
// This method will update the details of a task for a service.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the service belongs to.
// task_id:			The ID of the task to update.
// step:			(optional) The new step for the service.
//					Any changes in step number will adjust other step
//					number to make room in the list so no duplicate step numbers.
// name:			(optional) The new name for the service.
// description:		(optional) The new description for the service.
// instructions:	(optional) The new instructions for the service.
// duration:		(optional) The new duration for the service.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_services_taskUpdate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'task_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Task'), 
		'step'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Step'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'description'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Description'), 
        'instructions'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Instructions'), 
        'duration'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Duration'), 
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.taskUpdate', $args['task_id'], 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

	//  
	// Turn off autocommit
	//  
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Check if other steps need their step number adjusted to make room for 
	// this task step number.
	//
	if( isset($args['step']) && $args['step'] != '' ) {
		//
		// Get the service_id this task is a part of
		//
		$strsql = "SELECT ciniki_service_tasks.id, ciniki_service_tasks.service_id, step "
			. "FROM ciniki_service_tasks, ciniki_services "
			. "WHERE ciniki_service_tasks.id = '" . ciniki_core_dbQuote($ciniki, $args['task_id']) . "' "
			. "AND ciniki_service_tasks.service_id = ciniki_services.id "
			. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'task');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'847', 'msg'=>'Task does not exist', 'err'=>$rc['err']));
		}
		if( !isset($rc['task']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'848', 'msg'=>'Task does not exist', 'err'=>$rc['err']));
		}
		$service_id = $rc['task']['service_id'];
		if( isset($rc['task']['step']) && $rc['task']['step'] != $args['step'] ) {
			//
			// Move the steps required, to allow for the new step number.  This
			// might mean shifting them up or down.
			//
			$increment = 0;
			if( $args['step'] > $rc['task']['step'] ) {
				$strsql = "SELECT ciniki_service_tasks.id, step "
					. "FROM ciniki_service_tasks, ciniki_services "
					. "WHERE ciniki_service_tasks.service_id = '" . ciniki_core_dbQuote($ciniki, $rc['task']['service_id']) . "' "
					. "AND ciniki_service_tasks.service_id = ciniki_services.id "
					. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND step > '" . ciniki_core_dbQuote($ciniki, $rc['task']['step']) . "' "
					. "AND step <= '" . ciniki_core_dbQuote($ciniki, $args['step']) . "' "
					. "ORDER BY step "
					. "";
				$increment = -1;
			} else {
				$strsql = "SELECT ciniki_service_tasks.id, step "
					. "FROM ciniki_service_tasks, ciniki_services "
					. "WHERE ciniki_service_tasks.service_id = '" . ciniki_core_dbQuote($ciniki, $rc['task']['service_id']) . "' "
					. "AND ciniki_service_tasks.service_id = ciniki_services.id "
					. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND step >= '" . ciniki_core_dbQuote($ciniki, $args['step']) . "' "
					. "AND step < '" . ciniki_core_dbQuote($ciniki, $rc['task']['step']) . "' "
					. "ORDER BY step "
					. "";
				$increment = 1;
			}
			$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.services', 'tasks', 'step');
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'846', 'msg'=>'Unable to check for existing step', 'err'=>$rc['err']));
			}
			// 
			// Move each step between old and new
			//
			foreach($rc['tasks'] as $step => $task) {
				$strsql = "UPDATE ciniki_service_tasks "
					. "SET step = '" . ciniki_core_dbQuote($ciniki, $task['step'] + $increment) . "' "
					. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $task['id']) . "' "
					. "AND service_id = '" . ciniki_core_dbQuote($ciniki, $service_id) . "' "
					. "";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.services');
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'864', 'msg'=>'Unable to update existing steps', $rc['err']));
				}
				// Update the history
				$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
					$args['business_id'], 2, 'ciniki_service_tasks', $task['id'], 'step', $task['step']+$increment);
			}
		}
	}

	//
	// Add the order to the database
	//
	$strsql = "UPDATE ciniki_service_tasks SET last_updated = UTC_TIMESTAMP()";

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'step',
		'name',
		'description',
		'instructions',
		'duration',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
				$args['business_id'], 2, 'ciniki_service_tasks', $args['task_id'], $field, $args[$field]);
		}
	}
	$strsql .= "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['task_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'841', 'msg'=>'Unable to update task'));
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
