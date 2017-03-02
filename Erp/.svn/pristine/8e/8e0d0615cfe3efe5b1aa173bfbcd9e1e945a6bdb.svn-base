<?php
define('IN_ECS', true);

require_once('includes/init.php');
require_once('includes/lib_common.php');
require_once('includes/lib_service.php');
require_once('function.php');

require_once('includes/lib_postsale_cache.php');

admin_priv('customer_service_manage_order');

$act = $_REQUEST['act'];
$datetime = date("Y-m-d H:i:s", time());
extract($_POST);

if ($act == 'get_order_goods_item') { // 售后服务获取订单商品列表，updated by zjli at 2014.1.23（废老库存）
  
    $sql = "SELECT order_id, order_sn, order_status, shipping_status FROM {$ecs->table('order_info')} WHERE order_sn = '{$order_sn}' LIMIT 1 ";
    $order = $db->getRow($sql);
    $order_goods = array();
    
    if ($order['order_status'] == 0) {
        $order['status_text'] = '<span style="margin-left:30px;color:red;">订单未确认，请勿操作售后。</span>';
    }
    elseif ($order['order_status'] == 2) {
        $order['status_text'] = '<span style="margin-left:30px;color:red;">订单已经取消</span>';
    }
    elseif ($order['order_status'] == 4) {
        $order['status_text'] = '<span style="margin-left:30px;color:red;">该订单已拒收。</span>';
    }
    elseif($order['order_status'] == 1 && in_array($order['shipping_status'], array(0,8,9,10,12,13))){
    	$order['status_text'] = '<span style="margin-left:30px;color:red;">该订单还未发货。</span>';
    }
    elseif ($order['order_status'] == 1 && ($order['shipping_status'] == 2 || $order['shipping_status'] == 6)) {
        $order['status_text'] = '<span style="margin-left:30px;color:green;">成功交易订单</span>';
        $order_goods = get_order_goods_item($order_sn);  // updated by zjli at 2014.01.20: 添加了对商品售后申请情况的查询
    }
    elseif ($order['order_status'] == 1 && $order['shipping_status'] == 1) {
        $order['status_text'] = '<span style="margin-left:30px;color:green;">已发货，商品正在运送途中，可以申请价保</span>';
        $order_goods = get_order_goods_item($order_sn);  // updated by zjli at 2014.01.20: 添加了对商品售后申请情况的查询
    }
    // added by zjli at 2014.05.14 : 添加外包发货逻辑
    elseif ($order['order_status'] == 11 && in_array($order['shipping_status'], array(0,8,9,10,12,13))) {
        $order['status_text'] = '<span style="margin-left:30px;color:green;">该订单还未发货。</span>';
    }
    elseif ($order['order_status'] == 11 && ($order['shipping_status'] == 2 || $order['shipping_status'] == 6)) {
        $order['status_text'] = '<span style="margin-left:30px;color:green;">成功交易订单</span>';
        $order_goods = get_order_goods_item($order_sn);
    }
    elseif ($order['order_status'] == 11 && $order['shipping_status'] == 1) {
        $order['status_text'] = '<span style="margin-left:30px;color:green;">已发货（外包发货）</span>';
        $order_goods = get_order_goods_item($order_sn);
    }
    
    $sql1 = "select facility_name
            from ecshop.ecs_order_info as oi
            left join romeo.facility as rf on oi.facility_id = rf.facility_id
            where oi.order_sn = '{$order_sn}'";
    //原订单发货仓库
    $origin_facility_name = $db->getAll($sql1);
    
    $facility_list = get_available_facility($_SESSION['party_id']);
    $facility_list1 = join(",",$facility_list);
    $facility_list2 = "'".str_replace(",","','",$facility_list1)."'";
    
   $sql2 = "select facility_id,facility_name
            from romeo.facility
            where facility_name in($facility_list2)";
   //可用的受理仓库列表
    $available_facility = $db->getAll($sql2); 
    
    $json_result = array (
    'order_goods' => $order_goods,
    'order' => $order,
    'service_id' => $service_id,
    'origin_facility_name' => $origin_facility_name,
    'facility_list' => $available_facility
    );

}
elseif ($act == 'apply') {  // 建立售后服务的申请
    apply_service();
    exit ();
}
elseif ($act == 'edit_service_return') {  // 修改退款帐号信息
    $service_return = array ();
    foreach ($service_return_key_mapping as $key => $val) {
        if ($_POST[$key]) {
            $service_return[] = "('{$service_id}', '{$key}', '$_POST[$key]','bank_info')";
        }
    }
    if (count($service_return)) {
        $sql = "DELETE FROM service_return WHERE service_id = '{$service_id}' AND return_type = 'bank_info' ";
        $db->query($sql);
        $sql = "INSERT INTO service_return( service_id, return_name, return_value, return_type) VALUES " . join(",", $service_return);
        $db->query($sql);
        $json_result = $service_return;
    }
    header("Location:" . $back_url);
}
elseif ($act == 'update_service_facility') { // 修改受理仓库
    $sql = " SELECT * FROM service WHERE service_id = '{$service_id}' ";
    $service = $db->getRow($sql);
    $facility_id = $_POST['facility_id'];
    if ($facility_id != $service['facility_id']) {
        $sql = "UPDATE service SET facility_id = '{$facility_id}' 
                WHERE service_id = '{$service_id}' ";
        $db->query($sql);
    }

    //SINRI UPDATE POSTSALE CACHe
    POSTSALE_CACHE_updateService(null,180,$service_id);

    header("Location:" . $back_url);
    exit();
}
elseif ($act == 'update_service_goods_amount'){  // 修改售后服务商品数量
	if(!empty($service_order_goods)){
		foreach ($service_order_goods as $service_order_good){
			// 这里选择最新的一条记录是为了兼容老系统的售后服务商品数据
			$sql = "SELECT max(service_order_goods_id) FROM ecshop.service_order_goods WHERE service_id = '{$service_id}' 
						AND order_goods_id = '{$service_order_good['order_goods_id']}'";
			$service_order_goods_id = $db->getOne($sql);
			
			$sql = "UPDATE ecshop.service_order_goods SET amount = '{$service_order_good['service_amount']}'
						WHERE service_order_goods_id = '{$service_order_goods_id}'";
			$db->query($sql);
		}
	}
	header("Location:" . $back_url);
    exit();
}
elseif ($act == 'edit_service_shipping') { //更新售后寄回地址
    if ($service_goods_shipping_id == 0) {
        $sql = " INSERT INTO service_goods_shipping
            (service_id, consignee, address, mobile, tel, zipcode, email, remark, carrier_name)
            VALUES ('{$service_id}', '{$consignee}', '{$address}', '{$mobile}', '{$tel}',
             '{$zipcode}', '{$email}', '{$remark}', '{$carrier_name}') ";
    } else {
        if ($warranty_shipping_status) {
            // 修改快递信息
            $sql_warranty_shipping_status = " UPDATE service 
                SET warranty_shipping_status = '{$warranty_shipping_status}'
                WHERE service_id = '{$service_id}' LIMIT 1 ";
            $db->query($sql_warranty_shipping_status); 
            $sql = " SELECT * FROM service WHERE service_id = '{$service_id}' ";
            
            $service = $db->getRow($sql);
            $status_name = service_status($service);
            
            //售后日志
            $log_note = "{$_SESSION['admin_name']} 寄回用户";
            $service['log_note'] = $log_note;
            $service['log_type'] = 'LOGISTIC';
            service_log($service);
        }
        $sql = " UPDATE service_goods_shipping 
            SET carrier_no = '{$carrier_no}' 
            WHERE service_goods_shipping_id = '{$service_goods_shipping_id}'";
    }
    $db->query($sql);
    header("Location:" . $back);
    exit();
}
//确认完成返修，寄回
elseif ($act == 'warranty_service_shipping') {
    $sql = " UPDATE service SET service_status = '2'
        WHERE service_id = '{$service_id}' LIMIT 1 ";
    $db->query($sql);
    $sql = " SELECT * FROM service WHERE service_id = '{$service_id}' ";
    $service = $db->getRow($sql);
    $status_name = service_status($service);

    //售后日志
    $log_note = "{$_SESSION['admin_name']} 确认完成返修，寄回用户";
    $service['log_note'] = $log_note;
    $service['log_type'] = 'CUSTOMER_SERVICE';
    service_log($service);

    //SINRI UPDATE POSTSALE CACHe
    POSTSALE_CACHE_updateService(null,180,$service_id);

    header("Location:" . $back);
    exit();
}
/**
 * 回复售后留言
 */
