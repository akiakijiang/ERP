<?php
require_once ROOT_PATH . 'admin/includes/express/cls_freight_sheet.php';

class HuiTongFreightSheet extends FreightSheet{
   
    /*
     * 上海仓+汇通快递费:
     * 1、       系统预估重量在0-首重的订单单独可以设置为数值。
     * 2、       超过首重的订单按照如下规则进行计算：
     * 	2.1：包裹总金额=中转费(transit_fee)*包裹重量+操作费(operation_fee)+面单费(tracking_fee)
     * 
     * 其他仓：续重向上求整
     */  
    
    public function calculate_fee($order) {
        if ($order['first_fee'] == -1 &&  $order['continued_fee'] == -1) {
            return false;
        }
        
		if($order['facility_id']== '22143846' || $order['facility_id'] == '24196974' || $order['facility_id'] == '22143847' || $order['facility_id'] == '19568549'){
	        //快递公司重量快递费
			$row_weight = $order['excel_row']['weight'];
			if($row_weight <= $order['first_weight']){
				$calculated_fee = $order['first_fee'];
			}else{
				$calculated_fee = $order['transit_fee']*$row_weight+$order['operation_fee']+$order['tracking_fee'];
			}
			
	        //仓库重量快递费
	        $shipping_weight = $this->get_shipping_weight($order) / 1000;
			if($shipping_weight <= $order['first_weight']){
				$shipping_fee = $order['first_fee'];
			}else{
				$shipping_fee = $order['transit_fee']*$shipping_weight+$order['operation_fee']+$order['tracking_fee'];
			}
		}else{
			//快递公司重量快递费
	        $weight = $this->get_weight($order['excel_row']['weight'], $order['first_weight']);
	        $calculated_fee = $order['first_fee'] + $weight * $order['continued_fee'];
	
	        //仓库重量快递费
	        $shipping_weight = $this->get_shipping_weight($order) / 1000;
	        $shipping_weight = $this->get_weight($shipping_weight, $order);
	        $shipping_fee = $order['first_fee'] + $shipping_weight * $order['continued_fee'];
		}
		$calculated_fee = $this->get_real_value($calculated_fee);	
        $shipping_fee = $this->get_real_value( $shipping_fee);
        $order['calculated_fee'] = $calculated_fee;
        $order['shipping_fee'] = $shipping_fee;
        return $order;
    }
    /**
     * 重量
     */
    protected function get_weight($weight, $order) {
        $weight = max ( 0, ceil($weight) - $order['first_weight'] );
        return $weight;
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
                SELECT first_weight, first_fee, continued_fee,tracking_fee,operation_fee,transit_fee 
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
                $order['transit_fee'] = $region_list['transit_fee'];
            } else {
                $order['first_weight'] = -1;
                $order['first_fee'] = -1;
                $order['continued_fee'] = -1;
                $order['operation_fee'] = -1;
                $order['weighing_fee'] = -1;
                $order['transit_fee'] = -1;
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
            select first_weight, first_fee, continued_fee,tracking_fee,operation_fee,transit_fee 
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
                if($row['facility_id'] == '22143846' || $row['facility_id'] == '24196974' || $row['facility_id'] == '22143847' || $row['facility_id'] == '19568549'){
                 	$out_weight = $row['excel_row']['weight'];
                 	$weight = $row['weight'];
                 	if($out_weight <= $fee_item['first_weight']){
                 		$row['office_calculated_fee'] = $fee_item['first_fee'];
                 	}else{
                 		$row['office_calculated_fee'] = $fee_item['transit_fee']*$out_weight+$fee_item['operation_fee']+$fee_item['tracking_fee'];
                 	}
                 	if($weight <= $fee_item['first_weight']){
                 		$row['office_shipping_fee'] = $fee_item['first_fee'];
                 	}else{
                 		$row['office_shipping_fee'] = $fee_item['transit_fee']*$weight+$fee_item['operation_fee']+$fee_item['tracking_fee'];
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

}