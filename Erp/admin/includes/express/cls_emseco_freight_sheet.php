<?php
require_once ROOT_PATH . 'admin/includes/express/cls_freight_sheet.php';

class EmsEcoFreightSheet extends FreightSheet{
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
     * @param $weight
     * @param $order 订单信息
     */
    protected function get_weight($weight, $order) {
        $weight = parent::get_weight ( $weight, $order ['first_weight'] );
        return $weight;
    }
   
    
}