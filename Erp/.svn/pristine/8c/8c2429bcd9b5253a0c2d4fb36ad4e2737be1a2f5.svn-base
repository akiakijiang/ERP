<?php

/**
 * 提货清单（用于仓库发货便捷）
 * 
 * 查询商品维度的待发货订单
 */
 
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
admin_priv('distribution_purchase_request', 'distribution_generate_purchase_order');
require_once('function.php');
require_once(ROOT_PATH. 'includes/lib_order.php');
require_once(ROOT_PATH. 'includes/debug/lib_log.php');
require_once(ROOT_PATH. 'RomeoApi/lib_inventory.php');
require_once('includes/lib_product_code.php');

$info = isset($_REQUEST['info']) && trim($_REQUEST['info']) ? $_REQUEST['info'] : false ;
if(!in_array($_SESSION['party_id'],array('120','16','64','65668'))){
	die("此页面已被乐其电教，乐其蓝光承包发货。其他具体业务组查询请前往“库存管理->库存查询”页面进行数据搜索");
}
if ($info) {
    // 如果传递过来了消息则显示消息
    $smarty->assign('message', $info);
}
 
// 根据用户party_id取得非欧酷（1）和欧酷派（4）下的所有子分类
require_once ROOT_PATH . 'includes/helper/array.php';
$categorys = $db->getAllCached("SELECT cat_id, cat_name, parent_id FROM {$ecs->table('category')} WHERE party_id not in (1, 4) and is_delete = 0 and sort_order < 50 and " . party_sql('party_id'));  // 取得所有分类
$refs = array();
Helper_Array::toTree($categorys, 'cat_id', 'parent_id', 'childrens', $refs);
$category_list = array();
foreach ($refs as $ref) {
	$categorys = Helper_Array::treeToArray($ref, 'childrens');
	foreach ($categorys as $category) {
    	if ($category['_is_leaf']) {
    	    $category_list[$category['cat_id']] = $category['cat_name'];
    	}
    }
} 

// 筛选条件
$filter = array(
    'category_id' => 
        isset($_REQUEST['category_id']) && isset($category_list[$_REQUEST['category_id']])
        ? $_REQUEST['category_id']
        : key($category_list) ,
);

/**
 * 查询待采清单 
 */
// 查询出商品清单
$sql = "SELECT 
		g.goods_id, g.cat_id, IFNULL(gs.style_id, 0) AS style_id, gs.goods_color, 
		CONCAT_WS(' ', g.goods_name, 
		  if(gs.goods_color is null or gs.goods_color = '', s.color, gs.goods_color)) as goods_name,
		  rp.IS_SHOW_STORAGE_IN_FX_THQD  as is_show 
	FROM 
		{$ecs->table('goods')} AS g 
		LEFT JOIN {$ecs->table('goods_style')} AS gs ON gs.goods_id = g.goods_id
		left join {$ecs->table('style')} as s on gs.style_id = s.style_id
		LEFT JOIN romeo.party as rp on rp.party_id = convert(g.goods_party_id using utf8)
	WHERE
		g.is_on_sale = 1 AND if(gs.style_id is null, g.is_delete, gs.is_delete) = 0 AND 
		-- rp.party_id = {$_SESSION['party_id']} AND
		g.goods_party_id = {$_SESSION['party_id']} and  
		g.cat_id = {$filter['category_id']}
