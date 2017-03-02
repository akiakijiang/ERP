<?php
/**
 * 百世对接指示相关的函数封装
 * ljzhou 2014-12-20
 */
require_once(ROOT_PATH . 'admin/includes/lib_filelock.php');
	
function logRecord ($m) {
//	var_dump($m); return;
	if(is_array($m)) {
		print date("Y-m-d H:i:s"). "\r\n";
		var_dump($m);
	} else {
		print date("Y-m-d H:i:s") . " " . $m . "\r\n";
	}
}

 function send_indicate_mail($subject, $body = null, $path = null, $file_name = null) {
return;
 	require_once(ROOT_PATH . 'includes/helper/mail.php');
	$emailUsername= 'erp-report@leqee.com';
	$emailPassword= 'erpreport123';
	$mail=Helper_Mail::smtp();
	$mail->IsSMTP();                 // 启用SMTP
    $mail->Host="smtp.exmail.qq.com";      //smtp服务器 以126邮箱为例子
    $mail->SMTPAuth = true;         //启用smtp认证
    $mail->Username =$emailUsername;   // 你的邮箱地址
    $mail->Password = $emailPassword;      //你的邮箱密码  */
    $mail->CharSet='UTF-8';
	$mail->Subject="【BestIndicateCommand】" . $subject;
	$mail->SetFrom($emailUsername, '乐其网络科技');
	$mail->AddAddress("ytchen@leqee.com", "陈艳婷");	
	$mail->AddAddress("yfhu@leqee.com", "胡一帆");
	$mail->Body = date("Y-m-d H:i:s") . " " . $body;
	if($path != null && $file_name != null){
		$mail->AddAttachment($path, $file_name);
	}
	try {
		if ($mail->Send()) {
			//logRecord('mail send success');
	    } else {
	    	logRecord('mail send fail');
	    }
	} catch(Exception $e) {
		logRecord('mail send exception ' . $e->getMessage());
	}
}


/**
 * 修改指示状态
 */
 function updateIndicateStatus($order_id, $indicateStatus) {
 	global $db;
	$sql = "update ecshop.express_best_indicate set indicate_status='{$indicateStatus}',last_updated_stamp=now()
			where order_id='{$order_id}' limit 1";
	
	$result = $db->query($sql);
	return $result;
 }
 
 /**
 * 指示完结后，完结订单状态
 */
 function overOrderStatus($batch_order_id) {
 	global $db;
	$sql = "select order_type_id from ecshop.express_best_indicate where order_id='{$batch_order_id}' limit 1";
	$order_type_id = $db->getOne($sql);
	
	$sql = "select og.order_id
			from 
			ecshop.express_best_indicate i
			inner join ecshop.express_best_indicate_detail id ON i.order_id = id.order_id
			inner join ecshop.ecs_order_goods og ON id.order_goods_id = og.rec_id
			where i.order_id = '{$batch_order_id}' ";
	$order_ids = $db->getCol($sql);
	if(empty($order_ids)) {
	    logRecord("overOrderStatus error:".$sql);
	    return false;
	}
	// logRecord($sql);
	if($order_type_id == 'INVENTORY_IN') {
		$sql = "update ecshop.ecs_batch_order_mapping set is_over_c = 'Y' where order_id ".db_create_in($order_ids);
		$db->query($sql);
		$sql = "update romeo.purchase_order_info set over_time=now() where order_id ".db_create_in($order_ids);
		$db->query($sql);
		$sql = "update ecshop.ecs_batch_order_info set is_over_c = 'Y',batch_over_time=now() where batch_order_id ='{$batch_order_id}' limit 1";
		$db->query($sql);

	} else if($order_type_id == 'SUPPLIER_RETURN') {
		$sql = "select r.supplier_return_id
				from ecshop.ecs_order_info oi
				inner join romeo.supplier_return_request_gt gt ON oi.order_sn = gt.supplier_return_gt_sn
				inner join romeo.supplier_return_request r ON gt.supplier_return_id = r.supplier_return_id
				where oi.order_id ".db_create_in($order_ids);
	    //logRecord($sql);
		$supplier_return_ids = $db->getCol($sql);
		$sql = "update romeo.supplier_return_request set status = 'COMPLETION',last_update_stamp=now() where supplier_return_id ".db_create_in($supplier_return_ids);
		$db->query($sql);
	}
	return true;
 }
 

