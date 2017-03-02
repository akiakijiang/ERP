<?php

/**
 * 分销订单查询
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com 
 */
 
define('IN_ECS', true);
require_once(dirname(__FILE__) . '/init.php');
require_once(ROOT_PATH . 'admin/distribution.inc.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_page.php');

// 请求
$act = 
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('filter', 'search')) 
    ? $_REQUEST['act'] 
    : null ;
// 页码
$page = 
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
// 是否为导出
$export = 
    isset($_REQUEST['action']) && $_REQUEST['action'] == '导出' 
    ? true : false ;

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
            //'distributor_id'  => isset($_GET['distributor_id']) ? $_GET['distributor_id'] : null,
            'shipping_id'     => isset($_GET['shipping_id']) ? $_GET['shipping_id'] : null,
            'order_status'    => isset($_GET['order_status']) ? $_GET['order_status'] : 0,  // 默认未确认
            'pay_status'      => isset($_GET['pay_status']) ? $_GET['pay_status'] : null,
            'shipping_status' => isset($_GET['shipping_status']) ? $_GET['shipping_status'] : null,
            'consignee'       => isset($_GET['consignee']) ? $_GET['consignee'] : null,
            'start'           => isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', mktime(0,0,0, date('m'), 1, date('Y'))),
            'end'             => isset($_GET['end']) ? $_GET['end'] : null,
            'pay_id'          => isset($_GET['pay_id']) ? $_GET['pay_id'] : null,
            'keywords'        => isset($_GET['keywords']) ? $_GET['keywords'] : null,
            'time_field'      => isset($_GET['time_field']) ? $_GET['time_field'] : 'order_time',
        );
        $extra_params = $filter;
        $cond = _get_conditions($filter);
}

// 状态列表
$order_status_list    = $GLOBALS['_CFG']['adminvars']['order_status'];    // 订单状态
$pay_status_list      = $GLOBALS['_CFG']['adminvars']['pay_status'];      // 支付状态
$shipping_status_list = $GLOBALS['_CFG']['adminvars']['shipping_status']; // 发货状态 
$shipping_list = array(
    '44' => '顺风快递',
    '49' => '顺丰快递COD',
    '47' => '邮政EMS',
    '36' => '邮政COD',
    '87' => '龙邦快递',
    '88' => '龙邦COD',
);


// 构造分页参数
$sql = "
    SELECT count(o.order_id) 
    FROM 
        {$ecs->table('order_info')} AS o
        INNER JOIN distributor AS d ON d.distributor_id = o.distributor_id
        INNER JOIN order_attribute oa ON o.order_id = oa.order_id 
    WHERE 
        ". party_sql('o.party_id', NULL, $_SESSION['party_id']) ."
        AND oa.attr_name = 'OUTER_TYPE' AND oa.attr_value = d.name
        AND o.distributor_id = '{$_SESSION['distributor_id']}'
        AND o.distributor_id > 0 AND o.order_type_id = 'SALE' {$cond}
";
//pp($sql);
$total = $db->getOne($sql); // 总记录数
$page_size = 15;  // 每页数量
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size;
if ($export) {
    $offset = 0;
    $limit  = 65535;
}

// 查询订单
$sql = "
    SELECT 
        o.order_id, o.order_sn, o.order_time, o.order_amount, o.shipping_name, o.order_status, o.shipping_status, 
        o.consignee, cb.bill_no,
        o.pay_status, o.pay_name, o.taobao_order_sn, o.distribution_purchase_order_sn, d.name AS distributor_name,
        (SELECT MIN(action_time) FROM {$ecs->table('order_action')} WHERE order_id = o.order_id AND order_status = 1) AS confirm_time,
        o.shipping_time
    FROM 
        {$ecs->table('order_info')} AS o
        INNER JOIN distributor AS d ON d.distributor_id = o.distributor_id
        INNER JOIN order_attribute oa ON o.order_id = oa.order_id
        LEFT JOIN ecs_carrier_bill cb ON o.carrier_bill_id = cb.bill_id
    WHERE 
        ". party_sql('o.party_id', NULL, $_SESSION['party_id']) ."   
        AND oa.attr_name = 'OUTER_TYPE' AND oa.attr_value = d.name
        AND o.distributor_id = '{$_SESSION['distributor_id']}'
        AND o.distributor_id > 0 AND o.order_type_id = 'SALE' {$cond}    -- 乐琪的销售订单
    ORDER BY o.order_id DESC
    LIMIT {$offset}, {$limit}
