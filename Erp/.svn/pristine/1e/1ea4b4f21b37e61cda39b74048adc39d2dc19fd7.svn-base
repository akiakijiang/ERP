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
  if (count($temp_data) == 28) {
  	$temp_data[12] = strtotime($temp_data[12]) >0 ? strtotime($temp_data[12]) : '';
    $temp_data[13] = strtotime($temp_data[13]) >0 ? strtotime($temp_data[13]) : '';
    $temp_data[14] = strtotime($temp_data[14]) >0 ? strtotime($temp_data[14]) : '';
    $temp_data[15] = strtotime($temp_data[15]) >0 ? strtotime($temp_data[15]) : '';
    $temp_data[16] = strtotime($temp_data[16]) >0 ? strtotime($temp_data[16]) : '';
  
    $insert_array[] = "('". join("','", $temp_data) ."')";
  }
}
if (count($insert_array)) {
	$datastr = join(",", $insert_array);

  $db->query("INSERT INTO call_log(ID, TrunkID, TrunkGroupID, AgentGroupID, CallerID, PhoneNO, AgentID, WorkerID, iINorOUT, RecFileName, iFileFormat, iRecTimeLong, CallInTime, StartACDTime, CallAgentTime, AgentRcvTime, CallEndTime, iFileState, FaxFileName, isFaxOut, isFaxSuccess, iFaxPages, strFileState,Research, Listen, Listener, Memo, ChatMemo) values {$datastr}");
}
print "done";
