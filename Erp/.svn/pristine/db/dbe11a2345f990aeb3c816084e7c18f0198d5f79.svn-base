<?php
require_once ROOT_PATH . 'admin/includes/express/cls_freight_sheet.php';

class ZhaiJiSongFreightSheet extends FreightSheet {

    /**
     * 计算快递费
     * 宅急送 重量单位为1kg 首重1kg 续重1kg
     * 不足0.5kg按照0.5kg
     * 
     * @param $order
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
     * 费用检查
     */
    protected function check_fee() {
        global $db;
        foreach ( $this->order_list as $key=>$order ) {
            $order = $this->calculate_fee ( $order );
            $facility = facility_convert ( $order ['facility_id'] );
            // 现在只有上海仓使用称重系统，如果快递公司重量算的运费大于仓库重量算的运费算是异常重量
            if (( double ) $order ['calculated_fee'] > ( double ) $order ['shipping_fee'] &&$facility!='12768420') {
            	if(($order['excel_row']['final_fee'] - $order['shipping_fee'])<=0){
            		continue;
            	}
            	else{
            		$this->error_weight[$order['tracking_number']] = $order;
            		unset($this->order_list[$key]);
            	}
            } else {
            	if ($order['excel_row_note'] != "办公件") {
                	// 判断是否为东莞宅急送返货
                	$order ['fee_diff'] = $order ['excel_row'] ['final_fee'] - $order ['calculated_fee'];
                	// 东莞仓库
                	if ($facility_id == '19568548' || $facility_id == '19568549') {
                    	if ($order ['excel_row'] ['remark'] == '返货') {
                        	if ($order ['fee_diff'] == $order ['calculated_fee']) {
                            	$order ['fee_diff'] = 0;
                            	$order ['fee_note'] = '发货及返货费用';
                            	if ($facility_id == '19568548') {
                                	// 东莞仓同期账单中返货运单（快递费为一倍）
                                	$order ['calculated_fee'] = $order ['calculated_fee'] * 2;
                            	} elseif ($facility_id == '19568549') {
                                	// 上海仓同期账单中返货运单（快递费为0.8倍）
                                	$order ['calculated_fee'] = $order ['calculated_fee'] * 1.8;
                            	}
                            	$sql = "SELECT shipment_id, shipping_category FROM romeo.shipment
                            	WHERE tracking_number = '{$order['tracking_number']}'";
                        	} elseif ($order ['fee_diff'] == 0) {
                            	// 非同期账单中返货运单
                            	$sql = "SELECT shipment_id, shipping_category FROM romeo.shipment
                                WHERE tracking_number = '{$order['tracking_number']}' AND shipping_category = 'SHIPPING_RETURN'";
                        	}
                    	} else {
                        	// 非返货运单
                        	$sql = "SELECT shipment_id, shipping_category FROM romeo.shipment
                            WHERE tracking_number = '{$order['tracking_number']}' AND shipping_category != 'SHIPPING_RETURN'";
                    	}
                    	$shipment = $db->getAll ( $sql );
                    	if (count ( $shipment ) == 1) {
                        	$this->order_list [$order ['tracking_number']] ['shipment_id'] = $shipment [0] ['shipment_id'];
                        	$this->order_list [$order ['tracking_number']] ['shipping_category'] = $shipment [0] ['shipping_category'];
                    	} else {
                        	foreach ( $shipment as $k => $v ) {
                            	$this->order_list [$order ['tracking_number']] ['shipment_list'] [$k] ['shipment_id'] = $v ['shipment_id'];
                            	$this->order_list [$order ['tracking_number']] ['shipment_list'] [$k] ['shipping_category'] = $v ['shipping_category'];
                            	// 同期返款账单中每条运单号的快递费为账单中的一半
                            	$this->order_list [$order ['tracking_number']] ['shipment_list'] [$k] ['shipping_cost'] = $order ['excel_row'] ['final_fee'] / 2;
                        	}
                    	}
                	}
                	$this->order_list [$order ['tracking_number']] ['calculated_fee'] = $order ['calculated_fee'];
                	// 判断运费已经设置 且 费用多收
                	if ($order ['fee_diff'] > 0 && $order ['calculated_fee'] != false) {
                    	$order ['fee_note'] = '多收';
                     	$this->error_fee_list [$order ['tracking_number']] = $order;
                    	unset($this->order_list[$key]);
                	}
                	else{
                		$this->order_list[$key] = $order;     			
                	}
            	}
            }
        }
    }
    
