<?php
require_once ('cls_sales_order.php');

class ClsPlatformInfo extends ClsSalesOrderForModify implements ISalesOrderForUpdate{
	static protected function GetAllowedActionList(){
		return array('query', 'update');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}
	function QueryData(){
		global $db, $_CFG;
		//
		$this->platform_list_ = $_CFG['adminvars']['outer_type'];
		//
		$sql = "select taobao_order_sn, d.name as distributor,eoi.distributor_id,eoi.order_status, eoi.shipping_status,eoi.nick_name,eoi.shipping_id 
				from ecshop.ecs_order_info eoi
				left join ecshop.distributor d on eoi.distributor_id = d.distributor_id
				where eoi.order_id = '{$this->order_id_}'";
		$order_result = $db->getRow($sql);
		$this->order_sn_ = $order_result['taobao_order_sn'];
		$this->distributor_ = $order_result['distributor'];
		$this->distributor_id_ = $order_result['distributor_id'];
		$this->user_id_ = $order_result['nick_name'];
		$this->shipping_id_ = $order_result['shipping_id'];
		//
		require_once('cls_sales_order_header.php');
		$order_attributes = SalesOrderHeader::GetOrderAttribute($this->order_id_);
		$this->id_ = $order_attributes['OUTER_TYPE'];
//		$this->user_id_ = $order_attributes['TAOBAO_USER_ID'];
		$this->point_fee_ = $order_attributes['TAOBAO_POINT_FEE']==''?0:$order_attributes['TAOBAO_POINT_FEE'];
		//
		$this->DecideIfCanEdit($order_result['order_status'], $order_result['shipping_status']);
	}

	protected function PrepareForModify(){
		global $db;
		$order_info_attr_list = ClsPlatformInfo::GetOrderInfoAttrListForUpdate();
		$order_attr_attr_list = ClsPlatformInfo::GetOrderAttrAttrListForUpdate();
        $this->data_map_for_insert_order_action_['action_note'][] = '修改平台信息：';
		if($this->order_sn_!= $this->data_map_['taobao_order_sn']) {
			$sql_check = "select order_id from ecshop.ecs_order_info where taobao_order_sn = '{$this->data_map_['taobao_order_sn']}' and party_id = '65625';";
			$check_result = $db->getRow($sql_check);
			if($check_result) {
				$this->error_info_['err_no'] = 1;
				$this->error_info_['message'] = '该淘宝订单号已经存在了，ERP订单号:'.$check_result['order_id'];
				return false;
			} else {
				$this->data_map_for_insert_order_action_['action_note'][] 
				   = '淘宝订单号：'.$this->order_sn_.' --> '.$this->data_map_['taobao_order_sn'];	
			}
	    }
	    if(!in_array($this->data_map_['distributor_id'],array('2010','1950')) && in_array($this->shipping_id_,array('149','146'))){
	    	$this->error_info_['err_no'] = 1;
			$this->error_info_['message'] = '京东COD与京东配送 快递目前仅支持ecco部分京东店铺';
			return false;
	    }
	    if($this->distributor_id_!= $this->data_map_['distributor_id']) {
	    	$sql = "select d.name from ecshop.distributor d 
				   where d.distributor_id = '{$this->data_map_['distributor_id']}' limit 1";
			$modify_distributor = $db->getOne($sql);
	    	$this->data_map_for_insert_order_action_['action_note'][] 
				   = '分销商：'.$this->distributor_.' --> '.$modify_distributor;	
	    }
	    if($this->user_id_!= $this->data_map_['TAOBAO_USER_ID']) {
	    	$this->data_map_for_insert_order_action_['action_note'][] 
				   = '用户：'.$this->user_id_.' --> '.$this->data_map_['TAOBAO_USER_ID'];
			$this->sql_result_[] = "update ecshop.ecs_order_info 
									set nick_name = '{$this->data_map_['TAOBAO_USER_ID']}' 
									where order_id = {$this->order_id_}";
	    }
	    if($this->point_fee_!= $this->data_map_['TAOBAO_POINT_FEE']) {
	    	$this->data_map_for_insert_order_action_['action_note'][] 
				   = '积分：'.$this->point_fee_.' --> '.$this->data_map_['TAOBAO_POINT_FEE'];	
	    }
	    if($this->id_!= $this->data_map_['OUTER_TYPE']) {
	    	$this->data_map_for_insert_order_action_['action_note'][] 
				   = '平台：'.$this->id_.' --> '.$this->data_map_['OUTER_TYPE'];	
	    }
	    
		foreach ($this->data_map_ as $attr_name => $attr_value) {
			# code...
			if(in_array($attr_name, $order_info_attr_list)){
				$this->data_map_for_update_order_info_[$attr_name] = $attr_value;
			}else if(in_array($attr_name, $order_attr_attr_list)){
				if($attr_value != ''){
					$sql = "select 1 from ecshop.order_attribute where order_id = {$this->order_id_} and attr_name = '{$attr_name}'";
					global $db;
					if($db->getOne($sql)){
						$this->sql_result_[] = "update ecshop.order_attribute
									set attr_value = '{$attr_value}' 
									where order_id = {$this->order_id_} and attr_name = '{$attr_name}'";
					}else{
						$this->sql_result_[] = "insert into ecshop.order_attribute
									(order_id, attr_value, attr_name) values ({$this->order_id_}, '{$attr_value}', '{$attr_name}')";
					}
				}else{
					//wrong attrs
					$this->error_info_['err_no'] = 2;
					$this->error_info_['message'] = '属性'.$attr_name.'的值为空';
					return false;
				}
			}
		}
		return true;
	}

	static function GetAllAttrListForUpdate(){
		return array_merge(ClsPlatformInfo::GetOrderInfoAttrListForUpdate(), 
							ClsPlatformInfo::GetOrderAttrAttrListForUpdate());
	}
	static function GetOrderInfoAttrListForUpdate(){
		return array('taobao_order_sn', 'distributor_id','order_id');
	}
	private static function GetOrderAttrAttrListForUpdate(){
		return array('OUTER_TYPE', 'TAOBAO_USER_ID', 'TAOBAO_POINT_FEE','content_type');
	}

	var $order_id_;
	var $platform_list_ = array('平台列表');
	var $id_ = '平台id';
	var $order_sn_ = '平台订单号';
	var $user_id_ = '平台用户id';
	var $point_fee_ = '平台使用积分(optional)';
	var $distributor_ = '平台分销商';
}

?>