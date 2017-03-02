<?php
/**
 * 菜鸟对接指示相关的函数封装
 * qhu 2015-6-11
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
	
	$mail->AddAddress("ljzhou@leqee.com", "ljzhou");
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
 function updateIndicateStatus($order_id, $logisticsStatus) {
 	global $db;
	$sql = "update ecshop.express_bird_indicate set logistics_status='{$logisticsStatus}',last_updated_stamp=now()
			where out_biz_code='{$order_id}' limit 1";
	
	$result = $db->query($sql);
	return $result;
 }
 
 /**
 * 指示完结后，完结订单状态
 */
 function overOrderStatus($batch_order_id) {
 	global $db;
	$sql = "select order_type_id from ecshop.ecs_order_info where order_id='{$batch_order_id}' limit 1";
	$order_type_id = $db->getOne($sql);
	
	$sql = "select og.order_id
			from 
			ecshop.express_bird_indicate i
			inner join ecshop.express_bird_indicate_detail id ON i.out_biz_code = id.out_biz_code 
			inner join ecshop.ecs_order_goods og ON id.order_goods_id = og.rec_id
			where i.out_biz_code = '{$batch_order_id}' ";
	$order_ids = $db->getCol($sql);
	if(empty($order_ids)) {
	    logRecord("overOrderStatus error:".$sql);
	    return false;
	}
	// logRecord($sql);
	if($order_type_id == 'PURCHASE') {
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
 * bird规则是actual只同步过来一次，实绩数量<=指示数量，只做一次就行了
 */
function check_number_rightful($batch_order_id){
    $product_nums = get_indicate_actual_inventory_info($batch_order_id,'LEFT');
    if(empty($product_nums)) return false;
    
    foreach($product_nums as $product_num) {      
    	//指示数量-已出入库数量>实绩中实际发货数量     即部分出库
  	   if($product_num['item_quantity'] - $product_num['done_number'] > $product_num['real_quantity']) {
          $message = 'batch_order_id:'.$batch_order_id.', 订单order_id:'.$product_num['order_id'].'部分出库或入库， 指示数量为：'.$product_num['item_quantity'].
           '， 已出入库数量为:'.$product_num['done_number'].'， 实绩中实际发货数量为:'.$product_num['real_quantity'];
  	 	  logRecord($message);
  	 	  // 允许一个订单的部分商品先出库
  	   }
    }
  
    return true;
}

/**
 * 判断订单是否出入库完成，要求和实绩一模一样，否则报错
 */
function check_order_inventory_done($batch_order_id){
    $product_nums = get_indicate_actual_inventory_info($batch_order_id,'INNER');
    if(empty($product_nums)) return false;
    
    foreach($product_nums as $product_num) {
    	if($product_num['status_id'] =='INV_STTS_AVAILABLE') {
	       if($product_num['done_number'] != $product_num['real_quantity']) {
	       	 $message = 'batch_order_id:'.$batch_order_id.'，订单号order_id:'.$product_num['order_id'].'， status_id:'.$product_num['status_id'].'， ERP中实际出入库的数量!=实绩表中的数量: '.
	           ' ERP中实际出入库的数量:'.$product_num['done_number'].'， 实绩表中的数量:'.$product_num['real_quantity'];
	  	 	  logRecord($message);
//	          send_indicate_mail("【BEST_INDICATE】【ERROR】",$message);
	          return false;
	  	   }
    	}
    	 

    }
  
	return true;
}

function get_indicate_actual_inventory_info($batch_order_id,$join) {
	global $db;
 	
	 //出入库角度判断
	 $sql =" select 
		 			d.item_quantity,pm.product_id,ifnull(ii.status_id,'') as status_id,
		 			ifnull(sum(abs(iid.quantity_on_hand_diff)),0) as done_number,
				    ifnull(ad.plan_quantity,0) as plan_quantity,
				    ifnull(ad.real_quantity,0) as real_quantity,
	 				i.out_biz_code as batch_order_id,og.order_id
				from 
					ecshop.express_bird_indicate i
					left join ecshop.express_bird_indicate_detail d ON i.out_biz_code = d.out_biz_code
					left join ecshop.ecs_order_goods og ON d.order_goods_id = og.rec_id
					left join ecshop.express_bird_actual_detail ad ON d.order_goods_id = ad.order_goods_id
					" .$join." join romeo.inventory_item_detail iid ON convert(d.order_goods_id using utf8) = iid.order_goods_id
					left join romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
					left join romeo.product_mapping pm ON d.ext_order_item_code = concat(convert(pm.ecs_goods_id using utf8), '_', convert(pm.ecs_style_id using utf8)) 
				where i.out_biz_code = '{$batch_order_id}' 
					and 
					(
						( i.order_type = 'NORMAL_IN' and i.order_sub_type = 'PURCHASE' and i.logistics_status in ('采购已全部入库','采购已部分入库') ) 
						or ( i.order_type = 'NORMAL_OUT' and i.order_sub_type = 'OTHER' and i.logistics_status in ('-gt已全部出库','-gt已部分出库') )
					)
					and ad.real_quantity > 0
				group by d.order_goods_id, ii.status_id
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
 		updateIndicateStatus($batch_order_id, 'ERP中已完成虚拟出入库');
 		// 如果是采购或者-gt的，需要把对应的表的状态完结掉
 		return overOrderStatus($batch_order_id);
 	} else {
 		logRecord('check_order_inventory_done fail to updateIndicateStatus batch_order_id:'.$batch_order_id);
 	}
 	
 	logRecord('update_indicate_actual_status end');
 	
 	return false;
 	
}






function birdExpressActualDoAction($inventory_type, $party_id) {
	
	logRecord("birdExpressActualDoAction begin");
	$indicate_types = array('PURCHASE INVENTORY_IN', 'SUPPLIER_RETURN');
	$_SESSION['admin_name'] = isset($_SESSION['admin_name'])?$_SESSION['admin_name']:'system';
	
	if(!in_array($inventory_type,$indicate_types)) {
		logRecord('inventory_type is error:'.$inventory_type);
		return false;
	}
	global $db;

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

    $lock_file_name = get_file_lock_path($lock_name, 'birdExpressActualDoAction');
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
//		logRecord($orders);
		$countAll = count($orders);
		$countSuccess = $countError = 0;
//		die();
		if ($orders) {
			foreach ($orders as $batch_order_id => $order_goods) {
	
				// 判断数量是否合法
				if (check_number_rightful($batch_order_id)) {
	
					logRecord('start do action:'.$inventory_type.' batch_order_id:'.$batch_order_id);
//					die();
//					continue;
					// 执行出入库
					$info = actual_inventory_action($inventory_type,$order_goods);
					if($info['res'] == 'success') {
						$countSuccess++;
						
						logRecord('success actual_inventory_action:'.$inventory_type.' batch_order_id:'.$batch_order_id);
//						die();
						
			 			// 更新指示状态
				    	update_indicate_actual_status($batch_order_id,$info);
					} else {
						$countError++;
						logRecord('batch_order_id:'.$batch_order_id.' actual_inventory_action fail:'.$info['back']);
					}
					
				} else {
					$countError++;
				}

			}
		}

	} catch(Exception $e) {
		$exception_mssage = "$inventory_type exception:".$e->getMessage();
		logRecord($exception_mssage);
	}
	
	flock($lock_file_point, LOCK_UN);
    fclose($lock_file_point);
	    
	logRecord("处理总实绩个数: {$countAll}, 成功个数: {$countSuccess}, 失败个数: {$countError}" . 
	          ", 耗时: " . (microtime(true)-$start));
	logRecord("birdExpressActualDoAction end");
	
}



