<?php
/**
 * 热销产品
 */

define('IN_ECS', true);

require('includes/init.php');
require("function.php");
require_once(ROOT_PATH."RomeoApi/lib_inventory.php");
admin_priv('manage_hot_goods');
// 查询所有商品

$startCalendar = sqlSafe($_REQUEST['startCalendar']);
$endCalendar = sqlSafe($_REQUEST['endCalendar']);
$sort_type = trim($_REQUEST['sort_type']);
$sort_condition = "ORDER BY amount desc";

function compare($x,$y){
		if ($x['realamount'] == $y['realamount']) return 0;
		return ($x['realamount'] > $y['realamount']) ? -1 : 1;
}
if (strtotime($startCalendar) > 0){
		$startCalendar = date("Y-m-d H:i:s", strtotime($startCalendar));
		$time_condition = " AND o.order_time >= '$startCalendar'";
}

if (strtotime($endCalendar) > 0) {
		$endCalendar = date("Y-m-d H:i:s", strtotime("+1 day", strtotime($endCalendar)));
		$time_condition .= " AND o.order_time <= '$endCalendar'";
}

	
if($sort_type != ''){
	switch ($sort_type){
		case 'number':
			$sort_condition = "ORDER BY number desc";
			break;
		case 'amount':
			$sort_condition = "ORDER BY amount desc";
			break;		
		default;
	}
}


$sql = "SELECT temp.goods_id, temp.goods_name, sum( temp.num ) number, sum( temp.amount ) amount,url
		FROM (
		SELECT o.order_id, rec_id,og.goods_id, og.goods_number * goods_price+o.bonus amount, og.goods_number num, og.goods_name,
		(SELECT url FROM ecs_taobao_goods t where t.goods_id = og.goods_id order by t.last_modify desc limit 1) as url
		FROM ecs_order_info o, ecs_order_goods og
		WHERE o.order_id = og.order_id
		AND ". party_sql('o.party_id')."
		AND o.order_status =1 AND o.pay_status =2 AND ( o.shipping_status = 2 OR o.shipping_status = 1 ) AND o.order_type_id = 'SALE' $time_condition ) as temp 
		GROUP BY goods_id
		$sort_condition ";

$sqlc = "SELECT  og2.goods_id, og2.goods_number * og2.goods_price + bonus / num * goods_number realamount
		 FROM (SELECT sum( goods_number ) num, o.order_id, o.bonus
		 FROM ecs_order_goods og, ecs_order_info o
		 WHERE o.order_id = og.order_id
		 AND o.order_status =1
		 AND o.pay_status =2
		 AND (o.shipping_status =2 OR o.shipping_status =1)
		 AND o.order_type_id = 'SALE'
		 $time_condition
		 AND ".party_sql('o.party_id')."
		 GROUP BY order_id
		 ) AS temp, ecs_order_goods og2
		 WHERE og2.order_id = temp.order_id ";


$goods_list = $slave_db->getAll($sql);
$goods_amount = $slave_db->getAll($sqlc);

if(is_array($goods_amount)){
	$realamount = array();
	foreach ($goods_amount as $key => $goods_amounts){
		$goodsId = $goods_amount[$key]['goods_id'];
		$realamount[$goodsId] = 0;
	}
	foreach ($realamount as $goodsId => $realamount_list) {
		foreach ($goods_amount as $key => $goods_amounts){
			if ( $goodsId == $goods_amount[$key]['goods_id']){
				$realamount[$goodsId] = $goods_amount[$key]['realamount'] + $realamount[$goodsId];
			}
		}
	}	
}

foreach ($goods_list as $key => $goods_lists) {
		$goodsId = $goods_lists['goods_id'];
		$goods_list[$key]['realamount'] = round($realamount[$goodsId], 2);
	}
if( $sort_type == 'average'){
	usort($goods_list, "compare");
}

if(is_array($goods_list)){
	$sum = 0;
	foreach ($goods_list as $key => $goods_lists){
		$sum = $goods_list[$key]['realamount'] + $sum;
	}
}


$smarty->assign('sum',$sum);
$smarty->assign('startCalendar',$startCalendar);
$smarty->assign('endCalendar',$endCalendar);
$smarty->assign('goods_list',$goods_list);
$smarty->display('oukooext/hot_goods.html');
?>

