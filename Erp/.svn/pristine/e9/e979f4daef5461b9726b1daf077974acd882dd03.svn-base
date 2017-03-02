<?php

/**
 * 查看预付款交易明细
 * 
 * @author yxiang@oukoo.com
 * @copyright ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('cw_prepayment');
require_once(ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once(ROOT_PATH . 'includes/cls_page.php');

if (!party_explicit($_SESSION['party_id'])) {
    exit('请选择分公司的party_id，再进行操作');
}

// 交易类型
$transaction_type_list = array('all' => '所有', 'add' => '增加', 'consume' => '消费');

// 处理请求
$supplier_id =  // 付款单位id
    isset($_REQUEST['supplier_id']) && is_numeric($_REQUEST['supplier_id'])
    ? $_REQUEST['supplier_id']
    : false;
$page =         // 当前页码
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
$start = isset($_REQUEST['filter_start']) && strtotime($_REQUEST['filter_start']) 
    ? $_REQUEST['filter_start'] 
    : null;
$end = isset($_REQUEST['filter_end']) && strtotime($_REQUEST['filter_end']) 
    ? $_REQUEST['filter_end'] 
    : null;
$type = isset($_REQUEST['filter_type']) && in_array($_REQUEST['filter_type'], array_keys($transaction_type_list)) 
    ? $_REQUEST['filter_type'] 
    : 'all';

// 过滤条件
$filter = $arguments = array('filter_start' => $start, 'filter_end' => $end, 'filter_type' => $type);

$arguments['filter_start'] = $arguments['filter_start'] 
    ?  date('Y-m-d H:i:s', strtotime($arguments['filter_start'])) 
    : null ;
$arguments['filter_end'] = $arguments['filter_end'] 
    ?  date('Y-m-d H:i:s', strtotime('+1 day', strtotime($arguments['filter_end']))) 
    : null ;
    

// 取得账户
try {
    $handle = prepay_get_soap_client();
    $account_id = $handle->getAccountIdBySupplierId($supplier_id, $_SESSION['party_id'], trim($_REQUEST['type']));	
} catch (SoapFault $e) {
    die('该账户不存在，或者不在当前组织下');
}

// 取得交易总数
$total = prepay_get_transaction_count_by_conditions(
    $account_id, null, null, $arguments['filter_start'], $arguments['filter_end'], $arguments['filter_type'], 0, 1
);

// 构造分页
$page_size = 10;  // 每页数量
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size;

// 取得交易列表
$list = prepay_get_all_transaction_by_conditions(
    $account_id, null, null, $arguments['filter_start'], $arguments['filter_end'], $arguments['filter_type'], $offset, $limit
);

// 支付方式
$payment_type_list = prepay_payment_type_list();

if (!empty($list)) {
    // 取得每个交易的支付方式
    foreach ($list as $key => $item) {
        if (isset($payment_type_list[$item->prepaymentPaymentTypeId])) {
            $list[$key]->prepaymentPaymentType = $payment_type_list[$item->prepaymentPaymentTypeId];
        }
    }
}

// 构造分页
$extra_params = array('supplier_id' => $supplier_id, "type" =>trim($_REQUEST['type']));
$extra_params = array_merge($extra_params, $filter);
$pagination = new Pagination(
    $total, $page_size, $page, 'page', $url = 'prepayment_transaction.php', null, $extra_params
);
 

$smarty->assign('filter',   $filter);         // 过滤条件
$smarty->assign('supplier_id', $supplier_id);  // 供应商
$smarty->assign('type', trim($_REQUEST['type']));
$smarty->assign('total',  $total);       // 总数
$smarty->assign('list',   $list);        // 支付交易列表
$smarty->assign('pagination',            $pagination->get_simple_output());
$smarty->assign('payment_type_list',     $payment_type_list);      // 付款方式
$smarty->assign('transaction_type_list', $transaction_type_list);  // 交易类型   all | add | consume

$smarty->display('oukooext/prepayment_transaction.htm');
