<?php
/**
 * 追回货物
 */
define('IN_ECS', true);
require('includes/init.php');
require('includes/lib_order.php');

require_once('includes/lib_postsale_cache.php');

admin_priv('wl_shipped_cancel');
require_once("function.php");

require_once(ROOT_PATH. 'includes/debug/lib_log.php');
define("BACK", "追回");

$act = $_REQUEST['act'];

// 追回操作
if ($act == BACK) {
	global $db;
	
    $order_id = intval($_REQUEST['order_id']);
    $status = 'YES';

    //添加锁 yjchen
	if (!lock_acquire('shipped_cancel_'.$order_id)) {
		sys_msg("对不起，该订单正在执行追回操作，请稍后再试！", 1);
		exit;  
	}
    
    if ($order_id > 0 && $status != null) {
    	// 检查订单追回状态
        $sql = "SELECT is_back FROM {$ecs->table('order_info')} WHERE order_id = '{$order_id}' ";
        $order_back_status = $db->getOne($sql);
        $sqls[] = $sql;
        if ($order_back_status == 'YES') {
            die("该订单已退回");
        }
        
        // 取消订单->追回货物，生成-t订单
        $t_order_id = generate_return_all_back_order($order_id, 'cancel');
        if($t_order_id > 0){
        	// 更新订单追回状态
	        $sql = "UPDATE {$ecs->table('order_info')} SET is_back = '{$status}', shipping_status = 11
	            WHERE order_id = '{$order_id}' ";
	        $sqls[] = $sql;
	        if(!$db->query($sql)){
	        	sys_msg("对不起，操作失败！请联系ERP组", 1);
	        }
	        
	        // 更新订单物流时间
	        $sql = "SELECT shipping_time FROM {$ecs->table('order_info')} WHERE order_id = '{$order_id}' ";
	        $shipping_time = $db->getOne($sql);
	        $sqls[] = $sql;
	        if(!$shipping_time) {
	            $sql = "UPDATE {$ecs->table('order_info')} SET shipping_time = UNIX_TIMESTAMP() - 1 WHERE order_id = '{$order_id}' LIMIT 1 ";
	            $sqls[] = $sql;
	            if(!$db->query($sql)){
	        		sys_msg("对不起，操作失败！请联系ERP组", 1);
	            }
	        }
	        
	        $order_info = getOrderInfo($order_id);
	        $order_action_status['order_id'] = $order_id;
	        $order_action_status['order_status'] = $order_info['order_status'];
	        $order_action_status['pay_status'] = $order_info['pay_status'];
	        $order_action_status['shipping_status'] = $order_info['shipping_status'];
	        $order_action_status['invoice_status'] = $order_info['invoice_status'];
	        orderActionLog($order_action_status);
        	
            //SINRI UPDATE POSTSALE CACHe
            POSTSALE_CACHE_updateMessages(null,180,$order_id);

        	header("Location:shipped_cancel.php?order_id={$order_id}");
        }else{
        	$order_info = getOrderInfo($order_id);
	        $order_action_status['order_id'] = $order_id;
	        $order_action_status['order_status'] = $order_info['order_status'];
	        $order_action_status['pay_status'] = $order_info['pay_status'];
	        $order_action_status['shipping_status'] = $order_info['shipping_status'];
	        $order_action_status['invoice_status'] = $order_info['invoice_status'];
	        orderActionLog($order_action_status);
        	
        	sys_msg("对不起，操作失败！请联系ERP组", 1);
        }
    }
}

