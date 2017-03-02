<?php
/**
 * Z101销量统计
 * 
 * @author yxiang@oukoo.com 05/06/2009  
 */
define('IN_ECS', true);

require_once('includes/init.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
set_time_limit(300);

// 请求
$act = 
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('filter', 'search')) 
    ? $_REQUEST['act'] 
    : null ;
// 是否为导出
$export = 
    isset($_REQUEST['action']) && $_REQUEST['action'] == '导出' 
    ? true : false ;
// 当前页码
$page = 
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;
// 每页多少记录数
$page_size =
    is_numeric($_REQUEST['size']) && $_REQUEST['size'] > 0
    ? $_REQUEST['size']
    : 20 ;
// 销售渠道
$sale_channel =
    isset($_REQUEST['sale_channel']) ? $_REQUEST['sale_channel'] : null ;
    
// 构造查询条件
$extra_params = array();
switch ($act) {
    case 'filter' :
    case 'search' :
        if (isset($_POST['filter'])) {
            $extra_params = $filter = $_POST['filter'];
            $cond = _get_conditions($filter);            
        }
        break;
        
    case null :
        $filter = array(
            'order_status'    => is_numeric($_GET['order_status']) ? $_GET['order_status'] : null,
            'pay_status'      => is_numeric($_GET['pay_status']) ? $_GET['pay_status'] : null,
            'shipping_status' => is_numeric($_GET['shipping_status']) ? $_GET['shipping_status'] : null,
            'start'           => !empty($_GET['start']) && strtotime($_GET['start']) ? $_GET['start'] : date('Y-m-d', mktime(0,0,0, date('m'), 1, date('Y'))),
            'end'             => !empty($_GET['end']) && strtotime($_GET['end']) ? $_GET['end'] : date('Y-m-d'),
            'size'            => $page_size,
            'sale_channel'    => $sale_channel,
        );
        $extra_params = $filter;
        $cond = _get_conditions($filter);
}

// 状态列表
$order_status_list    = $GLOBALS['_CFG']['adminvars']['order_status'];    // 订单状态
$pay_status_list      = $GLOBALS['_CFG']['adminvars']['pay_status'];      // 支付状态
$shipping_status_list = $GLOBALS['_CFG']['adminvars']['shipping_status']; // 发货状态 
$sale_channel_list    = array('1' => 'OPPO', '2' => 'Taobao', '3' => 'OUKU');  // 销售渠道
$page_size_list       = array('20' => '20', '50' => '50', '100' => '100', '65535' => '不分页');

// 查询订单
$sql = "
    SELECT
        o.order_id, o.party_id, o.order_sn, o.order_amount, o.order_time, o.distributor_id, 
        o.order_status, o.pay_status, o.shipping_status, o.pay_name, o.province, o.city,
        (SELECT MIN(action_time) FROM {$ecs->table('order_action')} WHERE order_id = o.order_id AND order_status = 1) AS confirm_time
    FROM 
        {$ecs->table('order_info')} AS o
    WHERE 
        o.order_type_id = 'SALE' {$cond}
    HAVING confirm_time BETWEEN '{$filter['start']}' AND DATE_ADD('{$filter['end']}', INTERVAL 1 DAY)
";
$order_list = $slave_db->getAll($sql);
$total = 0;
if ($order_list) {
    $total = count($order_list);
    
    // 取得所有分销商
    $distributor_list = $slave_db->getAll("SELECT distributor_id, name FROM distributor WHERE status = 'NORMAL'"); 
    $distributor_list = Helper_Array::toHashmap((array)$distributor_list, 'distributor_id', 'name');
    
    // 取得发货地区
    $region_ids = array_merge(Helper_Array::getCols($order_list, 'province'), Helper_Array::getCols($order_list, 'city'));
    $sql = "SELECT region_id, region_name FROM {$ecs->table('region')} WHERE region_id ". db_create_in($region_ids);
    $region_list = Helper_Array::toHashmap((array)$slave_db->getAll($sql), 'region_id', 'region_name') ;

    foreach ($order_list as $key => $order) {
        // 订单的分销商和销售渠道
        if ($order['party_id'] == PARTY_OUKU_MOBILE) {
            $order_list[$key]['distributor_name'] = '欧酷';
            $order_list[$key]['sale_channel_name'] = 'OUKU';
        } else {
            // OPPO
            if ($order['distributor_id'] == 20) {
                $order_list[$key]['distributor_name'] = 'OPPO官方';
                $order_list[$key]['sale_channel_name'] = 'OPPO';
            }
            // TAOBAO
            else {
                $order_list[$key]['distributor_name'] = $distributor_list[$order['distributor_id']];
                $order_list[$key]['sale_channel_name'] = 'Taobao';
            }
        }
        
        // 订单的状态
        $order_list[$key]['mix_status_name'] = get_order_status($order['order_status']) . '，' .
            get_pay_status($order['pay_status']) . '，' . get_shipping_status($order['shipping_status']);
            
        // 送货地址
        $order_list[$key]['region'] = '[' . $region_list[$order['province']] . ']' . '[' . $region_list[$order['city']] . ']'; 
    }
}



