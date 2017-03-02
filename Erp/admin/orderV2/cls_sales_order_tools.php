<?php

class ClsSalesOrderTools{
	//地区相关
	public static function GetCountryList(){
		// 取得国家列表
		$result = ClsSalesOrderTools::GetDataUsingCache("region:country", array(0=>array(0)), 'ClsSalesOrderTools::GetRegionListByParentIdAndTypePrivate');
		return $result[0];
	}

	static function GetProviceList($country_id){
		// 取得省份列表
		$result = ClsSalesOrderTools::GetDataUsingCache("region:province", array($country_id=>array(1, $country_id)), 'ClsSalesOrderTools::GetRegionListByParentIdAndTypePrivate');
		return $result[$country_id];
	}

	static function GetCityList($provice_id){
		// 取得城市列表
		$result = ClsSalesOrderTools::GetDataUsingCache("region:city", array($provice_id=>array(2, $provice_id)), 'ClsSalesOrderTools::GetRegionListByParentIdAndTypePrivate');
		return $result[$provice_id];
	}
	static function GetDistrictList($city_id){
		// 取得地区列表
		$result = ClsSalesOrderTools::GetDataUsingCache("region:district", array($city_id=>array(3, $city_id)), 'ClsSalesOrderTools::GetRegionListByParentIdAndTypePrivate');
		return $result[$city_id];
	}
	private static function GetRegionListByParentIdAndTypePrivate($region_type, $parent_id = null){
		global $db;
		$sql = "select * from ecs_region where region_type = {$region_type} ";
		if(isset($parent_id)){
			$sql .= "and parent_id = '{$parent_id}'";
		}
		$regions = $db->getAll($sql);
		return $regions;
	}

	//支付相关
	static function GetPaymentList(){
		require_once('../function.php');
		$result = ClsSalesOrderTools::GetDataUsingCache("payments", array(0=>array()), 'getPayments');
		return $result[0];
	}
	static function GetGroupedPaymentList(){
		require_once('../function.php');
		$result = ClsSalesOrderTools::GetDataUsingCache("grouped_payments", array(0=>array()), 'getPayments');
		$payment_group = array();
		foreach ($result[0] as $pay_id => $payment) {
			$pay_order = $payment['pay_order'];
			if($pay_order >= 100 && $pay_order < 200){
				$payment_group['网银支付'][$pay_id] = $payment;
			}else if($pay_order >= 200 && $pay_order < 300){
				$payment_group['货到付款'][$pay_id] = $payment;
			}else if($pay_order >= 300 && $pay_order < 400){
				$payment_group['转账/汇款'][$pay_id] = $payment;
			}else if($pay_order >= 400 && $pay_order < 500){
				$payment_group['第三方支付'][$pay_id] = $payment;
			}else if($pay_order >= 500 && $pay_order < 600){
				$payment_group['信用卡支付'][$pay_id] = $payment;
			}else if($pay_order >= 600 && $pay_order < 700){
				$payment_group['内部购机'][$pay_id] = $payment;
			}else if($pay_order >= 700 && $pay_order < 900 && $pay_order !=801 && $pay_order != 802){
				$payment_group['海外支付'][$pay_id] = $payment;
			}else{
				$payment_group['其他'][$pay_id] = $payment;
			}
		}
		return $payment_group;
	}
	static function GetPaymentByID($id){
		$result = ClsSalesOrderTools::GetDataUsingCache("payments", array($id=>array($id)), 'ClsSalesOrderTools::GetPaymentByIDPrivate');
		return $result[$id];
	}
	private static function GetPaymentByIDPrivate($id){
		$payment_list = ClsSalesOrderTools::GetPaymentList();
		return $payment_list[$id];
	}

	// 仓库相关
	static function GetFacilityList(){
		global $db;
	    $sql = "select facility_id, facility_name from romeo.facility";
		require_once('../../includes/helper/array.php');
		return Helper_Array::toHashmap((array)$db->getAll($sql), 'facility_id','facility_name');
	}
	static function GetFacilityNameByID($id){
		$result = ClsSalesOrderTools::GetDataUsingCache("facilities", array($id=>array($id)), 'ClsSalesOrderTools::GetFacilityByIDPrivate');
		return $result[$id];
	}
	private static function GetFacilityByIDPrivate($id){
		$facility_list = ClsSalesOrderTools::GetFacilityList();
		return $facility_list[$id];
	}

