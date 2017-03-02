<?php
/*
* 可优化：
* 1. 缓存
*/
require_once ('cls_sales_order.php');
require_once('../../includes/helper/array.php');
require_once('cls_sales_order_tools.php');


class ClsSalesOrderPayment extends ClsSalesOrderForModify implements ISalesOrderForUpdate{
	static protected function GetAllowedActionList(){
		return array('query', 'update');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}
	function QueryData(){
		global $db;
		$sql = "select eoi.order_status,eoi.shipping_status,eoi.order_type_id, eoi.pay_id, eoi.shipping_id,eoi.inv_payee, eoi.pay_status,p.is_cod,
				eoi.pay_number,oa.attr_value as kjg_pay_id
				from ecshop.ecs_order_info eoi
				left join ecshop.ecs_payment p ON eoi.pay_id = p.pay_id
				left join ecshop.order_attribute oa ON eoi.order_id = oa.order_id and attr_name = 'KJG_PAY_ID'
				where eoi.order_id = '{$this->order_id_}' group by eoi.order_id";
		$this->SetClsDataFromOrderInfo($db->getRow($sql));
	}
	function SetClsDataFromOrderInfo($order_result){
		$this->DecideIfCanEdit($order_result['order_status'], $order_result['shipping_status']);
		$this->order_type_id_ = $order_result['order_type_id'];
		$this->pay_id_ = $order_result['pay_id'];
		$this->pay_number_ = $order_result['pay_number'];
		$this->kjg_pay_id_ = $order_result['kjg_pay_id'];
		$this->pay_status_ = $order_result['pay_status'];
		$this->is_cod_ = $order_result['is_cod'];
		$this->inv_payee_ = $order_result['inv_payee'];
		$this->payment_ = ClsSalesOrderTools::GetPaymentByID($this->pay_id_);
		$this->pay_name_ = $this->payment_['pay_name'];
		$this->shipping_ = ClsSalesOrderTools::GetShippingByID($order_result['shipping_id']);
	}

	protected function PrepareForModify(){
        $new_payment = ClsSalesOrderTools::GetPaymentByID($this->data_map_['pay_id']);
        if(!ClsSalesOrderTools::CheckPaymentAndShipping($new_payment, $this->shipping_, $this->error_info_)){
        	return false;
        }

		$this->data_map_for_update_order_info_['pay_id'] = $new_payment['pay_id'];
		$this->data_map_for_update_order_info_['pay_name'] = $new_payment['pay_name'];
		$this->data_map_for_insert_order_action_['action_note'][] 
				= " 修改支付方式 从 {$this->pay_name_} 修改为{$new_payment['pay_name']}";
				
	    if($this->inv_payee_!= $this->data_map_['inv_payee_']) {
	    	$this->data_map_for_update_order_info_['inv_payee'] = $this->data_map_['inv_payee_'];
	    	$this->data_map_for_insert_order_action_['action_note'][] 
				   = '修改发票抬头：'.$this->inv_payee_.' --> '.$this->data_map_['inv_payee_'];	
	    }
	    
	  	if($this->pay_number_!== $this->data_map_['pay_number']) {
	    	$this->data_map_for_update_order_info_['pay_number'] = $this->data_map_['pay_number'];
	    	$this->data_map_for_insert_order_action_['action_note'][] 
				   = '支付单号：'.$this->pay_number_.' --> '.$this->data_map_['pay_number'];	
	    }
	    
	  	if($this->kjg_pay_id_!= $this->data_map_['kjg_pay_id']) {
	    	$this->data_map_for_insert_order_action_['action_note'][] 
				   = '申报系统支付方式：'.$this->kjg_pay_id_.' --> '.$this->data_map_['kjg_pay_id'];
				   
			if($this->kjg_pay_id_ !== null){
				$sql = "update ecshop.order_attribute set attr_value = '{$this->data_map_['kjg_pay_id']}' where attr_name = 'kjg_pay_id' and order_id = '{$this->order_id_}'";
			}else {
				$sql = "insert into ecshop.order_attribute (order_id,attr_name,attr_value) value ('{$this->order_id_}','KJG_PAY_ID','{$this->data_map_['kjg_pay_id']}')";
			}	
//			var_dump($sql);
			$this->sql_result_[] = $sql;
	    }	    
	
        //同步修改payment_transaction的pay_id
		global $db;
        $sql="select pay_id 
              from romeo.payment_transaction 
              where order_id='{$this->order_id_}'";
        $origin_payId=$db->getOne($sql);
        if($origin_payId){
	        $this->sql_result_[] = "update romeo.payment_transaction 
                  set pay_id='{$this->data_map_['pay_id']}'
                  where order_id='{$this->order_id_}'";
        }
        return true;
    }

	static function ClsSalesOrderPaymentAndExpress(){
		return ClsPayInfoOfOrderDetail::GetOrderInfoAttrListForUpdate();
	}
	static function GetAllAttrListForUpdate(){
		return ClsSalesOrderPayment::GetOrderInfoAttrListForUpdate();
	}
	static function GetOrderInfoAttrListForUpdate(){
		return array('pay_id','inv_payee_','pay_number','kjg_pay_id');
	}

	var $order_type_id_;
	var $order_id_;
	var $pay_id_;
	var $pay_number_;
	var $kjg_pay_id_;
	var $payment_;
	var $is_cod_;
	var $shipping_;
	var $inv_payee_;
	var $pay_status_;
}

?>