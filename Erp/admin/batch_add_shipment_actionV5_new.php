<?php 

/**
 * 添加面单-批量版
 * 
 * @author ljzhou 2013-10-21
 * @copyright
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
//require_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');//By Sinri
require_once(ROOT_PATH . 'admin/config.vars.php');
require_once('includes/lib_sinri_DealPrint.php');

$shipment_ids = $_POST['SID'];
$tracking_numbers = $_POST['TNS'];

$message = '';
foreach($shipment_ids as $key=>$shipment_id) {

	if(empty($tracking_numbers[$key])) {
		$message .= "该发货单（shipmentId:{$shipment_id}）没有对应的面单号;";
        continue;  
	}

    // 如果传递了发货单号则查询相关信息
    $sql = "select * from romeo.shipment where shipment_id = '{$shipment_id}' ";
    $shipment = $db->getRow($sql);
    if (!$shipment){
        $message .= "该发货单（shipmentId:{$shipment_id}）不存在;";
        continue;   
    } 
    
    //没有预订的就不让发货
    $sql =" SELECT oir.status, oir.order_id
            FROM
                romeo.order_inv_reserved oir
            LEFT JOIN romeo.order_shipment os ON oir.order_id = os.order_id
            WHERE
                os.shipment_id = '{$shipment_id}'";
    $status = $db->getAll($sql);
    $status = $status[0];
    if ($status['status'] == "N" || $status == null) { 
        $message .= "该发货单（shipmentId:{$shipment_id}）对应的订单未预订成功（orderId:{$status['order_id']}）;";
        continue;   
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
	$order_shipment = $db->getAll($sql);
    if(!empty($order_shipment)){
    	$order_shipment_list = $order_shipment;
    } else{
        $message .= "该发货单（shipmentId:{$shipment_id}）异常，找不到对应的主订单;";
        continue;   
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
	// 复核过才能添加
    if ($order['shipping_status']==8){
    	$trackingNumberFrom=$trackingNumberTo=array();
    	$trackingNumberChanged=true;
    	$trackingNumber = $tracking_numbers[$key];
    	
    	// 添加面单号（分开多个面单发货）
		foreach($shipment_list as $shipment_item){
        	$trackingNumberFrom[]=$shipment_item['TRACKING_NUMBER'];
        	$trackingNumberTo[]=$shipment_item['TRACKING_NUMBER'];
        }
        $trackingNumberTo[] = $trackingNumber;
        
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
		
		}

    	
    	// 面单发生更改则更新
    	if($trackingNumberChanged){
    		// 修改提示语 ljzhou 2012.12.6
			$modify = array_diff ( $trackingNumberFrom, $trackingNumberTo );
			$add = array_diff ( $trackingNumberTo, $trackingNumberFrom );
			if (count ( $modify ) > 0) {
				$note = "批量追加：快递面单从" . implode ( ',', $modify ) . "更改为" . implode ( ',', $add );
			} else {
				$note = "批量追加：增加了快递面单" . implode ( ',', $add );
			}
			// 更新运单的运单号信息
			foreach($order_list as $order_item) {
				// killed by Sinri 20160105
				// $db->query(sprintf("UPDATE {$ecs->table('carrier_bill')} SET bill_no = '%s' WHERE bill_id = '%d'",implode(',',$trackingNumberTo),$order_item['carrier_bill_id']));
				// 记录订单备注
				$sql = "
					INSERT INTO {$ecs->table('order_action')} 
					(order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
					('{$order_item['order_id']}', '{$order_item['order_status']}', '{$order_item['shipping_status']}', '{$order_item['pay_status']}', NOW(), '{$note}', '{$_SESSION['admin_name']}')
				";
				$db->query($sql);
			}
            
    	}
    	else{
    		$message .= "该发货单（shipmentId:{$shipment_id}）面单号没有改变;";
            continue;   
    	}
    }
    // 不是可以扫面单的状态了
    else {
    	$message .= "该发货单未复核，必须复核过才能添加面单：".$shipment_id.";";
        continue;   
    }
        
}

$BPSN=$_REQUEST['BPSN'];
Qlog::log('not_success_add_shipments bpsn'.$BPSN.' shipment_ids:'.$message);

if(!empty($message)) {
	$message = $message.' 其余追加成功！未成功的请用单个添加运单功能完成！';
} else {
	$message = '批量追加成功！';
}

$shipments=getShipmentsfromBPSN($BPSN);

$update_done = true;
$OID = array();
foreach ($shipments as $no => $shipment) {
	foreach ($shipment as $key => $value) {
		if ($key=='main_order_id'){
			$OID[] = $value;
		}
	}
}
if($OID) {
	$order_ids = join(',',$OID);
}

$smarty->assign('message',$message);
$smarty->assign('shipments',$shipments);
$smarty->assign('shipment_size',count($shipments));
$smarty->assign('BPSN',$BPSN);
$smarty->assign('update_done',$update_done);
$smarty->assign('order_ids',$order_ids);

$smarty->display('shipment/batch_add_order_shipment_new.htm');

?>

