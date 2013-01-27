<?php
//
// Description
// -----------
// This function will go through the history of the ciniki.customers module and add missing history elements.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_services_historyFix($ciniki) {
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
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'services', 'private', 'checkAccess');
	$rc = ciniki_services_checkAccess($ciniki, $args['business_id'], 'ciniki.services.historyFix', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');

	//
	// Check for items that are missing a add value in history
	//
	$fields = array('uuid', 'status', 'name', 'category', 'type', 'duration', 'repeat_type', 'repeat_interval', 'repeat_number', 'due_after_days', 'due_after_months');
	foreach($fields as $field) {
		//
		// Get the list of services which don't have a history for the field
		//
		$strsql = "SELECT ciniki_services.id, ciniki_services.$field AS field_value, "
			. "UNIX_TIMESTAMP(ciniki_services.date_added) AS date_added, "
			. "UNIX_TIMESTAMP(ciniki_services.last_updated) AS last_updated "
			. "FROM ciniki_services "
			. "LEFT JOIN ciniki_service_history ON (ciniki_services.id = ciniki_service_history.table_key "
				. "AND ciniki_service_history.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND ciniki_service_history.table_name = 'ciniki_services' "
				. "AND (ciniki_service_history.action = 1 OR ciniki_service_history.action = 2) "
				. "AND ciniki_service_history.table_field = '" . ciniki_core_dbQuote($ciniki, $field) . "' "
				. ") "
			. "WHERE ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_services.$field <> '' "
			. "AND ciniki_services.$field <> '0000-00-00' "
			. "AND ciniki_services.$field <> '0000-00-00 00:00:00' "
			. "AND ciniki_service_history.uuid IS NULL "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'history');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	
		$elements = $rc['rows'];
		foreach($elements AS $rid => $row) {
			$strsql = "INSERT INTO ciniki_service_history (uuid, business_id, user_id, session, action, "
				. "table_name, table_key, table_field, new_value, log_date) VALUES ("
				. "UUID(), "
				. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', "
				. "'1', 'ciniki_services', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $field) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['field_value']) . "', "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $row['date_added']) . "') "
				. ")";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.services');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Check for items that are missing a add value in history
	//
	$fields = array('uuid', 'service_id','step','name', 'description', 'instructions', 'duration', 'billable_hours');
	foreach($fields as $field) {
		//
		// Get the list of address which don't have a history for the field
		//
		$strsql = "SELECT ciniki_service_tasks.id, ciniki_service_tasks.$field AS field_value, "
			. "UNIX_TIMESTAMP(ciniki_service_tasks.date_added) AS date_added, "
			. "UNIX_TIMESTAMP(ciniki_service_tasks.last_updated) AS last_updated "
			. "FROM ciniki_service_tasks "
			. "LEFT JOIN ciniki_services ON (ciniki_service_tasks.service_id = ciniki_services.id "
				. ") "
			. "LEFT JOIN ciniki_service_history ON (ciniki_service_tasks.id = ciniki_service_history.table_key "
				. "AND ciniki_service_history.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND ciniki_service_history.table_name = 'ciniki_service_tasks' "
				. "AND (ciniki_service_history.action = 1 OR ciniki_service_history.action = 2) "
				. "AND ciniki_service_history.table_field = '" . ciniki_core_dbQuote($ciniki, $field) . "' "
				. ") "
			. "WHERE ciniki_services.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_service_tasks.$field <> '' "
			. "AND ciniki_service_tasks.$field <> '0000-00-00' "
			. "AND ciniki_service_tasks.$field <> '0000-00-00 00:00:00' "
			. "AND ciniki_service_history.uuid IS NULL "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'history');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	
		$elements = $rc['rows'];
		foreach($elements AS $rid => $row) {
			$strsql = "INSERT INTO ciniki_service_history (uuid, business_id, user_id, session, action, "
				. "table_name, table_key, table_field, new_value, log_date) VALUES ("
				. "UUID(), "
				. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', "
				. "'1', 'ciniki_service_tasks', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $field) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['field_value']) . "', "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $row['date_added']) . "') "
				. ")";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.services');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Check for items missing a UUID
	//
	$strsql = "UPDATE ciniki_service_history SET uuid = UUID() WHERE uuid = ''";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Remote any entries with blank table_key, they are useless we don't know what they were attached to
	//
	$strsql = "DELETE FROM ciniki_service_history WHERE table_key = ''";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.services');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}


	return array('stat'=>'ok');
}
?>
