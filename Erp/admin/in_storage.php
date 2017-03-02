<?php
define('IN_ECS', true);
require('includes/init.php');
require("function.php");


// 通用商品组织权限特殊判断 ljzhou 2013.07.03
if(check_goods_common_party()) {
	admin_priv('ck_in_storage_common');
} else {
	admin_priv('ck_in_storage', 'wl_in_storage');
}

// 判断入库模式
check_in_storage_mode(1);
 
 
// 导出csv的权限
$csv = $_REQUEST['csv'];
if ($csv) { admin_priv("admin_other_csv"); }

// 消息
$info = $_REQUEST['info'];

if (trim($_REQUEST['act']) == 'today') {
    $_REQUEST['label'] = 'common_in_storage';
} 
// 增加批量入库功能 ljzhou 2012.11.27
$label = $_REQUEST['label'];
$labels = array (
'common_in_storage' => '普通入库',
'batch_in_storage' => '批量入库'
);

if ($label == 'common_in_storage') {
	// 当提交搜索时,将搜索请求保存在session中
	if ( ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['act']=='search') || $csv)
	{
	    $_SESSION['in_storage'] = array
	    (
	        'cagetory_name'  => $_REQUEST['cagetory_name'],
	        'goods_cagetory' => !empty($_REQUEST['cagetory_name']) ? $_REQUEST['goods_cagetory'] : null,  // 商品分类
	        'order_sn'       => $_REQUEST['order_sn'],                                                    // 采购单号
	        'goods_name'     => $_REQUEST['goods_name'],                                                  // 商品名
	        'goods_barcode'  => mysql_real_escape_string(trim($_REQUEST['goods_barcode'])),                    // 商品条码
	        'provider_name'  => $_REQUEST['provider_name'],
	        'provider_id'    => !empty($_REQUEST['provider_name']) ? $_REQUEST['provider_id'] : null,     // 供应商
	        'in_time_start'  => $_REQUEST['in_time_start'],                                               // 订购单开始时间
	        'in_time_end'    => $_REQUEST['in_time_end']                                                  // 订购单结束时间
	    );
	}
	elseif (isset($_REQUEST['act']) && $_REQUEST['act'] == 'today')
	{
		$_SESSION['in_storage']['in_time_start'] = date('Y-m-d');
	    $_SESSION['in_storage']['in_time_end'] = date('Y-m-d');
	}
	
	/**
	 * 搜索列表
	 */
	$condition = trim(get_condition());
	if (!$condition) sys_msg('请选择收货入库查询条件');
	$sql = "
		SELECT
	        o.order_sn, o.order_id, o.order_time,o.order_status,p.name as party_name,pm.is_over_c,pm.is_in_storage,pm.is_cancelled,
	        og.goods_name, og.goods_number, og.customized, og.goods_id, og.style_id, gs.internal_sku, if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) as barcode,
	        bo.order_type, bo.provider_id, bo.purchaser, o.facility_id, ep.provider_name
		FROM 
	        {$ecs->table('order_info')} AS o use index ( order_info_multi_index)
	        LEFT JOIN {$ecs->table('order_goods')} AS og ON og.order_id = o.order_id 
	        LEFT JOIN ecshop.ecs_batch_order_mapping pm ON o.order_id = pm.order_id
	        LEFT JOIN ecshop.ecs_batch_order_info bo ON pm.batch_order_id = bo.batch_order_id
	        LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = og.goods_id 
	        LEFT JOIN {$ecs->table('goods_style')} AS gs ON gs.goods_id = og.goods_id AND gs.style_id = og.style_id and gs.is_delete=0
	        LEFT JOIN romeo.party p ON convert(o.party_id using utf8) = p.party_id
	        LEFT JOIN ecshop.ecs_provider ep ON bo.provider_id = ep.provider_id
		WHERE
	        o.order_type_id in( 'PURCHASE','PURCHASE_TRANSFER') {$condition}
	    GROUP BY o.order_id
	    ORDER BY o.order_time DESC, o.order_id
	";
	$refs_value_order = $refs_order = array();
	$search_orders = $db->getAllRefBy($sql, array('order_id'), $refs_value_order, $refs_order, false);
	
	if (!empty($search_orders)) {
	    
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
	    $refs_value_count_1 = $refs_count_1 = array();
	    $db->getAllRefBy($sql, array('order_id'), $refs_value_count_1, $refs_count_1);
	
	    foreach ($search_orders as $key => $order) {
	        $in_count = $refs_count_1['order_id'][$order['order_id']][0]['in_count'];
	        $search_orders[$key]['in_count'] = $in_count ? $in_count : 0;
	        $search_orders[$key]['facility_name'] = facility_mapping($order['facility_id']);
	    }
	}
	$smarty->assign('search_orders', $search_orders);	
}