/**
 * 检查将要做的action的数量的准确性
 * best规则是actual只同步过来一次，实绩数量<=指示数量，只做一次就行了
 */
function check_number_rightful($batch_order_id){
    $product_nums = get_indicate_actual_inventory_info($batch_order_id,'LEFT');
    if(empty($product_nums)) return false;
    
    foreach($product_nums as $product_num) {
       if($product_num['normal_quantity'] + $product_num['defective_quantity'] != $product_num['to_do_goods_number']) {
  	 	  $message = 'batch_order_id:'.$batch_order_id.' order_id:'.$product_num['order_id'].' normal_quantity+defective_quantity != quantity normal_quantity'.$product_num['normal_quantity'].
           ' defective_quantity:'.$product_num['defective_quantity'].' quantity:'.$product_num['to_do_goods_number'];
  	 	  logRecord($message);
          send_indicate_mail("【BEST_INDICATE】【ERROR】",$message);
          return false;
  	   }
  	   if($product_num['item_quantity'] - $product_num['done_number'] < $product_num['to_do_goods_number']) {
          $message = 'batch_order_id:'.$batch_order_id.' order_id:'.$product_num['order_id'].' to_do_goods_number > left_number: item_quantity:'.$product_num['item_quantity'].
           ' done_number:'.$product_num['done_number'].' to_do_goods_number:'.$product_num['to_do_goods_number'];
  	 	  logRecord($message);
  	 	  // 允许一个订单的部分商品先出库
  	   }
    }
  
    return true;
}

/**
 * 判断订单是否出入库完成，要求和实绩一模一样,包含良品和不良品的数量，否则报错
 */
function check_order_inventory_done($batch_order_id){
    $product_nums = get_indicate_actual_inventory_info($batch_order_id,'INNER');
    if(empty($product_nums)) return false;
    
    foreach($product_nums as $product_num) {
    	if($product_num['status_id'] =='INV_STTS_AVAILABLE') {
	       if($product_num['done_number'] != $product_num['normal_quantity']) {
	       	 $message = 'batch_order_id:'.$batch_order_id.' order_id:'.$product_num['order_id'].' status_id:'.$product_num['status_id'].' done_number != normal_quantity: '.
	           ' done_number:'.$product_num['done_number'].' normal_quantity:'.$product_num['normal_quantity'];
	  	 	  logRecord($message);
	          send_indicate_mail("【BEST_INDICATE】【ERROR】",$message);
	          return false;
	  	   }
    	}
    	if($product_num['status_id'] =='INV_STTS_USED') {
    	   if($product_num['done_number'] != $product_num['defective_quantity']) {
	  	 	  $message = 'batch_order_id:'.$batch_order_id.' order_id:'.$product_num['order_id'].' status_id:'.$product_num['status_id'].'done_number != defective_quantity: '.
	           ' done_number:'.$product_num['done_number'].' defective_quantity:'.$product_num['defective_quantity'];
	  	 	  logRecord($message);
	          send_indicate_mail("【BEST_INDICATE】【ERROR】",$message);
	          return false;
	  	   }
    	}

    }
  
	return true;
}

function get_indicate_actual_inventory_info($batch_order_id,$join) {
	global $db;
 	
	 //出入库角度判断
	 $sql =" select d.item_quantity,pm.product_id,ifnull(ii.status_id,'') as status_id,
	 	ifnull(sum(abs(iid.quantity_on_hand_diff)),0) as done_number,
	    ifnull(ad.quantity,0) as to_do_goods_number,
	 	ifnull(ad.normal_quantity,0) as normal_quantity,
	 	ifnull(ad.defective_quantity,0) as defective_quantity,
	 	i.order_id as batch_order_id,og.order_id
		from 
		ecshop.express_best_indicate i use index(indicate_status)
		left join ecshop.express_best_indicate_detail d ON i.order_id = d.order_id
		left join ecshop.ecs_order_goods og ON d.order_goods_id = og.rec_id
		left join ecshop.express_best_actual_detail ad ON d.order_goods_id = ad.order_goods_id
		" .$join." join romeo.inventory_item_detail iid ON convert(d.order_goods_id using utf8) = iid.order_goods_id
		left join romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
		left join romeo.product_mapping pm ON d.goods_id = pm.ecs_goods_id and d.style_id = pm.ecs_style_id
		where i.order_id = {$batch_order_id} and i.indicate_status = 'RECEIVED' and ifnull(ad.quantity,0) > 0
		group by d.order_goods_id,ii.status_id
	 ";
	  $product_nums = $db->getAll($sql);
	  
	  return $product_nums;
}

