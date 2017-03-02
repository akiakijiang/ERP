<?php
/**
 * 查找淘宝订单运单号（运营录单需要外部订单运单号）
 */
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once('distribution.inc.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
admin_priv ('export_tracking_number');

$start = (isset($_REQUEST ['start']) && strtotime($_REQUEST ['start']) !== false) ? $_REQUEST['start']:null;
$end = (isset($_REQUEST ['end']) &&  strtotime($_REQUEST ['end']) !== false) ? $_REQUEST['end']:null;
$distributor_id = isset($_REQUEST ['distributor_id'])?trim($_REQUEST ['distributor_id']):'0'; 

if ($_REQUEST['act']=='search' || $_REQUEST['act']=='export') {
	$contant = "";
	if(!empty($start) && !empty($end)){
		$contant .= " and FROM_UNIXTIME(oi.shipping_time) >='{$start}' and FROM_UNIXTIME(oi.shipping_time) <'{$end}' ";
	}else if(!empty($end)){
		$contant .= " and FROM_UNIXTIME(oi.shipping_time)>=date_add('{$end}', interval '-1' week) and FROM_UNIXTIME(oi.shipping_time) <'{$end}' ";
	}else if(!empty($start)){
		$contant .= " and FROM_UNIXTIME(oi.shipping_time) >='{$start}' ";
	}else{
		$contant .= " and FROM_UNIXTIME(oi.shipping_time)>date_add(NOW(), interval '-1' week) ";
	} 
	$taobao_order_sn = isset($_REQUEST ['taobao_order_sn'])?trim($_REQUEST ['taobao_order_sn']):'';
	$party_id = $_SESSION['party_id'];
	
	if($distributor_id !='0'){
		$contant .= " AND d.distributor_id = '{$distributor_id}' ";
		$name_sql = "SELECT name FROM ecshop.`distributor` where distributor_id = '{$distributor_id}' limit 1";
		$smarty->assign ('distributor_id',$distributor_id);
		$smarty->assign ('name',$db->getOne($name_sql));
	}
	if($taobao_order_sn!=''){
		$contant .= " AND oi.taobao_order_sn = '{$taobao_order_sn}'";
		$smarty->assign ('taobao_order_sn',$taobao_order_sn);
	} 
	$sql = "SELECT oi.order_id,oi.distributor_id,d.name,oi.facility_id,f.facility_name,oi.order_sn,
			os.SHIPMENT_ID,GROUP_CONCAT(s.tracking_number separator ';') tracking_number,
			FROM_UNIXTIME(oi.shipping_time) shipping_time,oi.taobao_order_sn,es.shipping_name,oi.address
		from ecshop.ecs_order_info oi
		LEFT JOIN romeo.order_shipment os ON os.order_id = CONVERT(oi.order_id USING utf8)
		LEFT JOIN romeo.shipment s on os.shipment_id = s.shipment_id
		left join ecshop.distributor d on d.distributor_id = oi.distributor_id 
		left join romeo.facility f on oi.facility_id = f.facility_id
		left join ecshop.ecs_shipping es on oi.shipping_id=es.shipping_id
		where shipping_status in (1, 2, 3) and taobao_order_sn is not NULL  and oi.party_id = '{$party_id}' $contant
		group by oi.order_id  ";
//		 Qlog::log("sql = ".$sql);
	$tracking_numbers = $db->getAll($sql);
	if($_REQUEST['act']=="search"){
		$smarty->assign('tracking_numbers',$tracking_numbers);
	}else{
		$smarty->assign('tracking_numbers',$tracking_numbers);
		header("Content-type:application/vnd.ms-excel");
		header("Content-Disposition:filename=" .iconv("UTF-8", "GB18030", "运单号列表") . ".csv");
		$out = $smarty->fetch('oukooext/export_tracking_number_csv.htm');
		echo $out;
		exit();	 
	}
}else if($_REQUEST['act']=="search_distributors"){
	$distributor_type = $_POST['distributor_type'];
	$sql = "SELECT d.distributor_id,d.name FROM ecshop.`distributor` d
			LEFT JOIN main_distributor m ON d.main_distributor_id = m.main_distributor_id WHERE type = '{$distributor_type}' AND d.status = 'NORMAL' and ".party_sql('d.party_id');
    $distributor_list = $db->getAll($sql);
    $json = new JSON;
    print $json->encode($distributor_list);
    exit();
}

$smarty->assign ('start',$start);
$smarty->assign ('end',$end);
$smarty->display ( "oukooext/export_tracking_number.htm" );