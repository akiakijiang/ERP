<?php
define('IN_ECS', true);
require_once 'includes/init.php';
include_once 'function.php';

//require_once(ROOT_PATH . 'admin/includes/express/baseXLS.php');
set_include_path(get_include_path() . PATH_SEPARATOR .ROOT_PATH. 'admin/includes/Classes/');
require_once(ROOT_PATH . 'admin/includes/Classes/PHPExcel.php');
require_once(ROOT_PATH . 'admin/includes/Classes/PHPExcel/IOFactory.php');

admin_priv("office_shipment");
$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']): 'insert' ;
$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';
$type = isset($_REQUEST['type']) ? trim($_REQUEST['type']): '' ;
if($type == "download"){
	header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "办公件" ) . ".csv" );
	$out = "组织,发货日期(yyyy-mm-dd),发件省,发件市,发件区,收件省,收件市,收件区,快递方式,运单号,运单类型,包裹重量,备注\n";
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
}
global $db;
if (!empty($action)) {
	$party_id = isset($_REQUEST['party_id']) ? trim($_REQUEST['party_id']) : '';
	$date = isset($_REQUEST['date']) ? trim($_REQUEST['date']) : '';
	$start_province = isset($_REQUEST['start']['province']) ? trim($_REQUEST['start']['province']) : '';
	$start_city = isset($_REQUEST['start']['city']) ? trim($_REQUEST['start']['city']) : '';
	$start_district = isset($_REQUEST['start']['district']) ? trim($_REQUEST['start']['district']) : '';
	$end_province = isset($_REQUEST['end']['province']) ? trim($_REQUEST['end']['province']) : '';
	$end_city = isset($_REQUEST['end']['city']) ? trim($_REQUEST['end']['city']) : '';
	$end_district = isset($_REQUEST['end']['district']) ? trim($_REQUEST['end']['district']) : '';
	$shipping_id = isset($_REQUEST['shipping_id']) ? trim($_REQUEST['shipping_id']) : '';
	$tracking_number = isset($_REQUEST['tracking_number']) ? trim($_REQUEST['tracking_number']) : '';
	$package_type = isset($_REQUEST['package_type']) ? trim($_REQUEST['package_type']) : '';
}
if ($act == "insert" || $act == "update") {
	if ($action == 'add') {
		$weight = ($_REQUEST['weight'] != '') ? trim($_REQUEST['weight']) : 0.0000;
		$note = isset($_REQUEST['note']) ? trim($_REQUEST['note']) : '';
		$field_name = " party_id, shipping_date, start_province, start_city, end_province, end_city, tracking_number,
		    package_type, shipping_id, weight , action_user, status, note, action_time";
		$field_value = " {$party_id}, '{$date}', {$start_province}, {$start_city}, {$end_province}, {$end_city}, '{$tracking_number}',
		    {$package_type}, {$shipping_id}, '{$weight}' , '{$_SESSION['admin_name']}', 'OK', '{$note}', '".date("Y-m-d H:i:s", time())."'";
		if (!empty($start_district)) {
			$field_name .= " , start_district ";
			$field_value .= " , {$start_district} ";
		}
		if (!empty($end_district)) {
			$field_name .= " , end_district ";
			$field_value .= " , {$end_district} ";
		}
		$office_shipment = new stdClass();
		$office_shipment->partyId = $party_id;
		$office_shipment->shippingDate = $date;
		$office_shipment->startProvince = $start_province;
		$office_shipment->startCity = $start_city;
		$office_shipment->startDistrict = $start_district;
		$office_shipment->endProvince = $end_province;
		$office_shipment->endCity = $end_city;
		$office_shipment->endDistrict = $end_district;
		$office_shipment->trackingNumber = $tracking_number;
		$office_shipment->packageType = $package_type;
		$office_shipment->shippingId = $shipping_id;
		$office_shipment->weight = $weight;
		$office_shipment->actionUser = $_SESSION['admin_name'];
		$office_shipment->status = 'OK';
		$office_shipment->note = $note;
		$office_shipment->actionTime = date("Y-m-d H:i:s", time());
		$office_shipment->shippingCost = 0.00;
		$office_shipment->outWeight = 0.0000;
		$office_shipment->lastActionUser = '';
		$office_shipment->lastUpdateTime = date("Y-m-d H:i:s", time());
		$soap_client = soap_get_client('OfficeShipmentService');
		$result = $soap_client->createOfficeShipment(array('officeShipment' => $office_shipment));
		if ($result->return) {
			$message = "快递单号：".$tracking_number . "已录入，请核实";
		} else {
		    $message = "快递单号：".$tracking_number ."录入失败，如有疑问，请联系ERP组，谢谢";
		}
	}
	if ($act == "update") {
		$sql = "select * from romeo.office_shipment where shipment_id = {$_REQUEST['shipment_id']} limit 1";
		$update_value = $db->getRow($sql);
		if (!empty($update_value['start_province'])) {
			$start_city_list = get_regions(2, $update_value['start_province']);
			$smarty->assign("start_city_list", $start_city_list);
		}
		if (!empty($update_value['start_city'])) {
			$start_district_list = get_regions(3, $update_value['start_city']);
			$smarty->assign("start_district_list", $start_district_list);
		}
		if (!empty($update_value['end_province'])) {
			$end_city_list = get_regions(2, $update_value['end_province']);
			$smarty->assign("end_city_list", $end_city_list);
		}
		if (!empty($update_value['end_city'])) {
			$end_district_list = get_regions(3, $update_value['end_city']);
			$smarty->assign("end_district_list", $end_district_list);
		}
		$smarty->assign("update_value", $update_value);
	}
} else if ($act == "search") {
	$condition = " 1 ";
	if ($action == 'search') {
		if ($_REQUEST['party_id'] != 0) {
			$condition .= " and os.party_id = ". trim($_REQUEST['party_id']);
		}
		if (!empty($_REQUEST['start_shipping_date'])) {
			$condition .= " and os.shipping_date >= '{$_REQUEST['start_shipping_date']}' ";
		}
		if (!empty($_REQUEST['end_shipping_date'])) {
			$condition .= " and os.shipping_date < '{$_REQUEST['end_shipping_date']}' ";
		}
		if ($_REQUEST['shipping_id']!= 0) {
			$condition .= " and os.shipping_id = " . $_REQUEST['shipping_id'];
		}
		$tracking_number = trim($_REQUEST['tracking_number']);
		if (!empty($tracking_number)) {
			$condition .= " and os.tracking_number = '{$tracking_number}' ";
		}
		if ($_REQUEST['package_type'] != 0) {
			$condition .= " and os.package_type = '{$_REQUEST['package_type']}'";
		}
		if ($_REQUEST['start']['province'] != 0) {
			$condition .= " and os.start_province = {$_REQUEST['start']['province']} ";
		}
		if (isset($_REQUEST['start']['city']) && $_REQUEST['start']['city'] != 0) {
			$condition .= " and os.start_city = {$_REQUEST['start']['city']} ";
		}
		if (isset($_REQUEST['start']['district']) && $_REQUEST['start']['district'] != 0) {
			$condition .= " and os.start_district = {$_REQUEST['start']['district']} ";
		}
		if ($_REQUEST['end']['province'] != 0) {
			$condition .= " and os.end_province = {$_REQUEST['end']['province']} ";
		}
		if (isset($_REQUEST['end']['city']) && $_REQUEST['end']['city'] != 0) {
			$condition .= " and os.end_city = {$_REQUEST['end']['city']} ";
		}
		if (isset($_REQUEST['end']['district']) && $_REQUEST['end']['district'] != 0) {
			$condition .= " and os.end_district = {$_REQUEST['end']['district']} ";
		}
	} else if ($action == 'select_shipment') {
		$condition .= " and os.shipment_id = {$_REQUEST['shipment_id']}";
	}
	if (!empty($action)) {
		$sql = "
		select os.shipment_id, os.party_id, os.shipping_date, os.start_province, os.start_city, os.start_district,
			os.end_province, os.end_city, os.end_district, os.tracking_number, os.package_type, os.shipping_id,
			os.weight, os.action_user, os.status, os.note, r1.region_name as start_province_name, 
			r2.region_name as start_city_name,r3.region_name as start_district_name,
			r4.region_name as end_province_name, r5.region_name as end_city_name, 
			r6.region_name as end_district_name, p.name, os.action_time, s.shipping_name
		from romeo.office_shipment os
		left join romeo.party p on os.party_id = p.party_id
		left join ecshop.ecs_shipping s on s.shipping_id = os.shipping_id
		left join ecshop.ecs_region r1 on r1.region_id = os.start_province
		left join ecshop.ecs_region r2 on r2.region_id = os.start_city
		left join ecshop.ecs_region r3 on r3.region_id = os.start_district
		left join ecshop.ecs_region r4 on r4.region_id = os.end_province
		left join ecshop.ecs_region r5 on r5.region_id = os.end_city
		left join ecshop.ecs_region r6 on r6.region_id = os.end_district
		where os.status = 'OK' and " .$condition;
		$search_list = $db->getAll($sql);
		if($_REQUEST['is_export']){
			//echo "is_export:".$_REQUEST['is_export'];
			do_export_as_xlsx($search_list);
			die();
		}
		$smarty->assign("action", $action);
		$smarty->assign("search_list", $search_list);
	}
	
} else if ($act == 'check_tracking_number' && trim($_REQUEST['request'] == 'ajax')) {
	//检查录入运单号是否重复
	$tracking_number = trim($_REQUEST['tracking_number']);
	$shipping_id = trim($_REQUEST['shipping_id']);
	$cond = "";
	if ($_REQUEST['shipment_id']) {
		$cond .= " and shipment_id != ". trim($_REQUEST['shipment_id']);
	}
	$sql = "select 1 from romeo.office_shipment where tracking_number = '{$tracking_number}' 
	    and shipping_id = {$shipping_id} and status = 'OK' ". $cond ."  limit 1;";
	echo json_encode($db->getOne($sql));
	exit();
} else if ($act == 'delete' && trim($_REQUEST['request'] == 'ajax')) {
	$shipment_id = $_REQUEST['shipment_id'];
	$message = "error";
	if (!empty($shipment_id)) {
		$soap_client = soap_get_client('OfficeShipmentService');
		$request = array("shipmentId" => $shipment_id, "actionUser" => $_SESSION['admin_name'], "status" => 'DELETE');
		$result = $soap_client->updateOfficeShipmentStatus($request);
		if ($result->return) {
			$message = "ok";
		}
	}
	echo json_encode($message);
	exit();
} else if ($act == 'update_shipment') {
	$shipment_id =  $_REQUEST['shipment_id'];
	$sql = "select * from romeo.office_shipment where shipment_id = {$shipment_id} limit 1";
	$shipment_item = $db->getRow($sql);
	$weight = ($_REQUEST['weight'] != '') ? trim($_REQUEST['weight']) : 0.0000;
	$note = isset($_REQUEST['note']) ? trim($_REQUEST['note']) : '';
	if (($party_id == $shipment_item['party_id']) && ($date == $shipment_item['shipping_date']) &&
		($start_province == $shipment_item['start_province']) && ($start_city == $shipment_item['start_city'])
		&& ($start_district == $shipment_item['start_district']) && ($end_province == $shipment_item['end_province'])
		&& ($end_city == $shipment_item['end_city']) && ($end_district == $shipment_item['end_district'])
		&& ($tracking_number == $shipment_item['tracking_number']) && ($package_type == $shipment_item['package_type'])
		&& ($shipping_id == $shipment_item['shipping_id']) && ($weight == $shipment_item['weight'])
		&& ($note == $shipment_item['note'])) {
		sys_msg("内容未修改，请核实后再修改");
	} else {
		$condition = "";
		if (!empty($start_district)) {
			$condition .= ", start_district = {$start_district}";
		}
		if (!empty($end_district)) {
			$condition .= ", end_district = {$end_district} ";
		}
		$office_shipment = new stdClass();
		$office_shipment->shipmentId = $shipment_id;
		$office_shipment->partyId = $party_id;
		$office_shipment->shippingDate = $date;
		$office_shipment->startProvince = $start_province;
		$office_shipment->startCity = $start_city;
		$office_shipment->startDistrict = $start_district;
		$office_shipment->endProvince = $end_province;
		$office_shipment->endCity = $end_city;
		$office_shipment->endDistrict = $end_district;
		$office_shipment->trackingNumber = $tracking_number;
		$office_shipment->packageType = $package_type;
		$office_shipment->shippingId = $shipping_id;
		$office_shipment->weight = $weight;
		$office_shipment->actionUser = $_SESSION['admin_name'];
		$office_shipment->status = 'OK';
		$office_shipment->note = $note;
		$office_shipment->actionTime = date("Y-m-d H:i:s", time());
		$office_shipment->shippingCost = ($shipment_item['shippping_cost'] != '') ? $shipment_item['shippping_cost'] : 0.00;
		$office_shipment->outWeight = ($shipment_item['out_weight'] != '') ? $shipment_item['out_weight'] : 0.0000;
		$office_shipment->lastActionUser = ($shipment_item['last_action_user'] != '') ? $shipment_item['last_action_user'] : '';
		$office_shipment->lastUpdateTime = date("Y-m-d H:i:s", time());
		$soap_client = soap_get_client('OfficeShipmentService');
		$result = $soap_client->updateOfficeShipment(array('officeShipment' => $office_shipment));
		if ($result->return) {
			$message = "办公件相关信息已修改，请查看确认";
		} else {
			$message = "办公件相关信息修改失败，请重新操作，如有问题，请联系ERP组解决";
		}
		$url = $_SERVER['PHP_SELF']."?act=search&action=select_shipment&shipment_id=".$shipment_id;
		header("Location: {$url}"); 
	}
}