/**
 * 更新实绩状态
 */
function update_indicate_actual_status($batch_order_id){
 	global $db;
 	logRecord('update_indicate_actual_status start');
 	
 	// 判断出入库是否都完成
 	// 
 	if(check_order_inventory_done($batch_order_id)) {
 		logRecord('check_order_inventory_done success to updateIndicateStatus batch_order_id:'.$batch_order_id);
 		updateIndicateStatus($batch_order_id, 'FINISHED');
 		// 如果是采购或者-gt的，需要把对应的表的状态完结掉
 		return overOrderStatus($batch_order_id);
 	} else {
 		logRecord('check_order_inventory_done fail to updateIndicateStatus batch_order_id:'.$batch_order_id);
 	}
 	logRecord('update_indicate_actual_status end');
 	
 	return false;
 	
}


/**
 * 通用自动出库，兼容串号
 * 
 */
function actual_inventory_out_common($item){
	global $db;
	echo "[" . date ( 'c' ) . "] " ." actual_inventory_out begin" ."\n";
	$info = array();
	if (!empty($item)) {
		$msg = null;
		//已配货待出库
		//先检查订单状态是否已配货否则先进行配货
		$sql = "select shipping_status from ecshop.ecs_order_info where order_id = {$item['order_id']} limit 1";
		$shipping_status = $db->getOne($sql);
		if ($shipping_status == 0) {
			$shipping_9 = update_shipping_status_common($item['order_id'], 9);
 			if ($shipping_9['msg'] != 'success') {
 				$info['res'] = 'fail';
 				$info['back'] =  "[" . date ( 'c' ) . "] " . "[ERROR]actual_inventory_out: {$item['order_id']} error: {$shipping_9['back']}" ."\n";
 				return $info;
 			}
		}
		
 		$info = check_out_goods_common($item);
 		var_dump($info);
		if ($info['msg'] != 'success') {
 			$msg = "check_out_goods: {$info['back']}";
			$info['res'] = 'fail';
			$info['back'] =  "[" . date ( 'c' ) . "] " . "[ERROR]actual_inventory_out: {$item['order_id']} error: {$msg}" ."\n";
			return $info;
		}
 		if (check_batch_out_storage_status($item['order_id'])) {
 			echo "[" . date ( 'c' ) . "] " ." check_batch_out_storage_status true" ."\n";
 			// 修改快递,追加面单
			$update_status = update_indicate_shipping_tracking_number($item['order_id']);
				
			//已出库待发货
 			$shipping_8 = update_shipping_status_common($item['order_id'], 8);
 			if ($shipping_8['msg'] == "success") {
 				//已发货
 				$shipping_result = update_shipping_status_common($item['order_id'], 1);
				echo "[" . date ( 'c' ) . "] " .$shipping_result['back'] ."\n";
				
 			} else {
 				echo "[" . date ( 'c' ) . "] " .$shipping_8['back'] ."\n";
 			}
 		} else {
 			echo "[" . date ( 'c' ) . "] " ." check_batch_out_storage_status false" ."\n";
 		}
		
	}
	echo "[" . date ( 'c' ) . "] " ." actual_inventory_out_common end" ."\n";
	$info['res'] = 'success';
	return $info;
}



/**
 * 更新快递方式，追加面单，以实绩传过来的快递为准
 */
