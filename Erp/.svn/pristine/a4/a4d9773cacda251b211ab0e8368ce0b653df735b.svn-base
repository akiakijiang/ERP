<?php
/**
 * 分销商管理
 * 
 * $Author: zwsun
 * $Date: 2009年7月28日16:30:14$
 * $Id$
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
admin_priv('distributor_manage');
require_once("function.php");
require_once("pagination.php");
require_once(ROOT_PATH . 'includes/helper/array.php');

$act = $_REQUEST['act'];

// 添加动作
$edit_currency = false;
if ($act == 'add_submit') {
    $distributor = $_POST['distributor'];
    if (!$distributor['name']) {
        header("Location:distributor_manage.php?message=".urlencode("没有输入分销商的名字"));
        exit();
    }else if($distributor['name']!=trim($distributor['name']))
    {
    	header("Location:distributor_manage.php?message=".urlencode("请不要输入带有空格符的分销商名称"));
        exit();
    }       
    if (empty($distributor['party_id'])) {
        header("Location:distributor_manage.php?message=".urlencode("没有选择业务类型"));
        exit();
    }   
    if (empty($distributor['is_prepayment']))
    {
    	header("Location:distributor_manage.php?message=".urlencode("没有选择是否扣预存款"));
        exit();
    }
    if(empty($distributor['currency'])) {
    	$distributor['currency'] = 'RMB';
    }
    unset($distributor['distributor_id']);
    _validate($distributor);
    $db->autoExecute('distributor', $distributor);
    //将插入的新的分销商的信息同步到ecs_bubugao_dis
    /* $id_sql = "select distributor_id from ecshop.distributor where name = '{$distributor['name']}'";
    $id = $db->getOne($id_sql); */
    $id = $db->insert_id();
    if(16==$distributor['party_id']){
    	//密码初始化为 123456
    	$password = md5("123456");
    	$sql = "INSERT INTO ecshop.ecs_bubugao_dis (distributor_id, username, password, name, address) VALUES
    	('{$id}','{$distributor['name']}','{$password}','{$distributor['contact']}','{$distributor['address']}')";
    	try{
    		$db->query($sql);
    	}
    	catch (Exception $e)
    	{
    			header("Location:distributor_manage.php?message=".urlencode("数据同步到ecs_bubugao_dis时出错"));
    	}
    		 
    }
    header("Location:distributor_manage.php?message=".urlencode("添加成功"));
}
// 更新页面
elseif ($act == 'update') {
    $distributor_id = $_REQUEST['distributor_id'];
    $edit_distributor = $db->getRow("SELECT * FROM distributor WHERE distributor_id = '{$distributor_id}' ");
    $edit_currency = true;
    $smarty->assign('edit_distributor', $edit_distributor);
} 
// 更新动作
elseif ($act == 'update_submit') {
    $distributor_id = $_REQUEST['distributor_id'];
    $distributor = $_POST['distributor'];
    $sql = "select party_id from ecshop.distributor where distributor_id = '{$distributor_id}'";
    $party_id = $db->getOne($sql);
    unset($distributor['distributor_id'], $distributor['party_id']);
    _validate($distributor);  
    $db->autoExecute('distributor', $distributor, 'UPDATE', " distributor_id = '{$distributor_id}' " );
    //将更新的内容同步到ecs_bubugao_dis
    if(16==$party_id){
    	$sql = "update ecshop.ecs_bubugao_dis set username = '{$distributor['name']}',name = '{$distributor['contact']}',address = '{$distributor['address']}'
    	        where distributor_id = '{$distributor_id}'";
    	try{
    		$db->query($sql);
    	}
    	catch (Exception $e)
    	{
    		header("Location:distributor_manage.php?message=".urlencode("数据更新到ecs_bubugao_dis时出错"));
    	}
    }
    header("Location:distributor_manage.php?message=".urlencode("更新成功"));
    exit;
} 
// 更改状态 
elseif ($act == 'delete' || $act == 'normal' ) {
    $distributor_id = $_REQUEST['distributor_id'];
    if ($act == 'delete') {
        $status = 'DELETED';
    } else {
        $status = 'NORMAL';
    }
    $sql = "select distributor_id,name,contact,address,party_id from ecshop.distributor where distributor_id='{$distributor_id}'";
    $res = $db->getRow($sql);
    if(16==$res['party_id']){   	
    	if($act == 'delete'){
    		 $sql = "DELETE FROM ecshop.ecs_bubugao_dis WHERE distributor_id='{$distributor_id}' ";
    		 $db->query($sql);
    	}
    	else{
    		$sql = "INSERT INTO ecshop.ecs_bubugao_dis (distributor_id, username, password, name, address) VALUES
    		('{$distributor_id}','{$res['name']}',NULL,'{$res['contact']}','{$res['address']}')";
    		try{
    			$db->query($sql);
    		}
    		catch (Exception $e)
    		{
    			header("Location:distributor_manage.php?message=".urlencode("数据同步到ecs_bubugao_dis时出错"));
    		}
    	}
    }
    
    $sql = "UPDATE distributor SET status = '{$status}' WHERE distributor_id = '{$distributor_id}' ";
    $db->query($sql);
    header("Location:distributor_manage.php?message=".urlencode("更新成功"));
}

