<?php
//
// Description
// ===========
// This function will check the user has access to the services module,
// and return a list of other modules enabled for the business.
//
// Arguments
// =========
// ciniki:
// business_id: 		The ID of the business the request is for.
// method:				The method requested in the services module.
// service_id:			The ID of the service, if specified to check if the user
//						has access to.
// req_id:				The first ID to lookup.  This is used to check some methods
//						have access to a specified service or task.
// req2_id:				The second ID to lookup.  This is used to check some methods
//						have access to a specified service or task.
// 
// Returns
// =======
//
function ciniki_services_checkAccess($ciniki, $business_id, $method, $req_id, $req2_id) {
	//
	// Check if the business is active and the module is enabled
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
	$rc = ciniki_businesses_checkModuleAccess($ciniki, $business_id, 'ciniki', 'services');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['modules']) ) {
		$modules = $rc['modules'];
	} else {
		$modules = array();
	}

	if( !isset($rc['ruleset']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'833', 'msg'=>'No permissions granted'));
	}

	// FIXME: Check if this is needed
	//	//
	//	// Sysadmins are allowed full access, except for deleting.
	//	//
	//	if( $method != 'ciniki.services.delete' ) {
	//		if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
	//			return array('stat'=>'ok', 'modules'=>$modules);
//		}
//	}
//
	//
	// Users who are an owner or employee of a business can see the business services
	//
	$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "AND package = 'ciniki' "
		. "AND (permission_group = 'owners' OR permission_group = 'employees') "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	//
	// If the user has permission, return ok
	//
	if( isset($rc['rows']) && isset($rc['rows'][0]) 
		&& $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $ciniki['session']['user']['id'] ) {

		if( $method == 'ciniki.services.serviceUpdate'
			|| $method == 'ciniki.services.serviceGet' 
			|| $method == 'ciniki.services.serviceHistory' 
			|| $method == 'ciniki.services.serviceJobs' 
			|| $method == 'ciniki.services.subscriptionAdd'
			|| $method == 'ciniki.services.jobAdd' ) {
			//
			// Check to make sure the task is assigned to business
			//
			$strsql = "SELECT ciniki_services.id "
				. "FROM ciniki_services "
				. "WHERE ciniki_services.id = '" . ciniki_core_dbQuote($ciniki, $req_id) . "' "
				. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'service');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( !isset($rc['service']) || $rc['service']['id'] != $req_id ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'843', 'msg'=>'Permission denied'));
			}
		}
		if( $method == 'ciniki.services.subscriptionAdd'
			|| $method == 'ciniki.services.jobAdd' ) {
			//
			// Also check for customer_id
			//
			$strsql = "SELECT ciniki_customers.id "
				. "FROM ciniki_customers "
				. "WHERE ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $req2_id) . "' "
				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( !isset($rc['customer']) || $rc['customer']['id'] != $req2_id ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'849', 'msg'=>'Permission denied'));
			}
		}
		if( $method == 'ciniki.services.taskUpdate'
			|| $method == 'ciniki.services.taskGet' ) {
			//
			// Check to make sure the task is assigned to business
			//
			$strsql = "SELECT ciniki_service_tasks.id "
				. "FROM ciniki_service_tasks, ciniki_services "
				. "WHERE ciniki_service_tasks.id = '" . ciniki_core_dbQuote($ciniki, $req_id) . "' "
				. "AND ciniki_service_tasks.service_id = ciniki_services.id "
				. "AND ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'task');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( !isset($rc['task']) || $rc['task']['id'] != $req_id ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'844', 'msg'=>'Permission denied'));
			}
		}
		if( $method == 'ciniki.services.jobUpdate' 
			|| $method == 'ciniki.services.jobDelete' ) {
			//
			// Check to make sure the job is assigned to business
			//
			$strsql = "SELECT ciniki_service_jobs.id "
				. "FROM ciniki_service_jobs "
				. "WHERE ciniki_service_jobs.id = '" . ciniki_core_dbQuote($ciniki, $req_id) . "' "
				. "AND ciniki_service_jobs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'job');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( !isset($rc['job']) || $rc['job']['id'] != $req_id ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'860', 'msg'=>'Permission denied'));
			}
		}
		if( $method == 'ciniki.services.subscriptionUpdate' 
			|| $method == 'ciniki.services.subscriptionDelete' 
			) {
			//
			// Check to make sure the subscription is assigned to business
			//
			$strsql = "SELECT ciniki_service_subscriptions.id "
				. "FROM ciniki_service_subscriptions "
				. "WHERE ciniki_service_subscriptions.id = '" . ciniki_core_dbQuote($ciniki, $req_id) . "' "
				. "AND ciniki_service_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'subscription');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( !isset($rc['subscription']) || $rc['subscription']['id'] != $req_id ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'869', 'msg'=>'Permission denied'));
			}
		}

		return array('stat'=>'ok', 'modules'=>$modules);
	}

	//
	// By default, fail
	//
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'834', 'msg'=>'Access denied.'));
}
?>