function update_indicate_shipping_tracking_number($order_id) {
	global $db;
	$sql = "select pack_logistics_provider_code,pack_shipping_order_no,ifnull(pack_weight,0) as pack_weight from ecshop.express_best_actual_package where order_id = '{$order_id}'";
	$shipping_tracking_numbers = $db->getAll($sql);
    if(empty($shipping_tracking_numbers)) {
    	logRecord('shipping_tracking_numbers is null');
    	return false;
    }
    
    foreach($shipping_tracking_numbers as $key=>$shipping_tracking_number) {
    	$shipping_code = $shipping_tracking_number['pack_logistics_provider_code'];
    	$tracking_number = $shipping_tracking_number['pack_shipping_order_no'];
    	$pack_weight = $shipping_tracking_number['pack_weight']*1000;
    	
    	$shipping_id = get_best_mapping_shipping_id($shipping_code);
		if(empty($shipping_id)) {
			// 发送异常邮件
			$message = "order_id can not find shipping_id order_id:".$order_id." logistics_provider_code:".$shipping_code;
			send_indicate_mail("【BEST_INDICATE】【ERROR】",$message);
			return false;
		}
		$info = update_shipping ($order_id, $shipping_id);
		
		// 如果是多面单
		$sql = "select 1 from romeo.order_shipment os 
		left join romeo.shipment s ON os.shipment_id = s.shipment_id
        where os.order_id = '{$order_id}' and s.tracking_number = '{$tracking_number}' limit 1";
        $tracking_exists = $db->getOne($sql);
        if(empty($tracking_exists)) {
        	// 
        	$sql = "select shipment_id from romeo.order_shipment where order_id = '{$order_id}'  order by shipment_id limit 1";
        	$shipment_id = $db->getOne($sql);
        	if(empty($shipment_id)) {
        	    logRecord('order_id:'.$order_id.' tracking_not_exists:'.$tracking_number.' shipment_id is null');
        	    return false;
        	}
        	
        	// 如果是第一个面单，则更新，后面的追加
        	if($key == 0) {
        		$add_type = 'update';
        	} else {
        		$add_type = 'add';
        	}
        	logRecord('order_id:'.$order_id.' tracking_not_exists:'.$tracking_number.' add_type:'.$add_type);
//        	continue;
        	// 追加面单
        	$info = add_order_shipment($shipment_id,$tracking_number,$add_type,$pack_weight);
        	if($info['res'] !='success') {
        		logRecord('order_id:'.$order_id.'add_order_shipment fail:'.$info['message']);
        	}
        } else {
        	logRecord('order_id:'.$order_id.' tracking_exists:'.$tracking_number);
        }
    }
    
    if($info['res'] !='success') {
		logRecord('order_id:'.$order_id.'update_shipping fail:'.$info['message']);
	}

}


/**
 *  HTKY：汇通
	STO：圆通
	YUNDA：韵达
	SF：顺丰
	EMS：EMS
	SF-COD：顺丰COD
	YTO-COD：圆通COD
	
	只能发 ems 汇通 圆通
 */
