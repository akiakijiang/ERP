<?php
/*
* 可优化：
* 1. 缓存
*/
require_once ('cls_sales_order.php');
require_once('../../includes/helper/array.php');
require_once('cls_sales_order_tools.php');


class ClsSalesOrderExpress extends ClsSalesOrderForModify implements ISalesOrderForUpdate{
	static protected function GetAllowedActionList(){
		return array('query', 'update');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}
	function QueryData(){
		global $db;
		$sql = "select shipping_id, pay_id, carrier_bill_id,order_status,pay_status,shipping_status,distributor_id,
					shipping_fee, shipping_proxy_fee,goods_amount,pack_fee,bonus,integral_money,order_amount
				from ecshop.ecs_order_info eoi
				where eoi.order_id = '{$this->order_id_}'";
		$this->SetClsDataFromOrderInfo($db->getRow($sql));		
	}
	
	function SetClsDataFromOrderInfo($order_result){
		$this->DecideIfCanEdit($order_result['order_status'], $order_result['shipping_status']);
		$this->shipping_id_ = $order_result['shipping_id'];
		$this->shipping_ = ClsSalesOrderTools::GetShippingById($order_result['shipping_id']);
		$this->shipping_name_ = $this->shipping_['shipping_name'];
		$this->payment_ = ClsSalesOrderTools::GetPaymentByID($order_result['pay_id']);

		$this->carrier_bill_id_ = $order_result['carrier_bill_id'];
		$this->shipping_proxy_fee_ = $order_result['shipping_proxy_fee'];
		$this->shipping_fee_ = $order_result['shipping_fee'];
		$this->shipping_basic_fee_ = $order_result['shipping_fee'] - $order_result['shipping_proxy_fee'];

		$this->goods_amount_ =$order_result['goods_amount'];
		$this->pack_fee_ =$order_result['pack_fee'];
		$this->bonus_ =$order_result['bonus'];
		$this->integral_money_ =$order_result['integral_money'];

		$this->order_amount_ = $order_result['order_amount'];
		
		//支付状态
		$this->pay_status_ = $order_result['pay_status'];	
		
		//分销店铺
		$this->distributor_id_ =$order_result['distributor_id'];
	}
		
	protected function PrepareForModify(){
        if(!isset($this->data_map_['shipping_id'])){
        	$this->data_map_['shipping_id'] = $this->shipping_id_;
        }
        if(!isset($this->data_map_['shipping_basic_fee'])){
        	$this->data_map_['shipping_basic_fee'] = $this->shipping_basic_fee_;
        }
        if(!isset($this->data_map_['shipping_proxy_fee'])){
        	$this->data_map_['shipping_proxy_fee'] = $this->shipping_proxy_fee_;
        }
        $this->data_map_['shipping_fee'] = $this->data_map_['shipping_basic_fee'] + $this->data_map_['shipping_proxy_fee'];
		
		if(in_array($this->data_map_['shipping_id'],array('146')) && !in_array($this->distributor_id_,array('2836'))){
			$this->error_info_ = array('err_no' => 3, 'message' => "“京东COD”快递目前 只支持“ASC京东旗舰店”");
	       	return false;
		}else if($this->data_map_['shipping_id']=='149'){
			$this->error_info_ = array('err_no' => 3, 'message' => "“京东配送”快递暂不支持任何店铺使用");
	       	return false;
		}

    
        if($this->data_map_['shipping_id'] != $this->shipping_id_){
        	//修改快递方式
	        // 判断是否是合并发货
	    	$is_merge_shipment=false;
	    	$handle=soap_get_client('ShipmentService');
	    	$response=$handle->getShipmentByPrimaryOrderId(array('primaryOrderId'=>$this->order_id_));	
	    	//判断romeo.shipment表里是否已创建
	    	$sql = "select 1 from romeo.shipment where shipment_type_id = '{$this->shipping_id_}' and ".
	    	"primary_order_id = '{$this->order_id_}' ";
	    	$result = $GLOBALS['db']->getAll($sql);
	    	if($result == null || $result == ''){
	    	    $this->error_info_ = array('err_no' => 3, 'message' => "订单发货单号未创建不能修改配送方式");
	    	    return false;
	    	}
	    	
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
	   	        $this->error_info_ = array('err_no' => 3, 'message' => "合并发货的订单不能修改配送方式");
	       	    return false;
	    	}
	        
	        $new_shipping = ClsSalesOrderTools::GetShippingById($this->data_map_['shipping_id']);

	        if(!ClsSalesOrderTools::CheckPaymentAndShipping($this->payment_, $new_shipping, $this->error_info_)){
	        	return false;
	        }

			$this->data_map_for_update_order_info_['shipping_id'] = $new_shipping['shipping_id'];
			$this->data_map_for_update_order_info_['shipping_name'] = $new_shipping['shipping_name'];
			$this->data_map_for_insert_order_action_['action_note'][] 
					= " 修改配送方式 从 {$this->shipping_name_} 修改为{$new_shipping['shipping_name']}";

		   	// 更改承运商
	    	$carrier_id = $new_shipping['default_carrier_id'];
	    	$this->data_map_['carrier_id'] = $carrier_id;
	    	// killed by Sinri 20150106
	    	// $this->sql_result_[] = "update ecs_carrier_bill set carrier_id='{$carrier_id}' where bill_id='".$this->carrier_bill_id_."'";
        }

