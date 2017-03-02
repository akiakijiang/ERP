<?php
/**
 * 拒收收货
 */
define('IN_ECS', true);

require('includes/init.php');

admin_priv('cg_th_order');
require("function.php");
//require_once('../includes/debug/lib_log.php');

$csv = $_REQUEST['csv'];

$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

if ($csv == null) {
    $limit = "LIMIT $size";
    $offset = "OFFSET $start";
}

//拒收操作入库
if ($_REQUEST['act'] == 'reject_in') {
    $lock_name = 'reject_in';
    require_once('includes/lib_filelock.php');
    if (!wait_file_lock($lock_name, 10)) {
        die('操作超时，请重试，请核实是否有人也在进行拒收入库操作。如长时间出现该界面，请联系erp组');
    }
    create_file_lock($lock_name);
    $order_id = $_REQUEST['order_id'];
    require_once('includes/lib_order.php');
    $back = $_REQUEST['back'] !== null ? $_REQUEST['back'] : "/";
    $goodsType = $_REQUEST['goodsType'];
    
	// 拒收入库，生成-t订单
    $t_order_id = generate_return_all_back_order($order_id, 'reject', $goodsType);
    
    if($t_order_id > 0){
    	release_file_lock($lock_name);
    	header("location:$back");
    }else{
    	release_file_lock($lock_name);
    	sys_msg("对不起，操作失败！请联系ERP组", 1);
    }
    
    exit();
}

$condition = getCondition();

$sql = "SELECT o.*, u.user_name
       FROM {$ecs->table('order_info')} o use index(order_info_multi_index,order_sn)
       LEFT JOIN {$ecs->table('users')} u ON o.user_id = u.user_id
       WHERE (o.order_status = '4' OR o.shipping_status = '3')
       {$condition}
       ORDER BY o.order_id DESC
  	   $limit $offset
";

$sqlc = "SELECT COUNT(*)
       FROM {$ecs->table('order_info')} o use index(order_info_multi_index,order_sn)
       -- LEFT JOIN {$ecs->table('users')} u ON o.user_id = u.user_id
       WHERE (o.order_status = '4' OR o.shipping_status = '3')
       {$condition}
";

//Qlog::log($sql);
//Qlog::log($sqlc);
$orders = $db->getAllRefby($sql, array('order_id'), $order_ids, $orders_ref);
$count = $db->getOne($sqlc);


$pager = Pager($count, $size, $page, remove_param_in_url($_SERVER['REQUEST_URI'], 'info'));


$sql = "SELECT og.*, IF(gs.barcode IS NULL, g.barcode, gs.barcode) AS barcode, ogsi.shipping_invoice
		FROM ecshop.ecs_order_goods og
			LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id AND og.style_id = gs.style_id and gs.is_delete=0
			LEFT JOIN ecshop.ecs_goods g ON g.goods_id = og.goods_id
			LEFT JOIN romeo.order_shipping_invoice ogsi ON og.order_id = ogsi.order_id
        WHERE ". db_create_in($order_ids['order_id'], 'og.order_id');
$all_goods_list = $db->getAllRefby($sql, array('order_id'), $order_goods_order_ids, $order_goods_ref);
$payments = getPayments();

