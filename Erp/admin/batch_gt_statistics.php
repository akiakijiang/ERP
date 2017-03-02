<?php
/**
 * 批次gt订单统计信息
 * author ljzhou
 */
define ( 'IN_ECS', true );
require ('includes/init.php');
require_once ('includes/lib_goods.php');

$batch_gt_sn = $_REQUEST ['batch_gt_sn'];

if (empty($batch_gt_sn)) {
	die ( "请输入批次号" );
}

$batch_gt_info = get_batch_gt_info($batch_gt_sn);
//var_dump($batch_gt_info);

if(empty($batch_gt_info)) {
	die ( "批次订单号找不到或者不在当前组织中" );
}

$supplier_reutrn_list = get_supplier_return_list($batch_gt_sn);
//var_dump($supplier_reutrn_list);

$smarty->assign('batch_gt_info', $batch_gt_info);
$smarty->assign('supplier_reutrn_list', $supplier_reutrn_list);
$smarty->display ( 'oukooext/batch_gt_statistics.htm' );

function get_batch_gt_info($batch_gt_sn) {
	global $db;
	$sql = "SELECT * FROM ecshop.ecs_batch_gt_info WHERE batch_gt_sn ='{$batch_gt_sn}' AND " . party_sql ( 'party_id' );
//	var_dump($sql);
	$batch_order_info = $db->getRow ($sql);
	return $batch_order_info;
}

function get_supplier_return_list($batch_gt_sn) {
	global $db;
	$sql = "select f.facility_name,og.goods_name,ifnull(gs.barcode,g.barcode) as barcode,og.goods_number,if(r.status_id='INV_STTS_AVAILABLE','良品','不良品') as status_id,
	r.unit_price,r.return_order_amount,r.storage_amount,r.original_supplier_id,r.return_supplier_id,r.created_user_by_login,
	r.note,r.created_stamp,r.last_update_stamp,oi.order_sn,oi.order_id,bm.batch_gt_sn,boi.created_stamp,p.provider_name
	from ecshop.ecs_batch_gt_info boi 
	inner join ecshop.ecs_batch_gt_mapping bm ON boi.batch_gt_sn = bm.batch_gt_sn
	inner join ecshop.ecs_order_info oi ON bm.order_id = oi.order_id
	inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
	left join ecshop.ecs_goods g ON og.goods_id = g.goods_id
	left join ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id and og.style_id = gs.style_id and gs.is_delete=0
	inner join romeo.facility f ON oi.facility_id = f.facility_id
	inner join romeo.supplier_return_request_gt gt ON oi.order_sn = gt.SUPPLIER_RETURN_GT_SN
	inner join romeo.supplier_return_request r ON gt.supplier_return_id = r.supplier_return_id
	left join ecshop.ecs_provider p ON r.return_supplier_id = p.provider_id
	where boi.batch_gt_sn = '{$batch_gt_sn}' ";
//	var_dump($sql);
	$supplier_return_list = $db->getAll ($sql);
	return $supplier_return_list;
}

?>