<?php

/**
 * ECSHOP 支付方式管理程序
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     Weber Liu <weberliu@hotmail.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: weberliu $
 * $Date: 2007-05-15 16:52:17 +0800 (星期二, 15 五月 2007) $
 * $Id: payment.php 8620 2007-05-15 08:52:17Z weberliu $
*/

define('IN_ECS', true);

require('includes/init.php');

$exc = new exchange($ecs->table('payment'), $db, 'pay_id', 'pay_name');

/*------------------------------------------------------ */
//-- 支付方式列表 ?act=list
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{
    /* 查询数据库中启用的支付方式 */
//    $pay_list = array();
//    $sql = "SELECT * FROM " . $ecs->table('payment') . " WHERE enabled = '1' ORDER BY pay_order";
//    $res = $db->query($sql);
//    while ($row = $db->fetchRow($res))
//    {
//        $pay_list[$row['pay_code']] = $row;
//    }

    /* 取得插件文件中的支付方式 */
    $modules = read_modules('../includes/modules/payment');

//	for ($i = 0; $i < count($modules); $i++)
//    {
//        $code = $modules[$i]['code'];
//
//        /* 如果数据库中有，取数据库中的名称和描述 */
//        if (isset($pay_list[$code]))
//        {
//            $modules[$i]['name'] = $pay_list[$code]['pay_name'];
//            $modules[$i]['pay_fee'] =  $pay_list[$code]['pay_fee'];
//            $modules[$i]['is_cod'] = $pay_list[$code]['is_cod'];
//            $modules[$i]['desc'] = $pay_list[$code]['pay_desc'];
//            $modules[$i]['pay_order'] = $pay_list[$code]['pay_order'];
//            $modules[$i]['install'] = '1';
//        }
//        else
//        {
//            $modules[$i]['name'] = $_LANG[$modules[$i]['code']];
//            if (!isset($modules[$i]['pay_fee']))
//            {
//                $modules[$i]['pay_fee'] = 0;
//            }
//            $modules[$i]['desc'] = $_LANG[$modules[$i]['desc']];
//            $modules[$i]['install'] = '0';
//        }
//    }
    usort($modules, payment_sort);
    assign_query_info();
    foreach($modules as $key => $module){
    	$pay_configs = unserialize($module['pay_config']);
    	$modules[$key]['bank_account'] = $pay_configs['bank_account'];
    	$modules[$key]['bank_name'] = $pay_configs['bank_name'];
    	$modules[$key]['bank_people'] = $pay_configs['bank_people'];
    	$modules[$key]['service_phone'] = $pay_configs['service_phone'];
    }
    $smarty->assign('ur_here', $_LANG['03_payment_list']);
    $smarty->assign('modules', $modules);
    $smarty->display('payment_list.htm');
}

/*------------------------------------------------------ */
//-- 安装支付方式 ?act=install&code=".$code."
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'install')
{
    admin_priv('payment');

    /* 取相应插件信息 */
//    $set_modules = true;
//    include_once(ROOT_PATH.'includes/modules/payment/' . $_REQUEST['code'] . '.php');
//
//    $data = $modules[0];
//    /* 对支付费用判断。如果data['pay_fee']为false无支付费用，为空则说明以配送有关，其它可以修改 */
//    if (isset($data['pay_fee']))
//    {
//        $data['pay_fee'] = trim($data['pay_fee']);
//    }
//    else
//    {
//        $data['pay_fee']     = 0;
//    }
//
//    $pay['pay_code']    = $data['code'];
//    $pay['pay_name']    = $_LANG[$data['code']];
//    $pay['pay_desc']    = $_LANG[$data['desc']];
//    $pay['is_cod']      = $data['is_cod'];
//    $pay['pay_fee']     = $data['pay_fee'];
//    $pay['pay_config']  = array();
//
//    foreach ($data['config'] AS $key => $value)
//    {
//        $config_desc = (isset($_LANG[$value['name'] . '_desc'])) ? $_LANG[$value['name'] . '_desc'] : '';
//        $pay['pay_config'][$key] = $value +
//            array('label' => $_LANG[$value['name']], 'value' => $value['value'], 'desc' => $config_desc);
//
//        if ($pay['pay_config'][$key]['type'] == 'select' ||
//            $pay['pay_config'][$key]['type'] == 'radiobox')
//        {
//            $pay['pay_config'][$key]['range'] = $_LANG[$pay['pay_config'][$key]['name'] . '_range'];
//        }
//    }
	$pay_id = $_REQUEST['id'];
	$sql = "UPDATE {$ecs->table('payment')} SET enabled = 1 WHERE pay_id = '{$pay_id}'";
	$db->query($sql);
	
	assign_query_info();
    $link[] = array('text' => $_LANG['back_list'], 'href' => 'payment.php?act=list');
    sys_msg($_LANG['install_ok'], 0, $link);
    
//    $smarty->assign('pay', $pay);
//    $smarty->display('payment_edit.htm');
}

