<?php

/**
 * 为自动完成提供ajax数据
 * 
 * 该文件用来为常用的ajax请求提供数据， 函数命名规范为 "search_函数名"
 * 当请求为 "inventory_ajax_detail.php?act=purchase_delivery" 时，会自动调用 search_purchase_delivery 函数，并将函数返回的结构以josn的形式返回
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once('config.vars.php');
require_once ('function.php');
//require_once('includes/lib_service.php');

// 通过请求自动调用函数
$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : false;
if ($act && function_exists('search_'.$act))
{
    unset($_REQUEST['act']);
    // $_POST的数据会传递给回调函数
    $result = call_user_func('search_'.$act, isset($_POST) ? $_POST : null);
    if (!$result) $result = array();
    // 以json格式输出数据
    header('Content-type: text/html; charset=utf-8');
    $json = new JSON;
    print $json->encode($result);
}

/**
 * 提货清单 查询15内待发货订单  -- type=1
 * 库存查询 查询一个月内已确认数  -- type=2
 * 库存查询 查询一个月内未确认数  -- type=3
 */
function search_purchase_delivery($args){
	if (!empty($args)) extract($args); 
	$goods_id = $_REQUEST['goods_id']; 
	$style_id = $_REQUEST['style_id']; 
	$facility_id = $_REQUEST['facility_id']; 
	$type = $_REQUEST['type'];
	global $db;
	if($type==1){
		$start_order_time = date("Y-m-d H:i:s",strtotime("-15 days",time()));
		$cont = " and oo.order_time >='{$start_order_time}' and  oo.order_status = 1 " .
				" and exists (select 1 from romeo.order_inv_reserved ir where ir.order_id= oo.order_id and ir.facility_id=oo.facility_id and ir.status='Y') ";
	}elseif($type==2){
		$start_order_time = date("Y-m-d H:i:s",strtotime("-1 month",time()));
		$cont = " and oo.order_time >='{$start_order_time}' and  oo.order_status = 1  ";
	}elseif($type==3){
		$start_order_time = date("Y-m-d H:i:s",strtotime("-1 month",time()));
		$cont = " and oo.order_time >='{$start_order_time}' and  oo.order_status = 0  ";
	}else{
		$cont = "";
	}
	
	$sql = "SELECT 
	           og.goods_name, og.goods_number, oo.order_type_id, 
	           oo.order_id, oo.order_time, oo.order_sn,f.facility_name,
	           CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id
		FROM  ecshop.ecs_order_info AS oo force index (order_info_multi_index)
        INNER JOIN ecshop.ecs_order_goods AS og ON oo.order_id = og.order_id 
		INNER join romeo.facility f on oo.facility_id = f.facility_id
		LEFT JOIN ecshop.ecs_payment p ON p.pay_id = oo.pay_id 
		WHERE   oo.shipping_status in (0,13,16) {$cont}              -- 已确认还未发货的订单
        AND oo.order_type_id in ('SALE','RMA_EXCHANGE','SHIP_ONLY')   
	    AND oo.facility_id = '{$facility_id}'
	    AND not exists(select 1 from romeo.inventory_item_detail iid where iid.order_goods_id = convert(og.rec_id using utf8) limit 1)  -- 不存在出库记录
		AND og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}'  AND ". party_sql('oo.party_id') ." 
		".' -- InventoryAjaxDetail '.__LINE__.PHP_EOL;
    $r = $db->getAll($sql); 
    $result = array(); 
    if(empty($r)){
    	$result['is'] = 0;   
    }else{
    	$result['is'] = 1;
    	$result['content'] = $r;
    }
    return $result; 
}
/**
 * 库存查询 采购未入库量
 */
