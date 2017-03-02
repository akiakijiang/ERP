<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once ('function.php');

global $db;
$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):false;

if($act=='search'){
	$shipping_id = $_REQUEST ['shipping_id'];//快递方式
	$physical_facility = $_REQUEST ['physical_facility'];//物理仓
	$tray_barcode = trim ( $_REQUEST ['tray_barcode'] );//码托条码
	$delivery_start_time = $_REQUEST ['delivery_start_time'];//发货开始时间
	$delivery_end_time = $_REQUEST ['delivery_end_time'];//发货结束时间

	$condition='';
	// 快递方式
	if($shipping_id) {
		$condition .= " and p.shipping_id = {$shipping_id} ";
	}
	if($physical_facility){
		$condition .= " and p.physical_facility = '{$physical_facility}' ";
	}
	// 码托条码
	if($tray_barcode) {
		$condition .= " and p.pallet_no like '%{$tray_barcode}%' ";
	}
	// 发货时间
	if ($delivery_start_time != '') {
		$condition .= " and p.shipped_time > '{$delivery_start_time}' ";
	}	
	if ($delivery_end_time != '') {
		$condition .= " and p.shipped_time < '{$delivery_end_time}' ";
	}
	$sql = "select p.pallet_no,p.shipping_id,p.physical_facility,p.shipped_time,s.tracking_number from romeo.pallet p
			inner join romeo.pallet_shipment_mapping psm on psm.pallet_no = p.pallet_no
			inner join romeo.shipment s on psm.shipment_id = s.shipment_id
			where psm.bind_status = 'BINDED' {$condition} ";
    $keys = $values = array();
    $items = $db -> getAllRefBy($sql,array('pallet_no'),$keys,$values);
    $ret_items = $values['pallet_no'];
	$index=0;
	if(!empty($ret_items)){
			foreach($ret_items as $key => $value){
				$result[$index]['pallet_no'] = $key;//码托条码存储
				$result[$index]['shipping_id'] = $ret_items[$key][0]['shipping_id'];//快递方式
				$result[$index]['physical_facility'] = $ret_items[$key][0]['physical_facility'];//物理仓
				$result[$index]['shipped_time'] = $ret_items[$key][0]['shipped_time'];//发货时间
				$result[$index]['total_tracking'] = count($ret_items[$key]);//面单总数存储
				$tracking_number = array();
				for($i=0;$i<count($ret_items[$key]);$i++){
					$tracking_number[$i]=$ret_items[$key][$i]['tracking_number'];
		    	}
				 $result[$index]['tracking_number'] = $tracking_number;
				$index++;
			}
		}
	if($result[0]['pallet_no']==''){
		$result['display']='hide';
	}
}

$shipping_list = getShippingTypes();
$physical_facility_list_sql = "select distinct physical_facility from romeo.facility where is_closed='N' ";
$physical_facility_list = $db->getCol($physical_facility_list_sql);
$smarty->assign ( 'physical_facility_list', $physical_facility_list);
$smarty->assign ( 'shipping_list', $shipping_list );//获取快递方式
$smarty->assign('result',$result);
$smarty->display ( 'oukooext/shipping_handover.htm');

?>