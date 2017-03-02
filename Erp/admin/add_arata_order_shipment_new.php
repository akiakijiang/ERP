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
$url = 'add_arata_order_shipment_new.php';
if ($shipment_id) {
	
    //85	圆通快递 ; 89	申通快递 ; 99	汇通快递 ; 100	韵达快递 ; 115	中通快递 ;12 宅急送快递;117 顺丰陆运 ; 44顺丰快递
    $arata_shipping_ids=array('85','89','99','100','115','12','117','44');
    // 如果传递了发货单号则查询相关信息
    $sql = "select * from romeo.shipment where shipment_id = '{$shipment_id}' ";
    $shipment = $db->getRow($sql);
    if(in_array($shipment['CARRIER_ID'],array('63','62')) || strstr($shipment['TRACKING_NUMBER'],'VB')!=false){
    	header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment_id}）对应快递是京东cod或京东配送，不支持追加面单"));
        exit; 
    }elseif(!in_array($shipment['SHIPMENT_TYPE_ID'],$arata_shipping_ids)){
    	header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment_id}）暂不支持热敏追加方式，请换用普通追加"));
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
    /**
    if(in_array($shipment['shipment_type_id'],array('146','149'))){
		$sql = "SELECT 1
		FROM  ecshop.ecs_order_info oi 
		inner join romeo.distributor_shipping pds on  pds.distributor_id = oi.distributor_id and pds.shipping_id = oi.shipping_id and pds.is_delete = 0
		where oi.order_id = '{$shipment['PRIMARY_ORDER_ID']}' limit 1 ";
	}else{*/
		$sql = "SELECT 1
		FROM ecshop.ecs_order_info oi 
		inner join romeo.facility_shipping pfs on  pfs.facility_id = oi.facility_id and pfs.shipping_id = oi.shipping_id and pfs.is_delete = 0
		where oi.order_id = '{$shipment['PRIMARY_ORDER_ID']}' limit 1 ";
//	}
	$arata = $db->getOne($sql);
	if(empty($arata)){
		header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment_id}）不是热敏组合，请使用“追加普通面单”页面进行普通追加"));
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
	$json = new JSON;
	$result = array();
    switch ($act) {
		case 'add_unique_shipment':
			$add_unique_shipment_id=trim($_REQUEST['shipment_id']);
			$add_unique_shipping_id=trim($_POST['shipping_id']);
			if(in_array($add_unique_shipping_id,array('146','149'))){
				$add_unique_arata= '';
				/*$sql = "SELECT 1
				FROM  romeo.order_shipment os  
				inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id
				inner join romeo.distributor_shipping pds on  pds.distributor_id = oi.distributor_id and pds.shipping_id = oi.shipping_id and pds.is_delete = 0
				where os.shipment_id='{$add_unique_shipment_id}'";*/
			}else{
				$sql = "SELECT 1
				FROM  romeo.order_shipment os  
				inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id
				inner join romeo.facility_shipping pfs on  pfs.facility_id = oi.facility_id and pfs.shipping_id = oi.shipping_id and pfs.is_delete = 0
				where os.shipment_id='{$add_unique_shipment_id}'  limit 1";
				$add_unique_arata= $db->getOne($sql);
			}
			
			if(!empty($add_unique_arata)){
				//热敏订单，自动生成面单
				$result['arata'] = 1;
				require_once('batch_add_order_shipment_arata_new.php');
				$tracking_number = add_thermal_trackNum(array("shipment_id"=>$add_unique_shipment_id));
				if(empty($tracking_number)|| $tracking_number=='0'){
					$result['mes']="热敏面单空了，请联系采购组手动拉取，或使用普通追加";
					$result['tracking_number'] = '';
				}elseif($tracking_number=='-1'){
					$result['mes']="热敏面单自动生成失败，请联系ERP";
					$result['tracking_number'] = '';
				}else{
					try{
						$shipment = $db->getRow("select * from romeo.shipment where shipment_id = '{$add_unique_shipment_id}' ");
						$order_list = $db->getAll("select oi.order_id,oi.order_status,oi.pay_status,oi.shipping_status from romeo.order_shipment os 
								inner join ecshop.ecs_order_info oi on oi.order_id = cast(os.order_id as unsigned)  
								where os.shipment_id = '{$add_unique_shipment_id}' ");
						$note = "增加了(热敏)快递面单".$tracking_number;
            			$handle=soap_get_client('ShipmentService','ERPSYNC');
            			$object=$handle->createShipment(array(
            		        'orderId' => $shipment['PRIMARY_ORDER_ID'],
            				'partyId' => $shipment['PARTY_ID'],
            				'shipmentTypeId'=>$shipment['SHIPMENT_TYPE_ID'],
            				'carrierId'=>$shipment['CARRIER_ID'],
            				'trackingNumber'=>$tracking_number,
            				'createdByUserLogin'=>$_SESSION['admin_name']
            			));
            			
            			foreach($order_list as $order_item){
	            			$handle->createOrderShipment(array(
	            				'orderId'=>$order_item['order_id'],
	            				'shipmentId'=>$object->return,
	            			));
							$sql = "
								INSERT INTO ecshop.ecs_order_action
								(order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
								('{$order_item['order_id']}', '{$order_item['order_status']}', '{$order_item['shipping_status']}', '{$order_item['pay_status']}', NOW(), '{$note}', '{$_SESSION['admin_name']}')
							";
							$db->query($sql);
						}
            			$result['mes']="";
						$result['tracking_number']=''.$tracking_number;
        			}
        			catch (Exception $e){
        				QLOG::LOG("shipment ".$add_unique_shipment_id." add tn ".$tracking_number." get errorInfo:".$e);
        				$result['mes']="热敏面单与订单绑定失败";
						$result['tracking_number'] = '';
        			}
				}
			}else{
				$result['arata'] = 0;
				$result['tracking_number'] = '';
			}
			break;   
    }
	print json_encode($result);
	exit;
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
		$smarty->assign('order',$order);                  // 主订单
		$smarty->assign('shipment_list',$shipment_list);  // 主订单的分开发货信息
		$smarty->assign('order_count',count($order_list));
		$smarty->assign('screened_shipment_flag', $screened_shipment_flag);
		$smarty->assign('screened_barcode_flag', $screened_barcode_flag);
		
	} while (false);
}


$smarty->display('shipment/add_arata_order_shipment_new.htm');