";
//pp($sql);
$ref_field = $ref_orders = array();
$order_list = $db->getAllRefby($sql, array('order_id'), $ref_field, $ref_orders, false);

// 取得订单是否有定制图案
if ($order_list) {
    $sql = "
    	SELECT o.order_id, og.goods_name, og.goods_price, og.goods_number
    	FROM {$ecs->table('order_info')} AS o
    	    LEFT JOIN {$ecs->table('order_goods')} AS og ON og.order_id = o.order_id
        WHERE o.order_id ". db_create_in($ref_field['order_id']) ."
        GROUP BY o.order_id";
    $ref_tmp = $ref_order_goods = array();
    $result = $db->getAllRefby($sql, array('order_id'), $ref_tmp, $ref_order_goods, false);

    foreach ($ref_orders['order_id'] as $key => &$order) {
        $order[0]['goods_list'] = $ref_order_goods['order_id'][$key];        
    }
}

// 导出
if ($export) {
    $filename = "分销订单列表";
    
    if (!empty($order_list)) {
        set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
        require_once 'PHPExcel.php';
        require_once 'PHPExcel/IOFactory.php';
        
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($filename);
        
        $sheet = $excel->getActiveSheet();
        
        $sheet->setCellValue('A1', "订单号");
        $sheet->setCellValue('B1', "订单金额");
        $sheet->setCellValue('C1', "下单时间");
        $sheet->setCellValue('D1', "分销商");
        $sheet->setCellValue('E1', "承运商");
        $sheet->setCellValue('F1', "订单状态");
        $sheet->setCellValue('G1', "支付方式");
        $sheet->setCellValue('H1', "外部订单号");
        $sheet->setCellValue('J1', "分销采购订单号");
        $sheet->setCellValue('K1', "定制图案");
        $sheet->setCellValue('L1', "发货时间");
        
        $i = 2;
        foreach ($order_list as $order) {
            $sheet->setCellValueExplicit("A{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue("B{$i}", $order['order_amount']);
            $sheet->setCellValue("C{$i}", $order['order_time']);
            $sheet->setCellValue("D{$i}", $order['distributor_name']);
            $sheet->setCellValue("E{$i}", $order['shipping_name']);
            $sheet->setCellValue("F{$i}", "{$order_status_list[$order['order_status']]}, {$pay_status_list[$order['pay_status']]}, {$shipping_status_list[$order['shipping_status']]}");
            $sheet->setCellValue("G{$i}", $order['pay_name']);
            $sheet->setCellValue("H{$i}", $order['taobao_order_sn']);
            $sheet->setCellValue("J{$i}", $order['distribution_purchase_order_sn']);
            $sheet->setCellValue("K{$i}", $order['customize'] ? '是' : '否' );
            $sheet->setCellValue("L{$i}", @date('Y-m-d H:i:s', $order['shipping_time']));
            $i++;
        }
        
        // 输出
        if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $output->save('php://output');
            exit;
        }
    }
}

// 分页
$pagination = new Pagination($total, $page_size, $page, 'page', $url = 'salesOrderQuery.php', null, $extra_params);


$smarty->assign('order_list',     $order_list);  // 订单列表

$smarty->assign('filter',       $filter);

$smarty->assign('order_status_list',    $order_status_list);      // 发货状态 
$smarty->assign('shipping_status_list', $shipping_status_list);   // 订单状态列表
$smarty->assign('pay_status_list',      $pay_status_list);        // 支付状态列表
$smarty->assign('distributor_list',     distribution_get_distributor_list()); // 分销商列表
$smarty->assign('shipping_list',        $shipping_list);          // 配送方式列表
$smarty->assign('payment_list',         payment_list());          // 支付方式列表

