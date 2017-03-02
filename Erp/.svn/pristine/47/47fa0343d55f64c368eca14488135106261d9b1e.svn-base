<?php
/**
 * 用户订单统计
 */
define('IN_ECS', true);

require('includes/init.php');
require("function.php");
admin_priv('tj_analyze_user');
$start = $_REQUEST['start'];
$end = $_REQUEST['end'];

$one_day_seconds = 60 * 60 * 24;
$two_day_seconds = $one_day_seconds * 2;
$ten_day_seconds = $one_day_seconds * 10;

$hour = $_REQUEST['hour'] > 0 ? intval($_REQUEST['hour']) : 24;
$order_amount_end = $_REQUEST['order_amount_end'] > 0 ? $_REQUEST['order_amount_end'] : 1000;
$order_amount_begin = ($_REQUEST['order_amount_begin'] && $_REQUEST['order_amount_begin'] < $order_amount_end) ? $_REQUEST['order_amount_begin'] : 0;

if (!strtotime($start)) {
	$start = date("Y-m-d", strtotime("-1 week"));
}
if (!strtotime($end)) {
	$end = date("Y-m-d");
}
if (strtotime($start)+31*24*3600 < strtotime($end)) {
	$end = date("Y-m-d", strtotime($start)+31*24*3600);
}
// 得到一系列的日期
$dates = get_dates($start, $end);
//pp($start, $end);die();
//一段时间内注册咨询用户
/*$sql = " SELECT COUNT(DISTINCT u.user_name) FROM {$ecs->table('users')} u 
        INNER JOIN bj_comment c ON u.userId = c.user_id 
        WHERE reg_time > UNIX_TIMESTAMP('$start') 
              AND reg_time < UNIX_TIMESTAMP('$end') 
			        AND c.post_datetime > '$start' AND UNIX_TIMESTAMP(c.post_datetime) < (u.reg_time + ".$hour." * 3600) ";*/
//ncchen 090304
$sql = " SELECT COUNT(*) FROM {$ecs->table('users')} u
					WHERE u.reg_time >= UNIX_TIMESTAMP('$start')
						AND u.reg_time <= UNIX_TIMESTAMP('$end')
						AND EXISTS(
							SELECT 1 FROM bj_comment c 
							WHERE u.userId = c.user_id
							AND UNIX_TIMESTAMP(c.post_datetime) <= (u.reg_time + ".$hour." * 3600)
						)
			 ";
//pp($sql);die();
$range_register_user_bjcomment_count = $slave_db->getOne($sql);

