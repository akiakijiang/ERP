<?php
define('IN_ECS', true);

require('includes/init.php');
require("function.php");
party_priv(PARTY_OUKU);
admin_priv('analyze_comment');
$start = $_REQUEST['start'];
$end = $_REQUEST['end'];

// 得到一系列的日期
$dates = get_dates($start, $end);

$one_day_seconds = 60 * 60 * 24;
$two_day_seconds = $one_day_seconds * 2;
$ten_day_seconds = $one_day_seconds * 10;

$worktime_array = array('09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00');
$outworktime_array = array('22:00', '23:00', '00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00', '08:00');

//需要统计的用户从cfg里面取
$repliedby_users[''] = '全部';
$comment_users = explode("\n", $_CFG['comment_users']);
foreach ($comment_users as $user) {
  $user = trim($user);
  $repliedby_users[$user] = trim($user);
}
$repliedby_users_convert = array();
$lines = explode("\n", $_CFG['comment_users_convert']);
foreach ($lines as $line) {
  $line = trim($line);
  $tmp = explode("=", $line);
  $repliedby_users_convert[$tmp[1]] = $tmp[0];
}

//$repliedby_users = array(''=>'全部', 'ypli' => 'ypli', 'yhwang' => 'yhwang', 'hzhang' => 'hzhang', 'jjma' => 'jjma',
// 'clyang' => 'clyang', 'chzhang' => 'chzhang', 'xyxu' => 'xyxu', 
// 'llzhu'=>'llzhu',
// 'bzhang'=>'bzhang','pjun'=>'pjun',
//  'dwhuang'=>'dwhuang','wewang'=>'wewang', 'wqliang'=>'wqliang', 'chxtian'=>'chxtian',
//  'jidingtian'=>'jidingtian', 'llmei'=>'llmei', 'wjjia'=>'wjjia',
// );
//$repliedby_users_convert = array('chzhang'=>'pierremar', 'yhwang'=>'yhwang1985', 'llzhu'=>'liuliuzhu', "wewang" => "wewang3721", "wqliang" => "xiqelwq", 'dwhuang'=>'tailsttk');
$smarty->assign('repliedby_users', $repliedby_users);
$start = $_REQUEST['start'] ? $_REQUEST['start'] : date("Y-m-d");
$end = $_REQUEST['end'] ? $_REQUEST['end'] : date('Y-m-d', strtotime('+1 day'));
//$isoutwork = $_REQUEST['isoutwork'] ? 1 : 0;
$repliedby = $_REQUEST['repliedby'] ? $_REQUEST['repliedby'] : '';

$begin_work = '09:00';
$end_work = '21:00';
//上班时间
if ($start) {
	if(date("w", strtotime($start)) == 0 || date("w", strtotime($start)) == 6) {
	  $begin_work = '10:00';
    $end_work = '19:00';
	}
}

$work_sql_base = " AND (DATE_FORMAT(post_datetime, '%H:%i') >= '#begin#' AND DATE_FORMAT(post_datetime, '%H:%i') <  '#end#') ";
$work_sql_array = getWorksql($work_sql_base);
if ($repliedby) {
  if ($repliedby_users_convert[$repliedby]) {
  	$sql = "select userId from {$ecs->table('users')} where user_name = '{$repliedby_users_convert[$repliedby]}' ";
  } else {
    $sql = "select userId from {$ecs->table('users')} where user_name = '{$repliedby}' ";
  }
	
	$userId = $db->getOne($sql);
	$condition = " AND status != 'DELETED' AND replied_by != '' AND store_id = 0 AND replied_datetime >= '{$start}' AND replied_datetime < '{$end}' AND replied_by = '$userId' ";
} else {
  $condition = " AND status != 'DELETED' AND replied_by != '' AND store_id = 0 AND replied_datetime >= '{$start}' AND replied_datetime < '{$end}' ";
}

$comment_counts  = array();
$comment_counts_work  = array();
$comment_counts_outwork  = array();
$comment_counts_total = 0;

$comment_avgtime  = array();
$comment_avglenth  = array();

