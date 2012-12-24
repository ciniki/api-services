<?php
//
// Description
// ===========
// This method will return the list of jobs which are missing from
// subscriptions and have not yet been setup in the system.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the task list for.
//
// Returns
// -------
//	<jobs>
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
//	</jobs>
//
function ciniki_services_jobsMissing($ciniki) {
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
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'services', 'private', 'checkAccess');
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.jobsMissing', 0, 0); 
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

	if( !isset($args['year']) ) {
		$args['year'] = date('Y');
	}
	if( !isset($args['month']) ) {
		$args['month'] = date('m');
	}

    //
	// Get the types of customers available for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
	$rc = ciniki_customers_getCustomerTypes($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$types = $rc['types'];

	//
	// Build the SQL string to get the list of subscriptions with missing jobs,
	// and include the existing jobs so the holes can be filled in
	//
	$strsql = "SELECT ciniki_service_subscriptions.id, "
		. "IFNULL(DATE_FORMAT(ciniki_service_subscriptions.date_started, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_started, "
		. "IFNULL(DATE_FORMAT(ciniki_service_subscriptions.date_ended, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_ended, "
		. "ciniki_service_subscriptions.date_started AS raw_date_started, "
		. "ciniki_service_subscriptions.date_ended AS raw_date_ended, "
		. "ciniki_service_subscriptions.customer_id, "
		. "ciniki_customers.type AS customer_type, "
		. "";
	if( count($types) > 0 ) {
		// If there are customer types defined, choose the right name for the customer
		// This is required here to be able to sort properly
		$strsql .= "CASE ciniki_customers.type ";
		foreach($types as $tid => $type) {
			$strsql .= "WHEN " . ciniki_core_dbQuote($ciniki, $tid) . " THEN ";
			if( $type['detail_value'] == 'business' ) {
				$strsql .= " ciniki_customers.company ";
			} else {
				$strsql .= "CONCAT_WS(' ', first, last) ";
			}
		}
		$strsql .= "ELSE CONCAT_WS(' ', first, last) END AS customer_name, ";
	} else {
		// Default to a person
		$strsql .= "CONCAT_WS(' ', first, last) AS customer_name, ";
	}
	$strsql .= "ciniki_service_subscriptions.service_id, "
		. "ciniki_services.name AS service_name, "
		. "ciniki_services.repeat_type, "
		. "ciniki_services.repeat_interval, "
		. "ciniki_services.due_after_days, ciniki_services.due_after_months, "
		. "ciniki_service_jobs.id AS job_id, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.pstart_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS pstart_date, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.pend_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS pend_date, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.date_due, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS job_date_due, "
		. "ciniki_service_jobs.pend_date AS raw_pend_date "
		. "FROM ciniki_service_subscriptions "
		. "LEFT JOIN ciniki_services ON (ciniki_service_subscriptions.service_id = ciniki_services.id "
			. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
		. "LEFT JOIN ciniki_customers ON (ciniki_service_subscriptions.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
		. "LEFT JOIN ciniki_service_jobs ON (ciniki_service_subscriptions.id = ciniki_service_jobs.subscription_id "
			. "AND ciniki_service_subscriptions.customer_id = ciniki_service_jobs.customer_id ) "
		. "WHERE EXISTS ( "
			// Find service subscriptions which are missing jobs
			. "SELECT ciniki_service_subscriptions.id, "
			. "((PERIOD_DIFF("
				. "IF(ciniki_service_subscriptions.date_ended>ciniki_service_subscriptions.date_started, "
					. "DATE_FORMAT(ciniki_service_subscriptions.date_ended+INTERVAL due_after_months MONTH, '%Y%m'), "
					. "'" . ciniki_core_dbQuote($ciniki, sprintf("%04d%02d", $args['year'], $args['month'])) . "'), "
				. "DATE_FORMAT(ciniki_service_subscriptions.date_started-INTERVAL 1 DAY, '%Y%m'))-due_after_months) "
				. "DIV CASE repeat_type WHEN 40 THEN 12 WHEN 30 THEN repeat_interval END) AS required_jobs, "
			. "COUNT(ciniki_service_jobs.id) AS existing_jobs "
			. "FROM ciniki_service_subscriptions "
			. "LEFT JOIN ciniki_services ON (ciniki_service_subscriptions.service_id = ciniki_services.id "
				. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
			. "LEFT JOIN ciniki_service_jobs ON (ciniki_service_subscriptions.id = ciniki_service_jobs.subscription_id "
				. "AND ciniki_service_subscriptions.customer_id = ciniki_service_jobs.customer_id ) "
			. "WHERE ciniki_service_subscriptions.service_id = ciniki_services.id "
			. "GROUP BY ciniki_service_subscriptions.id "
			. "HAVING required_jobs > existing_jobs "
		. ") "
		. "ORDER BY customer_name, ciniki_services.name, ciniki_service_subscriptions.date_started "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
		array('container'=>'subscriptions', 'fname'=>'id', 'name'=>'subscription',
			'fields'=>array('id', 'customer_id', 'customer_type', 'customer_name',
				'service_id', 'service_name', 'date_started', 'date_ended', 
				'repeat_type', 'repeat_interval', 'raw_date_started', 'raw_date_ended', 'due_after_days', 'due_after_months')),
		array('container'=>'jobs', 'fname'=>'job_id', 'name'=>'job',
			'fields'=>array('id'=>'job_id', 
				'pstart_date', 'pend_date', 'raw_pend_date', 'date_due'=>'job_date_due'),
			'maps'=>array('status_text'=>$status_texts)),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	} 
	if( !isset($rc['subscriptions']) ) {
		return array('stat'=>'ok', 'subscriptions'=>array());
	}
	$subscriptions = $rc['subscriptions'];

	//
	// Go through all the subscriptions returned and build a list of missing jobs
	//
	$jobs = array();
	foreach($subscriptions as $sid => $service) {
		$service = $service['subscription'];
		$date_started = date_create($service['raw_date_started']);
		$date_ended = date_create($service['raw_date_ended']);
		$end_date = 0;
		if( $date_started->getTimestamp() > 0 && $date_ended->getTimestamp() > $date_started->getTimestamp() ) {
			$end_date = date_create($service['raw_date_ended']);
			// Add the due_after_months, this is used when subscription has an end date
			if( $service['due_after_months'] > 0 ) {
				$end_date->modify('+' . $service['due_after_months'] . ' month');
				// if it starts at 1st of month, it should end on last day of month, check for rollover
				if( $date_started->format('j') == 1 && $end_date->format('j') < 5 ) {
					$end_date->modify('last day of previous month');
				}
				// Check if end is last day of month
				elseif( $date_ended->format('t') == $date_ended->format('j') && $end_date->format('t') != $end_date->format('j') ) {
					$end_date->modify('last day of this month');
				}
			}
//		} elseif( isset($args['projections']) && $args['projections'] != '' ) {
//			$end_date = date_create("now");
//			$end_date = date_add($end_date, new DateInterval($args['projections']));
		} else {
			$end_date = date_create("now");
		}
		$interval = 0;
		if( $service['repeat_type'] == 30 ) {
			$interval = new DateInterval("P" . $service['repeat_interval'] . "M");
		} elseif( $service['repeat_type'] == 40 ) {
			$interval = new DateInterval("P" . $service['repeat_interval'] . "Y");
		} else {
			continue;
		}
		$dueinterval = 0;
		if( $service['due_after_days'] > 0 && $service['due_after_months'] > 0 ) {
			$dueinterval = new DateInterval("P" . $service['due_after_months'] . "M" . $service['due_after_days'] . "D");
		} elseif( $service['due_after_days'] > 0 ) {
			$dueinterval = new DateInterval("P" . $service['due_after_days'] . "D");
		} elseif( $service['due_after_months'] > 0 ) {
			$dueinterval = new DateInterval("P" . $service['due_after_months'] . "M");
		}
		$lessaday = new DateInterval("P1D");
	
		//
		// Setup status_text in existing jobs, and setup utc_pend_date
		//
		if( isset($service['jobs']) ) {
			foreach($service['jobs'] as $jid => $job) {
				// Setup utc_pend_date
				$subscriptions[$sid]['subscription']['jobs'][$jid]['job']['utc_pend_date'] = date_format(date_create($job['job']['raw_pend_date']), 'U');
			}
		}

		//
		// Setup the start and end dates of the current subscription period
		//
		$cur_pstart_date = clone $date_started;
		$cur_pstart_year = $date_started->format('Y');
		$cur_pend_date = clone $cur_pstart_date;
		$cur_pend_date->add($interval);
		$cur_pend_date->sub($lessaday);
		// Setup due date
		$due_date = clone $cur_pend_date;
		$due_date->add($dueinterval);
		// Check for month rollover
		if( $cur_pend_date->format('j') >= 28 && $due_date->format('j') < 5 ) {
			$due_date->modify('last day of previous month');
		}
		// Check if end is last day of month
		elseif( $cur_pend_date->format('t') == $cur_pend_date->format('j') && $due_date->format('t') != $due_date->format('j') ) {
			$due_date->modify('last day of this month');
		}
		$count = 0;
		while( $due_date->getTimestamp() <= $end_date->getTimestamp() ) {
			//
			// Check to see if the job exists
			//
			$utc_pend_date = $cur_pend_date->getTimestamp();
			$exists = 0;
			if( isset($service['jobs']) ) {
				foreach($service['jobs'] as $jid => $job) {
					if( $subscriptions[$sid]['subscription']['jobs'][$jid]['job']['utc_pend_date'] == $utc_pend_date ) {
						$exists = 1;
						break;
					}
				}
			}
			//
			// Keep track of the current quarter, if a quarterly job
			//
			if( $service['repeat_type'] == 30 && $service['repeat_interval'] == 3 ) {
				$quarter = (($count%4)+1);
			}
			//
			// Setup the name for the job
			//
			if( $service['repeat_type'] == 30 ) {
				if( $service['repeat_interval'] == 3 ) {
					$name = $cur_pstart_year . " Q" . $quarter;
				} else {
					$name = date_format($cur_pstart_date, 'Y-M');
				}
			} elseif( $service['repeat_type'] == 40 ) {
				$name = date_format($cur_pstart_date, 'Y');
			} else {
				$name = '';
			}

			if( $exists == 0 ) {
				if( $cur_pend_date->getTimestamp() < time() ) {
					$status = '1';
					$status_text = $status_texts[$status];
				} else {
					$status = '2';
					$status_text = $status_texts[$status];
				}
				//
				// Add the job
				//
				array_push($jobs, array('job'=>array('id'=>'0', 'subscription_id'=>$service['id'], 
					'service_id'=>$service['service_id'], 
					'service_name'=>$service['service_name'], 'name'=>$name, 
					'customer_id'=>$service['customer_id'], 'customer_type'=>$service['customer_type'],
					'customer_name'=>$service['customer_name'], 
					'status'=>$status, 'status_text'=>$status_text, 
					'utc_pend_date'=>$cur_pend_date->getTimestamp(), 
					'pstart_date'=>date_format($cur_pstart_date, "M j, Y"), 'pend_date'=>date_format($cur_pend_date, "M j, Y"),
					'date_scheduled'=>'', 'date_started'=>'', 'date_due'=>date_format($due_date, 'M j, Y'), 'date_completed'=>'')));
			}
			//
			// Advance the year
			//
			if( $service['repeat_type'] == 30 ) {
				if( ($count+1)%(12/$service['repeat_interval']) == 0 ) {
					$cur_pstart_year++;
				}
			}

			//
			// Setup the next period
			//
			$cur_pstart_date->add($interval);
			$cur_pend_date = clone $cur_pstart_date;
			$cur_pend_date->add($interval);
			$cur_pend_date->sub($lessaday);
			// Setup due date
			$due_date = clone $cur_pend_date;
			$due_date->add($dueinterval);
			// Check for month rollover
			if( $cur_pend_date->format('j') >= 28 && $due_date->format('j') < 5 ) {
				$due_date->modify('last day of previous month');
			}
			// Check if end is last day of month
			elseif( $cur_pend_date->format('t') == $cur_pend_date->format('j') && $due_date->format('t') != $due_date->format('j') ) {
				$due_date->modify('last day of this month');
			}
			$count++;
		}
	}

	return array('stat'=>'ok', 'jobs'=>$jobs);
}
?>
