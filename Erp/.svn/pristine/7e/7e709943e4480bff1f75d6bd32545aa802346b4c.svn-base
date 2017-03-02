<?php
define('IN_ECS', true);
require('../includes/init.php');
// require_once ('monitor_tools.php');


$monitor_header = new MonitorHeader(
	"金宝贝指示订单监控页",
	array('order_id'));
$smarty->assign('monitor_header', $monitor_header);

 
$order_id = trim( $_REQUEST['order_id'] ); 
   
    // 如果输入的订单号 不为空 
if( !empty( $order_id ) ){
	global $db;
	//根据订单号确定订单类型
	$sql_array = "select order_type_id,order_id,order_sn from ecshop.ecs_order_info where order_id = '{$order_id}'";
	$order_info = $db->getRow($sql_array);
	if(!empty($order_info)){
		
		if($order_info['order_type_id']=='SALE'){
			 // 指示表
			 $indicateMonitor = BrandGymboreeSales::getGymboreeSalesOrderByOrderId($order_id);
		     $monitor_info_for_generate[] = $indicateMonitor['monitor_info'];
		     $shipmentID = $indicateMonitor['query_info']['ShipmentID'];
		     
		     // 如果 indicate 表中存在记录 
		     if( !empty($shipmentID) ){
		     	 // 指示明细表
			     $indicateDetailMonitor = BrandGymboreeSales::getGymboreeSalesOrderGoodsByOrderId($order_id);
			     $monitor_info_for_generate[] = $indicateDetailMonitor['monitor_info'];
			     $order_goods_id = $indicateDetailMonitor['query_info']['ERP_Order_Line_Num'];
			     $item = $indicateDetailMonitor['query_info']['Item'];
				
				 // 实绩明细表 
			     $actualDetailMonitor = BrandGymboreeSales::getGymboreeSalesConfirmDetailByOrderId($order_id);
			     $monitor_info_for_generate[] = $actualDetailMonitor['monitor_info'];
			     
			     // 实绩表   
			     $actualMonitor = BrandGymboreeSales:: getGymboreeSalesConfirmByOrderId($order_id);
			     $monitor_info_for_generate[] = $actualMonitor['monitor_info'];
		     }    
		     // 预定表 romeo.order_inv_reserved
		     $romeo_order_inv_reserved = Inventory::getMonitorOfRomeoOrderInvReservedByEcsOrderId($order_id);
		     $monitor_info_for_generate[] = $romeo_order_inv_reserved['monitor_info'];
		     
		     // 预定表明细 romeo.order_inv_reserved_detail
			 // 可以获得 $order_inv_reserved_detail_id 
		     $romeo_order_inv_reserved_detail = Inventory::getMonitorOfRomeoOrderInvReservedDetailByEcsOrderId($order_id);
		     $monitor_info_for_generate[] = $romeo_order_inv_reserved_detail['monitor_info']; 
		     $order_inv_reserved_detail_id =  $romeo_order_inv_reserved_detail['query_info']['ORDER_INV_RESERVED_DETAIL_ID'];
		    
			//零售单指示[ecshop.brand_gymboree_sales_order_info]
			$sql_array['零售单指示[ecshop.brand_gymboree_sales_order_info]'] = "select Interface_Record_ID,ShipmentID,transfer_status,transfer_note," .
					"created_time,updated_time, Carrier,Process_Type,ALLOCATE_COMPLETE,Order_type " .
					" from ecshop.brand_gymboree_sales_order_info where ShipmentID='{$order_id}' limit 1 ";
			//零售单指示商品信息[ecshop.brand_gymboree_sales_order_goods]
			$sql_array['零售单指示商品信息[ecshop.brand_gymboree_sales_order_goods]'] = "select Interface_Link_ID,Interface_Record_ID,ERP_Order_Line_Num,Item,Total_qty,Goods_Name,Style_Name " .
					" from ecshop.brand_gymboree_sales_order_goods where Interface_Link_ID='{$order_id}' ";
			//零售单实绩[ecshop.brand_gymboree_sales_order_confirm]
			$sql_array['零售单实绩[ecshop.brand_gymboree_sales_order_confirm]'] = "select Warehouse,ShipmentID,transfer_status,transfer_note,created_time," .
					" updated_time,ACTUAL_SHIP_DATE_TIME,Carrier,User_def2,Order_type " .
					" from ecshop.brand_gymboree_sales_order_confirm where ShipmentID='{$order_id}' limit 1 ";
			//零售单实绩商品信息[ecshop.brand_gymboree_sales_order_confirm_detail]
			$sql_array['零售单实绩商品信息[ecshop.brand_gymboree_sales_order_confirm_detail]'] = "select ShipmentID,Item,shipped_qty,Order_type,User_def2" .
					" from ecshop.brand_gymboree_sales_order_confirm_detail where ShipmentID='{$order_id}' ";
			//预定表[romeo.order_inv_reserved]
			$sql_array['预定表[romeo.order_inv_reserved]'] = "select * from romeo.order_inv_reserved where order_id = '{$order_id}' limit 1";
			//预定表明细[romeo.order_inv_reserved_detail]
			$sql_array['预定表明细[romeo.order_inv_reserved_detail]'] = "select * from romeo.order_inv_reserved_detail where order_id = '{$order_id}' limit 1";
		}else if($order_info['order_type_id']=='RMA_RETURN'){	
			// 指示表
			 $indicateMonitor = BrandGymboreeReturn::getGymboreeReturnOrderByOrderId($order_id);
		     $monitor_info_for_generate[] = $indicateMonitor['monitor_info'];
		     $indicate_order_id = $indicateMonitor['query_info']['Receipt_ID']; 
		     
		     // 如果 indicate 表中存在记录 
		     if( !empty($indicate_order_id) ){
		     	 // 指示明细表
			     $indicateDetailMonitor = BrandGymboreeReturn::getGymboreeReturnOrderGoodsByOrderId($order_id);
			     $monitor_info_for_generate[] = $indicateDetailMonitor['monitor_info'];
			     $order_goods_id = $indicateDetailMonitor['query_info']['Interface_Record_ID'];
				
				 // 实绩明细表 
			     $actualDetailMonitor = BrandGymboreeReturn::getGymboreeReturnConfirmDetailByOrderId($order_id);
			     $monitor_info_for_generate[] = $actualDetailMonitor['monitor_info'];
			     
			     // 实绩表   
			     $actualMonitor = BrandGymboreeReturn:: getGymboreeReturnConfirmByOrderId($order_id);
			     $monitor_info_for_generate[] = $actualMonitor['monitor_info'];
		     	 
		     	 // 售后服务表 
		    	 $service =  SupplierAndService::getMonitorOfEcsServiceByReturnOrderId($order_id);
		     	 $monitor_info_for_generate[] = $service['monitor_info'];
		     	 $service_id = $service['query_info']['service_id'];
		     	 $back_order_id = $service['query_info']['back_order_id'];
		     	 $sales_order_id = $service['query_info']['order_id'];
	     	 
		     } 
			//退货单指示[ecshop.brand_gymboree_return_order_info]
			$sql_array['退货单指示[ecshop.brand_gymboree_return_order_info]'] = "select Receipt_ID,Interface_Record_ID,Item,Total_qty,transfer_status," .
					"transfer_note,created_time,updated_time" .
					" from ecshop.brand_gymboree_return_order_info where Receipt_ID='{$order_id}' limit 1 ";
			//退货单指示商品信息[ecshop.brand_gymboree_return_order_goods]
			$sql_array['退货单指示商品信息[ecshop.brand_gymboree_return_order_goods]'] = "select Interface_Link_ID,Interface_Record_ID,Item,Total_qty,Goods_Name,Style_Name " .
					" from ecshop.brand_gymboree_return_order_goods where Interface_Link_ID='{$order_id}' ";
			//退货单实绩[ecshop.brand_gymboree_return_order_confirm]
			$sql_array['退货单实绩[ecshop.brand_gymboree_return_order_confirm]'] = "select Receipt_ID,Receipt_ID_Type,ARRIVED_DATE_TIME,transfer_status," .
					"transfer_note,created_time," .
					" from ecshop.brand_gymboree_return_order_confirm where Receipt_ID='{$order_id}' limit 1 ";
			//退货单实绩商品信息[ecshop.brand_gymboree_return_order_confirm_detail]
			$sql_array['退货单实绩商品信息[ecshop.brand_gymboree_return_order_confirm_detail]'] = "select Receipt_ID,ORDER_TYPE,Item,Total_qty,User_def4" .
					" from ecshop.brand_gymboree_return_order_confirm_detail where Receipt_ID='{$order_id}' ";
			//售后服务申请表[ecshop.service]
			$sql_array['售后服务申请表[ecshop.service]']="select * from ecshop.service where back_order_id = '{$order_id}' ";
		}else if($order_info['order_type_id'] =='SUPPLIER_RETURN'){
			// 指示表
			 $indicateMonitor = BrandGiessenStorageOut::getMonitorOfStorageOutByOrderSn($order_info['order_sn']);
		     $monitor_info_for_generate[] = $indicateMonitor['monitor_info'];
		     $order_gt_sn = $indicateMonitor['query_info']['leqee_erp_gt_sn'];
//		     $gt_out_from = $indicateMonitor['query_info']['fchrWarehouseID'];
//		     $gt_out_to = $indicateMonitor['query_info']['move_to'];
//		     $gt_out_item = $indicateMonitor['query_info']['Item'];
//		     $gt_out_status = $indicateMonitor['query_info']['User_def4']; 
		     
			// 供应商申请表 
	    	 $supplierReturn =  SupplierAndService::getMonitorOfSupplierReturnGTBySupplierReturnId($order_gt_sn);
	     	 $monitor_info_for_generate[] = $supplierReturn['monitor_info'];
	     	 $supplier_return_id = $supplierReturn['query_info']['supplier_return_id'];

	     	 //供应商申请商品信息表
	     	 $supplierReturnGoods =  SupplierAndService::getMonitorOfSupplierReturnGoodsBySupplierReturnId($supplier_return_id);
	     	 $monitor_info_for_generate[] = $supplierReturnGoods['monitor_info'];
			
			//供应商申请表[romeo.supplier_return_request]
			$sql_array['供应商申请表[romeo.supplier_return_request]'] = "select * from romeo.supplier_return_request r " .
					"inner join romeo.supplier_return_request_gt rg on rg.SUPPLIER_RETURN_ID = r.SUPPLIER_RETURN_ID " .
					" where rg.SUPPLIER_RETURN_GT_SN ='{$order_info['order_sn']}' ";
		}else if($order_info['order_type_id']=='PURCHASE'){
		     // 批次订单映射表 
		     $ecs_batch_order_mapping_monitor = EcsBatch::getMonitorOfEcsBatchOrderMappingByEcsOrderId($order_id);
		     $monitor_info_for_generate[] = $ecs_batch_order_mapping_monitor['monitor_info'];
		     $ecs_batch_order_id = $ecs_batch_order_mapping_monitor['query_info']['batch_order_id'];
		     
		     // 批次订单信息表 
		     $ecs_batch_order_info_monitor = EcsBatch::getMonitorOfEcsBatchOrderInfoByEcsBatchOrderId($ecs_batch_order_id);
		     $monitor_info_for_generate[] = $ecs_batch_order_info_monitor['monitor_info'];
		     
			//批次订单映射表[ecshop.ecs_batch_order_mapping]
			$sql_array['批次订单映射表[ecshop.ecs_batch_order_mapping]']="select * from ecshop.ecs_batch_order_mapping where order_id = '{$order_id}' limit 1";
			//批次订单信息表[ecshop.ecs_batch_order_info]
			$sql_array['批次订单信息表[ecshop.ecs_batch_order_info]']="select oi.* from ecshop.ecs_batch_order_info oi " .
					" left join ecshop.ecs_batch_order_mapping om on om.batch_order_id = oi.batch_order_id" .
					" where om.order_id = '{$order_id}' limit 1";
		}
		 
	     
		// 订单商品表
		$ecs_order_goods_monitor = EcsOrder::getMonitorOfEcsOrderGoodsByOrderGoodsId($order_id);
		 $monitor_info_for_generate[] = $ecs_order_goods_monitor['monitor_info'];
		 $order_goods_id = $ecs_order_goods_monitor['query_info']['rec_id'];
		 
		 // 订单信息表 
		 $ecs_order_info_monitor =  EcsOrder::getMonitorOfEcsOrderInfoByEcsOrderId($order_id);
		 $monitor_info_for_generate[] = $ecs_order_info_monitor['monitor_info'];
		 $ecs_carrier_bill_id = $ecs_order_info_monitor['query_info']['carrier_bill_id'];
		 
		   // 订单操作记录表  ecshop.ecs_order_action
		 $ecs_order_action = EcsOrder::getMonitorOfEcsOrderActionByEcsOrderId($order_id);
		 $monitor_info_for_generate[] = $ecs_order_action['monitor_info'];
		 
		   // 订单属性表 ecshop.order_attribute
		 $ecs_order_attribute =  EcsOrder::getMonitorOfEcsOrderAttributeByEcsOrderId($order_id);
		 $monitor_info_for_generate[] = $ecs_order_attribute['monitor_info'];
		
		     
		     // 库存记录明细表[romeo.inventory_item_detail] 
	         // 可以得到 字段 INVENTORY_ITEM_ID 在表  romeo.inventory_item 查询时使用
	         // 可以得到  INVENTORY_TRANSACTION_ID   在查 romeo.inventory_transaction 时使用 
		     $romeo_inventory_item_detail =Inventory::getMonitorOfRomeoInventoryItemDetailByEcsOrderId($order_id);
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
		   		 	$romoeo_inventory_summary =Inventory::getMonitorOfRomeoInventorySummaryBy($product_id);
		   		 	$monitor_info_for_generate[] = $romoeo_inventory_summary['monitor_info'];
		   		 	
		   		 }
		     }
		
		//订单详情[ecshop.ecs_order_info]
		$sql_array['订单详情[ecshop.ecs_order_info]']="select order_id,party_id,facility_id,order_sn,order_time,order_status,shipping_status,pay_status,shipping_id," .
				" shipping_name,pay_id,order_type_id where order_id = '{$order_id}' limit 1 ";	
		//订单商品详情[ecshop.ecs_order_goods]
		$sql_array['订单商品详情[ecshop.ecs_order_goods]'] = "select rec_id,order_id,goods_id,goods_name,goods_number,status_id  from ecshop.ecs_order_goods where order_id = '{$order_id}' ";	
		//订单动作记录表[ecshop.ecs_order_action]
		$sql_array['订单动作记录表[ecshop.ecs_order_action]'] = "select * from ecshop.ecs_order_action where order_id = '{$order_id}'";
		//订单属性表[ecshop.order_attribute]
		$sql_array['订单属性表[ecshop.order_attribute]'] = "select * from ecshop.order_attribute where order_id = '{$order_id}'";
		//订单商品属性表[ecshop.order_goods_attribute]
		$sql_array['订单商品属性表[ecshop.order_goods_attribute]'] = "select oga.* from ecshop.order_goods_attribute oga " .
				"inner join ecshop.ecs_order_goods og on og.rec_id = oga.order_goods_id " .
				"where og.order_id = '{$order_id}'";
		//库存记录明细表[romeo.inventory_item_detail]
		$sql_array['库存记录明细表[romeo.inventory_item_detail]'] = "SELECT INVENTORY_ITEM_DETAIL_ID, ORDER_ID, ORDER_GOODS_ID, CANCELLATION_FLAG, INVENTORY_ITEM_ID, INVENTORY_TRANSACTION_ID,QUANTITY_ON_HAND_DIFF, AVAILABLE_TO_PROMISE_DIFF, CREATED_STAMP, LAST_UPDATED_STAMP
			from romeo.inventory_item_detail
			where order_id = '{$order_id}' order by created_stamp desc";
		//库存记录表[romeo.inventory_item]
		$sql_array['库存记录表[romeo.inventory_item]'] = "SELECT ii.INVENTORY_ITEM_ID,SERIAL_NUMBER,STATUS_ID,INVENTORY_ITEM_ACCT_TYPE_ID,INVENTORY_ITEM_TYPE_ID,FACILITY_ID,
		     CONTAINER_ID,QUANTITY_ON_HAND_TOTAL,AVAILABLE_TO_PROMISE,AVAILABLE_TO_PROMISE_TOTAL,QUANTITY_ON_HAND,PRODUCT_ID,CREATED_STAMP,
		     ii.LAST_UPDATED_STAMP,ii.UNIT_COST,ROOT_INVENTORY_ITEM_ID,PARENT_INVENTORY_ITEM_ID,currency,provider_id ,validity,batch_sn
			from romeo.inventory_item ii  
			inner join romeo.inventory_item_detail iid on iid.inventory_item_id = ii.inventory_item_id
			where iid.order_id = '{$order_id}'  order by ii.last_updated_stamp desc";
		//库存转移记录表[romeo.inventory_transaction]
		$sql_array['库存转移记录表[romeo.inventory_transaction]'] = "SELECT it.* from romeo.inventory_transaction it  
			inner join romeo.inventory_item_detail iid on iid.INVENTORY_TRANSACTION_ID = it.INVENTORY_TRANSACTION_ID
			where where iid.order_id = '{$order_id}'  order by it.CREATED_STAMP desc desc";
		//库存汇总表[romeo.inventory_summary]
		$sql_array['']="SELECT is.INVENTORY_SUMMARY_ID,is.STATUS_ID,is.FACILITY_ID,is.CONTAINER_ID,is.PRODUCT_ID,is.STOCK_QUANTITY,is.AVAILABLE_TO_RESERVED,is.CREATED_STAMP,is.LAST_UPDATED_STAMP 
			from romeo.inventory_summary is 
			inner join romeo.inventory_item ii on ii.facility_id = is.facility_id and ii.product_id = is.product_id and ii.status_id=is.STATUS_ID  
			inner join romeo.inventory_item_detail iid on iid.inventory_item_id = ii.inventory_item_id
			where iid.order_id = '{$order_id}' ";
