<?php

/**
 * 电教发货 
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
admin_priv('distribution_delivery');
require_once('function.php');
require_once(ROOT_PATH. 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH. 'includes/helper/array.php');
require_once(ROOT_PATH. 'includes/debug/lib_log.php');
require_once(ROOT_PATH. 'admin/includes/lib_filelock.php');

//require_once(ROOT_PATH . 'admin/includes/lib_express_arata.php');
require_once('includes/lib_sinri_DealPrint.php');

// 请求
$order_sn = isset($_REQUEST['order_sn']) && trim($_REQUEST['order_sn']) ? $_REQUEST['order_sn'] : false ;
$info = isset($_REQUEST['info']) && trim($_REQUEST['info']) ? $_REQUEST['info'] : false; 

$is_third_party = false;
if(check_admin_priv('third_party_warehouse') && ($_SESSION['action_list']!='all')){
     $is_third_party = true;
 }

$act=isset($_REQUEST['act'])?$_REQUEST['act']:null;
if($act=='ajax_arata_request_tracking_number'){
    $shipmentId=$_REQUEST['shipment_id'];
    if(empty($shipmentId)){
        echo "然而并没有收到发货单号。";
    }
    $done = add_thermal_tracking_number($shipmentId);
    if($done){
        //get TN
        //$TN='TEST'.time();//For TEST
        $TN=$db->getOne("SELECT tracking_number FROM romeo.shipment WHERE shipment_id='{$shipmentId}'");
        echo json_encode(array('tracking_number'=>$TN));
    }else{
        //just echo
    }
    exit();
}

if ($order_sn) {
    // 如果传递了订单号则查询相关信息
	$order = get_core_order_info($order_sn);
    if (!$order) {
        header("Location: distribution_delivery.php?info=".urlencode("系统中没有该订单"));
        exit;   
    }
	
	$sql_party = "SELECT 1 FROM ecshop.ecs_order_info WHERE order_id = '{$order['order_id']}' and party_id = '{$_SESSION['party_id']}' limit 1 ";
    $check_party = $db->getOne($sql_party);
    if(empty($check_party)) {
        header("Location: distribution_delivery.php?info={$order_sn}".urlencode("请切换到订单具体组织再操作！"));
        exit;   
	}
	
    $sql_status = "SELECT STATUS FROM romeo.order_inv_reserved WHERE order_id = '{$order['order_id']}' ";
    $status = $db->getOne($sql_status);
    if($status == '' || $status == null || $status == 'N') {
        header("Location: distribution_delivery.php?info={$order_sn}".urlencode("订单未预定成功, 请稍候再试"));
        exit;   
	}
	
	//查找电教订单中需要下载资料的订单，依照淘宝订单号来匹配
	if($_SESSION['party_id']==16){
		$sql_info_download = "select gi.status,gi.taobao_order_sn from ecshop.ecs_guest_info as gi
		inner join ecshop.ecs_order_info as oi on gi.taobao_order_sn=oi.taobao_order_sn
		where oi.order_sn = '{$order_sn}' AND ". party_sql('party_id') ;
		$result_info = $db->getAll($sql_info_download);
		foreach ($result_info as $re) {
			$download_status=$re['status'];
			$taobao_order_sn=$re['taobao_order_sn'];
			
		}
		
		$smarty->assign('download_status',$download_status);     //资料是否下载
	}
 	
    $sql = "select action_note from ecs_order_action where order_id = '{$order['order_id']}' and note_type = 'SHIPPING' ";
    $important_note = $db->getCol($sql);
    if (is_array($important_note)) {
       $order['important_note'] = join(";<br>", $important_note);
    }
    $order['postscript'] = $order['postscript'] . (!empty($order['postscript']) ? ";<br>" : '') . $order['important_note'];
	

    // 取得订单的分销商信息
    if ($order['distributor_id'] > 0) {
        $sql = "SELECT * FROM distributor WHERE distributor_id = '{$order['distributor_id']}'";
        $distributor = $db->getRow($sql, true);
    }
    
    // 取得配送信息
    $is_merge_shipment = $is_split_shipment = $is_primary_shipment = false;
    $shipment_list=null;
    $merge_shipment_list=array();
    
	$sql = " SELECT s.* from romeo.order_shipment os 
		INNER JOIN romeo.shipment s on s.SHIPMENT_ID = os.SHIPMENT_ID 
		where os.ORDER_ID = '{$order['order_id']}' and s.SHIPPING_CATEGORY='SHIPPING_SEND' ";
	$shipment_list = $db->getAll($sql);
	foreach($shipment_list as $shipment){
		if($shipment['PRIMARY_ORDER_ID']==$order['order_id']){
			$is_primary_shipment=true;
		}
		$sql = " SELECT os.* from romeo.order_shipment os 
			INNER JOIN romeo.shipment s on s.SHIPMENT_ID = os.SHIPMENT_ID 
			where os.shipment_id = '{$shipment['SHIPMENT_ID']}' ";
		$order_shipments = $db->getAll($sql);	
		// 合并发货的
		if(count($order_shipments)>1){
			$is_merge_shipment=true;
			// 取得合并发货的订单的信息
			$i=0;
			foreach($order_shipments as $orderShipment) {
//				var_dump($orderShipment);
				$merge_shipment_list[$i] = $db->getRow(sprintf("select order_id,order_sn,order_status,shipping_status,pay_status,carrier_bill_id from ecs_order_info where order_id = '%d'",$orderShipment['ORDER_ID']));
				if($shipment['PRIMARY_ORDER_ID']==$merge_shipment_list[$i]['order_id']){
					$merge_shipment_list[$i]['is_primary_shipment']=true;
				} else {
					$merge_shipment_list[$i]['is_primary_shipment']=false;
				}
				$i++;
			}
		}
	}
}

if ($info) {
    $smarty->assign('message', $info);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['act'])){
    // include_once('includes/lib_order_mixed_status.php');
    $lock_name='distribution_delivery-'.$order['order_id'];
    $order_facility_id = $order['facility_id'];
    switch ($_POST['act']) {
    	//更新ecs_guest_info中的status状态
    	case'download':
    		$sql = "update ecshop.ecs_guest_info set status=1 where taobao_order_sn = '{$taobao_order_sn}'";
    		$db->query($sql);
    		header("Location: distribution_delivery.php?order_sn={$order['order_sn']}&info=".urlencode("订单 {$order['order_sn']} 所需资料已下载")); exit;
        /* 出库 */
        case 'pick' :
            if (!lock_acquire($lock_name)) {
                $smarty->assign('message', '该订单正在操作出库，稍后请重试');
                break;
            }
            
            @set_time_limit(300);
            $order_list[] = $order;
            // 检查订单是否被修改
            foreach($order_list as $order_item){
                if($order_item['order_info_md5'] != $_POST['order_info_md5'][$order_item['order_id']]){
                	QLog::log('order_id:'.$order_item['order_id'].' md5_now：'.$order_item['order_info_md5'].' md5_old:'.$_POST['order_info_md5'][$order_item['order_id']]);
                    $smarty->assign('message', "订单{$order_item['order_sn']}已改变，请重新配货");
                    break;
                }
            }
            $real_out_goods_numbers = array();// 本次出库的数量
            $serial_numbers = array();        // 串号控制商品的串号
            $out_goods_numbers = array(); // 系统已经出库的数量
            $no_scan_goods_name = array(); // 没有扫描的商品名称
            $product_ids = get_product_ids_by_order_sn($order_sn);
            if(!empty($product_ids)) {
	            foreach($product_ids as $product_id) {
	            	$real_out_goods_numbers[$product_id] = trim($_POST['item_list_'.$product_id.'_real_out_goods_number']);
	            	$left_out_number = trim($_POST['item_list_'.$product_id.'_left_out_number']);
	            	
	            	if($real_out_goods_numbers[$product_id] < $left_out_number) {
	            		$no_scan_goods_name[$product_id] =  $_POST['item_list_'.$product_id.'_goods_name'];
	            	}
	            	$serial_numbers[$product_id] = $_POST['item_list_'.$product_id.'_serial_numbers'];

//	            	Qlog::log('pick_up: product_id:'.$product_id.' real_out:'.$real_out_goods_numbers[$product_id].' left_out:'.$left_out_number);
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
                if ($order['shipping_status'] == 0 || $order['shipping_status'] == 10) {
                    $sql = "UPDATE {$ecs->table('order_info')} SET shipping_status = 9 WHERE order_sn = '{$order['order_sn']}' LIMIT 1";
                    $result = $db->query($sql);
                    if ($result) {
                        $sqls[] = $sql;
						
                        // 添加order_action
                        $sql = "
                        	INSERT INTO {$ecs->table('order_action')} 
                        	(order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
                        	('{$order['order_id']}', '{$order['order_status']}', 9, '{$order['pay_status']}', NOW(), '%s', '{$_SESSION['admin_name']}')
                        ";
                        				            
                       $db->query(sprintf($sql, '配货出库'));
			            
                        // 是合并发货的，不是主订单，直接更新面单状态
                        if($is_merge_shipment && !$is_primary_shipment) {
	                        // 更新订单发货状态, 发货时间
			                $sql = "UPDATE ecs_order_info SET shipping_status = '8', shipping_time = UNIX_TIMESTAMP() WHERE order_id = '%d' LIMIT 1";
			                if ($db->query(sprintf($sql,$order['order_id']))) {
			                	foreach ($merge_shipment_list as $merge_shipment_order) {
			                		if ($merge_shipment_order['is_primary_shipment']) {
			                			$note = '和订单'.$merge_shipment_order['order_sn'].'合并发货';
			                			break;
			                		}
			                	}
			                	
			                    // 记录发货 order action
			                    $sql = "
			                        INSERT INTO {$ecs->table('order_action')} 
			                        (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
			                        ('%d', '%d', '8', '%d', NOW(), '%s', '%s')
			                    ";
			                    $db->query(sprintf($sql, $order['order_id'],$order['order_status'], $order['pay_status'], $note, $_SESSION['admin_name']));	
			                    
			                    // 记录订单状态
			                    //update_order_mixed_status($order['order_id'], array('warehouse_status' => 'delivered'), 'worker');
			                }
                        }
			        }
                }
			    
                // 为避免浏览器刷新会重复提交的问题，用header重新定向
                header("Location: distribution_delivery.php?order_sn={$order['order_sn']}&info=".urlencode("订单 {$order['order_sn']} 配货成功")); exit;
            } 
            // 有部分商品出库失败， 提示错误消息
            else {
                $info = '';
                if (!empty($no_scan_goods_name)) {
                    $info .= '商品：' . implode(' ， ', $no_scan_goods_name) . '还没出库完.';
                }
                if (!empty($outsn_lock_exception)) {
                    $info .= "并发锁异常，请联系ERP组:" . implode(' , ', $outsn_lock_exception) .".";
                }
                if (!empty($unmatched_notfound)) {
                    $info .= '串号' . implode(' ， ', $unmatched_notfound) . '不在系统中或已经出库了.';
                }
                if (!empty($transfer_exception)) {
                    $info .= "串号" . implode(' ， ', $transfer_exception) . '出库失败，请检查下出库数量是否正常，（出库数量正常就没事，不要开2个页面重复出库）！';
                }

                header("Location: distribution_delivery.php?order_sn={$order['order_sn']}&info=".urlencode($info)); exit;
            }
            break;   /* 出库处理完毕 */
		

        /* 扫描面单号 */
        case 'waybill' :
            // 如果是合并发货的
            // 如果是主订单, 需要判断其他订单是否已经发货了, 不然其他订单需要先出库
            if ($is_merge_shipment && $is_primary_shipment) {
           		foreach($merge_shipment_list as $merge_shipment_order) {
           			if (!$merge_shipment_order['is_primary_shipment'] && !in_array($merge_shipment_order['shipping_status'],array(9,8,1))) {
           				$smarty->assign('message','该订单和订单'.$merge_shipment_order['order_sn'].'合并发货的，需要先出库合并发货的订单'.$merge_shipment_order['order_sn']);
           				break 2;
           			}
           		}
            }
            // 检查数据
            $shipment_tracking_number=$_POST['shipment_tracking_number'];
            $add_shipment_tracking_number = $_POST['add_shipment_tracking_number'];
            if(!empty($shipment_tracking_number)) {
                Helper_Array::removeEmpty($shipment_tracking_number,true);
            }
            if (empty($shipment_tracking_number) && empty($add_shipment_tracking_number)) {
                $smarty->assign('message', "请输入面单号");
                break;
            }
            // 已经出库，待扫面单
            if ($order['shipping_status'] == 9) {
            	// 更新面单号
	            foreach ($shipment_list as $shipment) {
	            	$trackingNumber=$shipment_tracking_number[$shipment['SHIPMENT_ID']];
	            	$sql = "update romeo.shipment set tracking_number='{$trackingNumber}',last_modified_by_user_login='{$_SESSION['admin_name']}' where shipment_id='{$shipment['SHIPMENT_ID']}' ";
	            	$db->query($sql);
	            }
	            // 更新 carrier_bill
	            $bill_no=implode(',',$shipment_tracking_number);
	            $note = "扫描快递面单, 面单号为：{$bill_no}";
	            
	            $sql = "UPDATE {$ecs->table('carrier_bill')} SET bill_no = '%s' WHERE bill_id = '%d'";	
	            $db->query(sprintf($sql,$bill_no,$order['carrier_bill_id']));
                if ($is_merge_shipment && $is_primary_shipment) {
                    foreach($merge_shipment_list as $merge_shipment_order) {
                        if (!$merge_shipment_order['is_primary_shipment']) {
                            $db->query(sprintf($sql,$bill_no,$merge_shipment_order['carrier_bill_id']));
                        }
                    }
                }
            
	            // 更新订单发货状态, 发货时间
                $sql = "UPDATE {$ecs->table('order_info')} SET shipping_status = '8', shipping_time = UNIX_TIMESTAMP() WHERE order_id = '{$order['order_id']}' LIMIT 1";
                if ($db->query($sql)) {
                    // 记录发货 order action
                    $sql = "
                        INSERT INTO {$ecs->table('order_action')} 
                        (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
                        ('{$order['order_id']}', '{$order['order_status']}', '8', '{$order['pay_status']}', NOW(), '{$note}', '{$_SESSION['admin_name']}')
                    ";
                    $db->query($sql);	
                    
                    // 记录订单状态
                    // update_order_mixed_status($order['order_id'], array('warehouse_status' => 'delivered'), 'worker');
                }
            }
            // 已扫面单，需要更改
            else if ($order['shipping_status'] == 8) {
                //添加面单号
                if (!empty($add_shipment_tracking_number)) {
                    $sql = "SELECT default_carrier_id FROM ecs_shipping WHERE shipping_id = '{$order['shipping_id']}'";
                    $carrier_id = $db->getOne($sql);
                    try{
                        $handle=soap_get_client('ShipmentService' ,'ERPSYNC');
                        $object=$handle->createShipment(array(
                            'orderId' => $order['order_id'],
                            'partyId' => $order['party_id'],
                            'shipmentTypeId' => $order['shipping_id'],
                            'carrierId' => $carrier_id,
                            'trackingNumber' => $add_shipment_tracking_number,
                            'createdByUserLogin'=>$_SESSION['admin_name']
                        ));
                        $handle->createOrderShipment(array(
                            'orderId'=>$order['order_id'],
                            'shipmentId'=>$object->return,
                        ));
                        $trackingNumberTo[] = $add_shipment_tracking_number;
                    }
                    catch (Exception $e){
                         
                    }

                }
                // 更新面单号
                $changed=false;
                foreach ($shipment_list as $shipment) {
                    $trackingNumber=$shipment_tracking_number[$shipment['SHIPMENT_ID']];
                    $trackingNumberFrom[] = $shipment['TRACKING_NUMBER'];
                    if ($shipment['TRACKING_NUMBER']!=$trackingNumber) {
                        if (!empty($trackingNumber)) {
                            $sql = "update romeo.shipment set tracking_number='{$trackingNumber}',last_modified_by_user_login='{$_SESSION['admin_name']}' where shipment_id='{$shipment['SHIPMENT_ID']}' ";
	            			$db->query($sql);
                            if ($changed===false) {
                                $changed=true;
                            }
                            $trackingNumberTo[] = $trackingNumber;
                        } else {
                            $trackingNumberTo[] = $shipment['TRACKING_NUMBER'];
                        }
                    } else {
                        $trackingNumberTo[] = $shipment['TRACKING_NUMBER'];
                    }
                }
                 
                if ($changed || !empty($add_shipment_tracking_number)) {
                    $note = "订单{$order['order_sn']}快递面单号从".implode(',', $trackingNumberFrom)."改为".implode(',', $trackingNumberTo);
                    unset($add_shipment_tracking_number);
                    // 更新运单的运单号信息
                    $db->query(sprintf("UPDATE {$ecs->table('carrier_bill')} SET bill_no = '%s' WHERE bill_id = '%d'",
                        implode(',', $trackingNumberTo), $order['carrier_bill_id']));
                    if ($is_merge_shipment && $is_primary_shipment) {
                        foreach($merge_shipment_list as $merge_shipment_order) {
                            if (!$merge_shipment_order['is_primary_shipment']) {
                                $db->query(sprintf($sql,$bill_no,$merge_shipment_order['carrier_bill_id']));
                            }
                        }
                    }
	                
	                // 添加 order action
	                $sql = "
	                    INSERT INTO {$ecs->table('order_action')} 
	                    (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
	                    ('{$order['order_id']}', '{$order['order_status']}', '{$order['shipping_status']}', '{$order['pay_status']}', NOW(), '{$note}', '{$_SESSION['admin_name']}')
	                ";
	                $db->query($sql);
	            }
            }
            else {
            	$smarty->assign('message', "该订单还未出库或已发货，不能再扫描或更改面单");
            	break;
            }
       
            // 为避免浏览器刷新会重复提交的问题，用header重新定向
            header("Location: distribution_delivery.php?order_sn={$order['order_sn']}&info=".urlencode($note)); exit;
            break; /* 提交面单结束 */

            
        /* 修改发票号 */
        case 'change_invoice' :
            $order_id = trim($_POST['order_id']);  // 发票号
            $shipping_invoice = trim($_POST['shipping_invoice']);  // 发票号
             
            if (empty($shipping_invoice)) {
                $smarty->assign('message', "请输入发票号");
                break;
            }
            if (empty($order_id)) {
                $smarty->assign('message', "订单order_id为空！");
                break;
            }
            
            // 更新该订单的发票号
            update_order_shipping_invoice($order_id,$shipping_invoice);
            
            header("Location: distribution_delivery.php?order_sn={$order['order_sn']}&info=".urlencode("发票号更改为{$shipping_invoice}")); exit;
            break;
            
        /* 发货 */
        case 'shipment' :
        	QLog::log("distribution-delivery-shipment");
            if (!lock_acquire($lock_name)) {
                $smarty->assign('message', '该订单正在操作出库，稍后请重试');
                break;
            }
            
            @set_time_limit(300);
            // 如果是备发货状态则发货
            if ($order['shipping_status'] != 8) {
                $smarty->assign('message', '该订单不是待发货状态, 不能发货');
                break;
            }
            
            // 如果是取消的订单，则直接跳出
            if ($order['order_status'] == 2) {
                $smarty->assign('message', '该订单已被取消, 不能发货');
                break;
            }
            // 取得主分销商信息
            if ($distributor) {
            	$main_distributor = $db->getRow("select d.is_prepayment, md.main_distributor_id, md.name, md.type from ecshop.distributor d 
                								left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id 
                								where d.distributor_id = '{$distributor['distributor_id']}' limit 1");
            }
            
            // 分销的销售订单，抵扣预付款， 先判断
            $edu_adjust_need = in_array($order['party_id'],array(16,65558,65553,65609,65617))&& $order['order_type_id'] == 'SALE' && $distributor && $main_distributor['type']=='fenxiao' && $main_distributor['is_prepayment']=='Y';
            if ($edu_adjust_need) {
            	$result = distribution_edu_order_adjustment($order, $main_distributor['main_distributor_id']) ;
            	if(!empty($result)){
            		$smarty->assign('message', $result);
            		break ;
            	}
            }   
            
            // 直销的销售订单，需要检查是否开票了
            if ($order['party_id']==16 && $order['order_type_id']=='SALE' && $distributor && $main_distributor['type']=='zhixiao') {
            	$sql = "
                    SELECT 
                        ii.inventory_item_acct_type_id as order_type, si.shipping_invoice
                    FROM 
                        ecshop.ecs_order_goods AS og 
                        LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8)=iid.order_goods_id
                        LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
                        LEFT JOIN romeo.order_shipping_invoice si ON og.order_id = si.order_id
                    WHERE
                        ii.status_id='INV_STTS_AVAILABLE' and og.order_id = '{$order['order_id']}' 
                        AND ii.inventory_item_acct_type_id='B2C'
                        limit 1
                ";
                $item_info = $db->getRow($sql);
                if (empty($item_info['shipping_invoice'])) {
                    $smarty->assign('message', '该订单还未填写发票号');
                    break;
                }
            }
            
            // 更新订单的发货状态
            $sql = "UPDATE {$ecs->table('order_info')} SET shipping_time = UNIX_TIMESTAMP(), shipping_status = 1 WHERE order_id = '{$order['order_id']}'";
            $db->query($sql);
            orderActionLog(array('order_id'=>$order['order_id'], 'order_status'=>$order['order_status'], 'shipping_status'=>1, 'pay_status'=>$order['pay_status'], 'action_note'=>'分销操作发货'));
            
            // 记录订单状态
            // update_order_mixed_status($order['order_id'], array('shipping_status' => 'shipped'), 'worker');
            
            // 配送信息发货
            // 合并发货
            if ($is_split_shipment) {
            	$shipment_ids="'";
            	$shipment_id_arr = array();
            	foreach($shipment_list as $shipment){
            		array_push($shipment_id_arr,$shipment['shipment_id']);
            	}
            	$shipment_ids = implode("','",$shipment_id_arr);
            	$sql = "update romeo.shipment set status='SHIPMENT_SHIPPED',last_modified_by_user_login='{$_SESSION['admin_name']}' 
            			where shipment_id in ('{$shipment_ids}')";
            	$db->query($sql);
            }
            // 非合并发货
            else if ($is_primary_shipment) {
            	$sql = "update romeo.shipment set status='SHIPMENT_SHIPPED',last_modified_by_user_login='{$_SESSION['admin_name']}' 
            		where primary_order_id = '{$order['order_id']}' and shipping_category='SHIPPING_SEND' ";   
            	$db->query($sql);	         	
            }
//            QLog::log('出库成功');
            header("Location: distribution_delivery.php?info=".urlencode("订单{$order['order_sn']}发货成功, 该订单的分销采购订单号为{$order['distribution_purchase_order_sn']}")); exit;
            break;  /* 发货处理完毕 */
    }
}


/**
 * 出库扫描页面
 */
if ($order_sn) {
	if ($order) {
        if (!in_array($order['order_type_id'], array('SALE', 'SHIP_ONLY', 'RMA_EXCHANGE'))) {
            $smarty->assign('message', "订单 $order_sn 不是销售订单");
        } else if ($order['order_status'] != 1) {
            $smarty->assign('message', "订单 $order_sn 不是已确认订单");
        } else if ($order['pay_code'] != 'cod' 
            && $order['pay_status'] != 2 
            && $order['order_type_id'] != 'RMA_EXCHANGE' 
            && $order['order_type_id'] != 'SHIP_ONLY' ) { // 发货的条件：cod 或者 pay_status = 2 或者 是换货订单，或者是 SHIP_ONLY的订单
            $smarty->assign('message', "订单 $order_sn 还没有支付");
        } else if ($order['handle_time'] > 0 && time() < $order['handle_time']) {
            $smarty->assign('message', "订单 $order_sn 处理时间是： " . date("Y-m-d" , $order['handle_time']));
        } else if (!trim($order['facility_id'])) {
            $smarty->assign('message', '该订单未指定发货仓库');
        } else if (strpos($_SESSION['facility_id'].',', $order['facility_id'].',') === false) {
            $smarty->assign('message', '该订单无法在当前仓库发货');
        } else if (empty($shipment_list)) {
        	$smarty->assign('message', '该订单的发货信息异常, 需要联系erp组解决');
        }  else if ($order['shipping_status'] == 1) {
            $smarty->assign('message', "订单 $order_sn 已经发货");
    	}
    		
        // 订单状态
        $order['status_name'] = get_order_status($order['order_status']). "，" .get_pay_status($order['pay_status']). '，'.get_shipping_status($order['shipping_status']);
        
        // 取得新库存
        #$storage = getStorage('INV_STTS_AVAILABLE', $order['facility_id']);                  
        #$customized_type = get_customize_type();  // 定制信息列表 
        
        // 取得商品列表
        $goods_list = array();
        
        $order_list[] = $order;
        $item_info_list = array();
		// 格式化订单,按照商品级别统计
		$item_info_list = get_format_item_info_list($order_list);
        //需要屏蔽发货按钮的仓库列表
        $screened_shipment_facility_list = array('22143846', '22143847', '19568549', '24196974','19568548','3580047');
        $screened_shipment_flag = false;
        if (in_array($order['facility_id'], $screened_shipment_facility_list)) {
        	$screened_shipment_flag = true;
        }
        //上海仓除了金佰利外屏蔽条码
        $screened_barcode_facility_list = array('22143847', '19568549', '24196974');
        $screened_barcode_flag = false;
        if (in_array($order['facility_id'], $screened_barcode_facility_list) 
        && $_SESSION['party_id'] != '65555' //GALLO
        && $_SESSION['party_id'] != '65574' //金宝贝
        && $_SESSION['party_id'] != '65569') { // 安满
        	$screened_barcode_flag = true;
        }
        
        // 目前一个订单使用一张发票
        $shipping_invoice = $order['order_goods'][0]['shipping_invoice'];
        
        // 总商品数
        $total_goods_count = $order['total_goods_number'];
        $order['total_goods_count'] = $total_goods_count;
        
        // 面单信息 这是老的
        //$sql = "SELECT * FROM {$ecs->table('carrier_bill')} WHERE bill_id = '{$order['carrier_bill_id']}'";
        //$carrier_bill = $db->getRow($sql, true);
        // 面单信息 这是新的 20151202 邪恶的大鲵
        $sql="SELECT
            es.default_carrier_id
        FROM
            ecshop.ecs_order_info oi
        LEFT JOIN ecshop.ecs_shipping es ON oi.shipping_id = es.shipping_id
        WHERE
            oi.order_id = ".$order['order_id'];
        $carrier_id=$db->getOne($sql);
        $carrier_bill=array('carrier_id'=>$carrier_id);

        $smarty->assign('order', $order);                        // 订单信息
              
        $smarty->assign('carrier_bill', $carrier_bill);          // 面单信息 其实页面上只用到了carrier_id
        $smarty->assign('shipping_invoice', $shipping_invoice);  // 发票号
        $smarty->assign('distributor', $distributor);            // 分销商信息  
        $smarty->assign('item_info_list', $item_info_list);              // 订单商品列表

        $smarty->assign('shipment_list', $shipment_list);              // 配送信息
        $smarty->assign('merge_shipment_list', $merge_shipment_list);  // 合并发货的订单信息列表
        $smarty->assign('is_merge_shipment', $is_merge_shipment);      // 是否合并发货
        $smarty->assign('is_split_shipment', $is_split_shipment);      // 是否分开发货
        $smarty->assign('is_primary_shipment', $is_primary_shipment);  // 是否是发货的主订单
        $smarty->assign('screened_shipment_flag', $screened_shipment_flag);
        $smarty->assign('screened_barcode_flag', $screened_barcode_flag);
    }
	// 订单没有找到
    else {
        $smarty->assign('message', '系统中没有该订单');
    }
}
$user_facility_list = array_intersect_assoc(get_available_facility(),get_user_facility());
$smarty->assign('is_third_party',$is_third_party);//判断是否是第三方仓库                                                       
$smarty->assign('big_goods_number',get_big_goods_number());  // 得到大订单中大商品的界线数值
$smarty->assign('facility_name', facility_mapping(implode(',',array_keys($user_facility_list))));

$smarty->assign('sf_arata',checkSFArataStatus($order_sn));

$smarty->display('distributor/distribution_delivery.htm');  

function checkSFArataStatus($order_sn){
    global $db;
    $sql="SELECT oi.facility_id,oi.shipping_id FROM ecshop.ecs_order_info oi WHERE oi.order_sn='{$order_sn}'";
    $line=$db->getRow($sql);
    if($line['facility_id']=='3580047'){//乐其东莞仓
        if(in_array($line['shipping_id'],array(44,117))){//顺丰
            return 1;
        }
    }
    return 0;
}