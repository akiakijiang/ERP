<?php

/**
 * 待捡货列表|打印捡货单
 * 
 * @author yxiang@oukoo.com
 * @copyright 2010 ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('inventory_picking');
require_once('function.php');
require_once('distribution.inc.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');

// 请求
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('search')) 
    ? $_REQUEST['act'] 
    : NULL ;

// 当前页码
$page = 
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;

// 每页多少记录数
$page_size =
    is_numeric($_REQUEST['size']) && $_REQUEST['size'] > 0
    ? $_REQUEST['size']
    : 100 ;
    
// 消息
$message =
    isset($_REQUEST['message']) && trim($_REQUEST['message'])
    ? $_REQUEST['message']
    : false; 
    

// 所处仓库
$facility_list = get_user_facility();

// 订单类型
$order_type_list = array('SALE' => '销售订单', 'RMA_EXCHANGE' => '换货订单', 'SHIP_ONLY' => '补寄订单');

// 分销商
$distributor_list = Helper_Array::toHashmap(distribution_get_distributor_list(), 'distributor_id', 'name');
$distributor_list = array('0' => '') + $distributor_list + $_CFG['adminvars']['outer_type'];

// 配送方式
$shipping_type_list = Helper_Array::toHashmap(getShippingTypes(), 'shipping_id', 'shipping_name');
    
// 查询每页显示数列表
$page_size_list = array('20' => '20', '50' => '50', '100' => '100', '65535' => '不分页');


// 过滤条件
$filter = array(
    // 每页多少条记录
    'size' => 
        $page_size,
    // 仓库
    'facility_id' => 
        isset($_REQUEST['facility_id']) && isset($facility_list[$_REQUEST['facility_id']]) 
        ? $_REQUEST['facility_id'] 
        : NULL ,
    // 订单类型
    'order_type_id' => 
        isset($_REQUEST['order_type_id']) && isset($order_type_list[$_REQUEST['order_type_id']]) 
        ? $_REQUEST['order_type_id'] 
        : NULL ,
    // 分销商
    'distributor_id' =>
        isset($_REQUEST['distributor_id']) && isset($distributor_list[$_REQUEST['distributor_id']]) 
        ? $_REQUEST['distributor_id'] 
        : NULL , 
    // 配送方式
    'shipping_id' =>
        isset($_REQUEST['shipping_id']) && isset($shipping_type_list[$_REQUEST['shipping_id']]) 
        ? $_REQUEST['shipping_id'] 
        : NULL , 
    // 客服特殊标注
    'strike' =>
        isset($_REQUEST['strike']) && $_REQUEST['strike'] == 1
        ? true
        : NULL , 
);


// 链接
$url = 'picking_list.php';
$url = add_param_in_url($url, 'size', $filter['size']);
$url = add_param_in_url($url, 'strike', $filter['strike']);


// 取得库存预定成功的订单
try {
    $handle = soap_get_client('InventoryService');
    $response = $handle->getOrderInvReservedList(array('status' => 'Y'));
    $reserved_list = wrap_object_to_array($response->return->OrderInvReserved);

    if ($reserved_list) {
        $list = array();
        foreach ($reserved_list as $reserved) {
            $list[] = $reserved->orderId;
        }

        // 取得所有预定成功的订单信息
        if ($list) {
            $sql = "
                SELECT 
                    order_id, order_sn, consignee, order_time, facility_id,
                    order_type_id, shipping_id, distributor_id,
                    IF( (shipping_id = 44 OR shipping_id = 49) AND (province IN (2,3) OR city IN(233, 234, 249)), 
                        1, 0) AS region,
                    IF( order_type_id = 'SALE', 
                        (SELECT action_time FROM {$ecs->table('order_action')} WHERE order_id = o.order_id AND order_status = 1 LIMIT 1), 
                        order_time ) AS confirm_time,
                    IF( (SELECT 1 FROM order_mixed_status_note WHERE order_id = o.order_id AND note_type = 'SHIPPING' LIMIT 1) OR (SELECT 1 FROM ecs_order_action WHERE order_id = o.order_id AND note_type = 'SHIPPING' LIMIT 1),
                        1, 0) AS strike,
                    IF( (SELECT 1 FROM order_mixed_status_history WHERE order_id = o.order_id AND pick_list_status = 'printed' AND created_by_user_class = 'worker' LIMIT 1), 
                        1, 0) AS printed             
                FROM 
                    {$ecs->table('order_info')} AS o
                WHERE
                    order_id ". db_create_in($list) ."
                    AND ". party_sql('party_id'). " 
                    AND ". facility_sql('facility_id') ."
                    AND (handle_time = 0 OR handle_time < UNIX_TIMESTAMP())
                ORDER BY
                    confirm_time ASC, region DESC
            ";
            $links = array(
                // 订单的属性
                array(
                    'sql' => "SELECT * FROM order_attribute WHERE :in AND attr_name in('OUTER_TYPE','TAOBAO_USER_ID')",
                    'source_key' => 'order_id',
                    'target_key' => 'order_id',
                    'mapping_name' => 'attrs',
                    'type' => 'HAS_MANY'
                ),
            );
            $list = $db->findAll($sql, $links);
        }
    }
} catch (SoapFault $e) {}

if ($list) {
    // 统计订单总数
    $taxonomy = array();
    // 排序用
    $sort = array();
    foreach ($list as $key => & $order) {
        // 格式化属性
        if (!empty($order['attrs'])) {
            foreach ($order['attrs'] as $k => $attr) {
                $order['attrs'][$attr['attr_name']] = $attr['attr_value'];
                unset($order['attrs'][$k]);
            }
        }

        // 客服特殊标注的订单数
        if ($order['strike']) {
            $taxonomy['strike']++;
        }
        // 正常订单数
        else {
            $taxonomy['normal']++;
        }
        
    	// 只显示客服特殊标注的
        if (($filter['strike'] && !$order['strike']) || (!$filter['strike'] && $order['strike'])) {
            unset($list[$key]);
            continue;
        }
        
        // 取得订单的配送方式名
        $order['shipping_name'] = $shipping_type_list[$order['shipping_id']]; 
        
        // 按仓库分
        if (isset($facility_list[$order['facility_id']])) {
            $taxonomy['facility'][$order['facility_id']]++;
        }
        
        // 按订单类型分
        if (isset($order_type_list[$order['order_type_id']])) {
            $taxonomy['type'][$order['order_type_id']]++;
        }
        
        // 按渠道分
        if (empty($order['distributor_id']) && isset($order['attrs']['OUTER_TYPE'])) {
            $order['distributor_id'] = $order['attrs']['OUTER_TYPE'];
        }
        if (isset($distributor_list[$order['distributor_id']])) {
            $order['distributor_name'] = $distributor_list[$order['distributor_id']];
            $taxonomy['distributor'][$order['distributor_id']]++;
        }

        // 显示数量时，合并显示顺丰、万象的
        $order['shipping_id'] == 49 ? ( $order['shipping_id'] = 44 ) : NULL ; 
        $order['shipping_id'] == 48 ? ( $order['shipping_id'] = 51 ) : NULL ;
        // 按快递方式分
        if (isset($shipping_type_list[$order['shipping_id']])) {
            $taxonomy['shipping'][$order['shipping_id']]++;
        }
        
        // 排序用
        $sort['region'][$key] = $order['region'];
        $sort['taobao_user_id'][$key] = isset($order['attrs']['TAOBAO_USER_ID'])?$order['attrs']['TAOBAO_USER_ID']:'null';
    }
    
    // 排序
    // 江浙沪的排在前面
    // array_multisort($sort['region'], SORT_DESC, $list);  
    array_multisort($sort['taobao_user_id'], SORT_ASC, $list);

    // 根据条件过滤
    picking_list_filter($list, $filter);
    
    // 构造分页
    $total = count($list);
    $total_page = ceil($total/$page_size);  // 总页数
    $page = max($page, 1);
    $page = min($page, $total_page);
    $offset = ($page - 1) * $page_size;
    $limit = $page_size; 
    
    // 取得当页显示的订单列表
    $list = array_slice($list, $offset, $limit);

    // 分页
    $pagination = new Pagination(
        $total, $page_size, $page, 'page', $url, null, $filter
    );
    
    $smarty->assign('total', $total);  // 订单总数 
    $smarty->assign('list', $list);  // 订单列表
    $smarty->assign('taxonomy', $taxonomy);  // 订单分类
    $smarty->assign('pagination', $pagination->get_simple_output());  // 分页
}
else {
    $smarty->assign('message', '没有查询到有库存预定成功的订单');
}


$smarty->assign('url', $url);
$smarty->assign('filter', $filter);                          // 筛选条件
$smarty->assign('page_size_list', $page_size_list);          // 每页显示数列表
$smarty->assign('facility_list', $facility_list);            // 用户所处仓库列表
$smarty->assign('order_type_list', $order_type_list);        // 订单类型列表
$smarty->assign('shipping_type_list', $shipping_type_list);  // 配送方式列表 
$smarty->assign('distributor_list', $distributor_list);      // 分销商列表

$smarty->display('oukooext/picking_list.htm');


/**
 * 根据查询条件过滤订单列表
 *
 * @param array $list 订单列表
 */
function picking_list_filter(& $list, $filter = array()) {
    if (empty($list) || empty($filter)) return;
	
    foreach ($list as $key => $order) {
        $flag = true;
        if ($flag && isset($filter['facility_id'])) {
            $flag = $flag && ($order['facility_id'] == $filter['facility_id']);
        }
        if ($flag && isset($filter['order_type_id'])) {
            $flag = $flag && ($order['order_type_id'] == $filter['order_type_id']);
        }
        if ($flag && isset($filter['distributor_id'])) {
            $flag = $flag && ($order['distributor_id'] == $filter['distributor_id']);
        }
        if ($flag && isset($filter['shipping_id'])) {
            $flag = $flag && ($order['shipping_id'] == $filter['shipping_id']);
        }
        if ($flag && isset($filter['strike'])) {
            $flag = $flag && $order['strike'];
        }
        if (!$flag) {
            unset($list[$key]);
        }
    }
}
