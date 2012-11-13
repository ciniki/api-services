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
// 
// Returns
// =======
//
function ciniki_services_checkAccess($ciniki, $business_id, $method) {
	//
	// Check if the business is active and the module is enabled
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkModuleAccess.php');
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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	//
	// If the user has permission, return ok
	//
	if( isset($rc['rows']) && isset($rc['rows'][0]) 
		&& $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $ciniki['session']['user']['id'] ) {
		return array('stat'=>'ok', 'modules'=>$modules);
	}

	//
	// By default, fail
	//
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'834', 'msg'=>'Access denied.'));
}
?>
