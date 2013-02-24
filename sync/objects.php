<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_services_sync_objects($ciniki, &$sync, $business_id, $args) {
	//
	// A standard set of sync args are passed in for future use
	//

	$objects = array();
	$objects['service'] = array(
		'name'=>'Service',
		'table'=>'ciniki_services',
		'fields'=>array(
			'status'=>array(),
			'name'=>array(),
			'category'=>array(),
			'type'=>array(),
			'duration'=>array(),
			'repeat_type'=>array(),
			'repeat_interval'=>array(),
			'repeat_number'=>array(),
			'due_after_days'=>array(),
			'due_after_months'=>array(),
			),
		'history_table'=>'ciniki_service_history',
		);
	$objects['task'] = array(
		'name'=>'Service Task',
		'table'=>'ciniki_service_tasks',
		'fields'=>array(
			'service_id'=>array('ref'=>'ciniki.services.service'),
			'step'=>array(),
			'name'=>array(),
			'description'=>array(),
			'instructions'=>array(),
			'duration'=>array(),
			'billable_hours'=>array(),
			),
		'history_table'=>'ciniki_service_history',
		);
	$objects['subscription'] = array(
		'name'=>'Service Subscription',
		'table'=>'ciniki_service_subscriptions',
		'fields'=>array(
			'service_id'=>array('ref'=>'ciniki.services.service'),
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'status'=>array(),
			'date_started'=>array(),
			'date_ended'=>array(),
			),
		'history_table'=>'ciniki_service_history',
		);
	$objects['job'] = array(
		'name'=>'Service Job',
		'table'=>'ciniki_service_jobs',
		'fields'=>array(
			'subscription_id'=>array('ref'=>'ciniki.services.subscription'),
			'service_id'=>array('ref'=>'ciniki.services.service'),
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'project_id'=>array(),
			'invoice_id'=>array(),
			'tracking_id'=>array(),
			'name'=>array(),
			'pstart_date'=>array(),
			'pend_date'=>array(),
			'service_date'=>array(),
			'status'=>array(),
			'date_scheduled'=>array(),
			'date_started'=>array(),
			'date_due'=>array(),
			'date_completed'=>array(),
			'date_signedoff'=>array(),
			'efile_number'=>array(),
			'invoice_amount'=>array(),
			'tax1_name'=>array(),
			'tax1_amount'=>array(),
			'tax2_name'=>array(),
			'tax2_amount'=>array(),
			),
		'history_table'=>'ciniki_service_history',
		);
	$objects['job_task'] = array(
		'name'=>'Service Job Task',
		'table'=>'ciniki_service_job_tasks',
		'fields'=>array(
			'job_id'=>array('ref'=>'ciniki.services.job'),
			'task_id'=>array('ref'=>'ciniki.services.task'),
			'step'=>array(),
			'name'=>array(),
			'duration'=>array(),
			'status'=>array(),
			'description'=>array(),
			'date_scheduled'=>array(),
			'date_started'=>array(),
			'date_due'=>array(),
			'date_completed'=>array(),
			),
		'history_table'=>'ciniki_service_history',
		);
	$objects['job_note'] = array(
		'name'=>'Service Job Note',
		'table'=>'ciniki_service_job_notes',
		'fields'=>array(
			'parent_id'=>array(),
			'job_id'=>array('ref'=>'ciniki.services.job'),
			'task_id'=>array('ref'=>'ciniki.services.task'),
			'user_id'=>array('ref'=>'ciniki.users.user'),
			'content'=>array(),
			),
		'history_table'=>'ciniki_service_history',
		);
	$objects['job_hour'] = array(
		'name'=>'Service Job Hour',
		'table'=>'ciniki_service_job_hours',
		'fields'=>array(
			'job_id'=>array('ref'=>'ciniki.services.job'),
			'task_id'=>array('ref'=>'ciniki.services.task'),
			'user_id'=>array('ref'=>'ciniki.users.user'),
			'date_started'=>array(),
			'hours'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_service_history',
		);
	$objects['job_user'] = array(
		'name'=>'Service Job User',
		'table'=>'ciniki_service_job_users',
		'fields'=>array(
			'job_id'=>array('ref'=>'ciniki.services.job'),
			'user_id'=>array('ref'=>'ciniki.users.user'),
			'perms'=>array(),
			),
		'history_table'=>'ciniki_service_history',
		);
	$objects['setting'] = array(
		'type'=>'settings',
		'name'=>'Service Settings',
		'table'=>'ciniki_service_settings',
		'history_table'=>'ciniki_service_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
