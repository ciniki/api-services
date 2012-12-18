<?php
//
// Description
// ===========
// This method returns the list of active services for a business,
// and the number of jobs each month between the start and end dates.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the task list for.
//
// start_date:		Return all jobs that are due on or after this date.
// end_date:		Return all jobs that are due before or on this date.
// 
// Returns
// -------
//
function ciniki_services_serviceStats($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Start Date'), 
        'end_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'End Date'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	if( isset($args['start_date']) ) {
		$pstart_date = new DateTime($args['start_date']);
	} else {
		$pstart_date = new DateTime();
		$pstart_date->modify('first day of this month');
	}
	if( isset($args['start_date']) ) {
		$pend_date = new DateTime($args['end_date']);
	} else {
		$pend_date = clone $pstart_date;
		$pend_date->modify("+1 year");
		$pend_date->modify("-1 day");
	}

	$interval = $pstart_date->diff($pend_date);
	$num_months = ($interval->format('%Y') * 12) + $interval->format('%m') + 1;

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'services', 'private', 'checkAccess');
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.serviceStats', 0, 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	$strsql = "SELECT COUNT(ciniki_service_subscriptions.service_id) AS num_jobs, "
		. "ciniki_services.id, ciniki_services.name, repeat_type, repeat_interval, "
		. "ciniki_services.due_after_months, "
		. "CASE repeat_type WHEN 40 THEN 12 WHEN 30 THEN repeat_interval END AS repeat_period, "
		. "((PERIOD_DIFF('" . ciniki_core_dbQuote($ciniki, $pstart_date->format('Ym')) . "', DATE_FORMAT(date_started-INTERVAL 1 DAY, '%Y%m'))-due_after_months) "
			. "MOD CASE repeat_type WHEN 40 THEN 12 WHEN 30 THEN repeat_interval END) AS offset "
		. "FROM ciniki_services "
		. "LEFT JOIN ciniki_service_subscriptions ON (ciniki_services.id = ciniki_service_subscriptions.service_id) "
		. "GROUP BY ciniki_services.id, offset "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
		array('container'=>'services', 'fname'=>'id', 'name'=>'service',
			'fields'=>array('id', 'name', 'due_after_months', 'repeat_type', 'repeat_interval', 'repeat_period')),
		array('container'=>'jobs', 'fname'=>'offset', 'name'=>'jobcount',
			'fields'=>array('offset', 'num_jobs')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'871', 'msg'=>'Unable to find services', 'err'=>$rc['err']));
	}
	if( !isset($rc['services']) ) {
		return array('stat'=>'ok', 'services'=>array());
	}

	$services = $rc['services'];
	foreach($services as $sid => $service) {
		$service = $service['service'];
		$services[$sid]['service']['max_jobs'] = 0;
		$services[$sid]['service']['months'] = array();
		$cur_month = clone $pstart_date;
		for($i=0;$i<$num_months;$i++) {
			$services[$sid]['service']['months'][$i] = array('month'=>array(
				'id'=>$cur_month->format('Ym'),
				'year'=>$cur_month->format('Y'), 'name'=>$cur_month->format('M'), 
				'total_jobs'=>0
				));
			$cur_month->modify("+1 month");
		}
		$cur_month = clone $pstart_date;
		$repeat = $service['repeat_period'];
		//
		// Go through all the jobs for this service, which should be a maximum of 12.  This
		// will list the number of jobs at each month offset.  The month offset, is the number
		// of months from the current one the job is first set for.  Then add the repeat_interval,
		// and see if there's another month already setup at that month offset.
		//
		if( isset($service['jobs']) ) {
			foreach($service['jobs'] as $jid => $jobcount) {
				$jobcount = $jobcount['jobcount'];
				$month_offset = 0;
				if( $jobcount['offset'] > 0 ) {
					$month_offset = $service['repeat_period']-$jobcount['offset'];
				} else {
					$month_offset = $jobcount['offset'];
				}
				//
				// Keep checking for month offsets, until no more are found
				//
				while(isset($services[$sid]['service']['months'][$month_offset]) ) {
					$services[$sid]['service']['months'][$month_offset]['month']['total_jobs'] = $jobcount['num_jobs'];
					if( $jobcount['num_jobs'] > $services[$sid]['service']['max_jobs'] ) {
						$services[$sid]['service']['max_jobs'] = $jobcount['num_jobs'];
					}
					$month_offset+=$repeat;
				}
			}
			if( isset($services[$sid]['service']['jobs']) ) {
				unset($services[$sid]['service']['jobs']);
			}
		}
	}

	//
	// Get the existing jobs from the database for the period specified
	//
	$strsql = "SELECT ciniki_service_jobs.service_id, COUNT(ciniki_service_jobs.id) AS num_jobs, ciniki_service_jobs.status, "
		. "PERIOD_DIFF(DATE_FORMAT(date_due, '%Y%m'), '" . ciniki_core_dbQuote($ciniki, $pstart_date->format('Ym')) . "') AS offset, "
		. "CONCAT_WS('-', ciniki_service_jobs.status, PERIOD_DIFF(DATE_FORMAT(date_due, '%Y%m'), '" . ciniki_core_dbQuote($ciniki, $pstart_date->format('Ym')) . "')) AS groupid "
		. "FROM ciniki_service_jobs "
		. "WHERE ciniki_service_jobs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_service_jobs.date_due >= '" . ciniki_core_dbQuote($ciniki, $pstart_date->format('Y-m-d')) . "' "
		. "AND ciniki_service_jobs.date_due <= '" . ciniki_core_dbQuote($ciniki, $pend_date->format('Y-m-d')) . "' "
		. "GROUP BY ciniki_service_jobs.service_id, ciniki_service_jobs.status, offset "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
		array('container'=>'services', 'fname'=>'service_id', 'name'=>'service',
			'fields'=>array('service_id')),
		array('container'=>'jobs', 'fname'=>'groupid', 'name'=>'jobcount',
			'fields'=>array('status', 'offset', 'num_jobs')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'872', 'msg'=>'Unable to find jobs', 'err'=>$rc['err']));
	}
	if( isset($rc['services']) ) {
		//
		// Go through the results and add the job counts for status to the
		// services array
		//
		foreach($rc['services'] as $sid => $jobservice) {
			$jobservice = $jobservice['service'];
			// Find the service for the current job count result
			foreach($services as $sid => $service) {
				if( $service['service']['id'] == $jobservice['service_id'] ) {
					foreach($jobservice['jobs'] as $jid => $job) {
						switch($job['jobcount']['status']) {
							case 10: $services[$sid]['service']['months'][$job['jobcount']['offset']]['month']['jobs_entered'] = $job['jobcount']['num_jobs'];
								break;
							case 20: $services[$sid]['service']['months'][$job['jobcount']['offset']]['month']['jobs_started'] = $job['jobcount']['num_jobs'];
								break;
							case 30: $services[$sid]['service']['months'][$job['jobcount']['offset']]['month']['jobs_pending'] = $job['jobcount']['num_jobs'];
								break;
							case 40: $services[$sid]['service']['months'][$job['jobcount']['offset']]['month']['jobs_working'] = $job['jobcount']['num_jobs'];
								break;
							case 60: $services[$sid]['service']['months'][$job['jobcount']['offset']]['month']['jobs_completed'] = $job['jobcount']['num_jobs'];
								break;
							case 61: $services[$sid]['service']['months'][$job['jobcount']['offset']]['month']['jobs_skipped'] = $job['jobcount']['num_jobs'];
								break;
						}
					}
				}
			}

		}
	}

	return array('stat'=>'ok', 'services'=>$services);
}
?>
