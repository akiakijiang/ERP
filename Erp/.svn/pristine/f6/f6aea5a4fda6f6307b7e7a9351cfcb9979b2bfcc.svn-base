<?php
define('IN_ECS', true);

require('includes/init.php');
include_once 'function.php';
require_once(ROOT_PATH."RomeoApi/lib_inventory.php");
admin_priv('kf_order_reply');

$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

$limit = "LIMIT $size";
$offset = "OFFSET $start";

// 取得符合条件的订单
$condition = getCondition();

// 订单总数
$sqlc = "
    SELECT COUNT(DISTINCT o.order_id)
    FROM {$ecs->table('order_info')} o 
    INNER JOIN {$ecs->table('order_comment')} c  ON c.order_id = o.order_id
    INNER JOIN {$ecs->table('users')} u ON o.user_id = u.user_id
    INNER JOIN {$ecs->table('carrier_bill')} cb ON o.carrier_bill_id = cb.bill_id
    WHERE 1
        {$condition}
";
$count = $db->getOne($sqlc);
// 构造分页
$pager = Pager($count, $size, $page);

// 查询订单信息
$sql = "
	SELECT cb.carrier_id, cb.bill_no, cb.phone_no, u.user_name, o.*
	FROM {$ecs->table('order_info')} o 
	INNER JOIN {$ecs->table('order_comment')} c  ON c.order_id = o.order_id
	INNER JOIN {$ecs->table('users')} u ON o.user_id = u.user_id
	INNER JOIN {$ecs->table('carrier_bill')} cb ON o.carrier_bill_id = cb.bill_id
	WHERE 1
		{$condition}
    GROUP BY c.order_id
	ORDER BY c.post_datetime DESC $limit $offset
";

// 定义订单的关联查询
$links = array(
    // 订单的留言
    array(
        'sql' => "SELECT * FROM {$ecs->table('order_comment')} c WHERE :in ORDER BY reply, order_comment_id DESC",
        'source_key' => 'order_id',
        'target_key' => 'order_id',
        'mapping_name' => 'comments',
        'type' => 'HAS_MANY',
    ),
    // 订单的商品
    array(
        'sql' => "SELECT * FROM {$ecs->table('order_goods')} WHERE :in",
        'source_key' => 'order_id',
        'target_key' => 'order_id',
        'mapping_name' => 'goods_list',
        'type' => 'HAS_MANY',
    ),
    // 订单备注
    array(
        'sql' => "SELECT * FROM {$ecs->table('order_action')} WHERE :in AND action_note != '' ORDER BY action_time",
        'source_key' => 'order_id',
        'target_key' => 'order_id',
        'mapping_name' => 'action_notes',
        'type' => 'HAS_MANY',
    ),
);
$orders = $db->findAll($sql, $links);

$storage_list = getStorage();
foreach ($orders as $key => $order) {
	// 读取确认时间
	$sql = "
		SELECT * 
		FROM {$ecs->table('order_action')} 
		WHERE 
			order_id = '{$order['order_id']}' AND order_status = 1
		LIMIT 1
	";
	$action = $db->getRow($sql);
	$orders[$key]['confirm_time'] = $action['action_time'];
	$orders[$key]['confirm_user'] = $action['action_user'];
	
	// 读取支付时间
	$sql = "
		SELECT action_time , action_user
		FROM {$ecs->table('order_action')} 
		WHERE 
			order_id = '{$order['order_id']}' AND pay_status = 2
		LIMIT 1
	";
	$action = $db->getRow($sql);
	$orders[$key]['pay_time'] = $action['action_time'];
	$orders[$key]['pay_user'] = $action['action_user'];

	// 读取出库时间
	$sql = "
		SELECT * FROM {$ecs->table('order_action')} 
		WHERE
			order_id = '{$order['order_id']}' AND shipping_status = 8
	";
	$action = $db->getRow($sql);
	$orders[$key]['out_time'] = $action['action_time'];
	$orders[$key]['out_user'] = $action['action_user'];
	
	// 获取发货时间
	$sql = "
		SELECT * FROM {$ecs->table('order_action')} 
		WHERE 
		  order_id = '{$order['order_id']}' AND shipping_status = 1 LIMIT 1
	";
	$action = $db->getRow($sql);
	$orders[$key]['shipping_time'] = $action['action_time'];
	$orders[$key]['shipping_user'] = $action['action_user'];	

	// 取得商品的库存
	foreach ($orders[$key]['goods_list'] as $goods_key => $goods) {
		//采用新系统的库存
      	$orders[$key]['goods_list'][$goods_key]['storage_count'] = 
            isset($storage_list["{$goods['goods_id']}_{$goods['style_id']}"]['qohTotal'])
            ? $storage_list["{$goods['goods_id']}_{$goods['style_id']}"]['qohTotal']
            : 0 ;
	}
	
	//读取用户填写支付帐号的记录
	$sql = "SELECT * FROM {$ecs->table('order_payment_bank')} WHERE order_id = '{$order['order_id']}'";
	$orders[$key]['payment_bank'] = $db->getRow($sql);
}

$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('orders', $orders);
$smarty->assign('count', $count);
$smarty->assign('pager', $pager);

$smarty->display('oukooext/order_reply.htm');
?>

<?php
function getCondition() {
	$order_sn = $_REQUEST['order_sn'];
	$search_text = trim($_REQUEST['search_text']);
	$comment_cat = $_REQUEST['comment_cat'];	
	$is_replied = $_REQUEST['is_replied'];	
	$start = $_REQUEST['start'];	
	$end = $_REQUEST['end'];
	
	$condition = '';
	if ($order_sn != '') {
		$condition .= " AND o.order_sn = '$order_sn'";		
	}
	if ($search_text != '') {
		$condition .= " AND (o.order_sn LIKE '%$search_text%' OR c.replied_by LIKE '%$search_text%' OR o.consignee LIKE '%$search_text%' OR u.user_name LIKE '%$search_text%')";
	}
	
	if ($comment_cat !== null && $comment_cat != -1) {
		$condition .= " AND c.comment_cat = '$comment_cat'";		
	}
	if ($is_replied != -1) {
		switch ($is_replied) {
			case 1:
			case null:
				$condition .= " AND (c.replied_by = '' OR c.replied_by IS NULL)";	
				break;
			case 2:
				$condition .= " AND c.replied_by != '' AND NOT c.replied_by IS NULL";	
				break;				
			default:
				break;
		}
	}

	if (strtotime($start) > 0) {
		$condition .= " AND c.post_datetime >= '$start'";
	}
	if (strtotime($end) > 0) {
		$end = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end)));
		$condition .= " AND c.post_datetime <= '$end'";
	}
	
	# 添加party条件判断 2009/08/06 yxiang
	$condition .= " AND ". party_sql('o.party_id');
	 
	return $condition;
}

?>