    /**
     * 更新快递费
     */
    public function update_fee() {
        if ($this->check_data ()) {
            foreach ( $this->order_list as $order ) {
                if (! empty ( $order ['shipment_id'] )) {
                	if ($order['excel_row_note'] == '办公件') {
                		//$office_shipment_list
                		$office_shipment = new stdClass();
                		$office_shipment->shipmentId = $order['shipment_id'];
                		$office_shipment->lastActionUser = $_SESSION['admin_name'];
                		$office_shipment->outWeight = $order['excel_row']['weight'];
                		$office_shipment->shippingCost = $order['excel_row']['final_fee'];
                		$this->office_shipment_list[] = $office_shipment;
                	} else {
                    	$facility_id = facility_convert ( $order ['facility_id'] );
                    	if ($order ['shipment_type_id'] == 11 && $order ['shipment_list'] && $facility_id == '19568548') {
                        	foreach ( $order ['shipment_list'] as $k => $v ) {
                            	$weight = $order ['excel_row'] ['weight'] * 1000;
                            	$shipment = new stdClass ();
                            	$shipment->shipmentId = $v ['shipment_id'];
                            	$shipment->shippingOutWeight = $weight;
                            	$shipment->shippingLeqeeWeight = - 1;
                            	$shipment->shippingServiceFee = - 1;
                            	$shipment->shippingInsuredCost = - 1;
                            	$shipment->shippingCost = $v ['shipping_cost'];
                            	$this->shipment_list [] = $shipment;
                        	}
                    	} else {
                        	$weight = $order ['excel_row'] ['weight'] * 1000;
                        	$shipment = new stdClass ();
                        	$shipment->shipmentId = $order ['shipment_id'];
                        	$shipment->shippingLeqeeWeight = - 1;
                        	$shipment->shippingServiceFee = - 1;
                        	$shipment->shippingInsuredCost = - 1;
                        	if(!empty($order['excel_row']['weight'])){
                        		$shipment->shippingOutWeight = $order['excel_row']['weight'] * 1000;
                        	}
                        	else{
                        		$shipment->shippingOutWeight = -1;
                        	}
                        	if(!empty($order['excel_row']['final_fee'])){
                        		$shipment->shippingCost = $order['excel_row']['final_fee'];
                        	}
                        	else{
                        		$shipment->shippingCost = -1;
                        	}
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
                        	$this->shipment_list [] = $shipment;
                    	}
                	}
                } else {
                    continue;
                }
            }
            if (!empty($this->office_shipment_list)) {
            	$soap_client_office = soap_get_client('OfficeShipmentService');
            	$result = $soap_client_office->updateOfficeShipmentByImport(array('officeShipmentList' => $this->office_shipment_list));
            }
            if (! empty ( $this->shipment_list )) {
                parent::update_fee ();
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
    protected function get_weight($weight, $order) {
        $facility_id = facility_convert ( $order ['facility_id'] );
        if(79256821==$facility_id){
	        $weight = max($weight ,$order['first_weight']) -  $order['first_weight'];
    	}else 
        	$weight = max ( 0, (ceil($weight*2)/2 -  $order['first_weight'] ));
        return $weight;
    }
    
    /**
     * 返回运单号导入，不同快递公司匹配规则不同
     */
    public function import_return_tracking_number() {
        global $db;
        $i = $j = 0;
        $list = '';
        foreach ( $this->content as $order ) {
            // 先对返回单号处理按返货订单查找order_id
            if ($order ['tracking_number']) {
                if (strrpos ( $order ['tracking_number'], '-' ) !== false) {
                    list ( , $order ['info'] ['tracking_number'] ) = explode ( '-', $order ['tracking_number'] );
                    $order ['info'] = $this->get_order_by_tracking_number ( $order ['info'] ['tracking_number'] );
                    $sql = "SELECT shipment_id FROM romeo.shipment WHERE tracking_number = '{$order['tracking_number']}' ";
                } else {
                    $order ['info'] = $this->get_order_by_tracking_number ( $order ['tracking_number'] );
                    $sql = "SELECT shipment_id FROM romeo.shipment WHERE tracking_number = '{$order['tracking_number']}' 
                        AND shipping_category = 'SHIPPING_RETURN' ";
                }
                if (! $order ['info']) {
                    $list .= $order ['tracking_number'] . ',';
                    if (($i % 5) == 0) {
                        $list .= '<br/>';
                    }
                    $i ++;
                }
                $result = $db->getOne ( $sql );
                if (! $result) {
                    $this->content [$order ['tracking_number']] ['order_info'] = $order ['info'];
                } else {
                    $exist_tracking_number .= $order ['tracking_number'] . ',';
                    if (($j % 5) == 0) {
                        $exist_tracking_number .= '<br/>';
                    }
                    $j ++;
                }
                unset ( $order ['info'] );
            }
        }
        
        parent::import_return_tracking_number ();
        return $list . "<br/>以下运单号已经录入到ERP系统，请勿重复导入：" . $exist_tracking_number;
    }
}