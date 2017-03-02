<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('includes/cls_pagination.php');
require_once ('function.php');
// include_once ('includes/lib_order_mixed_status.php');

admin_priv ( 'order_batch_validate' );

//允许批量确认订单的组织：blackmores、gallo、金佰利、玛氏、贝亲、每伴、中美史克、蒙牛、安满、雀巢、ECCO、ACC、金奇仕、金宝贝 
$party_allow = "('65539','65546','65547','65553','65555','65558','65559','65562','65568','65569','65571','65572','65574','65578')";

// 违禁品快递
$contraband_shipping = get_contraband_shipping();

$fenxiao_order_batch_validate = false;
if( check_admin_user_priv($_SESSION['admin_name'], 'fenxiao_order_batch_validate') ) {
	$fenxiao_order_batch_validate = true;
	$smarty->assign('fenxiao_order_batch_validate', $fenxiao_order_batch_validate);
}


if($_REQUEST['act'] == 'list'){
    
    $start_time = isset($_REQUEST['start_time']) && ($_REQUEST['start_time'] != '') ? $_REQUEST['start_time'] : null;
    $end_time = isset($_REQUEST['end_time']) && (trim($_REQUEST['end_time']) != '') ? $_REQUEST['end_time'] : null;
    $pay_id = isset($_REQUEST['pay_id']) && ($_REQUEST['pay_id'] != -1) ? $_REQUEST['pay_id'] : null;
    $fenxiao = isset($_REQUEST['fenxiao']) && ($_REQUEST['fenxiao'] != -1) ? $_REQUEST['fenxiao'] : 0; 
    $message = $_REQUEST['message'];
    
     // 套餐列表
	$meal_list = meal_list();

	// 当前套餐
	$meal_code = 
	    !empty($_REQUEST['meal_code']) && array_key_exists($_REQUEST['meal_code'], $meal_list)
	    ? $_REQUEST['meal_code']
	    : null ;
	    
	$filter = array('meal_code'=>$meal_code);
    
    $parameter['start_time'] = $start_time;
    $parameter['end_time'] = $end_time;
    $parameter['pay_id'] = $pay_id;
    $parameter['fenxiao'] = $fenxiao;
    $parameter['meal_code'] = $meal_code;
 
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
    	$condition .= " AND o.postscript = ''";
    }
 	if (isset($parameter['fenxiao']) && $parameter['fenxiao'] == 1) {
    	$condition .= " AND md.type = 'fenxiao'";
    	$condition .= " AND o.postscript = ''";
    }
    
 	if (isset($parameter['meal_code']) && $parameter['meal_code'] != null) {
        $condition .= " AND oa.attr_name = 'TAOBAO_ITEM_MEAL_NAME_EX' AND oa.attr_value like '%{$parameter['meal_code']}%'";
    }

    // 上海仓不发圆通和韵达
    $condition .= " AND ( o.facility_id not in('19568549', '3633071', '22143846', '22143847', '24196974') or o.shipping_id not in('85','100') ) ";
	//客服管理模块下面只显示直销订单，分销订单在分销管理模块下面查看 添加md.type = 'zhixiao' 条件
	// 增加过滤条件 已付款，待配货才能批量确认 ,过滤掉赠品，小二留言，客户留言 ，配件添加，品牌特卖 ，员工自提，活动提醒，快递可达或部分可达，ecco不能超卖，订单金额一致 ljzhou 2012.11.9
	$sql = "
	    select
        o.*,pr.region_name as province_name,cr.region_name as city_name,dr.region_name as district_name
        from `ecshop`.`ecs_order_info` as o 
        left join `ecshop`.`ecs_order_goods` og ON o.order_id = og.order_id
        left join `ecshop`.`order_attribute` as oa on o.order_id = oa.order_id
        left join `ecshop`.`order_attribute` as oa1 on o.order_id = oa1.order_id and oa1.attr_name = 'TAOBAO_SELLER_MEMO'
        left join `ecshop`.`ecs_region` as pr on pr.region_id = o.province   
        left join `ecshop`.`ecs_region` as cr on cr.region_id = o.city 
        left join `ecshop`.`ecs_region` as dr on dr.region_id = o.district
        LEFT JOIN ecshop.distributor d ON d.distributor_id = o.distributor_id
        LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
        where 
        o.order_type_id = 'sale' {$condition} 
        and " . party_sql ( 'o.PARTY_ID' ) . " and o.party_id in {$party_allow} 
        and o.province != 0 and o.city != 0 and o.address != '' 
        and o.order_status = 0 and o.pay_status = 2 and o.shipping_status = 0
        and o.shipping_id != '86'
        and oa1.attr_name is null
        
        -- 排除赠品和品牌特卖
        and not exists (select * from ecshop.ecs_order_goods ogg where (ogg.subtitle is not null or ogg.goods_name LIKE '%品牌特卖%') and ogg.order_id = o.order_id limit 1)
        -- 快递可达
        and 
        (
            select ar.arrived
			from ecshop.ecs_order_info oi
			inner join ecshop.ecs_shipping_area sa ON oi.shipping_id = sa.shipping_id
			inner join ecshop.ecs_area_region ar ON sa.shipping_area_id = ar.shipping_area_id
			where (oi.province = ar.region_id or oi.city = ar.region_id or oi.district = ar.region_id)
			and oi.order_id = o.order_id
			order by ar.region_id desc 
			limit 1
        ) in ('PARTLY','ALL')              
        -- 没有活动提醒        
        and not exists
        ( 
          SELECT sum(FIND_IN_SET(o.distributor_id,gr.distributor_ids)) as position 
          FROM ecshop.ecs_group_goods_remind gr 
          where now() >= gr.start_time and now() <= gr.end_time and gr.status = 'OK' 
          and gr.party_id = o.party_id 
          group by o.party_id
          having position > 0 
          limit 1
	    )                                  
	    -- 订单金额一致
	    and not exists
	    (
		    select oii.order_amount as order_amount_price,oii.goods_amount as goods_amount_price,SUM(og2.goods_price * og2.goods_number)  as erp_price,
				ifnull((select 	oa3.attr_value 
				 		from 	ecshop.order_attribute oa3
				 		where 	oa3.order_id = o.order_id
				 		and 	oa3.attr_name = 'TAOBAO_ORDER_AMOUNT' 
				 		limit 1 ),0) as tmall_price,
				ifnull((SELECT 	SUM(r.TOTAL_AMOUNT) 
						FROM 	romeo.refund r
						WHERE 	r.order_id = cast(o.order_id as char(30))
						AND     r.status IN('RFND_STTS_COMPLETED','RFND_STTS_EXECUTED','RFND_STTS_CHECK_OK')
						LIMIT 1 ),0) as refund_price
				from ecshop.ecs_order_info oii
				inner join ecshop.ecs_order_goods og2 ON oii.order_id = og2.order_id
				where oii.order_id = o.order_id
				group by oii.order_id
				having !( abs(goods_amount_price-erp_price)<=0.1 && (abs(tmall_price-refund_price) <= 0.1 or abs(order_amount_price-(tmall_price-refund_price)) <= 0.1 ) )
		)
		-- 除了ecco,金宝贝外，其余都可以超卖
		and 
		(
		  o.party_id not in('65562','65574')
		  or (o.party_id in('65562','65574') and
			     not EXISTS
			     (
	              SELECT im.available_to_reserved,og3.goods_number FROM ecshop.ecs_order_info oi2
			         left join ecshop.ecs_order_goods og3 ON oi2.order_id = og3.order_id
			         left join romeo.product_mapping pm ON pm.ecs_goods_id = og3.goods_id and pm.ecs_style_id = og3.style_id
			         left join romeo.inventory_summary im ON pm.product_id = im.product_id and oi2.facility_id = im.facility_id
			         where im.status_id = 'INV_STTS_AVAILABLE' and oi2.order_id  = o.order_id
	               group by og3.rec_id 
	               having im.available_to_reserved < og3.goods_number
	               limit 1
	              )
              )
         )
         
         -- 没有退款信息
         and
         (
         	not EXISTS 
         	(
         		SELECT order_id 
         		FROM ecshop.taobao_refund
         		WHERE status !='CLOSED'
         		and order_id = o.order_id
         	)
         )
         -- 要求没有违禁品对应的快递
         and
         (
         	not EXISTS 
         	(
         		SELECT 1 
         		FROM ecshop.ecs_order_goods og 
         		LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
         		WHERE 
         		og.order_id = o.order_id and o.shipping_id ".db_create_in($contraband_shipping)." and g.is_contraband = 1
         		limit 1
         	)
         )
        group by o.order_id
        order by o.order_time asc 
        ";
	require_once (ROOT_PATH . 'includes/debug/lib_log.php');
	$startTime = microtime(true);
    global $db;
	$order_list = $db->getCol($sql);
	$cost_time = microtime(true)-$startTime;
	QLog::log("order_batch_validate,search sql costTime:{$cost_time}");

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
    $smarty->assign('meal_code',$meal_code);
    $smarty->assign('pagination',$pagination);
    $smarty->assign('orders',$orders);
    $smarty->assign('meal_list', $meal_list );
    $smarty->assign ( 'filter', $filter );
    $smarty->display('order/order_batch_list.htm');
}