foreach ($dates as $date) {
  $Idate = strtotime($date);
  $Idate_after_hour = $Idate + $hour * 3600;
  $date_after_hour = date("Y-m-d", $Idate_after_hour);
  $Idate_after_oneday = $Idate + 24 * 3600;
  $date_after_oneday = date("Y-m-d", $Idate_after_oneday);
  
	$values[$date] = array();
	
	// PV数
	$sql = "
		SELECT attr_value FROM {$ecs->table('oukoo_analyze')} a, {$ecs->table('oukoo_analyze_attribute')} aa
		WHERE 
			a.attr_id = aa.attr_id
			AND aa.attr_code = 'PV'
			AND a.refer_time = '{$date}'
	";
	$values[$date]['pv'] = $slave_db->getOne($sql);
	$sum['pv'] += $values[$date]['pv'];
	if ($values[$date]['pv'] != null) {
		$sum['pv_days']++;
	}
	
	// IP数
	$sql = "
		SELECT attr_value FROM {$ecs->table('oukoo_analyze')} a, {$ecs->table('oukoo_analyze_attribute')} aa
		WHERE 
			a.attr_id = aa.attr_id
			AND aa.attr_code = 'IP'
			AND a.refer_time = '{$date}'
	";
	$values[$date]['ip'] = $slave_db->getOne($sql);
	$sum['ip'] += $values[$date]['ip'];
	if ($values[$date]['ip'] != null) {
		$sum['ip_days']++;
	}	
		
	// 注册用户数
	$sql = "
		SELECT COUNT(*) FROM {$ecs->table('users')}
		WHERE reg_time > UNIX_TIMESTAMP('$date') 
			AND reg_time < (UNIX_TIMESTAMP('$date') +  ".$hour." * 3600)
	";
	$values[$date]['register_user_count'] = $slave_db->getOne($sql);
	$sum['register_user_count'] += $values[$date]['register_user_count'];
	// 注册用户数end
	
	//注册咨询用户
	/*$sql = " SELECT COUNT(DISTINCT u.user_name) FROM {$ecs->table('users')} u 
        INNER JOIN bj_comment c ON u.userId = c.user_id 
        WHERE reg_time > UNIX_TIMESTAMP('$date') 
			         AND reg_time < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY))
			         AND c.post_datetime > '$date' AND UNIX_TIMESTAMP(c.post_datetime) < (u.reg_time + ".$hour." * 3600) ";*/
	//ncchen 090304
	$sql = "SELECT COUNT(*)
					FROM {$ecs->table('users')} u
					WHERE u.reg_time >= UNIX_TIMESTAMP('$date')
						AND u.reg_time <= UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY))
						AND EXISTS(
							SELECT 1 FROM bj_comment c
							WHERE u.userId = c.user_id 
							 AND UNIX_TIMESTAMP(c.post_datetime) <= (u.reg_time + ".$hour." * 3600)
						)
			";
	$values[$date]['register_user_bjcomment_count'] = $slave_db->getOne($sql);
	$sum['register_user_bjcomment_count'] += $values[$date]['register_user_bjcomment_count'];
	
	//注册咨询后下单用户
	$sql = " SELECT COUNT(DISTINCT u.user_name) FROM {$ecs->table('users')} u 
        INNER JOIN bj_comment c ON u.userId = c.user_id 
        WHERE reg_time > UNIX_TIMESTAMP('$date') 
			         AND reg_time < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY))
			         AND c.post_datetime > '$date' AND UNIX_TIMESTAMP(c.post_datetime) <  (u.reg_time + ".$hour." * 3600)
			         AND EXISTS(
          				SELECT 1 FROM {$ecs->table('order_info')} o 
          				WHERE 
          					o.user_id = u.user_id 
          					AND o.order_type_id = 'SALE'
          					AND order_time > c.post_datetime
          					AND order_time < FROM_UNIXTIME(u.reg_time + ".$hour." * 3600)
          					AND ". party_sql('o.party_id') ."
          			)
			         ";
	$values[$date]['register_user_bjcomment_order_count'] = $slave_db->getOne($sql);
	$sum['register_user_bjcomment_order_count'] += $values[$date]['register_user_bjcomment_order_count'];
	
	//注册咨询后并未下单数
	$sql = " SELECT COUNT(DISTINCT u.user_name) FROM {$ecs->table('users')} u 
        INNER JOIN bj_comment c ON u.userId = c.user_id 
        WHERE reg_time > UNIX_TIMESTAMP('$date') 
			         AND reg_time < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY))
			         AND c.post_datetime > '$date' AND UNIX_TIMESTAMP(c.post_datetime) <  (u.reg_time + ".$hour." * 3600)
			         AND NOT EXISTS(
          				SELECT 1 FROM {$ecs->table('order_info')} o 
          				WHERE 
          					o.user_id = u.user_id 
          					AND o.order_type_id = 'SALE'
          					AND order_time > c.post_datetime
          					AND order_time < FROM_UNIXTIME(u.reg_time + ".$hour." * 3600)
          					AND ". party_sql('o.party_id') ."
          			)
			         ";
	$values[$date]['register_user_bjcomment_noorder_count'] = $slave_db->getOne($sql);
	$sum['register_user_bjcomment_noorder_count'] += $values[$date]['register_user_bjcomment_noorder_count'];
	
	//未咨询就下订单数
	$sql = "
	    SELECT 
	        COUNT(DISTINCT o.order_sn) 
	    FROM 
	        {$ecs->table('order_info')} o 
            INNER JOIN {$ecs->table('users')} u ON o.user_id = u.user_id
        WHERE
            o.order_type_id = 'SALE' AND ". party_sql('o.party_id') ." AND 
            o.order_time >= '$date' AND o.order_time < '$date_after_oneday' AND
            NOT EXISTS (SELECT 1 FROM bj_comment c WHERE c.user_id = u.userId AND c.post_datetime < o.order_time)
	";
	$values[$date]['register_user_nobjcomment_order_count'] = $slave_db->getOne($sql);
	$sum['register_user_nobjcomment_order_count'] += $values[$date]['register_user_nobjcomment_order_count'];
		
	// 注册下单会员数
	$sql = "
		SELECT COUNT(*) FROM {$ecs->table('users')} u
		WHERE reg_time > UNIX_TIMESTAMP('$date') 
			AND reg_time < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY))
			AND EXISTS(
				SELECT 1 FROM {$ecs->table('order_info')} o 
				WHERE 
					o.user_id = u.user_id 
					AND o.order_type_id = 'SALE'
					AND order_time > '$date'
					AND order_time < FROM_UNIXTIME(u.reg_time + ".$hour." * 3600)
					AND ". party_sql('o.party_id') ."
			)
	";
	$values[$date]['register_user_order_count'] = $slave_db->getOne($sql);
	$sum['register_user_order_count'] += $values[$date]['register_user_order_count'];	
	// end注册下单会员数
	
	//注册下单时间
  $sql = " SELECT AVG(UNIX_TIMESTAMP(o.order_time) - u.reg_time) FROM {$ecs->table('users')} u
          INNER JOIN {$ecs->table('order_info')} o ON u.user_id =  o.user_id 
          WHERE
            u.reg_time > UNIX_TIMESTAMP('$date') 
      			AND u.reg_time < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY))
      			AND o.order_time >= '$date'
      			AND o.order_time < FROM_UNIXTIME(u.reg_time + ".$hour." * 3600) 
      			AND ". party_sql('o.party_id');
  $values[$date]['register_user_order_avgtime'] = intval($slave_db->getOne($sql) / 60);
  $sum['register_user_order_avgtime'] += $values[$date]['register_user_order_avgtime'];
  
	// 注册并未下单会员数
	$sql = "
		SELECT COUNT(*) FROM {$ecs->table('users')} u
		WHERE reg_time > UNIX_TIMESTAMP('$date') 
			AND reg_time < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY))
			AND NOT EXISTS(
				SELECT 1 FROM {$ecs->table('order_info')} o 
				WHERE 
					o.user_id = u.user_id 
					AND o.order_type_id = 'SALE'
					AND order_time > '$date'
					AND order_time < FROM_UNIXTIME(u.reg_time + ".$hour." * 3600)
					AND ". party_sql('o.party_id') ."
			)
	";
	$values[$date]['register_user_noorder_count'] = $slave_db->getOne($sql);
	$sum['register_user_noorder_count'] += $values[$date]['register_user_noorder_count'];	
	// end注册并未下单会员数

	// 注册下单取消会员数
	$sql = "
		SELECT COUNT(*) FROM {$ecs->table('users')} u
		WHERE reg_time > UNIX_TIMESTAMP('$date') 
			AND reg_time < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY))
			AND EXISTS(
				SELECT 1 FROM {$ecs->table('order_info')} o 
				WHERE 
					o.user_id = u.user_id 
					AND o.order_type_id = 'SALE'
					AND order_time > '$date'
					AND order_time < FROM_UNIXTIME(u.reg_time + ".$hour." * 3600)
					AND o.order_status = '" . OS_CANCELED . "'
					AND ". party_sql('o.party_id') ."
			)
	";
	$values[$date]['register_user_cancelorder_count'] = $slave_db->getOne($sql);
	$sum['register_user_cancelorder_count'] += $values[$date]['register_user_cancelorder_count'];	
	// end注册下单取消会员数
	
	// 注册下单确认会员数
	$sql = "
		SELECT COUNT(*) FROM {$ecs->table('users')} u
		WHERE
		1
		AND reg_time > UNIX_TIMESTAMP('$date')
		AND reg_time < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY))
		AND EXISTS(
				SELECT 1 FROM {$ecs->table('order_info')} o
				WHERE
				o.user_id = u.user_id
				AND o.order_type_id = 'SALE'
				AND order_status = 1
				AND order_time > '$date'
				AND order_time < FROM_UNIXTIME(u.reg_time + ".$hour." * 3600)
				AND ". party_sql('o.party_id') ."
			  )
	";

	$values[$date]['register_user_confirm_order_count'] = $slave_db->getOne($sql);
	$sum['register_user_confirm_order_count'] += $values[$date]['register_user_confirm_order_count'];	
	// end注册下单确认会员数
	
	// 售前的用户数
	$sql = "
		SELECT COUNT(user_id)
			FROM bj_comment
		WHERE 
			status = 'OK' 
			AND post_datetime > ('$date') 
			AND post_datetime < DATE_ADD('$date',INTERVAL 1 DAY) 
	";
	$comment_user_count = $slave_db->getOne($sql);
	
	// 售前的用户数(按类型)
	$sql = "
		SELECT type, COUNT(comment_id) AS type_count
			FROM bj_comment
		WHERE 
			status = 'OK' 
			AND post_datetime > ('$date') 
			AND post_datetime < DATE_ADD('$date',INTERVAL 1 DAY) 
		GROUP BY type
	";
	$comment_users = $slave_db->getAll($sql);

	// 咨询后下单数（留言转换率）
	$sql = "
		SELECT COUNT(DISTINCT o.order_id) FROM bj_comment c
		INNER JOIN {$ecs->table('users')} u ON c.user_id = u.userId
		INNER JOIN {$ecs->table('order_info')} o ON o.user_id = u.user_id
		  WHERE			
				o.order_type_id = 'SALE'
				AND c.status = 'OK'
				AND o.order_time >= '{$date}'
				AND o.order_time >= c.replied_datetime
				AND o.order_time <= DATE_ADD(c.replied_datetime, INTERVAL 1 DAY) 
				AND c.post_datetime >= '{$date}'
				AND c.post_datetime <= DATE_ADD('{$date}', INTERVAL 1 DAY)
				AND replied_by != '' AND store_id = 0 
				AND o.order_status = 1
				AND ". party_sql('o.party_id') ."
	";
