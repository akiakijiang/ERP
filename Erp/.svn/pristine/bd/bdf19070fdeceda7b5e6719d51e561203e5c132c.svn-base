<?php

/**
 * 角色管理
 * 
 * @author yxiang@oukoo.com
 * @copyright 2010 ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
admin_priv('allot_priv');

// 请求
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('list', 'add', 'edit', 'delete')) 
    ? $_REQUEST['act'] 
    : NULL ;
    
// 消息
$message =
    isset($_REQUEST['message']) && trim($_REQUEST['message'])
    ? $_REQUEST['message']
    : false;
    
    
if ($message) {
	$smarty->assign('message', $message);
}


switch ($act) 
{
    // 角色列表
    case 'list' :   
        // 当前页码
        $page = 
            is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
            ? $_REQUEST['page'] 
            : 1 ;
    	$total = $db->getOne("SELECT count(*) FROM {$ecs->table('admin_role')}");
    	
        // 构造分页
        $page_size = 10;
        $total_page = ceil($total/$page_size);
        $page = min($page, $total_page);
        $page = max($page, 1);
        $offset = ($page - 1) * $page_size;
       
        $limit = $page_size;
        // 列表 
    	$list  = $db->getAll("SELECT * FROM {$ecs->table('admin_role')} LIMIT {$offset}, {$limit}");
        // 分页
        $pagination = new Pagination(
            $total, $page_size, $page, 'page', null, null, $filter
        );
    	$smarty->assign('list', $list);
        $smarty->assign('pagination', $pagination->get_simple_output());  // 分页
        $smarty->display('oukooext/role_list.htm');
        break;
        
    // 删除
    case 'delete' :
    	$id = is_numeric($_REQUEST['id']) ? $_REQUEST['id'] : 0 ;
    	$db->query("DELETE FROM {$ecs->table('admin_role')} WHERE role_id = '{$id}'");
    	header("Location: role.php?act=list&message=". urlencode('已删除'));
    	exit;
    	break;
    	
    // 编辑
    case 'edit' :
    	$id = is_numeric($_REQUEST['id']) ? $_REQUEST['id'] : 0 ;
    	
    	// 处理提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
            do {
            	$role = $_POST['role'];
            	Helper_Array::removeEmpty($role);
            	
                if (empty($role['role_name'])) {
                    $smarty->assign('message', '没有填写角色名');
                    break;
                }
                if (empty($role['action_list']) || !is_array($role['action_list'])) {
                    //允许新建没有任何权限的角色，by cywang
                	//$smarty->assign('message', '没有选择权限');
                    //break;
                	$action_list = '';
                }
                else 
                {
                	$action_list = implode(',', array_map('trim', $role['action_list']));
                }
            	
                if ($id) {
                    $sql = "UPDATE {$ecs->table('admin_role')} SET role_name = '". $db->escape_string($role['role_name'])."', description = '". $db->escape_string($role['description'])."', action_list = '{$action_list}' WHERE role_id = '{$id}'";
                    $db->query($sql);
                    $smarty->assign('message', '更新成功');
                }
                else {
                    $sql = "INSERT INTO {$ecs->table('admin_role')} (role_name, description, action_list) VALUES ('". $db->escape_string($role['role_name'])."', '". $db->escape_string($role['description'])."', '{$action_list}')";
                    $db->query($sql);
                    header("Location: role.php?act=list&message=". urlencode('添加成功'));
                    exit;
                }
            } while (false);
        }
    	
    	if ($id) {
            $role = $db->getRow("SELECT * FROM {$ecs->table('admin_role')} WHERE role_id = '{$id}'");
            if ($role) {
                $role['action_list'] = Helper_Array::normalize($role['action_list']);
                $smarty->assign('role', $role);
            }
            else {
                $smarty->assign('message', '找不到该角色');
            }
        }
    	
    	// 权限列表
    	include_once('../languages/' .$_CFG['lang']. '/admin/priv_action.php');
        $sql = "SELECT action_id, parent_id, action_code FROM " .$ecs->table('admin_action'). " WHERE is_shield = 0 ";
        $action_list = $db->getAll($sql);
        if ($role && !empty($role['action_list'])) {
            $role_priv_list = array_flip($role['action_list']);
            foreach ($action_list as $key => $priv) {
                $action_list[$key]['checked'] = isset($role_priv_list[$priv['action_code']]) ? true : false ;
            }
        }
        $action_group_list = Helper_Array::toTree($action_list, 'action_id', 'parent_id', 'priv_list');
        
        $smarty->assign('action_group_list', $action_group_list);
        $smarty->assign('lang', $_LANG);
    	$smarty->display('oukooext/role_edit.htm');
    	break;
    	
}