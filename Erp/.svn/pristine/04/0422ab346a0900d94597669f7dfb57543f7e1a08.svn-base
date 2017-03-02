<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('includes/cls_pagination.php');
require_once ('function.php');
// include_once ('includes/lib_order_mixed_status.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
 admin_priv ( 'order_batch_validate_double11' );

// 违禁品快递
$contraband_shipping = get_contraband_shipping();

$fenxiao_order_batch_validate = false;
if( check_admin_user_priv($_SESSION['admin_name'], 'fenxiao_order_batch_validate') ) {
	$fenxiao_order_batch_validate = true;
	$smarty->assign('fenxiao_order_batch_validate', $fenxiao_order_batch_validate);
}


if($_REQUEST['act'] == 'list'){
    $facility_id =  isset($_REQUEST['facility_id']) && ($_REQUEST['facility_id'] != -1) ? $_REQUEST['facility_id'] : null; 
    $is_jzh = isset($_REQUEST['is_jzh']) && ($_REQUEST['is_jzh'] != -1) ? $_REQUEST['is_jzh'] : null; 
    $outer_id = $_REQUEST['outer_id'];
    $is_tc = $_REQUEST['is_tc'];
    $goods_number = isset($_REQUEST['goods_number']) && is_numeric($_REQUEST['goods_number']) ? $_REQUEST['goods_number'] : null; 
    $fenxiao = isset($_REQUEST['fenxiao']) && ($_REQUEST['fenxiao'] != -1) ? $_REQUEST['fenxiao'] : null; 
    
    $message = $_REQUEST['message'];
    
	    
    
    $parameter['facility_id'] = $facility_id;
    $parameter['is_jzh'] = $is_jzh;
    $parameter['outer_id'] = $outer_id;
    $parameter['is_tc'] = $is_tc;
    $parameter['goods_number'] = $goods_number;
    $parameter['fenxiao'] = $fenxiao;
 
    $condition = '';
    
    if (isset($parameter['facility_id']) && $parameter['facility_id'] != -1) {
        $condition .= " AND o.facility_id = '{$parameter['facility_id']}'";
    }
    if (isset($parameter['is_jzh']) && $parameter['is_jzh'] == 1) {
    	$condition .= " AND o.province in (12, 11, 10)";
    }
 	if (isset($parameter['is_jzh']) && $parameter['is_jzh'] == 2) {
    	$condition .= " AND o.province not in (12, 11, 10)";
    }
    
    if (isset($parameter['fenxiao']) && $parameter['fenxiao'] == 1) {
    	$condition .= " AND md.type = 'zhixiao'";
    }
 	if (isset($parameter['fenxiao']) && $parameter['fenxiao'] == 2) {
    	$condition .= " AND md.type = 'fenxiao'";
    }
    
    if (isset($parameter['is_tc']) && $parameter['is_tc'] == 1) {
    	$outer_ary = explode('_', $parameter['outer_id']);
        $condition .= " AND og.goods_id = {$outer_ary[0]}";
        if ($outer_ary[1]) {
        	$condition .= " AND og.style_id = {$outer_ary[1]}";
        }
        if ($parameter['goods_number']) {
        	$condition .= " AND og.goods_number = {$parameter['goods_number']}";
        }
        $condition .= " AND not exists (select 1 from ecshop.ecs_order_goods ogg where ogg.order_id = o.order_id and ogg.rec_id <> og.rec_id limit 1 )";
    }
    
 	if (isset($parameter['is_tc']) && $parameter['is_tc'] == 2) {
 		//套餐
        $condition .= " AND oa.attr_name = 'TAOBAO_ITEM_MEAL_NAME_EX' AND oa.attr_value = '{$parameter['outer_id']};'";
    }

    // 上海仓不发圆通和韵达
    $condition .= " AND ( o.facility_id not in('19568549', '3633071', '22143846', '22143847', '24196974') or o.shipping_id not in('85','100') ) ";
    
    $party_ary = array('16','65558','65569','65581','65571','65547');
	if (in_array($_SESSION['party_id'], $party_ary)) {
		$condition .= " AND md.type ='zhixiao' ";
	}
	//客服管理模块下面只显示直销订单，分销订单在分销管理模块下面查看 添加md.type = 'zhixiao' 条件
	// 增加过滤条件 已付款，待配货才能批量确认  ，配件添加，品牌特卖 ，员工自提，活动提醒，ecco不能超卖 ljzhou 2012.11.9
	$sql = "
	    select 
        o.*,pr.region_name as province_name,cr.region_name as city_name,dr.region_name as district_name
        from `ecshop`.`ecs_order_info` as o 
        left join `ecshop`.`ecs_order_goods` og ON o.order_id = og.order_id
        left join `ecshop`.`order_attribute` as oa on o.order_id = oa.order_id
        left join `ecshop`.`order_attribute` as oa1 on o.order_id = oa1.order_id and oa1.attr_name = 'CANNOT_BATCH_VALIDATE_DOUBLE11' and oa1.attr_value = '{$parameter['goods_number']}'
        left join `ecshop`.`ecs_region` as pr on pr.region_id = o.province   
        left join `ecshop`.`ecs_region` as cr on cr.region_id = o.city 
        left join `ecshop`.`ecs_region` as dr on dr.region_id = o.district
        LEFT JOIN ecshop.distributor d ON d.distributor_id = o.distributor_id
        LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
        where o.order_time >= '2013-10-17' and o.order_time < '2013-11-15' and 
        o.order_type_id = 'sale' {$condition} 
        and " . party_sql ( 'o.PARTY_ID' ) . " 
        and o.province != 0 and o.city != 0 and o.address != '' 
        and o.order_status = 0 and o.pay_status = 2 and o.shipping_status = 0
        and o.shipping_id != '86'
        and oa1.attr_name is null 
        -- 排除赠品和品牌特卖
        and not exists (select * from ecshop.ecs_order_goods ogg where (ogg.goods_name LIKE '%品牌特卖%') and ogg.order_id = o.order_id limit 1)
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
        limit 100 
        ";
	require_once (ROOT_PATH . 'includes/debug/lib_log.php');
	$startTime = microtime(true);
    global $db;
	$orders = $db->getAll($sql);
	$cost_time = microtime(true)-$startTime;
	QLog::log("order_batch_validate_double11,search sql costTime:{$cost_time}");

    foreach ($orders as $key => $item){
    	if (isset($parameter['is_tc']) && $parameter['is_tc'] == 2) {
    		//判断套餐订单商品明细
	 		if ($parameter['goods_number']) {
	        	//数量
		        $sql =  " select t.goods_id, t.style_id, sum(t.tc_number) as tc_number, sum(t.tc_number) * {$parameter['goods_number']} as tc_number2, sum(t.goods_number) as goods_number, sum(t.tc_amount) * {$parameter['goods_number']} as tc_amount, sum(t.goods_amount) as goods_amount
		        		              from (
			        		              select dggi.goods_id, dggi.style_id, sum(dggi.goods_number) as tc_number, 0 as goods_number, sum(dggi.price*dggi.goods_number) as tc_amount, 0 as goods_amount
			        		              from ecshop.distribution_group_goods dgg 
			                              inner join ecshop.distribution_group_goods_item dggi on dgg.group_id = dggi.group_id 
			                              where dgg.code = '{$parameter['outer_id']}'
			                              group by dggi.goods_id, dggi.style_id 
			                              union all  
			                              select ogg.goods_id, ogg.style_id, 0 as tc_number, sum(ogg.goods_number) as goods_number, 0 as tc_amount, sum(ogg.goods_price*ogg.goods_number) as goods_amount
			                              from ecshop.ecs_order_goods ogg 
			                              where {$item['order_id']} = ogg.order_id 
		                                  group by ogg.goods_id, ogg.style_id 
		                              ) as t  
		                              group by t.goods_id, t.style_id 
		                              having tc_number = 0 or goods_number = 0 or tc_number2 <> goods_number or tc_amount <> goods_amount
		        ";
		        if ($db->getAll($sql)) {
		        	//标记错误,下次SQL拉不出来
		        	$db->query("insert into ecshop.order_attribute (order_id, attr_name, attr_value) values ({$item['order_id']}, 'CANNOT_BATCH_VALIDATE_DOUBLE11', '{$parameter['goods_number']}')");
		        	unset($orders[$key]);
		        	continue;
		        }
	        } else {
	        	//无数量
	        	$sql = " select t.goods_id, t.style_id, sum(t.tc_number) as tc_number, sum(t.goods_number) as goods_number
		        		              from (
			        		              select dggi.goods_id, dggi.style_id, sum(dggi.goods_number) as tc_number, 0 as goods_number
			        		              from ecshop.distribution_group_goods dgg 
			                              inner join ecshop.distribution_group_goods_item dggi on dgg.group_id = dggi.group_id 
			                              where dgg.code = '{$parameter['outer_id']}'
			                              group by dggi.goods_id, dggi.style_id 
			                              union all  
			                              select ogg.goods_id, ogg.style_id, 0 as tc_number, sum(ogg.goods_number) as goods_number
			                              from ecshop.ecs_order_goods ogg 
			                              where {$item['order_id']} = ogg.order_id 
		                                  group by ogg.goods_id, ogg.style_id 
		                              ) as t  
		                              group by t.goods_id, t.style_id 
		                              having tc_number = 0 or goods_number = 0 
		                           ";
		        if ($db->getAll($sql)) {
		        //标记错误,下次SQL拉不出来
		        $db->query("insert into ecshop.order_attribute (order_id, attr_name, attr_value) values ({$item['order_id']}, 'CANNOT_BATCH_VALIDATE_DOUBLE11', '{$parameter['goods_number']}')");
		        	unset($orders[$key]);
		        	continue;
		        }
		       $sql = " select count(distinct tt.multiple) as a, sum(if(tt.multiple is null, 1, 0)) as b, count(distinct tt.multiple_amount) as c, sum(tt.multiple_pk) as d from (
		        		            select sum(t.goods_number) / sum(t.tc_number) as multiple, sum(t.goods_amount) / sum(t.tc_amount) as multiple_amount, 
		        		            	if((sum(t.goods_number) / sum(t.tc_number)) <> (sum(t.goods_amount) / sum(t.tc_amount)), 1, 0) as multiple_pk
		        		              from (
			        		              select dggi.goods_id, dggi.style_id, sum(dggi.goods_number) as tc_number, 0 as goods_number, sum(dggi.price*dggi.goods_number) as tc_amount, 0 as goods_amount
			        		              from ecshop.distribution_group_goods dgg 
			                              inner join ecshop.distribution_group_goods_item dggi on dgg.group_id = dggi.group_id 
			                              where dgg.code = '{$parameter['outer_id']}'
			                              group by dggi.goods_id, dggi.style_id 
			                              union all  
			                              select ogg.goods_id, ogg.style_id, 0 as tc_number, sum(ogg.goods_number) as goods_number, 0 as tc_amount, sum(ogg.goods_price*ogg.goods_number) as goods_amount
			                              from ecshop.ecs_order_goods ogg 
			                              where {$item['order_id']} = ogg.order_id 
		                                  group by ogg.goods_id, ogg.style_id 
		                              ) as t  
		                              group by t.goods_id, t.style_id ) as tt
		                              having a > 1 or b > 0 or c > 1 or d > 0 
		       ";
		       if ($db->getAll($sql)) {
		        //标记错误,下次SQL拉不出来
		        $db->query("insert into ecshop.order_attribute (order_id, attr_name, attr_value) values ({$item['order_id']}, 'CANNOT_BATCH_VALIDATE_DOUBLE11', '{$parameter['goods_number']}')");
		        	unset($orders[$key]);
		        	continue;
		        }
	        }
    	}
        $orders[$key]['order_status_name'] = get_order_status($item['order_status']);
        $orders[$key]['shipping_status_name'] = get_shipping_status($item['shipping_status']);
        $orders[$key]['pay_status_name'] = get_pay_status($item['pay_status']);
    }

    $smarty->assign('facility_id',$facility_id);
    $smarty->assign('message',$message);
    $smarty->assign('is_jzh',$is_jzh);
    $smarty->assign('fenxiao',$fenxiao); 
    $smarty->assign('outer_id',$outer_id);
    $smarty->assign('goods_number',$goods_number);
    $smarty->assign('orders',$orders);
    $smarty->assign('count_orders', count($orders));
}

