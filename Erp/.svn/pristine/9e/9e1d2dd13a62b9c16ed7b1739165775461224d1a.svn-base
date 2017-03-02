<?php
/**
 * 手机组考核
 *
 */
define('IN_ECS', true);
require('includes/init.php');
party_priv(PARTY_OUKU);
admin_priv('tj_analyze_check');
require("function.php");

$start = $_REQUEST['start'];
$end = $_REQUEST['end'];

$weeks = get_weeks($start, $end);

if (!is_array($weeks))
	die("时间非法");

	
//需要统计的用户从cfg里面取
$confirm_action_users = explode("\n", $_CFG['confirm_action_users']);
foreach ($confirm_action_users as $key => $user) {
  $confirm_action_users[$key] = trim($user);
}

$ship_action_users = explode("\n", $_CFG['ship_action_users']);
foreach ($ship_action_users as $key => $user) {
  $ship_action_users[$key] = trim($user);
}

$comment_users = explode("\n", $_CFG['comment_users']);
foreach ($comment_users as $key => $user) {
  $comment_users[$key] = trim($user);
}

$lines = explode("\n", $_CFG['comment_users_convert']);
foreach ($lines as $line) {
  $line = trim($line);
  $tmp = explode("=", $line);
  $comment_users_convert[$tmp[0]] = $tmp[1];
}

// 初始化操作人员数据
foreach ($confirm_action_users as $key => $confirm_action_user) {
	$confirm_action_users[$confirm_action_user] = array();
	unset($confirm_action_users[$key]);
}

foreach ($ship_action_users as $key => $ship_action_user) {
	$ship_action_users[$ship_action_user] = array();
	unset($ship_action_users[$key]);
}

foreach ($comment_users as $key => $comment_user) {
	$comment_users[$comment_user] = array();
	unset($comment_users[$key]);
}

