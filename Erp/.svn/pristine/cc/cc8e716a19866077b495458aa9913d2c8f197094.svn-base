<?php

/**
 * 快递跟踪
 * 主要用于跟踪EMS发货4天后还未妥投的订单
 */
define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('customer_service_manage_order');
require_once('includes/lib_common.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');

// 配送方式
$shipping_id =
    is_numeric($_REQUEST['shipping_id']) && $_REQUEST['shipping_id'] > 0
    ? $_REQUEST['shipping_id']
    : 36 ;  // 默认EMS_COD
    
// 发货天数
$day = 
    isset($_REQUEST['day']) && is_numeric($_REQUEST['day'])
    ? $_REQUEST['day']
    : 4 ;

// 状态
$status = 
    isset($_REQUEST['status']) && in_array($_REQUEST['status'], array(1, 2))
    ? $_REQUEST['status']
    : 1 ;
    
// 排序
$order_by =
    isset($_REQUEST['order_by']) && in_array($_REQUEST['order_by'], array('order_time', 'shipping_time'))
    ? $_REQUEST['order_by']
    : 'shipping_time' ;
    
// 排序
$order_type =
    isset($_REQUEST['order_type']) && in_array($_REQUEST['order_type'], array('ASC', 'DESC'))
    ? $_REQUEST['order_type']
    : 'DESC' ;
    
// 当前页码
$page = 
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;
    
// 每页多少记录数
$page_size = 15;

// 过滤条件
$filter = array(
    'shipping_id' => $shipping_id, 'day' => $day, 'status' => $status,
    'order_by' => $order_by, 'order_type' => $order_type
);


if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
    $order_id = isset($_REQUEST['order_id']) && is_numeric($_REQUEST['order_id']) ? $_REQUEST['order_id'] : false ; 
    if ($order_id) { $order = order_info($order_id); }

    if ($order && $order['order_status'] == 1 && $order['shipping_status'] == 1) {
        switch ($_POST['act']) {
            // 标记为跟踪
            case 'track' :
                $message = "该订单长时间未妥投，客服已跟踪";
                $sql = "
                    INSERT INTO {$ecs->table('order_action')} (
                        order_id, order_status, shipping_status, pay_status, action_time, action_note, invoice_status, action_user, shortage_status
                    ) VALUES (
                        '{$order['order_id']}', -3, -3, -3, NOW(), '{$message}', -3, '{$_SESSION['admin_name']}', -3
                    )
                ";
                $db->query($sql);
                $smarty->assign('message', $message);              
                break;
                
            // 收货确认                
            case 'confirm' :
                $ret = $db->query("UPDATE {$ecs->table('order_info')} SET shipping_status = 2 WHERE order_id = '{$order['order_id']}'");
                if ($ret) {
                    $message = "帮助用户收货确认";
                    $sql = "
                        INSERT INTO {$ecs->table('order_action')} (
                            order_id, order_status, shipping_status, pay_status, action_time, action_note, invoice_status, action_user, shortage_status
                        ) VALUES (
                            '{$order['order_id']}', '{$order['order_status']}', '2', '{$order['pay_status']}', NOW(), '{$message}', '{$order['invoice_status']}', '{$_SESSION['admin_name']}', '{$order['shortage_status']}'
                        )
                    ";
                    $db->query($sql);
                }
                $smarty->assign('message', $message);
                break;
        }
    } else {
        $smarty->assign('message', $message);
    }
}


$conditions = _get_conditions($filter);

/**
 * 订单总数
 */
$sql = "
    SELECT 
        COUNT(o.order_id) 
    FROM 
        {$ecs->table('order_info')} AS o
        LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = o.pay_id
    WHERE
        o.order_status = 1 AND o.shipping_status = 1 AND
        ( (o.order_type_id = 'SALE' AND (
            (p.pay_code = 'cod' AND o.pay_status = 0) OR (p.pay_code != 'cod' AND o.pay_status = 2)
          )
        ) OR 
        o.order_type_id IN ('RMA_EXCHANGE', 'SHIP_ONLY') ) AND {$conditions}
";
$total = $db->getOne($sql);

// 构造分页
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size; 


// 发货4天后还未妥投的EMS配送订单
$sql = "
    SELECT
        o.order_id, o.order_sn, o.order_time, o.shipping_time, o.tel, o.mobile,
        o.order_status, o.pay_status, o.shipping_status
    FROM
        {$ecs->table('order_info')} AS o
        LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = o.pay_id
    WHERE
        o.order_status = 1 AND o.shipping_status = 1 AND
        ( (o.order_type_id = 'SALE' AND (
            (p.pay_code = 'cod' AND o.pay_status = 0) OR (p.pay_code != 'cod' AND o.pay_status = 2)
          ) 
        ) OR 
        o.order_type_id IN ('RMA_EXCHANGE', 'SHIP_ONLY') ) AND {$conditions}
    ORDER BY {$order_by} {$order_type}
    LIMIT {$offset}, {$limit}
";
$order_list = $db->getAll($sql);
if ($order_list) {
    foreach ($order_list as & $order) {
        $order['mixed_status_name'] = get_order_status($order['order_status']) .'，' .
            get_pay_status($order['pay_status']) .'，'. get_shipping_status($order['shipping_status']) ;
    }
}


// 构造分页
$pagination = new Pagination(
    $total, $page_size, $page, 'page', $url = 'shipping_track.php', null, $filter
);


$shipping_list = Helper_Array::toHashmap((array)getShippingTypes(), 'shipping_id', 'shipping_name');
$day_list = array('3' => '> 3', '4' => '> 4', '5' => '> 5');
$status_list = array('1' => '未跟踪', '2' => '已跟踪');
$order_by_list = array('shiping_time' => '发货时间', 'order_time' => '下单时间');
$order_type_list = array('DESC' => '倒序', 'ASC' => '顺序');

$smarty->assign('order_by_list', $order_by_list);
$smarty->assign('order_type_list', $order_type_list);
$smarty->assign('status_list', $status_list);
$smarty->assign('day_list', $day_list);
$smarty->assign('shipping_list', $shipping_list);

$smarty->assign('filter', $filter);  // 过滤条件
$smarty->assign('order_list', $order_list);
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
$smarty->display('oukooext/shipping_track.htm');



/**
 * 构造查询条件
 * 
 * @param array $filter
 * @return string
 */
function _get_conditions(& $filter) 
{
    $start = strtotime("-16 day");
    $end   = strtotime("-{$filter['day']} day");
    
    $cond = " 
        o.shipping_id = '{$filter['shipping_id']}' AND
        o.shipping_time BETWEEN '{$start}' AND '{$end}' AND " . party_sql('o.party_id');
    if ($filter['status'] == 1) {
        $cond .= " AND NOT EXISTS";
    } else {
        $cond .= " AND EXISTS";
    }
    $cond .= "(
        SELECT 1 FROM {$GLOBALS['ecs']->table('order_action')} WHERE order_id = o.order_id AND
        order_status = -3 AND shipping_status = -3 AND pay_status = -3 AND invoice_status = -3 AND shortage_status = -3 
    )";
    
    return $cond;
}