function get_best_mapping_shipping_id($shipping_code) {
	if(empty($shipping_code)) return null;
	$best_shipping_mapping = array(
		'SF'=>'44',//顺丰快递
		'SF'=>'117',// 顺丰（陆运）
		'SF-COD'=>'121',//顺丰空运—淘宝COD
		'SF-COD'=>'122',//顺丰陆运—淘宝COD
		'SF-COD'=>'127',//顺丰—到付
		'EMS'=>'47',//EMS快递
		'YTO'=>'85',//圆通快递
		'STO'=>'89',//圆通快递
		'HTKY'=>'99',//汇通快递
		'YUNDA'=>'100',//韵达快递
		'EMS'=>'118',//EMS经济快递
		'ZTO'=>'115',//EMS经济快递
		'ZJS'=>'12',//宅急送
		'YUNDA'=>'100',//韵达快运
		'EMS'=>'47',//EMS
		'EYB'=>'118',//EMS经济快递
	);

	if(isset($best_shipping_mapping[$shipping_code])) {
		return $best_shipping_mapping[$shipping_code];
	} else {
		$message = 'shipping_code can not find best_shipping_mapping'.$shipping_code;
  	 	logRecord($message);
        send_indicate_mail("【BEST_INDICATE】【ERROR】",$message);
	}
	
	return null;
}
function bestActualClosedDoAction($inventory_type){
	logRecord("bestActualClosedDoAction begin");
	$indicate_types = array('INVENTORY_IN','SUPPLIER_RETURN');
	$_SESSION['admin_name'] = isset($_SESSION['admin_name'])?$_SESSION['admin_name']:'system';
	
	if(!in_array($inventory_type,$indicate_types)) {
		logRecord('inventory_type is error:'.$inventory_type);
		return false;
	}
	global $db;
	$party_id = 65625;

	$start = microtime(true);
	$countAll = 0;
	
	ini_set('default_socket_timeout', 2400);
	
	// 加锁
	$lock_name = $inventory_type;

    $lock_file_name = get_file_lock_path($lock_name, 'bestActualClosedDoAction');
    $lock_file_point = fopen($lock_file_name, "w+");
    $would_block = false;
    if(!flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
    	fclose($lock_file_point);
    	logRecord("上次操作还在进行，请稍后 inventory_type:".$lock_name);
    	return false;
    }
	    
	try {
	
		// 获得实绩
		$orders = get_closed_actual_todo_orders($party_id,$inventory_type);
//		logRecord($orders);
		$countAll = count($orders);
		$countSuccess = $countError = 0;
		if ($orders) {
			global $db;
			foreach ($orders as $batch_order_id=>$order) {
				if($inventory_type =='SUPPLIER_RETURN'){
					foreach($order as $key=>$value){
						$supRetReqId = $order[$key]['supplier_return_id'];
						$sql = "update romeo.supplier_return_request 
		     	            set check_status = 'DENY',check_user = 'system'
		     	            where supplier_return_id = '{$supRetReqId}'";
	     	            if(!$db->query($sql)){
	     	            	$countError++;
	     	            	continue 2;
	     	            }
					}
				}else if($inventory_type =='INVENTORY_IN'){
					foreach($order as $key=>$value){
						if($order[$key]['is_over_c']=='N'){
							if(overOrderStatus($batch_order_id)){
								break;
							}
						}
					}
				}
 	        	updateIndicateStatus($batch_order_id, 'FINISHED');
 	        	$countSuccess++;
			}
		}

	} catch(Exception $e) {
		$exception_mssage = "$inventory_type exception:".$e->getMessage();
		send_indicate_mail("【BEST_INDICATE_CLOSED】【EXCEPTION】",$exception_mssage);
		logRecord($exception_mssage);
	}
	
	flock($lock_file_point, LOCK_UN);
    fclose($lock_file_point);
	    
	logRecord("总实绩个数: {$countAll},成功个数: {$countSuccess},失败个数: {$countError}" . 
	          "耗时: " . (microtime(true)-$start));
	logRecord("bestActualClosedDoAction end");
	
}	

function bestActualDoAction($inventory_type) {
	logRecord("bestActualDoAction begin");
	$indicate_types = array('INVENTORY_OUT', 'INVENTORY_IN', 'INVENTORY_RETURN', 'SUPPLIER_RETURN');
	$_SESSION['admin_name'] = isset($_SESSION['admin_name'])?$_SESSION['admin_name']:'system';
	
	if(!in_array($inventory_type,$indicate_types)) {
		logRecord('inventory_type is error:'.$inventory_type);
		return false;
	}
	global $db;
	$party_id = 65625;

	$start = microtime(true);
	$countAll = 0;
	$countIndicateFinish = 0;
	$countActualFinish = 0;
	$countActualError = 0;
	$countActualDetailFinish = 0;
	$countActualDetailError = 0;
	
	ini_set('default_socket_timeout', 2400);
	
	// 加锁
	$lock_name = $inventory_type;

    $lock_file_name = get_file_lock_path($lock_name, 'bestActualDoAction');
    $lock_file_point = fopen($lock_file_name, "w+");
    $would_block = false;
    if(!flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
    	fclose($lock_file_point);
    	logRecord("上次操作还在进行，请稍后 inventory_type:".$lock_name);
    	return false;
    }
	    
	try {
	
		// 获得实绩
		$orders = get_actual_todo_orders($party_id,$inventory_type);
// 		logRecord($orders);
		$countAll = count($orders);
		$countSuccess = $countError = 0;
		if ($orders) {
			foreach ($orders as $batch_order_id => $order_goods) {
	
				// 判断数量是否合法
				if (check_number_rightful($batch_order_id)) {
	
					logRecord('start do action:'.$inventory_type.' batch_order_id:'.$batch_order_id);//die();
//					continue;
					// 执行出入库
					$info = actual_inventory_action($inventory_type,$order_goods);
					if($info['res'] == 'success') {
						$countSuccess++;

			 			// 更新指示状态	
				    	update_indicate_actual_status($batch_order_id,$info);
					} else {
						$countError++;
						logRecord('batch_order_id:'.$batch_order_id.' actual_inventory_action fail:'.$info['back']);
						send_indicate_mail("【BEST_INDICATE】【ERROR】",'batch_order_id:'.$batch_order_id.' actual_inventory_action fail:'.$info['back']);
					}
					
				} else {
					$countError++;
				}

			}
		}

	} catch(Exception $e) {
		$exception_mssage = "$inventory_type exception:".$e->getMessage();
		send_indicate_mail("【BEST_INDICATE】【EXCEPTION】",$exception_mssage);
		logRecord($exception_mssage);
	}
	
	flock($lock_file_point, LOCK_UN);
    fclose($lock_file_point);
	    
	logRecord("总实绩个数: {$countAll},成功个数: {$countSuccess},失败个数: {$countError}" . 
	          "耗时: " . (microtime(true)-$start));
	logRecord("bestActualDoAction end");
	
}

