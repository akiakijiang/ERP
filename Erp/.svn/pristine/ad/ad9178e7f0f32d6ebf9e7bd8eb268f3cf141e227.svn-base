<?php
/*
* 可优化：
* 1. 缓存
*/
require_once ('cls_sales_order.php');
require_once(ROOT_PATH.'/includes/helper/array.php');
require_once('cls_sales_order_tools.php');


class ClsSalesOrderConsignee extends ClsSalesOrderForModify implements ISalesOrderForUpdate{
	static protected function GetAllowedActionList(){
		return array('query', 'update');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}
	function QueryData(){
		global $db;
		$sql = "select user_id, consignee, sex, tel, mobile, email, zipcode, country, province, city, district, address,party_id,
		is_shortage_await,postscript
		from ecshop.ecs_order_info eoi
		where eoi.order_id = '{$this->order_id_}'";
		$order_result = $db->getRow($sql);
		$this->SetClsDataFromOrderInfo($order_result);
	}
	function SetClsDataFromOrderInfo($order_result){
		$this->DecideIfCanEdit($order_result['order_status'], $order_result['shipping_status']);
		global $db;
		$this->user_id_ = $order_result['user_id'];
		$sql = "select user_name from ecshop.ecs_users where user_id = {$order_result['user_id']}";
		$this->user_name_ = $db->getOne($sql);
		$sql = "select attr_value from ecshop.order_attribute where order_id = {$this->order_id_} and attr_name = 'TAOBAO_SELLER_MEMO' limit 1";
		$this->taobao_seller_meno_ = $db->getOne($sql);
		$this->consignee_ = $order_result['consignee'];
		$this->sex_ = $order_result['sex'];
		$this->address_ = $order_result['address'];
		$this->zipcode_ = $order_result['zipcode'];
		$this->tel_ = $order_result['tel'];
		$this->mobile_ = $order_result['mobile'];
		$this->email_ = $order_result['email'];
        $this->party_id_ = $order_result['party_id'];
        
		$sql = "select region_id, region_name from ecs_region where region_id in (
					'{$order_result['country']}', '{$order_result['province']}', '{$order_result['city']}', '{$order_result['district']}')";
		$region_list = Helper_Array::toHashmap((array)$db->getAll($sql), 'region_id','region_name');
	    $this->country_id_ = $order_result['country'];
	    $this->province_id_ = $order_result['province'];
	    $this->province_ = $region_list[$order_result['province']];
	    $this->city_id_ = $order_result['city'];
	    $this->city_ = $region_list[$order_result['city']];
	    $this->district_id_ = $order_result['district'];
	    $this->district_ = $region_list[$order_result['district']];
	    $this->province_list_ = clsSalesOrderTools::GetProviceList($this->country_id_);
	    //pp($this->province_list_);
	    $this->city_list_ = clsSalesOrderTools::GetCityList($this->province_id_);
	    $this->district_list_ = clsSalesOrderTools::GetDistrictList($this->city_id_);
		$this->buyer_detail_info_ = $this->consignee_ .', '. $this->mobile_ .', '.$this->province_ .' '.  $this->city_ .' '.$this->district_ .' '.$this->address_ .', '. $this->zipcode_ ;
		$this->remark_list_['48小时无货'] = ($order_result['is_shortage_await'] == 'NO') ? '取消订单' : '再等3天';
		$this->remark_list_['客户留言'] = $order_result['postscript'];
		$this->remark_list_['小二留言'] = $this->taobao_seller_meno_;
		
		$this->is_maintain_birthday_ = false;
		$this->birthday_ = '';
		// 亨氏维护宝宝出生日期
		if($this->party_id_ == '65609') {
			require_once(ROOT_PATH.'/admin/function.php');
			$result = check_heinz_user_birthday($this->order_id_);
			$this->is_maintain_birthday_ = $result['is_maintain_birthday'];
			$this->birthday_ = $result['birthday'];
		}
	}

	protected function PrepareForModify(){
		if(parent::PrepareForModify()){
			global $db;
	        $this->data_map_for_insert_order_action_['action_note'][] = '修改收货信息：';
	        $modify_keys = array_keys($this->data_map_);
	        $modify_content = array_intersect(array('consignee', 'sex'), $modify_keys);
	        if(!empty($modify_content)){
				$this->data_map_for_insert_order_action_['action_note'][] = '收货人信息修改为['.$this->data_map_['consignee'].'('.$this->data_map_['sex'].')]';
			}
			$modify_content = array_intersect(array('zipcode', 'province', 'city', 'district', 'address'), $modify_keys);
	        if(!empty($modify_content)){
	        	$sql = "select region_id, region_name from ecs_region where region_id in (
					'{$this->data_map_['province']}', '{$this->data_map_['city']}', '{$this->data_map_['district']}')";
				$region_list = Helper_Array::toHashmap((array)$db->getAll($sql), 'region_id','region_name');
				$province_name = $region_list[$this->data_map_['province']];
				$city_name = $region_list[$this->data_map_['city']];
				$district_name = $region_list[$this->data_map_['district']];
				$this->data_map_for_insert_order_action_['action_note'][] = 
					'收货地址修改为['.$province_name.'省'.$city_name.'市'.$district_name.'区'.
					$this->data_map_['address'].'('.$this->data_map_['zipcode'].')'.']';
			}
			$modify_content = array_intersect(array('tel', 'mobile', 'email'), $modify_keys);
	        if(!empty($modify_content)){
				$this->data_map_for_insert_order_action_['action_note'][] = 
					'收货人联系方式修改为['.$this->data_map_['tel'].']['.$this->data_map_['mobile'].']['.$this->data_map_['email'].']';
			}
			
			// 亨氏维护宝宝出生日期
			if($this->party_id_ == '65609' && $this->is_maintain_birthday_) {
			    $this->sql_result_[] = "update ecshop.brand_heinz_active_user set birthday = '{$this->data_map_['birthday']}' where heinz_user_id = '{$this->mobile_}' limit 1";
			    $sql = "select date(birthday) from ecshop.brand_heinz_active_user where heinz_user_id = '{$this->mobile_}' limit 1";
			    $origin_birthday = $db->getOne($sql);
			    $this->data_map_for_insert_order_action_['action_note'][] = 
					"宝宝出生日期从{$origin_birthday}改为{$this->data_map_['birthday']}";
			}
			
			return true;
		}else{
			return false;
		}

	}


	static function GetAllAttrListForUpdate(){
		return array_merge(ClsSalesOrderConsignee::GetUserInfoAttrListForUpdate(), 
							ClsSalesOrderConsignee::GetOrderInfoAttrListForUpdate());
	}
	
	static function GetUserInfoAttrListForUpdate(){
		return array('birthday');
	}
	
	static function GetOrderInfoAttrListForUpdate(){
		return array('consignee', 'sex', 'tel', 'mobile', 'email', 
			'zipcode', 'province', 'city', 'district', 'address');
	}

	//
	var $order_id_;
	//
	var $user_id_;
	var $user_name_;
	var $consignee_;
	var $sex_;
	//
	var $province_id_;
	var $province_;
	var $city_id_;
	var $city_;
	var $district_id_;
	var $district_;
	var $address_;
    var $province_list_;
	var $buyer_detail_info_;
	var $zipcode_;
	var $tel_;
	var $mobile_;
	var $email_;
	var $taobao_seller_meno_;
	//
	var $remark_list_ = array();
}



?>