foreach ($work_sql_array as $work_sql) {
  $time_area = $work_sql['time'];
  $sql = "SELECT type, COUNT(*) as type_count FROM bj_comment WHERE 1=1 {$condition} {$work_sql['sql']} GROUP BY type";
//    pp($sql);
  $type_comment_counts = $db->getAll($sql);
  foreach ($type_comment_counts as $type_comment_count) {
    $type = $type_comment_count['type'];
    $comment_counts[$time_area][$type] = $type_comment_count['type_count'];
    $comment_counts_total += $type_comment_count['type_count'];
    if ($work_sql['isworktime']) {
    	$comment_counts_work[$type] += $type_comment_count['type_count'];
    } else {
      $comment_counts_outwork[$type] += $type_comment_count['type_count'];
    }
  }
  
  $sql = " SELECT  AVG(UNIX_TIMESTAMP( replied_datetime ) - UNIX_TIMESTAMP( post_datetime )) AS avg_replied_time  FROM `bj_comment` WHERE replied_datetime IS NOT NULL {$condition} {$work_sql['sql']}  ";
  $comment_avgtime[$time_area]= intval($db->getOne($sql) /60);
  
  $sql = "SELECT AVG(CHARACTER_LENGTH(reply)) AS avg_length FROM bj_comment WHERE 1=1 {$condition} {$work_sql['sql']}";
  $comment_avglenth[$time_area] = intval($db->getOne($sql)/60);
}

$smarty->assign('comment_counts_work', $comment_counts_work);
$smarty->assign('comment_counts_outwork', $comment_counts_outwork);
$smarty->assign('comment_counts', $comment_counts);
$smarty->assign('comment_counts_total', $comment_counts_total);
$smarty->assign('comment_avgtime', $comment_avgtime);

$outwork_sql = " AND (DATE_FORMAT(post_datetime, '%H:%i') < '{$begin_work}' OR DATE_FORMAT(post_datetime, '%H:%i') >  '{$end_work}') ";
$work_sql = " AND (DATE_FORMAT(post_datetime, '%H:%i') >= '{$begin_work}' AND DATE_FORMAT(post_datetime, '%H:%i') <=  '{$end_work}') ";
$sql = " SELECT  AVG(UNIX_TIMESTAMP( replied_datetime ) - UNIX_TIMESTAMP( post_datetime )) AS avg_replied_time  FROM `bj_comment` WHERE replied_datetime IS NOT NULL {$condition} {$work_sql}  ";

$comment_reply_avg_time_work = $db->getOne($sql);
$smarty->assign('comment_reply_avg_time_work', intval($comment_reply_avg_time_work / 60));

$sql = " SELECT  AVG(UNIX_TIMESTAMP( replied_datetime ) - UNIX_TIMESTAMP( post_datetime )) AS avg_replied_time  FROM `bj_comment` WHERE replied_datetime IS NOT NULL {$condition} {$outwork_sql}  ";

$comment_reply_avg_time_outwork = $db->getOne($sql);
$smarty->assign('comment_reply_avg_time_outwork', intval($comment_reply_avg_time_outwork / 60));

$sql = "SELECT DISTINCT info.order_id, info.order_sn, info.consignee, info.mobile, info.tel FROM {$ecs->table('order_info')} info INNER JOIN {$ecs->table('users')} u ON info.user_id = u.user_id INNER JOIN bj_comment b ON u.userId = b.user_id and info.order_time > b.replied_datetime and info.order_time < DATE_ADD(b.replied_datetime, INTERVAL 1 DAY)  where info.order_status = 1 {$condition} ";
$comment_buy_orders = $db->getAll($sql);
foreach ($comment_buy_orders AS $key_order => $comment_buy_order) {
	$comment_buy_orders[$key_order]['order_goods_info'] = getOrderGoods($comment_buy_order['order_id']);
}
$comment_buy_count = count($comment_buy_orders);
$comment_buy_percent = $comment_counts_total > 0 ?  sprintf("%.2f", $comment_buy_count / $comment_counts_total * 100) .'%' : '无售前留言';
$smarty->assign('buy_after_comment_list', $comment_buy_orders);
$smarty->assign('comment_buy_count', $comment_buy_count);
$smarty->assign('comment_buy_percent', $comment_buy_percent);

///售中留言统计
$work_sql_base = " AND (DATE_FORMAT(post_datetime, '%H:%i') >= '#begin#' AND DATE_FORMAT(post_datetime, '%H:%i') <=  '#end#') ";
$work_sql_array = getWorksql($work_sql_base);
if ($repliedby) {
  $condition = " AND post_datetime >= '{$start}' AND post_datetime < '{$end}' AND replied_by = '$repliedby' ";
} else {
  $condition = " AND post_datetime >= '{$start}' AND post_datetime < '{$end}' ";
}

$order_comment_counts  = array();
$order_comment_counts_work  = array();
$order_comment_counts_outwork  = array();
$order_comment_counts_total = 0;

