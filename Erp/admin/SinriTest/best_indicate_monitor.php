<?php
define('IN_ECS', true);
require('../includes/init.php');
// require_once ('monitor_tools.php');


$monitor_header = new MonitorHeader(
	"中粮指示订单监控页",
	array('indicate_order_id'));
$smarty->assign('monitor_header', $monitor_header);


 
$indicate_order_id = trim( $_REQUEST['indicate_order_id'] ); 
   
    // 如果输入的批次订单号 不为空 
if( !empty( $indicate_order_id ) ){
		 // 指示表
		 $indicateMonitor = BestIndicate::getMonitorOfIndicateByIndicateOrderId($indicate_order_id);
	     $monitor_info_for_generate[] = $indicateMonitor['monitor_info'];
	     $indicate_supplier_return_id = $indicateMonitor['query_info']['supplier_return_id'];
	     $indicate_service_id = $indicateMonitor['query_info']['service_id'];
	     $indicate_facility_id = $indicateMonitor['query_info']['facility_id'];
	     $indicate_order_id = $indicateMonitor['query_info']['order_id']; 
	     
	     // 如果 indicate 表中存在记录 
	     if( !empty($indicate_order_id) ){
	     	 // 指示明细表
		     $indicateDetailMonitor = BestIndicate::getMonitorOfIndicateDetailByIndicateOrderId($indicate_order_id);
		     $monitor_info_for_generate[] = $indicateDetailMonitor['monitor_info'];
		     $order_goods_id = $indicateDetailMonitor['query_info']['order_goods_id'];
		      
		      // 实际明细表 
		     $actualDetailMonitor = BestActual::getMonitorOfActualDetailByIndicateOrderId($indicate_order_id);
		     $monitor_info_for_generate[] = $actualDetailMonitor['monitor_info'];
		     $actual_order_id = $indicate_order_id;
		     
 	        // $smarty->assign('msg',var_dump($actual_order_id));
		     
		     // 实际表   
		     $actualMonitor = BestActual:: getMonitorOfActualByActualOrderId($actual_order_id);
		     $monitor_info_for_generate[] = $actualMonitor['monitor_info'];
		     
		       // 供应商申请表 
	    	 $supplierReturn =  SupplierAndService::getMonitorOfSupplierReturnBySupplierReturnId($indicate_supplier_return_id);
	     	 $monitor_info_for_generate[] = $supplierReturn['monitor_info'];
		     
		      // 售后服务表 
	    	 $service =  SupplierAndService::getMonitorOfEcsServiceByServiceId($indicate_service_id);
	     	 $monitor_info_for_generate[] = $service['monitor_info'];
	     	 // 实际串号表 
	     	$actualSncodeMonitor = BestActual:: getMonitorOfActualSncodeByOrderId($actual_order_id);
	    	$monitor_info_for_generate[] = $actualSncodeMonitor['monitor_info'];
	    	// 实际包裹表 
	     	$actualPackcodeMonitor = BestActual:: getMonitorOfActualPackCodeByActualOrderId($actual_order_id);
	     	$monitor_info_for_generate[] = $actualPackcodeMonitor['monitor_info'];
	     	$actual_pack_code = $actualPackcodeMonitor['query_info']['pack_code'];
	     	 // 实际包裹明细表 
		     if( !empty($actual_pack_code)){
		     	$actualPackDetailMonitor = BestActual:: getMonitorOfActualPackDetailByPackCode($actual_pack_code);
		     	$monitor_info_for_generate[] = $actualPackDetailMonitor['monitor_info'];
		     }
		     
		      
		     // 订单商品表
		     $ecs_order_goods_monitor = EcsOrder::getMonitorOfEcsOrderGoodsByOrderGoodsId($order_goods_id);
		     $monitor_info_for_generate[] = $ecs_order_goods_monitor['monitor_info'];
		     $ecs_order_id = $ecs_order_goods_monitor['query_info']['order_id'];
		     
		     // 订单信息表 
		     $ecs_order_info_monitor =  EcsOrder::getMonitorOfEcsOrderInfoByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $ecs_order_info_monitor['monitor_info'];
		     $ecs_carrier_bill_id = $ecs_order_info_monitor['query_info']['carrier_bill_id'];
		     
		       // 订单操作记录表  ecshop.ecs_order_action
		     $ecs_order_action = EcsOrder::getMonitorOfEcsOrderActionByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $ecs_order_action['monitor_info'];
		     
		       // 订单属性表 ecshop.order_attribute
		     $ecs_order_attribute =  EcsOrder::getMonitorOfEcsOrderAttributeByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $ecs_order_attribute['monitor_info'];
		     
		      // 订单商品属性表 ecshop.order_goods_attribute
		     $ecs_order_goods_attribute = EcsOrder::getMonitorOfEcsOrderGoodsAttributeByEcsOrderGoodsId( $order_goods_id);
		     $monitor_info_for_generate[] = $ecs_order_goods_attribute['monitor_info'];
		     
/*			    公元2015.12.04        创世神伸出了邪恶的触手，摧毁了这个世界
		     //订单快递属性表 ecshop.ecs_carrier_bill
		     $ecs_carrier_bill =  EcsOrder::getMonitorOfEcsCarrierBillByBillId($ecs_order_id);
		     $monitor_info_for_generate[] = $ecs_carrier_bill['monitor_info'];
*/
		     
		     // 订单状态历史表 ecshop.order_mixed_status_history
		     $ecs_order_mixedStatus_history =EcsOrder::getMonitorOfEcsOrderMixedStatusHistoryByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $ecs_order_mixedStatus_history['monitor_info'];
		     
		     // 订单状态备注表 ecshop.order_mixed_status_note 
		     $ecs_order_mixedStatus_note = EcsOrder::getMonitorOfEcsOrderMixedStatusNoteByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $ecs_order_mixedStatus_note['monitor_info'];
		     
		      // 淘宝订单映射表 ecshop.ecs_taobao_order_mapping
		     $ecs_taobao_order_mapping = EcsOrder::getMonitorOfEcsTaobaoOrderMappingByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $ecs_taobao_order_mapping['monitor_info'];
		     
		      // 新淘宝订单映射表 ecshop.ecs_order_mapping ，添加ecs_order_mapping中间表，废除ecs_taobao_order_mapping用
		     $ecs_order_mapping = EcsOrder::getMonitorOfEcsOrderMappingByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $ecs_order_mapping['monitor_info'];
		     
		     // 快递订单映射表 romeo.order_shipment
		     $romeo_order_shipment = EcsOrder::getMonitorOfRomeoOrderShipmentByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] =$romeo_order_shipment['monitor_info'];
		     
		     // 快递表 romeo.shipment
		     $romeo_shippment =  EcsOrder::getMonitorOfRomeoShipmentByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $romeo_shippment['monitor_info'];
		     
		      // 预定表 romeo.order_inv_reserved
		     $romeo_order_inv_reserved = Inventory::getMonitorOfRomeoOrderInvReservedByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $romeo_order_inv_reserved['monitor_info'];
		     
		     // 预定表明细 romeo.order_inv_reserved_detail
			 // 可以获得 $order_inv_reserved_detail_id 
		     $romeo_order_inv_reserved_detail = Inventory::getMonitorOfRomeoOrderInvReservedDetailByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $romeo_order_inv_reserved_detail['monitor_info']; 
		     $order_inv_reserved_detail_id =  $romeo_order_inv_reserved_detail['query_info']['ORDER_INV_RESERVED_DETAIL_ID'];
		     
		     if( !empty($order_inv_reserved_detail_id) ){
		     	// 预定表inventory_item 映射[romeo.order_inv_reserved_inventory_mapping] 需要传入 $order_inv_reserved_detail_id 
		     	$romeo_order_inv_reserved_inventory_mapping = 
		     					Inventory::getMonitorOfRomeoOrderInvReservedInventoryMappingByOrderInvReservedDetailID($order_inv_reserved_detail_id);
		     	$monitor_info_for_generate[] = $romeo_order_inv_reserved_inventory_mapping['monitor_info'];
		     }
		     
		       
		     // 库存记录明细表[romeo.inventory_item_detail] 
	         // 可以得到 字段 INVENTORY_ITEM_ID 在表  romeo.inventory_item 查询时使用
	         // 可以得到  INVENTORY_TRANSACTION_ID   在查 romeo.inventory_transaction 时使用 
		     $romeo_inventory_item_detail =Inventory::getMonitorOfRomeoInventoryItemDetailByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $romeo_inventory_item_detail['monitor_info']; 
		     $inventory_item_id = $romeo_inventory_item_detail['query_info']['INVENTORY_ITEM_ID'];
		     $inventory_transaction_id =  $romeo_inventory_item_detail['query_info']['INVENTORY_TRANSACTION_ID'];
		     
		     //库存记录表[romeo.inventory_item] 根据 $inventory_item_id 获取信息  可以得到 product_id 
		     if(!empty($inventory_item_id)){
		     	$romeo_inventory_item =Inventory::getMonitorOfRomeoInventoryItemByInventoryItemId($inventory_item_id);
		     	$monitor_info_for_generate[] = $romeo_inventory_item['monitor_info']; 
		     	$product_id = $romeo_inventory_item['query_info']['PRODUCT_ID'];
		     	
		     	 // 库存转移记录表[romeo.inventory_transaction]
		   		 if(!empty($inventory_transaction_id ) ){
		    		$romeo_inventory_transaction =Inventory:: getMonitorOfRomeoInventoryTransactonByInventoryTransactionId($inventory_transaction_id);
		    		$monitor_info_for_generate[] = $romeo_inventory_transaction['monitor_info']; 
		   		 }
		   		 
		   		  // 库存汇总表[romeo.inventory_summary] 
		   		 if( !empty($product_id)){
		   		 	$romoeo_inventory_summary =Inventory::getMonitorOfRomeoInventorySummaryBy($indicate_facility_id,$product_id);
		   		 	$monitor_info_for_generate[] = $romoeo_inventory_summary['monitor_info'];
		   		 	
		   		 }
		     }
		     
			   // 批次订单映射表 
		     $ecs_batch_order_mapping_monitor = EcsBatch::getMonitorOfEcsBatchOrderMappingByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $ecs_batch_order_mapping_monitor['monitor_info'];
		     $ecs_batch_order_id = $ecs_batch_order_mapping_monitor['query_info']['batch_order_id'];
		     
		     // 批次订单信息表 
		     $ecs_batch_order_info_monitor = EcsBatch::getMonitorOfEcsBatchOrderInfoByEcsBatchOrderId($ecs_batch_order_id);
		     $monitor_info_for_generate[] = $ecs_batch_order_info_monitor['monitor_info'];
		    
		     // 供应商退货批次订单映射表  
		     $ecs_batch_gt_mapping = EcsBatch::getMonitorOfEcsBatchGtMappingByEcsOrderId($ecs_order_id);
		     $monitor_info_for_generate[] = $ecs_batch_gt_mapping['monitor_info'];
		     $batch_gt_sn = $ecs_batch_gt_mapping['query_info']['batch_gt_sn'];
		     
		     // 供应商退货批次表 
		     $ecs_batch_gt_info = EcsBatch::getMonitorOfEcsBatchGtInfoByBatchGtSn($batch_gt_sn);
		     $monitor_info_for_generate[] = $ecs_batch_gt_info['monitor_info'];
		      
		     $smarty->assign('monitor_data', $monitor_info_for_generate);
	     }else{
	     	$smarty->assign('msg','ecshop.express_best_indicate表中的order_id输入错误请重新输入');
	     }
}else{
	$smarty->assign('msg','请输入ecshop.express_best_indicate表中的order_id进行查询，可输入多个，以逗号隔开');
} 

 