function search_purchase_inventory($args){
	if (!empty($args)) extract($args); 
	$goods_id = $_REQUEST['goods_id']; 
	$style_id = $_REQUEST['style_id']; 
	$facility_id = $_REQUEST['facility_id']; 
	global $db;
	$sql = "SELECT 
	        og.goods_number - sum(ifnull(iid.quantity_on_hand_diff,0)) as goods_number, og.goods_name, o.order_type_id, 
	        o.order_id, o.order_time, o.order_sn,f.facility_name,
	        CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id
    	FROM
	        ecshop.ecs_order_info AS o 
    	    inner join ecshop.ecs_batch_order_mapping bom on o.order_id = bom.order_id
   		    INNER JOIN ecshop.ecs_order_goods AS og ON o.order_id = og.order_id 
   		    left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8) 
   		    INNER join romeo.facility f on o.facility_id = f.facility_id
    	WHERE
			o.order_type_id = 'PURCHASE'  
			AND bom.is_cancelled = 'N' AND bom.is_over_c = 'N' AND bom.is_in_storage = 'N'
    		AND o.order_status <> 5 
			AND ". party_sql('o.party_id') ."                                                 
    		AND o.facility_id = '{$facility_id}'
    		AND og.goods_id = '{$goods_id}' AND og.style_id = '{$style_id}'
            group by og.rec_id
            having  goods_number > 0
    ".' -- InventoryAjaxDetail '.__LINE__.PHP_EOL;
	$r = $db->getAll($sql); 
    $result = array(); 
    if(empty($r)){
    	$result['is'] = 0;   
    }else{
    	$result['is'] = 1;
    	$result['content'] = $r;
    }
    return $result; 
}

/**
 * 库存查询 -gt需出库数
 */
function search_supplier_return($args){
	if (!empty($args)) extract($args); 
	$goods_id = $_REQUEST['goods_id']; 
	$style_id = $_REQUEST['style_id']; 
	$facility_id = $_REQUEST['facility_id']; 
	global $db;
	$sql= "SELECT 
	        og.goods_name, og.status_id,f.facility_name,
	        o.order_id, o.order_time, o.order_sn,  o.order_type_id,
	        CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id,
	        og.goods_number + sum(ifnull(iid.quantity_on_hand_diff,0)) as goods_number
	   FROM
	        ecshop.ecs_order_info AS o 
   		    INNER JOIN ecshop.ecs_order_goods AS og ON o.order_id = og.order_id 
   		    inner join romeo.supplier_return_request_gt srrg on srrg.SUPPLIER_RETURN_GT_SN = o.order_sn
            inner join romeo.supplier_return_request srr on srr.supplier_return_id = srrg.supplier_return_id 
   		    LEFT  JOIN  romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
   		    INNER join romeo.facility f on o.facility_id = f.facility_id
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
  	".' -- InventoryAjaxDetail '.__LINE__.PHP_EOL;
	$r = $db->getAll($sql); 
    $result = array(); 
    if(empty($r)){
    	$result['is'] = 0;   
    }else{
    	$result['is'] = 1;
    	$result['content'] = $r;
    }
    return $result; 	
}


/**
 * 库存查询 负可用库存
 */
function search_negative_inventory($args){
	if (!empty($args)) extract($args); 
	$goods_id = $_REQUEST['goods_id']; 
	$style_id = $_REQUEST['style_id']; 
	$facility_id = $_REQUEST['facility_id']; 
	global $db;
	$sql = "SELECT  og.goods_name, og.goods_number, og.goods_id, og.style_id,o.order_type_id, 
	       o.order_id, o.order_time, o.order_sn, f.facility_name,
	       CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id
		from ecshop.ecs_order_info o
		INNER join ecshop.ecs_order_goods og on o.order_id = og.order_id
		INNER join romeo.product_mapping pm on og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
		INNER join romeo.facility f on o.facility_id = f.facility_id
		where o.order_status in (0,1) 
		and o.reserved_time = 0 
		and  ". party_sql('o.party_id') ." 
		and o.order_type_id in ('SALE','RMA_EXCHANGE','SHIP_ONLY')
		AND o.facility_id = '{$facility_id}' 
		AND og.goods_id = {$goods_id} 
		AND og.style_id = {$style_id} 
		group by o.order_id, og.rec_id
	".' -- InventoryAjaxDetail '.__LINE__.PHP_EOL;
	$r = $db->getAll($sql); 
    $result = array(); 
    if(empty($r)){
    	$result['is'] = 0;   
    }else{
    	$result['is'] = 1;
    	$result['content'] = $r;
    }
    return $result; 	
}
