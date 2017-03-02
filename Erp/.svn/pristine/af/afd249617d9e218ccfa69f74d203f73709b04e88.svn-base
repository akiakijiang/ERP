<?php
require_once ('cls_sales_order.php');

class SalesOrderHeader extends ClsSalesOrderForModify implements ISalesOrderForUpdate{
	static protected function GetAllowedActionList(){
		return array('query', 'update');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}
	function QueryData(){
		global $db, $_CFG;
		$sql = "SELECT eoi.order_sn, eoi.party_id,eoi.facility_id, p.name as party_name, eoi.order_status, eoi.shipping_status, eoi.pay_status,
				eoi.order_time, eoi.order_amount, eoi.real_paid, confirm_time, pay_time, shipping_time,
				oir.status as inv_status,
				-- (select pick_list_status from ecshop.order_mixed_status_history where order_id = eoi.order_id and is_current='Y' limit 1) as pick_list_status
				(SELECT
					'printed'
				FROM
					(
						(
							SELECT
								'printed'
							FROM
								ecshop.ecs_print_action epa
				INNER JOIN romeo.order_shipment os on epa.print_type = 'SHIPMENT' and os.SHIPMENT_ID=epa.print_item
							WHERE
				 os.ORDER_ID='{$this->order_id_}'
						)
						UNION
							(
								SELECT
									'printed'
								FROM
									ecshop.ecs_print_action epa
								INNER JOIN romeo.batch_pick_mapping bpm ON epa.print_type = 'BATCH_SHIPMENT'
				INNER JOIN romeo.order_shipment os on epa.print_type = 'SHIPMENT' and os.SHIPMENT_ID=bpm.shipment_id
								AND epa.print_item = bpm.batch_pick_sn
								WHERE
									 os.ORDER_ID='{$this->order_id_}'
							)
					) AS t
				LIMIT 1) AS pick_list_status
				from ecshop.ecs_order_info eoi
				left join romeo.party p on convert(eoi.party_id using utf8) = p.party_id
				left join romeo.order_inv_reserved oir on eoi.order_id = oir.order_id
				where eoi.order_id = '{$this->order_id_}'";
		$order_result = $db->getRow($sql);
		
		$sql = "select order_id from ecshop.ecs_out_ship_order where order_id = '{$this->order_id_}' limit 1 ";
		$outShipOrder = $db->getOne($sql);

		//基本信息
		$this->order_sn_ = $order_result['order_sn'];
		$this->party_id_ = $order_result['party_id'];
		$this->party_name_ = $order_result['party_name'];
		$this->facility_id_ = $order_result['facility_id'];
		
		// 判断该订单是否可以申请退款
		require_once (ROOT_PATH.'RomeoApi/lib_refund.php');
		$this->refund_apply_enabled_ = refund_order_enabled($order_result);
		
		// 订单没预定成功原因
		require_once (ROOT_PATH.'admin/function.php');
		$this->reserve_error_ = '';
		$reserve_info = get_order_reserve_info($this->order_id_);
		$this->reserve_error_ = $reserve_info['reserve_error'];

		//合并订单情况
		require_once ('cls_sales_order_merge_info.php');
		$this->merge_info_ = new ClsSalesOrderMergeInfo(array('order_id' => $this->order_id_,'content_type' => 'merge_info'));
		$this->merge_info_->QueryData();
		$this->merge_info_->SetClsData($order_result);

		//sinri
		if(check_admin_priv('ORDER_DETAILS_FORCE_ORDER_DIVORCE')){
			$this->merge_info_->canForceDivorce=true;
		}

		// 客户信息
		require_once ('cls_sales_order_consignee.php');
		$this->consigne_info_ = new ClsSalesOrderConsignee(array('order_id' => $_REQUEST['order_id'], 'action_type'=>'query'));
		$this->consigne_info_->QueryData();
		
		//状态
		require_once ('cls_sales_order_basic_status.php');
		require_once ('cls_sales_order_shipping_status.php');
		$this->basic_status_ins_ = ClsSalesOrderBasicStatus::GetStatusInstance($order_result['order_status'], $outShipOrder);
		$this->shipping_status_ins_ = ClsSalesOrderShippingStatus::GetStatusInstance($order_result['shipping_status']);
        $this->pick_list_status_ = $order_result['pick_list_status'];
		//状态s
		$this->status_list_ = array();
		//基本状态
		$this->status_list_[] = $this->basic_status_ins_ ->status_name_;
		//预定状态
		switch ($order_result['inv_status']) {
			case null:
				$inv_status = '未预定';
				break;
			case 'N':
				$inv_status = '预定失败';
				break;
			case 'Y':
				$inv_status = '预定成功';
				break;
			case 'F':
				$inv_status = '预定成功(已发货)';
				break;
			default:
				$inv_status = '预定异常('.$order_result['inv_status'].')';
				break;
		}
		$this->status_list_[] = $inv_status;
		//付款状态
		$this->status_list_[] = $_CFG['adminvars']['pay_status'][$order_result['pay_status']];
		//发货状态
		$this->status_list_[] = $this->shipping_status_ins_->status_name_;

		//状态决定允许的操作
		$this->GenerateAllowedActions($this->basic_status_ins_, $this->shipping_status_ins_);

		//关键时间节点
		$this->time_line_ = array();
		//订单生成时间
		$this->time_line_[] = new TimeSpot($order_result['order_time'], '订单生成时间', 'orderPng', false);
		//客服确认时间
		$this->time_line_[] = new TimeSpot($order_result['confirm_time'], '客服确认时间', 'customPng');
		//到帐时间 or 付款时间
		$pay_time_name = ($order_result['pay_name'] == '货到付款') ? '到帐时间' : '付款时间';
		$this->time_line_[] = new TimeSpot($order_result['pay_time'], $pay_time_name, 'payPng');
		//物流发货时间
		$this->time_line_[] = new TimeSpot($order_result['shipping_time'], '物流发货时间', 'shippingPng');
		//用户确认收货时间
		$sql = "SELECT action_time FROM ecshop.ecs_order_action WHERE order_id = {$this->order_id_} AND (shipping_status = 2 OR shipping_status = 6) LIMIT 1";
		$this->time_line_[] = new TimeSpot($db->getOne($sql), '用户确认收货时间', 'completePng', false);
		//sort
		usort($this->time_line_, "TimeSpot::CompareSpotByTime");

		//平台信息
		require_once('cls_sales_order_platform_info.php');
		$this->platform_info_ = new ClsPlatformInfo(array('order_id'=>$this->order_id_, 'content_type' => 'platform_info'));
		$this->platform_info_->QueryData();

		//客服记录
		$this->service_count_ = $db->getOne("select count(*) from ecshop.service where order_id = {$this->order_id_}");
		//操作信息
		$this->action_count_ = $db->getOne("select count(*) from ecshop.ecs_order_action where order_id = {$this->order_id_}");
		//历史记录
		$this->history_count_ = 0;//$db->getOne("select count(*) from ecshop.order_mixed_status_history where order_id = {$this->order_id_}");
	}
	function QueryDataForModify(){
        global $db;
        $sql = "select party_id, order_status, shipping_status
                from ecshop.ecs_order_info
                where order_id = '{$this->order_id_}'";
        $order_result = $db->getRow($sql);
        
        $sql = "select order_id from ecshop.ecs_out_ship_order where order_id = '{$this->order_id_}' limit 1 ";
		$outShipOrder = $db->getOne($sql);
        //状态
        require_once ('cls_sales_order_basic_status.php');
        require_once ('cls_sales_order_shipping_status.php');
        $basic_status_ins = ClsSalesOrderBasicStatus::GetStatusInstance($order_result['order_status'], $outShipOrder);
        $shipping_status_ins = ClsSalesOrderShippingStatus::GetStatusInstance($order_result['shipping_status']);
        $this->party_id_ = $order_result['party_id'];
        $this->GenerateAllowedActions($basic_status_ins, $shipping_status_ins);
	}

