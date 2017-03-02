<?php

/**
 * 待采购清单
 */
define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('wl_delivery_order', 'cg_delivery_order', 'purchase_order');
require_once("function.php");
require_once('includes/lib_order.php');
require_once(ROOT_PATH. "RomeoApi/lib_inventory.php");


$act = $_REQUEST['act'];
$shortage = $_REQUEST['shortage'];

// 构造查询的排序语句
$sort_time_map = array(
	0 => "",
	1 => "o.order_time DESC ",
	2 => "o.order_time ",
);
$sort_cat_map = array(
	0 => "",
	1 => "g.top_cat_id, g.brand_id,g.goods_name ",
);
$sort_time = $_REQUEST['sort_time'] ? $_REQUEST['sort_time'] : 0;
$sort_cat = $_REQUEST['sort_cat'] ? $_REQUEST['sort_cat'] : 0;
$sort_condition = $sort_time_map[$sort_time]. $sort_cat_map[$sort_cat];
if (trim($sort_condition) == '') $sort_condition = 'g.top_cat_id,g.brand_id,g.goods_name ';

// 查询条件
$condition = getCondition();

// 查询出符合条件的所有的记录
$sql = "
    SELECT 
        og.customized, og.goods_name, og.goods_number, og.goods_id, og.style_id, o.order_id, o.order_time, o.order_sn,
        CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id, g.goods_party_id, o.shipping_name,
        CONCAT_WS('_', og.goods_id, og.style_id, o.order_id) AS goods_style_order_id
    FROM
        ( SELECT order_sn, order_id, order_time, pay_status, oo.pay_id, oo.shipping_name 
          FROM
            {$ecs->table('order_info')} AS oo
            LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = oo.pay_id 
          WHERE 
            order_status = 1 AND shipping_status IN (0, 4, 10, 13)                       -- 已经确认未发货的订单
            AND ( (oo.order_type_id = 'SALE' AND 
                   (p.pay_code = 'cod' OR oo.pay_status = 2 OR oo.pay_status = 1) ) 
                 OR oo.order_type_id IN ('RMA_EXCHANGE', 'SHIP_ONLY'))
            AND biaoju_store_id in (0, 7) {$condition['order']}
        ) AS o
        LEFT JOIN {$ecs->table('order_goods')} AS og ON o.order_id = og.order_id 
        LEFT JOIN {$ecs->table('goods')} AS g ON og.goods_id = g.goods_id 
    WHERE 
        not exists(select 1 from romeo.inventory_item_detail iid where iid.order_goods_id = convert(og.rec_id using utf8) limit 1)
        AND og.goods_id != 27080                             -- 排除掉自己库存的商品和测试商品
        {$condition['out']}
    group by goods_style_order_id
    ORDER BY {$sort_condition} 
";
$ref_fields = $refs = array();
$db->getAllRefby($sql, array('order_id', 'goods_style_order_id', 'goods_id'), $ref_fields, $refs, false);

// 对查询出的结果集进行处理, 取得待采购的商品清单
if ($refs) {
    $storage_list = getStorage('INV_STTS_AVAILABLE');  // 新库存
    $goods_list   = array();       // 待采购商品列表
    
    foreach ($refs['goods_style_order_id'] as $group) {
        $goods = reset($group);
        $goods_style_id = $goods['goods_style_id'];
        
        if (!isset($goods_list[$goods_style_id])) {
        	$goods_list[$goods_style_id]['total_number'] = 0;
            $goods_list[$goods_style_id] = $goods;
            $goods_list[$goods_style_id]['storage_number'] = 
                isset($storage_list[$goods_style_id]['qohTotal']) ? $storage_list[$goods_style_id]['qohTotal'] : 0 ;  // 取得库存数 
            $goods_list[$goods_style_id]['total_number'] += $goods['goods_number'];                               // 待采购数量
        } else {
            $goods_list[$goods_style_id]['total_number'] += $goods['goods_number'];   // 待采购数量增加
        }

        foreach ($group as $item) {
            // 商品对应的订单列表
            if ( !isset($goods_list[$goods_style_id]['order_group'][$item['order_id']]) )
                $goods_list[$goods_style_id]['order_group'][$item['order_id']] = array(
                	'order_id'   => $item['order_id'], 
                	'order_sn'   => $item['order_sn'], 
                	'order_time' => $item['order_time'],
                	'shipping_name'    => $item['shipping_name'],
                );
            
            // 商品对应的定制信息列表
            if ( !isset($goods_list[$goods_style_id]['customized_group'][$item['customized']]) ) {
                $goods_list[$goods_style_id]['customized_group'][$item['customized']] += $goods['goods_number'];  
            } else {
                $goods_list[$goods_style_id]['customized_group'][$item['customized']] += $goods['goods_number'];
            }
            // 商品对应的快递列表
            if ( !isset($goods_list[$goods_style_id]['shipping_group'][$item['shipping_name']]) ) {
                $goods_list[$goods_style_id]['shipping_group'][$item['shipping_name']] += $goods['goods_number']; 
            } else {
                $goods_list[$goods_style_id]['shipping_group'][$item['shipping_name']] += $goods['goods_number'];
            }
        }
    }
}

