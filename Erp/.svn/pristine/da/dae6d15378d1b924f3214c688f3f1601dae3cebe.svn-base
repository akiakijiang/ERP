<?php

/**
 * 支付交易查看列表
 * 
 * @author yxiang@oukoo.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('cw_payment_transaction_list');
require_once(ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once(ROOT_PATH . 'includes/cls_page.php');

$act = isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('list', 'search')) ? $_REQUEST['act'] : 'list'; 
$page = is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) ? $_REQUEST['page'] : 1;


// 构造查询条件
$conditions = array();
if ($act == 'search')
{
	$conditions = $search = $_POST['search'];
}
else
{
	$conditions = $search = array
	(
		'payment_transaction_id' => $_GET['payment_transaction_id'],
		'order_sn'               => $_GET['order_sn'],
		'status'                 => $_GET['status'],
		'start'                  => $_GET['start'],
		'end'                    => $_GET['end'],
		'account_from'           => $_GET['account_from'],
	);	
}
_paytrans_list_conditions($conditions);
$extra_params = $search;
$smarty->assign('search', $search);
	
if(!empty($conditions['start']) && !empty($conditions['end'])) {
// 构造分页参数
$total = paytrans_get_count_by_conditions($conditions); // 总记录数
$page_size = 20;  // 每页数量
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$conditions['offset'] = ($page - 1) * $page_size;
$conditions['limit'] = $page_size;


// 获取列表数据
$list = paytrans_get_all_by_conditions($conditions);


// 分页
$pagination = new Pagination($total, $page_size, $page, 'page', $url = 'payment_transaction_list.php', null, $extra_params);
$smarty->assign('pagination', $pagination->get_simple_output());
}


$smarty->assign('list', $list);

$smarty->assign('paytrans_status_list', paytrans_status_list());
$smarty->display('oukooext/payment_transaction_list.htm');


/**
 * 为支付交易列表页构造查询条件
 * 
 * @param array $conditions 未加工的查询条件
 */
function _paytrans_list_conditions(& $conditions)
{
    // 订单号
    if (isset($conditions['order_sn']))  
    {
    	$order_sn = $GLOBALS['db']->escape_string($conditions['order_sn']);
    	if (!empty($order_sn))
            $conditions['order_id'] = $GLOBALS['db']->getOne("SELECT order_id FROM {$GLOBALS['ecs']->table('order_info')} WHERE order_sn = '{$order_sn}'");
    	unset($conditions['order_sn']);	
    }
    
    // 期末时间， 需要+1天查询
    if (isset($conditions['end']) && strtotime($conditions['end']) !== false)
    {
    	$conditions['end'] = date('Y-m-d', strtotime('+1 day', strtotime($conditions['end'])));
    }
    
    // 来源账户
    if (isset($conditions['account_from']) && !empty($conditions['account_from']))
    {
        do
        {
            $username = $GLOBALS['db']->escape_string($conditions['account_from']);
           
            $userId = $GLOBALS['db']->getOne
            ("
                SELECT userId FROM {$GLOBALS['ecs']->table('users')} WHERE user_realname = '{$username}' or user_name = '{$username}'
            ");
            if (!empty($userId))
            {
                $conditions['account_from'] = $userId;
                break;  
            }
            
            $userId = $GLOBALS['db']->getOne
            ("
                SELECT userId FROM {$GLOBALS['ecs']->table('order_info')} o LEFT JOIN {$GLOBALS['ecs']->table('users')} u ON u.user_id = o.user_id 
                WHERE o.consignee = '{$username}'
            ");
            if (!empty($userId))
            { 
                $conditions['account_from'] = $userId;
                break; 
            }
                
        } while (false);
    }
}
