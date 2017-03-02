<?php
include_once("analyze_detail_header.php");

switch ($type) {
	case 'user_count':
		$condition = get_condition();
		$columns = array(
			'user_name' => '用户名',
			'reg_time' => '注册时间',
			'order' => '订单',
		);
		$style = array(
			"user_name" => "style='width:50px;'",
			"reg_time" => "style='width:80px;'",
			"order" => "style='width:500px;padding:0px'",
		);		
		$sql = "
			SELECT * 
			FROM {$ecs->table('users')} u
			WHERE
				1
				AND reg_time > UNIX_TIMESTAMP('$start_date') 
				AND reg_time < UNIX_TIMESTAMP(DATE_ADD('$end_date',INTERVAL 1 DAY)) 
				{$condition['where']}
			ORDER BY reg_time DESC
			$limit $offset
		";
		$sqlc = "
			SELECT COUNT(*)
			FROM {$ecs->table('users')} u
			WHERE
				1
				AND reg_time > UNIX_TIMESTAMP('$start_date') 
				AND reg_time < UNIX_TIMESTAMP(DATE_ADD('$end_date',INTERVAL 1 DAY)) 
				{$condition['where']}
		";
		$title .= "注册用户({$start_date}至{$end_date})";
		break;
		
	default:
		die("非法参数");
}
include_once("analyze_detail_footer.php");
?>
<?php
function data_handle($all_data) {
	global $type, $db, $ecs, $start_date, $end_date, $condition;
	if (!is_array($all_data)) return;
	foreach ($all_data as $key => $data) {
		if ($type == 'user_count') {
			// 查询订单
			$sql = "
				SELECT *, GROUP_CONCAT(og.goods_name SEPARATOR '<br>') goods_name 
				FROM {$ecs->table('order_info')} o 
				LEFT JOIN {$ecs->table('order_goods')} og ON og.order_id = o.order_id
				WHERE 
					user_id = '{$data['user_id']}'
					AND UNIX_TIMESTAMP(order_time) > UNIX_TIMESTAMP('$start_date')
					AND UNIX_TIMESTAMP(order_time) < UNIX_TIMESTAMP(DATE_ADD('$end_date',INTERVAL 1 DAY))
				GROUP BY order_sn	
				ORDER BY order_time DESC		
			";
			
			$orders = $db->getAll($sql);
			
			$all_data[$key]['order'] = "<table class='bWindow'>";
			foreach ($orders as $order) {
				$all_data[$key]['order'] .= "<tr align='center'>";
				$all_data[$key]['order'] .= "<td style='width:120px'>";
				$all_data[$key]['order'] .= "<a href='detail_info.php?order_id={$order['order_id']}' target='_blank'>{$order['order_sn']}</a> " . get_order_status($order['order_status']) . "<br>{$order['order_time']}";
				$all_data[$key]['order'] .= "</td>";
				$all_data[$key]['order'] .= "<td style='width:150px'>";
				$all_data[$key]['order'] .= "{$order['shipping_name']}";
				$all_data[$key]['order'] .= "</td>";				
				$all_data[$key]['order'] .= "<td>";
				$all_data[$key]['order'] .= "{$order['goods_name']}";
				$all_data[$key]['order'] .= "</td>";
				$all_data[$key]['order'] .= "<td style='width:80px'>";
				$all_data[$key]['order'] .= get_order_status($order['order_status']) . "<br>" . get_shipping_status($order['shipping_status']) . "<br>" . get_pay_status($order['pay_status']);				
				$all_data[$key]['order'] .= "</td>";
				$all_data[$key]['order'] .= "</tr>";				
			}
			$all_data[$key]['order'] .= "</table>";
			
			$order = $db->getRow($sql);
			if ($order != null) {
				$all_data[$key] = array_merge($all_data[$key], $order);
			}
		}
		
		// 处理注册时间
		if ($data['reg_time'] !== null) {
			$all_data[$key]['reg_time'] = date("Y-m-d H:i:s", $data['reg_time']);
		}
		
	}
	return $all_data;
}

function get_condition() {
	global $title, $ecs, $columns;
	
	$order_status = get_param_array("order_status");
	if ($order_status !== null) {
		if ($order_status[0] == 'exist') {
			$title .= "下单 ";
			$where_condition .= "
				AND EXISTS(
					SELECT 1 FROM {$ecs->table('order_info')} o 
					WHERE 
						o.user_id = u.user_id 
						AND o.order_type_id = 'SALE'
						AND DATE_FORMAT(order_time, '%Y-%m-%d') = DATE_FORMAT(FROM_UNIXTIME(reg_time), '%Y-%m-%d')
				)
			";			
		} else {
			$order_status_names = array();
			foreach ($order_status as $status) {
				$order_status_names[] = get_order_status($status);
			}
			$title .= join('、', $order_status_names) . " ";
			$db_in_order_status = db_create_in($order_status);
			$where_condition .= "
				AND EXISTS(
					SELECT 1 FROM {$ecs->table('order_info')} o 
					WHERE 
						o.user_id = u.user_id 
						AND o.order_type_id = 'SALE'
						AND DATE_FORMAT(order_time, '%Y-%m-%d') = DATE_FORMAT(FROM_UNIXTIME(reg_time), '%Y-%m-%d')
						AND o.order_status $db_in_order_status
				)
			";
		}
	}
	
	$condition['where'] = $where_condition;
	return $condition;
}

?>