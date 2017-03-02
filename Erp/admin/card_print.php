<?php
/*
 * 打印贺卡
 */
 
 define('IN_ECS', true);
require_once('includes/init.php');
include_once 'function.php';
require_once('includes/lib_order.php');
//include_once('includes/lib_order_mixed_status.php');//By Sinri
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'RomeoApi/lib_facility.php');
require_once(ROOT_PATH . 'admin/phpqrcode/phpqrcode.php');

$video = false;
$not_need_card = false;
if(isset($_REQUEST['taobao_order_sn']) && is_string($_REQUEST['taobao_order_sn'])){
	$taobao_order_sn = $_REQUEST['taobao_order_sn'];
	$sql = "select taobao_order_sn,greetings,video_name from ecshop.brand_lamer_gift where taobao_order_sn='{$taobao_order_sn}'";
	$sql_shipment_id = "select s.shipment_id from romeo.shipment s 
							LEFT JOIN ecshop.ecs_order_info eoi on convert(eoi.order_id USING utf8) = s.primary_order_id 
							LEFT JOIN ecshop.brand_lamer_gift blg on eoi.taobao_order_sn = blg.taobao_order_sn
							WHERE blg.taobao_order_sn = '{$taobao_order_sn}'";
	$card_info = $db->getRow($sql);						
	$shipment_id = $db->getOne($sql_shipment_id);
	if($card_info == null) {
		die("无此订单！");
	} elseif($card_info['video_name'] == 'no_video' && $card_info["greetings"] == '') {
		$not_need_card = true;	
	} elseif($card_info['video_name'] != 'no_video') {
		$video = true;
		$taobao_order_sn = $card_info['taobao_order_sn'];
//		$taobao_order_sn = '1028909934014327';
		$file_name = md5($taobao_order_sn).".mp4";
		$value = "http://video.lamer.com.cn/video.php?fileName=".$file_name."&bless=".($card_info["greetings"]?$card_info["greetings"]:"Forever Love"); //二维码内容 
//		var_dump($value);
		$errorCorrectionLevel = 'M';//容错级别 
		$matrixPointSize = 5;//生成图片大小 
		//生成二维码图片 
		QRcode::png($value, 'img/qr.png', $errorCorrectionLevel, $matrixPointSize, 2);  
		$QR = 'img/qr.png';//已经生成的原始二维码图 
		$QR = imagecreatefromstring(file_get_contents($QR)); 
		//输出图片 
		imagepng($QR, 'img/qr.png'); 	
	} elseif($card_info['video_name'] == 'no_video' && $card_info["greetings"] != '') {
		$value = "http://lamer.m.tmall.com"; //二维码内容 
		$errorCorrectionLevel = 'M';//容错级别 
		$matrixPointSize = 5;//生成图片大小 
		//生成二维码图片 
		QRcode::png($value, 'img/qr.png', $errorCorrectionLevel, $matrixPointSize, 2);  
		$QR = 'img/qr.png';//已经生成的原始二维码图 
		$QR = imagecreatefromstring(file_get_contents($QR)); 
		//输出图片 
		imagepng($QR, 'img/qr.png'); 
	}

} else {
    die("参数错误");
}
$smarty->assign('shipment_id',$shipment_id);
$smarty->assign('video',$video);
$smarty->assign('not_need_card',$not_need_card);	
$smarty->assign('card_info',$card_info);
$smarty->display('card/card_LAMER.htm');

?>