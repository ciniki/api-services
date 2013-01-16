<?php
//
// Description
// ===========
// This method will return the list of jobs, both actual and predicted for a
// given time period.  If no time period is specified, the current month will be
// used.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the task list for.
// service_id:		The ID of the service to get the jobs for.
// year:			(optional) The year to search for the jobs for the service.
// month:			(optional) The month to search for the jobs for the service.
// 
// Returns
// -------
// <jobs>
//		<job id="23" name="2007" status="60" status_text="Completed" pstart_date="Jan 1, 2007" pend_date="Dec 31, 2007" service_date="Dec 31, 2007" date_schedule="" date_due="Apr 31, 2008" 
//			date_started="Apr 5, 2010" date_completed="Apr 10, 2010" />
//		<job id="0" name="2008" status="0" status_text="Missing" pstart_date="Jan 1, 2008" pend_date="Dec 31, 2008" service_date="Dec 31, 2008" date_schedule="" date_due="Apr 31, 2009" 
//			date_started="" date_completed="" />
//		<job id="783" name="2009" status="61" status_text="Skipped" pstart_date="Jan 1, 2009" pend_date="Dec 31, 2009" service_date="Dec 31, 2009" date_schedule="" date_due="Apr 31, 2010"
//			date_started="" date_completed="" />
//		<job id="783" name="2010" status="60" status_text="Completed" pstart_date="Jan 1, 2010" pend_date="Dec 31, 2010" service_date="Dec 31, 2010" date_schedule="" date_due="Apr 31, 2011"
//			date_started="Mar 1, 2011" date_completed="Mar 23, 2011" />
//		<job id="783" name="2011" status="60" status_text="Completed" pstart_date="Jan 1, 2011" pend_date="Dec 31, 2011" service_date="Dec 31, 2011" date_schedule="" date_due="Apr 31, 2012"
//			date_started="Feb 25, 2012" date_completed="Mar 23, 2012" />
//		<job id="783" name="2012" status="0" status_text="Upcoming" pstart_date="Jan 1, 2012" pend_date="Dec 31, 2012" service_date="Dec 31, 2012" date_schedule="" date_due="Apr 31, 2013"
//			date_started="" date_completed="Mar 23, 2013" />
// </jobs>
//
function ciniki_services_serviceJobs($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'service_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Service'), 
        'year'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Year'), 
        'month'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Month'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	if( !isset($args['year']) ) {
		$args['year'] = date('Y');
	}
	if( !isset($args['month']) ) {
		$args['month'] = date('m');
	}

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'services', 'private', 'checkAccess');
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.serviceJobs', $args['service_id'], 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	//
	// Setup the timezone offset, to get back proper UTC dates from database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Load the list of status messages for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'services', 'private', 'statusList');
	$rc = ciniki_services_statusList($ciniki, $args['business_id']);
	$status_texts = $rc['list'];

	//
	// Check if year and month are for a future month
	//
	$default_status = 1; // Missing
	if( $args['year'] > date('Y') || ($args['year'] == date('Y') && $args['month'] > date('m')) ) {
		$default_status = 2; // Upcoming
	}
	//
	// Get the list of jobs that should be due for the month, then
	// merge in the actual jobs.
	//
	$strsql = "SELECT ciniki_service_subscriptions.id, ciniki_service_subscriptions.service_id, "
		. "CONCAT_WS('-', ciniki_service_subscriptions.id, ciniki_service_jobs.id) AS list_id, "
		. "IFNULL(ciniki_service_jobs.tracking_id, '') AS tracking_id, "
		. "IFNULL(ciniki_service_jobs.id, '0') AS job_id, "
		. "IFNULL(ciniki_service_jobs.name, '') AS name, "
		. "IFNULL(ciniki_service_jobs.status, '" . ciniki_core_dbQuote($ciniki, $default_status) . "') AS status, "
		. "IFNULL(ciniki_service_jobs.status, '" . ciniki_core_dbQuote($ciniki, $default_status) . "') AS status_text, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.pstart_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS pstart_date, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.pend_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS pend_date, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.service_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS service_date, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.date_due, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_due, "
		. "ciniki_customers.id AS customer_id, ciniki_customers.type AS customer_type, "
		. "CONCAT_WS(' ', first, last) AS customer_name, ciniki_customers.company, "
		. "("
			. "("
				. "("
					. "PERIOD_DIFF("
						. "'" . ciniki_core_dbQuote($ciniki, sprintf("%04d%02d", $args['year'], $args['month'])) . "', "
						. "DATE_FORMAT(ciniki_service_subscriptions.date_started, '%Y%m')"
					. ")"
					. "-due_after_months"
					. "-(IF(DAYOFMONTH(ciniki_service_subscriptions.date_started)>1,1,0))"
				. ") "
				. "MOD 12 "
			. ") DIV (CASE repeat_type WHEN 40 THEN 12 WHEN 30 THEN repeat_interval END))+1 AS quarter, "
		. "("
			. "("
				. "PERIOD_DIFF("
					. "'" . ciniki_core_dbQuote($ciniki, sprintf("%04d%02d", $args['year'], $args['month'])) . "', "
					. "DATE_FORMAT(ciniki_service_subscriptions.date_started, '%Y%m')"
				. ")"
				. "-due_after_months"
				. "-(IF(DAYOFMONTH(ciniki_service_subscriptions.date_started)>1,1,0))"
			. ") "
			. "DIV 12) AS year_offset, "
		. "due_after_months, "
		. "CASE repeat_type WHEN 40 THEN 12 WHEN 30 THEN repeat_interval END AS period_months, "
		. "IFNULL(DATE_FORMAT(ciniki_service_subscriptions.date_started, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS subscription_date_started, "
		. "ciniki_services.repeat_type, ciniki_services.repeat_interval, "
		. "IFNULL(ciniki_users.display_name, '') AS assigned_names "
		. "FROM ciniki_service_subscriptions "
		. "LEFT JOIN ciniki_service_jobs ON (ciniki_service_subscriptions.id = ciniki_service_jobs.subscription_id "
			. "AND ciniki_service_subscriptions.customer_id = ciniki_service_jobs.customer_id "
			. "AND ciniki_service_jobs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND DATE_FORMAT(ciniki_service_jobs.date_due, '%Y%m') = '" . ciniki_core_dbQuote($ciniki, sprintf("%04d%02d", $args['year'], $args['month'])) . "' "
			. ") "
		. "LEFT JOIN ciniki_services ON (ciniki_service_subscriptions.service_id = ciniki_services.id "
			. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_customers ON (ciniki_service_subscriptions.customer_id = ciniki_customers.id "
			. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_service_job_users ON (ciniki_service_jobs.id = ciniki_service_job_users.job_id "
			. "AND (ciniki_service_job_users.perms&0x04) = 0x04 "
			. ") "
		. "LEFT JOIN ciniki_users ON (ciniki_service_job_users.user_id = ciniki_users.id) "
		. "WHERE ciniki_service_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_service_subscriptions.service_id = '" . ciniki_core_dbQuote($ciniki, $args['service_id']) . "' "
		. "AND ((PERIOD_DIFF('" . ciniki_core_dbQuote($ciniki, sprintf("%04d%02d", $args['year'], $args['month'])) . "', DATE_FORMAT(ciniki_service_subscriptions.date_started-INTERVAL 1 DAY, '%Y%m'))-due_after_months) "
			. "MOD CASE repeat_type WHEN 40 THEN 12 WHEN 30 THEN repeat_interval END) = 0 "
		. "AND (ciniki_service_subscriptions.date_ended = 0 "
			. " OR PERIOD_DIFF('" . ciniki_core_dbQuote($ciniki, sprintf("%04d%02d", $args['year'], $args['month'])) . "', DATE_FORMAT(ciniki_service_subscriptions.date_ended, '%Y%m')) <= due_after_months ) "
		. "ORDER BY ciniki_service_jobs.status, ciniki_service_jobs.id, assigned_names "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
		array('container'=>'jobs', 'fname'=>'list_id', 'name'=>'job',
			'fields'=>array('id'=>'job_id', 'tracking_id', 'subscription_id'=>'id', 
				'name', 'status', 'status_text', 'service_id', 'customer_id', 'customer_type', 'customer_name', 'company', 
				'year_offset', 'period_months', 'due_after_months', 'quarter',
				'subscription_date_started', 'repeat_type', 'repeat_interval',
				'pstart_date', 'pend_date', 'date_due', 'service_date', 'assigned_names'),
			'lists'=>array('assigned_names'),
			'maps'=>array('status_text'=>$status_texts),
			),
		));
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	if( !isset($rc['jobs']) ) {
		return array('stat'=>'ok', 'jobs'=>array());
	}
	$jobs = $rc['jobs'];

	//
	// Setup any missing job names
	//
	foreach($jobs as $jid => $job) {
		if( $job['job']['name'] != '' ) { 
			continue;
		}
		$job = $job['job'];
		$pstart_date = new DateTime($job['subscription_date_started']);
		$pstart_date->modify('+' . $job['year_offset'] . ' years');
		$jobs[$jid]['job']['name'] = $pstart_date->format('Y');
		if( $job['repeat_type'] == 30 ) {
			if( $job['repeat_interval'] == 3 ) {
				$jobs[$jid]['job']['name'] .= ' Q' . $job['quarter'];
			} else {
				$jobs[$jid]['job']['name'] = $pstart_date->format('M') . ' ' . $jobs[$jid]['job']['name'];
			}
			// Add the number of period_months*quarters to get the quarter start date
			$pstart_date->modify('+' . ($job['period_months']*($job['quarter']-1)) . " months");
		}
		$jobs[$jid]['job']['pstart_date'] = $pstart_date->format('M j, Y');
		// Calculate end date
		$pend_date = clone $pstart_date;
		$pend_date->modify('+' . $job['period_months'] . ' months - 1 day');
		$jobs[$jid]['job']['pend_date'] = $pend_date->format('M j, Y');
		// Calculate due date
		$due_date = clone $pstart_date;
		$due_date->modify('+' . ($job['period_months']+$job['due_after_months']) . ' months - 1 day');

//		$jobs[$jid]['job']['service_date'] = $pend_date->format('M j, Y');
		$jobs[$jid]['job']['date_due'] = $due_date->format('M j, Y');
	}

	return array('stat'=>'ok', 'jobs'=>$jobs);
}
?>
