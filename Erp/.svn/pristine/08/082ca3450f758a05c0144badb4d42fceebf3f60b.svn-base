<?php
define('IN_ECS', true);

require('includes/init.php');
require("function.php");

$date = $_REQUEST['date'];

$worktime_array = array('09:30', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00');
$outworktime_array = array('19:00', '20:00', '21:00', '22:00', '23:00', '00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00', '08:00', '09:00');

$work_sql_base = " AND (DATE_FORMAT(c.post_datetime, '%H:%i') >= '#begin#' AND DATE_FORMAT(c.post_datetime, '%H:%i') <  '#end#') ";
$work_sql_array = getWorksql($work_sql_base);



foreach ($work_sql_array as $work_sql) {
  $time_area = $work_sql['time'];
  $sql = "
		SELECT type, COUNT(user_id) AS type_count
			FROM bj_comment c
		WHERE 
			c.status = 'OK'
			AND post_datetime > ('$date') 
			AND post_datetime < DATE_ADD('$date',INTERVAL 1 DAY) 
			{$work_sql['sql']}
		GROUP BY type
	";
	$comment_users = $db->getAll($sql);
	
  
  $sql = "
		SELECT c.type, COUNT(DISTINCT o.order_id) AS type_count FROM bj_comment c
		INNER JOIN {$ecs->table('users')} u ON c.user_id = u.userId
		INNER JOIN {$ecs->table('order_info')} o ON o.user_id = u.user_id
		  WHERE			
				o.order_type_id = 'SALE'
				AND c.status = 'OK'
				AND o.order_status = 1
				AND o.order_time > '{$date}'
				AND o.order_time > c.replied_datetime
				AND o.order_time < DATE_ADD(c.replied_datetime, INTERVAL 1 DAY) 
				AND c.post_datetime > '{$date}'
				AND c.post_datetime < DATE_ADD('{$date}', INTERVAL 1 DAY)
				{$work_sql['sql']}
		GROUP BY c.type
	";
  $comment_user_order = $db->getAll($sql);
  $total_comment_users_count = 0;
  $total_comment_users_orders_count = 0;
  foreach ($comment_users as $c) {
	  $type = $c['type'];
	  foreach ($comment_user_order as $o) {
	    if ($o['type'] == $type) {
	    	$temp_count = $o['type_count'];
	    	break;
	    }
	  }
	  
	  $values[$time_area][$type] = 
	   array(
	    'percent' => 
	    $c['type_count'] > 0 
	    ? sprintf('%.2f', ($temp_count / $c['type_count'] * 100))
	    : '',
	    'comment_users_count' => $c['type_count'],
	    'comment_users_orders_count' => $temp_count,
	    );
	  $total_comment_users_count += $c['type_count'];
	  $total_comment_users_orders_count += $temp_count;
	}
	if ($total_comment_users_count != 0) {
		$values[$time_area]['all'] = 
		array(
	    'percent' => 
	    $total_comment_users_count > 0 
	    ? sprintf('%.2f', ($total_comment_users_orders_count / $total_comment_users_count * 100))
	    : '',
	    'comment_users_count' => $total_comment_users_count,
	    'comment_users_orders_count' => $total_comment_users_orders_count,
    );
	}
}
$order_status = isset($_REQUEST['order_status']) ? trim($_REQUEST['order_status']) : -1;
$bjtype = empty($_REQUEST['bjtype']) ? 'all' : trim($_REQUEST['bjtype']);
$comment_status = empty($_REQUEST['comment_status']) ? 'all' : trim($_REQUEST['comment_status']);
$comments = get_comment($date, $date, 1, $order_status, -1, $bjtype, $comment_status);
$bjtype_mapping = array('all'=> '所有', 'goods'=>'商品咨询', 'shipping'=>'物流配送', 'payment'=>'支付问题', 'postsale'=>'保修及发票', 'complaint'=>'投诉建议');
$smarty->assign('values', $values);
$smarty->assign('date', $date);
$smarty->assign('bjtype_mapping', $bjtype_mapping);
$smarty->assign('comments', $comments);
$smarty->display('oukooext/analyze_comment_detail.htm');




function getWorksql($work_sql_base) {
  global $worktime_array, $outworktime_array;
  $work_sql_array = array();
  $work_sql_temp = array();
  for ($i = 0; $i < count($worktime_array) - 1; $i++) {
    $work_sql_temp['sql'] = str_replace(array('#begin#', '#end#'), array($worktime_array[$i], $worktime_array[$i+1]), $work_sql_base);
    $work_sql_temp['time'] = "$worktime_array[$i] - {$worktime_array[$i+1]}";
    $work_sql_temp['isworktime'] = true;
    $work_sql_array[] = $work_sql_temp;
  }

  for ($i = 0; $i < count($outworktime_array); $i++) {
    $work_sql_temp['isworktime'] = false;
    if ($i ==  count($outworktime_array) - 1 ) {
      $work_sql_temp['sql'] = str_replace(array('#begin#', '#end#'), array($outworktime_array[$i], $worktime_array[0]), $work_sql_base);
    	$work_sql_temp['time'] = "$outworktime_array[$i] - $worktime_array[0]";
    	$work_sql_array[] = $work_sql_temp;
    	continue;
    }
    if ($outworktime_array[$i] < $outworktime_array[$i+1]) {
      $work_sql_temp['sql'] = str_replace(array('#begin#', '#end#'), array($outworktime_array[$i], $outworktime_array[$i+1]), $work_sql_base);
    	$work_sql_temp['time'] = "$outworktime_array[$i] - {$outworktime_array[$i+1]}";
    	$work_sql_array[] = $work_sql_temp;
    } else {
      $work_sql_temp['sql'] = str_replace(array('#begin#', '#end#'), array($outworktime_array[$i], '24:00'), $work_sql_base);
    	$work_sql_temp['time'] = "$outworktime_array[$i] - 24:00";
    	$work_sql_array[] = $work_sql_temp;
    }
  }
  return $work_sql_array;
}

/**
 * 取得用户留言
 *
 * @param date $start
 * @param date $end
 * @param int $has_order -1:all, 0:false, 1:true
 * @param int $order_status -1:all, status
 * @param int $uid
 * @param string $type :('all', 'goods', 'shipping', 'payment', 'postsale', 'complaint')
 * 
 * return array
 */
function get_comment($start, $end, $has_order = -1, $order_status = -1, $user_id = -1, $type = 'all', $comment_status = 'OK') {
	global $ecs, $db;
	if (!strtotime($start)) {
		$start = date("Y-m-d", strtotime("-1 day"));
	}
	if (!strtotime($end)) {
		$end = date("Y-m-d");
	}
	if (strtotime($start)+2*24*3600 < strtotime($end)) {
		$end = date("Y-m-d", strtotime($start)+2*24*3600);
	}
	$sql_condition = " AND c.post_datetime >= '$start' AND c.post_datetime < '$end 23:59:59' ";
	if ($has_order != -1) {
		$sql_condition .= ($hassorder ? " NOT ": " "). 
			" AND EXISTS (
					SELECT 1 
					FROM {$ecs->table('order_info')} oi 
					WHERE oi.user_id = u.user_id
					 AND oi.order_time < DATE_ADD(c.post_datetime, INTERVAL 1 DAY)
					 AND oi.order_time >= c.post_datetime
					 ".
			($order_status == -1 ? "" : " AND oi.order_status = $order_status ").
			" )";
	}
	if ($user_id != -1) {
		$sql_condition .= " AND u.user_id = $user_id ";
	}
	if ($type != 'all') {
		$sql_condition .= " AND c.type = '$type' ";
	}
	if ($comment_status != 'all') {
		$sql_condition .= " AND c.status = '$comment_status' ";
	}
	$sql_group = " GROUP BY u.user_id ";
	$sql = "SELECT u.user_id, GROUP_CONCAT(c.comment SEPARATOR ';<br />') AS comments, MIN(c.post_datetime) AS min_pd, MAX(c.post_datetime) AS max_pd, u.user_name FROM {$ecs->table('users')} u, bj_comment c WHERE u.userId = c.user_id ". $sql_condition. $sql_group;
	$comments = $db->getAll($sql);
	foreach ($comments as $key => $comment) {
		$comments[$key]['user'] = get_user_details($comment['user_id']);
		$comments[$key]['orders'] = get_user_orders($comment['user_id'], $comment['min_pd'], $comment['max_pd'], $order_status);
	}
	return $comments;
}

function get_user_details($user_id) {
	global $ecs, $db;
	$sql = "SELECT * FROM {$ecs->table('users')} WHERE user_id = '{$user_id}'";
	$user = $db->getRow($sql);
	$user['reg_time_str'] = date("Y-m-d H:i:s", $user['reg_time']);
	$sql = "SELECT COUNT(*) AS num FROM {$ecs->table('order_info')} WHERE  user_id = '{$user_id}' AND order_status = 1 AND (shipping_status = 2 OR shipping_status = 6)";
	$buy_succes_times = $db->getOne($sql);
	$user['buy_succes_times'] = $buy_succes_times;
	
	$sql = "SELECT order_status, COUNT(*) AS num FROM {$ecs->table('order_info')} WHERE  user_id = '{$user_id}' GROUP BY order_status";
	$rows = $db->getAll($sql);
	foreach ($rows as $row) {
	  $order_status[$row['order_status']] = $row['num'];
	}
	
	$sql = "SELECT  COUNT(*) FROM {$ecs->table('order_info')} WHERE  user_id = '{$user_id}' AND shipping_status = '".SS_JUSHOU_RECEIVED."'";
	$order_status[OS_RETURNED] += $db->getOne($sql); //加上shipping_status中的用户拒收
	$user['order_status'] = $order_status;
	return $user;
}

function get_user_orders($user_id, $start, $end, $order_status) {
	global $ecs, $db;
	$mapping_status = array(
		'0'		=>		'未确认',
		'1'		=>		'已确认',
		'2'		=>		'已取消',
		);
	$sql = "SELECT GROUP_CONCAT(og.goods_name SEPARATOR ';') AS goods_name, oi.order_sn, oi.order_status, oi.order_id, oi.order_time, oi.shipping_name, oi.pay_name 
						FROM {$ecs->table('order_info')} oi, {$ecs->table('order_goods')} og
						WHERE oi.order_id = og.order_id AND oi.user_id = '$user_id' AND oi.order_time >= '$start' AND oi.order_time < DATE_ADD('$end', INTERVAL 1 DAY) ".
						($order_status == -1? "" : " AND oi.order_status = $order_status ").
						" GROUP BY oi.order_id ";
	$orders = $db->getAll($sql);
	$user_order = array();
	foreach ($orders as $key => $order) {
		$user_order['order_sn'] .= "<a target='_blank' href='detail_info.php?order_id={$order['order_id']}'>{$order['order_sn']}</a> {$mapping_status[$order['order_status']]}<br />";
		$user_order['goods_name'] .= $order['goods_name']."<br />";
		$user_order['order_time'] .= $order['order_time']."<br />";
		$user_order['shipping_name'] .= $order['shipping_name']."<br />";
		$user_order['pay_name'] .= $order['pay_name']."<br />";
	}
	return $user_order;
}