//	pp($sql);die();
	$comment_user_order_count = $slave_db->getOne($sql);
	$values[$date]['comment_user_order_count'] = $comment_user_count > 0 ? sprintf('%.2f', ($comment_user_order_count / $comment_user_count * 100)) : '';
	// 咨询后下单数（留言转换率）按类型
	$sql = "
		SELECT c.type, COUNT(DISTINCT o.order_id) AS type_count FROM bj_comment c
		INNER JOIN {$ecs->table('users')} u ON c.user_id = u.userId
		INNER JOIN {$ecs->table('order_info')} o ON o.user_id = u.user_id
		  WHERE			
				o.order_type_id = 'SALE'
				AND c.status = 'OK'
				AND o.order_time > '{$date}'
				AND o.order_time > c.replied_datetime
				AND o.order_time < DATE_ADD(c.replied_datetime, INTERVAL 1 DAY) 
				AND c.post_datetime > '{$date}'
				AND c.post_datetime < DATE_ADD('{$date}', INTERVAL 1 DAY)
				AND o.order_status = 1
				AND ". party_sql('o.party_id') ."
		GROUP BY c.type
	";
	$comment_user_order = $slave_db->getAll($sql);
//	pp($comment_user_order);
	foreach ($comment_users as $c) {
	  $type = $c['type'];
	  $temp_count = 0;
	  foreach ($comment_user_order as $o) {
	    if ($o['type'] == $type) {
	    	$temp_count = $o['type_count'];
	    	break;
	    }
	  }
	  $values[$date]['comment_user_order'][$type] = $c['type_count'] > 0 
	                                     ? sprintf('%.2f', ($temp_count / $c['type_count'] * 100))
	                                     : '';
	  $sum['comment_user_order'][$type]['temp_count'] += $c['temp_count'];
	  $sum['comment_user_order'][$type]['type_count'] += $c['type_count'];
	}
