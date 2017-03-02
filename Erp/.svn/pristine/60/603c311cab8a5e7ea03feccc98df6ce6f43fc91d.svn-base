<?php
/*
* 可优化：
* 1. 缓存
*/
require_once ('cls_sales_order.php');

class ClsSalesOrderActionRecords extends ClsSalesOrderBasic{
	static protected function GetAllowedActionList(){
		return array('query');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}

	function QueryData(){
		global $db;
		//首条记录
		$sql = "SELECT consignee, order_time from ecshop.ecs_order_info where order_id = {$this->order_id_}";
		$order = $db->getRow($sql);
		$action['merged_status'] = '订单生成，待确认';
		$action['real_name'] = $order['consignee'];
		$action['action_user'] = $order['consignee'];
		$action['action_time'] = $order['order_time'];
		$action['action_note'] = '新订单';
		$act_record = new ClsSalesOrderActionRecord($action,true);
		$curr_record = array('order_status' => $act_record->order_status_, 'action_list' => array($act_record));
		//后续记录
		global $db;
		$actionSQL = "SELECT order_status, pay_status, shipping_status, eoc.action_user, ifnull(eau.real_name,eoc.action_user) as real_name, action_time, action_note, note_type FROM ecs_order_action eoc " .
				" left join ecshop.ecs_admin_user eau on eau.user_name = eoc.action_user " .
				" WHERE eoc.order_id = '{$this->order_id_}' ORDER BY action_id";
		$actions = $db->getAll($actionSQL);
		foreach ($actions AS $action) {
			$act_record = new ClsSalesOrderActionRecord($action);
			if($act_record->order_status_ != $curr_record['order_status']){
				//与前一状态不同
				$this->action_list_[] = $curr_record;
				$curr_record['order_status'] = $act_record->order_status_;
				$curr_record['action_list'] = array($act_record);
			}else{
				//与前一状态相同
				$curr_record['action_list'][] = $act_record;
			}
		}
		if($curr_record){
			$this->action_list_[] = $curr_record;
			//
			foreach ($this->action_list_ as &$value) {
				# code...
				$value['action_count'] = count($value['action_list']);
			}
		}

	}

	var $order_id_;
	var $action_list_ = array();
}

class ClsSalesOrderActionRecord{
	function __construct($action, $first_act_flag = false){
		require_once('../config.vars.php');
		if($first_act_flag){
			$this->order_status_ = $action['merged_status'];
		}else{
			global $_CFG;
			$this->order_status_ = $_CFG['adminvars']['order_status'][$action['order_status']] . ',' . 
									$_CFG['adminvars']['pay_status'][$action['pay_status']] . ',' . 
									$_CFG['adminvars']['shipping_status'][$action['shipping_status']];
		}
		$this->action_user_ =$action['real_name'].' ('.$action['action_user'].')';
		$this->action_time_ = $action['action_time'];
		$this->note_ = $action['action_note'];
		$this->note_type_ = $action['note_type'];
	}
	var $order_status_;
	var $action_user_;
	var $action_time_;
	var $note_;
	var $note_type_;
}

?>