if (!empty($goods_list)) {		
    // 取得订单对应的action信息 {@link lib_order.php}
    $order_actions = get_order_actions_list_by_order_id($ref_fields['order_id'], true);
	
    foreach ($goods_list as $key => $goods) {
        // 如果是鞋子商品，则计算尺寸
        if ($goods['goods_party_id'] == PARTY_OUKU_SHOES) {
            $sku = $db->getOne("SELECT internal_sku FROM {$ecs->table('goods_style')} WHERE goods_id = '{$goods['goods_id']}' AND style_id = '{$goods['style_id']}' LIMIT 1");
            if ($sku) {
                $goods_list[$key]['size'] = intval(substr($sku, -3))/10;   
            }
        }
        
        // 取得商品对应订单的备注信息列表
        foreach ($goods['order_group'] as $order) {
            $goods_list[$key]['order_group'][$order['order_id']]['action_notes'] = 
                $order_actions['order_id'][$order['order_id']];
        }

        if ( ($shortage == 0 && $goods['storage_number'] >= $goods['total_number'])     // 只显示库存量不足 
            || ($shortage == 1 && $goods['storage_number'] < $goods['total_number']) )  // 只显示需要补货的
        {
            unset($goods_list[$key]);
        }
    }
}

$smarty->assign('goods_list', $goods_list);
$smarty->display('oukooext/delivery_order.htm');


// 构造查询条件
function getCondition() 
{
    $condition = array("order" => "", "out" => "");
    
	$start = trim($_REQUEST['start']);
	$end = trim($_REQUEST['end']);
	
	$order_sn = trim($_REQUEST['order_sn']);
	
	$goods_cagetory = intval($_REQUEST['goods_cagetory']);
	
	// 期初时间
	if ($start && strtotime($start) !== false) {
		$condition['order'] .= " AND order_time >= '$start'";	
	}
	
	// 如果没有指定期末时间，则以今天为准
	if ($end && strtotime($end) !== false) {
        $end = date('Y-m-d', strtotime('+1 day', strtotime($end)));
        $condition['order'] .= " AND order_time <= '$end'"; 
	}
	
	// 限制订单sn
	if ($order_sn !== null && $order_sn != '') {
		$condition['order'] .= " AND order_sn LIKE '%$order_sn%'";
	}
	
	// 限制商品分类
	if ($goods_cagetory != -1 && $goods_cagetory !== null) {
		switch ($goods_cagetory) {
			case 1:  // 手机
				$condition['out'] .= " AND g.top_cat_id = 1";
				break;
			case 2:  // 手机配件 补货商品
				$condition['out'] .= " AND (g.top_cat_id = 597 or g.top_cat_id = 1109)";
				break;
			case 3:  // 电教产品
                $condition['out'] .= " AND g.top_cat_id = 1458";
                break;
			case 4:  // 补货商品
				$condition['out'] .= " AND g.top_cat_id = 1109";
				break;
			case 5:  // 其他 (健康电子)
				$condition['out'] .= " AND (g.top_cat_id NOT IN (1, 597, 1109, 1458) AND g.cat_id != 1157)";
				break;
		}
	}
	
	# 添加party条件判断 2009/08/07 yxiang
	$condition['order'] .= ' AND ' . party_sql('oo.party_id');
    
	return $condition;
}

?>