	//快递相关
	static function GetShippingList(){
		require_once('../function.php');
		$result = ClsSalesOrderTools::GetDataUsingCache("shipping", array(0=>array()), 'getShippingTypes');
		return $result[0];
	}
	static function GetGroupedShippingList(){
		require_once('../function.php');
		$result = ClsSalesOrderTools::GetDataUsingCache("grouped_shipping", array(0=>array()), 'getShippingTypes');
		$shipping_group = array();
		foreach ($result[0] as $shipping_id => $shipping) {
			$support_no_cod = $shipping['support_no_cod'];
			$support_cod = $shipping['support_cod'];
			if($support_no_cod == 1 && $support_cod == 0){
				$shipping_group['款到发货'][$shipping_id] = $shipping;
			}else if($support_no_cod == 0 && $support_cod == 1){
				$shipping_group['货到付款'][$shipping_id] = $shipping;
			}else if($support_no_cod == 1 && $support_cod == 1){
				$shipping_group['上门自提'][$shipping_id] = $shipping;
			}else{
				$shipping_group['其他'][$shipping_id] = $shipping;
			}
		}
		return $shipping_group;
	}
	static function GetShippingByID($id){
		$result = ClsSalesOrderTools::GetDataUsingCache("shipping", array($id=>array($id)), 'ClsSalesOrderTools::GetShippingByIDPrivate');
		return $result[$id];
	}
	private static function GetShippingByIDPrivate($id){
		$list = ClsSalesOrderTools::GetShippingList();
		return $list[$id];
	}

	//检查支付方式与快递方式的兼容性
	static function CheckPaymentAndShipping($payment, $shipping, &$error_info)
	{
        if($shipping['support_cod'] && $shipping['support_no_cod']){
        	//该快递方式均支持cod和非cod，当然every支付方式都ok啦~~
        	return true;
        }else if(!$shipping['support_cod'] && !$shipping['support_no_cod'])
        {
        	//该快递方式对cod和非cod均不支持，可能出现这种情况吗...
	        $error_info = array('err_no' => 3, 'message' => "快递方式异常，不应该出现这种情况，请找erp组");
    	    return false;
        }else{
        	//快递方式支持其中一种付款方式
	        if($payment['is_cod'] != $shipping['support_cod']){
    	        $error_info = array('err_no' => 3, 'message' => "配送方式与支付方式的COD属性不一致");
        	    return false;
        	}else{
        		return true;
        	}
        }
	}

	//分销商相关
	static function GetDistributorListOfIDIndex(){
		global $db;
	    $sql = "select distributor_id as id, name from ecshop.distributor";
		require_once('../../includes/helper/array.php');
		return Helper_Array::toHashmap((array)$db->getAll($sql), 'id','name');
	}
	static function GetDistributorListOfNameIndex(){
		global $db;
	    $sql = "select distributor_id as id, name from ecshop.distributor";
		require_once('../../includes/helper/array.php');
		return Helper_Array::toHashmap((array)$db->getAll($sql), 'name', 'id');
	}
	static function GetDistributorNameByID($id){
		$result = ClsSalesOrderTools::GetDataUsingCache("distributor:id", array($id=>array($id)), 'ClsSalesOrderTools::GetDistributorByIDPrivate');
		return $result[$id];
	}
	static function GetDistributorIDByName($name){
		$result = ClsSalesOrderTools::GetDataUsingCache("distributor:name", array($name=>array($name)), 'ClsSalesOrderTools::GetDistributorByNamePrivate');
		return $result[$name];
	}
	private static function GetDistributorByIDPrivate($id){
		$result = ClsSalesOrderTools::GetDistributorListOfIDIndex();
		return $result[$id];
	}
	private static function GetDistributorByNamePrivate($name){
		$result = ClsSalesOrderTools::GetDistributorListOfNameIndex();
		return $result[$name];
	}

	// 缓存处理封装
	private static function GetDataUsingCache($cache_id, $key_vars_list = array(), $data_generate_callback, $cache_policy = array('life_time' => 21600), $refresh = false){
		// 取得国家列表 
	    // 缓存策略：默认为6小时缓存
	    // key_vars_list 为 key和回调函数$data_generate_callback参数的mapping列表
	    // 如：缓存的key为key1，对应vars为vars1，可调用$data_generate_callback(vars1)获取该缓存value
	    include_once('cls_sales_order_cache.php');
	    $cache = ClsSalesOrderCache::instance();
		$key_value_mapping = array(); 
	    if ($refresh == true) {
	    	$cache->delete($cache_id);
	    } else {
		    // 尝试从文件缓存取得对应关系表，并保存到内存
	        $key_value_mapping = $cache->get($cache_id, $cache_policy);
	    }

        $result = array();   // 返回结果
        $not_hit = false;
	    foreach ($key_vars_list as $key => $vars) {
	        // 判断在缓存中是否有
	        if (isset($key_value_mapping[$key])) {
	            $result[$key] = $key_value_mapping[$key]; 
	        } else {
	            $result[$key] = $key_value_mapping[$key] = call_user_func_array($data_generate_callback, $vars);
	            $not_hit = true;
	        }
	    }

	    if($not_hit){
		    // 写入缓存
		    $cache->set($cache_id, $key_value_mapping, $cache_policy);
	    }

		return $result;
	}

	//币种
	static function GetSymbolForCurrency($currency)
	{
		switch ($currency) {
			case '':
			case 'RMB':
				return '￥';
			case 'HKD':
				return 'HK$';
			case 'USD':
				return '$';
			default:
				return $currency;
		}
	}
}

?>