<?php
//
// Description
// ===========
// This method will return all the details for a configured service, and 
// the tasks associated with the service.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the service for.
// task_id:			The ID of the task to return the details for.
// 
// Returns
// -------
// <task id="23" name="Task 1" step="1" duration="30" description="" instruction=""/>
//
function ciniki_services_taskGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'task_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Task'), 
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.taskGet', $args['task_id'], 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the task information
	//
	$strsql = "SELECT ciniki_service_tasks.id, ciniki_service_tasks.service_id, "
		. "ciniki_service_tasks.step, "
		. "ciniki_service_tasks.name, ciniki_service_tasks.description, "
		. "ciniki_service_tasks.instructions, ciniki_service_tasks.duration, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_service_tasks.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_service_tasks.last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
		. "FROM ciniki_service_tasks, ciniki_services "
		. "WHERE ciniki_service_tasks.id = '" . ciniki_core_dbQuote($ciniki, $args['task_id']) . "' "
		. "AND ciniki_service_tasks.service_id = ciniki_services.id "
		. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'task');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['task']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'842', 'msg'=>'No service found'));
	}

	return array('stat'=>'ok', 'task'=>$rc['task']);
}
?>
