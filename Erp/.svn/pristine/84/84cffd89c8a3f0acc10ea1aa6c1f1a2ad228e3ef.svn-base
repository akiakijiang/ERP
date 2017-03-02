<?php

/**
 * 预付款管理
 * 
 * @author yxiang@oukoo.com
 * @copyright ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('cw_prepayment','cw_prepayment_supplier');
require_once('function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');


$act =  // 请求动作
    isset($_REQUEST['act']) && trim($_REQUEST['act']) 
    ? $_REQUEST['act'] 
    : 'list';
$page =  // 当前页码
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
$info =  // 消息 
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? $_REQUEST['info'] 
    : false;
$type =  // 类型，是供应商还是分销商
    isset($_REQUEST['type']) && trim($_REQUEST['type'])
    ? $_REQUEST['type']
    : 'DISTRIBUTOR';
    
// 通过权限判断可操作供应商还是分销商预付款
if ($type=='DISTRIBUTOR' && !check_admin_priv('cw_prepayment')) {
    $type='SUPPLIER';
}
if ($type=='SUPPLIER' && !check_admin_priv('cw_prepayment_supplier')) {
    $type='DISTRIBUTOR';
}

// 消息
if ($info) {
    $smarty->assign('message', $info);
}
 
if ($act == 'search') {
    $extra_params['keyword'] = trim($_REQUEST['keyword']);
    $cond = isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : NULL ;
}

// 验证操作人员所属的组织
if (!party_explicit($_SESSION['party_id'])) {
    $smarty->assign('message', '您可能需要选择明确的组织才能正确地显示该页面');
}
        
// 按分页取得列表
$total = prepay_get_account_count_by_conditions($type, $cond, $_SESSION['party_id']); // 总记录数
$page_size = 15;  // 每页数量
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size;
$list = prepay_get_all_account_by_conditions($type, $cond, $_SESSION['party_id'], $offset, $limit);

// 分页
$pagination = new Pagination(
    $total, $page_size, $page, 'page', $url = 'prepayment.php', null, $extra_params
);

// 显示
$smarty->assign('type',       $type);  // 账户类型
$smarty->assign('total',      $total);  // 总数
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
$smarty->assign('list',       $list);

$smarty->display('oukooext/prepayment.htm');

