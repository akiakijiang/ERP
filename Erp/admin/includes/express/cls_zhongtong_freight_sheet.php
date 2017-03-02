<?php
require_once ROOT_PATH . 'admin/includes/express/cls_freight_sheet.php';
class ZhongtongFreightSheet extends FreightSheet {

    /**
     * 计算快递费
     * @param $order
     * 中通  重量单位为 kg   一部分省份 首重为 1kg  续重均为1kg；另一部分首重为0.5kg，续重为1kg
     * 不足1KG 按照1kg计算  中通快递只有上海仓库使用
     * 
     */
    public function calculate_fee($order) {
        if ($order['first_fee'] == -1 &&  $order['continued_fee'] == -1) {
            return false;
        }
        //快递公司重量
        $weight = $this->get_weight($order['excel_row']['weight'], $order);
        //仓库重量
        $shipping_weight = $this->get_shipping_weight($order) / 1000;
        $shipping_weight = $this->get_weight($shipping_weight, $order);
        if($order['facility_id']== '22143846' || $order['facility_id'] == '24196974' || $order['facility_id'] == '22143847' || $order['facility_id'] == '19568549'){
			//根据目的省份不同计算方式不同
			if($order['province'] == 10 || $order['province'] == 11 ||$order['province'] == 12 ||$order['province'] == 13){
				//江浙沪皖地区
				if($weight>1.0){
					$calculated_fee = ($order['first_fee'] + $order['continued_fee'] * ($weight-1)) ;
				}else{
					$calculated_fee = $order['first_fee'];
				}
				if($shipping_weight>1.0){
					$shipping_fee = ($order['first_fee'] + ($shipping_weight-1) * $order['continued_fee']) ;
				}else{
					$shipping_fee = $order['first_fee'];
				}
			}else{
				//tracking_fee面单费,operation_fee操作费,weighing_fee 过磅费,transit_fee 中转费,lowest_transit_fee 最低中转费 
				if($order['transit_fee']>$order['lowest_transit_fee']){
					$calculated_fee = $order['tracking_fee']+$order['operation_fee']+$weight*($order['transit_fee']+$order['weighing_fee']);
					$shipping_fee = $order['tracking_fee']+$order['operation_fee']+$shipping_weight*($order['transit_fee']+$order['weighing_fee']);
				}else{
					$calculated_fee = $order['tracking_fee']+$order['operation_fee']+$order['lowest_transit_fee']+$weight*$order['weighing_fee'];
					$shipping_fee = $order['tracking_fee']+$order['operation_fee']+$order['lowest_transit_fee']+$shipping_weight*$order['weighing_fee'];
				}
			}
	        $calculated_fee = $this->get_real_value($calculated_fee);
	        $shipping_fee = $this->get_real_value( $shipping_fee);
	        $order['calculated_fee'] = $calculated_fee;
	        $order['shipping_fee'] = $shipping_fee;
	        
        }else{
	        $calculated_fee = ($order['first_fee'] + $order['continued_fee'] * $weight);
	        $calculated_fee = $this->get_real_value($calculated_fee);
	      
	        $shipping_fee = $this->get_real_value( $shipping_fee);
	        $shipping_fee = ($order['first_fee'] + $shipping_weight * $order['continued_fee']);
	        $order['calculated_fee'] = $calculated_fee;
	        $order['shipping_fee'] = $shipping_fee;
        }
        return $order;
    }
    
