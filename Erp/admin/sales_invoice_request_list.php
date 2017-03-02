<?php

/**
 * 销售发票请求管理
 * 该页面显示已出库待开票的订单清单
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'RomeoApi/lib_refund.php');
require_once(ROOT_PATH . 'includes/helper/validator.php');

// 请求
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('export', 'search', 'invoice')) 
    ? $_REQUEST['act'] 
    : null ;
// 消息
$message =
    isset($_REQUEST['message']) && trim($_REQUEST['message'])
    ? $_REQUEST['message']
    : false;
// 期初时间
$start = 
    isset($_REQUEST['start']) && strtotime($_REQUEST['start'])
    ? $_REQUEST['start']
    : date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
// 期末时间
$end = 
    isset($_REQUEST['end']) && strtotime($_REQUEST['end'])
    ? $_REQUEST['end']
    : date('Y-m-d') ;
// 分销商ID
$main_distributor_id =
    isset($_REQUEST['main_distributor_id']) && $_REQUEST['main_distributor_id'] > 0
    ? $_REQUEST['main_distributor_id']
    : null;
    
    // 订单号
$ordersn = isset($_REQUEST['ordersn']) && trim($_REQUEST['ordersn'])
        ? $_REQUEST['ordersn']
        : null ;

// 过滤条件
$filter = array('start' => $start, 'end' => $end, 'main_distributor_id' => $main_distributor_id, 'ordersn' => $ordersn);
    
// 用户没有选则party_id
if (!party_explicit($_SESSION['party_id'])) {
    $message = '如果需要生成发票的话，请先选择业务形态';
}


if ($act) {
    $conds  = _get_conditions($act, $filter);
    
    // 销售出库商品列表        
    $sql = "
        SELECT 
            o.order_id, o.order_sn, o.order_amount, o.distributor_id, o.shipping_fee, o.distribution_purchase_order_sn, o.order_time,o.bonus,
            og.goods_id, og.style_id, og.goods_price, og.goods_name, og.rec_id, - d.quantity_on_hand_diff AS goods_number,  
            ii.serial_number, ii.product_id, d.inventory_transaction_id, d.created_stamp, dis.main_distributor_id
        FROM
            ecshop.ecs_order_info AS o
            INNER JOIN ecshop.ecs_order_goods AS og ON og.order_id = o.order_id
            LEFT JOIN ecshop.distributor dis ON o.distributor_id = dis.distributor_id
            LEFT JOIN romeo.inventory_item_detail d ON d.order_id = convert(o.order_id using utf8) AND d.order_goods_id = convert(og.rec_id using utf8)
            LEFT JOIN romeo.inventory_item ii ON ii.inventory_item_id = d.inventory_item_id
            
        WHERE
            o.order_type_id IN ('SALE', 'SHIP_ONLY', 'RMA_EXCHANGE') AND                                                                -- 销售出库
            NOT EXISTS (SELECT 1 FROM sales_invoice_item WHERE inventory_transaction_id = d.inventory_transaction_id) AND  -- 没有生成过销售发票的
            d.quantity_on_hand_diff < 0 AND d.cancellation_flag <> 'Y' AND                                                 -- 按订单的出库时间查询
            ii.status_id IN ( 'INV_STTS_AVAILABLE', 'INV_STTS_USED', 'INV_STTS_DEFECTIVE' ) 
            {$conds} AND ". party_sql('o.party_id')."
        LIMIT 2000
    ";        
    $ref_sales_order_fields = $ref_sales_order_rowset = array();
    $sales_goods_rowset = $slave_db->getAllRefby($sql, array('order_id'), $ref_sales_order_fields, $ref_sales_order_rowset, false);
    // 取得订单的返利金额和开票金额
    if ($sales_goods_rowset) {
        // 取得每条记录的返利金额
        sales_invoice_get_item_rebate($ref_sales_order_rowset['order_id']);
        $sales_order_list = sales_invoice_get_order_list($ref_sales_order_rowset['order_id'], $ref_sales_order_fields, $main_distributor_id);
    }

    // 销退入库商品列表
    $sql = "
        SELECT 
            o.order_id, o.order_sn, o.order_amount, o.distributor_id, o.shipping_fee, o.distribution_purchase_order_sn, o.order_time,o.bonus,
            og.goods_id, og.style_id, og.goods_price, og.goods_name, og.rec_id, d.quantity_on_hand_diff as goods_number,
            ii.serial_number, ii.product_id, d.inventory_transaction_id, d.created_stamp, dis.main_distributor_id
        FROM
            ecshop.ecs_order_info AS o
            INNER JOIN ecshop.ecs_order_goods AS og ON og.order_id = o.order_id
            LEFT JOIN ecshop.distributor dis ON o.distributor_id = dis.distributor_id
            LEFT JOIN romeo.inventory_item_detail d ON d.order_id = convert(o.order_id using utf8) AND d.order_goods_id = convert(og.rec_id using utf8)
            LEFT JOIN romeo.inventory_item ii ON ii.inventory_item_id = d.inventory_item_id
        WHERE
            o.order_type_id = 'RMA_RETURN' AND                                                                             -- 销退入库
            og.rec_id <> '' AND og.rec_id IS NOT NULL AND
            NOT EXISTS (SELECT 1 FROM sales_invoice_item WHERE inventory_transaction_id = d.inventory_transaction_id) AND  -- 没有生成过销售发票的 
            d.quantity_on_hand_diff > 0 AND d.cancellation_flag <> 'Y' AND                                                 -- 按订单的入库时间查询
            ii.status_id IN ( 'INV_STTS_AVAILABLE', 'INV_STTS_USED', 'INV_STTS_DEFECTIVE' ) 
            {$conds} AND ". party_sql('o.party_id') ."
        LIMIT 2000    
    ";
    $ref_return_order_fields = $ref_return_order_rowset = array();
    $return_goods_rowset = $slave_db->getAllRefby($sql, array('order_id'), $ref_return_order_fields, $ref_return_order_rowset, false);
    
    // 取得销退订单的返利金额和开票金额
    if ($return_goods_rowset) {
        // 取得每条记录的返利金额         
        sales_invoice_get_item_rebate($ref_return_order_rowset['order_id']);
        $return_order_list = sales_invoice_get_order_list($ref_return_order_rowset['order_id'], $ref_return_order_fields, $main_distributor_id, 'RETURN'); 
    }
    
    $smarty->assign('return_order_list', $return_order_list);  // 销退入库的订单列表
    $smarty->assign('sales_order_list', $sales_order_list);  // 销售出库的订单列表
}

// 导出
if ($act == 'export') {
    sales_invoice_request_export($sales_order_list, $return_order_list);
}

/**
 * 提交处理
 * 对用户选中的订单查询出来的出库记录做相应处理，生成一张销售发票和发票明细
 * 
 * 发票明细项包括：已出库商品，商品的返利，订单的运费
 * 发票总金额 = SUM(各个明细项的数量 X 金额)
 * 其中销向部分和销退部分的抵消关系为：
 * 销向部分：
 *   商品项 ： 金额 (+)  数量 (+)
 *   返利项：  金额 (-)  数量 (+)
 *   运费项：  金额 (+)  数量 (+)
 * 销退部分：  
 *   商品项 ： 金额 (+)  数量 (-)
 *   返利项：  金额 (-)  数量 (-)
 *   运费项：  金额 (+)  数量 (-)
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && $act == 'invoice')
{
    do {
        if (!party_explicit($_SESSION['party_id'])) {
            $message = '您的业务形态不确定，不能生成发票';
            break;
        }
        
        if (!$main_distributor_id) {
            $message = '请先指定分销商';
            break;
        }
        
        if (empty($_POST['order_id'])) {
            $message = '请先选择需要生成发票的项目';
            break;
        }

        // 检查是否查询出记录了
        if (empty($sales_goods_rowset) && empty($return_goods_rowset)) {
            $message = '没有查询到需要生成发票的项目';
            break;
        }
        
        // 检查是否填写了税率
        if (empty($_POST['tax_rate']) || !preg_match("/^0\.\d+$/", $_POST['tax_rate'])) {
            $message = '税率填写不正确';
            break;
        }

        /**
         * 生成发票头
         */ 
        // 取得分销商名
        $main_distributor_name = $db->getOne("SELECT name FROM main_distributor WHERE main_distributor_id = '{$main_distributor_id}'", true);
        // 税率
        $tax_rate = (float)$_POST['tax_rate'];
        $sql = "
            INSERT INTO sales_invoice 
            (type, party_id, partner_id, partner_name, status, red_flag, tax_rate, created_user, created_stamp, updated_stamp) VALUES 
            ('NORMAL', '{$_SESSION['party_id']}', '{$main_distributor_id}', '{$main_distributor_name}', 'INIT', 'N', '{$tax_rate}', '{$_SESSION['admin_name']}', NOW(), NOW())
        ";
        $result = $db->query($sql, 'SILENT');
        $sales_invoice_id = $db->insert_id();
        if (!$result || !$sales_invoice_id) {
            $message = '发票生成失败，不能生成发票头，请检查数据库';
            break;
        }
        
        /**
         * 添加发票明细 
         * , rebate_amount, prepayment_amount
         */ 
        $total_amount = $total_net_amount = $total_tax = $total_rebate_amount = 0;  // 计算总金额，总税额，总去税金额        
        if (trim($_SESSION['party_id']) == 65558) {
        	//计算主分销商下对应分销商及返点金额
		    $sql_m = "
		    	select (sum(t.amount) -
		    			IFNULL((select sum(ii.rebate_amount)
						from ecshop.sales_invoice i
						left join ecshop.sales_invoice_item ii on i.sales_invoice_id = ii.sales_invoice_id
						where i.partner_id = '{$main_distributor_id}' and i.party_id = '{$_SESSION['party_id']}'
						group by i.party_id, i.partner_id), 0)) as amount
				from romeo.prepayment_account a
				left join romeo.prepayment_transaction t on a.prepayment_account_id = t.prepayment_account_id and t.is_rebate = 1
				where a.supplier_id = '{$main_distributor_id}' and a.prepayment_account_type_id = 'DISTRIBUTOR' 
					and a.party_id = '{$_SESSION['party_id']}'
			 	group by a.prepayment_account_id	
		    ";
		    $total_rebate_amount = $db->getOne($sql_m);
        }
        $sql = "
            INSERT INTO sales_invoice_item (
                sales_invoice_id, item_model, item_type, item_name, order_id, product_id, order_goods_id, 
                goods_id, style_id, serial_number, unit_price, unit_net_price, unit_tax, quantity, 
                inventory_transaction_id, created_user, created_stamp, updated_stamp, rebate_amount, prepayment_amount) VALUES %s 
        ";
        
        $adjust_amount_list = array();
        // 销售部分
        if (!empty($sales_goods_rowset)) {
            $snatch = $order_stack = $order_bonus=array();
            // 循环销售出库明细项生成sql，如果订单含有运费，每个订单还需要生成一条运费的明细
            foreach ($sales_goods_rowset as $row) {
            	$adjust_amount = $rebate_amount = 0;
                $unit_tax = round($tax_rate * $row['goods_price'], 4);          // 单个税额
                $unit_net_price = round(($row['goods_price'] - $unit_tax), 4);  // 不含税单价
                
                if (trim($_SESSION['party_id']) == 65558) {
                	$key_a = $row['order_id'] . "_" . $row['goods_id'] . "_" . $row['style_id'];
	          		if (!isset($adjust_amount_list[$key_a])) {
	          			$sql_a = "
	          				select sum(amount) as amount, sum(num) as num from ecshop.distribution_order_adjustment 
	                		where order_id = '{$row['order_id']}' and goods_id = '{$row['goods_id']}' and style_id = '{$row['style_id']}'
	                		 		and status = 'CONSUMED'
	                		group by order_id, goods_id, style_id";
	                	$adjust_info = $db->getRow($sql_a);
	                	if (!empty($adjust_info)) {
		                	$adjust_amount_list[$key_a] = $adjust_info;
	                	}
	          		}
	                if (($sales_order_list[$row['order_id']]['total_refund_amount'] == 0) && isset($adjust_amount_list[$key_a])) {
	                	$refund_order = $db->getOne("select 1 from ecshop.order_relation where root_order_id = '{$row['order_id']}' limit 1;"); 
	                	if (empty($refund_order)) {
	                		if (($total_rebate_amount >= ($adjust_info['amount']/$adjust_info['num'])) && ($total_rebate_amount > 0)) {
								$rebate_amount = $adjust_info['amount']/$adjust_info['num'];
								$total_rebate_amount -= ($adjust_info['amount']/$adjust_info['num']);
								$adjust_amount = 0;
							} else {
								$rebate_amount = $total_rebate_amount;
								$total_rebate_amount = 0 ;
								$adjust_amount = ($adjust_info['amount']/$adjust_info['num']) - $rebate_amount;
							}
	                	} else {
	                		$rebate_amount = 0;
	                		$adjust_amount =  $adjust_info['amount']/$adjust_info['num'];
	                	}
						$adjust_amount_list[$key_a]['amount'] -= $rebate_amount;
						$total_amount += $row['goods_price'] * $row['goods_number'] + $adjust_amount;
						$total_tax  += ($row['goods_price'] * $row['goods_number'] + $adjust_amount) * $tax_rate;  // 发票头总税额累加
			        } else {
		        	    $total_amount += $row['goods_price'] * $row['goods_number'];              // 发票头总金额累加
	            		$total_tax  += $row['goods_price'] * $row['goods_number'] * $tax_rate;  // 发票头总税额累加
			        }
                } else {
                	$total_amount += $row['goods_price'] * $row['goods_number'];              // 发票头总金额累加
	            	$total_tax  += $row['goods_price'] * $row['goods_number'] * $tax_rate;  // 发票头总税额累加
                }
                //预存款
                $snatch[] = "
                    ('{$sales_invoice_id}', '{$row['goods_name']}', 'GOODS', '{$row['goods_name']}', '{$row['order_id']}', '{$row['product_id']}',
                    '{$row['rec_id']}', '{$row['goods_id']}', '{$row['style_id']}', '{$row['serial_number']}', 
                    '{$row['goods_price']}', '{$unit_net_price}', '{$unit_tax}', '{$row['goods_number']}', 
                    '{$row['inventory_transaction_id']}', '{$_SESSION['admin_name']}', NOW(), NOW(), '{$rebate_amount}', '{$adjust_amount}')
                ";
                
                // 欧酷的返利清零
                if ($row['main_distributor_id'] == 25) {
                    $row['rebate_amount'] = 0;
                }
                
                // 如果该项有返款, 则返款也要生成一条发票明细
                if ($row['rebate_amount'] > 0) {
                    $unit_tax = round($tax_rate * $row['rebate_amount'], 4);         // 单个税额
                    $unit_net_price = round(($row['rebate_amount'] - $unit_tax), 4);  // 不含税单价
                    
                    $total_amount -= $row['rebate_amount'] * $row['goods_number'];              // 发票头总金额累加
                    $total_tax    -= $row['rebate_amount'] * $row['goods_number'] * $tax_rate;  // 发票头总税额累加
                    
                    $snatch[] = "
                        ('{$sales_invoice_id}', '{$row['goods_name']} [商品折扣]', 'DISCOUNT', '{$row['goods_name']} [商品折扣]', '{$row['order_id']}', '{$row['product_id']}', 
                        '{$row['rec_id']}', '{$row['goods_id']}', '{$row['style_id']}', '{$row['serial_number']}', 
                        '-{$row['rebate_amount']}', '-{$unit_net_price}', '-{$unit_tax}', '{$row['goods_number']}',
                        '{$row['inventory_transaction_id']}', '{$_SESSION['admin_name']}', NOW(), NOW(), 0, 0)
                    ";
                }
                
                // 如果订单的运费不为0， 则运费也需要生成一条发票明细
                if (!in_array($row['order_id'], $order_stack)) {
                    array_push($order_stack, $row['order_id']);  // 避免多条明细属于一个订单的情况
                    
                    if ($row['shipping_fee'] > 0) {
                        $unit_tax = round($tax_rate * $row['shipping_fee'], 4);         // 单个税额
                        $unit_net_price = round(($row['shipping_fee'] - $unit_tax), 4);  // 不含税单价
                        
                        $total_amount += $row['shipping_fee'];              // 发票头总金额累加
                        $total_tax    += $row['shipping_fee'] * $tax_rate;  // 发票头总税额累加

                        $snatch[] = "
                            ('{$sales_invoice_id}', '发货 [运费]', 'FEE', '订单{$row['order_sn']}的[运费]', '{$row['order_id']}', '', '', '', '', '', 
                            '{$row['shipping_fee']}', '{$unit_net_price}', '{$unit_tax}', '1',
                            '', '{$_SESSION['admin_name']}', NOW(), NOW(), 0, 0)
                        ";
                    }
                }
                
                //金佰利和安满以红包的形式来代替返利，故添加
                if(!in_array($row['order_id'],$order_bonus) && ($_SESSION['party_id']=='65558' || $_SESSION['party_id'] == '65569' || $_SESSION['party_id'] == '65571')){
                	array_push($order_bonus,$row['order_id']);
                	if($row['bonus']<0){
                		$unit_tax = round($tax_rate * $row['bonus'], 4);
	                    $unit_net_price = round(($row['bonus'] - $unit_tax), 4);
                		$total_amount +=$row['bonus'];
                		$total_tax    +=$row['bonus']*$tax_rate;
                    $snatch[]="
                         ('{$sales_invoice_id}', '[红包]', 'bonus', '订单{$row['order_sn']}的[红包]', '{$row['order_id']}', '', '', '', '', '', 
                         '{$row['bonus']}', '{$unit_net_price}', '{$unit_tax}', '1',
                         '', '{$_SESSION['admin_name']}', NOW(), NOW(), 0, 0)
                    ";
                	}
                }
            }
           
            if (!empty($snatch)) {
                $result = $result && $db->query(sprintf($sql, implode(',', $snatch)), 'SILENT');    
            }
        }
        
        // 销退部分
        if (!empty($return_goods_rowset)) {
            $snatch = $order_stack = $order_bonus=array();
            $rebate_prepayment_amount_total = 0;
            $rebate_prepayment_note = "sales_invoice_id:".$sales_invoice_id;
            foreach ($return_goods_rowset as $row) {
            	$adjust_amount = $rebate_amount = 0;
                $unit_tax = round($tax_rate * $row['goods_price'], 4);         // 税额
                $unit_net_price = round(($row['goods_price'] - $unit_tax), 4);  // 不含税单价
                
//                $total_amount -= $row['goods_price'] * $row['goods_number'];              // 总金额
//                $total_tax    -= $row['goods_price'] * $row['goods_number'] * $tax_rate;  // 总税额
                $key_a = $row['order_id'] . "_". $row['goods_id'] . "_" .$row['style_id'];
                $num = isset($adjust_amount_list[$key_a]['num']) ? $adjust_amount_list[$key_a]['num'] : 0;
            	$sql_a = "
					select i.rebate_amount,i.quantity, i.prepayment_amount
					from ecshop.order_relation r
					left join ecshop.sales_invoice_item i on r.root_order_id = i.order_id
					where r.order_id = '{$row['order_id']}' and i.goods_id = '{$row['goods_id']}' and i.style_id = '{$row['style_id']}'
					order by prepayment_amount desc
					limit 1 offset $num
				";
            	$adjust_info = $db->getRow($sql_a);
            	if (!empty($adjust_info)) {
                	$adjust_amount_list[$key_a] = $adjust_info;
                	$adjust_amount_list[$key_a]['num'] = 
                		(isset($adjust_amount_list[$key_a]['num']) ? $adjust_amount_list[$key_a]['num'] : 0) + 1;
                	$rebate_amount = $adjust_info['rebate_amount'] * -1;
                	$adjust_amount = $adjust_info['prepayment_amount'] * -1;
                	$total_amount -= ($row['goods_price'] * $row['goods_number'] +$adjust_info['prepayment_amount']);
					$total_tax  -= (($row['goods_price'] * $row['goods_number'] +$adjust_info['prepayment_amount']) * $tax_rate);  // 发票头总税额累加
            	} 
            	else {
	        	    $total_amount -= $row['goods_price'] * $row['goods_number'];              // 发票头总金额累加
            		$total_tax  -= $row['goods_price'] * $row['goods_number'] * $tax_rate;  // 发票头总税额累加
		        }
				$rebate_prepayment_amount_total += $rebate_amount;
				$rebate_prepayment_note .= "订单号：{$row['order_sn']}金额：".$rebate_amount;
                $snatch[] = "
                    ('{$sales_invoice_id}', '{$row['goods_name']}', 'GOODS', '{$row['goods_name']}', '{$row['order_id']}', '{$row['product_id']}',
                    '{$row['rec_id']}', '{$row['goods_id']}', '{$row['style_id']}', '{$row['serial_number']}', 
                    '{$row['goods_price']}', '{$unit_net_price}', '{$unit_tax}', '-{$row['goods_number']}', 
                    '{$row['inventory_transaction_id']}', '{$_SESSION['admin_name']}', NOW(), NOW(), '{$rebate_amount}', '{$adjust_amount}')
                ";

                // 欧酷的返利清零
                if ($row['main_distributor_id'] == 25) {
                    $row['rebate_amount'] = 0;
                }
                
                // 如果该项有返款, 则返款也要生成一条发票明细
                if ($row['rebate_amount'] > 0) {
                    $unit_tax = round($tax_rate * $row['rebate_amount'], 4);         // 单个税额
                    $unit_net_price = round(($row['rebate_amount'] - $unit_tax), 4);  // 不含税单价
                    
                    $total_amount += $row['rebate_amount'] * $row['goods_number'];              // 发票头总金额累加
                    $total_tax    += $row['rebate_amount'] * $row['goods_number'] * $tax_rate;  // 发票头总税额累加
                    
                    $snatch[] = "
                        ('{$sales_invoice_id}', '[退货抵消折扣]', 'DISCOUNT', '{$row['goods_name']} [退货抵消折扣]', '{$row['order_id']}', '{$row['product_id']}', 
                        '{$row['rec_id']}', '{$row['goods_id']}', '{$row['style_id']}', '{$row['serial_number']}', 
                        '-{$row['rebate_amount']}', '-{$unit_net_price}', '-{$unit_tax}', '-{$row['goods_number']}',
                        '{$row['inventory_transaction_id']}', '{$_SESSION['admin_name']}', NOW(), NOW(), 0, 0)
                    ";
                }
                
                // 运费项
                if (!in_array($row['order_id'], $order_stack)) {
                    array_push($order_stack, $row['order_id']); 
                    
                    if ($row['shipping_fee'] > 0) {
                        $unit_tax = round($tax_rate * $row['shipping_fee'], 4);         // 税额
                        $unit_net_price = round(($row['shipping_fee'] - $unit_tax), 4);  // 不含税单价
                        
                        $total_amount -= $row['shipping_fee'];              // 总金额
                        $total_tax    -= $row['shipping_fee'] * $tax_rate;  // 总税额
                        
                        $snatch[] = "
                            ('{$sales_invoice_id}', '[退货抵消运费]', 'FEE', '订单{$row['order_sn']}的[运费]', '{$row['order_id']}', '', '', '', '', '', 
                            '{$row['shipping_fee']}', '{$unit_net_price}', '{$unit_tax}', '-1',
                            '', '{$_SESSION['admin_name']}', NOW(), NOW(), 0, 0)
                        ";
                    }
                }
                
                //金佰利和安满以红包的形式来代替返利，故添加
	            if(!in_array($row['order_id'],$order_bonus) && ($_SESSION['party_id'] == '65558' || $_SESSION['party_id'] == '65569' || $_SESSION['party_id'] == '65571')){
	                	array_push($order_bonus,$row['order_id']);
	                	if($row['bonus']<0){
	                		$unit_tax = round($tax_rate * $row['bonus'], 4);
	                		$unit_net_price = round(($row['bonus'] - $unit_tax), 4);
	                		$total_amount -=$row['bonus'];
	                		$total_tax    -=$row['bonus']*$tax_rate;
	                    $snatch[]="
	                         ('{$sales_invoice_id}', '[红包]', 'bonus', '订单{$row['order_sn']}的[红包]', '{$row['order_id']}', '', '', '', '', '', 
	                         '{$row['bonus']}', '{$unit_net_price}', '{$unit_tax}', '-1',
	                         '', '{$_SESSION['admin_name']}', NOW(), NOW(), 0, 0)
	                    ";
	                	}
	           }
                
            }
            if (!empty($snatch)) {
                $result = $result && $db->query(sprintf($sql, implode(',', $snatch)), 'SILENT');    
            }
        }
        
        if ($result) {
            // 更新发票头的总金额和税额
            $total_net_amount = $total_amount - $total_tax;
            $sql = "
                UPDATE sales_invoice 
                SET total_amount = '{$total_amount}', total_net_amount = '{$total_net_amount}', total_tax = '{$total_tax}' 
                WHERE sales_invoice_id = '{$sales_invoice_id}'
            ";
            $db->query($sql);
            if ($rebate_prepayment_amount_total != 0) {
        		require_once(ROOT_PATH . 'RomeoApi/lib_payment.php');
        		// 添加预付款
	            $result = prepay_add(
	                $main_distributor_id,               // 供应商
	                $_SESSION['party_id'],              // 组织ID
	                '3139383',                          // 支付方式
	                null,                               // 最小金额
	                $rebate_prepayment_amount_total,    // 预付金额
	                date("Y-m-d", time()),              // 付款时间
	                $_SESSION['admin_name'],            // 交易创建人
	                $rebate_prepayment_note,            // 备注
	                'DISTRIBUTOR', 
	                0                          //是否为返点
	            );
	            switch ($result) {
	                case -1 :
	                    $message = '销退订单列表中返点金额转换预存款失败,添加账户失败';
	                    break;
	                case 0:
	                    $message = '销退订单列表中返点金额转换预存款失败,添加预付款失败';
	                    break;
	                default:
	                    $result = prepay_add(
			                $main_distributor_id,               // 供应商
			                $_SESSION['party_id'],              // 组织ID
			                '3139383',                          // 支付方式
			                null,                               // 最小金额
			                $rebate_prepayment_amount_total*-1, // 预付金额
			                 date("Y-m-d", time()),             // 付款时间
			                $_SESSION['admin_name'],            // 交易创建人
			                $rebate_prepayment_note,            // 备注
			                'DISTRIBUTOR', 
			                1                          //是否为返点
			            );
	                    exit;
	            }
        	}
             
            header("Location: sales_invoice_request_list.php?message=".urlencode('已生成发票, 可以去发票管理页面管理和编辑发票号'));
            exit;
        } else {
            // 如果发票明细没有保存成功
            $db->query("DELETE FROM sales_invoice_item WHERE sales_invoice_id = '{$sales_invoice_id}'");
            $db->query("DELETE FROM sales_invoice WHERE sales_invoice_id = '{$sales_invoice_id}'");
            $message = '数据库执行失败';    
        }
        
    } while (false);
    
}
// 主分销商列表
$main_distributor_list = Helper_Array::toHashmap((array)distribution_get_main_distributor_list(), 'main_distributor_id', 'name');
// 分销店铺列表
$distributor_list = Helper_Array::toHashmap((array)distribution_get_distributor_list(), 'distributor_id', 'name');