foreach ($orders as $key => $order) {
    $goods_list = $order_goods_ref['order_id'][$order['order_id']];
	
	// 检查每一件商品是否串号控制
    require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
	foreach($goods_list as $goods_key => $goods){
    	$is_serial = getInventoryItemType($goods['goods_id']);
    	if($is_serial == 'SERIALIZED'){
    		$goods_list[$goods_key]['is_serial'] = true;
    		$sql = "SELECT ii.serial_number
    				FROM romeo.inventory_item_detail iid
    				INNER JOIN romeo.inventory_item ii ON ii.inventory_item_id = iid.inventory_item_id
    				WHERE iid.order_goods_id = '{$goods['rec_id']}'";
    		$goods_list[$goods_key]['serial_numbers'] = $db->getCol($sql);
    	}else{
    		$goods_list[$goods_key]['is_serial'] = false;
    		for($i=0;$i<$goods['goods_number'];$i++){
    			$goods_list[$goods_key]['serial_numbers'][] = $goods['barcode'];
    		}
    	}
    }
	
    // 判断是否为COD(货到付款)订单
    $orders[$key]['is_cod'] = $payments[$order['pay_id']]['pay_code'] == 'cod' ? true : false;
    
    // 判断是否还可以退货入库
    $sql = "SELECT orl.order_sn, orl.order_id
    		FROM ecshop.order_relation orl
    			INNER JOIN ecshop.ecs_order_goods og ON og.order_id = orl.order_id
    			INNER JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = convert(og.rec_id using utf8)
    			INNER JOIN romeo.inventory_transaction it ON iid.inventory_transaction_id = it.inventory_transaction_id 
    				AND it.inventory_transaction_type_id IN ('ITT_SO_RET','ITT_SO_CANCEL','ITT_SO_REJECT')
    		WHERE orl.parent_order_id = '{$order['order_id']}'";
    $in_condition = $db->getAll($sql);
    if(!empty($in_condition)){
    	$orders[$key]['can_return'] = false;
    	$back_orders = array_unique($in_condition);
    	$orders[$key]['back_orders'] = $back_orders;
    }else{
    	$orders[$key]['can_return'] = true;
    }
    
    // 商品列表
    $orders[$key]['goods_list'] = $goods_list;
}

$smarty->assign('facility_name', facility_mapping($_SESSION['facility_id']));
$smarty->assign('orders', $orders);
$smarty->assign('pager', $pager);
$smarty->assign('back', remove_param_in_url($_SERVER['REQUEST_URI'], 'info'));
$smarty->assign('available_facility', array_intersect_assoc(get_available_facility(),get_user_facility()));
$smarty->display('oukooext/rejected_order.htm');

function getCondition() {
    global $ecs;

    $start_time = $_REQUEST['start'];
    $end_time = $_REQUEST['end'];
    $search_text = trim($_REQUEST['search_text']);
    $search_type = trim($_REQUEST['search_type']);
    $available_facility = $_REQUEST['available_facility'];

    $act = $_REQUEST['act'];

    $condition = '';

    if (strtotime($start_time) > 0) {
        $condition .= " AND o.order_time >= '$start_time'";
    }else{
    	$start_time = date('Y-m-d', strtotime('-10 day', time()));
    	$condition .= " AND o.order_time >= '$start_time'";
    	$_REQUEST['start']= $start_time;
    }
    if (strtotime($end_time) > 0) {
        $end_time = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end_time)));
        $condition .= " AND o.order_time <= '$end_time'";
    }
    
    if ($search_text != '') {
        if (in_array($search_type, array('order_sn', 'consignee'))) {
            $condition .= " AND  $search_type = '$search_text' ";
        }
        
        if ($search_type == 'bill_no') {
        	global $db;
        	// $sql="select order_sn 
        	// 	from ecshop.ecs_order_info o 
        	// 	inner join ecshop.ecs_carrier_bill c on o.carrier_bill_id = c.bill_id
        	// 	where c.bill_no = '$search_text'";
            // ECB 团灭 20151203 邪恶的大鲵
            $sql="SELECT
                    oi.order_sn
                FROM
                    romeo.shipment s
                INNER JOIN romeo.order_shipment os ON os.SHIPMENT_ID = s.SHIPMENT_ID
                INNER JOIN ecshop.ecs_order_info oi ON oi.order_id = cast(os.order_id AS UNSIGNED)
                WHERE
                    s.TRACKING_NUMBER = '$search_text'
                AND s.`STATUS` != 'SHIPMENT_CANCELLED'
                LIMIT 1
            ";

        	$order_sn = $db->getOne($sql);
        	
            $condition .= " AND  order_sn = '{$order_sn}' ";
        }
    } 

    # 添加party条件判断 2009/08/06 yxiang
	$condition .= ' AND '. party_sql('o.party_id') 
	                             ." AND " . facility_sql("o.facility_id");
                            
    //仓库 2012/5/8 mjzhou
    if ($available_facility != -1 && $available_facility != '' ) {
        $condition .= " AND o.facility_id = '{$available_facility}' ";
    }	                             

    return $condition;
}

