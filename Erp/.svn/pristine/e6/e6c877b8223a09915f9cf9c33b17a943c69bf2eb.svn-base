<?php
/*
* 可优化：
* 1. 缓存
*/
require_once ('cls_sales_order.php');

class ClsSalesOrderStatusHistoryRecords extends ClsSalesOrderBasic{
	static protected function GetAllowedActionList(){
		return array('query');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}
	function QueryData(){
		//获得订单状态的历史数据
		//require_once('../includes/lib_order_mixed_status.php');
		$order_mixed_status_history = null;//get_order_mixed_status_history($this->order_id_);
		if(!empty($order_mixed_status_history)){
			foreach ($order_mixed_status_history as $value) {
				# code...
				$this->hostory_list_[] = new ClsSalesOrderStatusHistory($value);
			}
		}
	}

	var $hostory_list_ = array();
}

class ClsSalesOrderStatusHistory{
	function __construct($record){
		//
		$this->order_status_ = $record['order_status_description'] . ','.$record['adjustment_status_description'];
		$this->pay_status_ = $record['pay_status_description'];
		$this->logistic_status_ = $record['warehouse_status_description'] . ','.$record['adjustment_status_description'];
		//
		$this->shipment_print_status_ = $record['pick_list_status_description'];
		$this->invoice_print_status_ = $record['invoice_status_description'];
		$this->waybill_print_status_ = $record['shipping_bill_status_description'];
		//
		$this->action_user_ = $record['created_by_user_login'];
		$this->action_time_ = $record['created_stamp'];
		//
		$this->notes_ = $record['note'];
		$this->note_count_ = $record['note_number'];
	}
	//
	var $order_status_ = '订单状态';
	var $pay_status_ = '付款状态';
	var $logistic_status_ = '物流状态';
	//
	var $shipment_print_status_ = '未打印';
	var $invoice_print_status_ = '未打印';
	var $waybill_print_status_ = '未打印';
	//
	var $action_user_ = 'action_user';
	var $action_time_ = '1970-01-01 00:00:00';
	//
	var $note_count_ = 0;
	var $notes_ = array();
}

?>