        if($this->data_map_['shipping_fee'] != $this->shipping_fee_){
        	// 修改快递费
			$this->data_map_for_update_order_info_['shipping_fee'] = $this->data_map_['shipping_fee'];  // 总运费
            $this->data_map_for_insert_order_action_['action_note'][] = ("配送总费用".$this->shipping_fee_."修改为".$this->data_map_['shipping_fee']);
        }
        if($this->data_map_['shipping_basic_fee'] != $this->shipping_basic_fee_){
        	// 修改基本费
            $this->data_map_for_insert_order_action_['action_note'][] = ("配送基本费用".$this->shipping_basic_fee_."修改为".$this->data_map_['shipping_basic_fee']);
        }

        if($this->data_map_['shipping_proxy_fee'] != $this->shipping_proxy_fee_){
        	// 修改快递手续费
			$this->data_map_for_update_order_info_['shipping_proxy_fee'] = $this->data_map_['shipping_proxy_fee'];
            $this->data_map_for_insert_order_action_['action_note'][] = ("配送手续费".$this->shipping_proxy_fee_."修改为".$this->data_map_['shipping_proxy_fee']);
        }

        $sinri_old_order_amount=$this->order_amount_;
		$sinri_new_order_amount= $this->data_map_['shipping_fee']
				+ max($this->goods_amount_ + $this->pack_fee_ + $this->bonus_ - $this->integral_money_, 0);

		// 修改支付状态
		global $_CFG;
		if(abs($sinri_old_order_amount-$sinri_new_order_amount)>0.000001){
			// 计算order_amount公式放在最后 TODO -h订单的计算要分开的啊，要考虑misc_fee，要把rpc的项加上
			$this->data_map_for_update_order_info_['order_amount'] = $sinri_new_order_amount;
			$this->data_map_for_insert_order_action_['action_note'][] ="订单总额从 ￥".$sinri_old_order_amount." 变为 ￥".$sinri_new_order_amount;

	 		if($this->pay_status_!='0') {
				$this->data_map_for_update_order_info_['pay_status'] = $this->data_map_['pay_status'];
	            $this->data_map_for_insert_order_action_['action_note'][] = "付款状态由".$_CFG['adminvars']['pay_status'][$this->pay_status_]."改为未付款";  
	        }else{
	        	$this->data_map_for_insert_order_action_['action_note'][] = "未付款订单修改快递 付款状态保持原状";
	        }
		}else{
			$this->data_map_for_insert_order_action_['action_note'][] = ("修改快递后金额不变");
		}

    	return true;
	}

	protected function ModifyViaRomeo(){
		if($this->data_map_['shipping_id'] != $this->shipping_id_){
			try {
	            $handle=soap_get_client('ShipmentService');
	            $handle->updateShipmentByPrimaryOrderId(array(
	                'primaryOrderId'=>$this->order_id_,
	                'shipmentTypeId'=>$this->data_map_['shipping_id'],
	                'carrierId'=>$this->data_map_['carrier_id'],
	            	'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
	            	'trackingNumber'=>'',
	            ));
	        }
	        catch (Exception $e) {
	            $this->error_info_ = array('err_no' => 3, 'message' => "romeo接口调用失败".$e->getMessage());
	            return false;
	        }
			return true;
		}
		return true;
	}

	static function GetAllAttrListForUpdate(){
		return ClsSalesOrderExpress::GetOrderInfoAttrListForUpdate();
	}
	static function GetOrderInfoAttrListForUpdate(){
		return array('shipping_id', 'shipping_basic_fee', 'shipping_proxy_fee');
	}

	var $order_id_;
	//配送方式
	var $shipping_id_;
	var $shipping_name_;

	//配送时间段
	var $need_shipping_time_ = false;
	var $start_time_;
	var $end_time_;
}
?>