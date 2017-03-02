<?php

/**
 * $Author: zwsun $
 * $Date: 2009-7-14 $
 * $Id: queryorderrelation.php $
*/

define('IN_ECS', true);

require('includes/init.php');


if ($_REQUEST['act'] == 'query_rma') {
    $order_sn = $_REQUEST['order_sn'];
    $sql = "SELECT ol.*, o.*
            FROM {$ecs->table('order_info')} o  
            LEFT JOIN order_relation ol ON o.order_id = ol.order_id 
            WHERE (ol.root_order_sn = '{$order_sn}' OR ol.order_sn = '{$order_sn}') AND o.order_type_id IN ('RMA_EXCHANGE','RMA_RETURN')";
    
    $rma_orders = $db->getAll($sql);
    
    if(!empty($rma_orders)){
    	foreach($rma_orders as $key=>$rma_order){
    		$rma_in_status = '';
    		
    		switch($rma_orders[$key]['order_type_id']){
    			case 'RMA_EXCHANGE':
    				$rma_in_status = '已入库';
    				break;
    			case 'RMA_RETURN':
    				$sql = "SELECT COUNT(1) FROM ecshop.ecs_order_goods og
							INNER JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id AND iid.quantity_on_hand_diff > 0
							WHERE og.order_id = {$rma_orders[$key]['order_id']}";
					$count = $db->getOne($sql);
					$rma_in_status = $count > 0 ? '已入库' : '未入库';
    				break;
    			default:
    				break;
    		}
    		
    		$rma_orders[$key]['rma_in_status'] = $rma_in_status;
    	}
    }
    $smarty->assign('rma_orders', $rma_orders);
}

$smarty->display("oukooext/queryorderrelation.htm");