//		$monitor_data_list_ = array();	
//		foreach($sql_array as $title=>$sql){
//			$tmp_array = $db->getAll($sql);
//			if(empty($tmp_array)){
//				continue;
//			}else{
//				$monitor_data_list_[$title] = $tmp_array;
//			}
//		}	
//		$smarty->assign('monitor_data_list_',$monitor_data_list_);

		$smarty->assign('monitor_data', $monitor_info_for_generate);
	}else{
		$smarty->assign('msg','order_id输入错误请重新输入');
	}
}else{
	$smarty->assign('msg','请输入order_id进行查询');
} 

$smarty->display('SinriTest/gymboree_order_monitor.htm');



/**
 *  BrandGymboreeSales 类 用来 处理 ecshop.brand_gymboree_sales_order_info 和 ecshop.brand_gymboree_sales_order_confirm 表 
 */
class BrandGymboreeSales{
	 
	/**
	 *  指示表[ecshop.brand_gymboree_sales_order_info] 根据 sales 表的 Interface_Record_ID 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getGymboreeSalesOrderByOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
			$sql = "select Interface_Record_ID,ShipmentID,transfer_status,transfer_note," .
					"created_time,updated_time, Carrier,Process_Type,ALLOCATE_COMPLETE,Order_type " .
					" from ecshop.brand_gymboree_sales_order_info where ". Helper::db_create_in('ShipmentID',$order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'零售单指示表[ecshop.brand_gymboree_sales_order_info]',$sql,'ShipmentID',array('ShipmentID'));
		return $result; 
	}
	
   
    
    /**
	 *  根据 ecshop.brand_gymboree_sales_order_goods 表的 Interface_Link_ID 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getGymboreeSalesOrderGoodsByOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
			$sql = "select Interface_Link_ID,Interface_Record_ID,ERP_Order_Line_Num,Item,Total_qty,Goods_Name,Style_Name " .
					" from ecshop.brand_gymboree_sales_order_goods where ". Helper::db_create_in('Interface_Link_ID',$order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'零售单指示商品明细表[ecshop.brand_gymboree_sales_order_goods]',$sql,'Interface_Record_ID',array('Interface_Link_ID','ERP_Order_Line_Num','Item'));
		return $result; 
	}
	
	/**
	 *  ecshop.brand_gymboree_sales_order_confirm_detail 根据  ShipmentID 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getGymboreeSalesConfirmDetailByOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
			$sql = "select ShipmentID,Item,shipped_qty,Order_type,User_def2" .
					" from ecshop.brand_gymboree_sales_order_confirm_detail where ". Helper::db_create_in('ShipmentID',$order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'零售单实绩商品明细表[ecshop.brand_gymboree_sales_order_confirm_detail]',$sql,'Item');
		return $result; 
	}
	
	 
    /**
	 *  ecshop.brand_gymboree_sales_order_confirm 根据  ShipmentID 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getGymboreeSalesConfirmByOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
			$sql = "select Warehouse,ShipmentID,transfer_status,transfer_note,created_time," .
					" updated_time,ACTUAL_SHIP_DATE_TIME,Carrier,User_def2,Order_type " .
					" from ecshop.brand_gymboree_sales_order_confirm where ". Helper::db_create_in('ShipmentID',$order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'零售单实绩表[ecshop.brand_gymboree_sales_order_confirm]',$sql,'ShipmentID');
		return $result; 
	}
	
}


/**
 *  BrandGymboreeReturn 类 用来 处理 ecshop.brand_gymboree_return_order_info 和 ecshop.brand_gymboree_return_order_confirm 表 
 */
class BrandGymboreeReturn{
	 
