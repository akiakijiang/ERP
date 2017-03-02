<?php
require_once ROOT_PATH . 'admin/includes/freight/cls_freight_sheet.php';

class UPSFreightSheet extends inter_FreightSheet {
    // 设置excel模板
    protected $tpls = array (
        'tracking_number' =>'运单号（长）',
        'weight' => '计费重量',
        'final_fee' => '总运费',
        'address' => '收件国家',
        'type' => '货物类型',
    	'date' => '出口日期',
    );
    /**
     * 计算快递费
     * UPS 重量不足0.5kg按照0.5kg
     * 
     * 
     * @param $order
     */
    public function calculate_fee($order) {
    	
    	global $db;
      //仓库重量快递费
        $shipping_weight = $order['shipping_leqee_weight'] / 1000;
        $shipping_weight = $this->get_weight($shipping_weight);
        $order['shipping_weight'] = $shipping_weight;
        $shipping_time = strtotime($order['shipping_time']);
      //查找送达地的分区，范围由小到大来寻找
        $order = parent::get_Partition($order);
    	
		
		
		if($order['excel_row']['type']=='NON-DOC'){
			$type = 'P';
			$order['type']='P';
		}
		else{
			$type = 'D';
			$order['type']= 'D';
		}
		$fee = 0;
		if(!empty($order['fenqu_id'])&&$order['fenqu_id'] != null){
			
			$sql = "select date, fee from ecs_inter_fee
					where weight = '{$shipping_weight}'
					and fenqu_id = '{$order['fenqu_id']}'
					and type = '{$type}'
					ORDER BY date DESC";
			$fee_list = $db->getAll($sql);
			foreach($fee_list as $fee){
				if($shipping_time >= $fee['date']){
					$order['fee'] = $fee['fee'];
					break;
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
					$order['res'] = $res ;
					break;
				}
			}
			
		}
        $calculated_fee = $order['fee'] * (1+$order['res']['fuel']) * $order['res']['price_discounts'] + $order['res']['registration_fee'] *$order['res']['registration_fee_discounts'] + $order['res']['declaration_charges'];
        $calculated_fee = trim($this->get_real_value($calculated_fee,2));
        
        $order['calculated_fee'] = $calculated_fee;
        
        /* if('BE' == $order['excel_row']['address']){
        	$order['excel_row']['address'] = '比利时';
        }
        elseif('US' == $order['excel_row']['address']){
        	$order['excel_row']['address'] = '美国';
        }
        else{
        	$order['excel_row']['address'] = '英国';
        } */
        return $order;
    }
    
    
   
    /**
     * 费用检查
     */
    protected function check_fee() {
    	
        global $db;
        foreach ( $this->order_list as $key=>$order ) {
            $order = $this->calculate_fee ( $order );
            if($order ['excel_row']['weight'] > $order ['shipping_weight'] ){// 如果快递公司重量大于仓库重量算是异常重量
            	if($order['shipping_weight']== 0){
            		$order['calculated_fee'] = 0;
            	}
            	$this->error_weight [$order ['tracking_number']] = $order;
            	unset($this->order_list[$key]);
            }         
            elseif (empty($order['fenqu_id'])||$order['region_name_chs']!=$order['excel_row']['address']||empty($order['res'])||empty($order['fee'])) {               
                $this->missing_carriage_list[$order['tracking_number']] = $order;
                unset($this->order_list[$key]);
            } else {
            	$order ['excel_row'] ['final_fee'] = trim($this->get_real_value($order ['excel_row'] ['final_fee'],2));
                $order ['fee_diff'] = $order ['excel_row'] ['final_fee'] - $order ['calculated_fee'];
                $order ['fee_diff'] = trim($this->get_real_value($order ['fee_diff'],2));
             
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
     *           $first_weight
     */
    protected function get_weight($weight, $first_weight) {
        
        $weight =  ceil ( $weight * 2 ) / 2 ;
        return $weight;
    }
    
    public  function export_error_excel($file_name) {
    	$file_name = 'UPS对账结果';
    	parent::export_error_excel($file_name);
    }
    
    
}