$party_list = $db->getAll("select party_id, name from romeo.party where status = 'OK' and SYSTEM_MODE <>'0' ");
$province_list = $db->getAll("select region_id, region_name from ecshop.ecs_region where parent_id = 1");
$shipping_list = $db->getAll("SELECT * FROM {$ecs->table('shipping')} WHERE enabled = 1 ORDER BY shipping_code, support_cod");
$smarty->assign("act", $act);
$smarty->assign("party_list", $party_list);
$smarty->assign("province_list", $province_list);
$smarty->assign("shipping_list", $shipping_list);
$smarty->assign("message", $message);
$smarty->display("office_shipment.htm");

function do_export_as_xlsx($search_list){
	/*
	A os.shipment_id, B os.party_id, C os.shipping_date, 
	//D os.start_province, E os.start_city, F os.start_district,
	//G os.end_province, H os.end_city, I os.end_district, 
	D r1.region_name as start_province_name, E r2.region_name as start_city_name,F r3.region_name as start_district_name,
	G r4.region_name as end_province_name, H r5.region_name as end_city_name, I r6.region_name as end_district_name, 
	J os.tracking_number, K os.package_type, 
	// os.shipping_id,
	M os.weight, N os.action_user, O os.status, P os.note, 
	Q p.name, R os.action_time, L s.shipping_name
	*/

	$file_name='office_shipment_export';

	//$cell_nos = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S');
	$cell_captains=array(
		'A'=>'发货单号','B'=>'业务组织','C'=>'发货日期',
		'D'=>'发出省','E'=>'市','F'=>'区',
		'G'=>'到达省','H'=>'市','I'=>'区',
		'J'=>'运单号','K'=>'包裹类型','L'=>'快递方式','M'=>'重量','N'=>'操作人',
		'O'=>'状态','P'=>'备注',
		'Q'=>'姓名','R'=>'操作时间',
	);
    	
    $excel = new PHPExcel();
    $excel->getProperties()->setTitle($file_name);
    $sheet_no = 1;

    $sheet_no ++;
    $sheet = $excel->getActiveSheet();
    $sheet->setTitle('sheet1');
    foreach ($cell_captains as $key => $value) {
    	$sheet->setCellValue($key.'1',$value);
    }
    foreach ($search_list as $no => $line) {
    	$i=$no+2;

    	global $db;
    	$sql="SELECT p.NAME FROM romeo.party p WHERE p.PARTY_ID='".$line['party_id']."';";
    	$party_name=$db->getOne($sql);
    	//<!--{if $item.package_type == 1}-->文件<!--{else if $item.package_type == 2}-->包裹<!--{/if}-->
		$sheet->setCellValue("A$i", $line['shipment_id']);
		$sheet->setCellValue("B$i", $party_name);//$line['party_id']
		$sheet->setCellValue("C$i", $line['shipping_date']);
		$sheet->setCellValue("D$i", $line['start_province_name']);
		$sheet->setCellValue("E$i", $line['start_city_name']);
		$sheet->setCellValue("F$i", $line['start_district_name']);
		$sheet->setCellValue("G$i", $line['end_province_name']);
		$sheet->setCellValue("H$i", $line['end_city_name']);
		$sheet->setCellValue("I$i", $line['end_district_name']);
		$sheet->setCellValue("J$i", $line['tracking_number']);
		$sheet->setCellValue("K$i", ($line['package_type']==1?'文件':($line['package_type']==2?'包裹':'未知')));
		$sheet->setCellValue("L$i", $line['shipping_name']);
		$sheet->setCellValue("M$i", $line['weight']);
		$sheet->setCellValue("N$i", $line['action_user']);
		$sheet->setCellValue("O$i", $line['status']);
		$sheet->setCellValue("P$i", $line['note']);
		$sheet->setCellValue("Q$i", $line['name']);
		$sheet->setCellValue("R$i", $line['action_time']);
	}

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="'.$file_name.'.xls"');
    header('Cache-Control: max-age=0');
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
    $output->save('php://output');
    exit;
}