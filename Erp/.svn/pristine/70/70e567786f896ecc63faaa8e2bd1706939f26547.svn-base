<?php

/**
 * 业务组下 非已发货订单筛选 用于发货操作（查询订单时长3天内）
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
set_time_limit(300);

global $db;
$session_party = $_SESSION['party_id'];

if(!in_array($session_party,array('16','64','65565','65668'))){
	die("乐其电教，乐其蓝光，乐贝蓝光业务组才可使用，其他业务组待配货订单请在订单查询页面搜索");
}
$sql = "select IS_LEAF from romeo.party where party_id = '{$session_party}' limit 1";
$is_leaf = $db->getOne($sql);
if($is_leaf == 'N'){
	die("请选择具体的业务组再来操作！");
}


$order_condition='';
$search_text = trim($_REQUEST['search_text']);
if(!empty($search_text)){
	$order_condition .= " AND oi.order_sn = '{$search_text}' ";
}

$user_facility_list = array_intersect_assoc(get_available_facility(),get_user_facility());
$user_facility_str = implode("','",array_keys($user_facility_list));
$sql1 = "select oi.order_id,order_sn,oi.order_time,order_status,pay_name,shipping_name,shipping_status,consignee,postscript,
		 oir.status as reserve_status,oi.pay_status,
		 (select attr_value from ecshop.order_attribute oa where oi.order_id = oa.order_id and oa.attr_name = 'TAOBAO_SELLER_MEMO') as action_note
		from ecshop.ecs_order_info oi use index(order_info_multi_index,order_sn ) 
		left join romeo.order_inv_reserved oir on oir.order_id = oi.order_id and oi.facility_id = oir.facility_id
		where order_status = 1 and pay_status=2 and oi.order_type_id in ('SALE','RAM_EXCHANGE') and oi.order_time >date_sub(NOW(),interval 3 day)
		and shipping_status in (8,9) and ( handle_time = 0 OR handle_time < UNIX_TIMESTAMP() )
		and oi.party_id = {$_SESSION['party_id']} and oi.facility_id in ('{$user_facility_str}') {$order_condition}
		";
$refs_value_order = $refs_order = array();
$orders = $db->getAllRefby($sql1, array('order_id'), $refs_value_order, $refs_order, false);

$count_all = count($orders);

// 组织数据
try {
    if (!empty($orders)){
    	// 获得订单商品
		$sql_order_goods = "
			SELECT og.order_id,og.goods_name,og.goods_number,group_concat(distinct ii.serial_number) as serial_number,
			if(og.style_id=0 or og.style_id is null,og.goods_id,concat(og.goods_id,'_',og.style_id)) as productcode,
			sum(ii2.QUANTITY_ON_HAND_TOTAL) as qohTotal
			FROM ecshop.ecs_order_goods AS og 
			INNER join ecshop.ecs_order_info oi on oi.order_id = og.order_id  
			LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
			LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
			INNER JOIN romeo.product_mapping pm on og.goods_id = pm.ECS_GOODS_ID and og.style_id = pm.ecs_style_id
			LEFT JOIN romeo.inventory_item ii2 on ii2.PRODUCT_ID =   pm.PRODUCT_ID  and ii2.facility_id = oi.facility_id
			WHERE ii2.STATUS_ID ='INV_STTS_AVAILABLE' and ii2.QUANTITY_ON_HAND_TOTAL > 0 and ".db_create_in($refs_value_order['order_id'], 'og.order_id')."
			group by og.order_id,og.rec_id";
		$goods_list = $db->getAllRefby($sql_order_goods, array('order_id'), $refs_value_goods, $refs_goods, false);
    	
	    foreach ($orders as $key => $order) {
	        $orders[$key]['goods_list'] = $refs_goods['order_id'][$order['order_id']];
	    }
    }else{
    	
    }
} catch (Exception $e) {
	require_once(ROOT_PATH.'includes/debug/lib_log.php');
	QLog::log($e->getMessage());
	die("系统繁忙，请稍候再试");
}



$smarty->assign('orders', $orders);
$smarty->assign('count_all',$count_all);
$smarty->assign('order_status_list',    $GLOBALS['_CFG']['adminvars']['order_status']);      // 发货状态 
$smarty->assign('pay_status_list', $GLOBALS['_CFG']['adminvars']['pay_status']);   // 付款状态
$smarty->assign('shipping_status_list', $GLOBALS['_CFG']['adminvars']['shipping_status']);   // 订单状态列表
$smarty->display('distributor/distribution_dph.htm');