if($act == 'search'){
    $size = $_REQUEST['size'] ? $_REQUEST['size'] : 10;
    $page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
    $start = ($page - 1) * $size;

    if ($csv == null) {
        $limit = "LIMIT $size";
        $offset = "OFFSET $start";
    }

    $condition = get_condition();

    //在消灭ECB的行动中被改造 20151202 邪恶的大鲵
    $sql = "SELECT o.*,
            s.tracking_number bill_no,
            s.carrier_id
        FROM ecshop.ecs_order_info o use index(PRIMARY,order_info_multi_index,order_sn)
        -- LEFT JOIN {$ecs->table('carrier_bill')} cb ON o.carrier_bill_id = cb.bill_id
        LEFT JOIN romeo.order_shipment os ON os.order_id = CONVERT (o.order_id USING utf8)
        LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
        WHERE
            o.order_status = 2
        AND s. STATUS != 'SHIPMENT_CANCELLED'
            {$condition}
        GROUP BY o.order_id
        ORDER BY o.confirm_time DESC
        $limit $offset
    ";//die($sql);

    // sqlc should be the same with sql, add left join by xyang replaced by Sinri
    $sqlc = "SELECT COUNT(*) FROM {$ecs->table('order_info')} o use index(PRIMARY,order_info_multi_index,order_sn)
        -- LEFT JOIN {$ecs->table('carrier_bill')} cb ON o.carrier_bill_id = cb.bill_id
        LEFT JOIN romeo.order_shipment os ON os.order_id = CONVERT (o.order_id USING utf8)
        LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
        WHERE
            o.order_status = 2
        AND s. STATUS != 'SHIPMENT_CANCELLED'
            {$condition}
        GROUP BY o.order_id
    ";

    $orders = $db->getAll($sql);
    $count = $db->getOne($sqlc);
    $find = false;
    $search_text1 = trim($_REQUEST['search_text1']);
    if ($search_text1 != '') {
        $find = true;
        $count = 0;
    }

    $payments = getPayments();

    $order_filtered = array();
    foreach ($orders as $key => $order) {
        $sql = "
            SELECT og.*, ogsi.shipping_invoice
            FROM ecshop.ecs_order_goods og
            LEFT JOIN romeo.order_shipping_invoice ogsi ON ogsi.order_id = og.order_id
            WHERE og.order_id = '{$order['order_id']}'";
        $goods_list = $db->getAll($sql);
    	
    	foreach($goods_list as $goods_key => $goods){
    		$sql = "SELECT ii.INVENTORY_ITEM_ACCT_TYPE_ID FROM romeo.inventory_item_detail iid
    				INNER JOIN romeo.inventory_item ii ON ii.inventory_item_id = iid.inventory_item_id
    				WHERE iid.order_goods_id = '{$goods['rec_id']}' LIMIT 1";
    		$order_type = $db->getOne($sql);
    		$goods_list[$goods_key]['order_type'] = $order_type;
    	}
    	
    	$orders[$key]['goods_list'] = $goods_list;
    	
    	// 判断是否为COD(货到付款)订单
        $orders[$key]['is_cod'] = $payments[$order['pay_id']]['pay_code'] == 'cod' ? true : false;
    	
    	if ($search_text1 != '') {
            $goods_found = false;
            foreach ($goods_list as $goods) {
                if (strpos($goods['goods_name'], $search_text1) !== false ||
                	strpos($goods['shipping_invoice'], $search_text1) !== false){
                    $goods_found = true;
                }
            }
            if ($goods_found) {
                $order_filtered[] = $orders[$key];
                $count ++;
            }
        }
    }
    if ($find) {$orders = $order_filtered;}
    $pager = Pager($count, $size, $page);
}
$smarty->assign('carriers', getCarriers());
$smarty->assign('orders', $orders);
$smarty->assign('order_time',empty($_REQUEST['order_time'])?(empty($_REQUEST['search_text'])&&empty($_REQUEST['search_text1'])?date('Y-m-d',strtotime("-10 days",time())):''):$_REQUEST['order_time']);
$smarty->assign('pager', $pager);
$smarty->assign('facility_name', facility_mapping($_SESSION['facility_id']));
$smarty->display("oukooext/shipped_cancel.htm");


function get_condition() {
    global $db, $ecs;
    $act = $_REQUEST['act'];
    $order_time = $_REQUEST['order_time'];
    $is_back = trim($_REQUEST['is_back']);
    $order_id = trim($_REQUEST['order_id']);
    $search_text = trim($_REQUEST['search_text']);
    $search_text1 = trim($_REQUEST['search_text1']);
    
    $condition .= " AND (o.shipping_status = 12 OR o.shipping_status = 8 OR o.shipping_status = 9 OR o.shipping_status = 10 OR o.shipping_status = 1 OR o.shipping_status = 11)";
    
    if ($is_back == 'YES'){
    	$condition .= " AND o.is_back = '{$is_back}' AND shipping_status = 11";
    }
    else if($is_back == 'NONE'){
    	$condition .= " AND o.is_back = '{$is_back}'";
    }
    if (strtotime($order_time) > 0) {
        $condition .= " AND o.order_time  >= '{$order_time}'";
        $end_order_time = strftime('%Y-%m-%d', strtotime("+10 day", strtotime($order_time)));
        $condition .= " AND o.order_time  < '$end_order_time'";
    }elseif(empty($order_id) && empty($search_text) && empty($search_text1)){
    	$start_time = date("Y-m-d",strtotime("-10 days",time()));
    	$condition .= " AND o.order_time >= '{$start_time}'";
    }
    
    if ($act != "search" && empty($order_id)) {
        $condition .= " AND o.is_back = 'NONE'";
    }
    
    if(!empty($order_id)){
    	$condition .= " AND o.order_id = {$order_id}";
    }

    if ($search_text != '') { 
        $condition .= " AND o.order_sn LIKE '{$search_text}%' ";
    }
    
    if ($search_text1 != '') { 
        $condition .= "
        AND EXISTS (
            SELECT 1 FROM 
            ecshop.ecs_order_goods og
            LEFT JOIN romeo.order_shipping_invoice ogsi ON og.order_id = ogsi.order_id
            WHERE og.order_id = o.order_id 
            AND (og.goods_name LIKE '{$search_text1}%' OR ogsi.shipping_invoice LIKE '{$search_text1}%')
        )";
    }
    

	# 添加party条件判断 2009/08/07 yxiang
	$condition .= ' AND '. party_sql('o.party_id') . " AND ".facility_sql("o.facility_id");
	
    return $condition;
}

?>