$values = array();
$sum = array();
foreach ($weeks as $week_key => $week) {
	// 每周每个留言回复者的留言平均回复时间分钟数,每周每个留言回复者的留言回复数量
	
	// 售中
	$sql = "
		SELECT replied_by, reply_datetime, post_datetime
		FROM {$ecs->table('order_comment')} c 
		WHERE reply_datetime >= '{$week['start']}'
			AND reply_datetime < DATE_ADD('{$week['end']}',INTERVAL 1 DAY)
			AND status = 'OK' AND comment_cat <> 4  -- 不包括订单确认的留言
	";
	$comments = $slave_db->getAll($sql);
	foreach ($comments as $comment) {
		if (key_exists($comment['replied_by'], $comment_users)) {
			$comment_users[$comment['replied_by']][$week_key]['mid_count']++;
			
			$comment['post_datetime'] = remove_break_time($comment['post_datetime']);
			$dif_time = calculate_dif_time($comment['post_datetime'], $comment['reply_datetime']);
			if ($dif_time > 0)
				$comment_users[$comment['replied_by']][$week_key]['mid_sum_second'] += $dif_time;
		}
	}
	// end 售中
	
	// 商品评论
	$sql = "
		SELECT u.user_name, replied_datetime, post_datetime
		FROM bj_comment c, {$ecs->table('users')} u
		WHERE
			replied_datetime >= '{$week['start']}'
			AND replied_datetime < DATE_ADD('{$week['end']}',INTERVAL 1 DAY)
			AND c.replied_by = u.userId
			AND status = 'OK'
	";
	$comments = $slave_db->getAll($sql);
	foreach ($comments as $comment) {
		if (key_exists($comment['user_name'], $comment_users) || key_exists($comment_users_convert[$comment['user_name']], $comment_users)) {
		  if (key_exists($comment_users_convert[$comment['user_name']], $comment_users)) {
		  	$comment['user_name'] = $comment_users_convert[$comment['user_name']];
		  }
			$comment_users[$comment['user_name']][$week_key]['before_count']++;
			
			$comment['post_datetime'] = remove_break_time($comment['post_datetime']);
			$dif_time = calculate_dif_time($comment['post_datetime'], $comment['replied_datetime']);
			
			if ($dif_time > 0)
				$comment_users[$comment['user_name']][$week_key]['before_sum_second'] += $dif_time;
		}
	}
	// end 商品评论
	
	// 转换订单数
	$sql = "
		SELECT DISTINCT u.user_name, COUNT(*) count
		FROM bj_comment c, {$ecs->table('users')} u
		WHERE
			replied_datetime >= '{$week['start']}'
			AND replied_datetime < DATE_ADD('{$week['end']}',INTERVAL 1 DAY)
			AND c.replied_by = u.userId
			AND status = 'OK'
			AND EXISTS (
				SELECT 1 FROM {$ecs->table('order_info')} o, {$ecs->table('order_goods')} og, {$ecs->table('users')} ou
				WHERE
					o.user_id = ou.user_id
					AND o.order_id = og.order_id
					AND ou.userId = c.user_id
					AND og.goods_id = c.store_goods_id
					AND o.special_type_id <> 'PRESELL'
					AND o.party_id = ". PARTY_OUKU_MOBILE ."
			)
		GROUP BY u.user_name		
	";
	$convert_comments = $slave_db->getAll($sql);
	foreach ($convert_comments as $convert_comment) {
		if (key_exists($convert_comment['user_name'], $comment_users)) {
			$comment_users[$convert_comment['user_name']][$week_key]['convert_count'] += $convert_comment['count'];
		}
	}
	// end 转换订单数
	
	// 售后订单的评论
	$sql = "
		SELECT u.user_name, r.post_time reply_time, c.post_time post_time
		FROM {$ecs->table('after_order_comment')} c, {$ecs->table('after_order_comment')} r, {$ecs->table('users')} u
		WHERE
			UNIX_TIMESTAMP(r.post_time) >= UNIX_TIMESTAMP('{$week['start']}')
			AND UNIX_TIMESTAMP(r.post_time) < UNIX_TIMESTAMP(DATE_ADD('{$week['end']}',INTERVAL 1 DAY))
			AND r.user_id = u.userId
			AND c.comment_id = r.reply_to
	";
	$comments = $slave_db->getAll($sql);
	foreach ($comments as $comment) {
		if (key_exists($comment['user_name'], $comment_users) || key_exists($comment_users_convert[$comment['user_name']], $comment_users)) {
		  if (key_exists($comment_users_convert[$comment['user_name']], $comment_users)) {
		  	$comment['user_name'] = $comment_users_convert[$comment['user_name']];
		  }
			$comment_users[$comment['user_name']][$week_key]['after_count']++;
			
			$comment['post_time'] = remove_break_time($comment['post_time']);
			$dif_time = calculate_dif_time($comment['post_time'], $comment['reply_time']);
			
			if ($dif_time > 0)
				$comment_users[$comment['user_name']][$week_key]['after_sum_second'] += $dif_time;			
		}
	}
	// end 售后订单的评论
	
	// 售后服务和咨询
	$sql = "
		SELECT review_username AS reviewer_name, review_datetime AS review_time, apply_datetime AS apply_time
		FROM service s
		WHERE	review_datetime >= ('{$week['start']}')	AND review_datetime < '{$week['end']} 24:00:00'
	";
	$comments = $slave_db->getAll($sql);
	foreach ($comments as $comment) {
		if (key_exists($comment['reviewer_name'], $comment_users)) {
			$comment_users[$comment['reviewer_name']][$week_key]['sale_count']++;
			
			$comment['apply_time'] = remove_break_time($comment['apply_time']);
			$dif_time = calculate_dif_time($comment['apply_time'], $comment['review_time']);
			if ($dif_time > 0)
				$comment_users[$comment['reviewer_name']][$week_key]['sale_sum_second'] += $dif_time;
		}
	}
	
	$sql = "SELECT replied_username AS reviewer_name, post_datetime AS apply_time, replied_datetime AS review_time
	        FROM service_comment 
	        WHERE replied_datetime >= '{$week['start']}'	AND replied_datetime < '{$week['end']} 24:00:00' ";
	$comments = $slave_db->getAll($sql);
	foreach ($comments as $comment) {
		if (key_exists($comment['reviewer_name'], $comment_users)) {
			$comment_users[$comment['reviewer_name']][$week_key]['sale_count']++;
			
			$comment['apply_time'] = remove_break_time($comment['apply_time']);
			$dif_time = calculate_dif_time($comment['apply_time'], $comment['review_time']);
			if ($dif_time > 0)
				$comment_users[$comment['reviewer_name']][$week_key]['sale_sum_second'] += $dif_time;
		}
	}
	// end 售后服务和咨询
	
	// end 每周每个留言回复者的留言平均回复时间分钟数,每周每个留言回复者的留言回复数量
	
	// 订单确认
	$sql = "
		SELECT
  			IF( p.pay_code = 'cod', 
  			    o.order_time,
				IFNULL((SELECT MIN(action_time) FROM {$ecs->table('order_action')} 
				        WHERE order_id = o.order_id AND pay_status = 2), 
                        o.order_time
				)
		  	) AS order_time,
		  	( SELECT action_user FROM {$ecs->table('order_action')} WHERE order_id = o.order_id AND order_status = 1 ORDER BY action_time ASC LIMIT 1) AS action_user,
		  	MIN(a.action_time) AS action_time,
		  	COUNT(DISTINCT a.order_id)
		FROM
			{$ecs->table('order_action')} AS a
			LEFT JOIN {$ecs->table('order_info')} AS o ON o.order_id = a.order_id
			LEFT JOIN {$ecs->table('payment')} AS p ON p.pay_id = o.pay_id
		WHERE 
            o.order_time <=  DATE_ADD('{$week['end']}',INTERVAL 1 DAY) and 
            o.order_time >= DATE_SUB('{$week['start']}',INTERVAL 10 DAY) and
			o.order_type_id = 'SALE' AND a.order_status = 1 AND 
			o.special_type_id <> 'PRESELL' AND o.party_id = ". PARTY_OUKU_MOBILE ."
		GROUP BY o.order_id
		HAVING action_time BETWEEN '{$week['start']}' AND DATE_ADD('{$week['end']}',INTERVAL 1 DAY)
	";
	$orders = $slave_db->getAll($sql);
	foreach ($orders as $order) {
		if (key_exists($order['action_user'], $confirm_action_users)) {
			$confirm_action_users[$order['action_user']][$week_key]['count']++;
		
			$order['order_time'] = remove_break_time($order['order_time']);
			$dif_time = calculate_dif_time($order['order_time'], $order['action_time']);
			if ($dif_time > 0)
				$confirm_action_users[$order['action_user']][$week_key]['sum_second'] += $dif_time;
				
			// 调试信息
            if (strtotime($order['action_time']) - strtotime($order['order_time']) > 0) {
            	$order['seconds'] = strtotime($order['action_time']) - strtotime($order['order_time']);
            } else {
				$order['seconds'] = 0;
            }
            if ($order['action_user'] == 'jyang') {
            	// pp($order);
            }
				
		}
	}
	// end 订单确认
	
	// 发货时间
	$sql = "
		SELECT o.order_time, 
            IFNULL((SELECT action_user FROM {$ecs->table('order_action')} WHERE order_id = o.order_id AND order_time = 1 ORDER BY action_time LIMIT 1), order_time) confirm_time, 
            (SELECT action_user FROM {$ecs->table('order_action')} WHERE order_id = o.order_id AND shipping_status = 8 ORDER BY action_time LIMIT 1) action_user, 
            (SELECT action_time FROM {$ecs->table('order_action')} WHERE order_id = o.order_id AND shipping_status = 8 ORDER BY action_time LIMIT 1) action_time
		FROM {$ecs->table('order_info')} o
		WHERE
			o.special_type_id <> 'PRESELL' AND UNIX_TIMESTAMP(o.order_time) >= UNIX_TIMESTAMP('{$week['start']}') AND o.party_id = ". PARTY_OUKU_MOBILE ."
			AND UNIX_TIMESTAMP(o.order_time) < UNIX_TIMESTAMP(DATE_ADD('{$week['end']}',INTERVAL 1 DAY))
			AND EXISTS (SELECT 1 FROM {$ecs->table('order_action')} WHERE o.order_id = order_id AND shipping_status = 8)
	";
	$orders = $slave_db->getAll($sql);
	foreach ($orders as $order) {
		if (key_exists($order['action_user'], $ship_action_users)) {
			$ship_action_users[$order['action_user']][$week_key]['count']++;
			// 过滤时间非工作时间
			$order['confirm_time'] = remove_break_time($order['confirm_time']);
			$dif_time = calculate_dif_time($order['confirm_time'], $order['action_time']);
			if ($dif_time > 0)
				$ship_action_users[$order['action_user']][$week_key]['sum_second'] += $dif_time;
		}
	}
	// end 发货时间
	
	// 记录评论周小计总和
	foreach ($comment_users as $comment_user) {
		$sum[$week_key]['count'] += $comment_user[$week_key]['before_count'] + $comment_user[$week_key]['mid_count'] + $comment_user[$week_key]['after_count'] + $comment_user[$week_key]['sale_count'];
		$sum[$week_key]['sum_second'] += $comment_user[$week_key]['before_sum_second'] + $comment_user[$week_key]['mid_sum_second'] + $comment_user[$week_key]['after_sum_second'] + $comment_user[$week_key]['sale_sum_second'];
	}
	// end 记录评论周小计总和
}