/*------------------------------------------------------ */
//-- 编辑支付方式 ?act=edit&code={$code}
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    admin_priv('payment');

    /* 查询该支付方式内容 */
    $pay_id = intval($_REQUEST['id']);
    
    if ($pay_id <= 0)
    {
   		die('invalid parameter');
    }


    $sql = "SELECT * FROM {$ecs->table('payment')} WHERE pay_id = '{$pay_id}'";
    $pay = $db->getRow($sql);
    if (empty($pay))
    {
        $links[] = array('text' => $_LANG['back_list'], 'href' => 'payment.php?act=list');
        sys_msg($_LANG['payment_not_available'], 0, $links);
    }

    /* 取相应插件信息 */
    $set_modules = true;
    include_once(ROOT_PATH . "includes/modules/payment/{$pay['pay_code']}.php");
    $data = $modules[0];

    /* 取得配置信息 */
    if (is_string($pay['pay_config']))
    {
        $pay['pay_config'] = unserialize($pay['pay_config']);
        foreach ($pay['pay_config'] AS $key => $value)
        {
            if (is_array($value)) {
            	if (strpos($value['name'], "_key") !== false && !check_admin_priv("payment_key")){
            		unset($pay['pay_config'][$key]);
            	} else {
                    $pay['pay_config'][$key]['label'] = $_LANG[$value['name']];
                    $pay['pay_config'][$key]['desc'] = (isset($_LANG[$value['name'] . '_desc'])) ?
                        $_LANG[$value['name'] . '_desc'] : '';
                    if ($pay['pay_config'][$key]['type'] == 'select' ||
                        $pay['pay_config'][$key]['type'] == 'radiobox') {
                        $pay['pay_config'][$key]['range'] = 
                            $_LANG[$pay['pay_config'][$key]['name'] . '_range'];
                    }
            	}
            }
        }
    }

    /* 对支付费用判断。如果data['pay_fee']为false无支付费用，为空则说明以配送有关，其它可以修改 */
    if (isset($data['pay_fee']))
    {
        if ($data['pay_fee'] === false)
        {
            $pay['pay_fee_ctl'] = -1;
        }
        elseif (strlen($data['pay_fee']) == 0)
        {
            $pay['pay_fee_ctl'] = 0;
        }
        else
        {
            $pay ['pay_fee_ctl'] = 1;
        }
    }
    else
    {
        $pay ['pay_fee_ctl'] = 1;
    }

    assign_query_info();

    $smarty->assign('ur_here', $_LANG['edit'] . $_LANG['payment']);
    $smarty->assign('pay', $pay);
    $smarty->display('payment_edit.htm');
}

