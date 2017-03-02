<?php die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
define('IN_ECS', true);
require('includes/init.php');
admin_priv('bonus_manage');
require("function.php");

$csv = $_REQUEST['csv'];

$size = 20;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

if ($csv == null) {
	$limit = "LIMIT $size";
	$offset = "OFFSET $start";
}

$give_time = $_REQUEST['give_time'];
$sql = "
	SELECT gt.*, gtl.gtl_utime FROM membership.ok_gift_ticket gt
	LEFT JOIN membership.ok_gift_ticket_log gtl ON gt.gt_id = gtl.gt_id
	WHERE give_time = '$give_time'
";
$gts = $db->getAllRefby($sql, array('refer_id', 'gt_creator'), $fields_value, $ref_tmp);
if ($gts)
{
    // È¡µÃºì°ü¹ØÁª¶©µ¥ÐÅÏ¢
    if (is_array($fields_value['refer_id']) && trim(reset($fields_value['refer_id'])) != '' )
    {
        $sql = "SELECT order_id, order_sn FROM {$ecs->table('order_info')} WHERE " . db_create_in($fields_value['refer_id'], 'order_id');
        $db->getAllRefby($sql, array('order_id'), $field_tmp1, $ref_orders);   
    }
    
    // È¡µÃºì°ü´´½¨ÈËÐÅÏ¢
    if (is_array($fields_value['gt_creator']) && trim(reset($fields_value['gt_creator'])) != '')
    {
        $sql = "SELECT userId, user_name FROM {$ecs->table('users')} WHERE " . db_create_in($fields_value['gt_creator'], 'userId');
        $db->getAllRefby($sql, array('userId'), $field_tmp2, $ref_users);   
    }
    
    // ×é×°Êý¾Ý
    foreach ($gts as $k => $v)
    {
        $gts[$k]['order_info'] = $ref_orders['order_id'][$v['refer_id']][0];
        if ($ref_users['userId'][$v['gt_creator']][0])
        {
            $gts[$k]['gt_creator'] = $ref_users['userId'][$v['gt_creator']][0];   
        }
    }   
}

$sqlc = "SELECT COUNT(*) FROM membership.ok_gift_ticket WHERE give_time = '$give_time'";
$count = $db->getOne($sqlc);

$pager = Pager($count, $size, $page);
$smarty->assign('pager', $pager);
$smarty->assign('gts', $gts);
$smarty->display('oukooext/gt_give_history_detail.htm');

?>