	protected function PrepareForModify(){
		//
		if(!array_key_exists($this->id_map_['order_action_id'], $this->allowed_action_list_)){
            $this->error_info_ = array('err_no' => 2, 'message' => '当前订单状态不允许进行操作'.$this->id_map_['order_action_id']);
            return false;
		}
		//
		require_once('cls_sales_order_action.php');
		$id_map = array('order_id' => $this->order_id_, 'party_id' => $this->party_id_);
		$this->action_ = ClsSalesOrderAction::GenerateActionInsByID(
					$this->id_map_['order_action_id'], 
					$id_map,
					$this->error_info_);
		if(!$this->action_){
			return false;
		}
		//
		if($this->action_->GenerateSQL($this->data_map_, $this->error_info_)){
			$this->data_map_for_update_order_info_ = array_merge($this->data_map_for_update_order_info_, $this->action_->data_map_for_update_order_info_);
			$this->data_map_for_insert_order_action_ = array_merge($this->data_map_for_insert_order_action_, $this->action_->data_map_for_insert_order_action_);
			// $this->data_map_for_insert_order_mixed_status_= array_merge($this->data_map_for_insert_order_mixed_status_, $this->action_->data_map_for_insert_order_mixed_status_);
			$this->sql_result_ = array_merge($this->sql_result_, $this->action_->sqls_);
			return true;
		}else{
			return false;
		}
	}
	protected function ModifyViaRomeo(){
		return $this->action_->ModifyViaRomeo($this->error_info_);
	}
	private function GenerateAllowedActions($basic_status_ins, $shipping_status_ins){
		$this->allowed_action_list_ = array();
		if(isset($basic_status_ins) && isset($shipping_status_ins)){
			$allowed_action_ids = array_intersect($basic_status_ins->allowed_action_list_, $shipping_status_ins->allowed_action_list_);
			require_once('cls_sales_order_action.php');
			foreach ($allowed_action_ids as $action_id) {
				$this->allowed_action_list_[$action_id] = ClsSalesOrderAction::GetActionNameByID($action_id);
			}
		}
	}
	static protected function GetIDCheckList($action_type){
		switch ($action_type) {
			case 'query':
				return array('order_id');
			case 'update':
				return array('order_id', 'order_action_id');
			default:
				return array();
		}
	}
	static function GetAllAttrListForUpdate(){
		return array('note_content','is_shipping_note','add_time');
	}
	static function GetOrderInfoAttrListForUpdate(){
		return array();
	}