/*------------------------------------------------------ */
//-- 提交支付方式 post
/*------------------------------------------------------ */
elseif (isset($_POST['Submit']))
{
    admin_priv('payment');

    /* 检查输入 */
    if (empty($_POST['pay_name']))
    {
        sys_msg($_LANG['payment_name'] . $_LANG['empty']);
    }

    $sql = "SELECT COUNT(*) FROM " . $ecs->table('payment') .
            " WHERE pay_name = '$_POST[pay_name]' AND pay_code <> '$_POST[pay_code]'";
    if ($db->getOne($sql) > 0)
    {
        sys_msg($_LANG['payment_name'] . $_LANG['repeat'], 1);
    }

    /* 取得配置信息 */
    $pay_config = array();
    $flag_edit_key = false;
    if (isset($_POST['cfg_value']) && is_array($_POST['cfg_value']))
    {
        for ($i = 0; $i < count($_POST['cfg_value']); $i++)
        {
            $pay_config[] = array('name'  => trim($_POST['cfg_name'][$i]),
                                  'type'  => trim($_POST['cfg_type'][$i]),
                                  'value' => trim($_POST['cfg_value'][$i])
            );
            if (strpos($_POST['cfg_name'][$i], "_key") !== false) {
            	$flag_edit_key = true;
            }
        }
    }
    if ($_POST['pay_id']) {
    	$sql = " SELECT pay_config FROM {$ecs->table('payment')} WHERE pay_id = '{$_POST['pay_id']}' ";
    	$key_config = $db->getOne($sql);
    	$key_config = unserialize($key_config);
    	foreach ($key_config AS $key => $value) {
    	    if (!is_array($value) && !isset($pay_config[$key])) {
    	        $pay_config[$key] = $key_config[$key];
    	    }
    	}
    	if (!$flag_edit_key) {
    		foreach ($key_config AS $key => $value) {
		  		if (strpos($value['name'], "_key") !== false) {
		  			$pay_config[] = $value;
		  			break;
		  		}
		  	}
    	}
    }
    $pay_config = serialize($pay_config);
    /* 取得和验证支付手续费 */
    $pay_fee    = empty($_POST['pay_fee'])?0:$_POST['pay_fee'];

    /* 检查是编辑还是安装 */
    $link[] = array('text' => $_LANG['back_list'], 'href' => 'payment.php?act=list');
    if ($_POST['pay_id'])
    {
        /* 编辑 */
        $sql = "UPDATE " . $ecs->table('payment') .
               "SET pay_name = '$_POST[pay_name]'," .
               "    pay_desc = '$_POST[pay_desc]'," .
               "    pay_config = '$pay_config', " .
               "    pay_fee    =  '$pay_fee' ".
               "WHERE pay_id = '$_POST[pay_id]' LIMIT 1";
        $db->query($sql);

        /* 记录日志 */
        admin_log($_POST['pay_name'], 'edit', 'payment');

        sys_msg($_LANG['edit_ok'], 0, $link);
    }
    else
    {
        /* 安装，检查该支付方式是否曾经安装过 */
        $sql = "SELECT COUNT(*) FROM " . $ecs->table('payment') . " WHERE pay_code = '$_REQUEST[pay_code]'";
        if ($db->getOne($sql) > 0)
        {
            /* 该支付方式已经安装过, 将该支付方式的状态设置为 enable */
            $sql = "UPDATE " . $ecs->table('payment') .
                   "SET pay_name = '$_POST[pay_name]'," .
                   "    pay_desc = '$_POST[pay_desc]'," .
                   "    pay_config = '$pay_config'," .
                   "    pay_fee    =  '$pay_fee', ".
                   "    enabled = '1' " .
                   "WHERE pay_id = '$_POST[pay_id]' LIMIT 1";
            $db->query($sql);
        }
        else
        {
            /* 该支付方式没有安装过, 将该支付方式的信息添加到数据库 */
            $sql = "INSERT INTO " . $ecs->table('payment') . " (pay_code, pay_name, pay_desc, pay_config, is_cod, pay_fee, enabled)" .
                   "VALUES ('$_POST[pay_code]', '$_POST[pay_name]', '$_POST[pay_desc]', '$pay_config', '$_POST[is_cod]', '$pay_fee', 0)";
            $db->query($sql);
        }

        /* 记录日志 */
        admin_log($_POST['pay_name'], 'install', 'payment');

        sys_msg($_LANG['install_ok'], 0, $link);
    }
}

