<?php
/**
 * 客户自退 验货
 */
define('IN_ECS', true);
require('includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH.'includes/debug/lib_log.php');
require_once('includes/lib_service.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
include_once('function.php');
global $db;

$tracking_number = $_REQUEST['tracking_number']? $_REQUEST['tracking_number']:$_POST['tracking_number'];
$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):(isset($_POST['act'])?trim($_POST['act']):'load'); // 默认加载有关快递单号信息
$facility_user_list = get_user_facility();
$sql = "select warehouse_service_status from ecshop.warehouse_service where tracking_number = '{$tracking_number}'";
$warehouse_service_status = $db->getOne($sql);
if($act=='load'){//根据快递单号获取已验货信息
	if(empty($tracking_number)){
		$message="获取快递单号失败";
	}
	$sql = "select * from ecshop.warehouse_service where tracking_number='{$tracking_number}' and warehouse_service_type='part' ";
	$warehouse_service = $db->getRow($sql);
	if(!empty($warehouse_service) && !empty($warehouse_service['original_order_sn'])){
		//根据快递单号获取
		$sql = "SELECT ws.warehouse_service_type,ws.warehouse_service_status,oi.order_status,oi.order_sn,
			oi.facility_id as original_facility_id,ws.warehouse_service_id,
			og.rec_id,og.goods_id,og.style_id,og.goods_name,og.goods_number,ifnull(gs.barcode,g.barcode) as product_barcode,
			ws.remark,ws.facility_id as scan_facility_id,f.facility_name
			FROM ecshop.warehouse_service ws 
			LEFT JOIN ecshop.ecs_order_info oi on oi.order_sn = ws.original_order_sn
			LEFT JOIN ecshop.ecs_order_goods og on og.order_id = oi.order_id 
			LEFT JOIN romeo.facility f on f.facility_id = oi.facility_id
			LEFT JOIN ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id and gs.style_id = og.style_id and gs.is_delete=0
			LEFT JOIN ecshop.ecs_goods g on g.goods_id = og.goods_id
			where warehouse_service_id = {$warehouse_service['warehouse_service_id']}  "; 
		$loadInfos = $db->getAll($sql);
		if(!empty($loadInfos)){
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
		$smarty->assign('original_order_sn',$loadInfos[0]['order_sn']);
		$smarty->assign('facility_name',$loadInfos[0]['facility_name']);
	}else if(!empty($warehouse_service) && empty($warehouse_service['original_order_sn']) && $warehouse_service['warehouse_service_status']=='CHECKED'){ //已验货
		$sql = "select  ws.warehouse_service_type,ws.warehouse_service_status,'' as original_facility_id,ws.warehouse_service_id,
			sg.warehouse_service_goods_id	as rec_id,sg.goods_id,sg.style_id,sg.goods_name,sg.goods_number,
			ifnull(gs.barcode,g.barcode) as product_barcode,ws.remark,ws.facility_id as scan_facility_id,
			IF(sg.serial_number is null or sg.serial_number='','N','Y') as is_serial,0 as service_amount_available,
		sg.goods_number as scan_goods_number_rec,sg.serial_number,sg.goods_status
		from ecshop.warehouse_service ws 
		left JOIN ecshop.warehouse_service_goods sg on sg.warehouse_service_id = ws.warehouse_service_id
		LEFT JOIN ecshop.ecs_goods_style gs on gs.goods_id = sg.goods_id and gs.style_id = sg.style_id and gs.is_delete=0
		LEFT JOIN ecshop.ecs_goods g on g.goods_id = sg.goods_id
		where ws.warehouse_service_id = {$warehouse_service['warehouse_service_id']}";
		$loadInfos = $db->getAll($sql);
		foreach($loadInfos as $key=>$loadInfo){
			if($loadInfo['is_serial']=='Y'){
				$loadInfos[$key]['serial_numbers'] = $loadInfo['serial_number'];
			}
			$loadInfos[$key]['scan_goods'] = array();
			$arr = array();
			$i=0;
			while($i<$loadInfo['goods_number']){
				$arr[$i] = array('goods_number'=>1,'goods_status'=>$loadInfo['goods_status']); 
				$i++;
			}
			$loadInfos[$key]['scan_goods'] = $arr;
			
		}
	}
	$smarty->assign('loadInfos',$loadInfos);
	$smarty->assign('scan_facility_id',$loadInfos[0]['scan_facility_id']);
	$smarty->assign('original_facility_id',$loadInfos[0]['original_facility_id']);
	$smarty->assign('message',$message);
}elseif($act=='loadInfo'){ //加载订单号商品信息
	$original_order_sn = trim($_REQUEST['original_order_sn']);
	if(empty($original_order_sn)){
		$message="获取订单号失败";
	}
	//根据订单号查原订单信息
	$sql = "SELECT oi.order_status,oi.order_sn, 
		f.facility_name, oi.facility_id as original_facility_id,
		og.rec_id,og.goods_id,og.style_id,og.goods_name,og.goods_number,ifnull(gs.barcode,g.barcode) as product_barcode 
		FROM ecshop.ecs_order_info oi  
		INNER JOIN ecshop.ecs_order_goods og on og.order_id = oi.order_id 
		INNER JOIN romeo.facility f on f.facility_id = oi.facility_id
		LEFT JOIN ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id and gs.style_id = og.style_id and gs.is_delete=0
		LEFT JOIN ecshop.ecs_goods g on g.goods_id = og.goods_id
		where order_sn='{$original_order_sn}' "; 
	$loadInfos = $db->getAll($sql);
	if(empty($loadInfos)){
		$message = "没有查询到订单号销售商品记录";
	}else{
		foreach($loadInfos as $key=>$loadInfo){
			$goods_serial = 'N';
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
		}
	}
	$smarty->assign('message',$message);
	$smarty->assign('original_order_sn',$original_order_sn);
	$smarty->assign('facility_name',$loadInfos[0]['facility_name']);
	$smarty->assign('original_facility_id',$loadInfos[0]['original_facility_id']);
	$smarty->assign('loadInfos',$loadInfos);
}else if($act=='goodScan'){ //ajax
	$result=array();
	
	//扫描商品条码 ,无原订单号使用
	$barcode = $_REQUEST['barcode'];
	$goods_sql = "select gs.goods_id,gs.style_id,gs.barcode,IF(gs.style_id IS NULL,g.goods_name, CONCAT_WS(',',g.goods_name,s.color) ) as goods_name " .
			" from ecshop.ecs_goods_style gs " .
			" left join ecshop.ecs_goods g on g.goods_id = gs.goods_id " .
			" left join ecshop.ecs_style s on s.style_id = gs.style_id " .
			" where gs.barcode = '{$barcode}' and gs.is_delete=0 limit 1";
	$goods_info = $db->getRow($goods_sql);
	if(empty($goods_info)){
		$goods_sql = "select goods_id,0 as style_id,goods_name,barcode " .
				" from ecshop.ecs_goods where barcode = '{$barcode}' limit 1";
		$goods_info = $db->getRow($goods_sql);
	}
	
	if(!empty($goods_info)){//1.1 barcode存在
		//2, 非串号控制
		$is_serial = getInventoryItemType($goods_info['goods_id']);   // 查询商品是否串号控制
		if($is_serial == 'SERIALIZED') { 
			$result['result'] = 'failure';
			$result['note'] = "串号商品请扫描串号条码！";
		}else{
			$goods_info['is_serial'] = 'N';
			$result['result'] = 'success';
			$result['goods_info'] = $goods_info;
		}
	}else{//1.2 barcode不存在，判断串号
		$serial_number = $barcode;
		$sql = "select * from romeo.inventory_item where serial_number = '{$serial_number}' limit 1 ";
		$serial_barcode_info = $db->getRow($sql);
		if(empty($serial_barcode_info)){
			$result['result'] = 'failure';
			$result['note'] = "系统中没有此条码，请核实";
		}else{
			//可以确定发货仓库，业务组，原始订单号，商品信息  -- 不回传，需在 入库/拒绝时判断多串号的出库信息一致性
			$goods_sql = "select pm.ecs_goods_id as goods_id,pm.ecs_style_id as style_id,
				  ifnull(gs.barcode,g.barcode) as barcode ,IF(gs.style_id IS NULL,g.goods_name, CONCAT_WS(',',g.goods_name,s.color) ) as goods_name
				from romeo.product_mapping pm  
				inner join romeo.product p on pm.product_id = p.product_id  
				left join ecshop.ecs_goods_style gs on gs.goods_id= pm.ecs_goods_id and gs.style_id = pm.ecs_style_id and gs.is_delete=0
				left join ecshop.ecs_goods g on g.goods_id = pm.ecs_goods_id 
				left join ecshop.ecs_style s on s.style_id = gs.style_id
				where pm.product_id = '{$serial_barcode_info['PRODUCT_ID']}' ";
			$goods_info = $db->getRow($goods_sql);
			if(empty($goods_info)){
				$result['result'] = 'failure';
				$result['note'] = "此串号码并未搜索到对应商品信息";
			}else{
				$goods_info['is_serial'] = 'Y';
				$goods_info['serial_number'] = $serial_number;
				$result['result'] = 'success';
				$result['goods_info'] = $goods_info;
			}
		}
	}
	echo json_encode($result);
	exit();
}else if($act=='storage'){ // 确认入库： 注意事务锁 ; 拒绝入库 
	//订单号是否存在
	$orderSn = $_POST['order_sn'];
	$innerCheck = $_POST['inner_check'];  //验货结果（pass/refuse）
	$trackingNumber = $_POST['tracking_number'] ; //退货快递单号
	$ReturnFacilityId = $_POST['return_facility_id'];  //受理仓库
	$applyReturnReason = $_POST['remark'];  //仓库备注（包含顾客退货原因）
	
	$sql = "select * from ecshop.warehouse_service where tracking_number = '{$trackingNumber}' " .
		" and warehouse_service_type = 'part' and warehouse_service_status = 'INIT' ";
	$warehouse_service = $db->getRow($sql);
	if(empty($warehouse_service)){
		sys_msg("快递单号".$trackingNumber."未匹配到客户自退已登记未验货信息，请刷新页面",1); 
	}else if(empty($orderSn) && $innerCheck!='receive'){//无订单号，则为提交
		sys_msg("无订单号，需通“提交”登记扫描商品信息",1); 
	}else if(!empty($orderSn) && $innerCheck=='receive'){//有订单号，则应 入库/拒绝
		sys_msg("有订单号，需通过“入库/拒绝”完成登记操作",1); 
	}
	$scanOrderGoods=$_POST['scan_order_goods'];  //扫描商品信息
	if(empty($scanOrderGoods)){
		sys_msg("没有接收到扫描商品信息",1); 
	}
	if($innerCheck=='receive'){ //无订单号,只负责登记商品信息，不考虑串号是否已经存在于系统一类的特殊情况
		
		/**
		 * 1. insert into ecshop.warehouse_service_goods
		 * 2. update ecshop.warehouse_service
		 */
		$party_id = 0;
		$db->start_transaction();
		$sql_values = array ();
    	$warehouse_service_id = $warehouse_service['warehouse_service_id'];
		foreach($scanOrderGoods as $rec_id=>$scanOrderGood){
			if($scanOrderGood['goods_back_num']==0){
				unset($scanOrderGoods[$rec_id]);
				continue;
			}else if($scanOrderGood['goods_back_num'] != count($scanOrderGood['goods_type']) ){
				sys_msg("扫描商品总数与商品新旧状态数量不等",1); 
			}else if($scanOrderGood['is_serial']=='Y' && $scanOrderGood['goods_back_num'] != count($scanOrderGood['serial_numbers'])){
				sys_msg("扫描的串号条码数量异常",1); 
			}else if($scanOrderGood['is_serial']=='Y'){ // 是串号，查串号对应商品信息是否一致
				$serial_number_strs = implode("','",array_values($scanOrderGood['serial_numbers']));
				$sql = "select product_id,count(distinct(serial_number)) as serial_num from romeo.inventory_item where serial_number in ('{$serial_number_strs}') group by product_id ";
				$serial_number_count = $db->getRow($sql);
				if(empty($serial_number_count)){
					sys_msg("根据串号条码未找到对应商品信息",1); 
				}else if($serial_number_count['serial_num'] != $scanOrderGood['goods_back_num']){
					sys_msg("串号条码数量异常",1); 
				}
				$sql = "select pm.ecs_goods_id as goods_id,pm.ecs_style_id as style_id,g.goods_party_id as party_id," .
					" IF(s.style_id IS NULL,g.goods_name, CONCAT_WS(',',g.goods_name,s.color) ) as product_name " .
					" from romeo.product_mapping pm " .
					" inner join romeo.product p on p.product_id = pm.product_id " .
					" left join ecshop.ecs_goods g on g.goods_id = pm.ecs_goods_id and g.is_delete = 0 " .
					" left join ecshop.ecs_style s on s.style_id = pm.ecs_style_id " .
					" where pm.product_id = '{$serial_number_count['product_id']}' limit 1 ";
				$goods_info = $db->getRow($sql);
				if(empty($goods_info)){
					sys_msg("没有找到商品信息！",1); 
				}else{
					$party_id = $goods_info['party_id'];
					foreach($scanOrderGood['serial_numbers'] as $key=>$serial_number){//串号商品，每个串号一条记录
						$goods_status = $scanOrderGood['goods_type'][$key];
						$sql_values[] = sprintf("('%d', '%d', '%d','%s','%d', '%s', '%s')", $warehouse_service_id, $goods_info['goods_id'], $goods_info['style_id'], $goods_info['product_name'],1,$goods_status,$serial_number);
					}
				}
			}else if($scanOrderGood['is_serial']=='N'){ //非串号商品
				$goods_sql = "select gs.goods_id,gs.style_id,g.goods_name from ecshop.ecs_goods_style gs left join ecshop.ecs_goods g on g.goods_id = gs.goods_id where gs.barcode = '{$scanOrderGood['barcode']}' and gs.is_delete=0 limit 1";
	    		$goods_info = $db->getRow($goods_sql);
	    		if(empty($goods_info)){
	    			$goods_sql = "select goods_id,0 as style_id,goods_name from ecshop.ecs_goods where barcode = '{$scanOrderGood['barcode']}' limit 1";
	    			$goods_info = $db->getRow($goods_sql);
	    		}
	    		if(empty($goods_info)){
	    			sys_msg("没有找到商品信息！",1); 
	    		}
				
				$old_number = 0;
    			$new_number = 0;
	    		foreach($scanOrderGood['goods_type'] as $goods_type){
	    			if($goods_type == 'INV_STTS_USED'){
	    				$old_number++;
	    			} else{
	    				$new_number++;
	    			}
	    		}
	    		if($old_number>0){
	    			$sql_values[] = sprintf("('%d', '%d', '%d', '%s', '%d', '%s', '%s')", 
	    			$warehouse_service_id,$goods_info['goods_id'],$goods_info['style_id'],$goods_info['goods_name'], $old_number, 'INV_STTS_USED', '');
	    		}
	    		if($new_number >0){
	    			$sql_values[] = sprintf("('%d', '%d', '%d', '%s', '%d', '%s', '%s')",  
	    			$warehouse_service_id,$goods_info['goods_id'],$goods_info['style_id'],$goods_info['goods_name'], $new_number, 'INV_STTS_AVAILABLE', '');
	    		}
			}
		}
		if(empty($sql_values)){
			sys_msg("扫描获取商品数量<1",1); 
		}else{
			$sql =  sprintf("insert into ecshop.warehouse_service_goods(warehouse_service_id,goods_id,style_id,goods_name,goods_number,goods_status,serial_number) " .
				" values %s", join(",", $sql_values));
			if(!$db->query($sql)){
				$db->rollback();
				sys_msg("商品登记插入失败！",1); 
			}
		}
		$sql = "update ecshop.warehouse_service set party_id ={$party_id} ,facility_id='{$ReturnFacilityId}',
			warehouse_service_status='CHECKED',checker_name='{$_SESSION['admin_name']}',check_time=now()
			where warehouse_service_id = {$warehouse_service['warehouse_service_id']}";
			
		if(!$db->query($sql)){
			$db->rollback();
			sys_msg("更新登记信息错误！",1); 
		}else{
			$db->commit();
			header("Location:warehouse_service.php");
		}
	}else{
		regist_back_goods_pass_reject($trackingNumber,$innerCheck,$warehouse_service,$orderSn,$scanOrderGoods,$ReturnFacilityId,$applyReturnReason);
	}
}

$smarty->assign('service_status',$warehouse_service_status);
$smarty->assign('facility_user_list',$facility_user_list);
$smarty->assign('tracking_number',$tracking_number);
$smarty->display('part_service.htm');


