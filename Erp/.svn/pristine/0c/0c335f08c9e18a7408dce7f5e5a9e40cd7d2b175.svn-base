<?php
require_once ROOT_PATH . 'admin/includes/express/cls_freight_sheet.php';

class ShunFengLuYunFreightSheet extends FreightSheet {
    //设置excel模板
    protected $tpls = array (
        'date' => '日期',
        'tracking_number' =>'运单号码',
        'weight' => "计费重量",
        'final_fee' => "应收费用",
        'fee_type' => '类型',
    	'note'=>'备注'
    );
    /**
     * 计算快递费
     * @param $order
     * 顺丰  重量单位为 kg 首重为 1kg  续重均为1kg 不足1.5KG 按照1.5KG
     *
     */
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
     * 返回续重
     * @param $weight
     * @param $order
     */
    protected function get_weight($weight, $order){
        $weight =  max (0, ceil($weight*2)/2 - $order['first_weight']);
        return $weight;
    }
   

}