$order_comment_avgtime  = array();

foreach ($work_sql_array as $work_sql) {
  $time_area = $work_sql['time'];
  $sql = "SELECT comment_cat AS type, COUNT(*) as type_count FROM {$ecs->table('order_comment')} WHERE 1=1 {$condition} {$work_sql['sql']} GROUP BY comment_cat";
  $type_comment_counts = $db->getAll($sql);
  foreach ($type_comment_counts as $type_comment_count) {
    $type = $type_comment_count['type'];
    $order_comment_counts[$time_area][$type] = $type_comment_count['type_count'];
    $order_comment_counts_total += $type_comment_count['type_count'];
    if ($work_sql['isworktime']) {
    	$order_comment_counts_work[$type] += $type_comment_count['type_count'];
    } else {
      $order_comment_counts_outwork[$type] += $type_comment_count['type_count'];
    }
  }
  $sql = " SELECT  AVG(UNIX_TIMESTAMP( reply_datetime ) - UNIX_TIMESTAMP( post_datetime )) AS avg_replied_time FROM {$ecs->table('order_comment')} WHERE reply_datetime IS NOT NULL 
{$condition} {$work_sql['sql']}  ";
  $order_comment_avgtime[$time_area] = intval($db->getOne($sql)/60);
}


$smarty->assign('order_comment_counts_work', $order_comment_counts_work);
$smarty->assign('order_comment_counts_outwork', $order_comment_counts_outwork);
$smarty->assign('order_comment_counts', $order_comment_counts);
$smarty->assign('order_comment_counts_total', $order_comment_counts_total);
$smarty->assign('order_comment_avgtime', $order_comment_avgtime);

$outwork_sql = " AND (DATE_FORMAT(post_datetime, '%H:%i') < '{$begin_work}' OR DATE_FORMAT(post_datetime, '%H:%i') >  '{$end_work}') ";
$work_sql = " AND (DATE_FORMAT(post_datetime, '%H:%i') >= '{$begin_work}' AND DATE_FORMAT(post_datetime, '%H:%i') <=  '{$end_work}') ";

$sql = " SELECT  AVG(UNIX_TIMESTAMP( reply_datetime ) - UNIX_TIMESTAMP( post_datetime )) AS avg_replied_time FROM {$ecs->table('order_comment')} WHERE reply_datetime IS NOT NULL 
{$condition} {$work_sql}  ";
$order_comment_reply_avg_time_work = $db->getOne($sql);
$smarty->assign('order_comment_reply_avg_time_work', intval($order_comment_reply_avg_time_work / 60));

$sql = " SELECT  AVG(UNIX_TIMESTAMP( reply_datetime ) - UNIX_TIMESTAMP( post_datetime )) AS avg_replied_time FROM {$ecs->table('order_comment')} WHERE reply_datetime IS NOT NULL 
{$condition} {$outwork_sql}  ";
$order_comment_reply_avg_time_outwork = $db->getOne($sql);
$smarty->assign('order_comment_reply_avg_time_outwork', intval($order_comment_reply_avg_time_outwork / 60));

//售后服务留言统计
$work_sql_base = " AND (DATE_FORMAT(apply_datetime, '%H:%i') >= '#begin#' AND DATE_FORMAT(apply_datetime, '%H:%i') <=  '#end#') ";
$work_sql_array = getWorksql($work_sql_base);
if ($repliedby) {
	$condition = " AND apply_datetime >= '{$start}' AND apply_datetime < '{$end}' AND reviewer_username = '{$repliedby}' ";
} else {
	$condition = " AND apply_datetime >= '{$start}' AND apply_datetime < '{$end}' ";
}

$service_counts  = array();
$service_counts_work  = array();
$service_counts_outwork  = array();
$service_counts_total = 0;

$service_avgtime  = array();
foreach ($work_sql_array as $work_sql) {
  $time_area = $work_sql['time'];
  $sql = "SELECT service_type AS type, COUNT(*) as type_count FROM service WHERE 1=1 {$condition} {$work_sql['sql']} GROUP BY service_type";
  $type_comment_counts = $db->getAll($sql);
  foreach ($type_comment_counts as $type_comment_count) {
    $type = $type_comment_count['type'];
    $service_counts[$time_area][$type] = $type_comment_count['type_count'];
    $service_counts_total += $type_comment_count['type_count'];
    if ($work_sql['isworktime']) {
    	$service_counts_work[$type] += $type_comment_count['type_count'];
    } else {
      $service_counts_outwork[$type] += $type_comment_count['type_count'];
    }
  }
  $sql = " SELECT  AVG(UNIX_TIMESTAMP(review_datetime ) - UNIX_TIMESTAMP( apply_datetime )) AS avg_replied_time FROM service WHERE apply_datetime IS NOT NULL AND review_datetime > apply_datetime {$condition} {$work_sql['sql']}  ";
  $service_avgtime[$time_area] = intval($db->getOne($sql) / 60);
  
}

