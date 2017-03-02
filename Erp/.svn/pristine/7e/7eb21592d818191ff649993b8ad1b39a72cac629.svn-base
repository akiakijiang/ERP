<?php

/**
 * 添加淘宝小二
 */
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
admin_priv('taobao_sales_add'); ////////////加权限

$shop_list = get_taobao_shop_nicks();
$smarty->assign('shop_nicks', $shop_list);

/*
 * 
 * 添加淘宝旺旺客服
 * 
 * */
if($_REQUEST['act']=='edit_taobao_sales'){
	do{
		if(empty($_REQUEST['nickname']))
		{
			$smarty->assign('message', '需要填写昵称');
			break;
		}
		
		if(strstr($_REQUEST['nickname'],'：')){
			$smarty->assign('message', '昵称不能使用中文冒号');
			break;
		}
		
		if(strstr(trim($_REQUEST['nickname']),' ')){
			$smarty->assign('message', '昵称中间存在空格');
			break;
		}
				
		if(empty($_REQUEST['taobao_shop_conf_id']))
		{
			$smarty->assign('message', '需要选择店铺');
			break;
		}else
			$taobao_shop_conf_id=trim($_REQUEST['taobao_shop_conf_id']);
				
		$sql="select taobao_sales_id from taobao_sales where taobao_shop_id = %d and nickname = '%s'";
		$taobao_sales=$db->getRow(sprintf($sql,$taobao_shop_conf_id,trim($_REQUEST['nickname'])));
		// 存在则更新
		if($taobao_sales)
		{
			$sql="update taobao_sales set enabled='%s' where taobao_sales_id = %d";
			$result=$db->query(sprintf($sql,trim($_REQUEST['enabled']),trim($taobao_sales['taobao_sales_id'])));
			if($result)
				$smarty->assign('message', '更新成功');
			else
				$smarty->assign('message', '悲剧了，更新失败');
		}
		else // 不存在添加记录
		{    
			$sql="insert into taobao_sales (taobao_shop_id,nickname,enabled,created) values (%d, '%s', '%s', NOW())";
			$result=$db->query(sprintf($sql,$taobao_shop_conf_id,trim($_REQUEST['nickname']),trim($_REQUEST['enabled'])));
			if($result)
				$smarty->assign('message', '添加成功，请注意勿重复提交');
			else
				$smarty->assign('message', '悲剧了，数据库执行不成功');
		}
	}
	while(false);
	$smarty->assign('nickname',$_REQUEST['nickname']);
	$smarty->assign('taobao_shop_conf_id',$_REQUEST['taobao_shop_conf_id']);
	$smarty->assign('enabled',$_REQUEST['enabled']);
}

/*
 * 
 * 查询淘宝旺旺客服
 * 
 * */
if($_REQUEST['act']=='search_taobao_sales'){
	$taobao_sale_names = get_taobao_sales_list();
	$smarty->assign('taobao_sale_names',$taobao_sale_names);
	$smarty->assign('nick_name',$_REQUEST['nick_name']);
	$smarty->assign('shop_nick',$_REQUEST['shop_nicks']);
}

$smarty->display("taobao/taobao_sales_add.htm");

/**
 * 取得淘宝店铺信息
 * 
 */
function get_taobao_shop_nicks() {
    $application_list = get_taobao_shop_list(); //取得taobao店铺信息
    $application_nicks = array();
    foreach ($application_list as $application) {
        $application_nicks[$application['taobao_shop_conf_id']] = $application['nick'];
    }
    return $application_nicks;
}

function get_taobao_shop_list() {
    global $db;
    $sql = "SELECT nick,taobao_shop_conf_id FROM taobao_shop_conf WHERE shop_type = 'taobao' and party_id = '".$_SESSION['party_id']."'";
    $application_list = $db->getAll($sql);
    return $application_list;
}

/**fdc
 * 获得条件
 *
 */
function get_taobao_sales_list(){
	global $db;
	extract($_REQUEST);
	$condition = "";
	
	if (trim($nick_name) != '') {
        $condition .= "AND ts.nickname LIKE '%".mysql_escape_string(trim($nick_name))."%'";
    }
	if ( trim($_REQUEST['shop_nicks']) != 'ALL' ) {
		$condition .= " AND ts.taobao_shop_id = '".mysql_escape_string(trim($shop_nicks))."'";
	}else {
		$condition .= " AND tsc.party_id = '".$_SESSION['party_id']."'";
	}
	
	$sql = "SELECT ts.nickname, tsc.nick, IF(ts.enabled = 'Y','开启','禁用') AS status, ts.created
		FROM taobao_sales ts LEFT JOIN taobao_shop_conf tsc ON ts.taobao_shop_id = tsc.taobao_shop_conf_id
		WHERE 1 ".$condition;
	
	$taobao_sales_list = $db->getAll($sql);
	return $taobao_sales_list;
}
?>
