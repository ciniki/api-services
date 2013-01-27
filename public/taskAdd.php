<?php
//
// Description
// ===========
// This method will add a new task to an existing service.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to add the service to.
// service_id:		The ID of the service to add the task to.
// step:			The step number in the sequence of steps.  If the number
//					exists all existing steps will be increased by one.
// name:			The name of the service.
// description:		(optional) The description of the task.
// instructions:	(optional) The instructions for performing the task.
// duration:		(optional) The length of time in minutes the task should take.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_services_taskAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'service_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Service'), 
		'step'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Step'),
		'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Description'), 
        'instructions'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Instructions'), 
        'duration'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Duration'), 
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.taskAdd', 0, 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
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
	$strsql = "SELECT ciniki_service_tasks.id, step "
		. "FROM ciniki_service_tasks, ciniki_services "
		. "WHERE ciniki_service_tasks.service_id = '" . ciniki_core_dbQuote($ciniki, $args['service_id']) . "' "
		. "AND ciniki_service_tasks.step >= '" . ciniki_core_dbQuote($ciniki, $args['step']) . "' "
		. "AND ciniki_service_tasks.service_id = ciniki_services.id "
		. "ORDER BY step "
		. "";
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.services', 'tasks', 'step');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'840', 'msg'=>'Unable to check for existing step', 'err'=>$rc['err']));
	}
	if( isset($rc['tasks']) && isset($rc['tasks'][$args['step']]) ) {
		foreach($rc['tasks'] as $step => $task) {
			$strsql = "UPDATE ciniki_service_tasks "
				. "SET step = '" . ciniki_core_dbQuote($ciniki, $task['step'] + 1) . "' "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $task['id']) . "' "
				. "AND service_id = '" . ciniki_core_dbQuote($ciniki, $args['service_id']) . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.services');
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'845', 'msg'=>'Unable to update existing steps', $rc['err']));
			}
			// Update the history
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
				$args['business_id'], 2, 'ciniki_service_tasks', $task['id'], 'step', $task['step']+1);
		}
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
	$strsql = "INSERT INTO ciniki_service_tasks (uuid, business_id, service_id, step, "
		. "name, description, instructions, duration, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['service_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['step']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['description']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['instructions']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['duration']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())"
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'839', 'msg'=>'Unable to add task'));
	}
	$task_id = $rc['insert_id'];

	//
	// Add the uuid to the history
	//
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
		$args['business_id'], 1, 'ciniki_service_tasks', $task_id, 'uuid', $uuid);

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
		$insert_name = $field;
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', 
				$args['business_id'], 1, 'ciniki_service_tasks', $task_id, $insert_name, $args[$field]);
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

	$ciniki['syncqueue'][] = array('push'=>'ciniki.services.task', 'args'=>array('id'=>$task_id));

	return array('stat'=>'ok', 'id'=>$task_id);
}
?>