$outwork_sql= " AND (DATE_FORMAT(apply_datetime, '%H:%i') < '{$begin_work}' OR DATE_FORMAT(apply_datetime, '%H:%i') >  '{$end_work}') ";
$work_sql = " AND (DATE_FORMAT(apply_datetime, '%H:%i') >= '{$begin_work}' AND DATE_FORMAT(apply_datetime, '%H:%i') <=  '{$end_work}') ";

$smarty->assign('service_counts_work', $service_counts_work);
$smarty->assign('service_counts_outwork', $service_counts_outwork);
$smarty->assign('service_counts', $service_counts);
$smarty->assign('service_counts_total', intval($service_counts_total));
$smarty->assign('service_avgtime', $service_avgtime);

$sql = " SELECT  AVG(UNIX_TIMESTAMP(review_datetime ) - UNIX_TIMESTAMP( apply_datetime )) AS avg_replied_time FROM service WHERE apply_datetime IS NOT NULL AND review_datetime > apply_datetime {$condition} {$work_sql}  ";
$service_reply_avg_time_work = $db->getOne($sql);
$smarty->assign('service_reply_avg_time_work', intval($service_reply_avg_time_work / 60));

$sql = " SELECT  AVG(UNIX_TIMESTAMP(review_datetime ) - UNIX_TIMESTAMP( apply_datetime )) AS avg_replied_time FROM service WHERE apply_datetime IS NOT NULL AND review_datetime > apply_datetime {$condition} {$outwork_sql}  ";
$service_reply_avg_time_outwork = $db->getOne($sql);
$smarty->assign('service_reply_avg_time_outwork', intval($service_reply_avg_time_outwork / 60));

//售后订单
$work_sql_base = " AND (DATE_FORMAT(apply_datetime, '%H:%i') >= '#begin#' AND DATE_FORMAT(apply_datetime, '%H:%i') <=  '#end#') ";
$work_sql_array = getWorksql($work_sql_base);
if ($repliedby) {
	$condition = " AND apply_datetime >= '{$start}' AND apply_datetime < '{$end}' AND reviewer_username = '{$repliedby}' ";
} else {
	$condition = " AND apply_datetime >= '{$start}' AND apply_datetime < '{$end}' ";
}

$service_order_counts  = array();
$service_order_counts_work  = array();
$service_order_counts_outwork  = array();
$service_order_counts_total = 0;
foreach ($work_sql_array as $work_sql) {
  $time_area = $work_sql['time'];
  $sql = "SELECT COUNT(DISTINCT order_id) AS type_count, service_type as type FROM service WHERE 1=1 {$condition} {$work_sql['sql']} GROUP BY service_type";
//    pp($sql);
  $type_comment_counts = $db->getAll($sql);
  foreach ($type_comment_counts as $type_comment_count) {
    $type = $type_comment_count['type'];
    $service_order_counts[$time_area][$type] = $type_comment_count['type_count'];
    $service_order_counts_total += $type_comment_count['type_count'];
    if ($work_sql['isworktime']) {
    	$service_order_counts_work[$type] += $type_comment_count['type_count'];
    } else {
      $service_order_counts_outwork[$type] += $type_comment_count['type_count'];
    }
  }
}


$smarty->assign('service_order_counts_work', $service_order_counts_work);
$smarty->assign('service_order_counts_outwork', $service_order_counts_outwork);
$smarty->assign('service_order_counts', $service_order_counts);
$smarty->assign('service_order_counts_total', $service_order_counts_total);

