<?php
/**
 * 用于显示每个权限有多少人能看到
 *
 */
define('IN_ECS', true);

require('includes/init.php');
include_once('../languages/' .$_CFG['lang']. '/admin/priv_action.php');

admin_priv('admin_manage');

// 获取用户列表
$sql = "SELECT * FROM {$ecs->table('admin_user')}";
$admin_user_list = $db->getAll($sql);

foreach ($admin_user_list as $key => $admin_user) {
	$admin_user_list[$key]['action_list_array'] = explode(',', $admin_user['action_list']);
}

// 添加特殊权限（all）
$super_priv = array(
	'action_id' => -1,
	'parent_id' => 0,
	'action_code' => 'special',
	'is_shield' => 0,
);

$all_priv = array(
	'action_id' => -2,
	'parent_id' => -1,
	'action_code' => 'all',
	'is_shield' => 0,
);
$super_priv['children'][] = $all_priv;
$priv_list[] = $super_priv;

// 获取权限权限列表
$sql = "SELECT * FROM {$ecs->table('admin_action')} WHERE parent_id = 0";
$priv_list = array_merge($priv_list, $db->getAll($sql));

foreach ($priv_list as $key => $priv) {
	
	if ($priv['action_id'] > 0) {	// 过滤额外添加的权限
		// 获取子权限
		$sql = "SELECT * FROM {$ecs->table('admin_action')} WHERE parent_id = {$priv['action_id']}";
		$priv_list[$key]['children'] = $db->getAll($sql);
	}
	
	// 获取子权限的用户
	foreach ($priv_list[$key]['children'] as $child_key => $child_priv) {
		$user_list = array();
		foreach ($admin_user_list as $user) {
			if (in_array($child_priv['action_code'], $user['action_list_array'])) {
				$user_list[] = "{$user['user_name']}({$user['real_name']})";
			}
		}
		$priv_list[$key]['children'][$child_key]['user_list'] = $user_list;
	}
	
	// 获取父权限用户
	$user_list = array();
	foreach ($admin_user_list as $user) {
		if (in_array($priv['action_code'], $user['action_list_array'])) {
			$user_list[] = $user['user_name'];
		}
	}
	$priv_list[$key]['user_list'] = $user_list;
}

//pp($priv_list);

$smarty->assign('lang', $_LANG);
$smarty->assign('priv_list', $priv_list);
$smarty->display('oukooext/admin_priv_list.htm');
?>