function get_actual_todo_order_ids($party_id,$inventory_type) {
	global $db;
	$cond = '';
	if(!in_array($inventory_type,array('PURCHASE INVENTORY_IN','SUPPLIER_RETURN'))) {
		$cond = " limit 50";
	}
	// 获得采购订单和-gt订单的实绩
	$sql = "select 
	         i.out_biz_code
	        from ecshop.express_bird_indicate i 
	        inner join ecshop.express_bird_indicate_detail id ON i.out_biz_code = id.out_biz_code 
	        inner join ecshop.express_bird_actual_detail ad ON id.order_goods_id = ad.order_goods_id
	        inner join ecshop.ecs_order_goods og ON id.order_goods_id = og.rec_id
			where i.party_id = {$party_id}
			and 
			(
				( i.order_type = 'NORMAL_IN' and i.order_sub_type = 'PURCHASE' and i.logistics_status in ('采购已全部入库','采购已部分入库') ) 
				or ( i.order_type = 'NORMAL_OUT' and i.order_sub_type = 'OTHER' and i.logistics_status in ('-gt已全部出库','-gt已部分出库') )
			)
			and ad.real_quantity > 0
			group by i.out_biz_code
			$cond
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
	
	// 获得采购订单和-gt订单的实绩的具体信息
	$sql = "select 
		         i.out_biz_code as batch_order_id, oi.facility_id, i.party_id,gt.supplier_return_id, 
		         oi.shipping_status,oi.order_status,dd.item_quantity,og.order_id,
		         dd.order_goods_id, dd.ext_order_item_code as outer_id,
	             ifnull(ad.plan_quantity,0) as plan_quantity,
	             ifnull(ad.real_quantity,0) as real_quantity
	        from ecshop.express_bird_indicate i 
		        inner join ecshop.express_bird_indicate_detail dd ON i.out_biz_code = dd.out_biz_code 
		        inner join ecshop.express_bird_actual_detail ad ON dd.order_goods_id = ad.order_goods_id
		        inner join ecshop.ecs_order_goods og ON dd.order_goods_id = og.rec_id
				inner join ecshop.ecs_order_info oi ON og.order_id = oi.order_id
				left join romeo.supplier_return_request_gt gt ON oi.order_sn = gt.supplier_return_gt_sn
			where i.party_id = {$party_id}
				and 
				(
					( i.order_type = 'NORMAL_IN' and i.order_sub_type = 'PURCHASE' and i.logistics_status in ('采购已全部入库','采购已部分入库') ) 
					or ( i.order_type = 'NORMAL_OUT' and i.order_sub_type = 'OTHER' and i.logistics_status in ('-gt已全部出库','-gt已部分出库') )
				)
				and ad.real_quantity > 0
				and i.out_biz_code ".db_create_in($order_ids)."
				-- 没出库的
				and not exists (
							select ifnull(sum(abs(iid.quantity_on_hand_diff)),0) as out_num
					from romeo.inventory_item_detail iid 
					where iid.order_goods_id = convert(og.rec_id using utf8) 
					group by iid.order_goods_id
					having out_num >= og.goods_number
					limit 1
				)
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
	}
	
	if(empty($info['back'])) $info['res'] = 'success';

	return $info;
}
 
 function actual_inventory_single_action($inventory_type,$item) {

    $order_goods_serial_numbers = $serial_numbers = null;
    $goods_type = 'NON-SERIALIZED';// 先默认全部非串号
    $info['res'] = 'fail';
    
	if($inventory_type == 'PURCHASE INVENTORY_IN') {
		
		//采购订单自动入库
		global $facility_id,$inventory_status;
		$facility_id = $item['facility_id'];
		if($item['real_quantity']) {
			$inventory_status = 'INV_STTS_AVAILABLE';
			// order_id这里对应的批次号，所以用origin_order_id
			if($goods_type == 'NON-SERIALIZED') {
				logRecord($inventory_type. ' actual_inventory_single_action start：NON-SERIALIZED Goods');
				$info = actual_inventory_in_common($item['order_id'], $item['real_quantity'], true, $inventory_status, $item['facility_id'], 'ErpSyncBirdExpressCommand');
			} else {
				logRecord($inventory_type. ' actual_inventory_single_action start：SERIALIZED Goods');
				$info = actual_inventory_in_common($item['order_id'], 1, true, $inventory_status, $item['facility_id'], 'ErpSyncBirdExpressCommand',$order_goods_serial_numbers);
			}
		}
		
	} else if($inventory_type == 'SUPPLIER_RETURN') {
		//自动-gt出库
		
		if($item['real_quantity']) {
            $info = actual_supplier_return($item['party_id'], $item['supplier_return_id'], $item['real_quantity'],'system',$order_goods_serial_numbers);
		}

	}
	
	return $info;
 }
 
 ?>
 