// 订单确认
$outwork_sql= " AND (DATE_FORMAT(o.order_time, '%H:%i') < '{$begin_work}' OR DATE_FORMAT(o.order_time, '%H:%i') >  '{$end_work}') ";
$work_sql = " AND (DATE_FORMAT(o.order_time, '%H:%i') >= '{$begin_work}' AND DATE_FORMAT(o.order_time, '%H:%i') <=  '{$end_work}') ";
$condition = " o.order_time >= '{$start}' AND o.order_time < '{$end}' ";
$sql = " SELECT COUNT(*)
		FROM {$ecs->table('order_info')} o, {$ecs->table('payment')} p
		WHERE
			$condition 
			$work_sql 
			AND EXISTS (SELECT 1 FROM {$ecs->table('order_action')} WHERE o.order_id = order_id AND pay_status != 2)
			AND LENGTH(order_sn) = 10
			AND p.pay_id = o.pay_id
			AND p.pay_code = 'cod'
			AND NOT EXISTS (
				SELECT * FROM {$ecs->table('order_info_snap')} tmos, {$ecs->table('payment')} tmp
				WHERE
					tmos.pay_id = tmp.pay_id
					AND tmos.order_id = o.order_id
					AND tmp.pay_code != 'cod'
			)	";
$order_dealed_count_work = $db->getOne($sql);
$sql = " SELECT COUNT(*)
		FROM {$ecs->table('order_info')} o, {$ecs->table('payment')} p
		WHERE
			$condition 
			$outwork_sql 
			AND EXISTS (SELECT 1 FROM {$ecs->table('order_action')} WHERE o.order_id = order_id AND pay_status != 2)
			AND LENGTH(order_sn) = 10
			AND p.pay_id = o.pay_id
			AND p.pay_code = 'cod'
			AND NOT EXISTS (
				SELECT * FROM {$ecs->table('order_info_snap')} tmos, {$ecs->table('payment')} tmp
				WHERE
					tmos.pay_id = tmp.pay_id
					AND tmos.order_id = o.order_id
					AND tmp.pay_code != 'cod'
			)	";
$order_dealed_count_outwork = $db->getOne($sql);

$smarty->assign('order_dealed_count_work', $order_dealed_count_work);
$smarty->assign('order_dealed_count_outwork', $order_dealed_count_outwork);

$sql = "SELECT AVG((SELECT UNIX_TIMESTAMP(action_time) FROM {$ecs->table('order_action')} WHERE order_id = o.order_id AND pay_status != 2 ORDER BY action_time LIMIT 1) - UNIX_TIMESTAMP(order_time))
		FROM {$ecs->table('order_info')} o, {$ecs->table('payment')} p
		WHERE
			$condition 
			$work_sql 
			AND EXISTS (SELECT 1 FROM {$ecs->table('order_action')} WHERE o.order_id = order_id AND pay_status != 2)
			AND LENGTH(order_sn) = 10
			AND p.pay_id = o.pay_id
			AND p.pay_code = 'cod'
			AND NOT EXISTS (
				SELECT * FROM {$ecs->table('order_info_snap')} tmos, {$ecs->table('payment')} tmp
				WHERE
					tmos.pay_id = tmp.pay_id
					AND tmos.order_id = o.order_id
					AND tmp.pay_code != 'cod'
			)
	";

$order_dealed_avgtime_work = $db->getOne($sql);
$sql = "SELECT AVG((SELECT UNIX_TIMESTAMP(action_time) FROM {$ecs->table('order_action')} WHERE order_id = o.order_id AND pay_status != 2 ORDER BY action_time LIMIT 1) - UNIX_TIMESTAMP(order_time))
		FROM {$ecs->table('order_info')} o, {$ecs->table('payment')} p
		WHERE
			$condition 
			$outwork_sql 
			AND EXISTS (SELECT 1 FROM {$ecs->table('order_action')} WHERE o.order_id = order_id AND pay_status != 2)
			AND LENGTH(order_sn) = 10
			AND p.pay_id = o.pay_id
			AND p.pay_code = 'cod'
			AND NOT EXISTS (
				SELECT * FROM {$ecs->table('order_info_snap')} tmos, {$ecs->table('payment')} tmp
				WHERE
					tmos.pay_id = tmp.pay_id
					AND tmos.order_id = o.order_id
					AND tmp.pay_code != 'cod'
			)
	";

$order_dealed_avgtime_outwork = $db->getOne($sql);
$smarty->assign('order_dealed_avgtime_work', intval($order_dealed_avgtime_work/60));
$smarty->assign('order_dealed_avgtime_outwork', intval($order_dealed_avgtime_outwork/60));

$smarty->assign('start', $start);
$smarty->assign('end', $end);
$smarty->assign('isoutwork', $isoutwork ? '非工作时间' : '工作时间');

$smarty->display('oukooext/analyze_comment.htm');


function getWorksql($work_sql_base) {
  global $db, $ecs, $start, $end, $worktime_array, $outworktime_array;
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