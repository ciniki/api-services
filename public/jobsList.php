<?php
//
// Description
// ===========
// This method will return the list of jobs that are in the system,
// and have the specified status.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the task list for.
// type:			(optional) The type of list to return.  The type of status_list must be passed.
// status_list:		(optional) The list of job statuses to get the jobs that match.  This should
//					be a comma delimited list.
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
function ciniki_services_jobsList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'type'=>array('required'=>'no', 'blank'=>'no', 'validlist'=>array('pastdue'), 'name'=>'Type'), 
        'status_list'=>array('required'=>'no', 'blank'=>'no', 'type'=>'idlist', 'name'=>'Status List'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	//
	// Must have type or status_list defined
	//
	if( !isset($args['type']) && !isset($args['status_list']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'874', 'msg'=>'Must specify a type or status list.'));
	}
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'services', 'private', 'checkAccess');
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.jobsList', 0, 0); 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Load the list of status messages for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'services', 'private', 'statusList');
	$rc = ciniki_services_statusList($ciniki, $args['business_id']);
	$status_texts = $rc['list'];

    //
	// Get the types of customers available for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
	$rc = ciniki_customers_getCustomerTypes($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$types = $rc['types'];

	$strsql = "SELECT ciniki_service_jobs.id, "
		. "ciniki_service_jobs.tracking_id, "
		. "ciniki_services.id AS service_id, "
		. "ciniki_services.name AS service_name, "
		. "ciniki_service_jobs.name, "
		. "ciniki_service_jobs.status, "
		. "ciniki_service_jobs.status AS status_text, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.pstart_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS pstart_date, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.pend_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS pend_date, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.date_scheduled, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_scheduled, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.date_started, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_started, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.date_due, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_due, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.date_completed, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_completed, "
		. "IFNULL(DATE_FORMAT(ciniki_service_jobs.date_completed, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_signedoff, "
		. "ciniki_customers.id AS customer_id, "
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
		$strsql .= "ELSE CONCAT_WS(' ', first, last) END AS customer_name ";
	} else {
		// Default to a person
		$strsql .= "CONCAT_WS(' ', first, last) AS customer_name ";
	}
	$strsql .= "FROM ciniki_service_jobs "
		. "LEFT JOIN ciniki_services ON (ciniki_service_jobs.service_id = ciniki_services.id "
			. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
		. "LEFT JOIN ciniki_customers ON (ciniki_service_jobs.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
		. "WHERE ciniki_service_jobs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	if( isset($args['type']) && $args['type'] == 'pastdue' ) {
		$strsql .= "AND ciniki_service_jobs.status < 60 "
			. "AND ciniki_service_jobs.date_due < CURDATE() "
			. "";
	} elseif( isset($args['status_list']) ) {
		$strsql .= "AND ciniki_service_jobs.status IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['status_list']) . ") ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'875', 'msg'=>'Must specify a type or status list.'));
	}
	$strsql .= "ORDER BY ciniki_service_jobs.status, customer_name, ciniki_service_jobs.date_due "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
		array('container'=>'jobs', 'fname'=>'id', 'name'=>'job',
			'fields'=>array('id'=>'id', 'customer_id', 'customer_name', 
				'service_name', 'name', 'status', 'status_text', 
				'pstart_date', 'pend_date', 
				'date_scheduled', 'date_started', 'date_due', 'date_completed', 'date_signedoff'),
			'maps'=>array('status_text'=>$status_texts)),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'873', 'msg'=>'Unable to get jobs', 'err'=>$rc['err']));
	}
	if( !isset($rc['jobs']) ) {
		return array('stat'=>'ok', 'jobs'=>array());
	}

	return array('stat'=>'ok', 'jobs'=>$rc['jobs']);
}
?>
