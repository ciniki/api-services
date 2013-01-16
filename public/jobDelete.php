<?php
//
// Description
// -----------
// This method will remove a customer job for a service.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the customer is attached to.
// job_id:				The ID of the job to be removed.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_services_jobDelete($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'job_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Job'), 
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.jobDelete', $args['job_id'], 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Remove the customer email address from the database.  It is still there in 
	// the ciniki_customer_history table.
	//
	$strsql = "DELETE FROM ciniki_service_jobs "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['job_id']) . "' ";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
		return $rc;
	}
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', $args['business_id'], 
		3, 'ciniki_service_jobs', $args['job_id'], '*', '');

	//
	// Remove all the tasks associated with job
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$strsql = "SELECT id "
		. "FROM ciniki_service_job_tasks "
		. "WHERE job_id = '" . ciniki_core_dbQuote($ciniki, $args['job_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'job');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'877', 'msg'=>'Unable to remove job tasks', 'err'=>$rc['err']));
	}
	if( isset($rc['rows']) ) {
		$tasks = $rc['rows'];
		foreach($tasks as $tid => $task) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', $args['business_id'],
				3, 'ciniki_service_job_tasks', $task['id'], '*', '');
		}
		$strsql = "DELETE FROM ciniki_service_job_tasks "
			. "WHERE job_id = '" . ciniki_core_dbQuote($ciniki, $args['job_id']) . "' "
			. "";
		$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.services');
		if( $rc['stat'] != 'ok' ) { 
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'878', 'msg'=>'Unable to remove job tasks', 'err'=>$err['err']));
		}
	}

	//
	// Remove all the notes associated with job
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$strsql = "SELECT id "
		. "FROM ciniki_service_job_notes "
		. "WHERE job_id = '" . ciniki_core_dbQuote($ciniki, $args['job_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'job');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'882', 'msg'=>'Unable to remove job notes', 'err'=>$rc['err']));
	}
	if( isset($rc['rows']) ) {
		$notes = $rc['rows'];
		foreach($notes as $tid => $note) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', $args['business_id'],
				3, 'ciniki_service_job_notes', $note['id'], '*', '');
		}
		$strsql = "DELETE FROM ciniki_service_job_notes "
			. "WHERE job_id = '" . ciniki_core_dbQuote($ciniki, $args['job_id']) . "' "
			. "";
		$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.services');
		if( $rc['stat'] != 'ok' ) { 
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'883', 'msg'=>'Unable to remove job notes', 'err'=>$err['err']));
		}
	}

	//
	// Remove all the hours associated with job
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$strsql = "SELECT id "
		. "FROM ciniki_service_job_hours "
		. "WHERE job_id = '" . ciniki_core_dbQuote($ciniki, $args['job_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'job');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'884', 'msg'=>'Unable to remove job hours', 'err'=>$rc['err']));
	}
	if( isset($rc['rows']) ) {
		$hours = $rc['rows'];
		foreach($hours as $tid => $hour) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', $args['business_id'],
				3, 'ciniki_service_job_hours', $hour['id'], '*', '');
		}
		$strsql = "DELETE FROM ciniki_service_job_hours "
			. "WHERE job_id = '" . ciniki_core_dbQuote($ciniki, $args['job_id']) . "' "
			. "";
		$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.services');
		if( $rc['stat'] != 'ok' ) { 
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'885', 'msg'=>'Unable to remove job hours', 'err'=>$err['err']));
		}
	}

	//
	// Remove all the users associated with job
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$strsql = "SELECT user_id "
		. "FROM ciniki_service_job_users "
		. "WHERE job_id = '" . ciniki_core_dbQuote($ciniki, $args['job_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'job');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'988', 'msg'=>'Unable to remove job hours', 'err'=>$rc['err']));
	}
	if( isset($rc['rows']) ) {
		$users = $rc['rows'];
		foreach($users as $uid => $user) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', $args['business_id'],
				3, 'ciniki_service_job_users', $user['user_id'], '*', '');
		}
		$strsql = "DELETE FROM ciniki_service_job_users "
			. "WHERE job_id = '" . ciniki_core_dbQuote($ciniki, $args['job_id']) . "' "
			. "";
		$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.services');
		if( $rc['stat'] != 'ok' ) { 
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'960', 'msg'=>'Unable to remove job hours', 'err'=>$err['err']));
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'services');

	return array('stat'=>'ok');
}
?>
