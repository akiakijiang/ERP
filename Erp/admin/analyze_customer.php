<?php
define('IN_ECS', true);
require('includes/init.php');
party_priv(PARTY_OUKU);
admin_priv('analyze_customer');
require("function.php");

$start = $_REQUEST['start'];
$end = $_REQUEST['end'];

// 获取客服名，初始化数据
$sql = "SELECT DISTINCT action_user FROM {$ecs->table('oukoo_consult')}";
$action_users = $db->getCol($sql);
foreach ($action_users as $key => $action_user) {
	if (!in_array($action_user, array('feixiao', 'qliu')))
		$action_users[$action_user] = array();
	unset($action_users[$key]);
}
// end获取客服名

/* 取得talk_in_time的数据库 */
$tit_db = new cls_mysql($tit_db_host, $tit_db_user, $tit_db_pass, $tit_db_name);

// 得到一系列的日期
$dates = get_dates($start, $end);

$values = array();
$sum = array();

$store_info = " 
	AND (SELECT COUNT(*) FROM {$ecs->table('order_info')} AS tmo WHERE tmo.parent_order_id = o.order_id) = 0 
	AND INSTR(order_sn, '-') = 0 
	AND biaoju_store_id in (0, 7)
";

foreach ($dates as $date) {
	$values[$day] = array();

	// 咨询详情
	$sql = "
		SELECT * FROM {$ecs->table('oukoo_consult')} 
		WHERE 
			consult_time = '$date'	
	";
	$consults = $db->getAll($sql);
	foreach ($consults as $consult) {
		// 转化订单数
		if ($consult['result'] == 'YES') {
			if ($consult['method'] == 'TIT') {
				$values[$date]['tit_order_count']++;
				$sum['tit_order_count']++;
				$action_users[$consult['action_user']][$date]['tit_order_count']++;
				$action_users[$consult['action_user']]['sum']['tit_order_count']++;
			} elseif ($consult['method'] == 'PHONE') {
				$values[$date]['phone_order_count']++;
				$sum['phone_order_count']++;
				$action_users[$consult['action_user']][$date]['phone_order_count']++;
				$action_users[$consult['action_user']]['sum']['phone_order_count']++;
			} elseif ($consult['method'] == 'ONLINE_STORE') {
				$values[$date]['online_store_order_count']++;
				$sum['online_store_order_count']++;
				$action_users[$consult['action_user']][$date]['online_store_order_count']++;
				$action_users[$consult['action_user']]['sum']['online_store_order_count']++;
			}
		}
		// end转化订单数
		
		// 电话咨询数
		if ($consult['method'] == 'PHONE') {
			$values[$date]['phone_count']++;
			$sum['phone_count']++;
			$action_users[$consult['action_user']][$date]['phone_count']++;
			$action_users[$consult['action_user']]['sum']['phone_count']++;
		} else if ($consult['method'] == 'ONLINE_STORE') {
			$values[$date]['online_store_count']++;
			$sum['online_store_count']++;
			$action_users[$consult['action_user']][$date]['online_store_count']++;
			$action_users[$consult['action_user']]['sum']['online_store_count']++;			
		}
		// end电话咨询数
	}
	// end咨询详情
	
	// 客服会话数
	$sql = "
		SELECT COUNT(*) FROM session 
		WHERE 
			start_time > UNIX_TIMESTAMP('$date') * 1000 
			AND start_time < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY)) * 1000
	";
	$values[$date]['tit_count'] = $tit_db->getOne($sql);
	$sum['tit_count'] += $values[$date]['tit_count'];
	
	foreach ($action_users as $user_name => $action_user) {
		$sql = "SELECT userId FROM {$ecs->table('users')} WHERE user_name = '$user_name'";
		$uuId = $db->getOne($sql);
		$sql = "
			SELECT COUNT(*) FROM session s, client c
			WHERE 
				s.session_id = c.session_id
				AND c.user_id = '$uuId'
				AND s.start_time > UNIX_TIMESTAMP('$date') * 1000 
				AND s.start_time < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY)) * 1000;
		";
		$action_users[$user_name][$date]['tit_count'] = $tit_db->getOne($sql);
		$action_users[$user_name]['sum']['tit_count'] += $action_users[$user_name][$date]['tit_count'];
	}
	
	// end客服会话数

	
	// 订单->已确认并发货率
	// 订单数
	$sql = "
		SELECT COUNT(*) FROM {$ecs->table('order_info')} o 
		WHERE 
			1
			$store_info
			AND UNIX_TIMESTAMP(order_time) > UNIX_TIMESTAMP('$date') 
			AND UNIX_TIMESTAMP(order_time) < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY))
	";
	$values[$date]['order_count'] = $db->getOne($sql);
	$sum['order_count'] += $values[$date]['order_count'];
	
	// 确认订单数
	$sql = "
		SELECT COUNT(*) FROM {$ecs->table('order_info')} o 
		WHERE 
			1
			$store_info
			AND UNIX_TIMESTAMP(order_time) > UNIX_TIMESTAMP('$date') 
			AND UNIX_TIMESTAMP(order_time) < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY))
			AND order_status = 1
	";
	$values[$date]['confirm_order_count'] = $db->getOne($sql);
	$sum['confirm_order_count'] += $values[$date]['confirm_order_count'];	
	
	// 发货订单数
	$sql = "
		SELECT COUNT(*) FROM {$ecs->table('order_info')} o 
		WHERE 
			1
			$store_info
			AND UNIX_TIMESTAMP(order_time) > UNIX_TIMESTAMP('$date') 
			AND UNIX_TIMESTAMP(order_time) < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY)) 
			AND 
				EXISTS (
					SELECT 1 FROM {$ecs->table('order_action')} a
					WHERE
						a.order_id = o.order_id
						AND a.shipping_status = 1			
				)
	";
	$values[$date]['shipped_order_count'] = $db->getOne($sql);
	$sum['shipped_order_count'] += $values[$date]['shipped_order_count'];
	
	// 发货->收款率
	$sql = "
		SELECT COUNT(*) FROM {$ecs->table('order_info')} o 
		WHERE 
			1
			$store_info
			AND UNIX_TIMESTAMP(order_time) > UNIX_TIMESTAMP('$date') 
			AND UNIX_TIMESTAMP(order_time) < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY)) 
			AND pay_status = 2
	";
	$values[$date]['payed_order_count'] = $db->getOne($sql);
	$sum['payed_order_count'] += $values[$date]['payed_order_count'];
	// end发货->收款率
	
	// 留言订单数
	$sql = "
		SELECT COUNT(DISTINCT order_sn) FROM {$ecs->table('order_info')} o, {$ecs->table('order_goods')} og
		WHERE
			1
			$store_info
			AND o.order_id = og.order_id
			AND UNIX_TIMESTAMP(order_time) > UNIX_TIMESTAMP('$date') 
			AND UNIX_TIMESTAMP(order_time) < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY))
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
	$values[$date]['message_order_count'] = $db->getOne($sql);
	$sum['message_order_count'] += $values[$date]['message_order_count'];
	// end留言订单数
	
}

$smarty->assign('start', $dates[count($dates) - 1]);
$smarty->assign('end', $dates[0]);
$smarty->assign('action_users', $action_users);
$smarty->assign('values', $values);
$smarty->assign('dates', $dates);
$smarty->assign('sum', $sum);
$smarty->display('oukooext/analyze_customer.htm');
?>