//	pp($values[$date]['comment_user_order']);
	

	// 订单数
	$sql = "
		SELECT *,
			(SELECT SUM(goods_number) FROM {$ecs->table(order_goods)} g WHERE g.order_id = o.order_id) goods_number,
			(EXISTS(SELECT 1 FROM {$ecs->table(order_goods)} g WHERE g.order_id = o.order_id AND g.parent_id != 0)) is_binding
		FROM {$ecs->table('order_info')} o 
		WHERE 
			order_time > '$date' 
			AND order_time < '$date_after_oneday'
			AND o.order_type_id = 'SALE'
			AND ". party_sql('o.party_id') ;
	$orders = $slave_db->getAll($sql);

	foreach ($orders as $order) {
		$values[$date]['order_count']++;
		$sum['order_count']++;
		
		//老用户订单
		$sql = "SELECT COUNT(*) FROM {$ecs->table('order_info')} WHERE user_id = '{$order['user_id']}' AND order_time < '' AND order_status = 1 AND ". party_sql('party_id');
		$is_olduser = $slave_db->getOne($sql);
		if ($is_olduser) {
			$values[$date]['olduser_order_count']++;
  		$sum['olduser_order_count']++;
		}		
		//end老用户订单
		
		// 确认订单
		if ($order['order_status'] == OS_CONFIRMED) {
			$values[$date]['confirm_order_count']++;
			$sum['confirm_order_count']++;
			// 确认的欧酷/镖局订单
			if ($order['biaoju_store_id'] != 0 && $order['biaoju_store_id'] != 7) {
				$values[$date]['biaoju_confirm_order_count']++;
				$sum['biaoju_confirm_order_count']++;			
			} else {
				$values[$date]['ouku_confirm_order_count']++;
				$sum['ouku_confirm_order_count']++;
				$values[$date]['ouku_confirm_order_amount'] += $order['order_amount'];
				$sum['ouku_confirm_order_amount'] += $order['order_amount'];
			}
			// end确认的欧酷/镖局订单	
		}
		// end确认订单
		
		// 未确认订单
		if ($order['order_status'] == OS_UNCONFIRMED) {
			$values[$date]['unconfirm_order_count']++;
			$sum['unconfirm_order_count']++;
			// 未确认的欧酷/镖局订单
			if ($order['biaoju_store_id'] != 0 && $order['biaoju_store_id'] != 7) {
				$values[$date]['biaoju_unconfirm_order_count']++;
				$sum['biaoju_unconfirm_order_count']++;			
			} else {
				$values[$date]['ouku_unconfirm_order_count']++;
				$sum['ouku_unconfirm_order_count']++;
			}
			// end未确认的欧酷/镖局订单	
		}
		// end未确认订单
		
		// 取消订单
		if ($order['order_status'] == OS_CANCELED) {
			$values[$date]['cancel_order_count']++;
			$sum['cancel_order_count']++;
			// 确认的欧酷/镖局订单
			if ($order['biaoju_store_id'] != 0 && $order['biaoju_store_id'] != 7) {
				$values[$date]['biaoju_cancel_order_count']++;
				$sum['biaoju_cancel_order_count']++;			
			} else {
				$values[$date]['ouku_cancel_order_count']++;
				$sum['ouku_cancel_order_count']++;
			}
			// end确认的欧酷/镖局订单	
		}
		// end取消订单
				
		// 拒收订单
		if ($order['order_status'] == OS_RETURNED) {
			$values[$date]['returned_order_count']++;
			$sum['returned_order_count']++;
			// 拒收的欧酷/镖局订单
			if ($order['biaoju_store_id'] != 0 && $order['biaoju_store_id'] != 7) {
				$values[$date]['biaoju_returned_order_count']++;
				$sum['biaoju_returned_order_count']++;			
			} else {
				$values[$date]['ouku_returned_order_count']++;
				$sum['ouku_returned_order_count']++;
			}
			// end拒收的欧酷/镖局订单	
		}
		// end拒收订单
		
		// 欧酷/镖局订单
		if ($order['biaoju_store_id'] != 0 && $order['biaoju_store_id'] != 7) {
			$values[$date]['biaoju_order_count']++;
			$sum['biaoju_order_count']++;			
		} else {
			$values[$date]['ouku_order_count']++;
			$sum['ouku_order_count']++;			
		}
		// end欧酷/镖局订单
		
		// 商品数大于一个的订单
		if ($order['goods_number'] > 1) {
			$values[$date]['more_than_one_order_count']++;
			$sum['more_than_one_order_count']++;
		}
		// end商品数大于一个的订单
		
		//订单价格在选择的区间内。
		if ($order['order_amount'] >= $order_amount_begin && $order['order_amount'] < $order_amount_end) {
			$values[$date]['order_in_amount_count']++;
			$sum['order_in_amount_count']++;
		}
		//end订单价格在选择的区间内。
		
		// 含有套餐的订单
		if ($order['is_binding'] > 0) {
			$values[$date]['binding_order_count']++;
			$sum['binding_order_count']++;
		}
		// end商品数大于一个的订单
		
		// 上海地区订单
		if ($order['province'] == 10) {
			$values[$date]['sh_order_count']++;
			$sum['sh_order_count']++;
		}		
		// end上海地区订单
		
		// 使用红包订单
		if ($order['bonus'] != 0) {
			$values[$date]['bonus_order_count']++;
			$sum['bonus_order_count']++;			
		}
		// end使用红包订单
	}
	// end订单数
};

