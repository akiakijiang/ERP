<?php
require_once ROOT_PATH . 'admin/includes/express/cls_freight_sheet.php';

class XiaoBaoFreightSheet extends FreightSheet {

    /**
     * 计算快递费
     * @param $order
     * 邮政小包  重量单位为 kg   0.0-1.3kg按1.0kg计算，1.31kg-2.3kg按2.0kg计算，2.31kg-3.3kg按3.0kg计算。首续重均为1kg
     * 
     */
    public function calculate_fee($order) {
        if ($order['first_fee'] == -1 &&  $order['continued_fee'] == -1) {
            return false;
        }

        //快递公司重量
        $weight = $this->get_weight($order['excel_row']['weight'], $order);
        $calculated_fee = ($order['first_fee'] + $order['continued_fee'] * $weight) ;
        $calculated_fee = $this->get_real_value($calculated_fee);
      
        //仓库重量快递费
        $shipping_weight = $this->get_shipping_weight($order) / 1000;
        $shipping_weight = $this->get_weight($shipping_weight, $order);
        $shipping_fee = $this->get_real_value( $shipping_fee);
        $shipping_fee = ($order['first_fee'] + $shipping_weight * $order['continued_fee']) ;
        $order['calculated_fee'] = $calculated_fee;
        $order['shipping_fee'] = $shipping_fee;
        return $order;
    }
    /**
     * @param $weight重量
     * @param $order订单信息
     * @return $weight
     */
    protected function get_weight($weight, $order) {
    	if($weight<=1.3){
    		$weight = 1;
    	}
    	elseif($weight<=2.3){
    		$weight= 2;
    	}
    	else{
    		$weight= 3;
    	}
        $weight = max(0, ($weight - $order['first_weight'])/ $order['first_weight']);
    	return $weight;
    }
    

}