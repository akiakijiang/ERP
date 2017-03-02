<?php
require_once ROOT_PATH . 'admin/includes/freight/cls_freight_sheet.php';

class ShenTongFreightSheet extends FreightSheet{
   
    public function calculate_fee($order) {
        if ($order['first_fee'] == -1 &&  $order['continued_fee'] == -1) {
            return false;
        }

        //快递公司重量快递费
        $weight = $this->get_weight($order['excel_row']['weight'], $order);
        $calculated_fee = $order['first_fee'] + $weight * $order['continued_fee'];
        $calculated_fee = $this->get_real_value($calculated_fee);
        //仓库重量快递费
        $shipping_weight = $this->get_shipping_weight($order) / 1000;
        $shipping_weight = $this->get_weight($shipping_weight, $order);
        $shipping_fee = $order['first_fee'] + $shipping_weight * $order['continued_fee'];
        $shipping_fee = $this->get_real_value( $shipping_fee);      
        $order['calculated_fee'] = $calculated_fee;
        $order['shipping_fee'] = $shipping_fee;
        return $order;
    }

    
    /**
     * 重量
     *
     * @param
     *           $weight
     * @param
     *           $first_weight
     */
    protected function get_weight($weight, $order) {
    	$facility_id = facility_convert ( $order ['facility_id'] );
    	if(19568549==$facility_id||19568548==$facility_id){
    		
    		$weight = max (0,ceil($weight * 2)/2 - $order['first_weight']);
    		
    	}
    	else{
    		$weight = parent::get_weight($weight, $order['first_weight']);
    	}
    	
    	return $weight;
    }
}