/**
 * 得到还未出入库过的该订单某商品剩余的串号列表
 */
function get_to_do_serial_numbers($item) {
	global $db;
	
	$sql = "select ad.order_goods_id,a.sn_code
	        from ecshop.express_best_actual_detail ad 
	        left join ecshop.express_best_actual_sncode a ON ad.order_id = a.order_id and ad.sku_code = a.sku_code
            where ad.order_id = '{$item['order_id']}'
	        and not exists (select 1 from romeo.inventory_item
	          where serial_number=a.sn_code and quantity_on_hand_total > 0 
	          and status_id in('INV_STTS_AVAILABLE','INV_STTS_USED') limit 1)
	        group by ad.order_goods_id,a.sn_code";
	logRecord($sql);
	$serial_numbers = $db->getAll($sql);
	$result = array();
	if(!empty($serial_numbers)) {
		foreach($serial_numbers as $serial_number) {
			$result[$serial_number['order_goods_id']][] = $serial_number['sn_code'];
		}
	}
	logRecord($result);

	return $result;
}

function get_actual_todo_order_ids($party_id,$inventory_type) {
	global $db;
	$cond = '';
	if(!in_array($inventory_type,array('PURCHASE','SUPPLIER_RETURN'))) {
		$cond = " limit 950";
	}
	// 获得实绩
	$sql = "select 
	         i.order_id
	        from ecshop.express_best_indicate i use index(indicate_status)
	        inner join ecshop.express_best_indicate_detail id ON i.order_id = id.order_id 
	        inner join ecshop.express_best_actual_detail ad ON id.order_goods_id = ad.order_goods_id
	        inner join ecshop.ecs_order_goods og ON id.order_goods_id = og.rec_id
			where i.party_id = {$party_id}
			and i.order_type_id = '{$inventory_type}'
			and i.indicate_status = 'RECEIVED' and ifnull(ad.quantity,0) > 0
			-- and i.order_id in(6658137,12326008)
			group by i.order_id
			$cond
		";
//	logRecord($sql);
	$order_ids = $db->getCol($sql);
	
	return $order_ids;
}

function get_closed_actual_todo_order_ids($party_id,$inventory_type) {
	global $db;
	// 获得实绩
	$sql = "select 
	         i.order_id
	        from ecshop.express_best_indicate i use index(indicate_status)
	        inner join ecshop.express_best_indicate_detail id ON i.order_id = id.order_id 
	        inner join ecshop.express_best_actual_detail ad ON id.order_goods_id = ad.order_goods_id
	        inner join ecshop.ecs_order_goods og ON id.order_goods_id = og.rec_id
			where i.party_id = '{$party_id}'
			and i.order_type_id = '{$inventory_type}' 
			and i.indicate_status = 'CLOSED' and ad.quantity=0
			group by i.order_id
			limit 50
		";
//	logRecord($sql);
	$order_ids = $db->getCol($sql);
	
	return $order_ids;
}


