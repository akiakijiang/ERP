<?php die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
/**
 * 抵用券领取记录
 */
define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('bonus_manage');
require_once("function.php");
require_once('includes/lib_bonus.php');
require_once(ROOT_PATH . 'includes/cls_page.php');


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
// 期初时间  (默认是当天的)
$start = 
    isset($_REQUEST['start']) && strtotime($_REQUEST['start']) > 0
    ? $_REQUEST['start']
    : null ;
// 期末时间
$end = 
    isset($_REQUEST['end']) && strtotime($_REQUEST['end']) > 0
    ? $_REQUEST['end']
    : null ;
// 查询时间段
$time_field = 
    isset($_REQUEST['time_field']) && strtotime($_REQUEST['time_field']) > 0
    ? $_REQUEST['time_field']
    : 'give_time' ;
// 查询用户
$user = 
    isset($_REQUEST['user']) && trim($_REQUEST['user'])
    ? $_REQUEST['user']
    : null ;
// 状态
$status =
    isset($_REQUEST['status']) && trim($_REQUEST['status'])
    ? $_REQUEST['status']
    : null ; 

// 过滤条件
$filter = array(
    'start' => $start, 'end' => $end, 'time_field' => $time_field,
    'size' => $page_size, 'user' => $user, 'status' => $status, 'act' => 'search'
);

if (isset($_REQUEST['act']) && $_REQUEST['act'] == 'search') {
    $condition = getCondition($filter);
    
    // 总数
    $total = $db->getOne("
        SELECT 
            count(t.gt_id)
        FROM 
            membership.ok_gift_ticket t
        WHERE 
            t.give_user != '' {$condition} 
    ");
    
    // 构造分页
    $total_page = ceil($total/$page_size);  // 总页数
    if ($page > $total_page) $page = $total_page;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $page_size;
    $limit = $page_size; 
    
    // 列表
    $sql = "
        SELECT 
            t.`gtc_id`, t.gt_code, t.`gtc_value`, t.`gt_state`, t.`gtc_stime`, t.`gtc_etime`, t.`give_user`, t.`give_time`,
            t.gtc_type_id, t.used_order_id, u.user_name
        FROM 
            membership.ok_gift_ticket t
            LEFT JOIN ecshop.ecs_users u ON u.userId = t.user_id
        WHERE 
            t.give_user != '' {$condition} 
        ORDER BY t.user_id, t.give_time DESC LIMIT {$offset}, {$limit}
    ";
    $gts = $db->getAll($sql);
    
    // 构造分页
    $pagination = new Pagination(
        $total, $page_size, $page, 'page', $url = 'gt_give_history.php', null, $filter
    );
    
    $smarty->assign('pagination', $pagination->get_simple_output());  // 分页
}


// 查询每页显示数列表
$page_size_list = array('20' => '20', '50' => '50', '100' => '100', '65535' => '不分页');

$smarty->assign('filter', $filter);
$smarty->assign('bonus_type_list', bonus_type_list());  // 红包类型
$smarty->assign('bonus_status_list', $_CFG['ms']['gt_state']);  // 红包状态
$smarty->assign('gts', $gts);
$smarty->assign('page_size_list', $page_size_list);  // 每页显示数列表
$smarty->display('oukooext/gt_give_history.htm');

/**
 * 构造查询条件
 * 
 * @param $filter
 */
function getCondition(& $filter) 
{
    global $db, $ecs;
    
    $cond = '';
    
    // 用户限制
    if (!empty($filter['user'])) {
        $result = preg_match_all("/[^,^\s]+/", $filter['user'], $matches);
        $users = ($result > 0) ? $matches[0] : array() ;
        if (!empty($users) && is_array($users)) {
            $userId = $db->getCol("SELECT userId FROM {$ecs->table('users')} WHERE user_name ". db_create_in($users));
            if ($userId) {
                $cond .= ' AND t.user_id '. db_create_in($userId);
            } else {
                $cond .= " AND t.user_id = '' ";  // 用户为空
            }
            
            $filter['user'] = implode(',', $users);
        }
    }
    
    // 时间限制
    if ($filter['start']) {
        $cond .= " AND t.{$filter['time_field']} > UNIX_TIMESTAMP('{$filter['start']}')";
    }
    
    if ($filter['end']) {
        list($_y, $_m, $_d) = explode('-', date('Y-m-d', strtotime($filter['end'])) );
        $_end = mktime(23, 59, 0, $_m, $_d, $_y);
        $cond .= " AND t.{$filter['time_field']} < $_end";
    }
    
    // 状态限制
    if ($filter['status']) {
        $cond .= " AND t.gt_state = '{$filter['status']}'" ;
    }
    
    return $cond;
}

?>