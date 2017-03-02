<?php
define('IN_ECS', true);

require('includes/init.php');
include_once('includes/lib_carrier.php');
include_once('includes/cls_json.php');

$act = $_REQUEST['act'];
$bill_no = $_REQUEST['bill_no'];
$carrier_id = $_REQUEST['carrier_id'];
if (!$bill_no || !$carrier_id) {
	die('no bill no');
}
if($act == 'getCarrier'){
  $result = get_carrier_info($bill_no, $carrier_id);
  $json = new JSON;
  echo $json->encode($result);  
}else{	
	print get_carrier_info($bill_no, $carrier_id);
}

