<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('includes/cls_pagination.php');
require_once ('function.php');
// include_once ('includes/lib_order_mixed_status.php');

// 批量修改违禁品快递 ljzhou 2013.05.21
admin_priv ( 'batch_modify_contraband_shipping' );

$fenxiao_batch_modify_contraband_shipping = false;
if( check_admin_user_priv($_SESSION['admin_name'], 'fenxiao_batch_modify_contraband_shipping') ) {
	$fenxiao_batch_modify_contraband_shipping = true;
	$smarty->assign('fenxiao_batch_modify_contraband_shipping', $fenxiao_batch_modify_contraband_shipping);
}

// 违禁品快递
$contraband_shipping = get_contraband_shipping();

if($_REQUEST['act'] == 'list'){
    
    $start_time = isset($_REQUEST['start_time']) && ($_REQUEST['start_time'] != '') ? $_REQUEST['start_time'] : null;
    $end_time = isset($_REQUEST['end_time']) && (trim($_REQUEST['end_time']) != '') ? $_REQUEST['end_time'] : null;
    $pay_id = isset($_REQUEST['pay_id']) && ($_REQUEST['pay_id'] != -1) ? $_REQUEST['pay_id'] : null;
    $fenxiao = isset($_REQUEST['fenxiao']) && ($_REQUEST['fenxiao'] != -1) ? $_REQUEST['fenxiao'] : 0; 
    $message = $_REQUEST['message'];
    
    $parameter['start_time'] = $start_time;
    $parameter['end_time'] = $end_time;
    $parameter['pay_id'] = $pay_id;
    $parameter['fenxiao'] = $fenxiao;
 
    $condition = '';
    if (isset($parameter['pay_id']) && $parameter['pay_id'] == 1) {
        $condition .= " AND o.pay_id = '{$parameter['pay_id']}'";
    }
    if (isset($parameter['pay_id']) && $parameter['pay_id'] == 2) {
        $condition .= " AND o.pay_id != 1";
    }
    if (isset($parameter['start_time']) && $parameter['start_time'] != null) {
        $condition .= " AND o.order_time >= '{$parameter['start_time']}'";
    }
    if (isset($parameter['end_time']) && $parameter['end_time'] != null) {
        $condition .= " AND o.order_time <= '{$parameter['end_time']}'";
    }
    if (isset($parameter['fenxiao']) && $parameter['fenxiao'] == 0) {
    	$condition .= " AND md.type = 'zhixiao'";
    }
 	if (isset($parameter['fenxiao']) && $parameter['fenxiao'] == 1) {
    	$condition .= " AND md.type = 'fenxiao'";
    }

	// 排除合并订单，顺丰快递（顺丰空运），顺丰空运—淘宝COD 不能发违禁品
	$sql = "
	    select
        o.*,pr.region_name as province_name,cr.region_name as city_name,dr.region_name as district_name
        from `ecshop`.`ecs_order_info` as o 
        left join `ecshop`.`ecs_order_goods` og ON o.order_id = og.order_id
        left join `ecshop`.`ecs_goods` g ON og.goods_id = g.goods_id
        left join `ecshop`.`ecs_region` as pr on pr.region_id = o.province   
        left join `ecshop`.`ecs_region` as cr on cr.region_id = o.city 
        left join `ecshop`.`ecs_region` as dr on dr.region_id = o.district
        LEFT JOIN ecshop.distributor d ON d.distributor_id = o.distributor_id
        LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
        where 
        o.order_type_id = 'sale' {$condition} 
        and " . party_sql ( 'o.party_id' ) . " 
        and o.order_status = 0 and o.pay_status = 2 and o.shipping_status = 0
        and o.shipping_id ".db_create_in($contraband_shipping)." and g.is_contraband = 1 
         -- 去掉合并订单的
        and
         (
         	not EXISTS 
         	(
         		SELECT count(distinct(os2.order_id)) as num 
         		FROM romeo.order_shipment os
         		LEFT JOIN romeo.order_shipment os2 ON os.shipment_id = os2.shipment_id
         		WHERE 
         		os.order_id = convert(o.order_id using utf8) 
         		having num > 1
         		limit 1
         	)
         )
        group by o.order_id
        order by o.order_time asc 
        ";
        
    global $db;
	$order_list = $db->getCol($sql);

    $pages = new Paginations();
    $pages->page_size = 100;
    $pages->set_query($sql,$GLOBALS ['db']);
    $pagination = $pages->get_simple_output(11);
    $orders = $pages->data_set;
    
    foreach ($orders as $key => $item){
        $orders[$key]['order_status_name'] = get_order_status($item['order_status']);
        $orders[$key]['shipping_status_name'] = get_shipping_status($item['shipping_status']);
        $orders[$key]['pay_status_name'] = get_pay_status($item['pay_status']);
    }

    $smarty->assign('start_time',$start_time);
    $smarty->assign('message',$message);
    $smarty->assign('end_time',$end_time);
    $smarty->assign('pay_id',$pay_id);
    $smarty->assign('fenxiao',$fenxiao); 
    $smarty->assign('pagination',$pagination);
    $smarty->assign('orders',$orders);
    $smarty->assign('shipping_list',getShippingKeyValueList());
    $smarty->display('order/batch_modify_contraband_shipping.htm');
}

if($_REQUEST['act'] == 'modify'){
    $message = '';
    $action_user = $_SESSION['admin_name'];
    $checked_orders = $_REQUEST['checked_orders'];
    $to_shipping_id = $_REQUEST['to_shipping_id'];

    if(in_array($to_shipping_id,$contraband_shipping)) {
    	$message = "目的快递不能是 不能发违禁品的快递！";
    	print "<script>window.location.href='batch_modify_contraband_shipping.php?act=list&message={$message}';</script>";
    	die();
    }
	$flag = strpos($checked_orders, ",");
	if ($flag !== false) {
	    $checked_orders = explode(",", $checked_orders);;
    } else {
    	$checked_orders= array($checked_orders);
    }
    $orders = $checked_orders;
  
    $shippings = getShippingTypes();
	if (! empty ( $orders )) {
		foreach ( $orders as $order_id ) {
			$order = $shopapi_client->getOrderById ( $order_id );
			if (isset ( $order->orderId )) {
				if ($order->orderStatus != 0 || !in_array($order->shippingId, $contraband_shipping)) {
					$message .= "订单号{$order->orderSn}批量修改出错，订单状态或快递已经修改了！</br>";
				} else {
                    $res = change_order_shipping($order_id,$to_shipping_id,'批量修改违禁品快递:');
                    if($res['message'] != "success") {
                    	$message .= $res['message']."</br>";	
                    }
                    // Order Action has been updated in function `change_order_shipping`
					//update_order_mixed_status ( $order->orderId, array ('order_status' => 'unconfirmed' ), 'worker', '批量修改违禁品快递' );
				}
			} else {
				$message .= "批量修改违禁品快递出错,订单号为{$order->orderSn}无关联订单,请与ERP组联系</br>";
			}
		}
	}
	if($message == '') {
		$message .= " 修改成功！";
	}
    print "<script>window.location.href='batch_modify_contraband_shipping.php?act=list&message={$message}';</script>";
}


?>