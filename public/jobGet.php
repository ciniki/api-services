<?php
//
// Description
// ===========
// This method will return all the details for a job, including tasks and notes if requested.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the service for.
// job_id:			The ID of the job to return the details for.
// children:		(optional) Request the tasks to be included in the results.
//
//					yes - include tasks
//					no - just the basic service information
// 
// Returns
// -------
// <job id="1" name="2012 Q1" service_name="HST">
//		<tasks>
//			<task id="23" step="1" name="The first step" duration="60" />
//		</tasks>
//		<notes>
//			<note id="1" content="The note contents" />
//		</notes>
// </job>
//
function ciniki_services_jobGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'job_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Job'), 
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.jobGet', 0, 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the job information
	//
	$strsql = "SELECT ciniki_service_jobs.id, ciniki_service_jobs.name, "
		. "DATE_FORMAT(ciniki_service_jobs.pstart_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS pstart_date, "
		. "DATE_FORMAT(ciniki_service_jobs.pend_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS pend_date, "
		. "DATE_FORMAT(ciniki_service_jobs.service_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS service_date, "
		. "ciniki_service_jobs.status, "
		. "DATE_FORMAT(ciniki_service_jobs.date_scheduled, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_scheduled, "
		. "DATE_FORMAT(ciniki_service_jobs.date_started, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_started, "
		. "DATE_FORMAT(ciniki_service_jobs.date_due, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_due, "
		. "DATE_FORMAT(ciniki_service_jobs.date_completed, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_completed, "
		. "ciniki_services.name AS service_name, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_service_jobs.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_service_jobs.last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
		. "FROM ciniki_service_jobs "
		. "LEFT JOIN ciniki_service_job_notes ON (ciniki_service_jobs.id = ciniki_service_job_notes.job_id) "
		. "LEFT JOIN ciniki_services ON (ciniki_service_jobs.service_id = ciniki_services.id) "
		. "WHERE ciniki_service_jobs.id = '" . ciniki_core_dbQuote($ciniki, $args['job_id']) . "' "
		. "AND ciniki_service_jobs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
		array('container'=>'jobs', 'fname'=>'id', 'name'=>'job',
			'fields'=>array('id', 'name', 'service_date', 'status', 'service_name',
				'pstart_date', 'pend_date', 
				'date_scheduled', 'date_started', 'date_due', 'date_completed', 
				'date_added', 'last_updated')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'857', 'msg'=>'No job found', 'err'=>$rc['err']));
	}
	if( !isset($rc['jobs']) || !isset($rc['jobs'][0]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'858', 'msg'=>'No job found'));
	}
	$job = $rc['jobs'][0]['job'];

	if( isset($args['children']) && $args['children'] == 'yes' ) {
		//
		// Get the tasks associated with a job
		//
		$strsql = "SELECT id, task_id, step, name, duration, status, "
			. "status AS status_text, "
			. "DATE_FORMAT(ciniki_service_job_tasks.date_scheduled, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_scheduled, "
			. "DATE_FORMAT(ciniki_service_job_tasks.date_started, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_started, "
			. "DATE_FORMAT(ciniki_service_job_tasks.date_due, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_due, "
			. "DATE_FORMAT(ciniki_service_job_tasks.date_completed, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_completed, "
			. "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
			. "DATE_FORMAT(CONVERT_TZ(last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
			. "FROM ciniki_service_job_tasks "
			. "WHERE ciniki_service_job_tasks.job_id = '" . ciniki_core_dbQuote($ciniki, $args['job_id']) . "' "
			. "ORDER BY step, name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
			array('container'=>'tasks', 'fname'=>'id', 'name'=>'task',
				'fields'=>array('id', 'task_id', 'step', 'name', 'duration', 'status', 'status_text',
					'date_scheduled', 'date_started', 'date_due', 'date_completed', 
					'date_added', 'last_updated'),
				'maps'=>array('status_text'=>array('10'=>'entered', '20'=>'started', '60'=>'completed', '61'=>'skipped')),
				),
			));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'859', 'msg'=>'No job found', 'err'=>$rc['err']));
		}
		if( !isset($rc['tasks']) ) {
			$job['tasks'] = array();
		} else {
			$job['tasks'] = $rc['tasks'];
		}

		//
		// Get the notes associated with a job
		//
		$strsql = "SELECT id, job_id, user_id, "
			. "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
			. "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(date_added) as DECIMAL(12,0)) as age, "
			. "content "
			. "FROM ciniki_service_job_notes "
			. "WHERE ciniki_service_job_notes.job_id = '" . ciniki_core_dbQuote($ciniki, $args['job_id']) . "' "
			. "ORDER BY ciniki_service_job_notes.date_added ASC "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQueryPlusDisplayNames');
		$rc = ciniki_core_dbRspQueryPlusDisplayNames($ciniki, $strsql, 'ciniki.services', 'notes', 'note', array('stat'=>'ok', 'notes'=>array()));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'856', 'msg'=>'Unable to get job', 'err'=>$rc['err']));
		}
		if( !isset($rc['notes']) ) {
			$job['notes'] = array();
		} else {
			$job['notes'] = $rc['notes'];
		}
	}

	return array('stat'=>'ok', 'job'=>$job);
}
?>
