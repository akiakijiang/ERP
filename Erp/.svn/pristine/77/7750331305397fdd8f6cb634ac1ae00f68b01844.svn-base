<?php

/**
 * ECSHOP 物流管理模块
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     Weber Liu <weberliu@hotmail.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: Zandy $
 * $Date: 2007-05-15 16:52:17 +0800 (星期二, 15 五月 2007) $
 * $Id$
 * @see : ecshop\admin\includes\inc_menu.php
 * @see : ecshop\languages\zh_cn\admin\common.php
 * @see : ecshop\languages\zh_cn\admin\priv_action.php
*/

define('IN_ECS', true);

require('includes/init.php');

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$_REQUEST['act'] = $_REQUEST['act'] ? $_REQUEST['act'] : 'list';

$action_user = $_SESSION['admin_name'];
$action_time = date("Y-m-d H:i:s");

include_once 'config.vars.php';
include_once 'function.php';
wuhuodengdaidaowuhuo(604800); // 超过 7 天的无货等待就自动到无货

/*------------------------------------------------------ */
//-- 支付方式列表 ?act=list
/*------------------------------------------------------ */

admin_priv('distribution_consignment');
$payments = getPayments();
$shippingTypes = getShippingTypes();

if ($_REQUEST['act'] == 'print_invoice')
{
	$order_sn = $_REQUEST['order_sn'];

	$sql1 = "
		SELECT *, 
			(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee
			, (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + bonus + integral_money) AS final_fee 
			, (SELECT action_note FROM {$ecs->table('order_action')} a WHERE a.order_id = o.order_id AND order_status = 1 LIMIT 1) note
		FROM {$ecs->table('order_info')} o
		LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = o.carrier_bill_id
		WHERE order_sn = '$order_sn'
	";
	$getRow = $db->getRow($sql1);
	$order_id = $getRow['order_id'];
	
	// 取得备注
	$sql = "SELECT * FROM {$ecs->table('order_action')} WHERE order_id = {$order_id} AND action_note != ''";
	$getRow['note'] = $db->getAll($sql);
	
	$getRow['shipping_time'] = $getRow['shipping_time'] ? date("Y-m-d H:i:s", $getRow['shipping_time']) : '';
	$getRow['confirm_time'] = $getRow['confirm_time'] ? date("Y-m-d H:i:s", $getRow['confirm_time']) : '';
	//重新获得支付方式和货运方式
	$getRow['shipping_name'] = $shippingTypes[$getRow['shipping_id']] ? $shippingTypes[$getRow['shipping_id']]['shipping_name'] : $getRow['shipping_name'];
	$getRow['pay_name'] = $payments[$getRow['pay_id']] ? $payments[$getRow['pay_id']]['pay_name'] : $getRow['pay_name'];
	
	$getRow['format_goods_amount'] = price_format($getRow['goods_amount']);
	$getRow['format_shipping_fee'] = price_format($getRow['shipping_fee']);
	$getRow['format_total_fee'] = price_format($getRow['total_fee']);
	$getRow['format_bonus'] = price_format(abs($getRow['bonus']));
	$getRow['format_integral_money'] = price_format(abs($getRow['integral_money']));
	$getRow['format_final_fee'] = $getRow['final_fee'] > 0 ? price_format($getRow['final_fee']) : price_format(0);
	
	
	$smarty->assign('bufahuodan', '');
	$sql2 = "select a.rec_id, a.order_id, a.goods_name, c.cat_name, a.goods_price, a.goods_number, a.goods_id, a.style_id, b.top_cat_id from " . $ecs->table('order_goods') . " a 
		left join " . $ecs->table('goods') . " b on a.goods_id = b.goods_id 
		left join " . $ecs->table('category') . " c on b.cat_id = c.cat_id
		where a.order_id = '$order_id' ";
	if ($_REQUEST['t'] == 'bu') {
		$sql2 = "select a.rec_id, a.order_id, a.goods_name, c.cat_name, a.goods_price, a.goods_number, a.goods_id, a.style_id, b.top_cat_id from " . $ecs->table('order_goods') . " a 
			left join " . $ecs->table('goods') . " b on a.goods_id = b.goods_id 
			left join " . $ecs->table('category') . " c on b.cat_id = c.cat_id
			where a.order_id = '$order_id' AND a.goods_status = 18 ";
		$smarty->assign('bufahuodan', '补');
	}
	$getAll = $db->getAll($sql2);
	
	foreach ($getAll as $k => $v) {
		$getAll[$k]['goods_amount'] = $v['goods_price'] * $v['goods_number'];
		if($v['top_cat_id'] != 1) $getAll[$k]['productCode'] = encode_goods_id($v['goods_id'], $v['style_id']);
	}
	foreach($getRow['note'] as $k => $v) { //暂时修复用户取消的bug
		$getRow['note'][$k]['action_note'] = str_replace('用户取消:on','',$v['action_note']);
	}
	
	$getRow['goods_list'] = $getAll;

	$getRow['code_width'] = max(240 + (str_len($order_sn) - 10) * 30, 150);
	$smarty->assign('orders', array($getRow));
//	pp($getRow);
	//die();
	
	
    
	$smarty->display('oukooext/print_invoice.htm');
}
elseif ($_REQUEST['act'] == 'list')
{
	$sizeof = false;
	$dc_type = trim($_REQUEST['type']);

	$dc_types = explode(",", $dc_type);
	#p($dc_type, $dc_types);
	$order_type = "(select count(*) from {$ecs->table('order_info')} as oi where oi.parent_order_id = a.order_id) = 0 and biaoju_store_id = 0";
	
	if ($dc_type && !in_array($dc_type, array('search', 'task'))) {
		$types = explode("_", $dc_types[1]);
		$types[1] = (int)$types[1];
		
		switch ($dc_types[0]) {
			case 'h':
				// 发货情况
				switch ($types[1]) {
					case 0:
						// 待发货
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 1 AND invoice_status = 3 AND shipping_status = '0' 
								AND ((pay_id = 1 AND pay_status = '0')
									or ( pay_id != 1 AND pay_status = '2')) ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 1 AND invoice_status = 3 AND shipping_status = '0' 
								AND ((pay_id = 1 AND pay_status = '0')
									or ( pay_id != 1 AND pay_status = '2')) 
							ORDER BY order_id DESC ";
						// {{{ 列出待发货的商品
						if ($types[2] == 'g') {
							$sqlc = "SELECT 0 as cc ";
							$sqla = "SELECT sum(b.goods_number) as goods_number, b.goods_id, b.goods_name, d.provider_name FROM " . $ecs->table('order_info') . " a 
								LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
								LEFT JOIN " . $ecs->table('goods') . " c ON c.goods_id = b.goods_id 
								LEFT JOIN " . $ecs->table('provider') . " d ON c.provider_id = d.provider_id 
								WHERE  $order_type AND a.order_status = 1 AND a.invoice_status = 3 AND a.shipping_status in (0, 4) 
									AND ((a.pay_id = 1 AND a.pay_status = '0')
										or ( a.pay_id != 1 AND a.pay_status = '2'))
								GROUP BY b.goods_id
								ORDER BY d.provider_id ";
							#p($sqlc, $sqla);
							$listGoodsForDistribution = true;
							$smarty->assign("listGoodsForDistribution", $listGoodsForDistribution);
						}
						
						$sql_carrier = "select * from " . $ecs->table('carrier');
						$res_carrier = $db->query($sql_carrier);
						$carriers = Array();
						while ($row_carrier = $db->fetchRow($res_carrier)){
							$carriers[] = $row_carrier;
						}
						
						$smarty->assign("carriers", $carriers);
						
						// }}}
						break;
					case 1:
						// 已发货
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 1 AND invoice_status = 3 AND shipping_status = '1' ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 1 AND invoice_status = 3 AND shipping_status = '1' 
							ORDER BY order_id DESC ";
						break;
				}
				break;
			case 't':
				// 自提情况
				switch ($types[1]) {
					case 4:
						// 自提待确认
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 1 AND invoice_status = 3 AND shipping_status = '4' AND ((pay_id = 1 and pay_status = 0) or (pay_status = 2 and pay_id != 1)) ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 1 AND invoice_status = 3 AND shipping_status = '4' AND ((pay_id = 1 and pay_status = 0) or (pay_status = 2 and pay_id != 1)) ORDER BY order_id DESC ";
						break;
					case 5:
						// 待自提
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 1 AND invoice_status = 3 AND shipping_status = '5' 
									AND ((pay_id = 1 AND pay_status = '0') 
										or ( pay_id != 1 AND pay_status = '2')) ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 1 AND invoice_status = 3 AND shipping_status = '5' 
									AND ((pay_id = 1 AND pay_status = '0') 
										or ( pay_id != 1 AND pay_status = '2')) 
							ORDER BY order_id DESC ";
						break;
					case 6:
						// 已自提
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 1 AND invoice_status = 3 AND shipping_status = '6' ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " AS a 
							WHERE $order_type AND order_status = 1 AND invoice_status = 3 AND shipping_status = '6' 
							ORDER BY order_id DESC ";
						break;
					case 7:
						// 自提取消
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 2 AND invoice_status = 3 AND shipping_status = '7' ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 2 AND invoice_status = 3 AND shipping_status = '7' 
							ORDER BY order_id DESC ";
						break;
				}
				break;
			case 'j':
				// 拒收情况
				switch ($types[1]) {
					case 1:
						// 已拒收
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 4 AND invoice_status = 3 AND shipping_status = '1' ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 4 AND invoice_status = 3 AND shipping_status = '1' 
							ORDER BY order_id DESC ";
						break;
					case 3:
						// 拒收退回
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 4 AND invoice_status = 3 AND shipping_status = '3' ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " AS a
							WHERE $order_type AND order_status = 4 AND invoice_status = 3 AND shipping_status = '3' 
							ORDER BY order_id DESC ";
						break;
				}
				break;
			case 'b':
				// 补货情况
				switch ($types[1]) {
					case 15:
						// 补货待审核
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 7 AND shipping_status = 1 AND invoice_status = 3 AND b.goods_status = '$types[1]' GROUP BY a.order_id ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 7 AND shipping_status = 1 AND invoice_status = 3 AND b.goods_status = '$types[1]' GROUP BY a.order_id 
							ORDER BY a.order_id DESC ";
						break;
					case 16:
						// 补货已审核
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 7 AND shipping_status = 1 AND invoice_status = 3 AND b.goods_status = '$types[1]' GROUP BY a.order_id ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 7 AND shipping_status = 1 AND invoice_status = 3 AND b.goods_status = '$types[1]' GROUP BY a.order_id 
							ORDER BY a.order_id DESC ";
						break;
					case 17:
						// 补货已批准
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 7 AND shipping_status = 1 AND invoice_status = 3 AND b.goods_status = '$types[1]' GROUP BY a.order_id ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 7 AND shipping_status = 1 AND invoice_status = 3 AND b.goods_status = '$types[1]' GROUP BY a.order_id 
							ORDER BY a.order_id DESC ";
						break;
					case 18:
						// 补货已发
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 7 AND shipping_status = 1 AND invoice_status = 3 AND b.goods_status = '$types[1]' GROUP BY a.order_id ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 7 AND shipping_status = 1 AND invoice_status = 3 AND b.goods_status = '$types[1]' GROUP BY a.order_id 
							ORDER BY a.order_id DESC ";
						break;
				}
				$sizeof = true;
				break;
			case 'w':
				// 无货
				switch ($types[1]) {
					case 21:
						// 无货
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 6 AND shipping_status in(0, 4) AND b.goods_status = '$types[1]' GROUP BY a.order_id ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 6 AND shipping_status in(0, 4) AND b.goods_status = '$types[1]' GROUP BY a.order_id 
							ORDER BY a.order_id DESC ";
						break;
					case 22:
						// 等待
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 6 AND shipping_status in(0, 4) AND b.goods_status = '$types[1]' GROUP BY a.order_id ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 6 AND shipping_status in(0, 4) AND b.goods_status = '$types[1]' GROUP BY a.order_id 
							ORDER BY a.order_id DESC ";
						break;
					case 23:
						// 到货
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 6 AND shipping_status in(0, 4) AND b.goods_status = '$types[1]' GROUP BY a.order_id ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 6 AND shipping_status in(0, 4) AND b.goods_status = '$types[1]' GROUP BY a.order_id 
							ORDER BY a.order_id DESC ";
						break;
					case 24:
						// 断货
						$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 6 AND shipping_status in(0, 4) AND b.goods_status = '$types[1]' GROUP BY a.order_id ";
						$sqla = "SELECT * FROM " . $ecs->table('order_info') . " a
							LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
							WHERE $order_type AND order_status = 6 AND shipping_status in(0, 4) AND b.goods_status = '$types[1]' GROUP BY a.order_id 
							ORDER BY a.order_id DESC ";
						break;
				}
				$sizeof = true;
				break;
		}
	}elseif($dc_type == 'search'){
		$sqladd = getOrderSearchCondition();
		
		$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') 
			. " info LEFT JOIN " . $ecs->table('carrier_bill') . " b ON info.carrier_bill_id = b.bill_id where 1 ";
		$sqla = "SELECT * FROM " . $ecs->table('order_info') 
			. " info LEFT JOIN " . $ecs->table('carrier_bill') . " b ON info.carrier_bill_id = b.bill_id where 1 ";
		
		$sqlc .= $sqladd;
		$sqla .= $sqladd;
		$sqla .= " GROUP BY info.order_id ORDER BY info.order_id DESC ";
		#p($sqla);
	}
	if (!$dc_type || $dc_type == 'task') {
		// 待处理订单
		$tasks = array();
		// 待发货
		$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " AS a
			WHERE $order_type AND order_status = 1 AND invoice_status = 3 AND shipping_status = '0' 
				AND ((pay_id = 1 AND pay_status = '0')
					or ( pay_id != 1 AND pay_status = '2')) ";
		$res = $db->query($sqlc);
		$row = $db->fetchRow($res);
		$total = intval($row['cc']);
		$tasks[] = array('total'=>$total, 'item'=>'待发货', 'type' => 'h,s_0');

		// 自提待确认
		$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " AS a
			WHERE $order_type AND order_status = 1 AND invoice_status = 3 AND shipping_status = '4' AND ((pay_id = 1 and pay_status = 0) or (pay_status = 2 and pay_id != 1)) ";
		$res = $db->query($sqlc);
		$row = $db->fetchRow($res);
		$total = intval($row['cc']);
		$tasks[] = array('total'=>$total, 'item'=>'自提待确认', 'type' => 't,s_4');

		// 补货待审核
		$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " a
			LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
			WHERE $order_type AND order_status = 7 AND shipping_status = 1 AND invoice_status = 3 AND b.goods_status = '15' GROUP BY a.order_id ";
		$row = $db->getAll($sqlc);
		$total = sizeof($row);
		$tasks[] = array('total'=>$total, 'item'=>'补货待审核', 'type' => 'b,g_15');

		// 补货已批准
		$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " a
			LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
			WHERE $order_type AND order_status = 7 AND shipping_status = 1 AND invoice_status = 3 AND b.goods_status = '17' GROUP BY a.order_id ";
		$row = $db->getAll($sqlc);
		$total = sizeof($row);
		$tasks[] = array('total'=>$total, 'item'=>'补货已批准', 'type' => 'b,g_17');

		// 无货等待
		$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " a
			LEFT JOIN " . $ecs->table('order_goods') . " b ON a.order_id = b.order_id 
			WHERE $order_type AND order_status = 6 AND shipping_status in(0, 4) AND b.goods_status = '22' GROUP BY a.order_id ";
		$row = $db->getAll($sqlc);
		$total = sizeof($row);
		$tasks[] = array('total'=>$total, 'item'=>'无货->等待', 'type' => 'w,g_22');

		$smarty->assign('tasks', $tasks);

	}else{
		$offset = 6;
		if ($sizeof) {
			$row = $db->getAll($sqlc);
			$total = sizeof($row);
		} else {
			$res = $db->query($sqlc);
			$row = $db->fetchRow($res);
			$total = $row['cc'];
		}
		
		$page = intval($_GET['page']);
		$page = max(1, $page);
		$from = ($page-1)*$offset;

		$order_list = array();
		#$sql = "SELECT * ".strstr($sql, "FROM ");
		$limit = " LIMIT $offset OFFSET $from ";
		!$listGoodsForDistribution && $sqla .= $limit;
		$res = $db->query($sqla);
		while ($row = $db->fetchRow($res))
		{
			//$order_list[$row['order_id']] = $row;
			$order_list[] = $row;
		}
		foreach ($order_list as $k => $v) {
			//$order_list[$k]['shipping_time'] = $v['shipping_time'] ? date("Y-m-d H:i:s", $v['shipping_time']) : '';
			$order_list[$k]['provider'] = getProvider($v['order_id']);
			$getGoodsStatusByOrderId = getGoodsStatusByOrderId($v['order_id']);
			$order_type = getOrderType($v['order_id']);
			$order_list[$k]['order_type'] = $order_type;
			if($dc_type == 'search'){
				if ($v['order_status'] == 1 && $v['shipping_status'] == 0 && $v['invoice_status'] == 3) {
					$order_list[$k]['type'] = "h,s_0";
				}elseif ($v['order_status'] == 1 && $v['shipping_status'] == 4 && $v['invoice_status'] == 3) {
					$order_list[$k]['type'] = "t,s_4";
				}elseif ($v['order_status'] == 1 && $v['shipping_status'] == 5 && $v['invoice_status'] == 3 
					&& (($v['pay_id'] == 1 and $v['pay_status'] == 0) OR ($v['pay_id'] != 1 and $v['pay_status'] == 2)) ) {
					$order_list[$k]['type'] = "t,s_5";
				}elseif ($v['order_status'] == 4 && $v['shipping_status'] == 1 && $v['invoice_status'] == 3) {
					$order_list[$k]['type'] = "j,s_1";
				}elseif ($v['order_status'] == 7 && $v['shipping_status'] == 1 && $v['invoice_status'] == 3 && in_array(15, $getGoodsStatusByOrderId)) {
					$order_list[$k]['type'] = "b,g_15";
				}elseif ($v['order_status'] == 7 && $v['shipping_status'] == 1 && $v['invoice_status'] == 3 && in_array(17, $getGoodsStatusByOrderId)) {
					$order_list[$k]['type'] = "b,g_17";
				}elseif ($v['order_status'] == 6 && in_array($v['shipping_status'], array(0, 4)) && in_array(22, $getGoodsStatusByOrderId)) {
					$order_list[$k]['type'] = "w,g_22";
				}else{
					$order_list[$k]['type'] = "";
				}
			}
		}
		$sqladd .= " AND (
			(order_status = 1 AND invoice_status = 3 AND shipping_status = '0' 
											AND ((pay_id = 1 AND pay_status = '0')
												or ( pay_id != 1 AND pay_status = '2')) /* 待发货 */) OR
			
			(order_status = 1 AND invoice_status = 3 AND shipping_status = '1' /* 已发货 */) OR
			
			(order_status in (0, 1) AND shipping_status = '4' AND (pay_id = 1 or (pay_status = 2 and pay_id != 1)) /* 自提待确认 */) OR
			
			(order_status = 1 AND invoice_status = 3 AND shipping_status = '5'
									AND ((pay_id = 1 AND pay_status = '0') 
										or ( pay_id != 1 AND pay_status = '2')) /* 待自提 */) OR
			
			(order_status = 1 AND invoice_status = 3 AND shipping_status = '6' /* 已自提 */) OR
			
			(order_status = 2 AND invoice_status = 3 AND shipping_status = '7' /* 自提取消 */) OR
			
			(order_status = 4 AND invoice_status = 3 AND shipping_status = '1' /* 已拒收 */) OR
			
			(order_status = 4 AND invoice_status = 3 AND shipping_status = '3' /* 拒收退回 */) OR
			
			(order_status = 7 AND shipping_status = 1 AND invoice_status = 3 /* 补货 */) OR
			
			(order_status = 6 AND shipping_status in(0, 4) /* 无货 */)
		)";

		$Pager = Pager($total, $offset, $page);
		$currentTime = date("Y-m-d H:i:s");

		$smarty->assign('currentTime', $currentTime);
		$smarty->assign('Pager', $Pager);
		$smarty->assign('order_list', $order_list);
	}

	// 读取供应商
	$carriers = getCarriers();
	$smarty->assign('carriers', $carriers);
	
	$smarty->assign('type', $dc_type);
	$smarty->assign('dc_type', $dc_type);
	$smarty->assign('adminvars', $_CFG['adminvars']);
	$smarty->display('oukooext/distribution_consignment.htm');
}

