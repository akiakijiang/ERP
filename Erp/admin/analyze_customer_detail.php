<?php
include_once("analyze_detail_header.php");

switch ($type) {
	case 'message_order':
		
		$condition = get_condition();
		$columns = array(
			'order_sn' => '订单号/时间',
			'order_goods' => '订单商品',
			'status' => '状态',
			'comment' => '留言',
			'goods_name' => '询问商品',			
		);
		$style = array(
			'order_sn' => "style='width:80px;'",
			"comment" => "style='width:500px;padding:0px' align='left'",
			'status' => "style='width:50px'",
		);
		$sql = "
			SELECT *, GROUP_CONCAT(og2.goods_name SEPARATOR '<br>') order_goods, GROUP_CONCAT(og.goods_id SEPARATOR ', ') goods_ids
			FROM {$ecs->table('order_info')} o, {$ecs->table('order_goods')} og, {$ecs->table('order_goods')} og2
			WHERE
				(SELECT COUNT(*) FROM {$ecs->table('order_info')} AS tmo WHERE tmo.parent_order_id = o.order_id) = 0 
				AND INSTR(order_sn, '-') = 0 
				AND biaoju_store_id in (0, 7)			
				AND o.order_id = og.order_id
				AND o.order_id = og2.order_id
				AND UNIX_TIMESTAMP(order_time) > UNIX_TIMESTAMP('$start_date') 
				AND UNIX_TIMESTAMP(order_time) < UNIX_TIMESTAMP(DATE_ADD('$end_date',INTERVAL 1 DAY))
				AND 
					EXISTS (
						SELECT 1 FROM bj_comment c
						LEFT JOIN {$ecs->table('users')} u ON c.user_id = u.userId
						WHERE
							c.store_id = 0
							AND c.store_goods_id = og.goods_id
							AND u.user_id = o.user_id
							AND UNIX_TIMESTAMP(DATE_ADD(o.order_time,INTERVAL -7 DAY)) < UNIX_TIMESTAMP(c.post_datetime)
					)
			GROUP BY o.order_sn
			ORDER BY o.order_time DESC
			$limit $offset
		";
		$sqlc = "
			SELECT COUNT(*) FROM {$ecs->table('order_info')} o, {$ecs->table('order_goods')} og
			WHERE
				(SELECT COUNT(*) FROM {$ecs->table('order_info')} AS tmo WHERE tmo.parent_order_id = o.order_id) = 0 
				AND INSTR(order_sn, '-') = 0 
				AND biaoju_store_id in (0, 7)			
				AND o.order_id = og.order_id
				AND UNIX_TIMESTAMP(order_time) > UNIX_TIMESTAMP('$start_date') 
				AND UNIX_TIMESTAMP(order_time) < UNIX_TIMESTAMP(DATE_ADD('$end_date',INTERVAL 1 DAY))
				AND 
					EXISTS (
						SELECT 1 FROM bj_comment c
						LEFT JOIN {$ecs->table('users')} u ON c.user_id = u.userId
						WHERE
							c.store_id = 0
							AND c.store_goods_id = og.goods_id
							AND u.user_id = o.user_id
							AND UNIX_TIMESTAMP(DATE_ADD(o.order_time,INTERVAL -7 DAY)) < UNIX_TIMESTAMP(c.post_datetime)
					)
		";
		$title .= "留言订单({$start_date}至{$end_date})";
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
		if ($type == 'message_order') {
			$sql = "
				SELECT * FROM bj_comment c
				LEFT JOIN {$ecs->table('users')} u ON c.user_id = u.userId
				WHERE
					c.store_id = 0
					AND c.store_goods_id IN ({$all_data[$key]['goods_ids']})
					AND u.user_id = {$all_data[$key]['user_id']}
					AND UNIX_TIMESTAMP(DATE_ADD('{$all_data[$key]['order_time']}',INTERVAL -7 DAY)) < UNIX_TIMESTAMP(c.post_datetime)
			";
			$comments = $db->getAll($sql);
			$all_data[$key]['comment'] = "<table class='bWindow'>";
			foreach ($comments as $comment) {
				$all_data[$key]['comment'] .= "<tr>";
				$all_data[$key]['comment'] .= "<td style='width:250px' align='left'>";
				$all_data[$key]['comment'] .= "<span style='color:red'>{$comment['post_datetime']} {$comment['user_name']}</span><br>{$comment['comment']}";
				$all_data[$key]['comment'] .= "</td>";
				$all_data[$key]['comment'] .= "</tr>";
				$all_data[$key]['comment'] .= "<tr>";
				$all_data[$key]['comment'] .= "<td style='width:250px' align='left'>";
				$all_data[$key]['comment'] .= "<span style='color:green'>{$comment['replied_datetime']} {$comment['replied_nick']}</span><br>{$comment['reply']}";
				$all_data[$key]['comment'] .= "</td>";
				$all_data[$key]['comment'] .= "</tr>";
			}
			$all_data[$key]['comment'] .= "</table>";
		}
		// 处理订单号
		if ($all_data[$key]['order_sn'] !== null && $all_data[$key]['order_id'] !== null) {
			$all_data[$key]['order_sn'] = "<a href='detail_info.php?order_id={$all_data[$key]['order_id']}' target='_blank'>{$all_data[$key]['order_sn']}</a><br>{$all_data[$key]['order_time']}";
		}
		
		// 处理状态
		if ($all_data[$key]['order_status'] !== null && $all_data[$key]['shipping_status'] !== null && $all_data[$key]['pay_status'] !== null) {
			$all_data[$key]['status'] = get_order_status($all_data[$key]['order_status']) . "<br>" . get_shipping_status($all_data[$key]['shipping_status']) . "<br>" . get_pay_status($all_data[$key]['pay_status']);
		}
		
	}
	return $all_data;
}

function get_condition() {
	global $title, $ecs, $columns;
	
	$condition['where'] = $where_condition;
	return $condition;
}

?>