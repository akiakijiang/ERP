<?php
/**
 * 降价提醒统计 单个商品
 * @author ncchen 090216
 */
define('IN_ECS', true);

require('includes/init.php');
require("function.php");
admin_priv('tj_analyze_pricecut_inform');
$start = $_REQUEST['start'];
$end = $_REQUEST['end'];
$goods_id = $_REQUEST['goods_id'];
$style_id = $_REQUEST['style_id'];
if (!$start || !$end || !$goods_id || !$style_id) {
	return;
}
$smarty->assign('start', $start);
$smarty->assign('end', $end);

$sql_change = " SELECT APPROVED_DATETIME, PRICE, IF(gs.price_range = 0, g.price_range, gs.price_range) AS price_range, g.goods_id,
									CONCAT_WS(' ', g.goods_name, IF(gs.goods_color = '', s.color, gs.goods_color)) AS goods_name
								FROM PRICE_TRACKER.SHOP_PRICE sp
									LEFT JOIN {$ecs->table('goods')} g ON sp.GOODS_ID = g.goods_id
									LEFT JOIN {$ecs->table('goods_style')} gs ON gs.style_id = $style_id AND gs.goods_id = sp.GOODS_ID AND sp.goods_style_id = gs.goods_style_id
									LEFT JOIN {$ecs->table('style')} s ON s.style_id = gs.style_id
								WHERE STATUS = 'OK' AND sp.GOODS_ID = $goods_id AND APPROVED_DATETIME >= '$start' AND APPROVED_DATETIME < '$end' AND IF($style_id, gs.style_id IS NOT NULL, sp.goods_style_id = 0)
								ORDER BY APPROVED_DATETIME
								";
$price_changes = $slave_db->getAll($sql_change);
$sql_group = " GROUP BY pi.price_inform ";
$condition = getCondition();

foreach ($price_changes as $change_key => $change) {
	if ($change_key == 0) {
		$price_changes[$change_key]['last_changed'] = $date_fir = $start;
	} else {
		$price_changes[$change_key]['last_changed'] = $date_fir = $date_sec;
	}
	
	$date_sec = $change['APPROVED_DATETIME'];
	$sql_price = "SELECT MAX(shop_price) AS shop_price
							 FROM {$ecs->table('pricecut_inform')} pi 
						   WHERE is_deal != 2 AND is_deal != 3 AND pi.date > '$date_fir' AND pi.date < '$date_sec' $condition ";
	if ($change['price_range'] == 0) {
		$price_changes[$change_key]['price_range'] = $change['price_range'] = 10;
	}
	$price_changes[$change_key]['shop_price'] = $change['shop_price'] = $last_price ? $last_price : $slave_db->getOne($sql_price);
	$last_price = $change['PRICE'];
	$price_changes[$change_key]['range'] = array();
	for ($i = 0;$i < 6 && $change['shop_price']-$change['price_range']*$i > 0; ++$i) {
		$price_changes[$change_key]['range'][$change['shop_price']-$change['price_range']*$i] = array("set" => 0, "buy" => 0);
	}
	$price_changes[$change_key]['sum_base'] = 0;
	$sql_base = "SELECT COUNT(pi.id) AS sum_base, price_inform,
								IF(pi.style_id = 0, -1, pi.style_id) AS group_style_id, pi.date
							 FROM {$ecs->table('pricecut_inform')} pi 
						   WHERE is_deal != 2 AND is_deal != 3 AND pi.date > '$date_fir' AND pi.date < '$date_sec' $condition ";
	$goods_lists = $slave_db->getAll($sql_base. $sql_group);
	
	foreach ($goods_lists as $goods) {
		$price_changes[$change_key]['sum_base'] += $goods['sum_base'];
		foreach ($price_changes[$change_key]['range'] AS $key => $range) {
	//		pp($key + $goods['price_range'], $goods['price_inform'], $key, $goods['price_range']);
			if ($key - $change['price_range'] < $goods['price_inform'] && $key >= $goods['price_inform']) {
				$price_changes[$change_key]['range'][$key]['set'] += $goods['sum_base'];
				break;
			}
		}
	}
	
	$sql_buy = "SELECT COUNT(pi.id) AS sum_buy, pi.price_inform  
							 FROM {$ecs->table('pricecut_inform')} pi 
							WHERE pi.is_deal = 1 AND pi.date > '$date_fir' AND pi.date < '$date_sec' $condition
								AND EXISTS(
									SELECT 1
									FROM {$ecs->table('order_info')} oi
									INNER JOIN {$ecs->table('order_goods')} og ON oi.order_id = og.order_id 
									WHERE oi.user_id = pi.uid AND oi.order_status = 1 AND oi.order_time > pi.action_time
									AND og.goods_id = pi.goods_id AND (og.style_id = pi.style_id OR og.style_id = 0)
								)
						";
	$range_buy = $slave_db->getAll($sql_buy.$sql_group);
	$sum_buy = 0;
	foreach ($range_buy as $buy) {
		foreach ($price_changes[$change_key]['range'] AS $key => $range) {
	//		pp($key + $goods['price_range'], $goods['price_inform'], $key, $goods['price_range']);
			if ($key - $change['price_range'] < $buy['price_inform'] && $key >= $buy['price_inform']) {
				$price_changes[$change_key]['range'][$key]['buy'] += $buy['sum_buy'];
				$sum_buy += $buy['sum_buy'];
				break;
			}
		}
	}
	foreach ($price_changes[$change_key]['range'] AS $key => $range) {
		$price_changes[$change_key]['range'][$key]['stat'] = $range['buy'] / ($range['set']?$range['set']:1)*100;
	}
	$sql_inform = "SELECT COUNT(pi.id) 
									FROM {$ecs->table('pricecut_inform')} pi
								 WHERE is_deal = 1 AND pi.date > '$date_fir' AND pi.date < '$date_sec' $condition ";
	$sum_inform = $slave_db->getOne($sql_inform);

	$price_changes[$change_key]['sum_inform'] = $sum_inform;
	$price_changes[$change_key]['sum_buy'] = $sum_buy;
	$price_changes[$change_key]['stat_buy'] = $sum_buy / ($price_changes[$change_key]['sum_base']?$price_changes[$change_key]['sum_base']:1) * 100;
}

//pp($price_changes);die();

$smarty->assign('back', $_SERVER['HTTP_REFERER']);
$smarty->assign('price_changes', $price_changes);
$smarty->display('oukooext/analyze_pricecut_inform_detail.htm');

function getCondition() {
  global $smarty;
  $goods_id = $_REQUEST['goods_id'];
  $style_id = $_REQUEST['style_id'];
  
	if ($goods_id) {
		$condition .= " AND pi.goods_id = $goods_id ";
	}
	if ($style_id) {
		$condition .= " AND pi.style_id = $style_id ";
	}
  return $condition; 
}