<?php
require_once ROOT_PATH . 'admin/includes/freight/cls_freight_sheet.php';

class YuanTongFreightSheet extends FreightSheet {

    /**
     * 计算快递费
     * @param $order
     * 东莞圆通 1.1-1.5kg按1.5kg算，1.5-2.0按2kg算
     * 其他仓库圆通  重量单位为 kg  首重为 1kg  续重均为1kg
     *
     */
    public function calculate_fee($order) {
        if ($order['first_fee'] == -1 &&  $order['continued_fee'] == -1) {
            return false;
        }

        //快递公司重量快递费
        $weight = $this->get_weight($order['excel_row']['weight'], $order['first_weight']);
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
    	if(19568548==$facility_id){
    		
    		$weight = max (0,ceil($weight * 2)/2 - $order['first_weight']);
    		
    	}
    	else{
    		$weight = parent::get_weight($weight, $order['first_weight']);
    	}
    	
    	return $weight;
    }

    
  
}