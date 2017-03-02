<?php
require_once ROOT_PATH . 'admin/includes/freight/cls_freight_sheet.php';
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
        if($order['first_weight'] == 0.5 ){
           //中通快递首重为0.5kg时，续重为1kg
           $weight = max(0, ceil(($weight - $order['first_weight'])/ 1.0));
        }else{
            $weight = parent::get_weight($weight, $order['first_weight']);
        }
    	return $weight;
    }
    

}