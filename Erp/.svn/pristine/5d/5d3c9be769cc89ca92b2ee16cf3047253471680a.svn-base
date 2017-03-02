<?php
/*
* 可优化：
* 1. 缓存
*/
require_once ('cls_sales_order.php');

class ClsSalesOrderLogisticInfo extends ClsSalesOrderBasic{
	static protected function GetAllowedActionList(){
		return array('query');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}

	function QueryData(){
		require_once('../function.php');
		require_once('cls_sales_order_tools.php');

        global $db;
        $sql = "SELECT eoi.order_status,eoi.shipping_status,eoi.party_id, eoi.facility_id, s.midway_address, sum(ifnull(eg.goods_weight, 0) * eog.goods_number) as goods_weight,
        				eoi.province, eoi.city, eoi.district
        		FROM ecshop.ecs_order_info eoi
        		LEFT JOIN ecshop.ecs_shipping s on eoi.shipping_id = s.shipping_id
        		LEFT JOIN ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id
	            LEFT JOIN ecshop.ecs_goods eg ON eog.goods_id = eg.goods_id
        		WHERE eoi.order_id = {$this->order_id_}
        		GROUP BY eog.order_id";
        $order_result = $db->getRow($sql);
        $this->DecideIfCanEdit($order_result['order_status'], $order_result['shipping_status']);
        
		$this->midway_address_ = $order_result['midway_address'];
		$this->facility_name_ = ClsSalesOrderTools::getFacilityNameByID($order_result['facility_id']);

        $sql_shipping_cost = "select s.shipment_id, s.shipping_cost ,s.tracking_number, round(s.shipping_leqee_weight/1000,2) as shipping_leqee_weight, es.shipping_name,if(s.status='SHIPMENT_SHIPPED','已交接','待发货') as shipping_status
									from romeo.shipment s
									left join romeo.order_shipment os on os.shipment_id = s.shipment_id
									left join ecshop.ecs_shipping es ON s.shipment_type_id = es.shipping_id
									where os.order_id = '{$this->order_id_}'";
        $shipping_cost_result = $db->getAll($sql_shipping_cost);
        foreach ($shipping_cost_result as $value) {
        	# code...
			$this->shipping_list_[] = new ClsShippingInfo($value['shipment_id'], $value['tracking_number'], $value['shipping_cost'], $value['shipping_name'], $value['shipping_leqee_weight'],$value['shipping_status']);
        }
        
//        $sql_transit_step="select td.status_desc,td.status_time from ecshop.ecs_taobao_logistics_trace_detail td 
//        						left join ecshop.ecs_taobao_logistics_trace t on td.logistics_trace_id=t.logistics_trace_id
//        						where t.order_id = '{$this->order_id_}'
//        						order by td.status_time ";
//        $transit_step_result = $db->getAll($sql_transit_step);
//        foreach($transit_step_result as $value){
//        	$this->transit_step_[] = new ClsTransitStep($value['status_time'],$value['status_desc']);
//        }						

		//预估重量
		//商品
		$this->estimate_goods_weight_ = $order_result['goods_weight'];
		//耗材
		$order_param = array('party_id'=>$order_result['party_id'], 'order_id'=>$this->order_id_);
		$this->package_estimate_weight_ = get_package_weight($order_param);
		//总重量
		$this->total_estimate_weight_ = $this->estimate_goods_weight_ + $this->package_estimate_weight_ ;


		//
		if ($order_result['district'] != '0') {
			$region_id = $order_result['district'] ;
		} elseif ($order_result['district'] == '0' && $order_result['city'] != '0') {
			$region_id = $order_result['city'] ;
		} else {
			$region_id = $order_result['province'] ;
		}

		if('65558' == $order_result['party_id']){
			//获取所有订单商品
			$product_sql = "select pm.product_id, sum(eog.goods_number) as goods_number
				from ecshop.ecs_order_goods eog
				left join romeo.product_mapping pm on eog.goods_id = pm.ecs_goods_id and eog.style_id = pm.ecs_style_id
				where eog.order_id ={$this->order_id_}
				group by pm.product_id";
			$ref_list = $product_list=array();
			$db->getAllRefBy($product_sql, array('product_id'), $ref_list, $product_list);
			$product_list = $product_list['product_id'];
			$product_count = count($product_list);
			$product_ids = $ref_list['product_id'];
			
			//获取所有仓库订单内商品库存
			$facility_product_sql = "select product_id, facility_id, sum(STOCK_QUANTITY) as STOCK_QUANTITY
							from romeo.inventory_summary
							where product_id ".db_create_in($product_ids)." 
								and STOCK_QUANTITY > 0
							group by product_id, facility_id";
			
			$db->getAllRefBy($facility_product_sql, array('facility_id'), $facility_list, $facility_product_list);
			$available_facility_list = array();
			if(!empty($facility_product_list['facility_id'])) {
				foreach ($facility_product_list['facility_id'] as $facility_id => $facility_product) {
					# code...	
					if(count($facility_product) == $product_count){
						$if_enough = true;
						foreach ($facility_product as $value) {
							# code...
							if($value['STOCK_QUANTITY'] < $product_list[$value['product_id']][0]['goods_number']){
								$if_enough = false;
								break;
							}
						}
						if($if_enough){
							$available_facility_list[] = $facility_id;
						}
					}
			   }
			}
			
			$physical_facility_list = ClsSalesOrderLogisticInfo::GetPhysicalFacilityList($available_facility_list);
		}else{
			$physical_facility_list = ClsSalesOrderLogisticInfo::GetPhysicalFacilityList(array($order_result['facility_id']));			
		}
		$this->best_express_list_ = ClsSalesOrderLogisticInfo::GetExpressInfoList(
			'BEST', $order_result['party_id'], $physical_facility_list, $region_id, $this->total_estimate_weight_);
		$other_express_list = ClsSalesOrderLogisticInfo::GetExpressInfoList(
			'SHOW', $order_result['party_id'], $physical_facility_list, $region_id, $this->total_estimate_weight_);
		$this->other_express_list_ = array_diff_key($other_express_list, $this->best_express_list_);
		
	}