elseif ($act == 'reply') {
    // review_point 只有在第一次回复才更新
    $service_id = intval($service_id);
    $review_remark = strip_tags($review_remark);
    $sql = "
        	UPDATE service SET 
          	  review_remark = '{$review_remark}', 
          	  review_username = '{$_SESSION['admin_name']}', 
          	  review_datetime = '{$datetime}',
        	  review_point = IF(review_point = 0 OR review_point IS NULL, '{$datetime}', review_point)
        	WHERE service_id = '{$service_id}' LIMIT 1 
          ";
    $db->query($sql);

    $sql = "SELECT * FROM service WHERE service_id = '{$service_id}' LIMIT 1 ";
    $json_result = $db->getRow($sql);

    //SINRI UPDATE POSTSALE CACHe
    POSTSALE_CACHE_updateService(null,180,$service_id);
}

/**
 * 回复 售后咨询
 */
elseif ($act == 'reply_comment') {
    // replied_point 只有在第一次回复才更新
    $service_comment_id = intval($service_comment_id);
    $reply = strip_tags($reply);
    $sql = "
          	UPDATE service_comment SET 
        	  reply = '{$reply}', 
        	  replied_username = '{$_SESSION['admin_name']}', 
        	  replied_datetime = '{$datetime}',
        	  replied_point = IF(replied_point = 0 OR replied_point IS NULL, '{$datetime}', replied_point)
            WHERE service_comment_id = '{$service_comment_id}' LIMIT 1 
          ";
    $db->query($sql);

    $sql = "SELECT * FROM service_comment WHERE service_comment_id = '{$service_comment_id}' LIMIT 1 ";
    $json_result = $db->getRow($sql);
}