".' -- DPR[TihuoQingdan] '.__LINE__.PHP_EOL;
$goods_list = $db->getAll($sql);
//QLog::log("商品清单sql1 : ".$sql);
$is_show_storage = 1;
$user_facility_list = array_intersect_assoc(get_available_facility(),get_user_facility());
$user_facility_ids = implode("','",array_keys($user_facility_list));
if ($goods_list)
{
	//分销-提货清单显示控制
	if ($_SESSION['roles'])
	{
		$roles = explode(',', $_SESSION['roles']) ;
		if(in_array($wuliu_id, $roles))
		{
			//如果是物流组的人
			$is_show_storage = $goods_list[0]['is_show'];
		}
		else {
			$is_show_storage = 1;		
		}
	}
	else
	{
		$is_show_storage = 1;
	}
	
    // 待查询商品ID
    $gIds = array();
    foreach ($goods_list as $item) {
        $gIds[] = $item['goods_id'];
    }
    $goodsIds = implode(',',$gIds);
    
    $start_order_time = date("Y-m-d H:i:s",strtotime("-15 days",time()));
    
	// 得到需要采购的商品
	// 查询出符合条件的所有的erp记录
	/*
	$sql = "SELECT 
	        og.customized, og.goods_name, og.goods_id, og.style_id, og.goods_number,
	        o.order_id, o.order_time, o.order_sn, o.facility_id,
	        CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id, 
	        CONCAT_WS('_', og.goods_id, og.style_id, o.facility_id) AS goods_style_facility_id, 
	        CONCAT_WS('_', og.goods_id, og.style_id, o.order_id) AS goods_style_order_id
		FROM
	        ( SELECT 
	        	order_sn, order_id, order_time, pay_status, oo.pay_id, oo.facility_id
			  FROM 
			  	{$ecs->table('order_info')} AS oo
	          WHERE
	            order_status = 1 AND shipping_status = 0 and order_time > '{$start_order_time}'                                             -- 已确认还未发货的订单
	            AND  order_type_id in ('SALE','RMA_EXCHANGE','SHIP_ONLY' )      
	            AND ". party_sql('oo.party_id') ."
	            AND  facility_id in ('{$user_facility_ids}')
			) AS o
			INNER JOIN {$ecs->table('order_goods')} AS og ON o.order_id = og.order_id 
			LEFT JOIN {$ecs->table('goods')} AS g ON og.goods_id = g.goods_id 
			inner join romeo.order_inv_reserved r on o.order_id = r.order_id and r.facility_id = o.facility_id 
		WHERE
			not exists(select 1 from romeo.inventory_item_detail iid where iid.order_goods_id = convert(og.rec_id using utf8) limit 1) -- 不存在出库记录
			AND og.goods_id != 27080 and r.STATUS = 'Y'  -- 排除自己库存和测试商品
	        AND  g.goods_id in ({$goodsIds})
		ORDER BY g.top_cat_id, g.brand_id, g.goods_name
	".' -- DPR[TihuoQingdan] '.__LINE__.PHP_EOL;
	*/
	$sql_party_sinri=party_sql('o.party_id');
	$sql_facility_sinri=facility_sql('o.facility_id');
	$sql="SELECT
		og.customized,
		og.goods_name,
		og.goods_id,
		og.style_id,
		og.goods_number,
		o.order_id,
		o.order_time,
		o.order_sn,
		o.facility_id,
		CONCAT_WS(
			'_',
			og.goods_id,
			og.style_id
		) AS goods_style_id,
		CONCAT_WS(
			'_',
			og.goods_id,
			og.style_id,
			o.facility_id
		) AS goods_style_facility_id,
		CONCAT_WS(
			'_',
			og.goods_id,
			og.style_id,
			o.order_id
		) AS goods_style_order_id
	FROM
		ecshop.ecs_order_info AS o force index(order_info_multi_index)
	LEFT JOIN ecshop.ecs_payment p ON o.pay_id = p.pay_id
	INNER JOIN ecshop.ecs_order_goods AS og ON o.order_id = og.order_id
	LEFT JOIN ecshop.ecs_goods AS g ON og.goods_id = g.goods_id
	INNER JOIN romeo.order_inv_reserved r ON o.order_id = r.order_id
	AND r.facility_id = o.facility_id
	WHERE
		o.order_status = 1
	AND o.shipping_status = 0
	AND o.order_time > '{$start_order_time}' -- 已确认还未发货的订单
	AND o.order_time > '2015-11-20 00:00:00'
	AND (
		(
			o.order_type_id = 'SALE'
			AND (
				p.pay_code = 'cod'
				OR o.pay_status = 1
				OR o.pay_status = 2
			)
		) -- 销售订单 (已确认|已支付|先款后货)
		OR o.order_type_id = 'RMA_EXCHANGE'
		OR o.order_type_id = 'SHIP_ONLY' -- 或者为换货订单，补寄订单 
	)
	AND {$sql_party_sinri}
	AND {$sql_facility_sinri}
	AND NOT EXISTS (
		SELECT
			1
		FROM
			romeo.inventory_item_detail iid
		WHERE
			iid.order_goods_id = CONVERT (og.rec_id USING utf8)
		LIMIT 1
	) -- 不存在出库记录
	AND og.goods_id != 27080
	AND r. STATUS = 'Y' -- 排除自己库存和测试商品
	AND g.goods_id IN ({$goodsIds})
	ORDER BY
		g.top_cat_id,
		g.brand_id,
		g.goods_name
	".' -- DPR[TihuoQingdan] '.__LINE__.PHP_EOL;
	QLog::log("合条件的所有的erp记录sql2 : ".$sql);
	$ref_fields = $refs = array();
	$db->getAllRefby($sql, array('order_id', 'goods_style_order_id', 'goods_style_facility_id'), $ref_fields, $refs, false);
	// 对查询出的结果集进行处理, 取得待采购的商品清单
	if ($refs)
	{
	    $request_list = array();     // 待采购商品列表
	    $goods_style_facility_list = array();  //提货清单分仓库显示 
	    foreach ($refs['goods_style_facility_id'] as $groups) {
	        foreach($groups as $group) {
	            // 取得同一仓库同种商品的订购数
	            $goods_style_facility_list[$group['goods_style_id']][$group['facility_id']] += $group['goods_number']; // 待采购数量	 
	        }
	    }
	    foreach ($refs['goods_style_order_id'] as $group) {
	        $goods = reset($group);
	        $goods_style_id = $goods['goods_style_id'];
	        
	        // 取得同一品类商品的订购数
	        if (!isset($request_list[$goods_style_id])) {
	            $request_list[$goods_style_id] = $goods;
	            $request_list[$goods_style_id]['request_number'] += $goods['goods_number']; // 待采购数量
	        }
	        else {
	            $request_list[$goods_style_id]['request_number'] += $goods['goods_number']; // 待采购数量增加
	        }
	        
	        // 取得同一品类商品在哪些订单里被订购
	        foreach ($group as $item) {            
	            // 商品对应的订单列表
	            if ( !isset($request_list[$goods_style_id]['request_order_group'][$item['order_id']]) )
	                $request_list[$goods_style_id]['request_order_group'][$item['order_id']] = 
	                    array(
	                        'order_id'   => $item['order_id'], 
	                        'order_sn'   => $item['order_sn'], 
	                        'order_time' => $item['order_time']
	                    );
	            
	            // 商品对应的定制信息列表
	            if ( !isset($request_list[$goods_style_id]['request_customized_group'][$item['customized']]) )
	                $request_list[$goods_style_id]['request_customized_group'][$item['customized']] += $item['goods_number']; 
	            else
	                $request_list[$goods_style_id]['request_customized_group'][$item['customized']] += $item['goods_number'];        
	        }
	    }
	}
   // 根据用户所在的仓库取得各个仓库的库存量
   	
   	$sql = "SELECT sum(ii.QUANTITY_ON_HAND_TOTAL) as qohTotal , CONCAT_WS('_',pm.ECS_GOODS_ID, pm.ECS_STYLE_ID,ii.FACILITY_ID) as  goods_style_facility_id
			 from romeo.inventory_item ii,   romeo.product_mapping pm  
			 where ii.STATUS_ID ='INV_STTS_AVAILABLE' and ii.QUANTITY_ON_HAND_TOTAL > 0  
			 	and ii.PRODUCT_ID =   pm.PRODUCT_ID   
			 	AND pm.ECS_GOODS_ID  in ({$goodsIds})
			 	AND ii.facility_id in ('{$user_facility_ids}')
			 group by pm.ECS_GOODS_ID, pm.ECS_STYLE_ID,ii.FACILITY_ID 
	".' -- DPR[TihuoQingdan] '.__LINE__.PHP_EOL;
	QLog::log("根据用户所在的仓库取得各个仓库的库存量sql: ".$sql);
	$storage_list_1 = $db->getAll($sql);		
	$storage_list = array();
	foreach($storage_list_1 as $key=>$value){
		$storage_list[$value['goods_style_facility_id']] = $value['qohTotal'];
	} 
    foreach ($goods_list as $key => $goods) {
    	$idx = $goods['goods_id'] . '_' . $goods['style_id'];
    	if (isset($request_list[$idx])) {
    		$goods_list[$key] = array_merge($request_list[$idx], $goods);             // 订购数和订购情况
    	}
    	
        // 库存数
        foreach ($user_facility_list as $facility_id => $facility_name) {
            if (isset($storage_list[$idx.'_'.$facility_id])) {
            	// 分仓库存
	            $goods_list[$key]['facility_storage'][$facility_name] = $storage_list[$idx.'_'.$facility_id];
	            // 总库存 
            	$goods_list[$key]['storage_number'] += $storage_list[$idx.'_'.$facility_id];
            }
            // 分销商订购数按仓库显示
            if(isset($goods_style_facility_list[$idx][$facility_id])) {
            	$goods_list[$key]['purchase_facility_name_storage_number'][$facility_id]['storage_number'] = $goods_style_facility_list[$idx][$facility_id];
                $goods_list[$key]['purchase_facility_name_storage_number'][$facility_id]['facility_name'] = $facility_name;
            }
            
        }

        $diff = $goods_list[$key]['request_number'] 
        	- $goods_list[$key]['storage_number'];
        $goods_list[$key]['advice_num'] = $diff > 0 ? $diff : 0 ;                     // 建议采购数       
        $goods_list[$key]['productName'] = $goods['goods_name'];
        $goods_list[$key]['productCode'] = encode_goods_id($goods['goods_id'], $goods['style_id']);
        $goods_list[$key]['goodsId'] = $goods['goods_id'];
    }
}
$smarty->assign('is_show_storage', $is_show_storage);
$smarty->assign('filter', $filter);
$smarty->assign('category_list', $category_list);
$smarty->assign('facility_name', facility_mapping(implode(',',array_keys($user_facility_list))));
$smarty->assign('goods_list', $goods_list);
$smarty->assign('available_facility', get_available_facility());
$smarty->assign('printers', get_serial_printers());
$smarty->display('distributor/distribution_purchase_request.htm');