	//weights
	var $total_estimate_weight_;
	var $package_estimate_weight_;
	var $estimate_goods_weight_;

	//中转地址
	var $midway_address_;
	var $facility_name_;

	var $shipping_list_ = array();
	var $best_express_list_ = array();
	var $other_express_list_ = array();

	/*
	* 根据订单商品的质量 计算对应的快递费用
	*/
	private function GetExpressInfoList($express_type, $party_id, $physical_facility_list, $region_id, $order_weight) {
		// 先过滤掉不用系统选择快递的业务
		if ('16' == $party_id) {
			return array();
		}
		//
		global $db;
		$arrivedMap = array('ALL' => '全境可达', 'PARTLY' => '部分可达', 'NONE' => '不可达');

		$express_list = array();
		foreach ($physical_facility_list as $facility_id) {
			if($express_type == 'BEST'){
				$sql = "select s.shipping_name, r.region_id, r.arrived, a.shipping_id,  c.first_weight, c.first_fee, c.continued_fee,ass.facility_id,f.facility_name
					from ecshop.ecs_area_region r
					left join ecshop.ecs_shipping_area a on r.shipping_area_id = a.shipping_area_id
					left join ecshop.ecs_carriage c on a.shipping_id = c.carrier_id
					left join ecshop.ecs_shipping s on a.shipping_id = s.shipping_id
					left join ecshop.ecs_party_assign_shipping ass on s.shipping_id = ass.shipping_id and c.facility_id = ass.facility_id
					left join romeo.facility f on f.facility_id = ass.facility_id
					where r.region_id = '{$region_id}'
					and c.facility_id = '{$facility_id}' and c.region_id = '{$region_id}'
					and s.support_cod = 0 and s.support_no_cod = 1
					and ass.party_id = {$party_id} and ass.enabled = 1 " ;
			}else if($express_type == 'SHOW'){
				$sql = "select s.shipping_name, r.region_id, r.arrived, a.shipping_id,  c.first_weight, c.first_fee, c.continued_fee, c.facility_id, f.facility_name
					from
					ecshop.ecs_party_assign_show_shipping ss
					left join ecshop.ecs_carriage c on ss.shipping_id = c.carrier_id
					left join ecshop.ecs_shipping_area a on a.shipping_id = ss.shipping_id
					left join ecshop.ecs_area_region r on r.shipping_area_id = a.shipping_area_id
					left join ecshop.ecs_shipping s on ss.shipping_id = s.shipping_id
					left join romeo.facility f on f.facility_id = c.facility_id
					where r.region_id = '{$region_id}'
					and c.facility_id = '{$facility_id}' and c.region_id = '{$region_id}'
					and s.support_cod = 0 and s.support_no_cod = 1 and ss.party_id = {$party_id}
					group by a.shipping_id";
			}else{
				return array();
			}
			$shippingInfos = $db->getAll($sql);
			if($shippingInfos != null){
				foreach ($shippingInfos as $shippingInfo) {
					$express_list[$shippingInfo['facility_id'].'_'.$shippingInfo['shipping_id']] = new ClsExpressInfo(
							$shippingInfo['facility_id'],$shippingInfo['facility_name'], $shippingInfo['shipping_id'], $shippingInfo['shipping_name'],
							$shippingInfo['first_fee'] + get_weight($order_weight, $shippingInfo) * $shippingInfo['continued_fee'],
							$arrivedMap[$shippingInfo['arrived']]);
				}
			}
		}
		usort($express_list, "ClsSalesOrderLogisticInfo::CompareExpressItem");
		return $express_list ;
	}
	 
