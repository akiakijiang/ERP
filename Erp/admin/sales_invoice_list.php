<?php

/**
 * 发票管理
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

// 请求
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('search', 'delete', 'rebate','csv')) 
    ? $_REQUEST['act'] 
    : null ;
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
// 消息
$message =
    isset($_REQUEST['message']) && trim($_REQUEST['message'])
    ? $_REQUEST['message']
    : false; 

/**
 * 删除动作
 */    
if ($act == 'delete') {
    // 主键值
    $pkv = isset($_REQUEST['sales_invoice_id']) && is_numeric($_REQUEST['sales_invoice_id']) 
        ? $_REQUEST['sales_invoice_id']
        : false ;
        
    if ($pkv) {
        $sales_invoice = $db->getRow("SELECT * FROM sales_invoice WHERE sales_invoice_id = '{$pkv}'", true);
    }
    
    do {
        if (!$sales_invoice) {
            $message = '参数有误，找不到要删除的记录';
            break;
        }
        
        if ($sales_invoice['status'] != 'INIT') {
            $message = '该发票已确认，不能删除';
            break;    
        }
        $db->query("DELETE FROM romeo.prepayment_transaction WHERE note like 'sales_invoice_id:{$pkv}订单号%'");
        // 删除发票及明细
        $db->query("DELETE FROM ecshop.sales_invoice_item WHERE sales_invoice_id = '{$pkv}'");
        $db->query("DELETE FROM ecshop.sales_invoice WHERE sales_invoice_id = '{$pkv}'");
        $message = '删除成功';
    } while (false);
}

/**
 * 返利结算
 */
else if ($act == 'rebate' && $_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
    do {
        if (empty($_POST['sales_invoice_id']) || !is_array($_POST['sales_invoice_id'])) {
            $message = '请先选择发票';
            break;
        }
        
        // 添加折扣项
        $sql = "
            INSERT INTO sales_rebate (total_rebate, created_user, created_stamp, updated_stamp) VALUES (
            0, '{$_SESSION['admin_name']}', NOW(), NOW())
        ";
        $result = $db->query($sql, 'SILENT');
        $sales_rebate_id = $db->insert_id();
        if (!$result || !$sales_rebate_id) {
            $message = '数据库执行失败, 不能添加折扣记录';
            break;
        }
        
        // 添加折扣明细
        $snatch = array();
        $total_discount = 0;
        $sales_invoice_ids = array();
        // 循环取得发票的折扣项
        foreach ($_POST['sales_invoice_id'] as $sales_invoice_id) {
            // 取得发票信息, 如果发票不是已确认状态的，则跳过
            $sales_invoice = $db->getRow("SELECT sales_invoice_id, status FROM sales_invoice WHERE sales_invoice_id = '{$sales_invoice_id}' LIMIT 1");
            if (!$sales_invoice || $sales_invoice['status'] != 'CONFIRMED') {
                continue;
            }
            
            $discount = $db->getOne("SELECT SUM(unit_price * quantity) FROM sales_invoice_item WHERE item_type = 'DISCOUNT' AND sales_invoice_id = '{$sales_invoice_id}'");
            // 如果折扣金额不为0则添加折扣明细
            if ($discount !== false) {
                $total_discount += $discount;
                $snatch[] = "('{$sales_rebate_id}', '{$sales_invoice_id}', '{$discount}', NOW())";
            }
            $sales_invoice_ids[] = $sales_invoice['sales_invoice_id'];
        }
        $sql = "INSERT INTO sales_rebate_item (sales_rebate_id, sales_invoice_id, rebate_cost, created_stamp) VALUES %s";
        if (!empty($snatch)) {
            $result = $result && $db->query(sprintf($sql, implode(',', $snatch)), 'SILENT');
        }
        
        // 明细添加成功了
        if ($result) {
            // 生成退款
            $refund_result = sales_invoice_refund($sales_invoice_ids);
            
            // 记录销售折扣与退款单号间的关系
            if ($refund_result) {
                $sql = "INSERT INTO sales_rebate_refund (sales_rebate_id, refund_id) VALUES %s";
                $snatch = array();
                foreach ($refund_result as $refund_id) {
                    $snatch[] = "('{$sales_rebate_id}', '{$refund_id}')";     
                }
                $db->query(sprintf($sql, implode(',', $snatch)), 'SILENT');
            }

            //  修改发票状态为已结算
            $db->query("UPDATE sales_invoice SET status = 'COMPLETED' WHERE sales_invoice_id ". db_create_in($sales_invoice_ids));
            $db->query("UPDATE sales_rebate SET total_rebate = '{$total_discount}' WHERE sales_rebate_id = '{$sales_rebate_id}'");
            if (!empty($refund_result)) {
                $message = '已完成结算, 并生成退款, 退款单号为：' . implode(', ', $refund_result) ;
            } else {
                $message = '已完成结算';
            }
        } else {
            $db->query("DELETE FROM sales_rebate WHERE sales_rebate_id = '{$sales_rebate_id}'");
            $db->query("DELETE FROM sales_rebate_item WHERE sales_rebate_id = '{$sales_rebate_id}'");
            $message = '折扣明细添加失败，请重试';
        }
    } while (false);
}