$smarty->assign('confirm_action_users', $confirm_action_users);
$smarty->assign('ship_action_users', $ship_action_users);
$smarty->assign('comment_users', $comment_users);
$smarty->assign('weeks', $weeks);
$smarty->assign('sum', $sum);
$smarty->display('oukooext/analyze_check.htm');


/**
 * 过滤时间非工作时间
 * 工作时间为：10点至18点
 * @param string $time 去除休息时间前的时间点
 * @param string $format 时间格式
 * @return string 去除休息时间后的时间点
 */
function remove_break_time($time, $format = "Y-m-d H:i:s") {
	// 先将前一天晚上过下班时间订单转移成第二天10点订单
	$time_array = getdate(strtotime($time));
	
	if (in_array($time_array['hours'], array(19, 20, 21, 22, 23))) {
		$time = date($format, strtotime("+10 hour", strtotime("tomorrow", strtotime($time))));
	}
	
	// 再将当天上班前的订单时间转移成上班时间的订单
	$time_array = getdate(strtotime($time));
	if (in_array($time_array['hours'], array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9))) {
		$time = date($format, strtotime("+10 hour", strtotime("today", strtotime($time))));
	}
	return $time;
}

/**
 * 如果隔夜的话去除第一个晚上的时间
 * 工作时间为：10点至18点
 * @param string $start 起始时间
 * @param string $end 结束时间
 */
function calculate_dif_time($start, $end) {
	$start_time = strtotime($start);
	$end_time = strtotime($end);
	
	$dif_time = $end_time - $start_time;
	
	if ($dif_time <= 0)
		return 0;
	
	$start_time_array = getdate($start_time);
	$end_time_array = getdate($end_time);
	
	if ($start_time_array['mday'] < $end_time_array['mday']) {
		$dif_time -= 16 * 60 * 60;
	}
	
	if ($dif_time < 0)
		$dif_time = 0;
		
	return $dif_time;
}
?>