	/**
	 *  指示表[ecshop.brand_gymboree_sales_order_info] 根据 sales 表的 Interface_Record_ID 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getGymboreeReturnOrderByOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
			$sql = "select * " .
					" from ecshop.brand_gymboree_return_order_info where ". Helper::db_create_in('Receipt_ID',$order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'退货单指示表[ecshop.brand_gymboree_return_order_info]',$sql,'Receipt_ID',array('Receipt_ID'));
		return $result; 
	}
	
   
    
    /**
	 *  根据 ecshop.brand_gymboree_return_order_goods 表的 Interface_Link_ID 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getGymboreeReturnOrderGoodsByOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
			$sql = "select Interface_Link_ID,Interface_Record_ID,Item,Total_qty,User_def3,Goods_Name,Style_Name " .
					" from ecshop.brand_gymboree_return_order_goods where ". Helper::db_create_in('Interface_Link_ID',$order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'退货单指示商品明细表[ecshop.brand_gymboree_return_order_goods]',$sql,'Interface_Record_ID',array('Interface_Link_ID','Interface_Record_ID'));
		return $result; 
	}
	
	/**
	 *  ecshop.brand_gymboree_return_order_confirm_detail 根据  Receipt_ID 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getGymboreeReturnConfirmDetailByOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
			$sql = "select Receipt_ID,ORDER_TYPE,Item,Total_qty,User_def4" .
					" from ecshop.brand_gymboree_return_order_confirm_detail where ". Helper::db_create_in('Receipt_ID',$order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'退货单实绩商品明细表[ecshop.brand_gymboree_return_order_confirm_detail]',$sql,'Item');
		return $result; 
	}
	
	 
    /**
	 *  ecshop.brand_gymboree_return_order_confirm 根据  Receipt_ID 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getGymboreeReturnConfirmByOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
			$sql = "select Receipt_ID,Receipt_ID_Type,ARRIVED_DATE_TIME,transfer_status," .
					"transfer_note,created_time,updated_time" .
					" from ecshop.brand_gymboree_return_order_confirm where  ". Helper::db_create_in('Receipt_ID',$order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'退货单实绩表[ecshop.brand_gymboree_return_order_confirm]',$sql,'Receipt_ID');
		return $result; 
	}
	
}


class BrandGiessenStorageOut{
	/**
	 *  店存出库表[ecshop.brand_gymboree_giessen_storage_out] 根据 sales 表的 Interface_Record_ID 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getMonitorOfStorageOutByOrderSn($order_gt_sn){
		$sql = null;
		if(!empty($order_gt_sn)){
			$sql = "select fchrWarehouseID,Item,Total_qty,inout_time,User_def4,move_to,ERP_vouch_code,leqee_erp_gt_sn,created_time,updated_time " .
				" from ecshop.brand_gymboree_giessen_storage_out where leqee_erp_gt_sn = '{$order_gt_sn}' limit 1";
				
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'店存出库表[ecshop.brand_gymboree_giessen_storage_out]',$sql,'leqee_erp_gt_sn',array('leqee_erp_gt_sn'));
		return $result; 
	}
	
}
/**
 *  供应商申请表   售后申请表 
 */
class SupplierAndService{
	 
