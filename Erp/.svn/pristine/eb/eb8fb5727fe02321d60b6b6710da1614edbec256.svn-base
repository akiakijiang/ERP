<?php die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");

/**
 * 与订单关联的红包发送
 * 
 * @author  yxiang@ouku.com  2009/07/09
 */
 
define('IN_ECS', true);
require_once('includes/init.php');
require_once('includes/lib_bonus.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
admin_priv('bonus_manage');
require("function.php");

$act = $_POST['act'];
$page = is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) ? $_REQUEST['page'] : 1;
$gtc_id = is_numeric($_REQUEST['gtc_id']) ? $_REQUEST['gtc_id'] : 0 ; 

// 需要保存发送人的32位id,所以需要单点登录
$admin_uuid = $db->getOne("SELECT userId FROM {$ecs->table('users')} WHERE user_name = '{$_SESSION['admin_name']}'");
if ($admin_uuid == '')
{
	die("请使用单点登陆的ID登陆！");
}

// 查找抵用券配置
$sql = "
    SELECT c.*, t.type_name FROM `membership`.`ok_gift_ticket_config` c 
    LEFT JOIN `membership`.`ok_gift_ticket_config_type` t ON t.gtc_type_id = c.gtc_type_id
    WHERE site_id = 1 AND gtc_id = '{$gtc_id}'
";
$gtc = $db->getRow($sql);
if (!$gtc)
{
    die("抵用券不存在");  
}

// 退款申请审核动作处理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && is_array($_POST['gt']))
{    
    $success = true;
    
    foreach ($_POST['gt'] as $id => $item)
    {
        if (isset($item['checked']) && $item['checked'] == 1)  // 发送选中的
        {
            $row = array('give_user' => $admin_uuid, 'give_time' => time());
            if (!empty($item['give_comment']))
            {
                $row['give_comment'] = $item['give_comment'];
            }
            
            // 更新抵用券的发送人，发送时间和发送备注
            if ($db->autoExecute('`membership`.`ok_gift_ticket`', $row, 'UPDATE', "gt_id = '{$id}'"))
            {
                // 发送抵用券给用户
                if (!give_gt_to_user($item['gt_code'], $item['user_name'])) { $success = false; }
            }   
        }
    }
}


// 查询未领取,未过期的抵用券
$sql = "
    SELECT g.*, t.*, o.order_sn, o.consignee, u.user_id, u.userId, u.user_name
    FROM `membership`.`ok_gift_ticket` g
    LEFT JOIN `membership`.`ok_gift_ticket_config_type` t ON t.gtc_type_id = g.gtc_type_id
    LEFT JOIN {$ecs->table('order_info')} o ON o.order_id = g.refer_id
    LEFT JOIN {$ecs->table('users')} u ON u.user_id = o.user_id
    WHERE (g.gt_state = 3 OR (g.gt_state = 2 AND UNIX_TIMESTAMP(NOW()) <= g.gtc_etime AND UNIX_TIMESTAMP(NOW()) > g.gtc_stime)) AND g.user_id = '' AND g.give_user = '' AND g.gtc_id = '{$gtc['gtc_id']}'
";
$gts = $db->getAll($sql);

$smarty->assign('gtc', $gtc);
$smarty->assign('gts', $gts);
$smarty->assign('gt_state_list', $_CFG['ms']['gt_state']);  // 红包类型的mapping
$smarty->display('oukooext/gt_give_order.htm');

