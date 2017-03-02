<?php
require_once ROOT_PATH . 'admin/includes/express/cls_freight_sheet.php';
class YunDaFreightSheet extends FreightSheet {

    /**
     * 计算快递费
     * @param $order
     * 韵达  重量单位为 kg   其他省份 首重为 1kg  续重均为1kg
     * 不足1KG 按照1kg计算  要去除上海含税金额 目前韵达快递只有上海仓库使用
     * 韵达系统维护价格里面包含税和快递面单费，对账的时候快递公司给的价格里面是不含税和面单费的我们要先除掉税率再减面单费，
     * 每笔面单费为2.6元
     */
    public function calculate_fee($order) {
        if ($order['first_fee'] == -1 &&  $order['continued_fee'] == -1) {
            return false;
        }

        //快递公司重量
        $weight = $this->get_weight($order['excel_row']['weight'], $order);
        $calculated_fee = ($order['first_fee'] + $order['continued_fee'] * $weight);
        $calculated_fee = $this->get_real_value($calculated_fee);
      
        //仓库重量快递费
        $shipping_weight = $this->get_shipping_weight($order) / 1000;
        $shipping_weight = $this->get_weight($shipping_weight, $order);
        $shipping_fee = $this->get_real_value( $shipping_fee);
        $shipping_fee = ($order['first_fee'] + $shipping_weight * $order['continued_fee']);
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
        $weight = max ( 0, (ceil($weight*2)/2 -  $order['first_weight'] ));
    	return $weight;
    }
}