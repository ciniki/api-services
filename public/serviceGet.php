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
// service_id:		The ID of the service to return the details for.
// children:		(optional) Request the tasks to be included in the results.
//
//					yes - include tasks
//					no - just the basic service information
// 
// Returns
// -------
// <service id="1" name="Service Name" category="Service Category" duration="60" repeat_type="40" repeat_interval="1">
// 		<tasks>
//			<task id="23" name="Task 1" step="1" duration="30" description="" instruction=""/>
//			<task id="41" name="Task 2" step="2" duration="10" description="" instruction=""/>
//			<task id="15" name="Task 3" step="3" duration="20" description="" instruction=""/>
//		</tasks>
// </service>
//
function ciniki_services_serviceGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'service_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Service'), 
		'children'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Children'),
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.serviceGet', $args['service_id'], 0); 
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
	// Get the service information
	//
	if( isset($args['children']) && $args['children'] == 'yes' ) {
		$strsql = "SELECT ciniki_services.id, ciniki_services.status, ciniki_services.type, "
			. "ciniki_services.name, ciniki_services.category, "
			. "ciniki_services.duration, ciniki_services.repeat_type, ciniki_services.repeat_interval, "
			. "ciniki_services.due_after_days, ciniki_services.due_after_months, "
			. "ciniki_service_tasks.id AS task_id, "
			. "ciniki_service_tasks.step, ciniki_service_tasks.name AS task_name, "
			. "ciniki_service_tasks.description, ciniki_service_tasks.instructions, "
			. "ciniki_service_tasks.duration, "
			. "DATE_FORMAT(CONVERT_TZ(ciniki_services.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
			. "DATE_FORMAT(CONVERT_TZ(ciniki_services.last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
			. "FROM ciniki_services "
			. "LEFT JOIN ciniki_service_tasks ON (ciniki_services.id = ciniki_service_tasks.service_id) "
			. "WHERE ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_services.id = '" . ciniki_core_dbQuote($ciniki, $args['service_id']) . "' "
			. "";
	
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
			array('container'=>'services', 'fname'=>'id', 'name'=>'service',
				'fields'=>array('id', 'status', 'name', 'category', 'type', 'duration', 
					'repeat_type', 'repeat_interval', 'date_added', 'last_updated', 
					'due_after_days', 'due_after_months')),
			array('container'=>'tasks', 'fname'=>'task_id', 'name'=>'task',
				'fields'=>array('id'=>'task_id', 'step', 'name'=>'task_name', 'description', 'instructions', 'duration')),
			));
	} else {
		$strsql = "SELECT ciniki_services.id, ciniki_services.status, ciniki_services.type, "
			. "ciniki_services.name, ciniki_services.category, "
			. "ciniki_services.duration, ciniki_services.repeat_type, ciniki_services.repeat_interval, "
			. "ciniki_services.due_after_days, ciniki_services.due_after_months, "
			. "DATE_FORMAT(CONVERT_TZ(ciniki_services.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
			. "DATE_FORMAT(CONVERT_TZ(ciniki_services.last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
			. "FROM ciniki_services "
			. "WHERE ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_services.id = '" . ciniki_core_dbQuote($ciniki, $args['service_id']) . "' "
			. "";
	
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
			array('container'=>'services', 'fname'=>'id', 'name'=>'service',
				'fields'=>array('id', 'status', 'name', 'category', 'type', 'duration', 
					'repeat_type', 'repeat_interval', 'date_added', 'last_updated', 
					'due_after_days', 'due_after_months')),
			));
	}
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['services'][0]['service']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'837', 'msg'=>'No service found'));
	}
	$service = $rc['services'][0]['service'];

	//
	// Setup the repeat string description
	//
	$service['repeat_description'] = '';
	if( isset($service['repeat_type']) && $service['repeat_type'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'formatRepeatDescription');
		$rc = ciniki_users_formatRepeatDescription($ciniki, 
			$service['repeat_type'], $service['repeat_interval'], '', '', '');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$service['repeat_description'] = $rc['description'];
	}

	return array('stat'=>'ok', 'service'=>$service);
}
?>