/**
 * 列表显示
 */
$filter = array(
    'size' => $page_size,
    'page' => $page,
);
$conds = _get_conditions($act, $filter);
// 总记录数
$total = $db->getOne("SELECT COUNT(distinct si.sales_invoice_id) 
                         FROM sales_invoice si 
                         LEFT JOIN sales_invoice_item sii ON si.sales_invoice_id = sii.sales_invoice_id
                         LEFT JOIN ecs_order_info o ON o.order_id = sii.order_id WHERE ". party_sql('si.party_id') ." {$conds}" );	
// 构造分页
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size; 

// 销售发票列表
$sql = "
    SELECT si.*, 
        (SELECT SUM(unit_price * quantity) FROM sales_invoice_item WHERE sales_invoice_id = si.sales_invoice_id AND item_type = 'DISCOUNT') as discount,o.order_time,count(distinct si.sales_invoice_id),
        o.order_sn, o.inv_payee	
    FROM sales_invoice AS si 
    LEFT JOIN sales_invoice_item AS sii ON si.sales_invoice_id = sii.sales_invoice_id
    LEFT JOIN ecs_order_info AS o ON o.order_id = sii.order_id
    WHERE " .party_sql('si.party_id') ."{$conds}
    GROUP BY si.sales_invoice_id
    ORDER BY si.created_stamp DESC
    LIMIT {$offset}, {$limit};
";
$links = array(
    // 发票明细
    array(
        'sql' => "SELECT * FROM sales_invoice_item WHERE :in",
        'source_key' => 'sales_invoice_id',
        'target_key' => 'sales_invoice_id',
        'mapping_name' => 'items',
    ),
);
$sales_invoice_list = $db->findAll($sql, $links);
// 查询发票的开票区间
if ($sales_invoice_list) {
    foreach ($sales_invoice_list as & $invoice) {
        if (!empty($invoice['items'])) {
            $inventory_transaction_id_array = 
                Helper_Array::getCols($invoice['items'], 'inventory_transaction_id');
            $sql = "
            	SELECT MIN(created_stamp) AS transaction_begin_date, MAX(created_stamp) AS transaction_end_date 
        	    FROM romeo.inventory_transaction WHERE inventory_transaction_id 
            	". db_create_in($inventory_transaction_id_array);
            $interval = $db->getRow($sql);
            if ($interval) {
                $invoice['transaction_begin_date'] = strtotime($interval['transaction_begin_date']);  // 交易开始时间
                $invoice['transaction_end_date'] = strtotime($interval['transaction_end_date']);      // 交易结束时间
            }
        }
    }
}

// 构造分页
$pagination = new Pagination(
    $total, $page_size, $page, 'page', $url = 'sales_invoice_list.php', null, $filter
);

// 分销商列表
$main_distributor_list = Helper_Array::toHashmap((array)distribution_get_main_distributor_list(), 'main_distributor_id', 'name');
// 发票状态列表
$invoice_status_list = array('INIT' => '未确认', 'CONFIRMED' => '已确认', 'COMPLETED' => '已结算', 'CLOSE' => '已关闭'); 
// 查询每页显示数列表
$page_size_list = array('20' => '20', '50' => '50', '100' => '100', '65535' => '不分页');

// 如果有消息则显示消息
if (!empty($message)) {
    $smarty->assign('message', $message); 
}

if($act == 'csv'){
    foreach ($sales_invoice_list as $key1 => $sales_invoices){
        foreach ($sales_invoice_list[$key1]['items'] as $key2 => $invoices){
            $sql = "select order_sn,order_time,order_amount from ecs_order_info where order_id = '{$invoices['order_id']}'";
            $order = $db->getRow($sql);
            if (($key2 == 0) || ($order['order_sn'].";" != $sales_invoice_list[$key1]['items'][$key2-1]['order_sn'])){
                $sales_invoice_list[$key1]['items'][$key2]['order_sn'] = $order['order_sn'].";";
                $sales_invoice_list[$key1]['items'][$key2]['order_time'] = date("Y-m-d",strtotime($order['order_time'])).";";
                $sales_invoice_list[$key1]['items'][$key2]['order_amount'] = $order['order_amount'].";";
            }
        }
    }
    $smarty->assign('sales_invoice_list', $sales_invoice_list);  // 销退入库的订单列表
    if (in_array($_REQUEST['invoice_status'], array('COMPLETED','CONFIRMED','CLOSE')) ){
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","已开发票明细") . ".csv");	
        $out = $smarty->fetch('sales_invoice/invoice_csv.htm');
        echo iconv("UTF-8","GB18030", $out);
        exit();	
    }
    elseif ($_REQUEST['invoice_status'] == 'INIT'){
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","未开发票明细") . ".csv");	
        $out = $smarty->fetch('sales_invoice/Without_invoice_csv.html');
        echo iconv("UTF-8","GB18030", $out);
        exit();
    }
    else {
        echo  "<script language=\"JavaScript\">\r\n";   echo " alert(\"请选择一种发票状态进行查询\");\r\n";   echo " history.back();\r\n";   echo "</script>";   exit;   		
    }
}
$smarty->assign('act',$act);
$smarty->assign('page_size_list', $page_size_list);
$smarty->assign('invoice_status_list', $invoice_status_list);
$smarty->assign('main_distributor_zhi_list', $main_distributor_zhi_list);
$smarty->assign('main_distributor_list', $main_distributor_list);
$smarty->assign('sales_invoice_list', $sales_invoice_list);  // 销退入库的订单列表
$smarty->assign('filter', $filter);
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
$smarty->display('sales_invoice/sales_invoice_list.htm');


/**
 * 生成退款
 * 
 * @param mixed $sales_invoice_id 销售发票ID
 */
function sales_invoice_refund($sales_invoice_id)
{
    global $db;
    require_once ROOT_PATH . 'RomeoApi/lib_refund.php';
    
    $sales_invoice_id = (array)$sales_invoice_id;
    
    // 取得需要抵扣的明细项并按订单分组，应该只考虑销向的返利 （销退的quantity为负）
    $sql = "SELECT * FROM sales_invoice_item WHERE item_type = 'DISCOUNT' AND quantity > 0 AND sales_invoice_id ". db_create_in($sales_invoice_id);
    $ref_fields = $ref_rowset = array();
    $result = $db->getAllRefby($sql, array('order_id'), $ref_fields, $ref_rowset, false);
    
    if ($result) {
        // 取得订单信息
        $sql = "SELECT order_id, order_sn, user_id FROM ecs_order_info WHERE order_id ". db_create_in($ref_fields['order_id']);
        $orders = Helper_Array::toHashmap((array)$db->getAll($sql), 'order_id');
        
        $refund_result = array();
        foreach ($ref_rowset['order_id'] as $order_id => $rebate_list) {
            // 订单信息
            $order = $orders[$order_id];
            
            // 构造退款信息
            $refund = array(
                'info' => array(
                    'refund_type_id' => '6',  # 退款类型,其他
                    'order_sn' => (string)$order['order_sn'],
                    'customer_user_id' => (string)$order['user_id'],  // 客户ID
                    'order_id' => (string)$order['order_id'],   
                    'applicant' => (string)$_SESSION['admin_name'],
                    'total_amount' => '',
                    'shipping_amount' => '0',  // 配送费0
                    'pack_amount' => '0',  // 包装费
                ),
                'detail' => array(
                    // 商品明细
                    'goods' => array(),
                    // 其他明细
                    // 'others' => array()
                ),
                'payment' => array(
                    'payment_type_id' => '1174702',  # 其他退款方式
                    'note' => '退款账号信息',
                ),
            ); 
            
            // 退款明细
            $total_amount = 0;
            foreach ($rebate_list as $rebate_item) {
                $refund['detail']['goods'][] = array(
                    'reason_id' => '1174704',  # 明细原因 - 分销销售返现
                    'product_id' => (string)$rebate_item['product_id'],
                    'order_goods_id' => (string)$rebate_item['order_goods_id'],
                    'serial_mumber' => (string)$rebate_item['serial_number'],
                    'cost' => (string)abs($rebate_item['unit_price'] * $rebate_item['quantity']),
                    'note' => '退商品返利',
                );
                $total_amount += abs($rebate_item['unit_price'] * $rebate_item['quantity']);
            }
            $refund['info']['total_amount'] = (string)$total_amount;
            // 生成并执行退款
            $refund_id = refund_save($refund);
            if ($refund_id !== false) {                
                // 执行该退款 
                refund_execute(array('refund_id' => $refund_id, 'user' => $_SESSION['admin_name'], 'note' => '返利结算执行退款'));
            }
            $refund_result[] = $refund_id;
        }
        
        return $refund_result;
    }
    
    return false;
}

/**
 * 查询条件
 * 
 * @return string
 */
function _get_conditions($act, & $filter)
{
    // 期初时间
    $start = 
        isset($_REQUEST['start']) && strtotime($_REQUEST['start'])
        ? $_REQUEST['start']
        :date('Y-m-d',strtotime('-1 month'));  // 当月第一天 
    // 期末时间
    $end = 
        isset($_REQUEST['end']) && strtotime($_REQUEST['end'])
        ? $_REQUEST['end']
        : date('Y-m-d') ;
    // 分销商ID
    $main_distributor_id =
        isset($_REQUEST['main_distributor_id']) && $_REQUEST['main_distributor_id'] > 0
        ? $_REQUEST['main_distributor_id']
        : null;
    // 状态
    $invoice_status = 
        isset($_REQUEST['invoice_status']) && trim($_REQUEST['invoice_status'])
        ? $_REQUEST['invoice_status']
        : null;
    // 发票号
    $invoice_no =
        isset($_REQUEST['invoice_no']) && trim($_REQUEST['invoice_no'])
        ? $_REQUEST['invoice_no']
        : null ;
    //订单时间
    $orderstart = 
        isset($_REQUEST['orderstart']) && trim($_REQUEST['orderstart'])
        ? $_REQUEST['orderstart']
        : null ;
    $orderend =
        isset($_REQUEST['orderend']) && trim($_REQUEST['orderend'])
        ? $_REQUEST['orderend']
        : null ;
        
    $ordersn = isset($_REQUEST['ordersn']) && trim($_REQUEST['ordersn'])
        ? $_REQUEST['ordersn']
        : null ;
        
    $inv_payee = isset($_REQUEST['inv_payee']) && trim($_REQUEST['inv_payee'])
        ? $_REQUEST['inv_payee']
        : null ;
    // 过滤条件
    $filter += array(
        'start' => $start, 'end' => $end, 'main_distributor_id' => $main_distributor_id,
        'invoice_status' => $invoice_status, 'invoice_no' => $invoice_no, 'orderstart' => $orderstart,'orderend' => $orderend, 
        'ordersn' => $ordersn, 'inv_payee' => $inv_payee
    );
    if ($start != null && $end != null){
        $conds = "AND si.created_stamp BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY)";
    }
    if ($orderend != null && $orderstart != null){
        $conds .= "AND o.order_time BETWEEN '{$orderstart}' AND DATE_ADD('{$orderend}', INTERVAL 1 DAY)";
    }
    if ($main_distributor_id) {
        $conds .= " AND partner_id = '{$main_distributor_id}'";
    }
    if ($invoice_status) {
        $conds .= " AND status = '{$invoice_status}'";
    }
    if ($invoice_no) {
        $conds .= " AND si.invoice_no = '{$invoice_no}'";
    }
    if ($ordersn) {
    	$conds .= " AND o.order_sn = '{$ordersn}'";
    }
    if ($inv_payee) {
    	$conds .= " AND o.inv_payee = '{$inv_payee}'";
    }
    
    return $conds;
}

?>
