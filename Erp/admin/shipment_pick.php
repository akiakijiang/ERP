<?php 

/**
 * 配货|打印面单|打印发票
 * 
 * @author yxiang@leqee.com
 * @copyright 2010 leqee.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('inventory_picking','ck_distribution_warehousing');
require_once('function.php');
require_once('includes/lib_order.php');
require_once('includes/lib_common.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'admin/includes/lib_filelock.php');
// require_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');
require_once(ROOT_PATH . 'admin/config.vars.php');

// 发货单号
$shipment_id = 
    isset($_REQUEST['shipment_id']) && trim($_REQUEST['shipment_id']) 
    ? $_REQUEST['shipment_id'] 
    : false ;

// 消息
$message = 
    isset($_REQUEST['message']) && trim($_REQUEST['message']) 
    ? $_REQUEST['message'] 
    : false;

// 当前页的url,构造url用
$url = 'shipment_pick.php';
if ($shipment_id) {
    // 如果传递了发货单号则查询相关信息
    $handle = soap_get_client('ShipmentService');
    $response = $handle->getShipment(array('shipmentId' => $shipment_id));
    $shipment = is_object($response->return) ? $response->return : null;
    if (!$shipment){
        header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment->shipmentId}）不存在"));
        exit;   
    }
    
    $no_reserve_order = check_shipment_all_reserved($shipment_id);
    if(!empty($no_reserve_order)) {
		 header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment->shipmentId}）对应的订单未预订成功（orderId:{$no_reserve_order}）"));
	     exit;   
    } 

    
    // 取得发货单的主订单信息
    // 取得发货单的所有订单信息
    // 如果是没有合并发货的订单，查找其发货单信息
    $order = null;
    $order_list = array();
    $shipment_list = array($shipment);
    $order_shipment_list = array();
    
    // 取得发货单的所有订单信息
    $response2 = $handle->getOrderShipmentByShipmentId(array('shipmentId' => $shipment->shipmentId));
    if (is_array($response2->return->OrderShipment)) {
    	$order_shipment_list = $response2->return->OrderShipment;
    } elseif (is_object($response2->return->OrderShipment)){
    	$order_shipment = $response2->return->OrderShipment;
    	$order_shipment_list[] = $order_shipment;
    } else{
        header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment->shipmentId}）异常，找不到对应的主订单"));
        exit;
    }
    
	$i = 0;
	foreach($order_shipment_list as $orderShipment) {
        $order_list[$i] = get_core_order_info('', $orderShipment->orderId);
		if ($shipment->primaryOrderId == $orderShipment->orderId){
			$order = $order_list[$i];
			$response3 = $handle->getShipmentByOrderId(array('orderId' => $order['order_id']));
			if (is_array($response3->return->Shipment)) {
				foreach($response3->return->Shipment as $_shipment){
					if ($_shipment->primaryOrderId != $shipment->primaryOrderId){
						$shipment_list[] = $_shipment;
					} elseif ($_shipment->shipmentId != $shipment->shipmentId) {
						$shipment_list[] = $_shipment;
					}
				}
			}
		}
		$i++;
	}
    
    // order_id 列表
    $order_id_list = array();
    foreach ($order_list as $order) {
        $sql = "
            select m.type from main_distributor m, distributor d, ecs_order_info o 
            where
                m.main_distributor_id = d.main_distributor_id
                and d.distributor_id = o.distributor_id
                and o.order_id = '{$order['order_id']}'
        ";
        $distributor_type = $db->getOne($sql);
        if ($distributor_type == "fenxiao") {
            header("Location: ".add_param_in_url($url, 'message', "该订单{$order['order_id']}是分销订单，请在分销界面操作发货"));
            exit;
        }
        
        // 获取重要备注——后来贯钢说备注都显示出来
        //$sql = "select action_note from ecs_order_action where order_id = '{$order['order_id']}' and note_type = 'SHIPPING' ";
        $sql = "select action_note from ecs_order_action where order_id = '{$order['order_id']}' ";
        $important_note = $db->getCol($sql);
        if (is_array($important_note)) {
            $order['important_note'] = join("；<br>", $important_note);
        }
        $order_id_list[] = $order['order_id'];
    }
    
    $url = add_param_in_url($url, 'shipment_id', $shipment->shipmentId);
 
}

if ($message) {
    // 如果传递过来了消息则显示消息
    $smarty->assign('message', $message);
}
/**
 * 动作处理
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['act'])){
    switch ($_POST['act']) {
        /* 出库 */
        case 'pick' :
        	if (!lock_acquire('shipment_pick-'.$shipment->shipmentId)) {
        		header("Location: ".add_param_in_url($url,'message', "该发货单正在操作发货，请稍候再试"));
        		exit;  
        	}
            @set_time_limit(300);

            // 检查订单是否被修改
            foreach($order_list as $order_item){
                if($order_item['order_info_md5'] != $_POST['order_info_md5'][$order_item['order_id']]){
                    $smarty->assign('message', "订单{$order_item['order_sn']}已改变，请重新配货");
                    break;
                }
            }
            $real_out_goods_numbers = array();// 本次出库的数量
            $serial_numbers = array();        // 串号控制商品的串号
            $out_goods_numbers = array(); // 系统已经出库的数量
            $no_scan_goods_name = array(); // 没有扫描的商品名称
            
            $product_ids = get_product_ids($shipment_id);
            if(!empty($product_ids)) {
	            foreach($product_ids as $product_id) {
	            	$real_out_goods_numbers[$product_id] = trim($_POST['item_list_'.$product_id.'_real_out_goods_number']);
	            	$left_out_number = trim($_POST['item_list_'.$product_id.'_left_out_number']);
	            	
	            	if($real_out_goods_numbers[$product_id] < $left_out_number) {
	            		$no_scan_goods_name[$product_id] =  $_POST['item_list_'.$product_id.'_goods_name'];
	            	}
	            	$serial_numbers[$product_id] = $_POST['item_list_'.$product_id.'_serial_numbers'];

	            	Qlog::log('pick_up: product_id:'.$product_id.' real_out:'.$real_out_goods_numbers[$product_id].' left_out:'.$left_out_number);
	            	if(!empty($serial_numbers[$product_id])) {
	            		Qlog::log('pick_up: $serial_numbers:'.implode($serial_numbers[$product_id]));
	            	}
	            	$out_goods_numbers[$product_id]=0;
	            }
            } else {
            	header("Location: ".add_param_in_url($url,'message', "由发货单获取product_id参数异常！"));
        		exit;  
            }

            $unmatched_notfound  = array();  // 输入的串号没有找到或已出库 
            $transfer_exception  = array();  // 出库异常
            $outsn_lock_exception = array(); // 获取出库单号锁异常
            
            // 配货出库
            $result = stock_delivery($order_list,$real_out_goods_numbers,$serial_numbers);
            
            $unmatched_notfound = $result['unmatched_notfound'];
            $transfer_exception = $result['transfer_exception'];
            $outsn_lock_exception = $result['outsn_lock_exception'];

            // 所有商品都已成功出库了， 更新订单的状态， 提示配货成功
            if (empty($no_scan_goods_name) && empty($unmatched_notfound) && empty($transfer_exception) && empty($outsn_lock_exception)) {
                foreach($order_list as $order_item){
                    if ($order_item['shipping_status'] == 0 || $order_item['shipping_status'] == 10) {
						// 更改订单状态
                        $sql="UPDATE {$ecs->table('order_info')} SET shipping_status=9 WHERE order_id='%d' LIMIT 1";
                        $result=$db->query(sprintf($sql, $order_item['order_id']));
                        if ($result) {
                            // 记录订单操作历史
                            $sql = "
                                INSERT INTO {$ecs->table('order_action')} 
                                (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
                                ('{$order_item['order_id']}', '{$order_item['order_status']}', 9, '{$order_item['pay_status']}', NOW(), '%s', '{$_SESSION['admin_name']}')
                            ";
                            if ($order['shipping_status'] == 10) {
                                $db->query(sprintf($sql, '重新配货出库'));
                                //update_order_mixed_status($order_item['order_id'], array('warehouse_status'=>'re-picked'), 'worker');
                            } elseif ($order['shipping_status'] == 0) {
                                $db->query(sprintf($sql, '配货出库'));
                                //update_order_mixed_status($order_item['order_id'], array('warehouse_status'=>'picked'), 'worker');
                            }
                        }
                    }   
                }
                
                // 为避免浏览器刷新会重复提交的问题，用header重新定向
                header("Location: ".add_param_in_url($url, 'message', "发货单 {$shipment->shipmentId} 配货成功"));
                exit;
            }
            // 有部分商品出库失败， 提示错误消息
            else {
                $info = '';
                if (!empty($no_scan_goods_name)) {
                    $info .= '商品：' . implode(' ， ', $no_scan_goods_name) . '还没出库完.';
                }
                if (!empty($outsn_lock_exception)) {
                    $info .= "并发锁异常，请联系ERP组:" . implode(' ， ', $outsn_lock_exception) .".";
                }
                if (!empty($unmatched_notfound)) {
                    $info .= '串号' . implode(' ， ', $unmatched_notfound) . '不在系统中或已经出库了.';
                }
                if (!empty($transfer_exception)) {
                    $info .= "串号" . implode(' ， ', $transfer_exception) . '出库失败，请联系ERP组.';
                }
                header("Location: ".add_param_in_url($url,'message',$info));
                exit;
            }
            
            break;   /* 出库处理完毕 */
        

        /* 扫描面单号 */
        case 'waybill' :
            // 检查数据
        	Helper_Array::removeEmpty($_POST);

            // 已经出库，待扫面单（扫描主面单）
            if ($order['shipping_status'] == 9) {
				$trackingNumber=$_POST['shipment_tracking_number'][$shipment->shipmentId];
            	if(empty($trackingNumber)){
                	$smarty->assign('message', "请输入面单号");
                	break;
            	}
            
            	// 输入面单号
            	if (is_null($shipment->picklistId)  // 没有参与批拣
            		|| $shipment->trackingNumber!=$trackingNumber) {  // 参与批拣了但修改了串号
            		$handle=soap_get_client('ShipmentService');
            		$handle->updateShipment(array(
						'shipmentId'=>$shipment->shipmentId,
						'trackingNumber'=>$trackingNumber,
						'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
	            	));	
            	}

                $note = "扫描快递面单, 面单号为：{$trackingNumber}";
                foreach($order_list as $order_item){
                    // 记录运单号 killed 20160105 by Sinri
                    // $db->query(sprintf("UPDATE {$ecs->table('carrier_bill')} SET bill_no = '%s' WHERE bill_id = '%d' LIMIT 1",$trackingNumber,$order_item['carrier_bill_id']));
                    
    	            // 更新订单发货状态, 发货时间
                    $sql = "UPDATE {$ecs->table('order_info')} SET shipping_status='8', shipping_time=UNIX_TIMESTAMP() WHERE order_id = '{$order_item['order_id']}' LIMIT 1";
                    if ($db->query($sql)) {
                        // 记录订单备注
                        $sql = "
                            INSERT INTO {$ecs->table('order_action')} 
                            (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
                            ('{$order_item['order_id']}', '{$order_item['order_status']}', '8', '{$order_item['pay_status']}', NOW(), '{$note}', '{$_SESSION['admin_name']}')
                        ";
                        $db->query($sql);	
                        
                        // 记录订单状态
                        // update_order_mixed_status($order_item['order_id'], array('warehouse_status'=>'delivered'), 'worker');
                    }
                }
                
                header("Location: ".add_param_in_url($url,'message',$note));
                exit;
            }
            // 已扫面单，需要更改或添加（多对多、多对一、一对多）
            else if ($order['shipping_status']==8){
            	$trackingNumberFrom=$trackingNumberTo=array();
            	$trackingNumberChanged=false;
            	foreach($shipment_list as $shipment_item){
            		$trackingNumberFrom[]=$shipment_item->trackingNumber;
            		
            		if(isset($_POST['shipment_tracking_number'][$shipment_item->shipmentId])){
						$trackingNumber=$_POST['shipment_tracking_number'][$shipment_item->shipmentId];
						$trackingNumberTo[]=$trackingNumber;
		                if ($shipment_item->trackingNumber!=$trackingNumber) {
		                	$trackingNumberChanged=true;
		                    $handle=soap_get_client('ShipmentService');
		            		$handle->updateShipment(array(
		            			'shipmentId'=>$shipment_item->shipmentId,
		            			'trackingNumber'=>$trackingNumber,
		            			'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
		            		));
			            }
            		}
            		
            		unset($_POST['shipment_tracking_number'][$shipment_item->shipmentId]);
            	}
            	
            	// 添加面单号（分开多个面单发货）
            	if(!empty($_POST['shipment_tracking_number'])){
            		$trackingNumberChanged=true;
            		
            		foreach($_POST['shipment_tracking_number'] as $key=>$trackingNumber){
            			$trackingNumberTo[]=$trackingNumber;
            			
            			try{
	            			$handle=soap_get_client('ShipmentService','ERPSYNC');
	            			$object=$handle->createShipment(array(
	            		        'orderId' => $order['order_id'],
	            				'partyId' => $shipment->partyId,
	            				'shipmentTypeId'=>$shipment->shipmentTypeId,
	            				'carrierId'=>$shipment->carrierId,
	            				'trackingNumber'=>$trackingNumber,
	            				'createdByUserLogin'=>$_SESSION['admin_name']
	            			));
	            			
	            			foreach($order_list as $order_item){
		            			$handle->createOrderShipment(array(
		            				'orderId'=>$order_item['order_id'],
		            				'shipmentId'=>$object->return,
		            			));
	            			}
            			}
            			catch (Exception $e){
            			
            			}
            		}
            	}
            	
            	// 面单发生更改则更新
            	if($trackingNumberChanged){
            		// 修改提示语 ljzhou 2012.12.6
					$modify = array_diff ( $trackingNumberFrom, $trackingNumberTo );
					$add = array_diff ( $trackingNumberTo, $trackingNumberFrom );
					if (count ( $modify ) > 0) {
						$note = "快递面单从" . implode ( ',', $modify ) . "更改为" . implode ( ',', $add );
					} else {
						$note = "增加了快递面单" . implode ( ',', $add );
					}
					// 更新运单的运单号信息
					foreach($order_list as $order_item) {
                        //killed by Sinri 20160105
						// $db->query(sprintf("UPDATE {$ecs->table('carrier_bill')} SET bill_no = '%s' WHERE bill_id = '%d'",implode(',',$trackingNumberTo),$order_item['carrier_bill_id']));
						// 记录订单备注
						$sql = "
							INSERT INTO {$ecs->table('order_action')} 
							(order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
							('{$order_item['order_id']}', '{$order_item['order_status']}', '{$order_item['shipping_status']}', '{$order_item['pay_status']}', NOW(), '{$note}', '{$_SESSION['admin_name']}')
						";
						$db->query($sql);
					}
                    
					header("Location: ".add_param_in_url($url,'message',$note));
					exit;
            	}
            	else{
            		$smarty->assign('message','面单号没有改变');
            	}
            }
            // 不是可以扫面单的状态了
            else {
            	$smarty->assign('message', "该订单还未出库或已发货，不能再扫描或更改面单");
            }
            
            break; /* 提交面单结束 */
            

        /* 发货 */
        case 'shipment' :
            if (!lock_acquire('shipment_pick-'.$shipment->shipmentId)) {
                $smarty->assign('message', '该订单正在操作出库，稍后请重试');
                break;
            }

            // 如果是备发货状态则发货
            if ($order['shipping_status'] != 8) {
                $smarty->assign('message', '订单不是待发货状态, 不能发货');
                break;
            }
            
            // 如果是取消的订单，则直接跳出
            if ($order['order_status'] == 2) {
                $smarty->assign('message', '该订单已被取消, 不能发货');
                break;
            }

            foreach($order_list as $order_item){
	            
            	// 更改订单状态
	            $db->query(sprintf("UPDATE {$ecs->table('order_info')} SET shipping_time=UNIX_TIMESTAMP(), shipping_status=1 WHERE order_id='%d'",$order_item['order_id']));

	            // 记录订单状态
	            orderActionLog(array('order_id'=>$order_item['order_id'], 'order_status'=>$order_item['order_status'], 'shipping_status'=>1, 'pay_status'=>$order_item['pay_status'], 'action_note'=>'操作发货'));
	            //update_order_mixed_status($order['order_id'], array('shipping_status' => 'shipped'), 'worker');
            }
            
            // 更新发货单的发货状态
            foreach($shipment_list as $shipment_item){
				try {
					$handle=soap_get_client('ShipmentService');
					$handle->updateShipment(array(
						'shipmentId'=>$shipment_item->shipmentId,
						'status'=>'SHIPMENT_SHIPPED',
						'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
					));
				}
				catch (Exception $e) {
					$smarty->assign('message', $e->getMessage());
					break;
				}
            }
			
            lock_release('shipment_pick-'.$shipment->shipmentId);
            header("Location: shipment_pick.php?message=".urldecode("发货成功")); exit;
            break;
    }
}


/**
 * 出库扫描页面
 */
if ($shipment) {
	do {
		if (!in_array($order['order_type_id'], array('SALE','SHIP_ONLY','RMA_EXCHANGE')) ) {
			$smarty->assign('message', "订单 {$order['order_sn']} 不是可出库的订单类型");
			break;
		}
		if ($order['order_status'] != 1) {
			$smarty->assign('message', "订单 {$order['order_sn']} 不是已确认订单");
			break;
		}
		if ($order['pay_code'] != 'cod' && $order['is_cod'] == '0'
			&& $order['pay_status'] != 2 
			&& $order['order_type_id'] != 'RMA_EXCHANGE' 
			&& $order['order_type_id'] != 'SHIP_ONLY' ) { // 发货的条件：cod 或者 pay_status = 2 或者 是换货订单，或者是 SHIP_ONLY的订单
			$smarty->assign('message', "订单 {$order['order_sn']} 还没有支付");
			break;
		}
		if ($order['handle_time'] > 0 && time() < $order['handle_time']) {
			$smarty->assign('message', "订单 {$order['order_sn']} 处理时间是： ." .date("Y-m-d" , $order['handle_time']));
			break;
		}
		if (!$order['facility_id']) {
			$smarty->assign('message', '该订单未指定发货仓库');
			break;
		}
		if (strpos($_SESSION['facility_id'].',', $order['facility_id'].',') === false) {
			$smarty->assign('message', '该订单无法在当前仓库发货');
			break;
		}
		
		$item_info_list = array();
		// 格式化订单,按照商品级别统计
		$item_info_list = get_format_item_info_list($order_list);
		
		//需要屏蔽发货按钮的仓库列表
		$screened_shipment_facility_list = array('22143846', '22143847', '19568549', '24196974','19568548','3580047');
		$screened_shipment_flag = false;
		if (in_array($order['facility_id'], $screened_shipment_facility_list)) {
		    $screened_shipment_flag = true;
		}
		//上海仓屏蔽条码
		$screened_barcode_facility_list = array('22143846', '22143847', '19568549', '24196974');
		$screened_barcode_flag = false;
		if (in_array($order['facility_id'], $screened_barcode_facility_list)) {
			$screened_barcode_flag = true;
		}

		// 显示配送信息
		$smarty->assign('shipment',$shipment);            // 配送信息
		$smarty->assign('order_list',$order_list);        // 订单列表
		$smarty->assign('item_info_list',$item_info_list);   // 商品配货列表
		$smarty->assign('order',$order);                  // 主订单
		$smarty->assign('shipment_list',$shipment_list);  // 主订单的分开发货信息
		$smarty->assign('order_count',count($order_list));
		$smarty->assign('screened_shipment_flag', $screened_shipment_flag);
		$smarty->assign('screened_barcode_flag', $screened_barcode_flag);
		
	} while (false);
}

$smarty->assign('big_goods_number',get_big_goods_number());  // 得到大订单中大商品的界线数值
$smarty->display('shipment/shipment_pick.htm');
