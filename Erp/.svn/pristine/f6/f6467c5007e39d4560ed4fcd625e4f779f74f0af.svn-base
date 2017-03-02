<?php die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
/**
 * 红包发送
 * 
 * @author last modified by yxiang 2009/05/22
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('includes/lib_bonus.php');
require_once('includes/lib_common.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
admin_priv('bonus_manage');
require("function.php");

$act = $_POST['act'];
$page = is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) ? $_REQUEST['page'] : 1;

$admin_uuid = $db->getOne("SELECT userId FROM {$ecs->table('users')} WHERE user_name = '{$_SESSION['admin_name']}'");
if ($admin_uuid == '')
{
	die("请使用单点登陆的ID登陆！");
}

// party列表
$party_list = array(PARTY_OUKU_MOBILE => '欧酷手机业务组', PARTY_OUKU_SHOES => '欧酷鞋子业务组');

/*
 * action
 */
if ($act == '发送给用户')
{
	$users = gt_user_check($_POST['user_names']);
	
	$gt_count  = intval($_POST['gt_count']);  // 要发送的抵用券数量 (每人)
	$gtc_id    = intval($_POST['gtc_id']);    // 抵用券配置
	$gtc_value = intval($_POST['gtc_value']); // 抵用券抵用金额
	$comment   = strval($_POST['comment']);   // 领取原因
	
	do
	{
		if ($gtc_id < 0 || $gt_count < 0)
		{
			$info = "请选择红包配置，并输入发送数量";
			break;
		}
		
		if (trim($comment) == '')
		{
			$info = "备注不能为空";
			break;
		}
		
		if (!empty($users['inexistent']))
		{
			$info = '用户('. gt_join($users['inexistent']) . ')不存在，不能发送';
			break;
		}
		
		if (empty($users['exists']))
		{
			$info = '请输入用户名';	
			break;
		}
		
		if (count($users['exists']) * $gt_count > get_gt_count($gtc_id))
		{
			$info = "没有足够数量的抵用券";
			break;
		}

		// 开始发送
		foreach ($users['exists'] as $user) 
		{
			// 发送抵用券
			$gt_codes = get_gt_code($gtc_id, $gt_count, $comment);
			if ($gt_codes == null) 
			{
				$info = "没有足够的抵用券发送给{$user['user_name']}了";
				break;
			}
			
			foreach ($gt_codes as $gt_code)
			{
				give_gt_to_user($gt_code, $user['user_name']);
			}
			$info = "发配成功!";
			
			$user_mobile = $db->getOne("SELECT o.mobile FROM {$ecs->table('order_info')} o INNER JOIN {$ecs->table('users')} u ON o.user_id = u.user_id WHERE u.user_name = '{$user['user_name']}' ORDER BY o.order_id DESC LIMIT 1");
			if (!$user_mobile)
			{
				$user_mobile = $db->getOne("SELECT user_mobile FROM {$ecs->table('users')} WHERE user_name = '{$user['user_name']}'");
			} 
			
			if ($user_mobile)
			{
			    $party_id = $db->getOne("SELECT party_id FROM membership.ok_gift_ticket_config WHERE gtc_id = '{$gtc_id}' LIMIT 1");
				$msg_vars = array('msg_gtc_value' => $gtc_value);
				# 短信事件 20090822
				erp_send_message('gt_send', $msg_vars, $party_id, NULL, $user_mobile);
			}
		}
	}
	while (false);
} 

elseif ($act == '导出抵用券列表CSV') 
{
	admin_priv("admin_other_csv");
	$gt_count = intval($_POST['gt_count']);
	$gtc_id   = intval($_POST['gtc_id']);
	$comment  = strval($_POST['comment']);
	
	if ($gtc_id < 0 || $gt_count < 0) 
	{
		$info = '请选择红包配置并数据要发送的红包数量';
	}
	elseif (trim($comment) == '')
	{
		$info = '请输入备注';
	}
	else
	{
		// 导出抵用券
		$gt_codes = get_gt_code($gtc_id, $gt_count, $comment);
		$smarty->assign('gt_codes', $gt_codes);
		$smarty->assign('info', $info);
		header("Content-type:application/vnd.ms-excel");
		header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","{$gt_count}个配置ID为{$gtc_id}的抵用券编码") . ".csv");	
		$out = $smarty->fetch('oukooext/gt_list_csv.htm');
		echo iconv("UTF-8","GB18030", $out);
		exit();			
	}
}


// 查询条件
$conditions = '';
if ( isset($_GET['code']) && trim($_GET['code']) ) {           // 配置编号
    $conditions .= ' AND c.gtc_id = "'. $_GET['code'] .'"';    
    $filter['code'] = $_GET['code'];
}
if ( isset($_GET['value']) && is_numeric($_GET['value']) ) {   // 红包金额
    $conditions .= ' AND c.gtc_value = "'. $_GET['value'] .'"';  
    $filter['value'] = $_GET['value'];
}
if ( isset($_GET['type']) && trim($_GET['type']) ) {           // 红包类型
    $conditions .= ' AND c.gtc_type_id = "'. $_GET['type'] .'"';
    $filter['type'] = $_GET['type'];
}
if ( isset($_GET['party_id']) && $_GET['party_id'] > 0 ) {     // 启动网站
    $conditions .= ' AND c.party_id = '. (int)$_GET['party_id'];
    $filter['party_id'] = $_GET['party_id'];    
}