$condition = get_condition();

$sql = "SELECT * FROM distributor as d WHERE " . party_sql ( 'd.PARTY_ID' )." {$condition}";
$distributors = $db->getAll($sql);

//店铺类型
$shop_type_list = array(
	'TMALL' => '天猫',
	'MARKET' => '集市'
);

// 业务形态设置
$party_list = party_list();

// 邮件发布设置
$mail_list = array(
    'NONE'        => '不发送',
    'DISTRIBUTOR' => '发给分销商',
    'CUSTOMER'    => '发给终端客户',
);

// 短信发送设置
$message_list = array(
    'NONE'        => '不发送',
    'CUSTOMER'    => '发给终端客户',    
);

// 主分销商
$main_distributor_list = Helper_Array::toHashmap((array)$db->getAll("SELECT * FROM main_distributor as m where status='NORMAL' and ".party_sql ( 'm.PARTY_ID' )), 'main_distributor_id','name');

// 订单录入币种选择
$currencies = array('HKD' => '港币', 'USD' => '美元', 'RMB' => '人民币');

//是否扣预存款
$is_prepayment_list = array('Y' => '是', 'N' => '否');

$smarty->assign('currency', $currencies);
$smarty->assign('currencys', get_currency_style()); //币种数组
$smarty->assign('edit_currency',$edit_currency);
$smarty->assign('main_distributor_list', $main_distributor_list);  // 主分销商
$smarty->assign('party_list',    $party_list);
$smarty->assign('shop_type_list',$shop_type_list);
$smarty->assign('mail_list',     $mail_list);
$smarty->assign('message_list',  $message_list);
$smarty->assign('is_prepayment_list', $is_prepayment_list);
$smarty->assign('distributors',  $distributors);
$smarty->display('distributor/distributor_manage.htm');


function get_condition() {
    if ($_REQUEST['act'] == 'search') {
        $keyword = $_REQUEST['keyword'];
        return " AND ( name LIKE '%{$keyword}%' OR contact LIKE '%{$keyword}%' OR address LIKE '%{$keyword}%' ) ";
    }
}


function _validate(&$distributor)
{
	if (!isset($distributor['is_taxpayer']) || $distributor['is_taxpayer'] != 'Y') {
		$distributor['is_taxpayer'] = 'N';
	}
	if (!isset($distributor['abt_print_invoice']) || $distributor['abt_print_invoice'] != 'Y') {
		$distributor['abt_print_invoice'] = 'N';
	}
	/*
	if (!isset($distributor['abt_logo_style']) || $distributor['abt_logo_style'] != 'Y') {
		$distributor['abt_logo_style'] = 'N';
	}
	*/
	if (!isset($distributor['abt_change_price']) || $distributor['abt_change_price'] != 'Y') {
		$distributor['abt_change_price'] = 'N';
	}
	if (!isset($distributor['distri_authorization']) || $distributor['distri_authorization'] != 'PART') {
	    $distributor['distri_authorization'] = 'COMPLETE';
	}
}
