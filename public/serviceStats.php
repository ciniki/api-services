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

	$start_date = new DateTime('Dec 1, 2012');
	$end_date = new DateTime('Nov 30, 2013');
	$interval = $start_date->diff($end_date);
	$num_months = $interval->format('%m') + 1;
//	print "start: " . $start_date->format('Y-m-d') . "\n";
//	print "end: " . $end_date->format('Y-m-d') . "\n";
//	print "diff: " . $interval->format('%m months') . "\n";

    
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
		. "((CASE repeat_type WHEN 40 THEN 12 WHEN 30 THEN repeat_interval END) "
			. "- (period_diff('201212', date_format(date_started-interval 1 day, '%Y%m'))+(12-due_after_months)) "
			. "mod CASE repeat_type WHEN 40 THEN 12 WHEN 30 THEN repeat_interval END) AS offset "
		. "FROM ciniki_services "
		. "LEFT JOIN ciniki_service_subscriptions ON (ciniki_services.id = ciniki_service_subscriptions.service_id) "
		. "GROUP BY service_id, offset "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
		array('container'=>'services', 'fname'=>'id', 'name'=>'service',
			'fields'=>array('id', 'name', 'due_after_months', 'repeat_type', 'repeat_interval')),
		array('container'=>'jobs', 'fname'=>'offset', 'name'=>'jobcount',
			'fields'=>array('offset', 'num_jobs')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['services']) ) {
		return array('stat'=>'ok', 'services'=>array());
	}

	$services = $rc['services'];
	foreach($services as $sid => $service) {
		$service = $service['service'];
		$services[$sid]['service']['months'] = array();
		$cur_month = clone $start_date;
		for($i=0;$i<$num_months;$i++) {
			$services[$sid]['service']['months'][$i] = array('month'=>array(
				'id'=>$cur_month->format('Ym'),
				'year'=>$cur_month->format('Y'), 'name'=>$cur_month->format('M'), 
				'total_jobs'=>0
				));
			$cur_month->modify("+1 month");
		}
		$cur_month = clone $start_date;
		$repeat = $service['repeat_interval'];
		if( $service['repeat_type'] == 40 ) {
			$repeat = $repeat * 12;
		}
		//
		// Go through all the jobs for this service, which should be a maximum of 12.  This
		// will list the number of jobs at each month offset.  The month offset, is the number
		// of months from the current one the job is first set for.  Then add the repeat_interval,
		// and see if there's another month already setup at that month offset.
		//
		if( isset($service['jobs']) ) {
			foreach($service['jobs'] as $jid => $jobcount) {
				$jobcount = $jobcount['jobcount'];
				$month_offset = $jobcount['offset'];
				//
				// Keep checking for month offsets, until no more are found
				//
				while(isset($services[$sid]['service']['months'][$month_offset]) ) {
					$services[$sid]['service']['months'][$month_offset]['month']['total_jobs'] = $jobcount['num_jobs'];
					$month_offset+=$repeat;
				}
			}
			if( isset($services[$sid]['service']['jobs']) ) {
				unset($services[$sid]['service']['jobs']);
			}
		}
	}

//	print_r($rc['services']);

	return array('stat'=>'ok', 'services'=>$services);
}
?>