$smarty->assign('total', $total);  // 总数
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页

$smarty->display('api/salesOrderQuery.htm');


/**
 * 根据请求返回查询条件
 * 
 * @return string
 */
function _get_conditions($cond)
{
    global $db, $ecs; 
    
    if (is_array($cond) && !empty($cond))
        $cond = array_filter(array_map('trim', $cond), 'strlen');
    
    if (!empty($cond)) {
        // 按关键字模糊搜索
        if (isset($cond['keywords'])) {
            $keywords = $db->escape_string($cond['keywords']);
            
            if ($carrier_bill_id = $db->getOne("SELECT bill_id FROM {$ecs->table('carrier_bill')} WHERE bill_no = '{$keywords}' LIMIT 1")) {
                // 先看有无运单号匹配
                return " AND o.carrier_bill_id = '{$carrier_bill_id}' ";
            }
            
            // 如果搜索关键字是数字
            if (eregi("^[0-9]+$", $keywords)) {  
                return " AND ( o.order_sn = '{$keywords}' OR o.taobao_order_sn = '{$keywords}' OR 
                    o.distribution_purchase_order_sn = '{$keywords}' ) ";
            } else {
                return " AND ( o.shipping_name LIKE '{$keywords}%' OR
                    o.pay_name LIKE '{$keywords}%' OR o.consignee LIKE '{$keywords}%' ) ";
            }
        }
        // 多条件过滤
        else {
            $conditions = array();
            if ($cond['time_field'] == 'order_time') {
                if (isset($cond['start']) && strtotime($cond['start']) !== false) {
                    $conditions[] = "o.`order_time` > '". $db->escape_string($cond['start']) ."'";
                }
                if (isset($cond['end']) && strtotime($cond['end']) !== false) {
                    $conditions[] = "o.`order_time` < DATE_ADD('". $db->escape_string($cond['end']) ."', INTERVAL 1 DAY)";
                }
            } else if ($cond['time_field'] == 'shipping_time') {
                if (isset($cond['start']) && strtotime($cond['start']) !== false) {
                    $conditions[] = "o.`shipping_time` > '". strtotime($cond['start']) ."'";
                }
                if (isset($cond['end']) && strtotime($cond['end']) !== false) {
                    list($_y, $_m, $_d) = explode('-', date('Y-m-d', strtotime($cond['end'])));  // 得到要查询的年月日
                    $_end = mktime(23, 59, 00, $_m, $_d, $_y);
                    $conditions[] = "o.`shipping_time` < '{$_end}'";
                }
            }

            if (isset($cond['distributor_id']) && $cond['distributor_id'] > -1) {
                $conditions[] = "o.`distributor_id` = '". $db->escape_string($cond['distributor_id']) ."'";
            }
            if (isset($cond['shipping_id']) && $cond['shipping_id'] > -1) {
                $conditions[] = "o.`shipping_id` = '". $db->escape_string($cond['shipping_id']) ."'";
            }
            if (isset($cond['order_status']) && $cond['order_status'] > -1) {
                $conditions[] = "o.`order_status` = '". $db->escape_string($cond['order_status']) ."'";
            }
            if (isset($cond['pay_status']) && $cond['pay_status'] > -1) {
                $conditions[] = "o.`pay_status` = '". $db->escape_string($cond['pay_status']) ."'";
            }
            if (isset($cond['shipping_status']) && $cond['shipping_status'] > -1) {
                $conditions[] = "o.`shipping_status` = '". $db->escape_string($cond['shipping_status']) ."'";
            }
            if (isset($cond['consignee'])) {
                $conditions[] = "o.`consignee` = '". $db->escape_string($cond['consignee']) ."'";
            }
            if (isset($cond['pay_id']) && $cond['pay_id'] > -1) {
                $conditions[] = "o.`pay_id` = '". $db->escape_string($cond['pay_id']) ."'";
            }
            if (!empty($conditions))
                return ' AND ( ' . implode(' AND ', $conditions) . ' )';   
        }
    }
}

