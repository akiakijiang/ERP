<?php
/**
 * 自动确认订单设置
 */

define('IN_ECS', true);
require('includes/init.php');
require("function.php");
require_once("includes/lib_main.php"); 

 admin_priv('auto_confirm_control');
 $party_id = $_SESSION['party_id'] ;
 $act = $_REQUEST['act'] ;
 
 if(!check_leaf_party($party_id)) {
 	echo '请切换到具体的组织再设置！';
 	die();
 }
 
 
 $is_auto_confirm = 1;
 $sel_distributor = "-1";
 $distributorlist = get_distributorlist($party_id);
 $zhixiao_distributorlist = get_zhixiao_distributorlist($party_id);
 $fenxiao_distributorlist = get_fenxiao_distributorlist($party_id);
 
 $result = array(); 
 $shop_facility_ids = ''; 
 $shop_facility_message = ''; 
 foreach($distributorlist as $dis){
 	 $temp = $dis['distributor_id'];
 	 $sql = "select is_auto_confirm , auto_confirm_facility_ids from ecshop.distributor where distributor_id = {$temp} limit 1";
 	 $result = $db->getAll($sql);
 	 if($result[0]['is_auto_confirm']==0){
 	 	$is_auto_confirm=0;
 	 	break;
 	 }
 	 if($shop_facility_ids ==''){
 	 	$shop_facility_ids = $result[0]['auto_confirm_facility_ids']; 
 	 }
 	 if($shop_facility_ids != $result[0]['auto_confirm_facility_ids']){
 	 	$shop_facility_message = "不同店铺选择的仓库不同 请谨慎操作 ";
 	 }
 }

 if($shop_facility_message !=''){
 	$shop_facility_ids = ''; 
 }

 
 if($act == 'modify_auto_confirm') {
 	 $sel_distributor = $_REQUEST['distributor'] ;
 	 $is_auto_confirm = $_REQUEST['is_auto_confirm'] ;
 	
 	 if($is_auto_confirm == 0){
 	 	$facility_ids = -1; 
 	 }else{
 	 	$facility = $_REQUEST['facility'] ;  
 	 	$facility_ids = implode(",",$facility); 
 	 }
 	 $shop_facility_ids = $facility_ids; 
 	 $shop_facility_message = ''; 


 	 if(!empty($facility_ids)){
	 	 if($sel_distributor=="-1"){
	 	 	$disArr = array();
	 	 	foreach($distributorlist as $dis){
	 	 		$disArr[]=$dis['distributor_id']; 	 			 		
	 	 	}
	 	 	$disStr=implode(',',$disArr);
	 	    $diss="(".$disStr.")";
	 	 	$sql = "update ecshop.distributor set is_auto_confirm = '{$is_auto_confirm}' , auto_confirm_facility_ids ='{$facility_ids}'  where distributor_id in ".$diss ;
	 	 }else{
	 	 	$sql = "update ecshop.distributor set is_auto_confirm = '{$is_auto_confirm}' , auto_confirm_facility_ids ='{$facility_ids}'  where distributor_id = '{$sel_distributor}' limit 1";
	 	 }
	 	 $result = $db->query($sql);
	 	 if($result) {
	 	 	$info = "修改成功！";
	 	 } else {
	 	 	$info = "修改失败！";
	 	 }
 	  }else{
 	  	$info = "修改失败 需要选择仓库"; 
 	  }
 }
  // 选择的店铺改变时，该店铺是否自动确认订单的属性也相应改变
 elseif ($act=='search_auto_confirm_control') {
 	 $sel_distributor = $_REQUEST['selDis'] ;
 	 if($sel_distributor=="-1"){
 	 	 $sel_auto = 1;
 	 	 $facility_ids = ''; 
 	 	 $facility_ids_common = true; 
 	 	 foreach($distributorlist as $dis){
		 	 $temp = $dis['distributor_id'];
		 	 $sql = "select is_auto_confirm , auto_confirm_facility_ids from ecshop.distributor where distributor_id = {$temp} limit 1";
		 	 $result = $db->getAll($sql);
		 	 if($facility_ids ==''){
		 	 	$facility_ids = $result[0]['auto_confirm_facility_ids']; 
		 	 }
		 	 if($facility_ids != $result[0]['auto_confirm_facility_ids']){
		 	 	$facility_ids_common = false; 
		 	 }
		 	 if($result[0]['is_auto_confirm']==0){
		 	 	$sel_auto=0;
		 	 	break;
		 	 }
		 }
		 $arr=array();
		 $arr['is_auto_confirm']=$sel_auto;
		 $arr['facility_ids_common']=$facility_ids_common;
		 $arr['facility_ids']=$facility_ids; 
		 $arrNew=array();
		 $arrNew[0]=$arr;
		 echo(json_encode($arrNew));
		 exit();
 	 }
 	 else{
 	 	 $sql = "select is_auto_confirm , auto_confirm_facility_ids as facility_ids from ecshop.distributor where distributor_id = {$sel_distributor} limit 1";
	     $result=$db->getAll($sql);
	     echo(json_encode($result));
		 exit();
 	 }
 }


$sql = "select name from romeo.party where party_id = '{$party_id}' limit 1";
$party_info = $db->getRow($sql);
$party_name = $party_info['name'];


$smarty->assign('info',$info);
$smarty->assign('party_name',$party_name);
$smarty->assign('sel_distributor',$sel_distributor);
$smarty->assign('zhixiao_distributorlist',$zhixiao_distributorlist);
$smarty->assign('fenxiao_distributorlist',$fenxiao_distributorlist);
$smarty->assign('is_auto_confirm',$is_auto_confirm);
$smarty->assign('shop_facility_message',$shop_facility_message); 
$smarty->assign('shop_facility_ids',$shop_facility_ids);  
$smarty->assign('available_facility', get_available_facility());  
$smarty->display('oukooext/auto_confirm_control.htm');


//获取指定业务组织下的所有店铺
function get_distributorlist($party_id) {
	global $db;
	$sql = "select distinct distributor_id, name from ecshop.distributor where party_id={$party_id} and status = 'NORMAL'";
	$distributor_list = $db -> getAll($sql);
	return $distributor_list;
}

//获取指定业务组织下的所有直销店铺
function get_zhixiao_distributorlist($party_id) {
	global $db;
	$sql = "select distinct d.distributor_id, d.name, d.is_auto_confirm, md.type from ecshop.distributor d
			LEFT JOIN ecshop.main_distributor  md on d.main_distributor_id=md.main_distributor_id 
			where d.party_id={$party_id} and d.status = 'NORMAL' and md.type='zhixiao'";
	$zhixiao_distributorlist = $db -> getAll($sql);
	return $zhixiao_distributorlist;
}

//获取指定业务组织下的所有分销店铺
function get_fenxiao_distributorlist($party_id) {
	global $db;
	$sql = "select distinct d.distributor_id, d.name, d.is_auto_confirm, md.type from ecshop.distributor d
			LEFT JOIN ecshop.main_distributor  md on d.main_distributor_id=md.main_distributor_id 
			where d.party_id={$party_id} and d.status = 'NORMAL' and md.type='fenxiao'";
	$fenxiao_distributorlist = $db -> getAll($sql);
	return $fenxiao_distributorlist;
}



?>