<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$monitor_header = new MonitorHeader("-gt信息监控页",array('party_id'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['party_id'])){
	$smarty->assign('msg', '请输入party_id');
}else
{
	$party_id = $_REQUEST['party_id'];
	$smarty->assign('monitor_data', GenerateSupplierFinanceInfo($party_id));
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateSupplierFinanceInfo($party_id){
	global $db;

	//ecshop.ecs_batch_order_info
	$sql = "select supplier_return_id,CREATED_STAMP,NOTE from romeo.supplier_return_request where party_id = '{$party_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'-gt申请信息表[romeo.supplier_return_request]', $sql, 'supplier_return_id');
	$return_data[] = $result['monitor_info'];

	return $return_data;
}
?>