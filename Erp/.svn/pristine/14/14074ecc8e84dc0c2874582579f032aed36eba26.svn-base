<?php

/**
 * 已称重未发货订单列表
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once("includes/lib_main.php");
admin_priv('weight_delivery');

global $db;
$session_party = $_SESSION['party_id'];
$sql = "select IS_LEAF from romeo.party where party_id = '{$session_party}' limit 1";
$is_leaf = $db->getOne($sql);
if($is_leaf == 'N'){
	die("请选择具体的业务组再来操作！");
}elseif(in_array($session_party,array('65644','65650','65581','65617','65652','65653','65569','65622','65645','65661','65668','65646','65539','65670','65619','65628','65639'))){
	die("该业务组发货需通过码托交接完成");
}

$tracking_barcode_list = get_weighted_not_shipment();
$batch_bill_no = get_batch_bill_no($tracking_barcode_list);
//var_dump('$batch_bill_no');var_dump($batch_bill_no);
$facility_list = get_user_facility();
$facilities = implode(',', $facility_list);
$smarty->assign('facilities',$facilities);
$smarty->assign('batch_bill_no',$batch_bill_no);
$smarty->assign('tracking_barcode_list',$tracking_barcode_list);
$smarty->display("oukooext/weighted_not_shipment.htm");

function get_batch_bill_no($tracking_barcode_list) {
	if(empty($tracking_barcode_list)) return null;
	$batch_bill_no = '';
	foreach($tracking_barcode_list as $tracking_barcode) {
		$batch_bill_no .= $tracking_barcode['tracking_number'].'-'.$tracking_barcode['weight'].' ';
	}
	return $batch_bill_no;
}

function get_weighted_not_shipment() {
	global $db;
	$sql = "select oi.order_sn,oi.order_id,oi.order_time,s.tracking_number,
		format(s.shipping_leqee_weight/1000,2) as weight,ifnull(bm.barcode,'') as barcode,p.name as party_name,f.facility_name
		from ecshop.ecs_order_info oi
		inner join romeo.party p ON convert(oi.party_id using utf8) = p.party_id
		inner join romeo.facility f ON oi.facility_id = f.facility_id
		inner join romeo.order_shipment os ON convert(oi.order_id using utf8) = os.order_id
		inner join romeo.shipment s ON os.shipment_id = s.shipment_id
		left join ecshop.ecs_barcode_tracking_mapping bm ON s.tracking_number = bm.tracking_number
		where 
		oi.order_time > ADDDATE(now(),-700)
		and oi.shipping_status = 8 and oi.order_status = 1 
		and oi.order_type_id in('SALE','SHIP_ONLY','EXCHANGE')
		and s.shipping_leqee_weight > 0 and oi.party_id = {$_SESSION['party_id']}  and ". facility_sql('oi.facility_id')."
        group by s.tracking_number order by f.facility_id,p.party_id,oi.order_id limit 100";
//    var_dump('get_weighted_not_shipment');var_dump($sql);
	$tracking_barcode_list = $db->getAll($sql);
	return $tracking_barcode_list;
}

