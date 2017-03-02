<?php
require_once ROOT_PATH . 'admin/includes/express/cls_freight_sheet.php';

class EMSFreightSheet extends FreightSheet{

    /**
     * 计算快递分别计算快递公司重量的快递费和仓库重量的快递费
     * EMS   首重500g  续重500g
     * @param $order
     */
    public function calculate_fee($order) {
        if ($order['first_fee'] == -1 && $order['continued_fee'] == -1) {
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
       	//上海同城EMSCOD，首重1kg，续重为1kg不足1kg按1kg计算
        // 东莞EMS同城,珠三角地区快递公司的账单中重量单位为1kg 北京仓库首重1kg 续重1kg 其他账单中的重量单位为1g
        // 东莞EMS 江浙沪安徽 发E邮宝，首重1kg,续重1kg,不足1kg使用1kg(已转到E邮宝快递 现作废)
        // 东莞 249, 上海 10, 江苏 11, 浙江 12, 安徽 13
        // 珠江三角洲经济区，包括广州、深圳、珠海、佛山、江门、东莞、中山、惠州市和肇庆市，
        // 东莞仓EMSCOD江浙沪安徽发普通EMS,补寄发票发EMS首重0.5kg,续重0.5kg,不足0.5kg按0.5算
     	$weight = max ( 0, ceil ( ($weight - $order['first_weight'] ) / $order['first_weight'] ) );//这种算法包括了首续重都为1kg和首续重都为0.5kg的情况
        return $weight;
    }
    /**
     * 更新快递费
     */
    public function update_fee() {
        if ($this->check_data()) {
            foreach ($this->order_list as $order) {
                if (!empty($order['shipment_id'])) {
                    $facility_id = facility_convert($order['facility_id']);
                    //东莞EMS同城
                    if($order['city'] == 249 && $facility_id == '19568548') {
                        $weight = $order['excel_row']['weight'] * 1000;
                    } else {
                        $weight = $order['excel_row']['weight'];
                    }
                    $shipment = new stdClass();
                    $shipment->shipmentId = $order['shipment_id'];
                    $shipment->shippingOutWeight = $weight;
                    $shipment->shippingLeqeeWeight = -1;
                    $shipment->shippingServiceFee = -1;
                	if(!empty($order['excel_row']['final_fee'])){
                    	$shipment->shippingCost = $order['excel_row']['final_fee'];
                    }
                    else{
                    	$shipment->shippingCost = -1;
                    }
                    //是否更新保价费
                    if (isset($order['excel_row']['excel_insurance'])&&!empty($order['excel_row']['excel_insurance'])) {
                        $shipment->shippingInsuredCost = $order['excel_row']['excel_insurance'];
                    } else {
                        $shipment->shippingInsuredCost = -1;
                    }
                    if(isset($order['excel_row']['remark'])&&!empty($order['excel_row']['remark'])){
                    	$shipment->billNote = $order['excel_row']['remark'];
                    	$shipment->shippingNote = $order['excel_row']['remark'];
                    }
                    else{
                    	$shipment->billNote = '无';
                    	$shipment->shippingNote = '无';
                    	 
                    }
                    $this->shipment_list[] = $shipment ;
                } else {
                    continue;
                }
            }
            if (!empty($this->shipment_list)) {
                parent::update_fee();
            }
        }
    }
    
}
