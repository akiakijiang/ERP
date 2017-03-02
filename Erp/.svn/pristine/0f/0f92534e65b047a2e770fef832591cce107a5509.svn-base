<?php
include_once("analyze_detail_header.php");

switch ($type) {
	case 'sale_service':
		$condition =  get_condition();
		$columns = array(
			'order_sn' => '订单号',
			'remark' => '评论',
			'review_remark' => '回复',
			'reviewer_name' => '回复人',
			'apply_time' => '评论时间',
			'calculate_time' => '实际计算',
			'review_time' => '回复时间',
			'reply_seconds' => '时间差',
		);
		$style = array(
			'remark' => "align='left' style='width:200px'",
			'review_remark' => "align='left' style='width:200px'",
		);
		$sql = "
			SELECT *
			FROM {$ecs->table('service')} s, {$ecs->table('order_info')} o
			WHERE
				review_time >= '{$start_date}'
				AND review_time < DATE_ADD('{$end_date}',INTERVAL 1 DAY)
				AND s.order_id = o.order_id
				{$condition['where']}
			ORDER BY s.review_time
			$limit $offset
		";
		$sqlc = "
			SELECT COUNT(*)
			FROM {$ecs->table('service')} s, {$ecs->table('order_info')} o
			WHERE
				review_time >= '{$start_date}'
				AND review_time < DATE_ADD('{$end_date}',INTERVAL 1 DAY)
				AND s.order_id = o.order_id
				{$condition['where']}
		";
		$title .= "售后咨询({$start_date}至{$end_date})";		
		break;
	case 'mid_comment':
		$condition =  get_condition();
		$columns = array(
			'order_sn' => '订单号',
			'comment' => '评论',
			'reply' => '回复',
			'replied_by' => '回复人',
			'post_datetime' => '评论时间',
			'calculate_time' => '实际计算',
			'reply_datetime' => '回复时间',
			'reply_seconds' => '时间差',
		);
		$style = array(
			'comment' => "align='left' style='width:200px'",
			'reply' => "align='left' style='width:200px'",
		);
		$sql = "
			SELECT *
			FROM {$ecs->table('order_comment')} c , {$ecs->table('order_info')} o
			WHERE 
				UNIX_TIMESTAMP(reply_datetime) >= UNIX_TIMESTAMP('{$start_date}')
				AND UNIX_TIMESTAMP(reply_datetime) < UNIX_TIMESTAMP(DATE_ADD('{$end_date}',INTERVAL 1 DAY))
				AND status = 'OK'
				AND c.order_id = o.order_id
				{$condition['where']}
			ORDER BY c.reply_datetime
			$limit $offset
		";
		$sqlc = "
			SELECT COUNT(*)
			FROM {$ecs->table('order_comment')} c , {$ecs->table('order_info')} o
			WHERE 
				UNIX_TIMESTAMP(reply_datetime) >= UNIX_TIMESTAMP('{$start_date}')
				AND UNIX_TIMESTAMP(reply_datetime) < UNIX_TIMESTAMP(DATE_ADD('{$end_date}',INTERVAL 1 DAY))
				AND status = 'OK'
				AND c.order_id = o.order_id
				{$condition['where']}	
		";
		$title .= "售中咨询({$start_date}至{$end_date})";
		break;
	default:
		die("非法参数");
}
include_once("analyze_detail_footer.php");
?>
<?php
function data_handle($all_data) {
	global $type, $ecs, $start_date, $end_date, $condition;
	if (!is_array($all_data)) return;
	
	foreach ($all_data as $key => $data) {
		// 处理订单号
		if ($all_data[$key]['order_sn'] !== null && $all_data[$key]['order_id'] !== null) {
			$all_data[$key]['order_sn'] = "<a href='detail_info.php?order_id={$all_data[$key]['order_id']}' target='_blank'>{$all_data[$key]['order_sn']}</a><br>{$all_data[$key]['order_time']}";
		}
		
		// 回复计算去除休息时间
		switch ($type) {
			case 'sale_service':
				$all_data[$key]['calculate_time'] = remove_break_time($all_data[$key]['apply_time']);
				$dif_time = calculate_dif_time($all_data[$key]['calculate_time'], $all_data[$key]['review_time']);
				if ($dif_time > 0) {
					$all_data[$key]['reply_seconds'] = number_format($dif_time / 60, 2, '.', '');
				} else {
					$all_data[$key]['reply_seconds'] = 0;
				}
				break;
			case 'mid_comment':
				$all_data[$key]['calculate_time'] = remove_break_time($all_data[$key]['post_datetime']);
				$dif_time = calculate_dif_time($all_data[$key]['calculate_time'], $all_data[$key]['reply_datetime']);
				if ($dif_time > 0) {
					$all_data[$key]['reply_seconds'] = number_format($dif_time / 60, 2, '.', '');
				} else {
					$all_data[$key]['reply_seconds'] = 0;
				}				
				break;
		}
	}
	return $all_data;
}

function get_condition() {
	global $title, $ecs, $columns, $type;
	
	$user_name = trim($_REQUEST['user_name']);
	
	if ($user_name != '') {
		switch ($type) {
			case 'sale_service':
				$where_condition .= " AND reviewer_name = '{$user_name}'";
				$title .= "{$user_name} ";
				break;
			case 'mid_comment':
				$where_condition .= " AND replied_by = '{$user_name}'";
				$title .= "{$user_name} ";
				break;
		}
	}
	
	$condition['where'] = $where_condition;
	return $condition;
}


/**
 * 过滤时间非工作时间：周一 － 周五：10：00-21：00、周六、周日：10：30-20：00
 *
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
 * 工作时间：周一 至 周五：10：00-21：00、周六、周日：10：30-20：00
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