<?php

/**
 * 查看待采清单详情
 */
define('IN_ECS', true);
require_once('includes/init.php');

$goods_id = isset($_REQUEST['goods_id']) && is_numeric($_REQUEST['goods_id']) ? $_REQUEST['goods_id'] : 0;
$style_id = isset($_REQUEST['style_id']) && is_numeric($_REQUEST['style_id']) ? $_REQUEST['style_id'] : 0;
$facility_id = isset($_REQUEST['facility_id']) && is_numeric($_REQUEST['facility_id']) ? $_REQUEST['facility_id'] : 0; //提货清单分仓库显示 ljzhou 2012.9.10  
$type = isset($_REQUEST['type']) && in_array($_REQUEST['type'], array('1', '2','3','4','5','6')) ? $_REQUEST['type'] : 1 ;  // 1表示订购详情， 2表示在途详情
switch ($type) {
	// 查询商品的订购详情
	case '1' :
	case '3' :	
		// 查询出符合条件的所有的记录
		 $sql= "
	     SELECT 
	           og.goods_name, og.goods_number, og.goods_id, og.style_id,oo.order_type_id, 
	           oo.order_id, oo.order_time, oo.order_sn, oo.facility_id,
	           CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id
		FROM   {$ecs->table('order_info')} AS oo use index(party_id,shipping_status)
        LEFT JOIN {$ecs->table('order_goods')} AS og ON oo.order_id = og.order_id 
		LEFT JOIN {$ecs->table('goods')}  AS g ON og.goods_id = g.goods_id 
		left join romeo.facility f on oo.facility_id = f.facility_id
		LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = oo.pay_id 
		WHERE  oo.order_status = 1 AND oo.shipping_status in (0,13)                        -- 已确认还未发货的订单
        AND oo.order_type_id in ('SALE','RMA_EXCHANGE','SHIP_ONLY')                                    -- 不考虑是否付款的问题 
	    AND ". party_sql('oo.party_id') ."
	    AND oo.facility_id = '{$facility_id}'
	    AND not exists(select 1 from romeo.inventory_item_detail iid where iid.order_goods_id = convert(og.rec_id using utf8) limit 1)  -- 不存在出库记录
		AND og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}'                                                          
	  ";	
		break;
		
    case '4' :
    	 // -gt订单详情
		 $sql= "
           SELECT 
		        og.goods_name, og.goods_id, og.style_id, og.status_id,o.facility_id,
		        o.order_id, o.order_time, o.order_sn,  o.order_type_id,
		        CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id,
		        og.goods_number + sum(ifnull(iid.quantity_on_hand_diff,0)) as goods_number
    	   FROM
		        ecshop.ecs_order_info AS o 
	   		    INNER JOIN ecshop.ecs_order_goods AS og ON o.order_id = og.order_id 
	   		    inner join romeo.supplier_return_request_gt srrg on srrg.SUPPLIER_RETURN_GT_SN = o.order_sn
                inner join romeo.supplier_return_request srr on srr.supplier_return_id = srrg.supplier_return_id 
	   		    LEFT  JOIN  romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
    	   WHERE
				o.order_type_id = 'SUPPLIER_RETURN'  
    			AND ". party_sql('o.party_id') ."                                                 
	    		AND o.facility_id = '{$facility_id}'
	    		AND og.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED')  
	    		AND srr.status in ('EXECUTING','CREATED')
	    		AND og.goods_id = '{$goods_id}' 
	    		AND og.style_id = '{$style_id}' AND  srr.check_status != 'DENY'
          GROUP BY og.rec_id,og.status_id  
           having  goods_number > 0
	  ";	
		break;
	case '5' :	
		// 查询未确认数
		 $sql= "
	     SELECT 
	           og.goods_name, og.goods_number, og.goods_id, og.style_id,oo.order_type_id, 
	           oo.order_id, oo.order_time, oo.order_sn, oo.facility_id,
	           CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id
		FROM   {$ecs->table('order_info')} AS oo use index(party_id,shipping_status)
        LEFT JOIN {$ecs->table('order_goods')} AS og ON oo.order_id = og.order_id 
		LEFT JOIN {$ecs->table('goods')}  AS g ON og.goods_id = g.goods_id 
		left join romeo.facility f on oo.facility_id = f.facility_id
		LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = oo.pay_id 
		WHERE  oo.order_status = 0 AND oo.shipping_status = 0                        -- 未确认还未发货的订单
        AND oo.order_type_id in ('SALE','RMA_EXCHANGE','SHIP_ONLY')                                    -- 不考虑是否付款的问题 
	    AND ". party_sql('oo.party_id') ."
	    AND oo.facility_id = '{$facility_id}'
	    AND not exists(select 1 from romeo.inventory_item_detail iid where iid.order_goods_id = convert(og.rec_id using utf8) limit 1)  -- 不存在出库记录
		AND og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}'                                                          
	  ";	
		break;
	case '6' :	
		$sql = "SELECT  og.goods_name, og.goods_number, og.goods_id, og.style_id,o.order_type_id, 
	           o.order_id, o.order_time, o.order_sn, o.facility_id,
	           CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id
			from ecshop.ecs_order_info o
			left join ecshop.ecs_order_goods og on o.order_id = og.order_id
			left join romeo.product_mapping pm on convert(og.goods_id using utf8) = pm.ecs_goods_id and convert(og.style_id using utf8) = pm.ecs_style_id
			left join romeo.inventory_item ii on pm.product_id = ii.product_id and o.facility_id = ii.facility_id 
			where o.order_status in (0,1) 
			-- and o.pay_status = 2 
			-- and o.shipping_status = 0 
			and o.reserved_time = 0 
			and  ". party_sql('o.party_id') ." 
			and o.order_type_id in ('SALE','RMA_EXCHANGE','SHIP_ONLY')
			AND o.facility_id = '{$facility_id}' 
			AND og.goods_id = '{$goods_id}' 
			AND og.style_id = '{$style_id}'  
			and ii.status_id IN ('INV_STTS_AVAILABLE','INV_STTS_USED')     
			group by o.order_id, og.rec_id
			";
		break;
	default  :  
		//  查询商品的在途详情
	    $sql = "
	    	SELECT 
		        og.goods_number - sum(ifnull(iid.quantity_on_hand_diff,0)) as goods_number, og.goods_name, 
		        o.order_id, o.order_time, o.order_sn, o.order_type_id,og.goods_id, og.style_id, o.facility_id,
		        CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id
	    	FROM
		        ecshop.ecs_order_info AS o 
	    	    inner join ecshop.ecs_batch_order_mapping bom on o.order_id = bom.order_id
	   		    INNER JOIN ecshop.ecs_order_goods AS og ON o.order_id = og.order_id 
	   		    left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
	    	WHERE
				o.order_type_id = 'PURCHASE'  
				AND bom.is_cancelled = 'N' AND bom.is_over_c = 'N' AND bom.is_in_storage = 'N'
	    		AND o.order_status <> 5 
    			AND ". party_sql('o.party_id') ."                                                 
	    		AND o.facility_id = $facility_id
	    		AND og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}'
	            group by og.rec_id
	            having  goods_number > 0
	    ";
		break;
	
}
	// 取得按订单分组的数据
	$db->getAllRefby($sql, array('order_id'), $ref_fields, $refs, false);

	// 组合数据
	if ($refs && !empty($refs['order_id'])) {
		$order_list = array();
		foreach ($refs['order_id'] as $order_id => $group) {
			// 订单信息
			if (!isset($order_list[$order_id])) {
				$order = reset($group);
				$order_list[$order_id] = array(
					'order_id'      => $order['order_id'],
					'facility_id'      => $order['facility_id'],
					'order_sn'      => $order['order_sn'],
					'order_time'    => $order['order_time'],
					'order_type_id' => $order['order_type_id'],
					'goods_list' => array()
				);
				
				// 根据订单类型和用户权限来构造Url
				if($type == 1 || $type == 2 ){
					if ($order['order_type_id'] == 'SALE') {
						if (check_admin_priv('customer_service_manage_order')) {  // 客服
							$order_list[$order_id]['url'] = "distribution_delivery.php?order_sn={$order['order_sn']}";	
						} else {
							$order_list[$order_id]['url'] = "distribution_delivery.php?order_sn={$order['order_sn']}";	
						}
					} elseif ($order['order_type_id'] == 'PURCHASE') {
		              //	$order_list[$order_id]['url'] = "distribution_purchase_stock_in.php?order_sn={$order['order_sn']}";
						$order_list[$order_id]['url'] = '#';
					} else {
						$order_list[$order_id]['url'] = '#';
					}
					
				}else if($type == 5){
					if ($order['order_type_id'] == 'SALE') {
						if (check_admin_priv('customer_service_manage_order')) {  // 客服
							$order_list[$order_id]['url'] = "order_edit.php?order_id={$order['order_id']}";	
						} else {
							$order_list[$order_id]['url'] = "order_edit.php?order_id={$order['order_id']}";
						}
					} elseif ($order['order_type_id'] == 'PURCHASE') {
		              //	$order_list[$order_id]['url'] = "distribution_purchase_stock_in.php?order_sn={$order['order_sn']}";
						$order_list[$order_id]['url'] = '#';
					} else {
						$order_list[$order_id]['url'] = '#';
					}
				}else if($type == 3 || $type == 6){
				     if ($order['order_type_id'] == 'SALE') {
						if (check_admin_priv('customer_service_manage_order','order_view')) {  
							$order_list[$order_id]['url'] = "order_edit.php?order_id={$order['order_id']}";	
						}else {
							$order_list[$order_id]['url'] = "order_edit.php?order_id={$order['order_id']}";	
						}
				     }
				}else {
					$order_list[$order_id]['url'] = '#';
				}
			}
			
			// 订单商品
			foreach ($group as $item) {
	//			if (!isset($order_list[$order_id]['goods_list'][$item['goods_style_id']])) {
					$order_list[$order_id]['goods_list'][] = array(
						'goods_name'   => $item['goods_name'], 
						'goods_number' => $item['goods_number'],
						'goods_id'     => $item['goods_id'],
						'style_id'     => $item['style_id'],
					);
	//			}
			}
		}
	}

