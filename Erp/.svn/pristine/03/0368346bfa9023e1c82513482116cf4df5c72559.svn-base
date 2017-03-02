<?php

define('IN_ECS', true);
require_once('../includes/init.php');

$user_priv_list = array(
	'LCZ'  => '售后巡查',
	'KF'   => '客服',
	'SHWL' => '上海物流',
	'DGWL' => '东莞物流',
	'CW'   => '财务',
	'DZ'   => '店长',
	'CG'   => '采购',
);

$message_id = $_REQUEST['message_id'];

// 错误信息
$errmsg = array();

if(isset($message_id) && !empty($message_id)){
	$sql = "
	    select support_type, send_by, message, program, 
	    	   responsible_person, next_process_group,group_concat(p.path) path
	    from ecshop.sale_support_message m 
	    left join ecshop.sale_support_message_pic p on m.sale_support_message_id = p.sale_support_message_id 
	    where m.sale_support_message_id = {$message_id} 
	    group by m.sale_support_message_id
	";
	$message = $db -> getRow($sql);
	if($message['path']!=null){
		$message['path']=explode(",",$message['path']);
	}
	$message['next_process_group'] = $user_priv_list[$message['next_process_group']];
	// if($message){
	// 	$sql = "select pic_name,path,pic_desc from ecshop.sale_support_message_pic where pic_status = 'OK' and sale_support_message_id = {$message_id}";
	// 	$pic_detail = $db -> getAll($sql);
	// 	foreach($pic_detail as $key => $detail){
	// 		$pic_detail[$key]['pic_desc'] = stripslashes($pic_detail[$key]['pic_desc']);
	// 	}
	// }else{
	// 	$errmsg[] = "没有找到该售后记录";
	// }
}else{
	$errmsg[] = "数据传入出错";
}

// $smarty -> assign('pic_detail', $pic_detail);
$smarty -> assign('errmsg',$errmsg);
$smarty -> assign('message',$message);

$smarty -> display('sale_support/sale_support_detail.htm');

?>