/*------------------------------------------------------ */
//-- 卸载支付方式 ?act=uninstall&code={$code}
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'uninstall')
{
    admin_priv('payment');

    /* 把 enabled 设为 0 */
    $sql = "UPDATE {$ecs->table('payment')} SET enabled = '0' WHERE pay_id = '{$_REQUEST[id]}' LIMIT 1";
    $db->query($sql);

    /* 记录日志 */
    admin_log($_REQUEST['code'], 'uninstall', 'payment');

    $link[] = array('text' => $_LANG['back_list'], 'href' => 'payment.php?act=list');
    sys_msg($_LANG['uninstall_ok'], 0, $link);
}

/*------------------------------------------------------ */
//-- 增加支付方式费用
/*------------------------------------------------------ */

elseif ($_POST['act'] == 'add')
{
    admin_priv('payment');

    $payment['pay_name'] = trim($_REQUEST['pay_name']);
    $payment['pay_code'] = trim($_REQUEST['pay_code']);
    $payment['pay_desc'] = trim($_REQUEST['pay_desc']);
    $payment['pay_fee'] = trim($_REQUEST['pay_fee']);
    $payment['pay_order'] = trim($_REQUEST['pay_order']);
    $payment['is_cod'] = trim($_REQUEST['is_cod']);
    $payment['enabled'] = 0;
    
    if ($payment['pay_name'] == '') {
    	sys_msg("支付名不能为空", 1);
    	die();
    }

    if ($payment['pay_code'] == '') {
    	sys_msg("支付编码不能为空", 1);
    	die();
    }
    
    $sql = "SELECT * FROM {$ecs->table('payment')} WHERE pay_code = '{$payment['pay_code']}' LIMIT 1";
    $demo_payment = $db->getRow($sql);
    
    if ($demo_payment == null) {
    	sys_msg("支付编码不存在", 1);
    	die();    	
    } else {
    	$payment['pay_config'] = $demo_payment['pay_config'];
    }

    $db->autoExecute($ecs->table('payment'), $payment, 'INSERT');
    
    $id = $db->insert_id();
    
    /* 记录管理员操作 */
    admin_log(addslashes($payment['pay_name']), 'install', 'shipping');
    
    /* 提示信息 */
    $lnk[] = array('text' => '返回列表页面', 'href' => 'payment.php?act=list');
    $lnk[] = array('text' => '编辑支付方式', 'href' => "payment.php?act=edit&id={$id}");
    
	sys_msg("安装支付方式{$payment['pay_name']}成功", 0, $lnk);    

}

/*------------------------------------------------------ */
//-- 删除支付方式费用
/*------------------------------------------------------ */

//elseif ($_REQUEST['act'] == 'delete')
//{
//    admin_priv('payment');
//
//    $id = $_REQUEST['id'];
//
// 	  $pay_name = $exc->get_name($id, 'pay_name');
	
//	  $exc->drop($id);    
    
    /* 记录管理员操作 */
//    admin_log(addslashes($payment['pay_name']), 'remove', 'payment');
    
    /* 提示信息 */
//    $lnk[] = array('text' => '返回列表页面', 'href' => 'payment.php?act=list');
    
//	sys_msg("删除支付方式{$payment['pay_name']}成功", 0, $lnk);    
//}

