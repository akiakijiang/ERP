<?php
	define('IN_ECS', true);

	require('includes/init.php');

	require("function.php");
	require("pagination.php");
	
	header("Content-type: text/html;charset=UTF-8");
	
	$type = trim($_GET['type']);
// 为客服分配权限，因此将	admin_priv('finance_order')分配到下列各项中
	
	$types = explode(",", $type);
	if ($type && $type != 'search') {
		$sub_types = explode("_", $types[1]);
		$sub_types[1] = (int)$sub_types[1];
		# 添加party条件判断 2009/08/07 yxiang
		$order_type = 'parent_order_id = 0 AND '. party_sql('info.party_id');
		switch ($types[0]) {
		case 'f':
		//发票
			admin_priv('finance_order');
			$type = '1';
			switch ($sub_types[1]) {
			case 0:
				$order_status = "1";
				$pay_status = "1";
				$shipping_status = "shipping_status in (0, 4)";
				$invoice_status = "invoice_status = 0";
				break;
			case 2:
				$order_status = "1";
				$pay_status = "1";
				$shipping_status = "shipping_status in (0, 4)";
				$invoice_status = "invoice_status = 2";
				break;
			default:
				die("type error!");
			}
			break;
		case 'dq': 
		// 待确认款项
			admin_priv('customer_service_manage_order', 'finance_order');
			switch ($sub_types[1]) {
			case 0:
				// 先款后货
				$order_status = "order_status in (0, 1)";
				$pay_status = "pay_status in (0, 1)";
				$shipping_status = "shipping_status in (0, 4)";
				$invoice_status = "1";
				
				$invoice_status .= " and pay_id != 1 "; // fix
				break;
			case 1:
				admin_priv('finance_order');	
				// 货到付款
				$order_status = "order_status = 1";
				$pay_status = "pay_status = 0";
				$shipping_status = "shipping_status in (2, 6)";
				$invoice_status = "invoice_status = 3";
				
				$invoice_status .= " and pay_id = 1 "; // fix
				break;
			default:
				die("type error!");
			}
			break;
		case 'dt':
			admin_priv('finance_order');
			// 待退款
			switch ($sub_types[1]) {
			case 0:
				$order_status = "1";
				$pay_status = "pay_status = 3";
				$shipping_status = "1";
				$invoice_status = "1";
				break;
			case 1:
				// 已付款
				$order_status = "1";
				$pay_status = "pay_status = 2";
				$shipping_status = "1";
				$invoice_status = "1";
				break;
			case 2:
				// 已退款
				$order_status = "1";
				$pay_status = "pay_status = 4";
				$shipping_status = "1";
				$invoice_status = "1";
				break;
			default:
				die("type error!");
			}
			break;
		default:
			die("type error!");
		}
		$baseSQL = "select order_id, order_sn, order_time, consignee, goods_amount, pay_name, order_status, pay_status, invoice_status, pay_note, inv_payee, order_amount, (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee from {$ecs->table('order_info')} AS info where $order_status and $pay_status and $shipping_status and $invoice_status and $order_type order by order_time desc";
	} elseif ($type == 'search') {
		admin_priv('customer_service_manage_order', 'finance_order');

		# 添加party条件判断 2009/08/07 yxiang
		// $baseSQL = "SELECT order_id, order_sn, order_time, consignee, goods_amount, pay_name, order_status, pay_status, invoice_status, pay_note, inv_payee, order_amount, (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee 
		// from {$ecs->table('order_info')} info 
		// left join {$ecs->table('carrier_bill')} bill on info.carrier_bill_id = bill.bill_id 
		// where ".party_sql('info.party_id')." $sqladd order by order_time desc";

		// the above killed with ECB by Sinri 20160105
		$baseSQL = "SELECT 
		    order_id,
		    order_sn,
		    order_time,
		    consignee,
		    goods_amount,
		    pay_name,
		    order_status,
		    pay_status,
		    invoice_status,
		    pay_note,
		    inv_payee,
		    order_amount,
		    (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee
		from
		    ecshop.ecs_order_info
		where ".party_sql('info.party_id')." order by order_time desc";
		
	} else {
		admin_priv('finance_order');
		
		# 添加party条件判断 2009/08/07 yxiang
		
		// 待处理订单
		$tasks = array();
		// 发票待确认
		$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . "
			WHERE shipping_status in (0, 4) AND invoice_status = 0 AND ". party_sql('party_id');
		$res = $db->query($sqlc);
		$row = $db->fetchRow($res);
		$total = intval($row['cc']);
		$tasks[] = array('total'=>$total, 'item'=>'发票待确认', 'type' => 'f,s_0');

		// 发票用户已修改
		$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . "
			WHERE shipping_status in (0, 4) AND invoice_status = 2 AND ". party_sql('party_id');
		$res = $db->query($sqlc);
		$row = $db->fetchRow($res);
		$total = intval($row['cc']);
		$tasks[] = array('total'=>$total, 'item'=>'发票修改反馈', 'type' => 'f,s_2');

		// 待确认款项(先款后货) 
		$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . "
			WHERE order_status in(0, 1) AND pay_status = 0 AND shipping_status in(0, 4) AND pay_id != 1 AND ". party_sql('party_id');
		$res = $db->query($sqlc);
		$row = $db->fetchRow($res);
		$total = intval($row['cc']);
		$tasks[] = array('total'=>$total, 'item'=>'先款后货', 'type' => 'dq,s_0');

		// 待确认款项(货到付款)
		$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . "
			WHERE order_status = 1 AND pay_status = 0 AND shipping_status in (2, 6) AND invoice_status = 3 AND pay_id = 1 AND ". party_sql('party_id');
		$res = $db->query($sqlc);
		$row = $db->fetchRow($res);
		$total = intval($row['cc']);
		$tasks[] = array('total'=>$total, 'item'=>'货到付款', 'type' => 'dq,s_1');

		// 待退款
		$sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . "
			WHERE pay_status = 3 AND ". party_sql('party_id');
		$res = $db->query($sqlc);
		$row = $db->fetchRow($res);
		$total = intval($row['cc']);
		$tasks[] = array('total'=>$total, 'item'=>'待退款', 'type' => 'dt,s_0');

		$smarty->assign('tasks', $tasks);
	}
	
	if ($type) {
		$pagination = new Pagination();
		$pagination->set_sql($baseSQL, $db);
		
		$order_list = array();
	 
		while ($row = $db->fetch_array($pagination->result)) {
			$goodsSQL = "select goods_name, goods_number, goods_price, goods_number * goods_price as total_price from " . $ecs->table('order_goods') . " where order_id = " . $row['order_id'];
			$goods_rst = $db->query($goodsSQL);
			$order_list[$row['order_id']] = $row;
			$goods_name = array();
			$goods = array();
			while ($goods_row = $db->fetch_array($goods_rst)) {
				$goods_name[] = $goods_row['goods_name'];
				$goods[] = $goods_row;
			}
			$order_list[$row['order_id']]['goods_name'] = implode(",", $goods_name);
			$order_list[$row['order_id']]['goods'] = $goods;
			$order_list[$row['order_id']]['status'] = get_order_status($row['order_status']) . " " . get_pay_status($row['pay_status']) . " " . get_invoice_status($row['invoice_status']);
			
			$order_type = getOrderType($v['order_id']);
			$order_list[$row['order_id']]['order_type'] = $order_type;
		}
		
		if ($type == 'search') {
			foreach ($order_list as $k => $v) {
				
				if (in_array($v['shipping_status'], array(0, 4)) && $v['invoice_status'] == 0) {
					$order_list[$k]['type'] = "f,s_0";
				}elseif (in_array($v['shipping_status'], array(0, 4)) && $v['invoice_status'] == 2) {
					$order_list[$k]['type'] = "f,s_2";
				}elseif (in_array($v['order_status'], array(0, 1)) && in_array($v['shipping_status'], array(0, 4)) && $v['pay_id'] != 1 && $v['pay_status'] == 0) {
					$order_list[$k]['type'] = "dq,s_0";
				}elseif ($v['order_status'] == 1 && in_array($v['shipping_status'], array(2, 6)) && $v['invoice_status'] == 3 && $v['pay_id'] = 1 && $v['pay_status'] == 0) {
					$order_list[$k]['type'] = "dq,s_1";
				}elseif ($v['pay_status'] == 3) {
					$order_list[$k]['type'] = "dt,s_0";
				}else{
					$order_list[$k]['type'] = "";
				}
			}
		}
		$smarty->assign('order_list', $order_list);
		$smarty->assign('pagination', $pagination);
		$smarty->assign('page_info', page_info($pagination));
	}
	
	
	// 读取供应商
	$carriers = getCarriers();
	$smarty->assign('carriers', $carriers);
	
	$smarty->assign('type', $type);
	$smarty->assign('dc_type', $type);
	$smarty->assign('modules', $modules);
	$smarty->assign('adminvars', $_CFG['adminvars']);
	$smarty->display('oukooext/financial_manage-order.htm');
	
	
	function page_info($pagination) {
		$str = "总计{$pagination->total_count}个记录 分为{$pagination->page_count}页 当前第{$pagination->page_number}页 ";
		$str .= $pagination->get_forward_view("首页", "上一页", "下一页", "末页");
		$str .= "<select id='oselect' onchange='change()' style='height:20px;width:50px'>";
		for($i = 1; $i <= $pagination->page_count; $i++) {
			if ($i == $pagination->page_number) {
				$str .= "<option selected=\"selected\" value=\"". $pagination->get_request_url($i) ."\">$i</option>";
			} else {
				$str .= "<option value=\"". $pagination->get_request_url($i) ."\">$i</option>";
			}
		}
		$str .= "</select>";
		return $str;
	}
?>