<?php
require_once('cls_sales_order_status_abstract.php');

abstract class ClsSalesOrderBasicStatus extends ClsSalesOrderStatusAbstract{
	function __construct(){
		parent::__construct();
		$this->status_name_ = '基本状态名';
    }
	static function GetStatusInstance($order_status_id, $outShipOrder = null){
		
		if ($outShipOrder) {
			// 外包订单
			return new ClsSalesOrderBasicStatusOutShipOrder($order_status_id);
		}else if($order_status_id == 0){
			return new ClsSalesOrderBasicStatusInit();
		}else if($order_status_id == 1){
			return new ClsSalesOrderBasicStatusConfirmed();
		}else if($order_status_id == 2){
			return new ClsSalesOrderBasicStatusCancelled();
		}else if($order_status_id == 4){
			return new ClsSalesOrderBasicStatusRejected();
		}else{
			//todo
			return new ClsSalesOrderBasicStatusOther($order_status_id);
		}
	}
}

class ClsSalesOrderBasicStatusInit extends ClsSalesOrderBasicStatus{
	//初始化：即未确认，order_status = 0
	function __construct(){
		parent::__construct();
		$this->status_name_ = '未确认';
    	$this->allowed_action_list_[] = 'order_confirm';
    	// $this->allowed_action_list_[] = 'order_cancel';
    	$this->allowed_action_list_[] = 'order_abandon';// Added by Sinri Follow JIANGONG's will. Abandon this order and would never go alive again
    	$this->allowed_action_list_[] = 'order_split';
    }
    function GetAllowedEditActionList(){
    	return array('order_header', 'merge_info', 'platform_info','consignee', 'payment', 'express', 'facility', 'goods_list', 'pay_info','logistic_info');
    }
}

class ClsSalesOrderBasicStatusConfirmed extends ClsSalesOrderBasicStatus{
	// 已确认，order_status = 1
	function __construct(){
		parent::__construct();
		$this->status_name_ = '已确认';
    	
		// $this->allowed_action_list_[] = 'order_cancel';
    	$this->allowed_action_list_[] = 'order_abandon';// Added by Sinri Follow JIANGONG's will. Abandon this order and would never go alive again
		$this->allowed_action_list_[] = 'order_revert';// Added by Sinri Follow JIANGONG's will. Revert this order to be unconfirmed
		
		$this->allowed_action_list_[] = 'rec_confirm';
    	$this->allowed_action_list_[] = 'reject';
    	$this->allowed_action_list_[] = 'order_split';
    }
    function GetAllowedEditActionList(){
    	return array('order_header', 'merge_info', 'payment', 'express', 'facility','logistic_info');
    }
}

class ClsSalesOrderBasicStatusCancelled extends ClsSalesOrderBasicStatus{
	// 取消，order_status = 2
	function __construct(){
		parent::__construct();
		$this->status_name_ = '已作废';//'已取消';
    	// $this->allowed_action_list_[] = 'order_recover';// Killed by Sinri Follow JIANGONG's will
    	// $this->allowed_action_list_[] = 'order_recover_force';// Killed by Sinri Follow JIANGONG's will
    	// $this->allowed_action_list_[] = 'order_split';// Killed by Sinri Follow JIANGONG's will
		
		$this->allowed_action_list_[]='mark_taobao_order_sn_with_tail_x';//added by Sinri follow XYTU's will
    }
    function GetAllowedEditActionList(){
    	return array('order_header');
    }
}

class ClsSalesOrderBasicStatusRejected extends ClsSalesOrderBasicStatus{
	// 拒收，order_status = 4
	function __construct(){
		parent::__construct();
		$this->status_name_ = '拒收';
    }
    function GetAllowedEditActionList(){
    	return array('order_header');
    }
}

class ClsSalesOrderBasicStatusOther extends ClsSalesOrderBasicStatus{
	// 已确认，order_status = 1
	function __construct($status_id){
		parent::__construct();
		$this->status_name_ = '其他订单状态['.$status_id.']';
    }
    function GetAllowedEditActionList(){
    	return array('order_header');
    }
}

class ClsSalesOrderBasicStatusOutShipOrder extends ClsSalesOrderBasicStatus{
	// 已确认，order_status = 1
	function __construct($status_id){
		parent::__construct();
		
		if ($status_id == 0) {
			$this->status_name_ = '未确认';
		} else if ($status_id == 1) {
			$this->status_name_ = '已确认';
		} else if ($status_id == 2) {
			$this->status_name_ = '已作废';//'已取消';
		} else if ($status_id == 4) {
			$this->status_name_ = '拒收';
		} else {
			$this->status_name_ = '其他订单状态['.$status_id.']';
		}
    }
    function GetAllowedEditActionList(){
    	return array('order_header');
    }
}
?>