/*------------------------------------------------------ */
//-- 修改支付方式名称
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_name')
{
    /* 检查权限 */
    check_authz_json('payment');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $name = trim($_POST['val']);

    /* 检查名称是否为空 */
    if (empty($name))
    {
        make_json_error($_LANG['name_is_null']);
    }

    /* 检查名称是否重复 */
    if (!$exc->is_only('pay_name', $name, $id))
    {
        make_json_error($_LANG['name_exists']);
    }

    /* 更新支付方式名称 */
    $exc->edit("pay_name = '$name'", $id);
    make_json_result(stripcslashes($name));
}

/*------------------------------------------------------ */
//-- 修改支付方式描述
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_desc')
{
    /* 检查权限 */
    check_authz_json('payment');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $desc = trim($_POST['val']);

    /* 更新描述 */
    $exc->edit("pay_desc = '$desc'", $id);
    make_json_result(stripcslashes($desc));
}

/*------------------------------------------------------ */
//-- 修改支付方式排序
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_order')
{
    /* 检查权限 */
    check_authz_json('payment');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $order = intval($_POST['val']);

    /* 更新排序 */
    $exc->edit("pay_order = '$order'", $id);
    make_json_result(stripcslashes($order));
}
/*------------------------------------------------------ */
//-- 修改银行账号、银行名、账户名
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_pay_config')
{
    /* 检查权限 */
    check_authz_json('payment');

    /* 取得参数 */  
    $pay_id = trim($_POST['id']);
   
    $sql = "SELECT * FROM {$ecs->table('payment')} WHERE pay_id = '{$pay_id}'";
    $demo_payment = $db->getRow($sql);
    $pay_configs = unserialize($demo_payment['pay_config']);
    if($_POST['name'] == 'bank_account'){
      $pay_configs['bank_account'] = trim($_POST['val']);
    }
    if($_POST['name'] == 'bank_name'){
      $pay_configs['bank_name'] = trim($_POST['val']);
    }    
    if($_POST['name'] == 'bank_people'){
      $pay_configs['bank_people'] = trim($_POST['val']);
    }
    if($_POST['name'] == 'service_phone'){
    	 $pay_configs['service_phone'] = trim($_POST['val']);
    }
    $pay_config = serialize($pay_configs);
    $sql = "UPDATE {$ecs->table('payment')} SET pay_config='{$pay_config}' WHERE pay_id = '{$pay_id}'";
    $db->query($sql);
    /* 更新排序 */
    make_json_result(stripcslashes(trim($_POST['val'])));
}
/*------------------------------------------------------ */
//-- 修改支付方式排序
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_is_cod')
{
    /* 检查权限 */
    check_authz_json('payment');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $value = intval($_POST['val']);

    /* 更新排序 */
    $exc->edit("is_cod = '$value'", $id);
    
    make_json_result(stripcslashes($value));
}

/*------------------------------------------------------ */
//-- 修改支付方式费用
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_pay_fee')
{
    /* 检查权限 */
    check_authz_json('payment');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $pay_fee = trim($_POST['val']);
    if (empty($pay_fee))
    {
        $pay_fee = 0;
    }
    else
    {
        $pay_fee = make_semiangle($pay_fee); //全角转半角
        if (strpos($pay_fee, '%') === false)
        {
            $pay_fee = floatval($pay_fee);
        }
        else
        {
            $pay_fee = floatval($pay_fee) . '%';
        }
    }

    /* 更新支付费用 */
    $exc->edit("pay_fee = '$pay_fee'", $id);
    make_json_result(stripcslashes($pay_fee));
}
/*------------------------------------------------------ */
//-- 修改账户名称
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_acct_name')
{
    /* 检查权限 */
    check_authz_json('payment');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $acct_name = trim($_POST['val']);

    /* 更新账户名称 */
    $exc->edit("acct_name = '{$acct_name}'", $id);
    make_json_result(stripcslashes($acct_name));
}

function payment_sort($a, $b) {
	if ($a['pay_order'] == $b['pay_order']) {
		return 0;
	} elseif ($a['pay_order'] < $b['pay_order']) {
		return -1;
	} else {
		return 1;
	}
	
}

?>