	static private function CompareExpressItem(ClsExpressInfo $a,ClsExpressInfo $b ){
		return (double)$a->shipping_fee_ > (double)$b->shipping_fee_;
	}

	static private function GetPhysicalFacilityMap($facility_list){
		require_once('../function.php');
		$facility_map = array();
		foreach ($facility_list as $value) {
			# code...
			$facility_map[$value] = facility_convert($value);
		}
		return $facility_map;		
	}

	static private function GetPhysicalFacilityList($facility_list){
		require_once('../function.php');
		$physical_facility_list = array();
		foreach ($facility_list as $facility_id) {
			# code...
			$physical_facility_id = facility_convert($facility_id);
			if(!in_array($physical_facility_id, $physical_facility_list)){
				$physical_facility_list[] = $physical_facility_id;
			}
		}
		return $physical_facility_list;		
	}
}

class ClsShippingInfo{
	function __construct($shipment_id, $tracking_number, $shipping_cost, $shipping_name, $shipping_leqee_weight,$shipping_status){
		$this->shipment_id_ = $shipment_id;
		$this->tracking_number_ = $tracking_number;
		$this->shipping_cost_ = $shipping_cost;
		$this->shipping_name_ = $shipping_name;
		$this->shipping_leqee_weight_ = $shipping_leqee_weight;
		$this->shipping_status_ = $shipping_status; 
	}
	var $shipment_id_;
	var $tracking_number_;
	var $shipping_cost_;
	var $shipping_name_;
	var $shipping_leqee_weight_;
	var $shipping_status_;
}

//class ClsTransitStep{
//	function __construct($status_time,$status_desc){
//		$this->status_time_ = $status_time;
//		$this->status_desc_ = $status_desc;
//	}
//	var $status_time_;
//	var $status_desc_;
//}
class ClsExpressInfo{
	function __construct($facility_id,$facility_name, $shipping_id, $shipping_name, $shipping_fee, $arrive_type){
		$this->facility_id_ = $facility_id;
		$this->facility_name_ = $facility_name;
		$this->shipping_id_ = $shipping_id;
		$this->shipping_name_ = $shipping_name;
		$this->shipping_fee_ = $shipping_fee;
		$this->arrive_type_ = $arrive_type;
	}
	//配送仓
	var $facility_id_;
	var $facility_name_;
	//快递
	var $shipping_id_;
	var $shipping_name_;
	//预算快递费
	var $shipping_fee_;
	//可达性
	var $arrive_type_;
}

?>