//修改售后档案
elseif ($act == 'edit_track') {
    require_once(ROOT_PATH."RomeoApi/lib_RMATrack.php");
    $trackId = $_REQUEST['trackId'];
    $result = getTrackByTrackId($trackId);
    $track = $result->resultList->Track;
    $track->brandName = $_REQUEST['brandName'];
    $track->productName = $_REQUEST['productName'];
    $track->serialNumber = $_REQUEST['serialNumber'];
    $track->contactInfo = $_REQUEST['contactInfo'];
    $track->customerName = $_REQUEST['customerName'];
    updateTrack($track);
    $back = $_POST['back'] ? $_POST['back'] : $_SERVER['HTTP_REFERER'];
    header("Location:".$back);
}
//删除"添加备注功能"
/* elseif ($act == 'remark') {
    $sql = "SELECT * FROM service WHERE service_id = '{$service_id}' ";
    $service = $db->getRow($sql);
    $service['log_note'] = $remark;
    $service['log_type'] = 'CUSTOMER_SERVICE';
    $service['is_remark'] = 1;
    service_log($service);
    $json_result = $service;
} */

elseif ($act == 'track') {
    $sql = " SELECT * FROM service WHERE service_id = '{$service_id}' ";
    $service = $db->getRow($sql);
    $service_type_name = $_CFG['adminvars']['service_type_mapping'][$service['service_type']];
    $status_name = service_status($service);

    //售后跟踪记录
    $sql = "INSERT INTO service_track (service_id, order_id, type_name, status_name, track_report, track_result, track_username, track_datetime)
                              values ('{$service_id}', '{$service['order_id']}', '{$service_type_name}', '{$status_name}', '{$track_report}', '{$track_result}', '{$_SESSION['admin_name']}', '$datetime' )   ";
    $db->query($sql);

    $service_track_id = $db->insert_id();
    $sql = "SELECT * FROM service_track WHERE service_track_id = '{$service_track_id}' LIMIT 1 ";

    $json_result = $db->getRow($sql);
}
elseif ($act == 'update_status') {  // 售后服务审核，updated by zjli at 2014.1.23(废老库存)
    $sql = " SELECT * FROM service WHERE service_id = '{$service_id}' ";
    $service = $db->getRow($sql);
    
    // 开始事务
    $db->start_transaction();

    if (in_array($service_type, array(SERVICE_TYPE_CHANGE, SERVICE_TYPE_BACK))) { //退货或者换货的服务
        if ($service_status == SERVICE_STATUS_REVIEWING || $service_status == SERVICE_STATUS_DENIED) { //如果是通过审核或者直接拒绝的
            $sql_snippet = "";
            if ($service_status == SERVICE_STATUS_REVIEWING) {
                include_once ('../includes/cls_json.php');
                $json = new JSON;
                $service_order_goods = $json->decode($service_order_goods_json, 1);

                //make_json_response('123', 5, '456');
                
                // 检查输入的商品数量是否合法，避免由于客服并发操作引起的问题 added by zjli at 2014.1.23
    			foreach ($service_order_goods as $service_order_good) {
    				if($service_order_good['service_amount'] > 0){
    					// 该订单中该商品已建立过售后服务申请的数量 (Still Improvements Needed)
						$sql = "SELECT SUM(amount) FROM ecshop.service_order_goods sog" .
						"    INNER JOIN ecshop.service s ON s.service_id = sog.service_id" .
						"    WHERE sog.order_goods_id = '{$service_order_good['order_goods_id']}'" .
						"	 AND sog.service_id != '{$service['service_id']}'" .
						"        AND ((s.is_complete = 0 AND s.inner_check_status = 0 AND s.service_status = 1 AND sog.is_approved = 1) " .
						"            OR (s.is_complete = 0 AND s.inner_check_status = 0 AND s.service_status = 0 AND sog.is_approved = 0)" .
						"            OR (s.is_complete = 0 AND s.inner_check_status = 32 AND s.service_status = 2 AND sog.is_approved = 1))";
						$amount_in_service = $db->getOne($sql);
		
						$sql = "SELECT goods_number FROM ecshop.ecs_order_goods WHERE rec_id = '{$service_order_good['order_goods_id']}'";
						$total_goods_amount = $db->getOne($sql);
						
						// 该订单中该商品还可以建立售后服务申请的数量
						$service_amount_available = $total_goods_amount - $amount_in_service;
			
						// 如果申请售后的商品数量超过可以建立售后服务申请的数量，则拒绝建立申请
						if($service_order_good['service_amount'] > $service_amount_available){
							$json_result = $service;
							make_json_response(
                                "该订单中该商品还可以建立售后服务申请的数量{$service_amount_available} = {$total_goods_amount} - {$amount_in_service}",
                                2, '如果申请售后的商品数量超过可以建立售后服务申请的数量，则拒绝建立申请',$json_result);
						}
    				}
    			}
                
                // 更新service_order_goods表（通过售后服务审核）
                if(!approve_service_order_goods($service, $service_order_goods)){
                	$db->rollback();
                	make_json_response('更新service_order_goods表（通过售后服务审核）', 3, "rollback at approve_service_order_goods({$service}, {$service_order_goods})");
                }
                
                //创建售后档案
                require_once(ROOT_PATH."RomeoApi/lib_inventory.php");
                require_once(ROOT_PATH."RomeoApi/lib_RMATrack.php");
                if(isRMATrackNeeded()){
                    $sql = "SELECT og.goods_id, og.style_id, og.goods_name,  g.top_cat_id, 
                            o.order_sn, o.consignee, o.address, IF(o.mobile = '', o.tel, o.mobile) AS tel,
                            ii.provider_id, p.provider_name, ii.serial_number,
                            b.brand_id, b.brand_name
                            FROM service_order_goods sog
                            INNER JOIN ecshop.ecs_order_info o ON sog.order_id = o.order_id 
                            INNER JOIN ecshop.ecs_order_goods og ON sog.order_goods_id = og.rec_id
                            INNER JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
                            LEFT JOIN ecshop.ecs_brand b ON b.brand_id = g.brand_id
                            INNER JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8)= iid.order_goods_id
                            INNER JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
                            LEFT JOIN ecshop.ecs_provider p ON ii.provider_id = p.provider_id
                            WHERE sog.service_id = '{$service_id}' AND sog.is_approved = 1";
                    $back_goods = $db->getAll($sql); 
                    
                    $track = new stdClass();
                    
                    foreach ($back_goods as $goods) {
                        if ($goods['top_cat_id'] == 1) {
                            $track->productName = $goods['goods_name'];
                            $track->productId = getProductId($goods['goods_id'], $goods['style_id']);
                            $track->serialNumber = $goods['serial_number'];
                            $track->supplierName = $goods['provider_name'];
                            $track->supplierId = $goods['provider_id'];
                            $track->brandName = $goods['brand_name'];
                            $track->brandId = $goods['brand_id'];
                        }
                    }
                    // rma type
                    $track->trackTypeId = "V1";
                    $track->createdUserByLogin = $_SESSION['admin_name'];
                    
                    $track->orderSn = $goods['order_sn'];
                    $track->orderId = $service['order_id'];
                    $track->customerName = $goods['consignee'];
                    $track->contactInfo = $goods['tel'];
                    $track->acceptDate = date("Y-m-d H:i:s");
                    $track->acceptUser = $_SESSION['admin_name'];
                    $track->postScript = $service['review_remark'];
                    $track->userComment = $service['apply_reason'];
                    $track->serviceId = $service['service_id'];
                    
    			    if (!function_exists('soap_get_client')) {
    			    	require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
    			    }
    			    try {
    			        $handle=soap_get_client('RMATrackService', 'ROMEO', 'Soap_Client');
    			        $handle->createTrack($track);
    			    }
    			    catch (Exception $e) {
    					$db->rollback();
    					make_json_response("soap_get_client('RMATrackService', 'ROMEO', 'Soap_Client')->createTrack(the_track);", 3, "exception = $e");
    			    }
                }
                // 生成-t订单
                $t_order_id = generate_back_order_new($service_id);
                
                if ($t_order_id > 0) {
	        		$log_note = $_SESSION['admin_name'] ." 于 {$datetime} 同意退回货物，生成退货订单";
	    		} else {
	    			$db->rollback();
	    			make_json_response("generate_back_order_new($service_id) failed", 4, '');
	    		}
            }else{
            	
            	if($service['inner_check_status'] == 32){
            		make_json_response("对不起，该售后申请的商品已入库", 3, '');
            	} 
            	
            	$sql_snippet = ", is_complete = '1' ";
                $log_note = $_SESSION['admin_name'] ." 于{$datetime} 拒绝退换货申请";
                
                $sql = "UPDATE ecshop.service_order_goods SET is_approved = 0, amount = 0 WHERE service_id = '{$service_id}'";
	            if(!$db->query($sql)){
	            	$db->rollback();
	            	make_json_response("拒绝退换货申请 failed update the sql", 3, "$sql");
	            }
	            
	            if(!empty($service['back_order_id']) && $service['back_order_id'] != 0){
	            	$sql = "UPDATE ecshop.order_relation SET parent_order_id = 0, root_order_id = 0, parent_order_sn = '', root_order_sn = '' WHERE order_id = '{$service['back_order_id']}'";
		            	if(!$db->query($sql)){
		            	$db->rollback();
		            	make_json_response('已经有back_order_id,执行sql失败', 3, "$sql");
		            }
	            }

            }

            $sql = "UPDATE service SET service_status = '$service_status', service_type='{$service_type}' $sql_snippet WHERE service_id = '{$service_id}' LIMIT 1 ";
            if(!$db->query($sql)){
            	$db->rollback();
            	make_json_response('更新售后状态 les sql failed', 3, "$sql");
            }
        }

        if ($service_call_status == SERVICE_CALL_STATUS_CALLED) { //确认过信息后产生换货订单
            if ($service['service_status'] == SERVICE_STATUS_OK) { //如果 在物流界面已经通过该用户的申请了，
                if ($service_type == SERVICE_TYPE_BACK) { //确认后退款
                    $apply_amount = abs($apply_amount);
                    include_once ('../includes/cls_json.php');
                    $json = new JSON;
                    $back_detail = $json->decode($amount_info_json, 1);
                    $back_detail = serialize($back_detail);
                    
                    // 夫大鲵之灭BA……
                    /*
                    $sql = "INSERT INTO back_amount(service_id, order_id, apply_amount, apply_datetime, back_amount_reason, apply_note, apply_username, back_detail)
                    VALUES('{$service_id}', '{$service['order_id']}', '{$apply_amount}', '{$datetime}', '" . BACK_AMOUNT_BACK_ORDER . "', '{$apply_note}', '{$_SESSION['admin_name']}', '{$back_detail}')";
                    if(!$db->query($sql)){
                    	$db->rollback();
                    	make_json_response('sql failed', 3, "$sql");
                    }
                    */
                    $sql = "UPDATE  service SET service_amount = '{$apply_amount}' WHERE service_id = '{$service_id}'";
                    if(!$db->query($sql)){
                    	$db->rollback();
                    	make_json_response('sql failed', 3, "$sql");
                    }
                    
                    //更新 -t 订单的杂项费用
                    $sql = "UPDATE {$ecs->table('order_info')} SET misc_fee = '{$misc_fee}' WHERE order_id = '{$service['back_order_id']}' ";
                    if(!$db->query($sql)){
                    	$db->rollback();
                    	make_json_response('sql failed', 3, "$sql");
                    }
                    $log_note = $_SESSION['admin_name'] ."已回访，申请退款 $apply_amount 元";
                }
                elseif ($service_type == SERVICE_TYPE_CHANGE) { //确认后换货
                	if($_SESSION['party_id'] == "_" ){
                		 $change_order_id = generate_change_order_new($service_id);
                	}else{
                		try{						  
							$client = new SoapClient(ERPSYNC_WEBSERVICE_URL.'GenerateSaleOrderService?wsdl');
							$response = $client->generate_change_order_new(array("service_id"=>$service_id));
							$res = (array)$response;
							$res = (array)$res['return'];
							$order_sn = $res['order_sn'];
							$order_id = $res['order_id'];
							$code = $res['code'];
							$message = $res['msg'];
							
							if($code=="00000"){
						  		$change_order_id = $order_id;
							}else{
								$change_order_id = -1;
							}
						}catch(Exception $e){
							$message = 'CreateHuanHuo Order Exception,请联系ERP！<br/>'.$e->getMessage();
							$change_order_id = -1;
						}
						QLog::log($service_id.' '.$message);
                	}
                    if($change_order_id > 0){
                    	$log_note = $_SESSION['admin_name'] ."已回访，生成换货订单";
                    }else{
                    	$db->rollback();
                    	make_json_response("failed generate_change_order_new($service_id);", 3, '确认后换货');
                    }

                    $sql = " SELECT IF(mobile IS NULL OR mobile = '', tel, mobile) AS mobile, party_id, distributor_id FROM {$ecs->table('order_info')} WHERE order_id = {$service['order_id']} LIMIT 1";
                    $oo = $db->getRow($sql);

                    // ncchen 090327 客服已生成换货申请，等待库房备货 __msg
                    // 换货申请确认
                    # 短信事件 20090822
                    erp_send_message('exchange_confirm', array(), $oo['party_id'], $oo['distributor_id'], $oo['mobile']);
                }

            } else {
                $log_note = "无质量问题，". $_SESSION['admin_name'] ."已回访";
            }

            $sql = "UPDATE service SET service_call_status = '{$service_call_status}', service_type='{$service_type}' WHERE service_id = '{$service_id}' LIMIT 1 ";
            if(!$db->query($sql)){
            	$db->rollback();
            	make_json_response('更新service_call_status 死了', 3, "$sql");
            }
        }
    } //退换货服务结束

    $service_type_name = $service_type_mapping[$service_type];

    $sql = " SELECT * FROM service WHERE service_id = '{$service_id}' ";
    $service = $db->getRow($sql);
    $status_name = service_status($service);

    //售后日志
    $service['log_note'] = $log_note;
    $service['log_type'] = 'CUSTOMER_SERVICE';
    if(!service_log($service)){
    	$db->rollback();
    	make_json_response("service_log($service) 挂了", 3, "log_note=$log_note");
    }
    if ($remark) { //如果有客服备注，那么再加上一条备注
        $service['log_note'] = $remark;
        $service['is_remark'] = 1;
        if(!service_log($service)){
	    	$db->rollback();
	    	make_json_response('如果有客服备注，那么再加上一条备注 挂了', 3, "remark=$remark");
    	}
    }
	// 提交事务
	$db->commit();
	
    $json_result = $service;

    //SINRI UPDATE POSTSALE CACHe
    POSTSALE_CACHE_updateService(null,180,$service_id);
}
elseif ($act == 'back_or_change') {
    $sql = " SELECT * FROM service WHERE service_id = '{$service_id}' ";
    $service = $db->getRow($sql);
    if ($service['service_type'] == SERVICE_TYPE_BACK && $service_type == SERVICE_TYPE_CHANGE) {
        $log_note = $_SESSION['admin_name'] .'操作退货转换货';
    }
    if ($service['service_type'] == SERVICE_TYPE_CHANGE && $service_type == SERVICE_TYPE_BACK) {
        $log_note = $_SESSION['admin_name'] .'操作换货转退货';
    }
    $sql = "UPDATE service SET service_type='{$service_type}' WHERE service_id = '{$service_id}' LIMIT 1";
    $db->query($sql);
    $service['service_type'] = $service_type;
    $service['log_note'] = $log_note;
    $service['log_type'] = 'CUSTOMER_SERVICE';
    service_log($service);

    if ($remark) { //如果有客服备注，那么再加上一条备注
        $service['log_note'] = $remark;
        $service['is_remark'] = 1;
        service_log($service);
    }
    $json_result = $service;

    //SINRI UPDATE POSTSALE CACHe
    POSTSALE_CACHE_updateService(null,180,$service_id);
}

if ($json_result) {
    make_json_response('', 0, '提交成功', $json_result);
} else {
    make_json_response('', 1, '提交失败');
}