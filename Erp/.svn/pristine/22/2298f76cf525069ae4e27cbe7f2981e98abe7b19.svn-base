<?php
/**
 * 批次收货入库+上架容器
 * author ljzhou
 * 2012.12.27
 */
define ( 'IN_ECS', true );
require ('includes/init.php');
admin_priv ( 'ck_in_storage', 'wl_in_storage' );
require_once ('includes/lib_goods.php');
require_once ('includes/lib_product_code.php');

// 导出csv的权限
$csv = $_REQUEST['csv'];
if ($csv) { admin_priv("admin_other_csv"); }

// 查询批次采购订单是否存在
$batch_order_id = intval ( $_REQUEST ['batch_order_id'] );
$sql = "SELECT * FROM {$ecs->table('batch_order_info')} WHERE batch_order_id = '{$batch_order_id}' AND " . party_sql ( 'party_id' );
$order = $db->getRow ( $sql, true );
if ($order == null) {
	die ( "批次订单号找不到或者不在当前组织中" );
}

//页面显示方式
$sort_method_list= array(
	'all' => '全部显示',
	'bigger2zero' => '未入库数>0',
	'zero' => '未入库数=0'
);

// 排序方式
$sort_method =
	isset($_REQUEST['sort']) && trim($_REQUEST['sort'])
    ? $_REQUEST['sort']
    : 'bigger2zero';    
    
$condition = '';
if($sort_method == 'bigger2zero') {
	$condition .= " and om.is_in_storage = 'N' ";
} else if($sort_method == 'zero') {
	$condition .= " and om.is_in_storage = 'Y' ";
}

//日期录入方式
$sort_validity_list= array(
	'start_validity' => '生产日期',
	'end_validity' => '到期日期'
);
// 排序方式
$sort_validity =
	isset($_REQUEST['sort_validity']) && trim($_REQUEST['sort_validity'])
    ? $_REQUEST['sort_validity']
    : 'start_validity';    

if($sort_validity == 'start_validity') {
	$validity_info = "生产日期";
} else {
	$validity_info = "到期日期";
}

$sql = "select
        sum(if(om.is_in_storage = 'Y',1,0)) as in_storage_num,sum(if(om.is_in_storage = 'N',1,0)) as not_in_storage_num,
        sum(if(om.is_cancelled = 'Y',1,0)) as cancelled_num,sum(if(om.is_over_c = 'Y',1,0)) as over_c_num,
        sum(if(om.order_id is not null,1,0)) as total_num
        from {$ecs->table('batch_order_mapping')} om 
        where om.batch_order_id = {$batch_order_id}
      ";
$statistics = $db->getRow($sql);

/**
 * 搜索列表
 */
$sql = "
		SELECT
	        o.order_sn, o.order_id, o.order_time,o.order_status,
	        og.goods_name, og.goods_number, og.customized, og.goods_id, og.style_id, g.is_maintain_warranty, gs.internal_sku, if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) as barcode,
	        boi.order_type, boi.provider_id, p.provider_name, boi.purchaser, o.facility_id, f.facility_name, boi.batch_order_sn
		FROM 
		    {$ecs->table('batch_order_info')} AS boi 
		    LEFT JOIN {$ecs->table('batch_order_mapping')} AS om ON om.batch_order_id = boi.batch_order_id
	        LEFT JOIN {$ecs->table('order_info')} AS o ON o.order_id = om.order_id
	        LEFT JOIN {$ecs->table('order_goods')} AS og ON og.order_id = o.order_id 
	        LEFT JOIN {$ecs->table('provider')} p on p.provider_id = boi.provider_id
	        LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = og.goods_id 
	        LEFT JOIN {$ecs->table('goods_style')} AS gs ON gs.goods_id = og.goods_id AND gs.style_id = og.style_id
	        LEFT JOIN romeo.facility f on f.facility_id = o.facility_id
		WHERE
	        o.order_type_id in ('PURCHASE','PURCHASE_TRANSFER') {$condition} and boi.batch_order_id = {$batch_order_id}
	        and om.is_cancelled = 'N' and om.is_over_c = 'N' and om.is_in_storage = 'N'
	    GROUP BY o.order_id
	    ORDER BY o.order_time DESC, o.order_id
	";
$refs_value_order = $refs_order = array ();
$search_orders = $db->getAllRefBy ( $sql, array ('order_id' ), $refs_value_order, $refs_order, false );

if (! empty ( $search_orders )) {
	$in_order_ids = db_create_in ( $refs_value_order ['order_id'], 'order_id' );

    // 查询每个订单的已入库数
    $sql = "
        SELECT
            og.order_id, ifnull(sum(iid.quantity_on_hand_diff),0) AS in_count
        FROM
            ecs_order_goods og
        LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id 
        WHERE
            ".db_create_in($refs_value_order['order_id'], 'og.order_id')."
        GROUP BY og.order_id
    ";
	$refs_value_count_1 = $refs_count_1 = array ();
	$db->getAllRefBy ( $sql, array ('order_id' ), $refs_value_count_1, $refs_count_1 );

	require_once ('includes/lib_goods.php');
	foreach ( $search_orders as $key => $order ) {
		$in_count = $refs_count_1 ['order_id'] [$order ['order_id']] [0] ['in_count'];
		$search_orders [$key] ['in_count'] = $in_count ? $in_count : 0;
		$not_in_count = $order['goods_number'] - $search_orders [$key] ['in_count'];
		$search_orders [$key] ['not_in_count'] = $not_in_count ? $not_in_count : 0;
		$search_orders [$key] ['facility_name'] = facility_mapping ( $order ['facility_id'] );
		$search_orders [$key] ['goods_item_type'] = get_goods_item_type ( $order ['goods_id'] );
	}
	$smarty->assign ( 'search_orders', $search_orders );
}

if ($csv == "批次订单详细csv") {
	header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "批次采购订单详细" ) . ".csv" );
	$out = $smarty->fetch ( 'oukooext/batch_in_storage_csv.htm' );
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
}
$url = "batch_edit_in_storage.php?batch_order_id={$batch_order_id}";
$smarty->assign('url', $url);
$smarty->assign('statistics', $statistics);
$smarty->assign('sort_method', $sort_method);      			 // 默认显示方式
$smarty->assign('sort_method_list', $sort_method_list);      // 显示方式列表
$smarty->assign('sort_validity', $sort_validity);  // 日期录入列表
$smarty->assign('sort_validity_list', $sort_validity_list);  // 日期录入列表
$smarty->assign('validity_info', $validity_info);  // 日期显示方式
$smarty->assign ( 'back', $_SERVER ['REQUEST_URI'] );
$smarty->assign('batch_order_sn', $order['batch_order_sn']);
$smarty->assign ( 'version', 'V2' );
$smarty->assign ( 'info', $info );
$smarty->assign ( 'printers', get_serial_printers () );
$smarty->assign('info', $info);
$smarty->display ( 'oukooext/batch_edit_in_storage.htm' );

?>