/*------------------------------------------------------ */
//-- ajax post
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'ajax')
{
	// ajax
	admin_priv('distribution_consignment');
	$do = $_REQUEST['do'];
	if ($do == 'cancel') { // 取消
		$order_sn = $_REQUEST['order_sn'];
		$action_note = $_REQUEST['note'];
		$fetch = $db->getRow("SELECT * FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
		$order_id = $fetch['order_id'];
		
		$sql_u = "UPDATE ".$ecs->table('order_info')." SET order_status = '2' WHERE order_id = '$order_id'";
		$db->query($sql_u);
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die("error");
		}
		$_info = array('order_id' => $order_id, 'order_status' => 2, 'action_note' => $action_note);
		orderActionLog($_info);
		
		if (in_array($fetch['shipping_status'], array(5))) {
			// 自提取消
			$sqladd = "";
			if ($fetch['pay_status'] == 2 && $fetch['pay_id'] != 1) {
				$sqladd = "pay_status = 3, ";
			}
			$sql_u = "UPDATE ".$ecs->table('order_info')." SET $sqladd shipping_status = 7 
				WHERE order_id = '$order_id' LIMIT 1 "; // 如果已付款就需要将订单设为待退款
			$db->query($sql_u);
			$affected_rows = $db->affected_rows();
			if (!$affected_rows) {
				die("error");
			}
			$_info = array('order_id' => $order_id, 'shipping_status' => 7, 'action_note' => $action_note);
			if ($sqladd) {
				$_info['pay_status'] = 3;
			}
			orderActionLog($_info);
		}
		// {{{ 1 退还欧币，如果有
		if (abs($fetch['integral']) > 0)
		{
			// {{{ 1.1 检查是否有该用户，没有则插入
			$sql0 = "select * from ".$ecs->table('users')." where user_id = '{$fetch['user_id']}' limit 1 ";
			$ft0 = $db->getRow($sql0);
			if (!$ft0['userId']){die('error');}
			$sql = "select * from ".DB_TBL_OK_USER." where user_id = '{$ft0['userId']}' limit 1 ";
			$ft1 = $db->getRow($sql);

			if (!$ft1['user_id'])
			{
				$sql = "INSERT INTO ".DB_TBL_OK_USER." (`rank_points`, `pay_points`, `rank_price`, `user_id`) VALUES ('', '', '', '{$ft0['userId']}');";
				$ttt1 = $db->query($sql);
			}
			// }}}
			$nowtime = time();
			// {{{ 1.2 退还加欧币
			$sqlu = "update ".DB_TBL_OK_USER." 
				set pay_points = pay_points-".abs($fetch['integral'])." 
				where user_id = '{$ft0['userId']}' limit 1";
			$ttta = $db->query($sqlu);
			// }}}
			// {{{ 1.3
			$sql_i = "INSERT INTO ".DB_TBL_OK_POINT_LOG." 
				(`pl_id`, `user_id`, `site_id`, `pl_utime`, `pl_uip`, `pl_ponits`, `use_mark`, `use_type`, `pl_comment`) 
				VALUES 
				('', '{$ft0['userId']}', 1, $nowtime, '".ip2long(getRealIp())."', ".abs($fetch['integral']).", '{$fetch['order_sn']}', 6, '订单取消退还');";
			$tttb = $db->query($sql_i);
			// }}}
		}
		// }}}
		die("ok");
	} elseif ($do == 'fahuo') { // 正常发货
		$order_sn = $_REQUEST['order_sn'];
		$action_note = $_REQUEST['note'];
		
		$bill_no = $_REQUEST['bill_no'];
		$carrier_id = $_REQUEST['carrier_id'];
		$weight = $_REQUEST['weight'];
		
		$query = "SELECT order_id, order_sn, consignee, is_ship_emailed, o.address, tel, u.email, u.user_name, c.name as carrier_name, c.web_site as carrier_web_site FROM {$ecs->table('order_info')} AS o, {$ecs->table('users')} AS u LEFT JOIN {$ecs->table('carrier')} AS c ON c.carrier_id = '$carrier_id' WHERE order_sn = '$order_sn' AND o.user_id = u.user_id";
		$order = $db->getRow($query);
		
		$order_id = $order['order_id'];
		$order_sn = $order['order_sn'];
		$send_address = $order['address'];
		$receiver = $order['consignee'];
		$phone_no = $order['tel'];
		$user_name = $order['user_name'];
		$email = $order['email'];
//		$carrier_name = $fetch['carrier_name'];
		
		
		$sql_bill = "insert into " . $ecs->table('carrier_bill') . " (weight, carrier_id, bill_no, bill_status, send_address, receiver, phone_no, remarks) values('$weight', '$carrier_id', '$bill_no', 1, '$send_address', '$receiver', '$phone_no', '$action_note')";
		$result = $db->query($sql_bill);
		
		$bill_id = $db->insert_id();
		
		$sql_action = "insert into " . $ecs->table('carrier_bill_action') . " (bill_id, bill_status, action_time, action_user) values('$bill_id', 1, now(), '$action_user')";
		$db->query($sql_action);
		
		$genInvoiceNO = genInvoiceNO();
		$sql_u = "UPDATE ".$ecs->table('order_info')." SET shipping_status = '1', carrier_bill_id = '$bill_id', shipping_time = ".time().", invoice_no = '$genInvoiceNO' WHERE order_id = '$order_id'";
		$db->query($sql_u);
		$affected_rows = $db->affected_rows();
		
		if (!$affected_rows) {
			die("error");
		}
		$_info = array('order_id' => $order_id, 'shipping_status' => 1, 'action_note' => $action_note);
		orderActionLog($_info);
		
		die("ok");
	} elseif ($do == 'bufahuo') { // 补货发货
		$order_sn = $_REQUEST['order_sn'];
		$carrier_bill_id = intval($_REQUEST['carrier_bill_id']);
		$action_note = $_REQUEST['note'];
		$goods_ids = $_REQUEST['goods_ids'];
		$query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
		$fetch = $db->fetch_array($query);
		$order_id = $fetch['order_id'];
		
		/*$sql_u = "UPDATE ".$ecs->table('order_info')." SET carrier_bill_id = '$carrier_bill_id' WHERE order_status = 7 AND invoice_status = 3 AND shipping_status = '1' AND order_id = '$order_id' LIMIT 1 ";
		$db->query($sql_u);
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die('error');
		}
		//invoice_no = 'F".date("ymdHis")."'
		*/

		$genInvoiceNO = genInvoiceNO();
		$sql_u = "UPDATE ".$ecs->table('order_goods')." 
			SET goods_status = '18', carrier_bill_id = '$carrier_bill_id', invoice_num = '$genInvoiceNO'  
			WHERE order_id = '$order_id' AND goods_status = '17' ";
		$db->query($sql_u);
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die('error');
		}

		$goods_ids = explode(",", $goods_ids);
		foreach ($goods_ids as $v) {
			$_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 18, 'action_note' => $action_note);
			orderGoodsActionLog($_info);
		}
		die("ok");
	} elseif ($do == 'wait') { // 无货等待
		$order_sn = $_REQUEST['order_sn'];
		$action_note = $_REQUEST['note'];
		$query = $db->query("SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id WHERE a.order_sn = '$order_sn'");
		$fetchs = $goods_ids = array();
		while ($fetch = $db->fetch_array($query)) {
			#$fetchs[] = $fetch;
			if ($fetch['goods_status'] == 21) {
				$goods_ids[] = $fetch['goods_id'];
			}
		}
		$order_id = $fetch[0]['order_id'];
		$db->query("UPDATE ".$ecs->table('order_goods')." SET goods_status = 22 WHERE order_id = '$order_id'");
		foreach ($goods_ids as $v) {
			$_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 22, 'action_note' => $action_note);
			orderGoodsActionLog($_info);
		}
		die("ok");
	} elseif ($do == 'wuhuo') { // 无货通知
		$order_sn = $_REQUEST['order_sn'];
		$action_note = $_REQUEST['note'];
		$goods_ids = $_REQUEST['goods_ids'];
		$query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
		$fetch = $db->fetch_array($query);
		$order_id = $fetch['order_id'];

		$db->query("UPDATE ".$ecs->table('order_info')." SET order_status = '6' WHERE order_id = '$order_id'"); // 订单状态改为无货
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die('error');
		}
		$_info = array('order_id' => $order_id, 'order_status' => 6, 'action_note' => $action_note);
		orderActionLog($_info);

		$db->query("UPDATE ".$ecs->table('order_goods')." SET goods_status = '21' WHERE order_id = '$order_id' AND goods_id in ($goods_ids) ");
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die('error');
		}

		$goods_ids = explode(",", $goods_ids);
		foreach ($goods_ids as $v) {
			$_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 21, 'action_note' => $action_note);
			orderGoodsActionLog($_info);
		}
		die("ok");
	} elseif ($do == 'wuhuow') { // 无货等待到无货
		$order_sn = $_REQUEST['order_sn'];
		$action_note = $_REQUEST['note'];
		$goods_ids = $_REQUEST['goods_ids'];
		$query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
		$fetch = $db->fetch_array($query);
		$order_id = $fetch['order_id'];

		$db->query("UPDATE ".$ecs->table('order_goods')." SET goods_status = '21' WHERE order_id = '$order_id' AND goods_id in ($goods_ids) ");
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die('error');
		}

		$goods_ids = explode(",", $goods_ids);
		foreach ($goods_ids as $v) {
			$_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 21, 'action_note' => $action_note);
			orderGoodsActionLog($_info);
		}
		die("ok");
	} elseif ($do == 'daohuo') { // 无货等待到到货
		$order_sn = $_REQUEST['order_sn'];
		$action_note = $_REQUEST['note'];
		$goods_ids = $_REQUEST['goods_ids'];
		$query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
		$fetch = $db->fetch_array($query);
		$order_id = $fetch['order_id'];

		$db->query("UPDATE ".$ecs->table('order_goods')." SET goods_status = '23' WHERE order_id = '$order_id' AND goods_id in ($goods_ids) ");
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die('error');
		}

		$goods_ids = explode(",", $goods_ids);
		foreach ($goods_ids as $v) {
			$_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 23, 'action_note' => $action_note);
			orderGoodsActionLog($_info);
		}
		die("ok");
	} elseif ($do == 'duanhuo') { // 无货等待到断货
		$order_sn = $_REQUEST['order_sn'];
		$action_note = $_REQUEST['note'];
		$goods_ids = $_REQUEST['goods_ids'];
		$query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
		$fetch = $db->fetch_array($query);
		$order_id = $fetch['order_id'];

		$db->query("UPDATE ".$ecs->table('order_goods')." SET goods_status = '24' WHERE order_id = '$order_id' AND goods_id in ($goods_ids) ");
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die('error');
		}

		$goods_ids = explode(",", $goods_ids);
		foreach ($goods_ids as $v) {
			$_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 24, 'action_note' => $action_note);
			orderGoodsActionLog($_info);
		}
		die("ok");
	} elseif ($do == 'rejectionReturn') { // 拒收商品收货确认
		$order_sn = $_REQUEST['order_sn'];
		$action_note = $_REQUEST['note'];
		$query = $db->query("SELECT * FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
		$fetch = $db->fetch_array($query);
		$order_id = $fetch['order_id'];
		
		$sql_u = "UPDATE ".$ecs->table('order_info')." SET shipping_status = '3' 
			WHERE order_id = '$order_id' AND order_status = 4 AND invoice_status = 3 AND shipping_status = 1 LIMIT 1 "; // 订单状态为拒收，发票确认，发货为已发货
		$db->query($sql_u);
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die("error");
		}
		$_info = array('order_id' => $order_id, 'shipping_status' => 3, 'action_note' => $action_note);
		orderActionLog($_info);
		
		if ($fetch['pay_status'] == 2 && $fetch['pay_id'] != 1) {
			$sql_u = "UPDATE ".$ecs->table('order_info')." SET pay_status = '3' 
				WHERE order_id = '$order_id' AND order_status = 4 AND invoice_status = 3 AND pay_id != 1 LIMIT 1 "; // 如果已付款就需要将订单设为待退款
			$db->query($sql_u);
			$affected_rows = $db->affected_rows();
			if (!$affected_rows) {
				die("error");
			}
			$_info = array('order_id' => $order_id, 'pay_status' => 3, 'action_note' => $action_note);
			orderActionLog($_info);
		}
		die("ok");
	} elseif ($do == 'buhuo') { // 补货
		$order_sn = $_REQUEST['order_sn'];
		$action_note = $_REQUEST['note'];
		$goods_ids = $_REQUEST['goods_ids'];
		$query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
		$fetch = $db->fetch_array($query);
		$order_id = $fetch['order_id'];

		$db->query("UPDATE ".$ecs->table('order_info')." SET order_status = '7' WHERE order_id = '$order_id'"); // 订单状态改为补货
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die('error');
		}
		$_info = array('order_id' => $order_id, 'order_status' => 7, 'action_note' => $action_note);
		orderActionLog($_info);

		$db->query("UPDATE ".$ecs->table('order_goods')." SET goods_status = '15' WHERE order_id = '$order_id' AND goods_id in ($goods_ids) "); // 已确认的订单才可以收货确认
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die('error');
		}

		$goods_ids = explode(",", $goods_ids);
		foreach ($goods_ids as $v) {
			$_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 15, 'action_note' => $action_note);
			orderGoodsActionLog($_info);
		}
		die("ok");
	} elseif ($do == 'buhuoConfirm') { // 批准补货
		$order_sn = $_REQUEST['order_sn'];
		$action_note = $_REQUEST['note'];
		$goods_ids = $_REQUEST['goods_ids'];
		$query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
		$fetch = $db->fetch_array($query);
		$order_id = $fetch['order_id'];

		$db->query("UPDATE ".$ecs->table('order_goods')." SET goods_status = '16' WHERE order_id = '$order_id' AND goods_id in ($goods_ids) "); // 已确认的订单才可以收货确认
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die('error');
		}

		$goods_ids = explode(",", $goods_ids);
		foreach ($goods_ids as $v) {
			$_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 16, 'action_note' => $action_note);
			orderGoodsActionLog($_info);
		}
		die("ok");
	} elseif ($do == 'daiziti') { // 设为待自提
		$order_sn = $_REQUEST['order_sn'];
		$action_note = $_REQUEST['note'];
		$query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
		$fetch = $db->fetch_array($query);
		$order_id = $fetch['order_id'];
		
		$sql_u = "UPDATE ".$ecs->table('order_info')." SET shipping_status = '5' WHERE order_id = '$order_id'";
		$db->query($sql_u);
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die('error');
		}
		$_info = array('order_id' => $order_id, 'shipping_status' => 5, 'action_note' => $action_note);
		orderActionLog($_info);
		die("ok");
	} elseif ($do == 'ziti') { // 设为已自提
		$order_sn = $_REQUEST['order_sn'];
		$action_note = $_REQUEST['note'];
		$query = $db->query("SELECT order_id, user_id, integral, order_amount total_fee FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
		$fetch = $db->fetch_array($query);
		$order_id = $fetch['order_id'];
		
		$sql_u = "UPDATE ".$ecs->table('order_info')." SET shipping_status = '6' WHERE order_id = '$order_id'";
		$db->query($sql_u);
		$affected_rows = $db->affected_rows();
		if (!$affected_rows) {
			die('error');
		}
		$_info = array('order_id' => $order_id, 'shipping_status' => 6, 'action_note' => $action_note);
		orderActionLog($_info);

		// {{{ 返送欧币，如果有
		$sql_s = "select sum(return_points) as return_points, group_concat(return_bonus) as return_bonus 
			from ".$ecs->table('order_goods')."
			where order_id = '$order_id' 
			group by order_id ";
		$ft = $db->getRow($sql_s);
		
		// 1 返欧币
		$return_points = $ft['return_points'];
		if ($return_points > 0)
		{
			// {{{ 1.1 检查是否有该用户，没有则插入
			$sql0 = "select * from ".$ecs->table('users')." where user_id = '{$fetch['user_id']}' limit 1 ";
			$ft0 = $db->getRow($sql0);
			if (!$ft0['userId']){die('error');}
			$sql = "select * from ".DB_TBL_OK_USER." where user_id = '{$ft0['userId']}' limit 1 ";
			$ft1 = $db->getRow($sql);

			if (!$ft1['user_id'])
			{
				$sql = "INSERT INTO ".DB_TBL_OK_USER." (`rank_points`, `pay_points`, `rank_price`, `user_id`) VALUES ('', '', '', '{$ft0['userId']}');";
				$ttt1 = $db->query($sql);
			}
			// }}}
			$nowtime = time();
			// {{{ 1.2 加欧币
			$sqlu = "update ".DB_TBL_OK_USER." 
				set rank_points = rank_points+$return_points, pay_points = pay_points+".abs($fetch['integral']).", rank_price = rank_price + {$fetch['total_fee']} 
				where user_id = '{$ft0['userId']}' limit 1";
			$ttt2 = $db->query($sqlu);
			// }}}
			// {{{ 1.3 日志
			$sql_i = "INSERT INTO ".DB_TBL_OK_POINT_LOG." 
				(`pl_id`, `user_id`, `site_id`, `pl_utime`, `pl_uip`, `pl_ponits`, `use_mark`, `use_type`, `pl_comment`) 
				VALUES 
				('', '{$ft0['userId']}', 1, $nowtime, '".ip2long(getRealIp())."', $return_points, '$order_sn', 7, '订单完成返送');";
			$ttt3 = $db->query($sql_i);
			// }}}
		}

		// {{{ 2 加红包
		$return_bonus = $ft['return_bonus'];
		if (false !== strpos($return_bonus, ","))
		{
			$return_bonus = explode(",", $return_bonus);
		}
		else
		{
			$return_bonus = array(trim($return_bonus, ","));
		}
		foreach ($return_bonus as $k => $v)
		{
			if ('' == $v)
			{
				continue;
			}
			$sqls = "select * from ".DB_TBL_OK_GIFT_TICKET." where gt_code = '$v' and gt_state in (2, 3) "; // gt_state @see config.vars.php
			$ttt4 = $getRow = $db->getRow($sqls);
			// 如果红包确实存在
			if ($getRow)
			{
				$sql_i = "INSERT INTO ".DB_TBL_OK_GIFT_TICKET_LOG." 
					( `gtl_id` , `gtc_id` , `gt_id` , `user_id` , `gtl_utime` , `gtl_uip` , `gtl_fk_id` , `gtl_type` , `gtl_comment` )
					VALUES (
					NULL , '{$getRow['gtc_id']}', '{$getRow['gt_id']}', '{$ft0['userId']}', '$nowtime', '".ip2long(getRealIp())."', '$order_sn', '2', '活动获得'
					);";
				$ttt5 = $db->query($sql_i);
			}
		}
		// }}}
		// }}}
		die("ok");
	// {{{ get info
	} elseif ($do == 'getWaitGoods') { // 取得某个订单等待到货的商品
		$order_sn = $_REQUEST['order_sn'];
		$sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id WHERE a.order_sn = '$order_sn' AND b.goods_status = 21 ";
		$query = $db->query($sql);
		$goods_names = array();
		while ($fetch = $db->fetch_array($query)) {
			if ($fetch['goods_status'] == 21) {
				$fetch['goods_name'] && $goods_names[] = $fetch['goods_name'];
			}
		}
		#file_put_contents(dirname(__FILE__).'/sql.txt', $sql);
		die(join("\t", $goods_names));
	} elseif ($do == 'getOrderGoods') { // 取得某个订单所有的商品名字
		$order_sn = $_REQUEST['order_sn'];
		$sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id WHERE a.order_sn = '$order_sn' ";
		$query = $db->query($sql);
		$goods_names = array();
		while ($fetch = $db->fetch_array($query)) {
			$fetch['goods_name'] && $goods_names[] = $fetch['goods_name'];
		}
		die(join("\t", $goods_names));
	} elseif ($do == 'getOrderGoods2') { // 取得某个订单所有的商品名字
		$order_sn = $_REQUEST['order_sn'];
		$sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id WHERE a.order_sn = '$order_sn' ";
		$query = $db->query($sql);
		$goods = array();
		while ($fetch = $db->fetch_array($query)) {
			$goods[] = $fetch['goods_id']."\t".$fetch['goods_name'];
		}
		die(join("\t\t", $goods));
	} elseif ($do == 'getOrderGoods3') { // 取得某个订单需要补货的商品名字
		$order_sn = $_REQUEST['order_sn'];
		$sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id WHERE a.order_sn = '$order_sn' ";
		$query = $db->query($sql);
		$goods = array();
		while ($fetch = $db->fetch_array($query)) {
			$fetch['goods_status'] == 15 && $goods[] = $fetch['goods_id']."\t".$fetch['goods_name'];
		}
		die(join("\t\t", $goods));
	} elseif ($do == 'getOrderGoods4') { // 取得某个订单xx状态的商品id、名字
		$order_sn = $_REQUEST['order_sn'];
		$goods_status = $_REQUEST['goods_status'];
		$sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id WHERE a.order_sn = '$order_sn' ";
		$query = $db->query($sql);
		$goods = array();
		while ($fetch = $db->fetch_array($query)) {
			$fetch['goods_status'] == $goods_status && $goods[] = $fetch['goods_id']."\t".$fetch['goods_name'];
		}
		die(join("\t\t", $goods));
	} else {
		die(__LINE__);
	}
// }}}

} else {
	die(__LINE__);
}


#p($GLOBALS);

?>