// 构造分页
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size; 


// 构造分类列表数据
if (!empty($order_list)) {
    $order_list = array_slice($order_list, $offset, $limit);
}

// 构造分页
$pagination = new Pagination(
    $total, $page_size, $page, 'page', $url = 'analyze_z101.php', null, $filter
);


$smarty->assign('page_size_list',       $page_size_list);         // 可选分页  
$smarty->assign('sale_channel_list',    $sale_channel_list);      // 销售渠道
$smarty->assign('order_status_list',    $order_status_list);      // 发货状态 
$smarty->assign('shipping_status_list', $shipping_status_list);   // 订单状态列表
$smarty->assign('pay_status_list',      $pay_status_list);        // 支付状态列表

$smarty->assign('total',       $total);   // 总计记录条
$smarty->assign('filter',      $filter);  // 过滤条件
$smarty->assign('order_list',  $order_list);  // 数据列表
$smarty->assign('pagination',  $pagination->get_simple_output());  // 分页

$smarty->display('oukooext/analyze_z101.htm');


/**
 * 根据请求返回查询条件
 * 
 * @return string
 */
function _get_conditions($cond)
{
    global $slave_db, $ecs; 
    
    // 过滤空的查询条件
    if (is_array($cond) && !empty($cond))
        $cond = array_filter(array_map('trim', $cond), 'strlen');
    
    $conditions = array();
    if (!empty($cond)) {
        $cond = array_map(array(& $slave_db, 'escape_string'), $cond);
        
        // 限制了销售渠道
        if ($cond['sale_channel'] > 0) {
            // OPPO
            if ($cond['sale_channel'] == 1) {
                $conditions[] = "(o.party_id = ". PARTY_LEQEE_MOBILE." AND o.distributor_id = 20)";
            } 
            // TAOBAO
            else if ($cond['sale_channel'] == 2) {
                $conditions[] = "(o.party_id = ". PARTY_LEQEE_MOBILE ." AND o.distributor_id <> 20)";
            }
            // OUKU
            else if ($cond['sale_channel'] == 3) {
                $conditions[] = "(o.party_id = ". PARTY_OUKU_MOBILE ." AND EXISTS(SELECT 1 FROM {$ecs->table('order_goods')} WHERE order_id = o.order_id AND goods_id = 31994 LIMIT 1))";
            }
        } else {
            $conditions[] = "(o.party_id = ". PARTY_LEQEE_MOBILE." OR (
                o.party_id = ". PARTY_OUKU_MOBILE ." AND EXISTS (SELECT 1 FROM {$ecs->table('order_goods')} WHERE order_id = o.order_id AND goods_id = 31994 LIMIT 1)
            ))";
        }
       
        // 多条件过滤
        if (isset($cond['order_status']) && $cond['order_status'] > -1) {
            $conditions[] = "o.order_status = '{$cond['order_status']}'";
        }
        if (isset($cond['pay_status']) && $cond['pay_status'] > -1) {
            $conditions[] = "o.pay_status = '{$cond['pay_status']}'";
        }
        if (isset($cond['shipping_status']) && $cond['shipping_status'] > -1) {
            $conditions[] = "o.shipping_status = '{$cond['shipping_status']}'";
        }
     
        if (!empty($conditions)) {
            return ' AND ' . implode(' AND ', $conditions) ;
        }
    }
}