<?php die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
/**
 * 查询用户的抵用券
 *
 */
define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('bonus_manage');
require_once("function.php");
require_once('includes/lib_bonus.php');
require_once(ROOT_PATH . 'includes/cls_page.php');

// 分页
$page = 
    isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) ? max(1, $_REQUEST['page']) : 1;
// 用户名
$user =
    isset($_REQUEST['user']) ? trim($_REQUEST['user']) : false ;

$filter = array('user' => $user);


if ($user) {
    $userId = $db->getOne("
        SELECT userId FROM {$ecs->table('users')} WHERE user_name = '". $db->escape_string($user) ."'"
    );
}

if ($userId) {
    // 总数
    $total = $db->getOne("
        SELECT COUNT(*) FROM membership.ok_gift_ticket WHERE user_id = '{$userId}'
	");
    
    // 构造分页
    $page_size = 15;
    $total_page = ceil($total/$page_size);  // 总页数
    if ($page > $total_page) $page = $total_page;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $page_size;
    $limit = $page_size; 
    
    // 查询用户拥有的红包
    $gts = $db->getAll("
        SELECT * FROM membership.ok_gift_ticket WHERE user_id = '{$userId}' LIMIT $offset, $limit 
	");
    
    if ($gts) {
        // 红包类型列表
        $bonus_type_list = bonus_type_list();
        
        foreach ($gts as & $gt) {
            // 取得红包关联订单的信息
            if ($gt['refer_id'] > 0) {
                $sql = "SELECT order_id, order_sn FROM {$ecs->table('order_info')} WHERE order_id = {$gt['refer_id']}";
                $gt['refer_order'] = $db->getRow($sql, true);
            }
            
            // 取得红包类型名
            $gt['type'] = $bonus_type_list[$gt['gtc_type_id']];
            
            // 取得红包发送人的信息
            if ($gt['give_user']) {
                $gt['give_user'] = $db->getOne("
                    SELECT user_name FROM {$ecs->table('users')} WHERE userId = '{$gt['give_user']}' LIMIT 1 
				");
            }
        }
    }
    
    // 构造分页
    $pagination = new Pagination(
        $total, $page_size, $page, 'page', 'gt_give_history_byuser.php', null, $filter
    );
    
    $smarty->assign('pagination', $pagination->get_simple_output());  // 分页
}


$smarty->assign('gts', $gts);
$smarty->assign('filter', $filter);
$smarty->display( 'oukooext/gt_give_history_byuser.htm');

?>