	/**
	 *    供应商申请表[romeo.supplier_return_request_gt]  根据 $supplier_return_gt_sn 获取信息 
	 */
	public static function getMonitorOfSupplierReturnGTBySupplierReturnId($order_gt_sn){
		$sql = null;
		if( !empty($order_gt_sn)){
		    $sql = "SELECT supplier_return_gt_sn,supplier_return_id from romeo.supplier_return_request_gt where ".Helper::db_create_in('supplier_return_gt_sn',$order_gt_sn);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'供应商申请映射表[romeo.supplier_return_request_gt]', $sql, 'supplier_return_gt_sn',array('supplier_return_gt_sn','supplier_return_id'));
		return $result; 
	}
	
	/**
	 *    供应商申请表[romeo.supplier_return_request]  根据 $supplier_return_id 获取信息 
	 */
	public static function getMonitorOfSupplierReturnGoodsBySupplierReturnId($supplier_return_id){
		$sql = null;
		if( !empty($supplier_return_id)){
		    $sql = "SELECT *
			from romeo.supplier_return_request
			where ".Helper::db_create_in('supplier_return_id',$supplier_return_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'供应商申请表[romeo.supplier_return_request]', $sql, 'supplier_return_id');
		return $result; 
	}
	
	/**
	 *   售后服务表[ecshop.service]
	 */
	public static function getMonitorOfEcsServiceByReturnOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
			$sql = "SELECT *
			from ecshop.service
			where  ".Helper::db_create_in('back_order_id',$order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'售后服务申请表[ecshop.service]', $sql, 'back_order_id',array('service_id','back_order_id','order_id'));
	    return $result;
	}
	
}


/**
 *   ecs_order_goods 表  ecs_order_info 表  
 */
class EcsOrder{
 
