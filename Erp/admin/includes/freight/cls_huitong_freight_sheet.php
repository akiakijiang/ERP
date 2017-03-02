<?php
require_once ROOT_PATH . 'admin/includes/freight/cls_freight_sheet.php';

class HuiTongFreightSheet extends FreightSheet{
   
    //汇通快递费  首重1公斤 续重1公斤 不足一公斤按照一公斤计算
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
     */
    protected function get_weight($weight, $order) {
        $weight = max ( 0, ceil($weight) - $order['first_weight'] );
        return $weight;
    }
    /**
     * 更新快递费
     */
  
}