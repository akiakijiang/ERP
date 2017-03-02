<?php
/**
 * 主分销商管理
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
admin_priv('distributor_manage');
require_once("function.php");
require_once(ROOT_PATH . 'includes/helper/array.php');

$act = $_REQUEST['act'];

// 添加动作
if ($act == 'add_submit') {
    $distributor = $_POST['distributor'];

	
    if (!$distributor['name']) {
        header("Location:main_distributor_manage.php?message=".urlencode("没有输入分销商的名字"));
        exit();
    }else if($distributor['name']!=trim($distributor['name']))
    {
    	header("Location:distributor_manage.php?message=".urlencode("请不要输入带有空格符的收货人信息"));
        exit();
    }
    
    $sql = "select 1 from ecshop.main_distributor where party_id='{$_SESSION['party_id']}' and name='{$distributor['name']}' limit 1";
    $exists_name = $db->getOne($sql);
    if(!empty($exists_name)) {
        header("Location:main_distributor_manage.php?message=".urlencode("系统中已创建该主分销商,不能重复创建"));
        exit();
    }    
        
	if (empty($distributor['party_id'])) {
        header("Location:main_distributor_manage.php?message=".urlencode("没有选择业务类型"));
        exit();
    }
    
    $distributor['create_user'] = $_SESSION['admin_name'];
    $distributor['create_time'] = date('Y-m-d H:i:s');
    $distributor['last_update_user'] = $_SESSION['admin_name'];
    $distributor['last_update_time'] = date('Y-m-d H:i:s');    
    unset($distributor['main_distributor_id']);
    $db->autoExecute('main_distributor', $distributor);
    header("Location:main_distributor_manage.php?message=".urlencode("添加成功"));
    exit;
} 
// 更新页面
elseif ($act == 'update') {
    $main_distributor_id = $_REQUEST['main_distributor_id'];
    $edit_distributor = $db->getRow("SELECT * FROM main_distributor WHERE main_distributor_id = '{$main_distributor_id}' ");
    // 预警值展示以prepayment_account中的预警值为准 ljzhou 2013.03.23
    $sql = "select min_amount from romeo.prepayment_account where supplier_id = '{$main_distributor_id}' and PREPAYMENT_ACCOUNT_TYPE_ID = 'DISTRIBUTOR' limit 1";
    $min_amount = $db->getOne($sql);
    if(!empty($min_amount)) {
    	$edit_distributor['min_amount'] = $min_amount;
    }
    $smarty->assign('edit_distributor', $edit_distributor);
} 
// 更新动作
elseif ($act == 'update_submit') {
    $main_distributor_id = $_REQUEST['main_distributor_id'];
    $distributor = $_POST['distributor'];
    unset($distributor['main_distributor_id']);
    $distributor['last_update_user'] = $_SESSION['admin_name'];
    $distributor['last_update_time'] = date('Y-m-d H:i:s');     
    $db->autoExecute('main_distributor', $distributor, 'UPDATE', " main_distributor_id = '{$main_distributor_id}' " );
    // 同时更新prepayment_account中预存款的预警值 ljzhou 2013.03.19
    $sql = "select 1 from romeo.prepayment_account where supplier_id = '{$main_distributor_id}' and PREPAYMENT_ACCOUNT_TYPE_ID = 'DISTRIBUTOR' limit 1";
    $result = $db->getOne($sql);
    if(!empty($result)) {
    	$sql = "update romeo.prepayment_account set min_amount = '{$distributor['min_amount']}' where supplier_id = '{$main_distributor_id}' and PREPAYMENT_ACCOUNT_TYPE_ID = 'DISTRIBUTOR' limit 1";
    	$res = $db->query($sql);
    	if($res) {
    		$message = ' 预存款预警值更新成功';
    	} else {
    		$message = ' 预存款预警值更新失败';
    	}
    }
    header("Location:main_distributor_manage.php?message=".urlencode("更新成功".$message));
    exit;
} 
// 更改状态 

elseif ($act == 'delete'   ) {
    $main_distributor_id = $_REQUEST['main_distributor_id'];
 
    $sql = "UPDATE main_distributor SET status = 'DELETE',last_update_user='{$_SESSION['admin_name']}', last_update_time=now() WHERE main_distributor_id = '{$main_distributor_id}' ";
    $db->query($sql);
    header("Location:main_distributor_manage.php?message=".urlencode("更新成功"));
}

$condition = get_condition();

$sql = "SELECT m.main_distributor_id,m.type,m.name,m.contact,s.NAME as party_name,m.status FROM `ecshop`.`main_distributor` as m ".
	   "left join `romeo`.`party` as s on s.PARTY_ID=m.party_id ".
	   " WHERE  " . party_sql ( 'm.PARTY_ID' )." {$condition}  " ." and m.status='NORMAL' ";
$main_distributors = $db->getAll($sql);
$party_list = party_list();

//print_r($party_list);
$smarty->assign('party_list',   $party_list);
$smarty->assign('main_distributors', $main_distributors);
$smarty->display('distributor/main_distributor_manage.htm');


function get_condition() {
    if ($_REQUEST['act'] == 'search') {
        $keyword = $_REQUEST['keyword'];
        return " AND ( m.name LIKE '%{$keyword}%' OR m.contact LIKE '%{$keyword}%') ";
    }
}