	static function GetOrderAttribute($order_id){
		require_once(dirname(__FILE__).'/../includes/lib_order.php');
		$order_attrs = get_order_attribute_list($order_id, null);
	    $order_attributes = array();
		if ($order_attrs) {
		    foreach ($order_attrs as $attr_name => $attr_value) {
		        $order_attributes[$attr_name] = $attr_value[0]['attr_value'];
		    }
		}
		return $order_attributes;
	}

	//basic
	var $order_id_;
	var $order_sn_ = 'order_sn';
	var $party_id_ = 'party_id';
	var $party_name_ = '组织名';
	//statuses
	var $status_list_ = array();
	//allowed_actions
	var $allowed_action_list_ = array();

	//platform
	var $platform_info_ = null;

	//timeline
	var $time_line_ = array();

	//service
	var $service_count_ = 0;
	//action
	var $action_count_ = 0;
	//history
	var $history_count_ = 0;
}


class TimeSpot{
	function  __construct($time, $action, $icon_img_name, $is_time_stamp = true){
		if(isset($time)){
			if($is_time_stamp){
				$this->timestamp_ = $time;
				$this->time_ = date('Y-m-d H:i:s', $time);
			}else{
				$this->time_ = $time;
				$this->timestamp_ = strtotime($time);
			}
		}

		$this->action_ = $action;
		$this->icon_img_name_ = $icon_img_name;
	}


	static function CompareSpotByTime( TimeSpot $a, TimeSpot $b ){
		if($a->timestamp_ != '0' && $b->timestamp_ != '0'){
			return $a->timestamp_ - $b->timestamp_;
		}else if($a->timestamp_ == '0' and $b->timestamp_ == '0'){
			return 1;
		}else if($a->timestamp_ == '0'){
			return 1;
		}else{
			return -1;
		}
	}

	var $time_ = '0000-00-00 00:00';
	var $timestamp_ = '0';
	var $action_ = '时间节点名';
	var $icon_img_name_ = 'nonePng';
}

?>