	/**
	 *  根据 ecs_order_goods 表的 order_id 获取 表的信息 构建 监控 返回监控  
	 */
	public static function getMonitorOfEcsOrderGoodsByOrderGoodsId($order_id){
		$sql = null;
		if(empty($order_id)){
			$sql = null;
		}else{
			$sql = "SELECT * FROM  ecshop.ecs_order_goods WHERE ". Helper::db_create_in('order_id',$order_id);
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'订单商品表[ecshop.ecs_order_goods]',$sql,'rec_id', array('rec_id'));
		return $result; 
	}
	
    
    
    
    /**
	 *  根据 ecshop.ecs_order_info 表的 order_id 获取 表的信息 构建 监控 返回监控  
	 *  可以获得  carrier_bill_id 和 facility_id
	 */
	public static function getMonitorOfEcsOrderInfoByEcsOrderId($order_id){
		$sql = null ;
		if( !empty($order_id)){
			$sql = "SELECT * FROM  ecshop.ecs_order_info WHERE ". Helper::db_create_in('order_id',$order_id );
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'订单信息表[ecshop.ecs_order_info]',$sql,'order_id',array('order_id','carrier_bill_id','facility_id'));
		return $result; 
	}
	
	
	// 订单操作记录表  ecshop.ecs_order_action
	public static function getMonitorOfEcsOrderActionByEcsOrderId($order_id){
		$sql = null;
		if( empty($order_id) ) {
			$sql = null;
		}else{
			$sql = "SELECT *
			from ecshop.ecs_order_action
			where  ". Helper::db_create_in('order_id',$order_id );	
		}
	    $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单动作记录表[ecshop.ecs_order_action]', $sql, 'action_id');
		return $result; 
	} 
	
	
	 
