<?php

define('IN_ECS', true);
require_once 'includes/init.php';
include_once 'function.php';
require_once 'includes/helper/array.php';
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

admin_priv('shop_weight_set');//店铺权重设置

$party_id = $_SESSION['party_id'] ;
if(!check_leaf_party($party_id)) {
	die('请切换到具体的组织再设置！');
}


$act = $_REQUEST['act'] ;
$distributor_list = getDistributorList($party_id);


global $db;
$sql = "select name from romeo.party where party_id = '{$party_id}' limit 1";
$party_info = $db->getRow($sql);
$party_name = $party_info['name'];
if($act=='update'){
	$distributor_id = $_POST['distributor_id'];
	$price_weight=$_POST['price_weight'];
	$quality_weight = $_POST['quality_weight'];
	$result = array();
	if(empty($distributor_id)){
		$result['flag'] = false;
		$result['info'] = '店铺ID检测为空，请重试';
	}else if($price_weight+$quality_weight != 1){
		$result['flag'] = false;
		$result['info'] = '请保证价格权重+质量权重=1';
	}else{
		$sql = "update romeo.shop_weight set price_weight=".$price_weight." ,quality_weight = ". $quality_weight.
			" where distributor_id = ".$distributor_id;
		if($db->query($sql)){
			$result['flag'] = true;
		}else{
			$result['flag'] = false;
			$result['info'] = '权重更新失败，请重试';
		}	
	}
	echo(json_encode($result));
    exit;
}


$smarty->assign('party_name',$party_name);
$smarty->assign('distributor_list',$distributor_list);
$smarty->display('shop_weight_list.htm');



//获取指定业务组织下的所有店铺
function getDistributorList($party_id) {
	global $db;
	$sql = "select * from romeo.shop_weight 
			where party_id={$party_id} ";
	$distributor_list = $db -> getAll($sql);
	return $distributor_list;
}

?>