/*
 * view
 */

// 构造分页参数
$sql = "
    SELECT count(gtc_id) 
    FROM `membership`.`ok_gift_ticket_config` AS c
    WHERE 
    	site_id = 1 AND 
		(gtc_state = 3 OR (gtc_state = 2 AND UNIX_TIMESTAMP(NOW()) <= gtc_etime AND UNIX_TIMESTAMP(NOW()) > gtc_stime))
		{$conditions}
";
$total = $db->getOne($sql); // 总记录数
$page_size = 20;  // 每页数量
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size;

// 查询未过的抵用券
$sql = "
	SELECT 
	    c.*, t.type_name, t.refer_id_required 
    FROM 
        `membership`.`ok_gift_ticket_config` c 
        LEFT JOIN `membership`.`ok_gift_ticket_config_type` t ON t.gtc_type_id = c.gtc_type_id
	WHERE 
	    site_id = 1 AND 
	    (gtc_state = 3 OR (gtc_state = 2 AND UNIX_TIMESTAMP(NOW()) <= gtc_etime AND UNIX_TIMESTAMP(NOW()) > gtc_stime))
	    {$conditions}
	ORDER BY c.gtc_id DESC, c.gtc_etime LIMIT {$offset}, {$limit}
";
$gtcs = $db->getAllRefby($sql, array('gtc_id'), $fields_value, $ref_tmp, false);

// 查询每种抵用券还未领取的抵用券数量
if (!empty($gtcs))
{
    $sql = "
        SELECT count(gt_id) AS gt_count, gtc_id 
        FROM membership.ok_gift_ticket 
        WHERE user_id = '' AND give_user = '' AND " . db_create_in($fields_value['gtc_id'], 'gtc_id')." 
        GROUP BY gtc_id 
    ";
    $db->getAllRefby($sql, array('gtc_id'), $fileds_tmp, $ref, true);
    foreach ($gtcs as $k => $v)
    {
        $gtcs[$k]['gt_count'] = $ref['gtc_id'][$v['gtc_id']][0]['gt_count'];   
    }
}

// 分页
$pagination = new Pagination($total, $page_size, $page, 'page', $url = 'gt_give.php', null, $filter);

$smarty->assign('filter', $filter);
$smarty->assign('party_list', $party_list);
$smarty->assign('gtcs', $gtcs);
$smarty->assign('info', $info);
$smarty->assign('all_gtc_state', $_CFG['ms']['gtc_state']);  // 红包使用状态的mapping
$smarty->assign('pagination', $pagination->get_simple_output());
$smarty->assign('bonus_type_list', bonus_type_list());
$smarty->display('oukooext/gt_give.htm');



/**
 * 判断用户是否存在
 * 
 * @param mixed  $users 
 * 
 * @return array   返回存在的用户和不存在的用户
 */
function gt_user_check($users)
{
	global $db, $ecs;
	
	$exists = array();
	$inexistent = array();
 
 	if (!empty($users) && !is_array($users))
 	{
		$users = preg_split('/[\s,]+/', $users);
		$users = array_filter(array_map('trim', $users), 'strlen');	
 	}
 	
 	if (is_array($users) && count($users) > 0)
 	{
 		$users = array_unique($users); // 移除重复的用户名
 		$sql = "SELECT `userId`, `user_name` FROM {$ecs->table('users')} WHERE `user_name` IN (%s)";
 		
 		// 30个用户名一组构造IN查询，避免SQL过长引发的问题
 		$group = array_chunk($users, 30);
		foreach ($group as $in)
		{
			$exclude = $in;
			$result = $db->getAll(sprintf($sql, gt_join($in)));
			if ($result)
			{
				// 所有用户都存在
				if (count($in) == count($result))
				{
					$exists = array_merge($exists, $result);		
				}
				// 有部分用户不存在，则循环匹配
				else
				{
					foreach ($result as $u)
					{
						// 用户存在
						if (in_array($u['user_name'], $in))
						{
							$exists[] = $u;
							unset($exclude[array_search($u['user_name'], $exclude)]);
						}
					}
					// 剩下的是不存在的用户
					if (count($exclude) > 0)
					{
						$inexistent = array_merge($inexistent, $exclude);	
					}
				}
			}
			else
			{
				$inexistent = array_merge($inexistent, $in);	
			}
			unset($result, $exclude);
		} 
 	}
	
	return compact('exists', 'inexistent');	
}

/**
 * 用于构造SQL查询的IN部分
 */
function gt_join($array)
{
	$result = '';
	
	$count = count($array);
	$end = $count - 1;
	for ($i = 0; $i < $count; $i++)
	{
 		$result .=  $i == $end ? "'{$array[$i]}'" : "'{$array[$i]}'," ;	
	}
	
	return $result;
}