// 模版可用：$order_list	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>待采清单详情</title>
  <link href="styles/default.css" rel="stylesheet" type="text/css">
  <style type="text/css">
		*.margin: 0;
  </style>
</head>
<body>

<div style="width:700px; margin:0 auto;">
<table class="bWindow">
	<tr align="center">
	  	<th width="20%">订单号</th>
	  	<th width="20%">下单时间</th>
	    <th width="25%">商品名</th>
	    <th width="10%">数量</th>
	    <th width="15%"><?php if ($type == 1) print "发货仓库"; else print "收货仓库"; ?> </th>
	    <th width="10%">备注</th>
	</tr>
  
  <?php if (!empty($order_list) && is_array($order_list)) : foreach ($order_list as $order) : ?>
  <?php
	if ( !empty($order['goods_list']) && is_array($order['goods_list']) ) {
		$count = count($order['goods_list']);
	} else {
		$count = 1; 
	}
  ?>
	<tr align="center">
		<td rowspan="<?php print $count; ?>">
			<a href="<?php print $order['url']; ?>" target="_blank"><?php print $order['order_sn']; ?></a>
		</td>
	  	<td rowspan="<?php print $count; ?>">
	  		<?php print $order['order_time']; ?>
	  	</td>
	  	
	  	<?php $goods = reset($order['goods_list']); ?>
	    <td><?php print $goods['goods_name']; ?></td>
	    <td><?php print $goods['goods_number']; ?></td>
	    
	    <td rowspan="<?php print $count; ?>">
	    <?php print facility_mapping($order['facility_id']) ?>
	    </td>
	    <td rowspan="<?php print $count; ?>"><?php print $order['action_note']; ?></td>
	</tr>
	
	<?php if ($count > 1) : $i = 0; foreach ($order['goods_list'] as $goods ) : ?>
	<?php if ($i >0) : ?>
	<tr align="center">
	    <td><?php print $goods['goods_name']; ?></td>
	    <td><?php print $goods['goods_number']; ?></td>
	</tr>
	<?php endif; ?>
	<?php $i++; endforeach; endif; ?>
	
	<?php endforeach; endif; ?>
<table>
</div>

</body>
</html>
