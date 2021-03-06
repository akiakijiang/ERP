<?php 

/**
 * 添加面单-单个版
 * 
 * @author ljzhou 2013-10-19
 * @copyright
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('inventory_picking','ck_distribution_warehousing');
require_once('function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'includes/cls_json.php');

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
$url = 'add_order_shipment_new.php';
if ($shipment_id) {
    // 如果传递了发货单号则查询相关信息
    $sql = "select * from romeo.shipment where shipment_id = '{$shipment_id}' ";
    $shipment = $db->getRow($sql);
    if(in_array($shipment['CARRIER_ID'],array('63','62')) || strstr($shipment['TRACKING_NUMBER'],'VB')!=false){
    	header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment_id}）对应快递是京东cod或京东配送，不支持追加面单"));
        exit; 
    }
    if (!$shipment){
        header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment_id}）不存在"));
        exit;   
    }
    // 已参与批拣但未完成
    elseif (!is_null($shipment['PICKLIST_ID']) && $shipment['STATUS']!='SHIPMENT_PICKED'){
        header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment_id}）已参与批拣任务（picklistId:{$shipment['PICKLIST_ID']}），请等待批拣任务完成后才能操作"));
        exit;
    }
    
    
    //没有预订的就不让发货
    $no_reserve_order = check_shipment_all_reserved($shipment_id);
    if(!empty($no_reserve_order)) {
		 header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment_id}）对应的订单未预订成功（orderId:{$no_reserve_order}）"));
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
    $sql = " SELECT os.* from romeo.order_shipment os 
			INNER JOIN romeo.shipment s on s.SHIPMENT_ID = os.SHIPMENT_ID 
			where os.shipment_id = '{$shipment_id}' ";
	$order_shipments = $db->getAll($sql);	
    if(!empty($order_shipments)){
    	$order_shipment_list = $order_shipments;
    } else{
        header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment_id}）异常，找不到对应的主订单"));
        exit;
    }
    
	$i = 0;
	foreach($order_shipment_list as $orderShipment) {
        $order_list[$i] = get_core_order_info('', $orderShipment['ORDER_ID']);
		if ($shipment['PRIMARY_ORDER_ID'] == $orderShipment['ORDER_ID']){
			$order = $order_list[$i];
			$sql = " SELECT s.* from romeo.order_shipment os 
				INNER JOIN romeo.shipment s on s.SHIPMENT_ID = os.SHIPMENT_ID 
				where os.ORDER_ID = '{$order['order_id']}' and s.SHIPPING_CATEGORY='SHIPPING_SEND' ";
			$shipment_list_arr = $db->getAll($sql);
			
			if (!empty($shipment_list_arr)) { 
				foreach($shipment_list_arr as $_shipment){
					if ($_shipment['PRIMARY_ORDER_ID'] != $shipment['PRIMARY_ORDER_ID']){
						$shipment_list[] = $_shipment;
					} elseif ($_shipment['SHIPMENT_ID'] != $shipment['SHIPMENT_ID']) {
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
        // 获取重要备注——后来贯钢说备注都显示出来
        //$sql = "select action_note from ecs_order_action where order_id = '{$order['order_id']}' and note_type = 'SHIPPING' ";
        $sql = "select action_note from ecs_order_action where order_id = '{$order['order_id']}' ";
        $important_note = $db->getCol($sql);
        if (is_array($important_note)) {
            $order['important_note'] = join("；<br>", $important_note);
        }
        $order_id_list[] = $order['order_id'];
    }
    
    $url = add_param_in_url($url, 'shipment_id', $shipment['SHIPMENT_ID']);

}

if ($message) {
    // 如果传递过来了消息则显示消息
    $smarty->assign('message', $message);
}

$act=isset($_POST['act'])?$_POST['act']:$_REQUEST['act'];
/**
 * 动作处理
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($act)){
    switch ($act) {
        /* 扫描面单号 */
        case 'waybill' :
            // 检查数据
        	Helper_Array::removeEmpty($_POST);

            // 已经出库，待扫面单（扫描主面单）
            if ($order['shipping_status'] == 9) {
				$trackingNumber=$_POST['shipment_tracking_number'][$shipment['SHIPMENT_ID']];
            	if(empty($trackingNumber)){
                	$smarty->assign('message', "请输入面单号");
                	break;
            	}
            
            	if (is_null($shipment['PICKLIST_ID'])  // 没有参与批拣
            		|| $shipment['TRACKING_NUMBER']!=$trackingNumber) {  // 参与批拣了但修改了串号
	            	$sql = "update romeo.shipment set tracking_number='{$trackingNumber}',last_modified_by_user_login='{$_SESSION['admin_name']}' where shipment_id='{$shipment['SHIPMENT_ID']}' ";
	            	$db->query($sql);
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
                    }
                }
                
                header("Location: ".add_param_in_url($url,'message',$note));
                exit;
            }
            // 已扫面单，需要更改或添加（多对多、多对一、一对多）
            // 暂时允许特殊状态：已发货，收货确认
            else if (in_array($order['shipping_status'],array(1,2,8))){
            	$trackingNumberFrom=$trackingNumberTo=array();
            	$trackingNumberChanged=false;
            	foreach($shipment_list as $shipment_item){
            		$trackingNumberFrom[]=$shipment_item['TRACKING_NUMBER'];
            		
            		if(isset($_POST['shipment_tracking_number'][$shipment_item['SHIPMENT_ID']])){
						$trackingNumber=$_POST['shipment_tracking_number'][$shipment_item['SHIPMENT_ID']];
						$trackingNumberTo[]=$trackingNumber;
		                if ($shipment_item['TRACKING_NUMBER']!=$trackingNumber) {
		                	$trackingNumberChanged=true;
		                   	$sql = "update romeo.shipment set tracking_number='{$trackingNumber}',last_modified_by_user_login='{$_SESSION['admin_name']}' where shipment_id='{$shipment_item['SHIPMENT_ID']}' ";
	            			$db->query($sql);
			            }
            		}
            		
            		unset($_POST['shipment_tracking_number'][$shipment_item['SHIPMENT_ID']]);
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
	            				'partyId' => $shipment['PARTY_ID'],
	            				'shipmentTypeId'=>$shipment['SHIPMENT_TYPE_ID'],
	            				'carrierId'=>$shipment['CARRIER_ID'],
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
            			echo($e);
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
						// killed 20160105 by Sinri
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
            	$smarty->assign('message', "该订单还未出库或已发货，不是已复核状态，不能再扫描或更改面单");
            }
            
            break; /* 提交面单结束 */
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
		// 暂时可以允许特殊状态： 发货、收货确认
		if (!in_array($order['shipping_status'],array(1,2,8)) ) {
			$smarty->assign('message', "该发货单不是已复核状态：".$shipment['SHIPMENT_ID']);
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


		// 格式化订单的配货商品结构,将erp记录按order_goods_id分组
		foreach($order_list as $order_key=>$order_item)
		{
			$item_list=array();
			foreach($order_item['order_goods'] as $goods_key=>$order_goods)
			{
				$key=$order_goods['rec_id'];
				$sql = "
				    select 1 from ecshop.ecs_goods g, ecshop.ecs_category c
				    where g.cat_id = c.cat_id and c.cat_name = '虚拟商品' and g.goods_id = '{$order_goods['goods_id']}'
					limit 1
				";
				$item_list[$key]['is_productcode'] = false;
				if($db->getOne($sql)){
					$item_list[$key]['is_productcode'] = true;
				}

				$item_list[$key]['goods_name']=$order_goods['goods_name'];
				$item_list[$key]['goods_number']=$order_goods['goods_number'];
				$item_list[$key]['rec_id']=$order_goods['rec_id'];
				$item_list[$key]['productcode']=encode_goods_id($order_goods['goods_id'], $order_goods['style_id']);
				$item_list[$key]['erp'][]=$order_goods;
				$item_list[$key]['goods_type']=getInventoryItemType($order_goods['goods_id']);
				$item_list[$key]['status_id']=$order_goods['status_id'] == 'INV_STTS_AVAILABLE' ? '全新' : ( $order_goods['status_id'] == 'INV_STTS_USED' ? '二手' : $order_goods['status_id']) ;
			}
			$order_list[$order_key]['item_list']=$item_list;
		}
		//需要屏蔽发货按钮的仓库列表
		$screened_shipment_facility_list = array('22143846', '22143847', '19568549', '24196974','19568548','3580047');
		$screened_shipment_flag = false;
		if (in_array($order['facility_id'], $screened_shipment_facility_list)) {
		    $screened_shipment_flag = true;
		}
		//上海仓除了金佰利外屏蔽条码
		$screened_barcode_facility_list = array('22143846', '22143847', '19568549', '24196974');
		$screened_barcode_flag = false;
		if (in_array($order['facility_id'], $screened_barcode_facility_list) && $_SESSION['party_id'] != '65558') {
			$screened_barcode_flag = true;
		}
		
		// 显示配送信息
		$smarty->assign('shipment',$shipment);            // 配送信息
		$smarty->assign('order_list',$order_list);        // 订单列表
		$smarty->assign('order',$order);
		
		$smarty->assign('shipment_list',$shipment_list);  // 主订单的分开发货信息
		$smarty->assign('order_count',count($order_list));
		$smarty->assign('screened_shipment_flag', $screened_shipment_flag);
		$smarty->assign('screened_barcode_flag', $screened_barcode_flag);
		
	} while (false);
}


$smarty->display('shipment/add_order_shipment_new.htm');
