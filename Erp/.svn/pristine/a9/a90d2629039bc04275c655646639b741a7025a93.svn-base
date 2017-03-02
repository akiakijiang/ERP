<?php
require_once('cls_sales_order_status_abstract.php');


abstract class ClsSalesOrderShippingStatus extends ClsSalesOrderStatusAbstract{
	function __construct(){
		parent::__construct();
		$this->status_name_ = '发货状态名';
    }

	static function GetStatusInstance($shipping_status_id){
		if($shipping_status_id == 0){
			return new ClsSalesOrderShippingStatusTopick();
		}else if($shipping_status_id == 1){
			return new ClsSalesOrderShippingStatusShipped();
		}else if($shipping_status_id == 2){
			return new ClsSalesOrderShippingStatusConfirmRec();
		}else if($shipping_status_id == 3){
			return new ClsSalesOrderShippingStatusRejected();
		}else if($shipping_status_id == 8){
			return new ClsSalesOrderShippingStatusToDeliver();
		}else if($shipping_status_id == 9){
			return new ClsSalesOrderShippingStatusPickedToStock();
		}else if($shipping_status_id == 11){
			return new ClsSalesOrderShippingStatusBacked();
		}else if($shipping_status_id == 12){
			return new ClsSalesOrderShippingStatusStockedToCheck();
		}else if($shipping_status_id == 13){
			return new ClsSalesOrderShippingStatusBatchPicking();
		}else if($shipping_status_id == 16){
			return new ClsSalesOrderShippingStatusSentToWMS();
		}else{
			//todo
			return new ClsSalesOrderShippingStatusOther($shipping_status_id);
		}
	}
}

//0
class ClsSalesOrderShippingStatusTopick extends ClsSalesOrderShippingStatus{
	// shipping_status = 0
	function __construct(){
		parent::__construct();
		$this->status_name_ = '待配货';
		
    	// $this->allowed_action_list_[] = 'order_cancel';
		// $this->allowed_action_list_[] = 'order_recover';
		$this->allowed_action_list_[] = 'order_abandon';// Added by Sinri Follow JIANGONG's will. Abandon this order and would never go alive again
		$this->allowed_action_list_[] = 'order_revert';// Added by Sinri Follow JIANGONG's will. Revert this order to be unconfirmed
		
    	$this->allowed_action_list_[] = 'order_confirm';
    	$this->allowed_action_list_[] = 'order_split';
		
		$this->allowed_action_list_[]='mark_taobao_order_sn_with_tail_x';//added by Sinri follow XYTU's will
    }
    function GetAllowedEditActionList(){
    	return array('order_header', 'merge_info', 'platform_info','consignee', 'payment', 'express', 'facility', 'goods_list', 'pay_info','logistic_info');
    }
}

//1
class ClsSalesOrderShippingStatusShipped extends ClsSalesOrderShippingStatus{
	// shipping_status = 1
	function __construct(){
		parent::__construct();
		$this->status_name_ = '已发货';
    	$this->allowed_action_list_[] = 'rec_confirm';
    	$this->allowed_action_list_[] = 'reject';
		
	}
    function GetAllowedEditActionList(){
    	return array('order_header', 'merge_info');
    }
}

//2
class ClsSalesOrderShippingStatusConfirmRec extends ClsSalesOrderShippingStatus{
	// shipping_status = 2
	function __construct(){
		parent::__construct();
		$this->status_name_ = '收货确认';
    }
    function GetAllowedEditActionList(){
    	return array('order_header', 'merge_info');
    }
}

//3
class ClsSalesOrderShippingStatusRejected extends ClsSalesOrderShippingStatus{
	// shipping_status = 3
	function __construct(){
		parent::__construct();
		$this->status_id_ = 3;
		$this->status_name_ = '拒收退回';
    }
    function GetAllowedEditActionList(){
    	return array('order_header', 'merge_info');
    }
}

