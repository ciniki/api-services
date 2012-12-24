<?php
//
// Description
// -----------
// This method will update one or more settings for the services module.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:				The ID of the business to get to update the settings for.
// use-tracking-id:			(optional) Specify if the business should use a tracking ID for each job.
//
//							yes - tracking ID field is displayed in the UI
//							no - tracking ID field is not displayed.
// 
// job-status-1-colour:		The HEX colour code for missing jobs.  Should include # at beginning of colour.
// job-status-2-colour:		The HEX colour code for upcoming jobs.  Should include # at beginning of colour.
// job-status-10-colour:	The HEX colour code for entered jobs.  Should include # at beginning of colour.
// job-status-20-colour:	The HEX colour code for started jobs.  Should include # at beginning of colour.
// job-status-30-colour:	The HEX colour code for pending jobs.  Should include # at beginning of colour.
// job-status-50-colour:	The HEX colour code for completed jobs.  Should include # at beginning of colour.
// job-status-60-colour:	The HEX colour code for signed off jobs.  Should include # at beginning of colour.
// job-status-61-colour:	The HEX colour code for skipped jobs.  Should include # at beginning of colour.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_services_settingsUpdate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'use-tracking-id'=>array('required'=>'no', 'blank'=>'no', 'validlist'=>array('no','yes'), 'name'=>'Use Tracking ID'),
		'job-status-1-colour'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Missing Status Colour'),
		'job-status-2-colour'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Upcoming Status Colour'),
		'job-status-10-colour'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Entered Status Colour'),
		'job-status-20-colour'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Started Status Colour'),
		'job-status-30-colour'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Pending Status Colour'),
		'job-status-50-colour'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Completed Status Colour'),
		'job-status-60-colour'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Signed Off Status Colour'),
		'job-status-61-colour'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Skipped Status Colour'),
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
    $rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.settingsUpdate', 0, 0); 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// The list of allowed fields for updating
	//
	$changelog_fields = array(
		'use-tracking-id',
		'job-status-1-colour',
		'job-status-2-colour',
		'job-status-10-colour',
		'job-status-20-colour',
		'job-status-30-colour',
		'job-status-40-colour',
		'job-status-50-colour',
		'job-status-60-colour',
		'job-status-61-colour',
		);
	//
	// Check each valid setting and see if a new value was passed in the arguments for it.
	// Insert or update the entry in the ciniki_service_settings table
	//
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql = "INSERT INTO ciniki_service_settings (business_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $field) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "'"
				. ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
				. "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.services');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.services');
				return $rc;
			}
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', $args['business_id'], 
				2, 'ciniki_service_settings', $field, 'detail_value', $args[$field]);
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