if($_REQUEST['act'] == 'validate'){
    
    $action_user = $_SESSION['admin_name'];
    $orders = $_POST['orders'];
    
    $message = '';
    $shippings = getShippingTypes();
	if (! empty ( $orders )) {
		foreach ( $orders as $order_id ) {
			$order = $shopapi_client->getOrderById ( $order_id );
			if (isset ( $order->orderId )) {
				$can_sure_order = false;
				$erp_price_sql = "select sum(goods_price * goods_number) p FROM ecshop.ecs_order_goods WHERE order_id = " . $order->orderId;
				$erp_price = $db->getAll ( $erp_price_sql );
				$erp_price = floatval ( $erp_price [0] [p] );
				$taobao_price = floatval ( $order->goods_amount );
				if (abs ( $erp_price - $taobao_price ) < 0.1 || $order->order_type_id != 'SALE' || is_jjshouse ( $order ['party_id'] )) {
					$can_sure_order = true;
				}

	            // 邮政小包 > 3.3kg 则不让确认  ljzhou 2013.03.14
	            $can_sure_xiaobao = true;
				if (isset ( $order->shippingId )) {
					if ($order->shippingId == '119') {
						$order_new = getOrderInfo ( $order->orderId );
						$package_weight = get_package_weight ( $order_new );
						$order_weight = get_order_weight ( $order_new );
						$total_weight = $package_weight + $order_weight;
						if ($total_weight > 3300) {
							$can_sure_xiaobao = false;
						}
					}
				}			
				
				if ($order->orderStatus != 0 || ! $can_sure_order) {
					$message .= "订单号{$order->orderSn}批量确定出错</br>";
				} else if(!$can_sure_xiaobao) {
					$message .= "订单号{$order->orderSn} 邮政小包>3.3kg不让确认</br>";
				} else {
                    $order->confirmTime = strtotime(date("Y-m-d H:i:s"));  // 确认订单时间
					$order->orderStatus = 1;
					$order->actionUser = $action_user;
					$order->actionNote = '批量确认订单';
					
					$shopapi_client->updateOrder ( $order );
					// update_order_mixed_status ( $order->orderId, array ('order_status' => 'confirmed' ), 'worker', '批量确认订单' );
				}
			} else {
				$message .= "批量确定订单出错,order_id为{$order_id}无关联订单,请与ERP组联系</br>";
			}
		}
	}
   
    print "<script>window.location.href='order_batch_validate.php?act=list&message={$message}';</script>";
}


