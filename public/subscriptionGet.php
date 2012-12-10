<?php
//
// Description
// ===========
// This method will return the details for a service subscription.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the service for.
// subscription_id:		The ID of the subscription to return the details for.
// 
// Returns
// -------
// <subscription id="1" service_name="HST" status="10" 
//		date_started="Jan 1, 2009" date_ended="" 
//		date_added="Dec 10, 2012 10:18 am" last_updated="Dec 10, 2012 10:18 am" />
//
function ciniki_services_subscriptionGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'subscription_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subscription'), 
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.subscriptionGet', 0, 0); 
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
	// Get the subscription information
	//
	$strsql = "SELECT ciniki_service_subscriptions.id, "
		. "ciniki_service_subscriptions.status, "
		. "ciniki_services.name AS service_name, "
		. "DATE_FORMAT(ciniki_service_subscriptions.date_started, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_started, "
		. "DATE_FORMAT(ciniki_service_subscriptions.date_ended, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_ended, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_service_subscriptions.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_service_subscriptions.last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
		. "FROM ciniki_service_subscriptions "
		. "LEFT JOIN ciniki_services ON (ciniki_service_subscriptions.service_id = ciniki_services.id) "
		. "WHERE ciniki_service_subscriptions.id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
		. "AND ciniki_service_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.services', array(
		array('container'=>'subscriptions', 'fname'=>'id', 'name'=>'subscription',
			'fields'=>array('id', 'service_name', 'status',
				'date_started', 'date_ended', 'date_added', 'last_updated')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'867', 'msg'=>'No subscription found', 'err'=>$rc['err']));
	}
	if( !isset($rc['subscriptions']) || !isset($rc['subscriptions'][0]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'868', 'msg'=>'No subscription found'));
	}
	$subscription = $rc['subscriptions'][0]['subscription'];

	return array('stat'=>'ok', 'subscription'=>$subscription);
}
?>