	// 订单属性表 ecshop.order_attribute
	public static function getMonitorOfEcsOrderAttributeByEcsOrderId($order_id){
		$sql ;
		if( empty($order_id) ){
			$sql = null;
		}else{
			$sql = "SELECT *
			from ecshop.order_attribute
			where  ". Helper::db_create_in('order_id',$order_id );
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
	
	
	
	 // 订单状态历史表 ecshop.order_mixed_status_history
	public static function getMonitorOfEcsOrderMixedStatusHistoryByEcsOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
		    $sql = "SELECT *
			from ecshop.order_mixed_status_history
			where  ".Helper::db_create_in('order_id',$order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单状态历史表[ecshop.order_mixed_status_history]', $sql, 'order_mixed_status_history_id');
	 	return $result;
	} 
	
	 // 订单状态备注表 ecshop.order_mixed_status_note 
	public static function getMonitorOfEcsOrderMixedStatusNoteByEcsOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
			$sql = "SELECT *
			from ecshop.order_mixed_status_note
			where ".Helper::db_create_in('order_id',$order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单状态备注表[ecshop.order_mixed_status_note]', $sql, 'order_mixed_status_note_id');
	 	return $result;
	} 
	
	
	  // 淘宝订单映射表 ecshop.ecs_taobao_order_mapping，此处只定义了，并没有使用
	public static function getMonitorOfEcsTaobaoOrderMappingByEcsOrderId($order_id){
		$sql = null ;
		if(!empty($order_id)){
	        $sql = "SELECT *
			from ecshop.ecs_taobao_order_mapping
			where ".Helper::db_create_in('order_id',$order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'淘宝订单映射表[ecshop.ecs_taobao_order_mapping]', $sql, 'mapping_id');
	 	return $result;
	} 
	
	  // 新淘宝订单映射表 ecshop.ecs_order_mapping，此处只定义了，并没有使用，添加ecs_order_mapping中间表，废除ecs_taobao_order_mapping用
	public static function getMonitorOfEcsOrderMappingByEcsOrderId($order_id){
		$sql = null ;
		if(!empty($order_id)){
	        $sql = "SELECT *
			from ecshop.ecs_order_mapping
			where ".Helper::db_create_in('order_id',$order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'新淘宝订单映射表[ecshop.ecs_order_mapping]', $sql, 'mapping_id');
	 	return $result;
	}
	
	
	// 快递订单映射表 romeo.order_shipment
	public static function getMonitorOfRomeoOrderShipmentByEcsOrderId($order_id){
		$sql = null ;
		if(!empty( $order_id )) {
		    $sql = "SELECT *
			from romeo.order_shipment
			where  ".Helper::db_create_in('order_id',$order_id);
		}

		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'快递订单映射表[ecshop.order_shipment]', $sql, 'order_id');
	 	return $result;
	} 
	
	 
	// 快递表 romeo.shipment
	public static function getMonitorOfRomeoShipmentByEcsOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
		    $sql = "SELECT *
			from romeo.shipment
			where ".Helper::db_create_in('primary_order_id',$order_id);
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
	public static function getMonitorOfEcsBatchOrderMappingByEcsOrderId($order_id){
		$sql = null;
		if( !empty($order_id) ) {
			$sql = "SELECT * from ecshop.ecs_batch_order_mapping WHERE ". Helper::db_create_in('order_id',$order_id);
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
	
}


class Inventory{
	  // 预定表 romeo.order_inv_reserved
	public static function getMonitorOfRomeoOrderInvReservedByEcsOrderId($order_id){
		$sql = null ;
		if( !empty($order_id)){
	        $sql = "SELECT *
			from romeo.order_inv_reserved
			where ". Helper::db_create_in('order_id',$order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'预定表[romeo.order_inv_reserved]', $sql, 'order_inv_reserved_id');
	    return $result;
	}
	
	// 预定表明细 romeo.order_inv_reserved_detail
	// 可以获得 $order_inv_reserved_detail_id 
	public static function getMonitorOfRomeoOrderInvReservedDetailByEcsOrderId($order_id){
		$sql = null;
		if(!empty ($order_id )){
		    $sql = "SELECT *
			from romeo.order_inv_reserved_detail
			where ".Helper::db_create_in('order_id',$order_id);
		}
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'预定表明细[romeo.order_inv_reserved_detail]', $sql, 'ORDER_INV_RESERVED_DETAIL_ID',array('ORDER_INV_RESERVED_DETAIL_ID'));
	    return $result;
	}
	
	
	
	//库存相关   
	// 库存记录明细表[romeo.inventory_item_detail] 
    // 根据 ecs_order_id 获取 
    // 可以得到 字段 INVENTORY_ITEM_ID 在表  romeo.inventory_item 查询时使用
    // 可以得到  INVENTORY_TRANSACTION_ID   在查 romeo.inventory_transaction 时使用 
	public static function getMonitorOfRomeoInventoryItemDetailByEcsOrderId($order_id){
		$sql = null;
		if( !empty($order_id)){
			$sql = "SELECT INVENTORY_ITEM_DETAIL_ID, ORDER_ID, ORDER_GOODS_ID, CANCELLATION_FLAG, INVENTORY_ITEM_ID, INVENTORY_TRANSACTION_ID,QUANTITY_ON_HAND_DIFF, AVAILABLE_TO_PROMISE_DIFF, CREATED_STAMP, LAST_UPDATED_STAMP
			from romeo.inventory_item_detail
			where ".Helper::db_create_in('order_id',$order_id)." order by created_stamp desc";
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
	public static function getMonitorOfRomeoInventorySummaryBy($product_id){
		$sql = null;
		$facility_id = GYMBOREE_FACILITY_ID;
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
	            $value_list = explode(',', $value_list);//将字符串打散为数组
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
			$extend_ref_name_array = array_merge(array($primary_key_name), $ref_name_array);//如果primary不存在，就把primary放进ref_name_array里
		}else{
			$extend_ref_name_array = $ref_name_array;
		}
		$ref_list =$data_list=array();
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