if($_REQUEST['act'] == 'validate'){
    
    $action_user = $_SESSION['admin_name'];
    $orders = $_POST['orders'];
    $to_facility_id = $_POST['to_facility_id'];
    $action_type = $_POST['action_type'];
    
    
    $message = '';
    $shippings = getShippingTypes();
    $result = array();
	if (! empty ( $orders )) {
		foreach ( $orders as $order_id ) {
			$res = array();
			$res['order_id'] = $order_id;
			$order = $shopapi_client->getOrderById ( $order_id );
			if (isset ( $order->orderId )) {
				$res['province'] = $order->provinceName;
				$res['city'] = $order->cityName;
				$res['district'] = $order->districtName;
				$res['address'] = $order->address;
				
				$facility = facility_by_order_Id( $order->orderId);
				$facility_name = $facility[0]['facility_name'];
				$res['facility_name'] = $facility_name;
				
				//批量取消
				if ($action_type == 2) {
					if ($to_facility_id != -1) {
						$to_facility = facility($to_facility_id);
						$to_facility_name = $to_facility[0]['facility_name'];
						
						$GLOBALS['db']->query("update ecshop.ecs_order_info set facility_id = {$to_facility_id} where order_id = {$order->orderId} limit 1" );
						$order->actionUser = $action_user;
						$order->actionNote = "双十一修改配货仓库 由 " . $facility_name . " 改成 " . $to_facility_name;
						$shopapi_client->updateOrder ( $order );
						

					}
					
					$order->orderStatus = 2;
					$order->actionUser = $action_user;
					$order->actionNote = '双十一批量取消订单';
					
					$shopapi_client->updateOrder ( $order );
					// update_order_mixed_status ( $order->orderId, array ('order_status' => 'canceled' ), 'worker', '双十一批量取消订单' );
					
					$db->query("insert into ecshop.order_attribute (order_id, attr_name, attr_value) values ({$order->orderId}, 'BATCH_VALIDATE_DOUBLE11', '2')");
					
					$res['message'] = '取消订单成功';
					$result[] = $res;
					continue;
				}
				
				// 批量确认
				$can_sure_order = true;
//				$erp_price_sql = "select sum(goods_price * goods_number) p FROM ecshop.ecs_order_goods WHERE order_id = " . $order->orderId;
//				$erp_price = $db->getAll ( $erp_price_sql );
//				$erp_price = floatval ( $erp_price [0] [p] );
//				$taobao_price = floatval ( $order->goods_amount );
				//if ($order->order_type_id != 'SALE' || is_jjshouse ( $order ['party_id'] )) {
				//	$can_sure_order = true;
				//}

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
					$res['message'] = "订单号{$order->orderSn}批量确定出错";
				} else if(!$can_sure_xiaobao) {
					$message .= "订单号{$order->orderSn} 邮政小包>3.3kg不让确认</br>";
					$res['message'] = "订单号{$order->orderSn} 邮政小包>3.3kg不让确认";
				} else {
					if ($to_facility_id != -1) {
						$to_facility = facility($to_facility_id);
						$to_facility_name = $to_facility[0]['facility_name'];
						
						
						$GLOBALS['db']->query("update ecshop.ecs_order_info set facility_id = {$to_facility_id} where order_id = {$order->orderId} limit 1" );
						$order->actionUser = $action_user;
						$order->actionNote = "双十一修改配货仓库 由 " . $facility_name . " 改成 " . $to_facility_name;
						$shopapi_client->updateOrder ( $order );
					}

					$order->orderStatus = 1;
					$order->actionUser = $action_user;
					$order->actionNote = '双十一批量确认订单';
					
					$shopapi_client->updateOrder ( $order );
					// update_order_mixed_status ( $order->orderId, array ('order_status' => 'confirmed' ), 'worker', '双十一批量确认订单' );
					
					$db->query("insert into ecshop.order_attribute (order_id, attr_name, attr_value) values ({$order->orderId}, 'BATCH_VALIDATE_DOUBLE11', '1')");
					
					$res['message'] = '确认订单成功';
				}
			} else {
				$message .= "批量操作订单出错,order_id为{$order_id}无关联订单,请与ERP组联系</br>";
				$res['message'] = "批量操作订单出错,order_id为{$order_id}无关联订单,请与ERP组联系";
			}
			$result[] = $res;
		}
	}
	if ($result) {
	header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","双十一批量订单处理结果") . ".csv");
    $out = "ERP单号,淘宝订单号,收货人姓名,收件人手机,收件人电话,省,市,区,详细地址,订单时间 ,原仓库,当前仓库,宝贝标题,处理结果\n";
    foreach ( $result as $order ) {
		$sql="select o.order_sn, o.taobao_order_sn, o.order_time, o.consignee, o.mobile, o.tel, f.facility_name, group_concat(og.goods_name) as goods_name
		     from ecshop.ecs_order_info o
		     inner join ecshop.ecs_order_goods og on o.order_id = og.order_id  
		     left join romeo.facility f on o.facility_id = f.facility_id 
		     where o.order_id = {$order['order_id']} 
		     group by o.order_id limit 1";
		$order2 = $GLOBALS['db']->getAll($sql);
		$a =  array("\r\n", "\n", "\r", ",");
		$address = str_replace($a, ' ', $order['address']);
		$out .= $order2[0]['order_sn'] . "," . $order2[0]['taobao_order_sn'] . "," . $order2[0]['consignee'] . ",";
		$out .= $order2[0]['mobile'] . "," . $order2[0]['tel'] . "," . $order['province'] . ",";
		$out .= $order['city'] . "," . $order['district'] . "," . $address . "," ;
		$out .= $order2[0]['order_time'] . "," . $order['facility_name'] . "," . $order2[0]['facility_name'] . ",";
		$out .= $order2[0]['goods_name'] . "," . $order['message'] . "\n";
	}
    echo iconv("UTF-8","GB18030", $out);
    exit();
	} else {
		$message = "无结果" . $message;
	    print "<script>window.location.href='order_batch_validate_double11.php?act=list&message={$message}';</script>";
	}
}


