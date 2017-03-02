<?php
require_once ROOT_PATH . 'admin/includes/freight/cls_freight_sheet.php';

class InterYouzhengFreightSheet extends inter_FreightSheet {
    // 设置excel模板
	const JULY = 1343145600;
    protected $tpls = array (
        'tracking_number' =>'条码',
        'weight' => '重量（克）',
        'final_fee' => '资费',
        'address' => '国家',
        
    );
    /**
     * 计算快递费
     * 
     * 
     * 
     * @param $order
     */
    public function calculate_fee($order) {
    	global $db;
      //仓库重量快递费
        $shipping_weight = $order['shipping_leqee_weight'] ;//系统中为g;7月25号之后分区和运算规则改变，运算时需要的计算单位是kg
        $shipping_weight = $this->get_weight($shipping_weight );//向上取整
        $order['temp_excel_weight'] = $order['excel_row']['weight'];//这里的单位是g
        $order['temp_excel_weight'] = $this->get_weight($order['temp_excel_weight']);
        
      //查找送达地的分区，范围由小到大来寻找,找到与订单发货时间匹配的分区信息
       $shipping_time = strtotime($order['shipping_time']);
       $order = parent::get_Partition($order);
	
		
		
		$fee = 0;
       
		if(!empty($order['fenqu_id'])&&$order['fenqu_id'] != null){
			if($shipping_time < self::JULY ){
				$order['shipping_weight'] = $shipping_weight;
				$sql = "select date, first_weight, first_fee, continue_weight, continue_fee
						from ecs_inter_carriage
						where region_id = '{$order['region_id']}'
						ORDER BY date DESC
				";
				$fee_list = $db->getAll($sql);
				if(empty($fee_list)){
					$sql = "select date, first_weight, first_fee, continue_weight, continue_fee
							from ecs_inter_carriage
							where region_id = '{$order['fenqu_id']}'
							ORDER BY date DESC
					";
					$fee_list = $db->getAll($sql);
				}
				foreach ($fee_list as $fee){
					$fee['first_weight'] = $fee['first_weight']* 1000;//系统中首续重的单位为kg
					$fee['continue_weight'] = $fee['continue_weight']* 1000;
					if($shipping_time >= $fee['date'])
					{
						$order['fee'] = $fee;
						break;
					}
				}
				if($order['fee']['continue_weight']){
					$order ['temp_c_weight'] = max(0,ceil(($order['temp_excel_weight']-$order['fee']['first_weight'])/$order['fee']['continue_weight']));
					$order ['temp_shipping_weight'] = max(0,ceil(($order['shipping_weight']-$order['fee']['first_weight'])/$order['fee']['continue_weight']));
					$temp_fee = $order['fee']['first_fee'] + max(0,ceil(($order['shipping_weight']-$order['fee']['first_weight'])/$order['fee']['continue_weight'])) * $order['fee']['continue_fee'];
						
					$calculated_fee = $temp_fee *(1+$order['res']['fuel']) * $order['res']['price_discounts'] + $order['res']['registration_fee'] *$order['res']['registration_fee_discounts'] + $order['res']['declaration_charges'];
					$calculated_fee = $this->get_real_value($calculated_fee);
						
					$order['calculated_fee'] = $calculated_fee;
				}
			}
			else{
				$order['temp_excel_weight'] = $order['temp_excel_weight']/1000; //快递公司称重单位也统一转为kg
				$order['shipping_weight'] = $shipping_weight/1000;//7月25号之后计算方法为：运费 = 重量*单位重量运费（单位为kg）
				$sql = "select date, first_weight, first_fee, continue_weight, continue_fee
						from ecs_inter_carriage
						where region_id = '{$order['fenqu_id']}'
						ORDER BY date DESC
				";
				$fee_list = $db->getAll($sql);
				foreach ($fee_list as $fee){
					if($shipping_time >= $fee['date'])
					{
						$order['fee'] = $fee;
						break;
					}
				}
			}
			
			$sql = "select date, fuel, registration_fee, price_discounts, registration_fee_discounts, declaration_charges
					from ecshop.ecs_inter_discount
					where fenqu_id = '{$order['fenqu_id']}'
					ORDER BY date DESC
			";
			
			$res_list = $db ->getAll($sql);
			
			foreach ($res_list as $res){
				if($shipping_time >= $res['date']){
					$order['res'] = $res;
					break;
				}
			}
			if($order['fee']['continue_weight']){
				$order ['temp_c_weight'] = max(0,($order['temp_excel_weight']-$order['fee']['first_weight'])/$order['fee']['continue_weight']);
				$order ['temp_shipping_weight'] = max(0,($order['shipping_weight']-$order['fee']['first_weight'])/$order['fee']['continue_weight']);
				$temp_fee = $order['fee']['first_fee'] + max(0,($order['shipping_weight']-$order['fee']['first_weight'])/$order['fee']['continue_weight']) * $order['fee']['continue_fee'];
					
				$calculated_fee = $temp_fee *(1+$order['res']['fuel']) * $order['res']['price_discounts'] + $order['res']['registration_fee'] *$order['res']['registration_fee_discounts'] + $order['res']['declaration_charges'];
					
				$order['calculated_fee'] = $calculated_fee;
			}
		}
		
        return $order;
    }
    
    
   
    /**
     * 费用检查
     */
    protected function check_fee() {
        global $db;
        foreach ( $this->order_list as $key=>$order ) {
            $order = $this->calculate_fee ( $order );
            if($order ['temp_c_weight'] > $order ['temp_shipping_weight']){// 如果快递公司重量大于仓库重量算是异常重量
            	if($order['shipping_weight'] == 0){
            		$order['calculated_fee'] = 0;
            	}
            	$this->error_weight [$order ['tracking_number']] = $order;
            	unset($this->order_list[$key]);
            	
            }
            
            elseif ( empty($order['fenqu_id'])||$order['region_name_chs']!=$order['excel_row']['address']||empty($order['fee'])||empty($order['res'])) {
                
                $this->missing_carriage_list[$order['tracking_number']] = $order;
                unset($this->order_list[$key]);
            } else {
                
                $order ['fee_diff'] = $order ['excel_row'] ['final_fee'] - $order ['calculated_fee'];
                
                
                
                // 判断运费已经设置 且 费用多收
                if ($order ['fee_diff'] > 0 && $order ['calculated_fee'] != false) {
                    $order ['fee_note'] = '多收';
                 	$this->error_fee_list [$order ['tracking_number']] = $order;
                    unset($this->order_list[$key]);
                    
                }
                else {
                	$this->order_list [$order ['tracking_number']] = $order;
                }
            }
        
        }
    }
    
    
    /**
     * 重量
     * 
     * @param
     *           $weight
     * @param
     *           将小数向上取整
     */
    protected function get_weight($weight, $first_weight) {
        
        $weight =  ceil ( $weight ) ;
        return $weight;
    }
    
    public  function export_error_excel($file_name) {
    	$file_name = '海外邮政小包对账结果';
    	parent::export_error_excel($file_name);
    }
    
    
}