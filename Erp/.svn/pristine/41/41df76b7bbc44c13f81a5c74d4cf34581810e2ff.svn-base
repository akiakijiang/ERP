<?php
/**
 * 还机
 * 
 * @author ljzhou 2014-3-7
 */
define ( 'IN_ECS', true );
require ('includes/init.php');
require ("function.php");
require_once ('includes/debug/lib_log.php');


if(isset($_REQUEST['to_print']) && $_REQUEST['to_print']=='1'){
	$smarty->assign('to_print','1');
}

// 通用商品组织权限特殊判断 ljzhou 2013.07.03
if (check_goods_common_party ()) {
	admin_priv ( 'cg_storage_common' );
} else {
	admin_priv ( 'cw_finance_storage_main', 'cg_storage', 'purchase_order' );
}

// 消息
$info = $_REQUEST ['info'];

admin_priv ( '4cw_finance_storage_main_csv', '5cg_storage_csv' );

$mtime = explode ( ' ', microtime () );
$start_time = $mtime [1] + $mtime [0];
$condition = getCondition ();

if ((trim ( $_REQUEST ['act'] ) == 'search')) {
	
	//获取内部人员借机的订单
	$sql = 
		"SELECT
		ifnull(ii.unit_cost,0) as purchase_paid_amount, ii.inventory_item_acct_type_id as order_type, ii.serial_number, ir.created_by_user_login as action_user, ii.status_id,iid.created_stamp, iid.order_goods_id,
		og.goods_name, og.goods_id, func_get_goods_category_name(g.top_cat_id, g.cat_id) as category,og.goods_number,
		oi.order_sn, oi.order_id, oi.order_time, oi.consignee, oi.postscript, p.provider_name, 
		if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) as barcode, 
		(select max(bh.predict_return_time) from ecshop.ecs_borrow_history bh where oi.order_id = bh.order_id limit 1) as predict_return_time,
		if((og.goods_number - ifnull((select sum(quantity_on_hand_diff) from romeo.inventory_item_detail iid2 
		where iid2.quantity_on_hand_diff > 0 and iid2.order_goods_id = convert(og.rec_id using utf8) group by og.rec_id),0)) <=0,1,0) as is_return 
		FROM
		{$ecs->table('order_info')} oi
		LEFT JOIN {$ecs->table('order_goods')} og ON oi.order_id = og.order_id
		LEFT JOIN romeo.inventory_item_detail iid ON convert (og.rec_id using utf8) = iid.order_goods_id
		LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
		LEFT JOIN romeo.inventory_transaction ir ON iid.inventory_transaction_id = ir.inventory_transaction_id
	    LEFT JOIN {$ecs->table('goods')} g ON g.goods_id = og.goods_id
	    LEFT JOIN ecshop.ecs_goods_style AS gs ON gs.style_id = og.style_id and gs.goods_id = og.goods_id and gs.is_delete=0
		LEFT JOIN ecshop.ecs_style AS s on s.style_id = gs.style_id 
		LEFT JOIN {$ecs->table('provider')} p ON p.provider_id = ii.provider_id
		WHERE
		oi.order_type_id = 'BORROW' $condition 
		AND ". party_sql('oi.party_id') ."
		AND ". facility_sql('oi.facility_id') ."
		GROUP by og.rec_id
	";
	$sh_goods_list = $db->getAll($sql);

	$sh_sum_purchase_paid_amount = 0.0;
	$goods['return_time_history'] = array();
	foreach ($sh_goods_list as $key => &$goods) {
		$sh_sum_purchase_paid_amount += $goods['purchase_paid_amount'];
		$sql = "
			select concat_ws('|',operator,left(predict_return_time, 10),left(operate_time, 10)) as operator_and_time
			from ecshop.ecs_borrow_history
			where order_id = '{$goods['order_id']}'
			order by operate_time desc
		";
		$goods['return_time_history'] = $db->getAll($sql);
	}
	$smarty->assign ( 'info', $info );
} 

$mtime = explode ( ' ', microtime () );
$end_time = $mtime [1] + $mtime [0];
$cost_time = round ( $end_time - $start_time, 2 );

$smarty->assign ( 'sh_goods_list', $sh_goods_list );
$smarty->assign ( 'sh_sum_purchase_paid_amount', $sh_sum_purchase_paid_amount );

// 第三方仓库要屏蔽商品分类搜索 ljzhou 2013.04.23
$is_third_party_warehouse = false;
if (check_admin_priv ( 'third_party_warehouse' ) && ($_SESSION ['action_list'] != 'all')) {
	$is_third_party_warehouse = true;
}
$smarty->assign ( 'is_third_party_warehouse', $is_third_party_warehouse );

$smarty->assign ( 'cost_time', $cost_time );
$smarty->assign ( 'available_facility', array_intersect_assoc ( get_available_facility (), get_user_facility () ) );


$smarty->assign ( 'facility_name', facility_mapping ( $_SESSION ['facility_id'] ) );

//is_sinri_csv
if($_REQUEST['is_sinri_csv']=='on'){
	//print_r($sh_goods_list);
	//die('sinri done');
	header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","还机信息表") . ".csv");
    $out = $smarty->fetch('oukooext/h_return_list.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
}

$smarty->display ( 'oukooext/h_return.htm' );



function getCondition() {
	global $ecs;
	
	$condition = "";
	$barcode = trim ( $_REQUEST ['barcode'] );
	$goods_name = trim ( $_REQUEST ['goods_name'] );
	$is_new = $_REQUEST ['is_new'];
	$available_facility = $_REQUEST ['available_facility'];

	if ($barcode != '') {
		$condition .= " AND if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) LIKE '%{$barcode}%' ";
	}
	if ($goods_name != '') {
		$condition .= " AND CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, ''))) LIKE '%{$goods_name}%'";
	}
	if ($is_new != - 1 && $is_new !== null) {
		$condition .= " AND ii.status_id = '{$is_new}'";
	}

	//仓库
	if ($available_facility != - 1 && $available_facility != '') {
		$condition .= " AND oi.facility_id = '{$available_facility}' ";
	}
	
	return $condition;
}

?>