function get_actual_todo_orders($party_id,$inventory_type) {
	global $db;
	// 得到指示order_id，批次号级别
	$order_ids = get_actual_todo_order_ids($party_id,$inventory_type);
	if(empty($order_ids)) return null;
	logRecord("指示order_ids，批次号级别数量  : " .count($order_ids));
	// 获得实绩
	$sql = "select 
	         i.order_id as batch_order_id, i.facility_id,i.party_id,gt.supplier_return_id,i.service_id,
	         oi.shipping_status,oi.order_status,dd.item_quantity,og.order_id,
	         dd.order_goods_id,dd.goods_id,dd.style_id,ad.sku_code,
             ifnull(ad.quantity,0) as quantity,
             ifnull(ad.normal_quantity,0) as normal_quantity,
             ifnull(ad.defective_quantity,0) as defective_quantity
        from ecshop.express_best_indicate i use index(indicate_status)
        inner join ecshop.express_best_indicate_detail dd ON i.order_id = dd.order_id 
        inner join ecshop.express_best_actual_detail ad ON dd.order_goods_id = ad.order_goods_id
        inner join ecshop.ecs_order_goods og ON dd.order_goods_id = og.rec_id
        inner join ecshop.ecs_order_info oi ON og.order_id = oi.order_id
        left join romeo.supplier_return_request_gt gt ON oi.order_sn = gt.supplier_return_gt_sn
		where i.party_id = {$party_id}
		and i.order_type_id = '{$inventory_type}'
		and i.indicate_status = 'RECEIVED' and ifnull(ad.quantity,0) > 0
		and i.order_id ".db_create_in($order_ids)."
		-- 没出库的
		and not exists (
	        select ifnull(sum(abs(iid.quantity_on_hand_diff)),0) as out_num
			from romeo.inventory_item_detail iid 
			where iid.order_goods_id = convert(og.rec_id using utf8) 
			group by iid.order_goods_id
			having out_num >= og.goods_number
			limit 1
		)
		group by og.rec_id  limit 500
	";
//	logRecord($sql);
	$ref_keys = $ref_values = array();
	$db->getAllRefBy($sql,array('batch_order_id'),$ref_keys,$ref_values);
	$orders = $ref_values['batch_order_id'];
	
	return $orders;
}

function get_closed_actual_todo_orders($party_id,$inventory_type) {
	global $db;
	// 得到指示order_id，批次号级别
	$order_ids = get_closed_actual_todo_order_ids($party_id,$inventory_type);
	if(empty($order_ids)) return null;
	
	// 获得实绩
	$sql = "select 
	         i.order_id as batch_order_id,og.order_id,gt.supplier_return_id,bom.is_over_c
        from ecshop.express_best_indicate i use index(indicate_status)
        inner join ecshop.express_best_indicate_detail dd ON i.order_id = dd.order_id 
        inner join ecshop.express_best_actual_detail ad ON dd.order_goods_id = ad.order_goods_id
        inner join ecshop.ecs_order_goods og ON dd.order_goods_id = og.rec_id
        inner join ecshop.ecs_order_info oi ON og.order_id = oi.order_id
        left join romeo.supplier_return_request_gt gt ON oi.order_sn = gt.supplier_return_gt_sn
        left join ecshop.ecs_batch_order_mapping bom on bom.batch_order_id = i.order_id
		where i.party_id = '{$party_id}' 
		and i.order_type_id = '{$inventory_type}'
		and i.indicate_status = 'CLOSED' and ad.quantity= 0
		and i.order_id ".db_create_in($order_ids)."
		group by og.rec_id
	";
//	logRecord($sql);
	$ref_keys = $ref_values = array();
	$db->getAllRefBy($sql,array('batch_order_id'),$ref_keys,$ref_values);
	$orders = $ref_values['batch_order_id'];
	
	return $orders;
}

/**
 * 出入库动作
 */