/**
 * 根据order的信息合法的得到该order的shippinga_area_id
 * @author Wang Yuan
 * @param   array $order   订单关联数组，至少包含'province' 和 'shipping_id'两个键
 */
function get_shipping_area_id($province_id, $shipping_id) {
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('area_region') . " WHERE region_id = {$province_id};";
    $shipping_area_list = $GLOBALS['db']->getAll($sql);
    $shipping_area_id_list = array();
    foreach ($shipping_area_list as $k => $v) {
        $shipping_area_id_list[] = $v['shipping_area_id'];
    }
    //pp($shipping_area_id_list);
    $str = empty($shipping_area_id_list) ? '(null)' : '('.implode(',', $shipping_area_id_list).')';

    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('shipping_area') . " WHERE shipping_area_id in {$str};";
    
    $list = $GLOBALS['db']->getAll($sql);
    foreach ($list as $key => $val) {
        if ($val['shipping_id'] == $shipping_id) {
            $shipping_area_id = $val['shipping_area_id'];
        }
    }
    return $shipping_area_id;
}

/**
 * 返回仓库设施的mapping表
 *
 * @return array
 */
function meal_list() {
	$meal_mapp = array();
	$sql = "
		SELECT 		*
		FROM		ecshop.distribution_group_goods
		WHERE		status = 'OK'
		AND 		" . party_sql('party_id') . "
		ORDER BY	updated DESC
	";
    $meal_list = $GLOBALS['db']->getAll($sql);
    if ($meal_list) {
    	foreach ($meal_list as $meal) {
    		$meal_mapp[$meal['code']] = $meal['code'] . "	" . $meal['name']; 
    	}
    }
    
    return $meal_mapp;
}

?>