else if ($label == 'batch_in_storage') {
	// 当提交搜索时,将搜索请求保存在session中
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['act']=='search')
	{
	    $_SESSION['batch_in_storage'] = array
	    (
	        'batch_order_sn' => $_REQUEST['batch_order_sn'],                                              // 批次采购单号
	        'batch_in_time'  => $_REQUEST['batch_in_time']                                                // 订购单时间
	    );
	}
	else
	{
	    $_SESSION['batch_in_storage']['batch_in_time'] = date('Y-m-d');
	}
	
	/**
	 * 搜索列表
	 */
	$condition = trim(get_batch_condition());
	if (!$condition) sys_msg('请选择收货入库查询条件');
	$sql = "
		SELECT
	        o.*
		FROM 
	        {$ecs->table('batch_order_info')} AS o
		WHERE
	        true {$condition}
	    ORDER BY o.order_time DESC, o.batch_order_id
	";
	$refs_value_order = $refs_order = array();
	$search_orders = $db->getAllRefBy($sql, array('batch_order_id'), $refs_value_order, $refs_order, false);
	
	if (!empty($search_orders)) {
	    $in_order_ids = db_create_in($refs_value_order['batch_order_id'], 'batch_order_id');
	    // 查询订单是否已被取消
	    $sql = "
		SELECT
	        o.batch_order_id, count(o.batch_order_id) AS count
		FROM 
	        {$ecs->table('batch_order_info')} AS o
		WHERE
	        $in_order_ids and o.is_cancelled = 'Y'
	    GROUP BY o.batch_order_id
	    ";
	    $refs_value_count = $refs_count = array();
	    $db->getAllRefBy($sql, array('batch_order_id'), $refs_value_count, $refs_count);
	    
	    foreach ($search_orders as $key => $order) {
	        $search_orders[$key]['canceled'] = $refs_count['batch_order_id'][$order['batch_order_id']][0]['count'];
	        $search_orders[$key]['party_name'] = party_mapping($order['party_id']);
	        $search_orders[$key]['facility_name'] = facility_mapping($order['facility_id']);
	    }
	}
	$smarty->assign('search_orders', $search_orders);
} 

if ($csv == "采购入库订单详情csv") {
	header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "采购入库订单详情" ) . ".csv" );
	$out = $smarty->fetch ( 'oukooext/in_storage_csv.htm' );
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
}

// 取组织仓库权限和人仓库权限的交集 ljzhou 2013-04-20
$smarty->assign ( 'facility_id_list', array ('0' => '未指定仓库' ) + array_intersect_assoc(get_available_facility(),get_user_facility()) );
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('labels', $labels);
$smarty->assign('facility_name', facility_mapping($_SESSION['facility_id']));
$smarty->assign('version', 'V2');
$smarty->assign('info', $info);
$smarty->display('oukooext/in_storage.htm');

/**
 * 根据session中的信息构造查询条件
 */
function get_condition()
{
    $condition = "";

    // 从session 中获取检索条件
    $order_sn       = trim($_SESSION['in_storage']['order_sn']);
    $goods_name     = trim($_SESSION['in_storage']['goods_name']);
    $goods_barcode  = trim($_SESSION['in_storage']['goods_barcode']);
    $goods_cagetory = trim($_SESSION['in_storage']['goods_cagetory']);
    $provider_id    = trim($_SESSION['in_storage']['provider_id']);
    $in_time_start  = $_SESSION['in_storage']['in_time_start'];
    $in_time_end    = $_SESSION['in_storage']['in_time_end'];
    $facility_id    = $_REQUEST['facility_id'];
    
    if ($order_sn != '')
    {
        $condition .= " AND order_sn LIKE '%{$order_sn}%'";
    }
    if ($goods_name != '')
    {
        $condition .= " AND og.goods_name LIKE '%{$goods_name}%'";
    }
    if ($goods_barcode != '')
    {
        $condition .= " AND if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) = '{$goods_barcode}'";
    }
    if ($is_new != '' && $is_new != -1)
    {
        $condition .= " AND e.is_new = '{$is_new}'";
    }
    if ($provider_id != 0)
    {
        $condition .= " AND e.provider_id = '{$provider_id}'";
    }
    if ($facility_id !=0)
    {
        $condition .= " AND o.facility_id = '{$facility_id}'";
    }

    // 商品分类
    if ($goods_cagetory)
    {
        switch ($goods_cagetory)
        {
            case 'mobile':
                $condition .= " AND g.top_cat_id = '1' ";
                break;
            case 'fittings':
                $condition .= " AND g.top_cat_id = '597' ";
                break;
            case 'dvd':
                $condition .= " AND g.cat_id = '1157' ";
                break;
            case 'education':
                $condition .= " AND g.top_cat_id = '1458' ";
                break;
            case 'other':
                $condition .= " AND g.top_cat_id NOT IN (1, 597, 1109, 1458) AND g.cat_id != '1157'";
                break;
        }
    }

    // 指定哪一天的
    if ( ($in_time_start && strtotime($in_time_start) !== false) && ($in_time_end && strtotime($in_time_end) !== false ))
    {
        $start = $in_time_start;
        $end = date('Y-m-d', strtotime("+1 day", strtotime($in_time_end)));
        $condition .= " AND (o.order_time > '{$start}' AND o.order_time < '{$end}') ";
    }

    # 添加party条件判断 2009/08/06 yxiang
    // 加上facility限制 2009-10-21 zwsun
    $condition .= ' AND '. party_sql('o.party_id')." AND ".facility_sql("o.facility_id");
    
    return $condition;
}


/**
 * 根据session中的信息构造查询条件
 */
function get_batch_condition()
{
    $condition = "";

    // 从session 中获取检索条件
    $batch_order_sn = trim($_SESSION['batch_in_storage']['batch_order_sn']);
    $batch_in_time  = $_SESSION['batch_in_storage']['batch_in_time'];

    if ($batch_order_sn != '')
    {
        $condition .= " AND batch_order_sn LIKE '%{$batch_order_sn}%'";
    }

    // 指定哪一天的
    if ($batch_in_time && strtotime($batch_in_time) !== false)
    {
        $start = $batch_in_time;
        $end = date('Y-m-d', strtotime("+1 day", strtotime($start)));
        $condition .= " AND (order_time > '{$start}' AND order_time < '{$end}') ";
    }

    $condition .= ' AND '. party_sql('o.party_id')." AND ".facility_sql("o.facility_id");
    
    return $condition;
}

?>