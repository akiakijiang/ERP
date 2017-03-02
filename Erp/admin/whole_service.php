<?php
/**
 * 原单退货 验货
 */
define('IN_ECS', true);
require('includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH.'includes/debug/lib_log.php');
require_once('includes/lib_service.php');
include_once('function.php');
global $db;

$tracking_number = $_REQUEST['tracking_number']? $_REQUEST['tracking_number']:$_POST['tracking_number'];
$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):(isset($_POST['act'])?trim($_POST['act']):'load'); // 默认加载有关快递单号信息
$facility_user_list = get_user_facility();

if($act=='load'){
	if(empty($tracking_number)){
		$message="获取快递单号失败";
	}
	//根据快递单号获取
	$sql = "SELECT ws.warehouse_service_type,ws.warehouse_service_status,oi.order_status,oi.order_sn,
		oi.facility_id as original_facility_id,ws.warehouse_service_id,
		og.rec_id,og.goods_id,og.style_id,og.goods_name,og.goods_number,ifnull(gs.barcode,g.barcode) as product_barcode,
		ws.remark,ws.facility_id as scan_facility_id,f.facility_name
		FROM ecshop.warehouse_service ws 
		INNER JOIN ecshop.ecs_order_info oi on oi.order_sn = ws.original_order_sn
		INNER JOIN ecshop.ecs_order_goods og on og.order_id = oi.order_id  
		INNER JOIN romeo.facility f on f.facility_id = oi.facility_id
		LEFT JOIN ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id and gs.style_id = og.style_id and gs.is_delete=0
		LEFT JOIN ecshop.ecs_goods g on g.goods_id = og.goods_id
		where tracking_number = '{$tracking_number}' and ws.warehouse_service_type = 'whole'   "; 
	$loadInfos = $db->getAll($sql);
	if(empty($loadInfos)){
		$message = "没有查询到登记过原订单销售商品记录";
	}else{
		foreach($loadInfos as $key=>$loadInfo){
			$goods_serial = 'N';
			require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
       		$is_serial = getInventoryItemType($loadInfo['goods_id']);   // 查询商品是否串号控制
        	if($is_serial == 'SERIALIZED') {  // 串号控制商品
            	$serial_numbers = get_out_serial_numbers($loadInfo['rec_id']);   // 得到已经出库的串号
            	$loadInfos[$key]['serial_numbers'] = $serial_numbers;
            	$goods_serial='Y';
        	}
        	$loadInfos[$key]['is_serial'] = $goods_serial;
        	
			// 该订单中该商品已建立过售后服务申请的数量
			$sql = "SELECT SUM(amount) FROM ecshop.service_order_goods sog
				INNER JOIN ecshop.service s ON s.service_id = sog.service_id
				WHERE sog.order_goods_id = '{$loadInfo['rec_id']}'
				    AND ((s.is_complete = 0 AND s.inner_check_status = 0 AND s.service_status = 1 AND sog.is_approved = 1) 
			        OR (s.is_complete = 0 AND s.inner_check_status = 0 AND s.service_status = 0 AND sog.is_approved = 0)
			        OR (s.is_complete = 0 AND s.inner_check_status = 32 AND s.service_status = 2 AND sog.is_approved = 1))";
			$amount_in_service = $db->getOne($sql);
			
			$service_amount_available = $loadInfo['goods_number'] - $amount_in_service;
			$loadInfos[$key]['service_amount_available'] =  $service_amount_available;
			
			if($loadInfo['warehouse_service_status']!='INIT'){
				//已验货的需获取 新旧 数量组合
				$sql = "select goods_number,goods_status
					from ecshop.warehouse_service_goods where original_order_goods_id = {$loadInfo['rec_id']} and warehouse_service_id= {$loadInfo['warehouse_service_id']} ";
				$check_goods_info = $db->getAll($sql);
				$loadInfos[$key]['scan_goods'] =$check_goods_info; 
				$scan_goods_number_rec= 0 ;
				if(!empty($check_goods_info)){
					foreach($check_goods_info as $value){
						$scan_goods_number_rec += $value['goods_number'];
					}
				}
				$loadInfos[$key]['scan_goods_number_rec'] = $scan_goods_number_rec;
			}
		}
		
	}
	$smarty->assign('scan_facility_id',$loadInfos[0]['scan_facility_id']);
	$smarty->assign('service_status',$loadInfos[0]['warehouse_service_status']);
	$smarty->assign('original_facility_id',$loadInfos[0]['original_facility_id']);
	$smarty->assign('original_order_sn',$loadInfos[0]['order_sn']);
	$smarty->assign('facility_name',$loadInfos[0]['facility_name']);
	$smarty->assign('loadInfos',$loadInfos);
	$smarty->assign('message',$message);
}else if($act=='storage'){ // 确认入库： 注意事务锁 ; 拒绝入库 
	$innerCheck = $_POST['inner_check'];  //验货结果（pass/refuse）
	$orderSn = trim($_POST['original_order_sn']); //原销售订单号
	$trackingNumber = $_POST['tracking_number'] ; //退货快递单号
	$applyReturnReason = $_POST['remark'];  //仓库备注（包含顾客退货原因）
	$ReturnFacilityId = $_POST['return_facility_id']; //受理仓库
	$scanOrderGoods=$_POST['scan_order_goods'];  //扫描商品信息
	if(!($innerCheck && $orderSn && $trackingNumber && $scanOrderGoods)){
		sys_msg("接收信息不完整",1); 
	}else{
		$sql_warehouse_service = "select * from ecshop.warehouse_service " .
				" where tracking_number = '{$trackingNumber}' and original_order_sn = '{$orderSn}' limit 1 ";
		$warehouse_service = $db->getRow($sql_warehouse_service);
		if(empty($warehouse_service)){
			sys_msg("根据快递单号与原订单号未查到登记信息",1); 
		}else{
			regist_back_goods_pass_reject($trackingNumber,$innerCheck,$warehouse_service,$orderSn,$scanOrderGoods,$ReturnFacilityId,$applyReturnReason);
		}
	}	
}

$smarty->assign('facility_user_list',$facility_user_list);
$smarty->assign('tracking_number',$tracking_number);
$smarty->display('whole_service.htm');