	 /**
     * 查询运费
     * @param array $order 订单信息
     * @return array 包含运费的订单信息
     */
    protected function get_freight($order) {
        global $db;
        //内部员工自提  首重快递费和续重快递费为0
        if ($order['shipment_type_id'] == 86) {
            $order['first_weight'] = 0;
            $order['first_fee'] = 0;
            $order['continued_fee'] = 0;
        } else {
        	
            if ($order['addr']['district']) {
                $region_id = $order['addr']['district'];
            } elseif ($order['addr']['city']) {
                $region_id = $order['addr']['city'];
            } elseif ($order['addr']['province']) {
                $region_id = $order['addr']['province'];
            }
            $facility_id = facility_convert($order['addr']['facility_id']);
            $sql = "
                SELECT first_weight, first_fee, continued_fee,tracking_fee,operation_fee,weighing_fee,transit_fee,lowest_transit_fee 
                FROM ecshop.ecs_express_fee -- ecshop.ecs_carriage 
                WHERE facility_id = '{$facility_id}' AND carrier_id = '{$order['shipment_type_id']}' 
                    AND region_id = '{$region_id}'
            ";
            $region_list = $db->getRow($sql);
            if (!empty($region_list)) {
                $order['first_weight'] = $region_list['first_weight'];
                $order['first_fee'] = $region_list['first_fee'];
                $order['continued_fee'] = $region_list['continued_fee'];
                $order['tracking_fee'] = $region_list['tracking_fee'];
                $order['operation_fee'] = $region_list['operation_fee'];
                $order['weighing_fee'] = $region_list['weighing_fee'];
                $order['transit_fee'] = $region_list['transit_fee'];
                $order['lowest_transit_fee'] = $region_list['lowest_transit_fee'];
            } else {
                $order['first_weight'] = -1;
                $order['first_fee'] = -1;
                $order['continued_fee'] = -1;
                $order['tracking_fee'] = -1;
                $order['operation_fee'] = -1;
                $order['weighing_fee'] = -1;
                $order['transit_fee'] = -1;
                $order['lowest_transit_fee'] = -1;
            }
        }
        return $order;
    }
    /**
     * 获取办公件的快递费
     * @param array $row
     */
    protected function check_office_fee ($row) {
        global $db;
        if (!empty($row['start_district'])) {
            $from_region_id = $row['start_district'];
        } elseif (!empty($row['start_city'])) {
            $from_region_id = $row['start_city'];
        } elseif (!empty($row['start_province'])) {
            $from_region_id = $row['start_province'];
        }
        //转换为仓库 todo
        $region_facility_list = array(
            '462' => '19568549', // 上海市青浦区崧煌路810号3号楼2楼 
            '451' => '12768420', //上海市长宁区愚园路1258号1005室
            '490' => '42741887', //北京海淀区羊坊店路18号光耀东方S座847室
            '2771'=> '19568548', //广东省  东莞市  长安镇  乌沙步步高大道126号
        );
        $str = "";
        if (array_key_exists($from_region_id, $region_facility_list)) {
            $facility_id = $region_facility_list[$from_region_id] ;
            $str = " and facility_id = '{$facility_id}' ";
            $row['facility_id'] = $facility_id;
        } else {
            $str = " and from_region_id = {$from_region_id} ";
        }
        if (!empty($row['end_district'])) {
            $region_id = $row['end_district'];
        } elseif (!empty($row['end_city'])) {
            $region_id = $row['end_city'];
        } elseif (!empty($row['end_province'])) {
            $region_id = $row['end_province'];
        }
        $sql = "
            select first_weight, first_fee, continued_fee,tracking_fee,operation_fee,weighing_fee,transit_fee,lowest_transit_fee 
            from ecshop.ecs_express_fee -- ecshop.ecs_carriage 
            where region_id = {$region_id}  and carrier_id = {$row['shipping_id']} ". $str ."
            limit 1
        ";
        $fee_item = $db->getRow($sql);
        if (!empty($fee_item)) {
            $row['first_weight'] =  $fee_item['first_weight'];
            $out_weight = $this->get_weight($row['excel_row']['weight'], $row);
            if ($row['package_type'] == 1) {
                $row['office_shipping_fee'] = $fee_item['first_fee'];
                $row['weight'] = $fee_item['first_weight'];
                $row['office_calculated_fee'] = $fee_item['first_fee'] +  $out_weight * $fee_item['continued_fee'];
            } elseif ($row['package_type'] == 2) {
                $weight = $this->get_weight($row['weight'], $row);
                if($row['shipping_id']==115 && ($row['facility_id'] == '22143846' || $row['facility_id'] == '24196974' || $row['facility_id'] == '22143847' || $row['facility_id'] == '19568549')){//中通专用计算方式
                    if($row['end_province'] == 10 || $row['end_province'] == 11 ||$row['end_province'] == 12 ||$row['end_province'] == 13){
                        $row['office_shipping_fee'] = ($fee_item['first_fee'] + $fee_item['continued_fee'] * ($weight-1)) ;
                        $row['office_calculated_fee'] = ($fee_item['first_fee'] + ($out_weight-1) * $fee_item['continued_fee']) ;
                    }else{
                        if($fee_item['transit_fee']>$fee_item['lowest_transit_fee']){
                            $row['office_shipping_fee']  = $fee_item['tracking_fee']+$fee_item['operation_fee']+$weight*($fee_item['transit_fee']+$fee_item['weighing_fee']);
                            $row['office_calculated_fee'] = $fee_item['tracking_fee']+$fee_item['operation_fee']+$out_weight*($fee_item['transit_fee']+$fee_item['weighing_fee']);
                        }else{
                            $row['office_shipping_fee']  = $fee_item['tracking_fee']+$fee_item['operation_fee']+$fee_item['lowest_transit_fee']+$weight*$fee_item['weighing_fee'];
                            $row['office_calculated_fee'] = $fee_item['tracking_fee']+$fee_item['operation_fee']+$fee_item['lowest_transit_fee']+$out_weight*$fee_item['weighing_fee'];
                        }
                    }   
                }else{
                    $row['office_shipping_fee'] = $fee_item['first_fee'] +  $weight * $fee_item['continued_fee'];
                    $row['office_calculated_fee'] = $fee_item['first_fee'] +  $out_weight * $fee_item['continued_fee'];
                }
            }
            $row['office_shipping_fee'] = $this->get_real_value($row['office_shipping_fee']);
            $row['office_calculated_fee'] = $this->get_real_value($row['office_calculated_fee']);
            $row['fee_diff'] = $row['excel_row']['final_fee'] - $row['office_shipping_fee'];
            if ( round($row['excel_row']['final_fee'],2) > round($row['office_shipping_fee'],2) && ($row['excel_row']['weight'] > $row['weight'])) {
                $this->error_weight[$row['tracking_number']] = $row;
            } elseif ($row['fee_diff'] > 0) {
                $row['fee_note'] = '多收（办公件）'.$row['note'];
                $this->error_fee_list[$row['tracking_number']] = $row;
            } else {
                $this->order_list[$row['tracking_number']] = $row;
            }
        } else {
            $this->missing_carriage_list[$row['tracking_number']] = $row;
        }
        
    }
    /**
     * 计算总重 中通比较奇葩 by 邪恶的大鲵
     * @param $weight       excel账单中的重量
     * @param $first_weight 系统中记录的首重
     * @return 快递单的总重
     * 继续改：by ytchen 包裹重量小于1的按照1处理，其余原数据不变！！
     */
    protected function get_weight($weight, $first_weight=0) {
		return max(1.0,$weight);
    }
}