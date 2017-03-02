<?php
/**
 * 采购入库查询
 * 
 * @author yxiang@oukoo.com 2009/5/27
 */
 
define('IN_ECS', true);
require('includes/init.php');
admin_priv('cg_in_storage'); 

require("function.php");
// 时间区间
$start = isset($_REQUEST['start']) && strtotime($_REQUEST['start']) ? $_REQUEST['start'] : date("Y-m-d 09:00");
$end   = isset($_REQUEST['end']) && strtotime($_REQUEST['end']) ? $_REQUEST['end'] : date('Y-m-d 22:00');

if ((strtotime($end) - strtotime($start)) > 2592000)
{
	sys_msg('时间跨度不能在一月以上，避免查询造成系统负担过重');	
}

// 当提交搜索时,将搜索请求保存在session中
$cond = $_REQUEST;
$cond['start'] = $start;
$cond['end'] = $end;

/**
 * 搜索列表
 */
$condition = get_condition($cond);

global $db;
$sql = "SELECT eoi.order_sn, eoi.order_time, eog.goods_name, if(sum(iid.quantity_on_hand_diff),sum(iid.quantity_on_hand_diff),0) as in_count, 
				min(iid.created_stamp) as min_in_time, boi.purchaser, boi.provider_id, boi.order_type " .
		"from ecshop.ecs_order_info eoi " .
			"LEFT JOIN ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id " .
			"LEFT JOIN romeo.inventory_item_detail iid on iid.order_goods_id = convert(eog.rec_id using utf8)" .
			"LEFT JOIN ecshop.ecs_batch_order_mapping bom on bom.order_id = eoi.order_id " .
			"LEFT JOIN ecshop.ecs_batch_order_info boi on boi.batch_order_id = bom.batch_order_id " .
			"LEFT JOIN romeo.facility f on eoi.facility_id = f.FACILITY_ID " .
		"WHERE eoi.order_sn LIKE '%-c' {$condition} and boi.is_cancelled ='N'
			GROUP BY eoi.order_id";
$result = $db->getAll($sql);
$smarty->assign('search_orders', $result);
$smarty->assign('start', $start);
$smarty->assign('end',   $end);
$smarty->display('oukooext/purchase_in_storage.htm');


/**
 * 根据session中的信息构造查询条件
 * 
 * @param array $cond 
 */ 
function get_condition($cond) 
{
	$condition = "";
	
	// 从session 中获取检索条件
	$order_sn       = trim($cond['order_sn']);
	$goods_name     = trim($cond['goods_name']);
	$provider_id    = trim($cond['provider_id']);
	$start          = $cond['start'];
	$end            = $cond['end'];
	
	// 订单号
	if ($order_sn != '')
	{
		$condition .= " AND eoi.order_sn LIKE '%{$order_sn}%'";
	}
	// 商品名
	if ($goods_name != '')
	{
		$condition .= " AND eog.`goods_name` LIKE '%{$goods_name}%'";
	}
	// 供应商
	if ($provider_id != 0)
	{
		$condition .= " AND boi.`provider_id` = '{$provider_id}'";
	}
	
	// 时间段
	$condition .= "AND ( eoi.order_time BETWEEN '{$start}' AND '{$end}') ";

	# 添加party条件判断 2009/08/06 yxiang
	$condition .= ' AND ' . party_sql('eoi.party_id');

	return $condition;
}

?>