<?php
/**
 
**/
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');

//var_dump($_REQUEST);
if(isset($_REQUEST['taobao_order_sn'])){
	$taobao_order_sn=$_REQUEST['taobao_order_sn'];
} else {
	$taobao_order_sn=null;
}
//var_dump($taobao_order_sn);
$get_info = false;
$not_need_card = false;
if(isset($_REQUEST['act'])){
	if ($_REQUEST['act']=="query"){
	$sql = "select taobao_order_sn,greetings,video_name from ecshop.brand_lamer_gift where taobao_order_sn='{$taobao_order_sn}'";
	$card_info = $db->getRow($sql);
	if($card_info != null) {	
		$get_info = true;
	} else {
		$message = '查询不到该订单，请确认后在查询！';
	}
	if($card_info['video_name'] == 'no_video' && $card_info['greetings'] == '') {
		$not_need_card = true;
	}
	}
}
$smarty->assign('message',$message);
$smarty->assign('not_need_card',$not_need_card);
$smarty->assign('card_info',$card_info);
$smarty->assign('get_info',$get_info);
$smarty->display('Deal_Card_Print.htm');
	
?>