if($_REQUEST['act'] == 'search_outer_id') {
	$json = new JSON;
	$sql = "select if(gs.style_id is null, g.goods_id, concat(g.goods_id, '_', gs.style_id)) as outer_id, 1 as is_tc
			from ecshop.ecs_goods g
			left join ecshop.ecs_goods_style gs on g.goods_id = gs.goods_id and gs.is_delete=0
			where g.goods_id like '%{$_POST['q']}%' and 
			g.goods_party_id = {$_SESSION['party_id']} and 
			g.is_delete = 0 
			union all 
			select g.code as outer_id, 2 as is_tc
			from ecshop.distribution_group_goods g 
			where g.code like '%{$_POST['q']}%' and
			g.party_id = {$_SESSION['party_id']} and 
			g.status = 'OK'";
	print $json->encode($db->getAll($sql));
	exit();
}
$smarty->assign('facility_list', get_available_facility($_SESSION['party_id']));
$smarty->display('order/order_batch_list_double11.htm');

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
function facility($facility_id) {
	$sql = "
		SELECT 		facility_id, facility_name
		FROM		romeo.facility
		WHERE		facility_id = '{$facility_id}' 
		LIMIT 1
	";
    $facility = $GLOBALS['db']->getAll($sql);
    
    return $facility;
}

/**
 * 返回仓库设施的mapping表
 *
 * @return array
 */
function facility_by_order_Id($order_id) {
	$sql = "
		SELECT 		f.facility_id, f.facility_name
		FROM		ecshop.ecs_order_info o 
		inner join romeo.facility f on o.facility_id = f.facility_id 
		WHERE	o.order_id = {$order_id}
		LIMIT 1
	";
    $facility = $GLOBALS['db']->getAll($sql);
    
    return $facility;
}

?>