$smarty->display('SinriTest/common_monitor.htm');



/**
 *  BestIndicate 类 用来 处理 ecshop.express_best_indicate 和 ecshop.express_best_indicate_detail 表 
 */
class BestIndicate{
	 
	/**
	 *  指示表[ecshop.express_best_indicate] 根据 indicate 表的 order_id 获取 表的信息 构建 监控 返回监控  
	 *  可以获得  supplier_return_id  service_id facility_id
	 */
	public static function getMonitorOfIndicateByIndicateOrderId($indicate_order_id){
		$sql = null;
		if( !empty($indicate_order_id)){
			$sql = "SELECT * FROM  ecshop.express_best_indicate WHERE ". Helper::db_create_in('order_id',$indicate_order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'指示表[ecshop.express_best_indicate]',$sql,'order_id',array('order_id','supplier_return_id','service_id','facility_id'));
		return $result; 
	}
	
   
    
    /**
	 *  根据 ecshop.express_best_indicate_detail 表的 order_id 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getMonitorOfIndicateDetailByIndicateOrderId($indicate_order_id){
		$sql = null;
		if( !empty($indicate_order_id)){
			$sql = "SELECT * FROM  ecshop.express_best_indicate_detail WHERE ". Helper::db_create_in('order_id',$indicate_order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'指示明细表[ecshop.express_best_indicate_detail]',$sql,'indicate_detail_id',array('indicate_detail_id','order_id','order_goods_id'));
		return $result; 
	}
	
}



/**
 *  供应商申请表[romeo.supplier_return_request]  售后申请表[ecshop.service] 
 */
class SupplierAndService{
	 
	/**
	 *    供应商申请表[romeo.supplier_return_request]  根据 $supplier_return_id 获取信息 
	 */
	public static function getMonitorOfSupplierReturnBySupplierReturnId($supplier_return_id){
		$sql = null;
		if( !empty($supplier_return_id)){
		    $sql = "SELECT *
			from romeo.supplier_return_request
			where ".Helper::db_create_in('supplier_return_id',$supplier_return_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'供应商申请表[romeo.supplier_return_request]', $sql, 'supplier_return_id',array('supplier_return_id'));
		return $result; 
	}
	
	/**
	 *   售后服务表[ecshop.service]
	 */
	public static function getMonitorOfEcsServiceByServiceId($service_id){
		$sql = null;
		if( !empty($service_id)){
			$sql = "SELECT *
			from ecshop.service
			where ".Helper::db_create_in('service_id',$service_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'售后服务申请表[ecshop.service]', $sql, 'service_id',array('service_id'));
	    return $result;
	}
	
}


/**
 *   包括 ecshp.express_best_actual 表 和 ecshop.express_best_actual_detail 表  ecshop.express_best_actual_sncode 表
 *   实际包裹表[ecshop.express_best_actual_package]
 */
class BestActual{
	 
	/**
	 *  ecshop.express_best_actual_detail 根据  order_goods_id 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getMonitorOfActualDetailByOrderGoodsId($order_goods_id){
		$sql = null;
		if( !empty($order_goods_id)){
			$sql = "SELECT * FROM ecshop.express_best_actual_detail WHERE ". Helper::db_create_in('order_goods_id',$order_goods_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'实际明细表[ecshop.express_best_actual_detail]',$sql,'actual_detail_id',array('actual_detail_id','order_goods_id','order_id'));
		return $result; 
	}
	
		/**
	 *  ecshop.express_best_actual_detail 根据  order_id 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getMonitorOfActualDetailByIndicateOrderId($indicate_order_id){
		$sql = null;
		if( !empty($indicate_order_id)){
			$sql = "SELECT * FROM ecshop.express_best_actual_detail WHERE ". Helper::db_create_in('order_id',$indicate_order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'实际明细表[ecshop.express_best_actual_detail]',$sql,'actual_detail_id',array('order_id'));
		return $result; 
	}
	
	 
    /**
	 *  ecshop.express_best_actual 表 根据 order_id   获取 表的信息 构建 监控 返回监控  
	 */
	public static function getMonitorOfActualByActualOrderId($actual_order_id){
		$sql = null;
		if( !empty($actual_order_id)){
			$sql = "SELECT * FROM  ecshop.express_best_actual WHERE ". Helper::db_create_in('order_id',$actual_order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'实际表[ecshop.express_best_actual]',$sql,'order_id',array('order_id'));
		return $result; 
	}
	
	/**
	 *  实际串号表[ecshop.express_best_actual_sncode] 
	 */
	public static function getMonitorOfActualSncodeByOrderId($actual_order_id){
		$sql = null;
		if( ! empty($actual_order_id)){
			 $sql = "SELECT * from ecshop.express_best_actual_sncode
			 where ". Helper::db_create_in('order_id',$actual_order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'实际串号表[ecshop.express_best_actual_sncode]',$sql,'sn_code',array('sn_code'));
		return $result; 
	}
	
	/**
	 * 实际包裹表[ecshop.express_best_actual_package] 根据 actual order_id 获取信息 
	 */
	public static function getMonitorOfActualPackCodeByActualOrderId($actual_order_id){
		$sql ;
		if(empty($actual_order_id)){
			$sql = null;
		}else{
				$sql = "SELECT *
			from ecshop.express_best_actual_package
			where ".Helper::db_create_in('order_id',$actual_order_id);	
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'实际包裹表[ecshop.express_best_actual_package]',$sql,'pack_code',array('pack_code'));
		return $result; 
	}
	
     /**
	 * 实际包裹明细表[ecshop.express_best_actual_package_detail] 根据 pack_code 获取信息 
	 */
	public static function getMonitorOfActualPackDetailByPackCode($actual_pack_code){
		$sql ;
		if(empty($actual_pack_code)){
			$sql = null;
		}else{
			$sql = "SELECT *
			from ecshop.express_best_actual_package_detail
			where ".Helper::db_create_in('pack_code',$actual_pack_code);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'实际包裹明细表[ecshop.express_best_actual_package_detail]',$sql,'pack_code',array('pack_code'));
		return $result; 
	}
	
}


/**
 *   ecs_order_goods 表  ecs_order_info 表  
 */
class EcsOrder{
 
	/**
	 *  根据 ecs_order_goods 表的 rec_id 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getMonitorOfEcsOrderGoodsByOrderGoodsId($order_goods_id){
		$sql;
		if(empty($order_goods_id)){
			$sql = null;
		}else{
			$sql = "SELECT * FROM  ecshop.ecs_order_goods WHERE ". Helper::db_create_in('rec_id',$order_goods_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'订单商品表[ecshop.ecs_order_goods]',$sql,'rec_id', array('rec_id','order_id'));
		return $result; 
	}
	
    
    
    
    /**
	 *  根据 ecshop.ecs_order_info 表的 order_id 获取 表的信息 构建 监控 返回监控  
	 *  可以获得  carrier_bill_id 和 
	 */
	public static function getMonitorOfEcsOrderInfoByEcsOrderId($ecs_order_id){
		$sql = null ;
		if( !empty($ecs_order_id)){
			$sql = "SELECT * FROM  ecshop.ecs_order_info WHERE ". Helper::db_create_in('order_id',$ecs_order_id );
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'订单信息表[ecshop.ecs_order_info]',$sql,'order_id',array('order_id','carrier_bill_id','facility_id'));
		return $result; 
	}
	
	
	// 订单操作记录表  ecshop.ecs_order_action
	public static function getMonitorOfEcsOrderActionByEcsOrderId($ecs_order_id){
		$sql ;
		if( empty($ecs_order_id) ) {
			$sql = null;
		}else{
			$sql = "SELECT *
			from ecshop.ecs_order_action
			where  ". Helper::db_create_in('order_id',$ecs_order_id );	
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单动作记录表[ecshop.ecs_order_action]', $sql, 'action_id');
		return $result; 
	} 
	
	
	 
	// 订单属性表 ecshop.order_attribute
	public static function getMonitorOfEcsOrderAttributeByEcsOrderId($ecs_order_id){
		$sql ;
		if( empty($ecs_order_id) ){
			$sql = null;
		}else{
			$sql = "SELECT *
			from ecshop.order_attribute
			where  ". Helper::db_create_in('order_id',$ecs_order_id );
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单属性表[ecshop.order_attribute]', $sql, 'attribute_id');
		return $result; 
	} 
	
	
	 // 订单商品属性表 ecshop.order_goods_attribute
	public static function getMonitorOfEcsOrderGoodsAttributeByEcsOrderGoodsId($ecs_order_goods_id){
		$sql ;
		if( empty($ecs_order_goods_id) ){
			$sql = null;
		}else{
			$sql = "SELECT *
			from ecshop.order_goods_attribute
			where ".Helper::db_create_in('order_goods_id',$ecs_order_goods_id);	
		}

		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单商品属性表[ecshop.order_goods_attribute]', $sql, 'order_goods_attribute_id');
		return $result; 
	} 
	
/*	
	 //订单快递属性表 ecshop.ecs_carrier_bill
	public static function getMonitorOfEcsCarrierBillByBillId($ecs_order_id){
		$sql = null;
		if( !empty($ecs_order_id) ){
			$sql = "SELECT o.carrier_bill_id,s.carrier_id,s.TRACKING_NUMBER
			from ecshop.ecs_order_info o
			left join romeo.order_shipment os on os.order_id=o.order_id
			left join romeo.shipment s on os.shipment_id = s.shipment_id
			where  ".Helper::db_create_in('o.order_id',$ecs_order_id);	
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单快递属性表[ecshop.ecs_order_info,order_shipment,shipment]', $sql, 'o.order_id');
	    return $result;
	} 
*/	
	
	 // 订单状态历史表 ecshop.order_mixed_status_history
	public static function getMonitorOfEcsOrderMixedStatusHistoryByEcsOrderId($ecs_order_id){
		$sql = null;
		if( !empty($ecs_order_id)){
		    $sql = "SELECT *
			from ecshop.order_mixed_status_history
			where  ".Helper::db_create_in('order_id',$ecs_order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单状态历史表[ecshop.order_mixed_status_history]', $sql, 'order_mixed_status_history_id');
	 	return $result;
	} 
	
	 // 订单状态备注表 ecshop.order_mixed_status_note 
	public static function getMonitorOfEcsOrderMixedStatusNoteByEcsOrderId($ecs_order_id){
		$sql = null;
		if( !empty($ecs_order_id)){
			$sql = "SELECT *
			from ecshop.order_mixed_status_note
			where ".Helper::db_create_in('order_id',$ecs_order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单状态备注表[ecshop.order_mixed_status_note]', $sql, 'order_mixed_status_note_id');
	 	return $result;
	} 
	
	
	  // 淘宝订单映射表 ecshop.ecs_taobao_order_mapping
	public static function getMonitorOfEcsTaobaoOrderMappingByEcsOrderId($ecs_order_id){
		$sql = null ;
		if(!empty($ecs_order_id)){
	        $sql = "SELECT *
			from ecshop.ecs_taobao_order_mapping
			where ".Helper::db_create_in('order_id',$ecs_order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'淘宝订单映射表[ecshop.ecs_taobao_order_mapping]', $sql, 'mapping_id');
	 	return $result;
	} 
	
	  // 新淘宝订单映射表 ecshop.ecs_order_mapping  ，添加ecs_order_mapping中间表，废除ecs_taobao_order_mapping用
	public static function getMonitorOfEcsOrderMappingByEcsOrderId($ecs_order_id){
		$sql = null ;
		if(!empty($ecs_order_id)){
	        $sql = "SELECT *
			from ecshop.ecs_order_mapping
			where ".Helper::db_create_in('erp_order_id',$ecs_order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'新淘宝订单映射表[ecshop.ecs_order_mapping]', $sql, 'mapping_id');
	 	return $result;
	} 
	
	
	
	// 快递订单映射表 romeo.order_shipment
	public static function getMonitorOfRomeoOrderShipmentByEcsOrderId($ecs_order_id){
		$sql = null ;
		if(!empty( $ecs_order_id )) {
		    $sql = "SELECT *
			from romeo.order_shipment
			where  ".Helper::db_create_in('order_id',$ecs_order_id);
		}

		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'快递订单映射表[ecshop.order_shipment]', $sql, 'order_id');
	 	return $result;
	} 
	
	 
	// 快递表 romeo.shipment
	public static function getMonitorOfRomeoShipmentByEcsOrderId($ecs_order_id){
		$sql = null;
		if( !empty($ecs_order_id)){
		    $sql = "SELECT *
			from romeo.shipment
			where ".Helper::db_create_in('primary_order_id',$ecs_order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'快递表[ecshop.shipment]', $sql, 'shipment_id');
	 	return $result;
	} 
	
}

/**
 * ecshop.ecs_batch_order_info 表 和 ecshop.ecs_batch_order_mapping 表 
 */
class EcsBatch{
 
	/**
	 *  ecshop.ecs_batch_order_mapping 根据 ecs_order_id  获取 表的信息 构建 监控 返回监控  
	 */
	public static function getMonitorOfEcsBatchOrderMappingByEcsOrderId($ecs_order_id){
		$sql = null;
		if( !empty($ecs_order_id) ) {
			$sql = "SELECT * from ecshop.ecs_batch_order_mapping WHERE ". Helper::db_create_in('order_id',$ecs_order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'批次订单映射表[ecshop.ecs_batch_order_mapping]',$sql,'order_id', array('order_id','batch_order_id'));
		return $result; 
	}
	
    
    /**
	 *  ecshop.ecs_batch_order_info 根据  batch_order_id  获取 表的信息 构建 监控 返回监控  
	 */
	public static function getMonitorOfEcsBatchOrderInfoByEcsBatchOrderId($ecs_batch_order_id){
		$sql = null;
		if( !empty($ecs_batch_order_id)){
			$sql = "SELECT * from ecshop.ecs_batch_order_info WHERE ". Helper::db_create_in('batch_order_id',$ecs_batch_order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'批次订单信息表[ecshop.ecs_batch_order_info]',$sql,'batch_order_id', array('batch_order_id'));
		return $result; 
	}
	
	/**
	 *  供应商退货映射表  ecshop.ecs_batch_gt_mapping
	 */
	public static function getMonitorOfEcsBatchGtMappingByEcsOrderId($ecs_order_id){
		$sql = null;
		if( !empty($ecs_order_id) ) {
			$sql = "SELECT * from  ecshop.ecs_batch_gt_mapping WHERE ". Helper::db_create_in('order_id',$ecs_order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'供应商退货批次订单映射表[ecshop.ecs_batch_gt_mapping]',$sql,'order_id', array('order_id','batch_gt_sn'));
		return $result; 
	}
	
	/**
	 *  供应商退货信息表  ecshop.ecs_batch_gt_info
	 */
	public static function getMonitorOfEcsBatchGtInfoByBatchGtSn($batch_gt_sn){
		$sql = null;
		if( !empty($batch_gt_sn) ) {
			$sql = "SELECT * from  ecshop.ecs_batch_gt_info WHERE ". Helper::db_create_in('batch_gt_sn',$batch_gt_sn);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'供应商退货批次信息表[ecshop.ecs_batch_gt_info]',$sql,'batch_gt_sn');
		return $result; 
	}
	
}


class Inventory{
	  // 预定表 romeo.order_inv_reserved
	public static function getMonitorOfRomeoOrderInvReservedByEcsOrderId($ecs_order_id){
		$sql = null ;
		if( !empty($ecs_order_id)){
	        $sql = "SELECT *
			from romeo.order_inv_reserved
			where ". Helper::db_create_in('order_id',$ecs_order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'预定表[romeo.order_inv_reserved]', $sql, 'order_inv_reserved_id');
	    return $result;
	}
	
	// 预定表明细 romeo.order_inv_reserved_detail
	// 可以获得 $order_inv_reserved_detail_id 
	public static function getMonitorOfRomeoOrderInvReservedDetailByEcsOrderId($ecs_order_id){
		$sql = null;
		if(!empty ($ecs_order_id )){
		    $sql = "SELECT *
			from romeo.order_inv_reserved_detail
			where ".Helper::db_create_in('order_id',$ecs_order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'预定表明细[romeo.order_inv_reserved_detail]', $sql, 'ORDER_INV_RESERVED_DETAIL_ID',array('ORDER_INV_RESERVED_DETAIL_ID'));
	    return $result;
	}
	
	// 预定表inventory_item 映射[romeo.order_inv_reserved_inventory_mapping] 需要传入 $order_inv_reserved_detail_id 
	public static function getMonitorOfRomeoOrderInvReservedInventoryMappingByOrderInvReservedDetailID($order_inv_reserved_detail_id){
		$sql = null;
		if(!empty($order_inv_reserved_detail_id)){
					$sql = "SELECT *
				from romeo.order_inv_reserved_inventory_mapping
				where order_inv_reserved_detail_id <>'' " .
				" and  ".Helper::db_create_in('order_inv_reserved_detail_id',$order_inv_reserved_detail_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'预定表inventory_item明细[romeo.order_inv_reserved_inventory_mapping]', $sql, 'order_inv_reserved_mapping_id');
	    return $result;
	}
	
	
	
	//库存相关   
	// 库存记录明细表[romeo.inventory_item_detail] 
    // 根据 ecs_order_id 获取 
    // 可以得到 字段 INVENTORY_ITEM_ID 在表  romeo.inventory_item 查询时使用
    // 可以得到  INVENTORY_TRANSACTION_ID   在查 romeo.inventory_transaction 时使用 
	public static function getMonitorOfRomeoInventoryItemDetailByEcsOrderId($ecs_order_id){
		$sql = null;
		if( !empty($ecs_order_id)){
			$sql = "SELECT INVENTORY_ITEM_DETAIL_ID, ORDER_ID, ORDER_GOODS_ID, CANCELLATION_FLAG, INVENTORY_ITEM_ID, INVENTORY_TRANSACTION_ID,QUANTITY_ON_HAND_DIFF, AVAILABLE_TO_PROMISE_DIFF, CREATED_STAMP, LAST_UPDATED_STAMP
			from romeo.inventory_item_detail
			where ".Helper::db_create_in('order_id',$ecs_order_id)." order by created_stamp desc";
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'库存记录明细表[romeo.inventory_item_detail]', $sql, 'INVENTORY_ITEM_DETAIL_ID', array('INVENTORY_ITEM_DETAIL_ID','INVENTORY_ITEM_ID', 'INVENTORY_TRANSACTION_ID'));
	    return $result;
	}
	
	
    //库存记录表[romeo.inventory_item] 根据 $inventory_item_id 获取信息  可以得到 PRODUCT_ID 
	public static function getMonitorOfRomeoInventoryItemByInventoryItemId($inventory_item_id){
		$sql = null;
		if( !empty($inventory_item_id)){
			$sql = "SELECT INVENTORY_ITEM_ID,SERIAL_NUMBER,STATUS_ID,INVENTORY_ITEM_ACCT_TYPE_ID,INVENTORY_ITEM_TYPE_ID,FACILITY_ID,
		     CONTAINER_ID,QUANTITY_ON_HAND_TOTAL,AVAILABLE_TO_PROMISE,AVAILABLE_TO_PROMISE_TOTAL,QUANTITY_ON_HAND,PRODUCT_ID,CREATED_STAMP,
		     LAST_UPDATED_STAMP,UNIT_COST,ROOT_INVENTORY_ITEM_ID,PARENT_INVENTORY_ITEM_ID,currency,provider_id ,validity,batch_sn
			from romeo.inventory_item
			where ".Helper::db_create_in('inventory_item_id',$inventory_item_id)." order by last_updated_stamp desc";
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'库存记录表[romeo.inventory_item]', $sql, 'INVENTORY_ITEM_ID',array('INVENTORY_ITEM_ID','PRODUCT_ID'));
		return $result; 
	}
	
	 // 库存转移记录表[romeo.inventory_transaction]
	 // if(!empty($inv_it_d_result['query_info']['INVENTORY_TRANSACTION_ID']) and !empty($inv_it_d_result['query_info']['INVENTORY_ITEM_ID']))
	public static function getMonitorOfRomeoInventoryTransactonByInventoryTransactionId($inventory_transaction_id){
		$sql = null;
		if( !empty($inventory_transaction_id)){
			$sql = "SELECT *
			from romeo.inventory_transaction
			where  ".Helper::db_create_in('inventory_transaction_id',$inventory_transaction_id)." order by CREATED_STAMP desc";
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'库存转移记录表[romeo.inventory_transaction]', $sql, 'INVENTORY_TRANSACTION_ID');
		return $result; 
	}
	
	// 库存汇总表[romeo.inventory_summary] 
	public static function getMonitorOfRomeoInventorySummaryBy($facility_id,$product_id){
		$sql = null;
		if( !empty($facility_id) && !empty($product_id)){
			$sql = "SELECT INVENTORY_SUMMARY_ID,STATUS_ID,FACILITY_ID,CONTAINER_ID,PRODUCT_ID,STOCK_QUANTITY,AVAILABLE_TO_RESERVED,CREATED_STAMP,LAST_UPDATED_STAMP 
			from romeo.inventory_summary
			where ".Helper::db_create_in('facility_id',$facility_id)." and " .
			     Helper::db_create_in('STATUS_ID',array('INV_STTS_AVAILABLE','INV_STTS_USED')) .
				" and ".Helper::db_create_in('product_id',$product_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'库存汇总表[romeo.inventory_summary]', $sql, 'INVENTORY_SUMMARY_ID');
		return $result; 
	}
 
}



 class Helper{
 	
	/**
	 *  在查询数据库时 构造 field_name IN ('value1','value2')
	 *  $field_name 字段名 
	 *  $value_list 值 array 或者是 逗号分隔的字符串 
	 */
	public static function db_create_in($field_name = '',$value_list )
	{
	    if (empty($value_list))
	    {
	        return $field_name . " IN ('') ";
	    }
	    else
	    {
	        if (!is_array($value_list))
	        {
	            $value_list = explode(',', $value_list);
	        }
	        $value_list = array_unique($value_list); // 去除重复数值 
	        $value_list_tmp = '';
	        foreach ($value_list AS $item)
	        {
	            $item = trim($item);
	            if ($item !== '')
	            {   
	            	if( $item[0] =="'"){
	            		$value_list_tmp .= $value_list_tmp ? ",$item" : "$item";  // in 的第一个 值 不需要逗号
	            	}else{
	            		$value_list_tmp .= $value_list_tmp ? ",'$item'" : "'$item'";  // in 的第一个 值 不需要逗号
	            	}
	            }
	        }
	        if (empty($value_list_tmp))
	        {
	            return $field_name . " IN ('') ";
	        }
	        else
	        {
	            return $field_name . ' IN (' . $value_list_tmp . ') ';
	        }
	    }
	}
	
}


class MonitorHeader
{
	public $title_ = '页面标题';
	public $search_condition_list_ = array('检索参数');
	public $back_url_ = '表单提交链接';

	function __construct($title, $search_condition_list)
	{
		$this->title_ = $title;
		$this->search_condition_list_ = $search_condition_list;
		$this->back_url_ = end(explode('/',$_SERVER['PHP_SELF']));
	}
}

function GetTableMonitorInfoAndAdditionalQueryInfoFromSQL($table_name, $sql, $primary_key_name, $ref_name_array=array()){
	global $db;
	if( ! $sql == null ){
		$extend_ref_name_array = array();
		if(!in_array($primary_key_name, $ref_name_array)){
			$extend_ref_name_array = array_merge(array($primary_key_name), $ref_name_array);
		}else{
			$extend_ref_name_array = $ref_name_array;
		}
	
		$ref_list = $data_list=array();
		$db->getAllRefBy($sql, $extend_ref_name_array, $ref_list, $data_list);
	
		$table_info = array('table_name'=>$table_name, 'attr_list' =>array(), 'item_list' => array());
		$ref_str_list = array();
		if(empty($data_list)){
			$table_info['attr_list'][] = '无记录';
		}else{
			foreach ($ref_name_array as $ref_name) {
				# code...
				$ref_data_list = $ref_list[$ref_name];
				$ref_str_list[$ref_name] = "'". implode($ref_data_list, "','") . "'";
			}
	
			$data_list = $data_list[$primary_key_name];
			$sample = reset($data_list);
			$attr_list = array();
			foreach ($sample[0] as $key => $value) {
				$attr_list[] = $key;
			}
			foreach ($data_list as $key => &$value) {
				$value = $value[0];
			}
			$table_info['attr_list'] = $attr_list;
			$table_info['item_list'] = $data_list;
		}
		$result = array('monitor_info' => $table_info, 'query_info' => $ref_str_list);
		return $result;
		
	}else{
		$table_info = array('table_name'=>$table_name, 'attr_list' =>array(), 'item_list' => array());
		$table_info['attr_list'][] = '无记录';
		$result = array('monitor_info' => $table_info, 'query_info' => array());
		return $result;
	}

}

function convert_str_to_sql($strs) {
	if(empty($strs)) return '';
	
	$nos = explode(',',$strs);
	$result = "'";
	foreach($nos as $no) {
		$result .= $no."','";
	}
	$result = substr($result,0,strlen($result)-2);
	
	return $result;
}


?>