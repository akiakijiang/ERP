<?php
/*
* 可优化：
* 1. 缓存
*/
require_once ('cls_sales_order.php');
require_once('../../includes/helper/array.php');
require_once('cls_sales_order_tools.php');


class ClsSalesOrderFacility extends ClsSalesOrderForModify implements ISalesOrderForUpdate{
	static protected function GetAllowedActionList(){
		return array('query', 'update');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}
	function QueryData(){
		global $db;
		$sql = "select facility_id,order_status,shipping_status
				from ecshop.ecs_order_info eoi
				where eoi.order_id = '{$this->order_id_}'";
		$this->SetClsDataFromOrderInfo($db->getRow($sql));
		//仅在query操作时获取可转仓仓库列表
		$sql = "SELECT concat(PRODUCT_ID, '_', status_id) as product_status_id, sum(goods_number) as goods_number,eog.goods_name
				from ecshop.ecs_order_goods eog
				LEFT JOIN romeo.product_mapping pm on eog.goods_id = pm.ECS_GOODS_ID and eog.style_id = pm.ECS_STYLE_ID
				where order_id = {$this->order_id_} 
				GROUP BY PRODUCT_ID, status_id";
		$product_list = $order_product_number_list=array();
		$db->getAllRefBy($sql, array('product_status_id'), $product_list, $order_product_number_list);
		$order_product_number_list_temp = $order_product_number_list['product_status_id'];
		$order_product_number_list = array();
		foreach ($order_product_number_list_temp as $product_status_id => $value) {
			# code...
			$order_product_number_list[$product_status_id]['goods_number'] = $value[0]['goods_number'];
			$order_product_number_list[$product_status_id]['goods_name'] = $value[0]['goods_name'];
		}
		$sql = "SELECT ii.FACILITY_ID, concat(ii.product_id, '_', ii.STATUS_ID) as product_status_id, ifnull(sum(ii.AVAILABLE_TO_RESERVED) - ifnull(gir.reserve_number,0),0) as total_quantity
				FROM romeo.inventory_summary ii
				left join romeo.product_mapping pm on ii.PRODUCT_ID = pm.product_id 
				left join ecshop.ecs_order_goods eog on pm.ecs_goods_id = eog.goods_id and pm.ecs_style_id = eog.style_id and eog.status_id = ii.STATUS_ID
				LEFT JOIN ecshop.ecs_goods_inventory_reserved gir on gir.goods_id = eog.goods_id and gir.style_id = eog.style_id and gir.facility_id = ii.facility_id and gir.`status` = 'OK' 
				where eog.order_id = {$this->order_id_} and ". facility_sql('ii.facility_id') ."
				GROUP BY ii.FACILITY_ID, ii.PRODUCT_ID, ii.STATUS_ID";

		$facility_list = $facility_product_list=array();
		$db->getAllRefBy($sql, array('FACILITY_ID'), $facility_list, $facility_product_list);
		$facility_product_list_temp = $facility_product_list['FACILITY_ID'];
		$facility_product_list = array();
		if(!empty($facility_product_list_temp)) {
			foreach ($facility_product_list_temp as $facility_id => $product_list) {
				# code...
				foreach ($product_list as $value) {
					# code...
					$facility_product_list[$facility_id][$value['product_status_id']] = $value['total_quantity'];
				}
			}
		}
		$this->available_facility_list_ = $facility_product_list;
		
		require_once('../includes/lib_main.php');
		
		//取人的仓库权限和 组织的仓库权限交集
		$can_user_facilitys = explode(',',$_SESSION['facility_id']);
		$can_party_facilitys = array();
		$party_available_facilitys = get_available_facility();
		foreach($party_available_facilitys as $facility_id=>$party_available_facility) {
			$can_party_facilitys[] = $facility_id;
		}
		$can_edit_facilitys = array_intersect($can_user_facilitys,$can_party_facilitys);		
		if(empty($can_edit_facilitys)) {
			$this->error_info_ = array('err_no' => 3, 'message' => "请检查当前用户的仓库权限，只有当前用户拥有权限的仓库才会显示在列表中");
			return false;
		}
		foreach ($can_edit_facilitys as $can_edit_facility) {
			if(array_key_exists($can_edit_facility,$this->available_facility_list_)) {
				$is_enough = '(库存足够)';
				foreach ($order_product_number_list as $product_status_id => $order_goods) {
					if(!isset($this->available_facility_list_[$can_edit_facility][$product_status_id])) {
						$the_facility_goods_number = 0;
					} else {
						$the_facility_goods_number = $this->available_facility_list_[$can_edit_facility][$product_status_id];
					}
					
					if($the_facility_goods_number < $order_goods['goods_number']) {
						$is_enough = '(库存不足:'.$order_goods['goods_name'].'需要'.$order_goods['goods_number'].' 只有:'.$the_facility_goods_number.')';
					    break;
					}
				}
				$this->available_facility_list_[$can_edit_facility] = ClsSalesOrderTools::GetFacilityNameByID($can_edit_facility) . $is_enough;
			} else {
				// 给没有库存的仓库赋值
				// $this->available_facility_list_[$can_edit_facility] = ClsSalesOrderTools::GetFacilityNameByID($can_edit_facility) . '(没入库过)';
			}
		}
		return true;
	}
	function SetClsDataFromOrderInfo($order_result){
		$this->DecideIfCanEdit($order_result['order_status'], $order_result['shipping_status']);

		$this->facility_id_ = $order_result['facility_id'];
		$this->facility_name_ = ClsSalesOrderTools::GetFacilityNameByID($order_result['facility_id']);
	}

