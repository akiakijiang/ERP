<?php
define('IN_ECS', true);

require('../includes/master_init.php');
$p = $_REQUEST['p'];

if ($p != 'OUKOO_IMPORTCALLCENTER_PASS') {
  header("Status: 404 Not Found",false,404);
	die();
}

if ($_REQUEST['get'] == "maxid") {
	print "done\n".$db->getOne("select max(ID) from call_log");
	die();
}

$data = $_REQUEST['data'];

$logs = explode("\n",$data);
$datastr = "";
$insert_array = array();
foreach ($logs as $log) {
  $temp_data = explode("\t", $log);
  if (count($temp_data) == 8) {
  	$temp_data[6] = strtotime($temp_data[6]) >0 ? strtotime($temp_data[6]) : '';
    $temp_data[7] = strtotime($temp_data[7]) >0 ? strtotime($temp_data[7]) : '';
    $temp_data[8] = $temp_data[7] - $temp_data[6];
    $temp_data[9] = $temp_data[6];
	
    $insert_array[] = "('". join("','", $temp_data) ."')";
  }
}
if (count($insert_array)) {
	$datastr = join(",", $insert_array);

  $db->query("INSERT INTO call_log(ID, TrunkID, CallerID, PhoneNO, WorkerID, iINorOUT, AgentRcvTime, CallEndTime, iRecTimeLong, CallInTime) values {$datastr}");
}
print "done";
