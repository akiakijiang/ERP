<?php
/**
 * 到货通知统计
 * @author ncchen 090207
 */
define('IN_ECS', true);

require('includes/init.php');
require("function.php");
party_priv(PARTY_OUKU);
admin_priv('tj_analyze_goods_inform');
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

$sql_group = " GROUP BY gi.goods_id, group_style_id ";
$sql_order = " ORDER BY g.top_cat_id, goods_name ";
$sql_having = getConditionHaving();
$condition = getCondition();
$condition_name = getConditionName();
$sql_base = "SELECT COUNT(gi.id) AS sum_base, gi.goods_id, gi.style_id, 
								IF(gi.style_id = 0, -1, gi.style_id) AS group_style_id, IFNULL(gs.style_price, g.shop_price) AS price,
								CONCAT_WS(' ', g.goods_name, IF(gs.goods_color = '', s.color, gs.goods_color)) AS goods_name,
								IFNULL(gs.sale_status, g.sale_status) AS sale_status
							FROM {$ecs->table('goods_inform')} gi 
							LEFT JOIN {$ecs->table('goods')} g ON gi.goods_id = g.goods_id
							LEFT JOIN {$ecs->table('goods_style')} gs ON gi.style_id = gs.style_id AND gs.goods_id = gi.goods_id
							LEFT JOIN {$ecs->table('style')} s ON s.style_id = gs.style_id
						 WHERE is_deal != 2 AND is_deal != 3 $condition_name $condition ";

$goods_list = $slave_db->getAll($sql_base. $sql_group. $sql_having. $sql_order);
$sum_base = 0;
foreach ($goods_list as $key => $goods) {
	
	$sql_inform = "SELECT COUNT(gi.id) 
									FROM {$ecs->table('goods_inform')} gi
								 WHERE is_deal = 1 AND gi.goods_id = {$goods['goods_id']} AND gi.style_id = {$goods['style_id']} $condition ";
	$sum_inform = $slave_db->getOne($sql_inform);
	$sql_buy = "SELECT COUNT(gi.id) 
							 FROM {$ecs->table('goods_inform')} gi 
							WHERE gi.is_deal = 1 AND gi.goods_id = {$goods['goods_id']} AND gi.style_id = {$goods['style_id']} $condition
								AND EXISTS(
									SELECT 1
									FROM {$ecs->table('order_info')} oi
									INNER JOIN {$ecs->table('order_goods')} og ON oi.order_id = og.order_id 
									WHERE oi.user_id = gi.uid AND oi.order_status = 1 AND oi.order_time > gi.action_time
									AND og.goods_id = gi.goods_id AND (og.style_id = gi.style_id OR og.style_id = 0)
								)
						";
	$sum_buy = $slave_db->getOne($sql_buy);
	$sum_base += $goods['sum_base'];
	$goods_list[$key]['status'] = get_sale_status($goods['sale_status']);
	$goods_list[$key]['sum_inform'] = $sum_inform;
	$goods_list[$key]['sum_buy'] = $sum_buy;
	$goods_list[$key]['stat_buy'] = $sum_buy /($sum_inform?$sum_inform:1) * 100;
}

$smarty->assign('price_mapping', $price_mapping);
$smarty->assign('goods_list', $goods_list);
$smarty->assign('sum_base', $sum_base);
$smarty->assign('goods_list_count', count($goods_list));
$smarty->display('oukooext/analyze_goods_inform.htm');

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
  $condition = " AND gi.date >= '{$start}' AND gi.date < '{$end_t}' ";
	if ($goods_id) {
		$condition .= " AND gi.goods_id = $goods_id ";
	}
	if ($style_id) {
		$condition .= " AND gi.style_id = $style_id ";
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