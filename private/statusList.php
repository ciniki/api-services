<?php
//
// Description
// ===========
// This function will return the array of status numbers and the associated text descriptions.
//
// Arguments
// =========
// ciniki:
// business_id: 		The ID of the business the request is for.
// 
// Returns
// =======
//
function ciniki_services_statusList($ciniki, $business_id) {
	return array('stat'=>'ok', 'list'=>array('1'=>'Missing', '2'=>'Upcoming', '10'=>'Entered', '20'=>'Started', '30'=>'Pending', '50'=>'Completed', '60'=>'Signed Off', '61'=>'Skipped'));
}
?>
