<?php
/**
 * 降价提醒统计
 * @author ncchen 090207
 */
define('IN_ECS', true);

require('includes/init.php');
require("function.php");
party_priv(PARTY_OUKU);
admin_priv('tj_analyze_pricecut_inform');
$start = $_REQUEST['start'];
$end = $_REQUEST['end'];

$price_mapping = array();
$price_mapping[$price_range] = $i * $price_range;

$price_mapping = array(
	'all' 	=> '所有',
	'500' 	=> '0-500',
	'1000' 	=> '500-1000',
	'1500' 	=> '1000-1500',
	'2000' 	=> '1500-2000',
	'2500' 	=> '2000-2500',
	'3000' 	=> '2500-3000',
	'above'	=> '3000以上',
	);

$sql_group = " GROUP BY pi.goods_id, group_style_id, pi.price_inform ";
$sql_order = " ORDER BY g.top_cat_id, goods_name ";
$sql_having = getConditionHaving();
$condition = getCondition();
$condition_name = getConditionName();
$sql_base = "SELECT COUNT(pi.id) AS sum_base, pi.goods_id, pi.style_id, price_inform,
								IF(pi.style_id = 0, -1, pi.style_id) AS group_style_id, 
								IFNULL(gs.style_price, g.shop_price) AS price, IF(gs.price_range = 0, g.price_range, gs.price_range) AS price_range,
								CONCAT_WS(' ', g.goods_name, IF(gs.goods_color = '', s.color, gs.goods_color)) AS goods_name,
								IFNULL(gs.sale_status, g.sale_status) AS sale_status
							FROM {$ecs->table('pricecut_inform')} pi 
							LEFT JOIN {$ecs->table('goods')} g ON pi.goods_id = g.goods_id
							LEFT JOIN {$ecs->table('goods_style')} gs ON pi.style_id = gs.style_id AND gs.goods_id = pi.goods_id
							LEFT JOIN {$ecs->table('style')} s ON s.style_id = gs.style_id
						 WHERE is_deal != 2 AND is_deal != 3 $condition_name $condition ";

$goods_lists = $slave_db->getAll($sql_base. $sql_group. $sql_having. $sql_order);

$goods_list = array();
foreach ($goods_lists as $goods_key => $goods) {
	if ($goods['price_range'] == 0) {
		$goods_lists[$goods_key]['price_range'] = $goods['price_range'] = 10;
	}
	if (empty($goods_list["{$goods['goods_id']}_{$goods['group_style_id']}"])) {
		$goods_list["{$goods['goods_id']}_{$goods['group_style_id']}"] = $goods;
		for ($i = 0;$i < 6 && $goods['price']-$goods['price_range']*$i > 0; ++$i) {
			$goods_list["{$goods['goods_id']}_{$goods['group_style_id']}"]['range'][$goods['price']-$goods['price_range']*$i] = 0;
		}
		$goods_list["{$goods['goods_id']}_{$goods['group_style_id']}"]['sum_base'] = 0;
	}
	$goods_list["{$goods['goods_id']}_{$goods['group_style_id']}"]['sum_base'] += $goods['sum_base'];
	foreach ($goods_list["{$goods['goods_id']}_{$goods['group_style_id']}"]['range'] AS $key => $range) {
//		pp($key + $goods['price_range'], $goods['price_inform'], $key, $goods['price_range']);
		if ($key - $goods['price_range'] < $goods['price_inform'] && $key >= $goods['price_inform']) {
			$goods_list["{$goods['goods_id']}_{$goods['group_style_id']}"]['range'][$key] += $goods['sum_base'];
			break;
		}
	}
}
//pp($goods_list);die();
$sum_base = 0;
foreach ($goods_list as $key => $goods) {
	
	$sql_inform = "SELECT COUNT(pi.id) 
									FROM {$ecs->table('pricecut_inform')} pi
								 WHERE is_deal = 1 AND pi.goods_id = {$goods['goods_id']} AND pi.style_id = {$goods['style_id']} $condition ";
	$sum_inform = $slave_db->getOne($sql_inform);
	$sql_buy = "SELECT COUNT(pi.id) 
							 FROM {$ecs->table('pricecut_inform')} pi 
							WHERE pi.is_deal = 1 AND pi.goods_id = {$goods['goods_id']} AND pi.style_id = {$goods['style_id']} $condition
								AND EXISTS(
									SELECT 1
									FROM {$ecs->table('order_info')} oi
									INNER JOIN {$ecs->table('order_goods')} og ON oi.order_id = og.order_id 
									WHERE oi.user_id = pi.uid AND oi.order_status = 1 AND oi.order_time > pi.action_time
									AND og.goods_id = pi.goods_id AND (og.style_id = pi.style_id OR og.style_id = 0)
								)
						";
	$sum_buy = $slave_db->getOne($sql_buy);
	$sum_base += $goods['sum_base'];
	$goods_list[$key]['status'] = get_sale_status($goods['sale_status']);
	$goods_list[$key]['sum_inform'] = $sum_inform;
	$goods_list[$key]['sum_buy'] = $sum_buy;
	$goods_list[$key]['stat_buy'] = $sum_buy / ($goods['sum_base']?$goods['sum_base']:1) * 100;
}

$smarty->assign('back', $_SERVER['HTTP_REFERER']);
$smarty->assign('price_mapping', $price_mapping);
$smarty->assign('sum_base', $sum_base);
$smarty->assign('goods_list', $goods_list);
$smarty->assign('goods_list_count', count($goods_list));
$smarty->display('oukooext/analyze_pricecut_inform.htm');

function get_sale_status($sale_status) {
	$sale_status_list = array(
		'normal' 		=> 		'在售',
		'tosale' 		=> 		'即将上市',
		'shortage' 	=> 		'缺货',
		'withdrawn'	=> 		'下市',
		);
	return $sale_status_list[$sale_status];
}
function getCondition() {
  global $smarty;
  $start = $_REQUEST['start'];
  $end = $_REQUEST['end'];
  $goods_id = $_REQUEST['goods_id'];
  $style_id = $_REQUEST['style_id'];
  if (!strtotime($start)) {
    $start = date('Y-m-d');
  }
  if (!strtotime($end)) {
    $end = date('Y-m-d');
  }
  
  $smarty->assign('start', $start);
  $smarty->assign('end', $end);
  $end_t = date("Y-m-d", strtotime($end) + 24 * 3600);
  $condition = " AND pi.date >= '{$start}' AND pi.date < '{$end_t}' ";
	if ($goods_id) {
		$condition .= " AND pi.goods_id = $goods_id ";
	}
	if ($style_id) {
		$condition .= " AND pi.style_id = $style_id ";
	}
  return $condition; 
}
function getConditionName() {
  global $smarty;
  $goods_name = trim($_REQUEST['goods_name']);
  $smarty->assign('goods_name', $goods_name);
	if ($goods_name) {
		$condition = " AND g.goods_name LIKE '%$goods_name%' ";
	}
	return $condition;
}
function getConditionHaving() {
  global $smarty;
  $price = $_REQUEST['price'];
  $smarty->assign('price', $price);
	if ($price && $price != 'all') {
		if ($price == 'above') {
			$price_floor = '3000';
			$price_roof = '10000000';
		} else {
			$price_floor = $price - 500;
			$price_roof = $price;
		}
		$condition = " HAVING price >= $price_floor AND price <= $price_roof ";
	}
	return $condition;
}