//foreach ($slave_db->queryLog as $value) {
//  print $value;
//  pp($slave_db->getAll("explain $value "));
//}

// 获取全局信息
$sql = "SELECT COUNT(*) FROM {$ecs->table('users')}";
$sum['total_user_count'] = $slave_db->getOne($sql);

$sql = "SELECT COUNT(*) FROM {$ecs->table('order_info')} WHERE order_status = 1 AND order_type_id = 'SALE' AND ". party_sql('party_id');
$sum['total_confirm_count'] = $slave_db->getOne($sql);
// end获取全局信息

$bjtype_mapping = array('goods'=>'商品咨询', 'shipping'=>'物流配送', 'payment'=>'支付问题', 'postsale'=>'保修及发票', 'complaint'=>'投诉建议');

$smarty->assign('start', $dates[count($dates) - 1]);
$smarty->assign('end', $dates[0]);
$smarty->assign('hour', $hour);
$smarty->assign('order_amount_begin', $order_amount_begin);
$smarty->assign('order_amount_end', $order_amount_end);
$smarty->assign('values', $values);
$smarty->assign('dates', array_reverse($dates));
$smarty->assign('dates_count', count($dates));
$smarty->assign('bjtype_mapping', $bjtype_mapping);
$smarty->assign('sum', $sum);
$range_register_user_bjcomment_percent = $sum['register_user_count'] > 0 
                                         ? sprintf('%.2f', $range_register_user_bjcomment_count / $sum['register_user_count'] * 100). "%"
                                         : '';
$smarty->assign('range_register_user_bjcomment_percent', $range_register_user_bjcomment_percent);
$smarty->assign('range_register_user_bjcomment_count', $range_register_user_bjcomment_count);
if ($_REQUEST['view'] == 'old') {
	$smarty->display('oukooext/analyze_user.htm');
} else {
	$smarty->display('oukooext/analyze_user_new.htm');
}

?>
