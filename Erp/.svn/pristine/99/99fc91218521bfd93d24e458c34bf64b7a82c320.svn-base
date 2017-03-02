<?php

class FactorageSheet extends AbstractSheet {
    /**
     * 手续费计算
     */
    protected function calculate_fee($order) {

    }
    /**
     * 更新手续递费
     */
    public function update_fee() {
        if ($this->check_data()) {
            foreach ($this->order_list as $order) {
                if (!empty($order['shipment_id'])) {
                    $shipment = new stdClass();
                    $shipment->shipmentId = $order['shipment_id'];
                    $shipment->shippingCost = -1;
                    $shipment->shippingServiceFee = $order['excel_row']['final_fee'];
                    $shipment->shippingOutWeight = -1;
                    $shipment->shippingLeqeeWeight = -1;
                    $shipment->shippingInsuredCost = -1;
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
    /**
     * 费用检查
     */
    protected function check_fee() {
        foreach ($this->order_list as $order) {
            $order['calculated_fee'] = $this->calculate_fee($order);
            $this->order_list[$order['tracking_number']]['calculated_fee'] = $order['calculated_fee'];
            $order['fee_diff'] = $order['excel_row']['final_fee'] - $order['calculated_fee'];
            //判断费用多收
            if ($order['fee_diff'] > 0) {
                $order['fee_note'] = '多收';
                $this->error_fee_list[$order['tracking_number']] = $order;
            }
        }
    }

    /**
     * 将异常数据输出
     * @param string $file_name 导出excel文件名
     */
    public function export_error_excel($file_name) {
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($file_name);
        $sheet_no = 1;
        if (!empty($this->error_fee_list)) {
            // var_dump($this->error_fee_list);die();
            $sheet_no ++;
            $sheet = $excel->getActiveSheet();
            $sheet->setTitle('手续费明细');
            $sheet->setCellValue('A1', "日期");
            $sheet->setCellValue('B1', "运单号");
            $sheet->setCellValue('C1', "订单号");
            $sheet->setCellValue('D1', "淘宝订单号");
            $sheet->setCellValue('E1', "收款方式");
            $sheet->setCellValue('F1', "组织");
            $sheet->setCellValue('G1', "姓名");
            $sheet->setCellValue('H1', "省");
            $sheet->setCellValue('I1', "市及地区");
            $sheet->setCellValue('J1', "快递公司手续费");
            $sheet->setCellValue('K1', "ERP计算手续费");
            $sheet->setCellValue('L1', "差值");
            $sheet->setCellValue('M1', "备注");
            //var_dump($this->error_fee_list);die();
            $i = 2;
            foreach ($this->error_fee_list as $order) {
                $sheet->setCellValueExplicit("A{$i}", $order['excel_row']['date'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("B{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue("E{$i}", $order['pay_name']);
                $sheet->setCellValue("F{$i}", $order['name']);
                $sheet->setCellValue("G{$i}", $order['consignee']);
                $sheet->setCellValue("H{$i}", $order['province_name']);
                $sheet->setCellValue("I{$i}", $order['city_name'].$order['district_name']);
                $sheet->setCellValue("J{$i}", $order['excel_row']['final_fee']);
                $sheet->setCellValue("K{$i}", $order['calculated_fee']);
                $sheet->setCellValue("L{$i}", $order['fee_diff']);
                $sheet->setCellValue("M{$i}", $order['fee_note']);
                $i++;
            }
        }
        if (!empty($this->missing_order_list)) {
            //var_dump($this->missing_order_list);die();
            $no = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
            if ($sheet_no == 1 ) {
                $name = '$sheet';
                $name = $excel->getActiveSheet();
            } else {
                $name = '$sheet'.$sheet_no;
                $name = $excel->createSheet();
            }
            $sheet_no++;
            $name->setTitle('未找到对应订单');
            $i = 1;
            $j = 0;
            foreach ($this->missing_order_list as $order) {
                foreach ($order as $key => $col) {
                    foreach ($this->tpls as $k => $var_name) {
                        if ($k == $key) {
                            $name->setCellValue("{$no[$j]}{$i}", $var_name);
                            $j++;
                        }
                    }
                }
                break;
            }
            $j = 0;
            foreach ($this->missing_order_list as $order) {
                $i++;
                $j = 0;
                foreach ($order as $key => $col){
                    if ($key == 'tracking_number' || $key == 'date') {
                        $name->setCellValueExplicit("{$no[$j]}{$i}", $col, PHPExcel_Cell_DataType::TYPE_STRING);
                    } else {
                        $name->setCellValue("{$no[$j]}{$i}", $col);
                    }
                    $j++;
                }
            }
        }
        if (!empty($this->order_list)) {
            if ($sheet_no == 1 ) {
                $name = '$sheet';
                $name = $excel->getActiveSheet();
            } else {
                $name = '$sheet'.$sheet_no;
                $name = $excel->createSheet();
            }
            $sheet_no++;
            $name->setTitle('运单-订单列表');
            $name->setCellValue('A1', "日期");
            $name->setCellValue('B1', "运单号");
            $name->setCellValue('C1', "订单号");
            $name->setCellValue('D1', "淘宝订单号");
            $name->setCellValue('E1', "收款方式");
            $name->setCellValue('F1', "组织");
            //手续费中重量不进行计算
            //$name->setCellValue('G1', "重量");
            $name->setCellValue('G1', "金额");
            $name->setCellValue('H1', "省");
            $name->setCellValue('I1', "市/区");
            $i = 2;
            foreach ($this->order_list as $order) {
                $name->setCellValueExplicit("A{$i}", $order['excel_row']['date'], PHPExcel_Cell_DataType::TYPE_STRING);
                $name->setCellValueExplicit("B{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
                $name->setCellValueExplicit("C{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
                $name->setCellValueExplicit("D{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
                $name->setCellValue("E{$i}", $order['pay_name']);
                $name->setCellValue("F{$i}", $order['name']);
                //$name->setCellValue("G{$i}", $order['excel_row']['weight']);
                $name->setCellValue("G{$i}", $order['excel_row']['final_fee']);
                $name->setCellValue("H{$i}", $order['province_name']);
                $name->setCellValue("I{$i}", $order['city_name'].$order['district_name']);
                $i++;
            }
        }
        if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$file_name.'.xls"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $output->save('php://output');
            exit;
        }
    }
}