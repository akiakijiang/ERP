<?php
require_once('cls_sales_order_status_abstract.php');


abstract class ClsSalesOrderPayStatus extends ClsSalesOrderStatusAbstract{
	function __construct(){
		parent::__construct();
		$this->status_name_ = '付款状态名';
    }

	static function GetStatusInstance($pay_status_id){
		if($pay_status_id == 0){
			return new ClsSalesOrderNoPay();
		}else if($pay_status_id == 1){
			return new ClsSalesOrderPaying();
		}else if($pay_status_id == 2){
			return new ClsSalesOrderHasPay();
		}else if($pay_status_id == 3){
			return new ClsSalesOrderWaitRefund();
		}else if($pay_status_id == 4){
			return new ClsSalesOrderHasRefund();
		}else{
			//todo
			return new ClsSalesOrderPayStatusOther($pay_status_id);
		}
	}
}

//0
class ClsSalesOrderNoPay extends ClsSalesOrderPayStatus{
	// pay_status = 0
	function __construct(){
		parent::__construct();
		$this->status_name_ = '未付款';
    }
    function GetAllowedEditActionList(){
    	return array();
    }
}

//1
class ClsSalesOrderPaying extends ClsSalesOrderPayStatus{
	// pay_status = 1
	function __construct(){
		parent::__construct();
		$this->status_name_ = '付款中';
    }
    function GetAllowedEditActionList(){
    	return array();
    }
}

//2
class ClsSalesOrderHasPay extends ClsSalesOrderPayStatus{
	// pay_status = 2
	function __construct(){
		parent::__construct();
		$this->status_name_ = '已付款';
    }
    function GetAllowedEditActionList(){
    	return array('payment');
    }
}

//3
class ClsSalesOrderWaitRefund extends ClsSalesOrderPayStatus{
	// pay_status = 3
	function __construct(){
		parent::__construct();
		$this->status_name_ = '待退款';
    }
    function GetAllowedEditActionList(){
    	return array();
    }
}

//4
class ClsSalesOrderHasRefund extends ClsSalesOrderPayStatus{
	// pay_status = 4
	function __construct(){
		parent::__construct();
		$this->status_name_ = '已退款';
    }
    function GetAllowedEditActionList(){
    	return array();
    }
}


class ClsSalesOrderPayStatusOther extends ClsSalesOrderPayStatus{
	// pay_status = 0
	function __construct($pay_status_id){
		parent::__construct();
		$this->status_name_ = '未知付款状态['.$pay_status_id.']';
    }
    function GetAllowedEditActionList(){
    	return array();
    }
}

?>