// 如果有消息则显示消息
if (!empty($message)) {
    $smarty->assign('message', $message);
}
$smarty->assign('main_distributor_zhi_list',$main_distributor_zhi_list);
$smarty->assign('main_distributor_list', $main_distributor_list);
$smarty->assign('distributor_list', $distributor_list);
$smarty->assign('filter', $filter);
$smarty->display('sales_invoice/sales_invoice_request_list.htm');


/**
 * 查询条件
 * 
 * @return string
 */
function _get_conditions($act, & $filter)
{
    global $slave_db;
    
    $conds = " AND (d.created_stamp BETWEEN '{$filter['start']}' AND DATE_ADD('{$filter['end']}', INTERVAL 1 DAY))";
    if ($filter['main_distributor_id']) {
        // 通过主分销商取得分销店铺
        $distributor_list = $slave_db->getCol("
            SELECT distributor_id FROM distributor WHERE status = 'NORMAL' AND main_distributor_id = '{$filter['main_distributor_id']}'
        ");
        if ($distributor_list) {
            $conds .= " AND o.distributor_id " . db_create_in($distributor_list);
        } else {
            $conds .= " AND o.distributor_id = ''";
        }
    }
    
    // 如果动作为生成销售发票，则限制选中的订单
    if ($act == 'invoice' && !empty($_POST['order_id'])) {
        $conds .= " AND o.order_id ". db_create_in($_POST['order_id']) ;
    }
    
    if ($filter['ordersn']) {
    	$conds.= " AND o.order_sn = '{$filter['ordersn']}'";
    }

    return $conds;
}

/**
 * 取得商品项的返利金额  
 */
function sales_invoice_get_item_rebate(& $rowset)
{
    global $db;
    static $rets = array();
    
    // 8月31号之后没有运费不需要计算返利了
    $point=strtotime('2010-08-31');
    
    foreach ($rowset as $order_id => $goods_list) {
        foreach ($goods_list as $key => $g) {
            $ref = & $rowset[$order_id][$key];
            $timestamp=strtotime($ref['order_time']);
            if (($timestamp!==false && $timestamp <= $point) || $ref['shipping_fee']>0) {
	            $idx = $ref['goods_id'] .'_'. $ref['style_id'] . '_' . $ref['order_time'];
	            if (!isset($rets[$idx])) {
	                $rets[$idx] = distribution_get_sale_rebate(0, $ref['goods_id'], $ref['style_id'], $ref['order_time']);
	            }
	            $ref['rebate_amount'] = $rets[$idx];    
            }
        }
    }
}

/**
 * 将开票请求项组装成为订单列表
 * 
 * @param $rowset
 * 
 * @return array
 */
function sales_invoice_get_order_list(& $rowset, & $fields, $main_distributor_id, $type = 'SALE')
{
    $order_list = array();
    if (empty($rowset)) { return $order_list; }
    global $db;
    $total_rebate_amount = 0;
    if ((trim($_SESSION['party_id']) == '65558') && ($type == 'SALE')) {
    	//计算主分销商下对应分销商及返点金额
	    $sql_m = "
	    	select (sum(t.amount) -
	    			IFNULL((select sum(ii.rebate_amount)
					from ecshop.sales_invoice i
					left join ecshop.sales_invoice_item ii on i.sales_invoice_id = ii.sales_invoice_id
					where i.partner_id = '{$main_distributor_id}' and i.party_id = '{$_SESSION['party_id']}'
					group by i.party_id, i.partner_id), 0)) as amount
			from romeo.prepayment_account a
			left join romeo.prepayment_transaction t on a.prepayment_account_id = t.prepayment_account_id and t.is_rebate = 1
			where a.supplier_id = '{$main_distributor_id}' and a.prepayment_account_type_id = 'DISTRIBUTOR' 
				and a.party_id = '{$_SESSION['party_id']}'
		 	group by a.prepayment_account_id	
	    ";
	    $total_rebate_amount = $db->getOne($sql_m);
    } 
 
    // 查询订单的退款状态
    $handle = refund_get_soap_client(); // service服务句柄
    
    foreach ($rowset as $key => $group) {
        // 取得订单头信息
        $order = reset($group);
        $order_list[$key] = array(
            'order_id'       => $order['order_id'],
            'order_sn'       => $order['order_sn'],
            'order_amount'   => $order['order_amount'],
            'distributor_id' => $order['distributor_id'],
            'distribution_purchase_order_sn' => $order['distribution_purchase_order_sn'],
            'order_time'     => $order['order_time'],
            'shipping_fee'   => $order['shipping_fee'],
            'goods_list'     => array(),
        );
        
        // 取得订单的累积退款金额
        try {
            $order_total_refund_money = $handle->getOrderTotalRefundMoney(array('arg0'=>$order['order_id']))->return;
        } catch (SoapFault $e) {
            $order_total_refund_money = 0;
        }
        $order_list[$key]['total_refund_amount'] = $order_total_refund_money;
        $refund_order = $db->getOne("select 1 from ecshop.order_relation where root_order_id = '{$order_list[$key]['order_id']}' limit 1;"); 
        if ((trim($_SESSION['party_id']) == '65558') && ($type == 'SALE') && ($order_total_refund_money == 0)) {
        	//如果没有退款记录，且返点金额大于0，则计算返点金额
	    	//查询预存款使用金额
	    	$sql = "
	    		select sum(amount)
	    		from ecshop.distribution_order_adjustment 
				where order_id = '{$order_list[$key]['order_id']}' and status = 'CONSUMED' and type = 'GOODS_ADJUSTMENT'
				group by order_id
			";
			$adjust_amount = $db->getOne($sql);
			$order_list[$key]['total_adjust_amount'] = $adjust_amount; 
			
	        if (($total_rebate_amount > 0) && ($order_total_refund_money == 0) && empty($refund_order)) {
				if ($total_rebate_amount >= $adjust_amount) {
					$order_list[$key]['total_rebate_amount'] = $adjust_amount;
					$total_rebate_amount -= $adjust_amount;
				} else {
					$order_list[$key]['total_rebate_amount'] = $total_rebate_amount;
					$total_rebate_amount = 0 ;
				}
	        } else {
	        	$order_list[$key]['total_rebate_amount'] = 0;
	        }
	        $order_list[$key]['adjust_amount'] = $order_list[$key]['total_adjust_amount'] - $order_list[$key]['total_rebate_amount'];
        } else if ((trim($_SESSION['party_id']) == '65558') && ($type == 'RETURN')) {
        	$sql = "
        		select o.root_order_id
				from ecshop.sales_invoice_item i
				left join ecshop.order_relation o on i.order_id = o.root_order_id
				where o.root_order_id = i.order_id and o.order_id = '{$order_list[$key]['order_id']}'
				limit 1
        	";
        	$root_order_id = $db->getOne($sql);
        	if (empty($root_order_id)) {
        		$sql = "
        			select (a.amount/a.num)*og.goods_number
					from ecshop.ecs_order_goods og
					left join ecshop.order_relation r on og.order_id = r.order_id
					left join ecshop.distribution_order_adjustment a on og.goods_id = a.goods_id and og.style_id = a.style_id 
							and r.root_order_id = a.order_id and a.status = 'CONSUMED' and a.type = 'GOODS_ADJUSTMENT'
					where og.order_id = '{$order_list[$key]['order_id']}'
					group by r.root_order_id, a.goods_id, a.style_id
				";
				$adjust_amount = array_sum($db->getCol($sql));
				$order_list[$key]['total_adjust_amount'] = empty($adjust_amount) ? 0 : $adjust_amount*-1; 
				$order_list[$key]['total_rebate_amount'] = 0;
				$order_list[$key]['adjust_amount'] = empty($adjust_amount) ? 0 : $adjust_amount*-1;
			
        	} else {
        		$sql_g = "select og.goods_id, og.style_id, sum(og.goods_number) as number
        			from ecshop.ecs_order_goods og
        			where og.order_id = '{$order_list[$key]['order_id']}'
        			group by og.order_id, og.goods_id, og.style_id";
	        	$goods_info = $db->getAll($sql_g);
	        	$order_list[$key]['total_adjust_amount'] = $order_list[$key]['total_rebate_amount'] = $order_list[$key]['adjust_amount'] = 0; 
	        	foreach ($goods_info as $k => $v) {
	        		$sql_a = "
	        			select rebate_amount as rebate, prepayment_amount as prepayment
	        			from ecshop.sales_invoice_item 
	        			where order_id = '{$root_order_id}' and goods_id = '{$v['goods_id']}' and style_id = '{$v['style_id']}'
	        			order by prepayment_amount desc
	        			limit {$v['number']} OFFSET {$k}
	        		";
	        		$sales_item = $db->getRow($sql_a);
					$order_list[$key]['total_adjust_amount'] += $sales_item['prepayment'];
	        		$order_list[$key]['total_rebate_amount'] += $sales_item['rebate'];
					$order_list[$key]['adjust_amount'] += $sales_item['prepayment'];
	        	}
	         	$order_list[$key]['total_adjust_amount'] = $order_list[$key]['total_adjust_amount'] * -1;
	        	$order_list[$key]['total_rebate_amount'] = $order_list[$key]['total_rebate_amount'] * -1;
	        	$order_list[$key]['adjust_amount'] = $order_list[$key]['adjust_amount'] * -1;
        	}

        }
        
        // 订单商品的返利金额和商品总金额
        $goods_rebate_amount = $goods_total_amount = 0;
        
        // 取得订单的商品列表  
        foreach($rowset[$key] as $item) {
            $goods_style_id = $item['goods_id'] . '_' . $item['style_id'];
            
            // 如果是同类商品则合并数量
            if (!isset($order_list[$key]['goods_list'][$goods_style_id])) {
                $order_list[$key]['goods_list'][$goods_style_id] = array(
                    'goods_id'      => $item['goods_id'],
                    'style_id'      => $item['style_id'],
                    'goods_name'    => $item['goods_name'],
                    'goods_price'   => $item['goods_price'],
                    'goods_number'  => $item['goods_number'],
                    'rebate_amount' => $item['rebate_amount'],
                );
            }
            else {
                $order_list[$key]['goods_list'][$goods_style_id]['goods_number'] += $item['goods_number'];
            }
            
            $goods_rebate_amount += $item['rebate_amount'] * $item['goods_number'];
            $goods_total_amount  += $item['goods_price'] * $item['goods_number'];
        }
        
        $order_list[$key]['rebate_amount']  = sprintf('%01.2f', $goods_rebate_amount);  // 订单的返利金额
        $order_list[$key]['goods_amount']   = sprintf('%01.2f', $goods_total_amount);   // 订单的商品金额
        $order_list[$key]['bonus']          = sprintf('%01.2f', $order['bonus']);  // 订单的红包金额
        
        // 欧酷的返利为0
        if ($order['main_distributor_id'] == 25) {
            $order_list[$key]['rebate_amount'] = 0;
        }
        
        // 销向和销退的开票金额计算公式不一样
        if ($type == 'SALE') {
        	if ($order_list[$key]['total_adjust_amount']) {
        		$order_list[$key]['invoice_amount'] = $order_list[$key]['goods_amount'] + $order_list[$key]['shipping_fee'] 
        			+ $order_list[$key]['bonus'] - $order_list[$key]['rebate_amount'] + $order_list[$key]['adjust_amount'];
        	} else {
        		$order_list[$key]['invoice_amount'] = sprintf('%01.2f',
		           ($order_list[$key]['order_amount'] - $order_list[$key]['rebate_amount'])
		        );
        	}
        } else {
            $order_list[$key]['invoice_amount'] = $order_list[$key]['order_amount'] + $order_list[$key]['adjust_amount'];            
        }
    }

    return $order_list;
}

/**
 * 导出销售发票请求
 * 
 * @param array $sales_order_list  销售订单
 * @param array $return_order_list 销退订单
 */
function sales_invoice_request_export($sales_order_list, $return_order_list)
{
    $filename = "销售发票请求";

    set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
    require_once 'PHPExcel.php';
    require_once 'PHPExcel/IOFactory.php';
    $excel = new PHPExcel();
    $excel->getProperties()->setTitle($filename);

    // 销售明细
    if (!empty($sales_order_list)) {
        $sheet = $excel->getActiveSheet();
        $sheet->setTitle('销售明细');
        
        $sheet->setCellValue('A1', "订单号");
        $sheet->setCellValue('B1', "订单金额");
        $sheet->setCellValue('C1', "运费");
        $sheet->setCellValue('D1', "商品名");
        $sheet->setCellValue('E1', "商品数量");
        $sheet->setCellValue('F1', "商品单价");
        $sheet->setCellValue('G1', "商品金额");
        
        $i = 2;
        foreach ($sales_order_list as $order) {
        foreach ($order['goods_list'] as $goods) {
            $sheet->setCellValueExplicit("A{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue("B{$i}", $order['order_amount']);
            $sheet->setCellValue("C{$i}", $order['shipping_fee']);
            $sheet->setCellValue("D{$i}", $goods['goods_name']);
            $sheet->setCellValue("E{$i}", $goods['goods_number']);
            $sheet->setCellValue("F{$i}", $order['goods_price']);
            $sheet->setCellValue("G{$i}", $order['goods_amount']);
            $i++;
        }}
    }
    
    // 销退明细
    if (!empty($return_order_list)) {
        $sheet2 = $excel->createSheet();
        $sheet2->setTitle('销退明细');
        
        $sheet2->setCellValue('A1', "订单号");
        $sheet2->setCellValue('B1', "订单金额");
        $sheet2->setCellValue('C1', "运费");
        $sheet2->setCellValue('D1', "商品名");
        $sheet2->setCellValue('E1', "商品数量");
        $sheet2->setCellValue('F1', "商品单价");
        $sheet2->setCellValue('G1', "商品金额");
        $i = 2;
        foreach ($return_order_list as $order) {
        foreach ($order['goods_list'] as $goods) {   
            $sheet2->setCellValueExplicit("A{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet2->setCellValue("B{$i}", $order['order_amount']);
            $sheet2->setCellValue("C{$i}", $order['shipping_fee']);
            $sheet2->setCellValue("D{$i}", $goods['goods_name']);
            $sheet2->setCellValue("E{$i}", $goods['goods_number']);
            $sheet2->setCellValue("F{$i}", $goods['goods_price']);
            $sheet2->setCellValue("G{$i}", $order['goods_amount']);
            
            $i++;
        }}
    }
    
    // 输出
    if (!headers_sent()) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');
        $output = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $output->save('php://output');
        exit;
    }
}

?>