//8
class ClsSalesOrderShippingStatusToDeliver extends ClsSalesOrderShippingStatus{
	// shipping_status = 8
	function __construct(){
		parent::__construct();
		$this->status_name_ = '已出库/复核，待发货';
    	// $this->allowed_action_list_[] = 'order_cancel';
		$this->allowed_action_list_[] = 'order_abandon';// Added by Sinri Follow JIANGONG's will. Abandon this order and would never go alive again
		
		$this->allowed_action_list_[]='mark_taobao_order_sn_with_tail_x';//added by Sinri follow XYTU's will
    }
    function GetAllowedEditActionList(){
    	return array('order_header');
    }
}

//9
class ClsSalesOrderShippingStatusPickedToStock extends ClsSalesOrderShippingStatus{
	// shipping_status = 9
	function __construct(){
		parent::__construct();
		$this->status_name_ = '已配货，待出库';
		
		$this->allowed_action_list_[]='mark_taobao_order_sn_with_tail_x';//added by Sinri follow XYTU's will
    }
    function GetAllowedEditActionList(){
    	return array('order_header', 'merge_info');
    }
}

//11
class ClsSalesOrderShippingStatusBacked extends ClsSalesOrderShippingStatus{
	// shipping_status = 11
	function __construct(){
		parent::__construct();
		$this->status_name_ = '已追回';
		
		$this->allowed_action_list_[]='mark_taobao_order_sn_with_tail_x';//added by Sinri follow XYTU's will
    }
    function GetAllowedEditActionList(){
    	return array('order_header', 'merge_info');
    }
}

//12
class ClsSalesOrderShippingStatusStockedToCheck extends ClsSalesOrderShippingStatus{
	// shipping_status = 12
	function __construct(){
		parent::__construct();
		$this->status_name_ = '已拣货出库，待复核';
    	// $this->allowed_action_list_[] = 'order_cancel';
		$this->allowed_action_list_[] = 'order_abandon';// Added by Sinri Follow JIANGONG's will. Abandon this order and would never go alive again
		
		$this->allowed_action_list_[]='mark_taobao_order_sn_with_tail_x';//added by Sinri follow XYTU's will
    }
    function GetAllowedEditActionList(){
    	return array('order_header', 'merge_info');
    }
}

//13
class ClsSalesOrderShippingStatusBatchPicking extends ClsSalesOrderShippingStatus{
	// shipping_status = 13
	function __construct(){
		parent::__construct();
		$this->status_name_ = '批拣中';
		// $this->allowed_action_list_[] = 'order_cancel';
		$this->allowed_action_list_[] = 'order_abandon';// Added by Sinri Follow JIANGONG's will. Abandon this order and would never go alive again
		// $this->allowed_action_list_[] = 'order_revert';// Added by Sinri Follow JIANGONG's will. Revert this order to be unconfirmed
		
		$this->allowed_action_list_[]='mark_taobao_order_sn_with_tail_x';//added by Sinri follow XYTU's will
    }
    function GetAllowedEditActionList(){
    	return array('order_header', 'merge_info');
    }
}

class ClsSalesOrderShippingStatusSentToWMS extends ClsSalesOrderShippingStatus{
	// shipping_status = 16
	function __construct(){
		parent::__construct();
		$this->status_name_ = '已推送WMS';
		// $this->allowed_action_list_[] = 'order_cancel';
		$this->allowed_action_list_[] = 'order_abandon';// Added by Sinri Follow JIANGONG's will. Abandon this order and would never go alive again
		// $this->allowed_action_list_[] = 'order_revert';// Added by Sinri Follow JIANGONG's will. Revert this order to be unconfirmed
		
		// $this->allowed_action_list_[]='mark_taobao_order_sn_with_tail_x';//added by Sinri follow XYTU's will
    }
    function GetAllowedEditActionList(){
    	return array('order_header', 'merge_info');
    }
}

class ClsSalesOrderShippingStatusOther extends ClsSalesOrderShippingStatus{
	// 待配货：shipping_status = 0
	function __construct($shipping_status_id){
		parent::__construct();
		$this->status_name_ = '未知发货状态['.$shipping_status_id.']';
		
		$this->allowed_action_list_[]='mark_taobao_order_sn_with_tail_x';//added by Sinri follow XYTU's will
    }
    function GetAllowedEditActionList(){
    	return array('order_header');
    }
}

?>