	protected function PrepareForModify(){
        // 判断是否是合并发货
    	$is_merge_shipment=false;
    	$handle=soap_get_client('ShipmentService');
    	$response=$handle->getShipmentByPrimaryOrderId(array('primaryOrderId'=>$this->order_id_));
    	if(is_object($response->return)){
			$shipment=$response->return;
			if($shipment->status=='SHIPMENT_CANCELLED'){
				$is_merge_shipment=true;
			}
			else{
				$response2=$handle->getOrderShipmentByShipmentId(array('shipmentId'=>$shipment->shipmentId));
				if(is_array($response2->return->OrderShipment)){
					$is_merge_shipment=true;
				}
			}
    	}
    	if($is_merge_shipment){
   	        $this->error_info_ = array('err_no' => 3, 'message' => "合并发货的订单不能修改仓库");
       	    return false;
    	}
        
        // 判断有无部分出库
        require_once(ROOT_PATH.'admin/function.php');
        $is_part_out = check_order_part_delivery($this->order_id_);
        if($is_part_out){
   	        $this->error_info_ = array('err_no' => 3, 'message' => "部分出库的订单不能修改仓库");
       	    return false;
    	}
    	
    	/* 
    	 * 判断是否已经推送给菜鸟,已推送给菜鸟物流系统的不能立即转仓
    	 * 状态【已推送给菜鸟的订单不能转仓】，将会弹出框要求是否取消推送成功的单再转仓
    	 * 状态【已成功推送给菜鸟的订单取消后不能转回菜鸟仓】，弹框显示
    	 * by hzhang1 2015-10-09
    	*/
    	$is_express_bird = check_order_sendto_bird($this->order_id_,$this->data_map_['facility_id']);
    	if($is_express_bird == "error"){
   	        $this->error_info_ = array('err_no' => 3, 'message' => "已推送给菜鸟的订单不能转仓");
       	    return false;
    	}else if($is_express_bird == "not_again"){
   	        $this->error_info_ = array('err_no' => 3, 'message' => "已成功推送给菜鸟的订单取消后不能转回菜鸟仓");
       	    return false;
    	}
    	
    	/* 
    	 * 如果菜鸟仓状态是'等待推送时取消成功','由ERP发货无须推送'的需要转仓，
    	 * 可以任意的转仓，只设计indicate中间表推送状态的改变
    	 * by hzhang1 2015-10-09
    	 */
    	$is_wait_express_bird = check_order_waitto_bird($this->order_id_,$this->data_map_['facility_id']);
    	if($is_wait_express_bird === true){
   	        $this->error_info_ = array('err_no' => 3, 'message' => "转仓成功");
    	}
    	
		$facility_name = ClsSalesOrderTools::GetFacilityNameByID($this->data_map_['facility_id']);

		$this->data_map_for_update_order_info_['facility_id'] = $this->data_map_['facility_id'];
		$this->data_map_for_insert_order_action_['action_note'][] 
				= " 修改配货仓库 从 {$this->facility_name_} 修改为{$facility_name}";
    	return true;
	}

	protected function ModifyViaRomeo(){
		// 取消库存预定 20101229 yxiang
		try{
			$handle=soap_get_client('InventoryService');
			$handle->cancelOrderInventoryReservation(array('orderId'=>$this->order_id_));
		}
        catch (Exception $e) {
            $this->error_info_ = array('err_no' => 3, 'message' => "romeo接口调用失败".$e->getMessage());
            return false;
        }
        return true;
	}

	static function GetAllAttrListForUpdate(){
		return ClsSalesOrderFacility::GetOrderInfoAttrListForUpdate();
	}
	static function GetOrderInfoAttrListForUpdate(){
		return array('facility_id');
	}

	var $order_id_;

	//配送仓库
	var $facility_id_;
	var $facility_name_;
}
?>