function actual_inventory_action($inventory_type,$order_goods) {
	if(empty($inventory_type) || empty($order_goods)) return null;
	$info['res'] = 'fail';
	$info['back'] = '';
	foreach($order_goods as $item) {
		$result = actual_inventory_single_action($inventory_type,$item);
		if($result['res']=='fail') {
			$info['back'] .= $result['back']." \n";
		}
		
		// 退货只做一次，因为是order_id级别的
		if($inventory_type == 'INVENTORY_RETURN') {
		    break;
		}
	}
	
	if(empty($info['back'])) $info['res'] = 'success';

	return $info;
}
 
 function actual_inventory_single_action($inventory_type,$item) {

    $order_goods_serial_numbers = $serial_numbers = null;
    $goods_type = 'NON-SERIALIZED';// 先默认全部非串号
    $info['res'] = 'fail';
    
	if($inventory_type == 'INVENTORY_IN') {
		global $facility_id,$inventory_status;
		$facility_id = $item['facility_id'];
		//自动入库
		if($item['normal_quantity']) {
			$inventory_status = 'INV_STTS_AVAILABLE';
			// order_id这里对应的批次号，所以用origin_order_id
			if($goods_type == 'NON-SERIALIZED') {
				$info = actual_inventory_in_common($item['order_id'], $item['normal_quantity'], true, $inventory_status, $item['facility_id'], 'BestIndicateCommand');
			} else {
				$info = actual_inventory_in_common($item['order_id'], 1, true, $inventory_status, $item['facility_id'], 'BestIndicateCommand',$order_goods_serial_numbers);
			}
		}
		
		if($item['defective_quantity']) {
			$inventory_status = 'INV_STTS_USED';
			if($goods_type == 'NON-SERIALIZED') {
				$info = actual_inventory_in_common($item['order_id'], $item['defective_quantity'], true, $inventory_status, $item['facility_id'], 'BestIndicateCommand',$order_goods_serial_numbers);
			} else {
				$info['back'] = "INVENTORY_IN serial can not has defective_quantity,order_id:".$item['order_id'];
				return $info;
			}
		}
		
	} else if($inventory_type == 'INVENTORY_OUT') {

        // 销售订单的指示的数量和实绩的数量必须一致
    	if($item['item_quantity'] != $item['quantity']) {
            $message = "INVENTORY_OUT item_quantity != quantity,order_id:".$item['order_id'];
			$info['back'] = $message;
	  	 	logRecord($message);
	        send_indicate_mail("【BEST_INDICATE】【ERROR】",$message);
			return $info;
    	}
//		logRecord($item);return;
		if($item['normal_quantity']) {
			$item['status_id'] = 'INV_STTS_AVAILABLE';
			$item['goods_number'] = $item['normal_quantity'];
			$item['serial_numbers'] = isset($serial_numbers[$item['order_goods_id']])?$serial_numbers[$item['order_goods_id']]:array('');

            if($goods_type == 'SERIALIZED') {
            	// 销售订单的指示的数量和实绩的数量必须一致
            	if(count($item['serial_numbers']) != $item['quantity']) {
			        $info['back'] = "INVENTORY_OUT serial_numbers != quantity,order_goods_id:".$item['order_goods_id'];
					return $info;
            	}
            }
			$info = actual_inventory_out_common($item);
		}
		
		if($item['defective_quantity']) {
			$item['status_id'] = 'INV_STTS_USED';
			$item['goods_number'] = $item['defective_quantity'];
			$item['serial_numbers'] = isset($serial_numbers[$item['order_goods_id']])?$serial_numbers[$item['order_goods_id']]:array('');

            if($goods_type == 'SERIALIZED') {
            	// 销售订单的指示的数量和实绩的数量必须一致
            	if(count($item['serial_numbers']) != $item['quantity']) {
			        $info['back'] = "INVENTORY_OUT serial_numbers != quantity,order_goods_id:".$item['order_goods_id'];
					return $info;
            	}
            }
			$info = actual_inventory_out_common($item);
		}

		
	} else if($inventory_type == 'INVENTORY_RETURN') {
		//自动售后退货入库
		$info = actual_service($item['service_id']);

		
	} else if($inventory_type == 'SUPPLIER_RETURN') {
		//自动-gt出库
		//TODO 新旧不能一起，因为一个supplier_return_id只有一种新旧属性
		if($item['normal_quantity'] && $item['defective_quantity']) {
			send_indicate_mail("【BEST_INDICATE】【ERROR】","SUPPLIER_RETURN can't has normal_quantity and defective_quantity at the same time! supplier_return_id: ".$item['supplier_return_id']);
			
			$info['back'] = "SUPPLIER_RETURN can't has normal_quantity and defective_quantity at the same time! supplier_return_id: ".$item['supplier_return_id'];
			return $info;
		}
		
		if($item['normal_quantity']) {
            $info = actual_supplier_return($item['party_id'], $item['supplier_return_id'], $item['normal_quantity'],'system',$order_goods_serial_numbers);
		}
		
		if($item['defective_quantity']) {
			$info = actual_supplier_return($item['party_id'], $item['supplier_return_id'], $item['defective_quantity'],'system',$order_goods_serial_numbers);
		}

	}
	
	return $info;
 }
 
 ?>
 