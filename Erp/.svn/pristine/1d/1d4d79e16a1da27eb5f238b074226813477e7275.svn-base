<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
admin_priv('distribution_order');
$act = (isset($_REQUEST['act']) && trim($_REQUEST['act']))
	? trim($_REQUEST['act']) 
	: null;
$page =  // 当前页码
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;	

$message = "";
if (!empty($act)) {
	global $db;
	$start_time = trim($_REQUEST['startTime']);
	$end_time = trim($_REQUEST['endTime']);
	$order_status = trim($_REQUEST['order_status']);
	$pay_status = trim($_REQUEST['pay_status']);
	$shipping_status = trim($_REQUEST['shipping_status']);
	$distributor_name = trim($_REQUEST['distributor_name']);
	$distributor_id = trim($_REQUEST['distributor_id']);
	$buyer_payment = trim($_REQUEST['buyer_payment']);
	$goods_total_num = trim($_REQUEST['goods_total_num']);
	$order_goods = $_REQUEST['order_goods'];
	if (gettype($order_goods) == "string") {
		$order_goods =  unserialize(base64_decode($order_goods));
	} 
	$con_inventory_time="";
	$con_order="";
	$con_buyer_payment="";
	$con_order_goods="";
	$con_limit="";
	$exp_buyer_payment=" '' as buyer_payment,";
 
	if (!empty($start_time)) {
		$con_inventory_time .= " and iid.created_stamp >= '{$start_time}' ";
	}
	if (!empty($end_time)) {
		$con_inventory_time .= " and iid.created_stamp < '{$end_time}' ";
	}
	if (!empty($order_status) && $order_status != "-1") {
		$con_order .= " and oi.order_status = '{$order_status}' ";
	}
	if (!empty($shipping_status) && $shipping_status != "-1") {
		$con_order .= " and oi.shipping_status = '{$shipping_status}' ";
	}
	if (!empty($pay_status) && $pay_status != "-1") {
		$con_order .= " and oi.pay_status = '{$pay_status}' ";
	}
	
	if (!empty($distributor_id)) {
		$con_order .= " and oi.distributor_id = '{$distributor_id}' ";
	}
	
 
	if (!empty($order_goods)) {
		$str_order_goods="";
		foreach ($order_goods as $item) {
	 
			$str_order_goods .= "({$item['goods_id']}, {$item['style_id']}),";
		}
		$str_order_goods = " (".rtrim($str_order_goods,',').") ";
 
		$con_order_goods = " and (og.goods_id,og.style_id) in {$str_order_goods}" ;
		if (!empty($goods_total_num)) {
		$con_order_goods .= "
			and exists ( select sum(og1.goods_number) from ecshop.ecs_order_goods og1
				where og1.order_id = o.order_id and (og1.goods_id,og1.style_id) in {$str_order_goods} 
				group by og1.order_id
			    having sum(og1.goods_number) >= {$goods_total_num}  limit 1 )";
		}			 
	}
	if (!empty($buyer_payment)) {
		$exp_buyer_payment = " (select attr_value from ecshop.order_attribute as oa2
				where oa2.order_id = o.order_id and oa2.attr_name = 'BUYER_PAYMENT' and oa2.attr_value >= {$buyer_payment} limit 1 )
			 as buyer_payment, ";
		$con_buyer_payment = " and exists (select 1 from ecshop.order_attribute as oa1
				where oa1.order_id = o.order_id and oa1.attr_name = 'BUYER_PAYMENT' and oa1.attr_value >= {$buyer_payment} limit 1)";
	}

	if (!empty($buyer_payment)) {
			 
		$sql_t = "
			select count(*)
			from ecshop.ecs_order_info o  
			inner join ( select  oi.order_id,iid.created_stamp as inventory_time
					       from ecshop.ecs_order_info oi 
					       inner join romeo.inventory_item_detail iid on oi.order_id=cast(iid.order_id as decimal(17,0)) 
					       inner join ecshop.distributor d1 on oi.distributor_id=d1.distributor_id and oi.party_id=d1.party_id 
					       inner join ecshop.main_distributor md1 on d1.main_distributor_id = md1.main_distributor_id
					       where oi.order_type_id = 'SALE'	and oi.party_id = '{$_SESSION['party_id']}' 
					       	 and d1.status = 'NORMAL' and md1.type = 'fenxiao' {$con_inventory_time} {$con_order}  
					       group by oi.order_id	 		 	
					) tmp on tmp.order_id = o.order_id
			left join ecshop.ecs_order_goods og on og.order_id = o.order_id
			left join romeo.rebate_item ri on ri.order_id = convert(o.order_id using utf8) and ri.party_id = o.party_id and ri.distributor_id = o.distributor_id
					 and ri.item_type = 'ORDER_REBATE' and ri.status = 'AUDITED'
			left join romeo.rebate_item_detail id on id.rebate_item_id = ri.rebate_item_id
			left join ecshop.distributor d on o.distributor_id = d.distributor_id and o.party_id = d.party_id
			left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id
			where o.order_type_id = 'sale' and o.party_id = '{$_SESSION['party_id']}' and d.status = 'NORMAL' and md.type = 'fenxiao'
			and not exists(
				select 1 from ecshop.service ss where ss.order_id = o.order_id and ss.service_type = 2 and ss.service_status <> 3
			)and ri.rebate_item_id is null
			". $con_buyer_payment ;
	
	} else {
		$sql_t = "
			select count(*)
			from ecshop.ecs_order_info o 
			inner join ( select  oi.order_id,iid.created_stamp as inventory_time
					       from ecshop.ecs_order_info oi 
					       inner join romeo.inventory_item_detail iid on oi.order_id=cast(iid.order_id as decimal(17,0)) 
					       inner join ecshop.distributor d1 on oi.distributor_id=d1.distributor_id and oi.party_id=d1.party_id 
					       inner join ecshop.main_distributor md1 on d1.main_distributor_id = md1.main_distributor_id
					       where oi.order_type_id = 'SALE'	and oi.party_id = '{$_SESSION['party_id']}' 
					       	 and d1.status = 'NORMAL' and md1.type = 'fenxiao' {$con_inventory_time} {$con_order}  
					       group by oi.order_id	 		 	
					) tmp on tmp.order_id = o.order_id					
			left join ecshop.ecs_order_goods og on og.order_id = o.order_id
			left join romeo.rebate_item ri on ri.order_id =  convert(o.order_id using utf8) and ri.party_id = o.party_id and ri.distributor_id = o.distributor_id
					 and ri.item_type = 'ORDER_REBATE' and ri.status = 'AUDITED'
			left join romeo.product_mapping pm1 on pm1.ecs_goods_id = og.goods_id and pm1.ecs_style_id = og.style_id
			left join romeo.rebate_item_detail id on id.rebate_item_id = ri.rebate_item_id and id.product_id = pm1.product_id
			left join ecshop.distributor d on o.distributor_id = d.distributor_id and o.party_id = d.party_id
			left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id
			where o.order_type_id = 'sale' and o.party_id = '{$_SESSION['party_id']}' and d.status = 'NORMAL' and md.type = 'fenxiao'
			  and not exists(
				  select 1 from ecshop.service ss where ss.order_id = o.order_id and ss.service_type = 2 and ss.service_status <> 3
				)
				and ri.rebate_item_id is null
			". $con_order_goods. $con_buyer_payment;
					
	}
	QLog::log("customerquery:".$sql_t);
	// 按分页取得列表
	$total = $db->getOne($sql_t);
	$page_size = 20;  // 每页数量
	$total_page = ceil($total/$page_size);  // 总页数
	if ($page > $total_page) $page = $total_page;
	if ($page < 1) $page = 1;
	$offset = ($page - 1) * $page_size;
	$limit = $page_size;
	if ($act == "search") {
		$con_limit = " limit $limit OFFSET $offset ";
	}
	if (!empty($buyer_payment)) {
		$sql = "
			select o.order_id, o.order_sn, o.taobao_order_sn, o.distribution_purchase_order_sn, o.order_status, o.pay_status, o.shipping_status, 
				o.order_time, tmp.inventory_time,o.party_id, d.name, id.rebate_item_detail_id, ifnull(sum(id.amount), sum(ri.amount)) as rebate_detail_amount, 
				ri.note as rebate_item_note,
				" .$exp_buyer_payment."
				Cast( group_concat( CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), 
				ifnull(gs.goods_color, ''))) SEPARATOR '\n' ) AS char( 1000 ) ) as gift_goods_name,
				o.distributor_id
			from ecshop.ecs_order_info o 
			inner join ( select  oi.order_id,iid.created_stamp as inventory_time
					       from ecshop.ecs_order_info oi 
					       inner join romeo.inventory_item_detail iid on oi.order_id=cast(iid.order_id as decimal(17,0)) 
					       inner join ecshop.distributor d1 on oi.distributor_id=d1.distributor_id and oi.party_id=d1.party_id 
					       inner join ecshop.main_distributor md1 on d1.main_distributor_id = md1.main_distributor_id
					       where oi.order_type_id = 'SALE'	and oi.party_id = '{$_SESSION['party_id']}' 
					       	 and d1.status = 'NORMAL' and md1.type = 'fenxiao' {$con_inventory_time} {$con_order}  
					       group by oi.order_id	 		 	
					) tmp on tmp.order_id = o.order_id					
			left join romeo.rebate_item ri on ri.order_id = convert(o.order_id using utf8) and ri.party_id = o.party_id and ri.distributor_id = o.distributor_id
					 and ri.item_type = 'ORDER_REBATE' and ri.status = 'AUDITED'
			left join romeo.rebate_item_detail id on id.rebate_item_id = ri.rebate_item_id 
			left join ecshop.distributor d on o.distributor_id = d.distributor_id and o.party_id = d.party_id
			left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id
			left join romeo.product_mapping pm on id.gifts_product_id = pm.product_id
			left join ecshop.ecs_goods_style gs on gs.goods_id = pm.ecs_goods_id and gs.style_id = pm.ecs_style_id and gs.is_delete=0
			left join ecshop.ecs_goods g on g.goods_id = gs.goods_id
			left join ecshop.ecs_style s on s.style_id = gs.style_id
			where o.order_type_id = 'sale' and o.party_id = '{$_SESSION['party_id']}' and d.status = 'NORMAL' and md.type = 'fenxiao'
				and not exists(
					select 1 from ecshop.service ss where ss.order_id = o.order_id and ss.service_type = 2 and ss.service_status <> 3
				) 
				and ri.rebate_item_id is null
				". $con_buyer_payment ."
			group by o.order_id, ri.rebate_item_id
			order by o.order_id desc
			".$con_limit;
	} else {
		$sql = "
			select o.order_id, o.order_sn, o.taobao_order_sn, o.distribution_purchase_order_sn, o.order_status, o.pay_status, o.shipping_status,
				o.party_id, o.order_time,tmp.inventory_time, d.name, CONCAT(g1.goods_name,' ',IF (gs1.goods_color = '' or gs1.goods_color is null , 
				ifnull(s1.color, ''), ifnull(gs1.goods_color, ''))) as goods_name, og.goods_number, og.goods_price,
				id.rebate_item_detail_id, id.amount as rebate_detail_amount, ri.note as rebate_item_note, " .$exp_buyer_payment.
			"CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, '')))  as gift_goods_name, 
			 o.distributor_id, og.goods_id, og.style_id
			from ecshop.ecs_order_info o 
			inner join ( select  oi.order_id,iid.created_stamp as inventory_time
					       from ecshop.ecs_order_info oi 
					       inner join romeo.inventory_item_detail iid on oi.order_id=cast(iid.order_id as decimal(17,0)) 
					       inner join ecshop.distributor d1 on oi.distributor_id=d1.distributor_id and oi.party_id=d1.party_id 
					       inner join ecshop.main_distributor md1 on d1.main_distributor_id = md1.main_distributor_id
					       where oi.order_type_id = 'SALE'	and oi.party_id = '{$_SESSION['party_id']}' 
					       	 and d1.status = 'NORMAL' and md1.type = 'fenxiao' {$con_inventory_time} {$con_order}  
					       group by oi.order_id	 		 	
					) tmp on tmp.order_id = o.order_id				
			left join ecshop.ecs_order_goods og on og.order_id = o.order_id
			left join romeo.rebate_item ri on ri.order_id = convert(o.order_id using utf8) and ri.party_id = o.party_id and ri.distributor_id = o.distributor_id
					 and ri.item_type = 'ORDER_REBATE' and ri.status = 'AUDITED'
			left join romeo.product_mapping pm1 on pm1.ecs_goods_id = og.goods_id and pm1.ecs_style_id = og.style_id
			left join romeo.rebate_item_detail id on id.rebate_item_id = ri.rebate_item_id and id.product_id = pm1.product_id
			left join ecshop.distributor d on o.distributor_id = d.distributor_id and o.party_id = d.party_id
			left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id
			left join ecshop.ecs_goods_style gs1 on gs1.goods_id = og.goods_id and gs1.style_id = og.style_id and gs1.is_delete=0
			left join ecshop.ecs_goods g1 on g1.goods_id = og.goods_id
			left join ecshop.ecs_style s1 on s1.style_id = gs1.style_id
			left join romeo.product_mapping pm on id.gifts_product_id = pm.product_id
			left join ecshop.ecs_goods_style gs on gs.goods_id = pm.ecs_goods_id and gs.style_id = pm.ecs_style_id and gs.is_delete=0
			left join ecshop.ecs_goods g on g.goods_id = gs.goods_id
			left join ecshop.ecs_style s on s.style_id = gs.style_id
			where o.order_type_id = 'sale' and o.party_id = '{$_SESSION['party_id']}' and d.status = 'NORMAL' and md.type = 'fenxiao'
				and not exists(
					select 1 from ecshop.service ss where ss.order_id = o.order_id and ss.service_type = 2 and ss.service_status <> 3
				)
				and ri.rebate_item_id is null
			". $con_order_goods .$con_buyer_payment . $con_limit;
	}
	QLog::log("customerquery:".$sql);
	$goods_list = $db->getAll($sql);
	
	$smarty->assign('order_goods', json_encode($order_goods));
	$smarty->assign('goods_list', $goods_list);
    
    if ($act == "add") {
    	$item_type = trim($_REQUEST['item_type']);
    	$item_note = trim($_REQUEST['note']);
    	$handle = soap_get_client('DistributorRebateService');
    	if ($item_type == "order_rebate") {
    		//订单返点金额
    		$item_type_value = trim($_REQUEST['item_type_value']);
			$order_rebate_ids = array();
			foreach ($goods_list as $key => $item) {
				if (in_array($item['order_id'], $order_rebate_ids)) {
					continue;
				}
				$c = new stdClass();
				$c->partyId = "{$item['party_id']}";
				$c->distributorId = "{$item['distributor_id']}";
				$c->orderId = "{$item['order_id']}";
				$c->amount = $item_type_value;
				$c->itemType = "ORDER_REBATE";
				$c->applyUser = trim($_SESSION['admin_name']);
				$c->approveUser = trim($_SESSION['admin_name']);
				$c->note = $item_note;
				$c->status = "AUDITED";
				$res =  $handle->addRebateRecordByOrderId($c);
				if ($res->return->code != 'SUCCEED') {
					$message .= "订单号：{$item['order_sn']} 添加返点失败\r\n ";
				} else {
					$order_rebate_id[] = $item['order_id'];
				}
			}
    	} elseif ($item_type == "goods_rebate") {
    		//单个商品返点金额
    		$item_type_value = trim($_REQUEST['item_type_value']); //返点金额
    		//查询商品对应的productid
    		$product_ids = array();
    		foreach ($order_goods as $key => $value) {
    			$goods_style_id = $value['goods_id']."_".$value['style_id'];
    			$sql = "select product_id from romeo.product_mapping
						where ecs_goods_id = '{$value['goods_id']}' and ecs_style_id = '{$value['style_id']}'
						limit 1";
				$product_ids[$goods_style_id]['product_id'] = $db->getOne($sql);
    		}
			//计算总返点金额
			$item_total_amount_list = $order_info_list = array();
			foreach ($goods_list as $key => $value) {
				$order_id = $value['order_id'];
				$goods_style_id = $value['goods_id']."_".$value['style_id'];
				if (!isset($order_goods[$goods_style_id])) {
					continue;
				}
				if (isset($order_info_list[$order_id])) {
					$order_info_list[$order_id]['item_total_amount'] += $value['goods_number'] * $item_type_value;
					$order_info_list[$order_id]['rebate_detail_list'][] = array("productId"=>$product_ids[$goods_style_id]['product_id'],
						"productNumber" => $value['goods_number'], 
						"amount" => (double) ($value['goods_number']*$item_type_value), 
						"giftsProductId"=> "", "giftsProductNumber" => (double) 0, "status" => "OK");
				} else {
					$order_info_list[$order_id]['order_id'] = $order_id;
					$order_info_list[$order_id]['order_sn'] = $value['order_sn'];
					$order_info_list[$order_id]['party_id'] = $value['party_id'];
					$order_info_list[$order_id]['distributor_id'] = $value['distributor_id'];
					$order_info_list[$order_id]['order_time'] = $value['order_time'];
					$order_info_list[$order_id]['rebate_detail_list'][] = array("productId"=>$product_ids[$goods_style_id]['product_id'],
						"productNumber" => $value['goods_number'], 
						"amount" => (double) ($value['goods_number']*$item_type_value), 
						"giftsProductId"=> "", "giftsProductNumber" => (double) 0, "status" => "OK",
						);
					$order_info_list[$order_id]['item_total_amount'] += $value['goods_number'] * $item_type_value;
				}
			}
			$order_rebate_ids = array();
			foreach ($order_info_list as $key => $item) {
    			if (in_array($item['order_id'], $order_rebate_ids)) {
					continue;
				}
    			$c = new stdClass();
    			$c->partyId = "{$item['party_id']}";
    			$c->distributorId = "{$item['distributor_id']}";
    			$c->orderId = "{$item['order_id']}";
    			$c->amount = $item['item_total_amount'];
    			$c->itemType = "ORDER_REBATE";
    			$c->applyUser = trim($_SESSION['admin_name']);
    			$c->approveUser = trim($_SESSION['admin_name']);
    			$c->note = $item_note;
    			$c->status = "AUDITED";
    			$c->validDate = date("Y-m-01 H:i:s", strtotime("{$item['order_time']}"));
    			$c->rebate_detail_list = $item['rebate_detail_list'];

    			$res = $handle->accountRebateByOrderId($c);
    			if ($res->return->code != 'SUCCEED') {
    				$message .= " 订单号：{$item['order_sn']} 单个商品 核算返点添加失败\r\n ";
    			}else{
    				$order_rebate_id[] = $item['order_id'];
    			}
    		}
    	} elseif($item_type == "gifts_goods"){
    		//订单送送赠品
    		$rebate_goods_list = $_REQUEST['rebate_goods'];
    		$product_list = array();
			foreach ($rebate_goods_list as $k => $value) {
				$sql = "select product_id from romeo.product_mapping
						where ecs_goods_id = '{$value['goods_id']}' and ecs_style_id = '{$value['style_id']}'
						limit 1";
				$product_id = $db->getOne($sql);
				$product_num = (double)$value['goods_number'];
				$product_list[] = array("productId"=>"", "productNumber" => (double) 0, "amount" => (double) 0, 
					"giftsProductId"=> $product_id, "giftsProductNumber" => $product_num, "status" => "OK");
			}			
			$order_rebate_ids = array();
    		foreach ($goods_list as $key => $item) {
    			if (in_array($item['order_id'], $order_rebate_ids)) {
					continue;
				}
    			$c = new stdClass();
    			$c->partyId = "{$item['party_id']}";
    			$c->distributorId = "{$item['distributor_id']}";
    			$c->orderId = "{$item['order_id']}";
    			$c->amount = (double) 0;
    			$c->itemType = "ORDER_REBATE";
    			$c->applyUser = trim($_SESSION['admin_name']);
    			$c->approveUser = trim($_SESSION['admin_name']);
    			$c->note = $item_note;
    			$c->status = "AUDITED";
    			$c->validDate = date("Y-m-01 H:i:s", strtotime("{$item['order_time']}"));
    			$c->rebate_detail_list = $product_list;
    			$res = $handle->accountRebateByOrderId($c);
    			if ($res->return->code != 'SUCCEED') {
    				$message .= " 订单号：{$item['order_sn']}核算返点添加失败\r\n ";
    			}else{
    				$order_rebate_id[] = $item['order_id'];
    			}
    		}
    	}
    	if (empty($message)) {
    		$message = "添加返点金额或赠品已成功，请勿重复添加";
    	}
    } elseif ($act == "export") {
    	header("Content-type:application/vnd.ms-excel");
		header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","返点核算导出") . ".csv");	
		$out = $smarty->fetch('distributor/account_rebate_csv.htm');
		echo iconv("UTF-8","GB18030", $out);
		exit();	
		die();
    }

	$extra_params = array('startTime' => $start_time, 'endTime' => $end_time, 'order_status' => $order_status,
		'pay_status' => $pay_status, 'shipping_status' => $shipping_status, 'account_type' => $account_type, 
		'distributor_name' => $distributor_name, 'distributor_id' => $distributor_id,
		'buyer_payment' => $buyer_payment, 'goods_total_num' => $goods_total_num, 'item_type'=>$item_type,
		'order_goods' =>base64_encode(serialize($order_goods)), 'page'=>$page);
	$pagination = new Pagination(
	    $total, $page_size, $page, 'page', $url = 'account_rebate.php?act='.$act, null, $extra_params
	);
	$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
	//如果商品搜索不为空，需要展示搜索商品
	$smarty->assign("message", $message);
}

$smarty->display("distributor/account_rebate.htm");
?>