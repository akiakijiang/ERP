<?php

/**
 * 销售订单录入
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com
 */

define('IN_ECS', true);

require_once(dirname(__FILE__) . '/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 

    
$act = isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('login')) 
    ? $_REQUEST['act'] 
    : null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
	$account = $_POST['account'];
	if (!empty($account['name']) && !empty($account['password'])) {
		do {
			$user = $db->getRow("SELECT distributor_id, party_id, name, password FROM distributor WHERE name = '{$account['name']}' ");
            if (!$user) {
                $smarty->assign('message', '错误的用户名或密码');
                break; 
            }
            if (md5($account['password']) != $user['password']) {
                $smarty->assign('message', '错误的用户名或密码');
                break;
            }

            // 成功，注册session
            $_SESSION['distributor_id']   = $user['distributor_id'];
            $_SESSION['party_id']         = $user['party_id'];
            $_SESSION['distributor_name'] = $user['name'];
            $_SESSION['distributor_pass'] = $user['password'];

            header("Location: salesOrderEntry.php");
            exit;
		} while (false);
	}
	else {
		$smarty->assign('message', '请输入用户名和密码');
	}
}

    
$smarty->display('api/login.htm');
