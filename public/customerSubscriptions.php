<?php
//
// Description
// ===========
// This method will return the list of service subscriptions for a customer,
// and include a list of all jobs for each subscription, including missing ones.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the task list for.
// customer_id:		The ID of the customer to get the subscriptions for.
// subscription_id:	(optional) The ID of the subscription to get.  If not specified,
//					all subscriptions for a customer will be returned.
//
// jobs:			(optional) Specify if the return should include
//					all jobs for each service, or just the list of services.
//
// projections:		(optional) How far ahead to project the jobs for.  This
//					will be used to return future/upcoming jobs.  The format
//					should be the PHP dateInterval format.
//					
//					P1Y - project ahead 1 year
//					P4M	- project ahead 4 months
//
// jobsort:			(optional) This can specify how to return the jobs sorted, either
//					ascending or descending.
//
//					ASC - (default) return the job list with oldest first.
//					DESC - return the job list with newest first.
// 
// Returns
// -------
// <subscriptions>
// 		<service id="1" name="Service Name" date_started="Sep 1, 2010" date_ended=""/>
//			<jobs>
//				<job id="23" name="2007" status="60" status_text="Completed" pstart_date="Jan 1, 2007" pend_date="Dec 31, 2007" service_date="Dec 31, 2007" date_schedule="" date_due="Apr 31, 2008" 
//					date_started="Apr 5, 2010" date_completed="Apr 10, 2010" />
//				<job id="0" name="2008" status="0" status_text="Missing" pstart_date="Jan 1, 2008" pend_date="Dec 31, 2008" service_date="Dec 31, 2008" date_schedule="" date_due="Apr 31, 2009" 
//					date_started="" date_completed="" />
//				<job id="783" name="2009" status="61" status_text="Skipped" pstart_date="Jan 1, 2009" pend_date="Dec 31, 2009" service_date="Dec 31, 2009" date_schedule="" date_due="Apr 31, 2010"
//					date_started="" date_completed="" />
//				<job id="783" name="2010" status="60" status_text="Completed" pstart_date="Jan 1, 2010" pend_date="Dec 31, 2010" service_date="Dec 31, 2010" date_schedule="" date_due="Apr 31, 2011"
//					date_started="Mar 1, 2011" date_completed="Mar 23, 2011" />
//				<job id="783" name="2011" status="60" status_text="Completed" pstart_date="Jan 1, 2011" pend_date="Dec 31, 2011" service_date="Dec 31, 2011" date_schedule="" date_due="Apr 31, 2012"
//					date_started="Feb 25, 2012" date_completed="Mar 23, 2012" />
//				<job id="783" name="2012" status="0" status_text="Upcoming" pstart_date="Jan 1, 2012" pend_date="Dec 31, 2012" service_date="Dec 31, 2012" date_schedule="" date_due="Apr 31, 2013"
//					date_started="" date_completed="Mar 23, 2013" />
//			</jobs>
//		</service>
// </subscriptions>
//
function ciniki_services_customerSubscriptions($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
		'subscription_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Subscription'),
		'jobs'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Jobs'),
		'projections'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Projections'),
		'jobsort'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Job Sort'),
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.customerSubscriptions', 0, 0); 
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

	if( isset($args['jobs']) && $args['jobs'] == 'yes' ) {
		$strsql = "SELECT ciniki_service_subscriptions.id, "
			. "ciniki_service_subscriptions.service_id, "
			. "ciniki_service_subscriptions.status, "
			. "IFNULL(DATE_FORMAT(ciniki_service_subscriptions.date_started, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_started, "
			. "IFNULL(DATE_FORMAT(ciniki_service_subscriptions.date_ended, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_ended, "
			. "ciniki_service_subscriptions.date_started AS raw_date_started, "
			. "ciniki_service_subscriptions.date_ended AS raw_date_ended, "
			. "ciniki_services.name, "
			. "ciniki_services.repeat_type, "
			. "ciniki_services.repeat_interval, "
			. "ciniki_services.due_after_days, ciniki_services.due_after_months, "
			. "ciniki_service_jobs.id AS job_id, "
			. "ciniki_service_jobs.name AS job_name, "
			. "ciniki_service_jobs.pend_date AS raw_pend_date, "
			. "ciniki_service_jobs.status AS status_text, "
			. "IFNULL(DATE_FORMAT(ciniki_service_jobs.pstart_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS pstart_date, "
			. "IFNULL(DATE_FORMAT(ciniki_service_jobs.pend_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS pend_date, "
			. "IFNULL(DATE_FORMAT(ciniki_service_jobs.service_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS service_date, "
			. "IFNULL(DATE_FORMAT(ciniki_service_jobs.date_scheduled, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS job_date_scheduled, "
			. "IFNULL(DATE_FORMAT(ciniki_service_jobs.date_started, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS job_date_started, "
			. "IFNULL(DATE_FORMAT(ciniki_service_jobs.date_due, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS job_date_due, "
			. "IFNULL(DATE_FORMAT(ciniki_service_jobs.date_completed, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS job_date_completed, "
			. "ciniki_service_jobs.status AS job_status "
			. "FROM ciniki_service_subscriptions "
			. "LEFT JOIN ciniki_services ON (ciniki_service_subscriptions.service_id = ciniki_services.id "
				. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
			. "LEFT JOIN ciniki_service_jobs ON (ciniki_service_subscriptions.service_id = ciniki_service_jobs.service_id "
				. "AND ciniki_service_subscriptions.customer_id = ciniki_service_jobs.customer_id ) "
			. "WHERE ciniki_service_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_service_subscriptions.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
		if( isset($args['subscription_id']) && $args['subscription_id'] != '' ) {
			$strsql .= "AND ciniki_service_subscriptions.id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' ";
		}
		$strsql .= ""
			. "AND ciniki_services.repeat_type > 0 "
			. "ORDER BY ciniki_services.name, ciniki_service_subscriptions.id, ciniki_service_jobs.pend_date ";
		if( isset($args['jobsort']) && $args['jobsort'] == 'DESC' ) {
			$strsql .= "DESC ";
		}
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
			array('container'=>'subscriptions', 'fname'=>'id', 'name'=>'service',
				'fields'=>array('id', 'service_id', 'name', 'status', 'date_started', 'date_ended', 
					'repeat_type', 'repeat_interval', 'raw_date_started', 'raw_date_ended', 'due_after_days', 'due_after_months')),
			array('container'=>'jobs', 'fname'=>'job_id', 'name'=>'job',
				'fields'=>array('id'=>'job_id', 'name'=>'job_name', 'status'=>'job_status', 'status_text', 
					'pstart_date', 'pend_date', 'raw_pend_date', 
					'service_date', 'date_scheduled'=>'job_date_scheduled', 'date_started'=>'job_date_started', 
					'date_due'=>'job_date_due', 'date_completed'=>'job_date_completed'),
				'maps'=>array('status_text'=>$status_texts)),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		} 
		if( !isset($rc['subscriptions']) ) {
			if( isset($args['subscription_id']) && $args['subscription_id'] != '' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'850', 'msg'=>'Unable to find subscription'));
			}
			return array('stat'=>'ok', 'subscriptions'=>array());
		}
		$subscriptions = $rc['subscriptions'];

		date_default_timezone_set('UTC');

		//
		// Fill in the missing jobs
		//
		foreach($subscriptions as $sid => $service) {
			$service = $service['service'];
			$date_started = date_create($service['raw_date_started']);
			$date_ended = date_create($service['raw_date_ended']);
			$end_date = 0;
			if( $date_started->getTimestamp() > 0 && $date_ended->getTimestamp() > $date_started->getTimestamp() ) {
				$end_date = date_create($service['raw_date_ended']);
			} elseif( isset($args['projections']) && $args['projections'] != '' ) {
				$end_date = date_create("now");
				$end_date = date_add($end_date, new DateInterval($args['projections']));
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
					$subscriptions[$sid]['service']['jobs'][$jid]['job']['utc_pend_date'] = date_format(date_create($job['job']['raw_pend_date']), 'U');
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
			$jobs = array();
			while( $due_date->getTimestamp() <= $end_date->getTimestamp() ) {
				//
				// Check to see if the job exists
				//
				$utc_pend_date = $cur_pend_date->getTimestamp();
				$exists = 0;
				if( isset($service['jobs']) ) {
					foreach($service['jobs'] as $jid => $job) {
						if( $subscriptions[$sid]['service']['jobs'][$jid]['job']['utc_pend_date'] == $utc_pend_date ) {
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
					array_push($jobs, array('job'=>array('id'=>'0', 'name'=>$name, 'status'=>$status, 'status_text'=>$status_text, 
						'utc_pend_date'=>$cur_pend_date->getTimestamp(), 
						'pstart_date'=>date_format($cur_pstart_date, "M j, Y"), 'pend_date'=>date_format($cur_pend_date, "M j, Y"),
//						'service_date'=>date_format($cur_pend_date, "M j, Y"),
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

			//
			// Merge added jobs and database jobs
			//
			if( !isset($subscriptions[$sid]['service']['jobs']) ) {
				$subscriptions[$sid]['service']['jobs'] = $jobs;
			} else {
				$subscriptions[$sid]['service']['jobs'] = array_merge($subscriptions[$sid]['service']['jobs'], $jobs);
			}

			//
			// Sort the jobs array.  It only needs to be sorted
			// if there were jobs added, otherwise it came from
			// the database sorted.
			//
			if( count($jobs) > 0 ) {
				if( isset($args['jobsort']) && $args['jobsort'] == 'DESC' ) {
					usort($subscriptions[$sid]['service']['jobs'], function($a, $b) {
						if( $a['job']['utc_pend_date'] == $b['job']['utc_pend_date'] ) {
							return 0;
						}
						return $a['job']['utc_pend_date'] > $b['job']['utc_pend_date'] ? -1 : 1;
					});
				} else {
					usort($subscriptions[$sid]['service']['jobs'], function($a, $b) {
						if( $a['job']['utc_pend_date'] == $b['job']['utc_pend_date'] ) {
							return 0;
						}
						return $a['job']['utc_pend_date'] < $b['job']['utc_pend_date'] ? -1 : 1;
					});
				}
			}
		}
	} else {
		$strsql = "SELECT ciniki_service_subscriptions.id, "
			. "ciniki_service_subscriptions.service_id, "
			. "ciniki_service_subscriptions.status, "
			. "ciniki_service_subscriptions.date_started, "
			. "ciniki_service_subscriptions.date_ended, "
			. "ciniki_services.repeat_type, "
			. "ciniki_services.repeat_interval "
			. "FROM ciniki_service_subscriptions "
			. "LEFT JOIN ciniki_services ON (ciniki_service_subscriptions.service_id = ciniki_services.id "
				. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
			. "WHERE ciniki_service_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_service_subscriptions.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND ciniki_services.repeat_type > 0 "
			. "";
	}

	return array('stat'=>'ok', 'subscriptions'=>$subscriptions);
}
?>
