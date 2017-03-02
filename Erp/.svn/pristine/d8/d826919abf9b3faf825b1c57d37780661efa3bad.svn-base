<?php
class inter_FreightSheet extends FreightSheet {
	/**
     * 查找订单信息
     */
    protected function get_order_by_tracking_number($tracking_number) {
    	$order = AbstractSheet::get_order_by_tracking_number($tracking_number);
    	return $order;
    }
    /**
     * 检查所有的运费设置是否有异常，或者地区是否有异常，将这些异常存入$this->missing_carriage_list中
     * 从cls_abstract_sheet.php中剪切出来
     */
    protected function check_freight_set() {
    }
    /**
     * 按地区范畴由小到大
     * 获取该区域对应的分区ID
     */
	public function get_Partition($order){
		global $db ;
		$shipping_time = strtotime($order['shipping_time']);
		if(3859 == $order['country']&& 24 == $order['carrier_id']){
			if(($order['zipcode'] >= 80000 && $order['zipcode'] <= 81699) || ($order['zipcode'] >= 83200 && $order['zipcode'] <= 83999)||($order['zipcode'] >= 84000 && $order['zipcode'] <= 84799)||($order['zipcode'] >= 85000 && $order['zipcode'] <= 86599)||($order['zipcode'] >= 89000 && $order['zipcode'] <= 89899)||($order['zipcode'] >= 90000 && $order['zipcode'] <= 96699)||($order['zipcode'] >= 97000 && $order['zipcode'] <= 97999)||($order['zipcode'] >= 98000 && $order['zipcode'] <= 99499))
			{
				$order['fenqu_id'] = 4407;
				$order['region_name_chs'] = '美国';
				$order['state'] = '西部';
			}
			else{
				$order['fenqu_id'] = 4408;
				$order['region_name_chs'] = '美国';
				$order['state'] = '东部';
			}
		}
		else{
			$order = $this ->get_fenqu($order, $order['district'], $order['carrier_id'], $shipping_time);
			if(0==$order['fenqu_id']){
				$order = $this ->get_fenqu($order, $order['city'], $order['carrier_id'], $shipping_time);
			}
			if(0==$order['fenqu_id']){
				$order = $this ->get_fenqu($order, $order['province'], $order['carrier_id'], $shipping_time);
			}
			if(0==$order['fenqu_id']){
				$order = $this ->get_fenqu($order, $order['country'], $order['carrier_id'], $shipping_time);
			}
			
		}
		
		return $order ;
	}
	/**
	 * 
	 * 按照已有的region_id, carrier_id  获取fenqu_id 
	 * 
	 * */
	public function get_fenqu($order, $region_id , $carrier_id, $shipping_time)
	{
		global $db;
		$sql = "select region_id, fenqu_id,region_name_chs, date from ecshop.ecs_inter_partition
				where region_id = '{$region_id}'
				and carrier_id = '{$carrier_id}'
				ORDER BY date DESC
		";
		
		$fenqu_list = $db->getAll($sql);
		
		foreach ($fenqu_list as $fenqu){
			if($shipping_time >= $fenqu['date']){
				$order['fenqu_id'] = $fenqu['fenqu_id'];
				$order['region_id'] = $fenqu['region_id'];
				$order['region_name_chs'] = $fenqu['region_name_chs'];
				break;
			}
		}
		return $order;
	}
	public  function export_error_excel($file_name) {
		$excel = new PHPExcel();
		$excel->getProperties()->setTitle($file_name);
		$sheet_no = 1;
		if (!empty($this->error_fee_list)) {
			//var_dump($this->error_fee_list);die();
			$sheet_no ++;
			$sheet = $excel->getActiveSheet();
			$sheet->setTitle('快递费多收明细');
			$sheet->setCellValue('A1', "ERP发货日期");
			$sheet->setCellValue('B1', "运单号");
			$sheet->setCellValue('C1', "订单号");
			$sheet->setCellValue('D1', "淘宝订单号");
			$sheet->setCellValue('E1', "收款方式");
			$sheet->setCellValue('F1', "组织");
			$sheet->setCellValue('G1', "仓库");
			$sheet->setCellValue('H1', "国家");
			$sheet->setCellValue('I1', "省");
			$sheet->setCellValue('J1', "ERP重量");
			$sheet->setCellValue('K1', "快递公司重量");
			$sheet->setCellValue('L1', "快递公司快递费");
			$sheet->setCellValue('M1', "ERP计算快递费");
			$sheet->setCellValue('N1', "快递费差值");
			$sheet->setCellValue('O1', "快递费备注");
			$sheet->setCellValue('P1', "快递公司发货日期");
			$sheet->setCellValue('Q1', "快递类型");
			$sheet->setCellValue('R1', "送达地邮编");
			$i = 2;
			foreach ($this->error_fee_list as $order) {
				$sheet->setCellValue("A{$i}", $order['shipping_time']);
				$sheet->setCellValueExplicit("B{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
				$sheet->setCellValueExplicit("C{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
				$sheet->setCellValueExplicit("D{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
				$sheet->setCellValue("E{$i}", $order['pay_name']);
				$sheet->setCellValue("F{$i}", $order['name']);
				$sheet->setCellValue("G{$i}", $order['facility_name']);
				$sheet->setCellValue("H{$i}", $order['region_name_chs']);
				$sheet->setCellValue("I{$i}", $order['addr']['province_name']);
				$sheet->setCellValue("J{$i}", $order['shipping_weight']);
				$sheet->setCellValue("K{$i}", $order['excel_row']['weight']);
				$sheet->setCellValue("L{$i}", $order['excel_row']['final_fee']);
				$sheet->setCellValue("M{$i}", $order['calculated_fee']);
				$sheet->setCellValue("N{$i}", $order['fee_diff']);
				$sheet->setCellValue("O{$i}", $order['fee_note']);
				$sheet->setCellValue("P{$i}", $order['excel_row']['date']);
				$sheet->setCellValue("Q{$i}", $order['excel_row']['type']);
				$sheet->setCellValue("R{$i}", $order['zipcode']);
				$i++;
			}
		}
		if (!empty($this->missing_carriage_list)) {
	
			if ($sheet_no == 1) {
				$name = '$sheet';
				$name = $excel->getActiveSheet();
			} else {
				$name = '$sheet'.$sheet_no;
				$name = $excel->createSheet();
	
			}
			$sheet_no++;
			$name->setTitle('订单地区或费用维护异常');
			$name->setCellValue('A1', "日期");
			$name->setCellValue('B1', "运单号");
			$name->setCellValue('C1', "订单号");
			$name->setCellValue('D1', "淘宝订单号");
			$name->setCellValue('E1', "收款方式");
			$name->setCellValue('F1', "组织");
			$name->setCellValue('G1', "仓库");
			$name->setCellValue('H1', "系统内国家");
			$name->setCellValue('I1', "快递账单国家地址");
			$name->setCellValue('J1', "重量");
			$name->setCellValue('K1', "快递公司快递费");
			$name->setCellValue('L1', "快递公司发货时间");
			$i = 2;
			foreach ($this->missing_carriage_list as $order) {
				$name->setCellValue("A{$i}", $order['shipping_time']);
				$name->setCellValueExplicit("B{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
				$name->setCellValueExplicit("C{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
				$name->setCellValueExplicit("D{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
				$name->setCellValue("E{$i}", $order['pay_name']);
				$name->setCellValue("F{$i}", $order['name']);
				$name->setCellValue("G{$i}", $order['facility_name']);
				$name->setCellValue("H{$i}", $order['region_name_chs']);
				$name->setCellValue("I{$i}", $order['excel_row']['address']);
				$name->setCellValue("J{$i}", $order['excel_row']['weight']);
				$name->setCellValue("K{$i}", $order['excel_row']['final_fee']);
				$name->setCellValue("L{$i}", $order['excel_row']['date']);
				$i++;
			}
	
		}
		if (!empty($this->error_match_list)) {
	
			if ($sheet_no == 1) {
				$name = '$sheet';
				$name = $excel->getActiveSheet();
			} else {
				$name = '$sheet'.$sheet_no;
				$name = $excel->createSheet();
	
			}
			$sheet_no++;
			$name->setTitle('订单快递与所选择快递不一致');
			$name->setCellValue('A1', "运单实际快递方式");
			$name->setCellValue('B1', "ERP发货日期");
			$name->setCellValue('C1', "运单号");
			$name->setCellValue('D1', "订单号");
			$name->setCellValue('E1', "淘宝订单号");
			$name->setCellValue('F1', "收款方式");
			$name->setCellValue('G1', "组织");
			$name->setCellValue('H1', "仓库");
			$name->setCellValue('I1', "国家");
			$name->setCellValue('J1', "省");
			$name->setCellValue('K1', "重量");
			$name->setCellValue('L1', "快递公司快递费");
			$i = 2;
			foreach ($this->error_match_list as $order) {
				$name->setCellValue("A{$i}", $order['shipping_name']);
				$name->setCellValue("B{$i}", $order['shipping_time']);
				$name->setCellValueExplicit("C{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
				$name->setCellValueExplicit("D{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
				$name->setCellValueExplicit("E{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
				$name->setCellValue("F{$i}", $order['pay_name']);
				$name->setCellValue("G{$i}", $order['name']);
				$name->setCellValue("H{$i}", $order['facility_name']);
				$name->setCellValue("I{$i}", $order['excel_row']['address']);
				$name->setCellValue("J{$i}", $order['addr']['province_name']);
				$name->setCellValue("K{$i}", $order['excel_row']['weight']);
				$name->setCellValue("L{$i}", $order['excel_row']['final_fee']);
				$i++;
			}
	
		}
		if (!empty($this->error_weight)) {
	
			if ($sheet_no == 1) {
				$name = '$sheet';
				$name = $excel->getActiveSheet();
			} else {
				$name = '$sheet'.$sheet_no;
				$name = $excel->createSheet();
	
			}
			$sheet_no++;
			$name->setTitle('快递公司重量异常');
			$name->setCellValue('A1', "运单快递方式");
			$name->setCellValue('B1', "ERP发货日期");
			$name->setCellValue('C1', "运单号");
			$name->setCellValue('D1', "订单号");
			$name->setCellValue('E1', "淘宝订单号");
			$name->setCellValue('F1', "收款方式");
			$name->setCellValue('G1', "组织");
			$name->setCellValue('H1', "仓库");
			$name->setCellValue('I1', "国家");
			$name->setCellValue('J1', "快递公司快递费");
			$name->setCellValue('K1', "快递公司重量");
			$name->setCellValue('L1', "仓库称重重量");
			$name->setCellValue('M1', "仓库称重运费");
			$i = 2;
			foreach ($this->error_weight as $order) {
				$name->setCellValue("A{$i}", $order['shipping_name']);
				$name->setCellValue("B{$i}", $order['shipping_time']);
				$name->setCellValueExplicit("C{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
				$name->setCellValueExplicit("D{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
				$name->setCellValueExplicit("E{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
				$name->setCellValue("F{$i}", $order['pay_name']);
				$name->setCellValue("G{$i}", $order['name']);
				$name->setCellValue("H{$i}", $order['facility_name']);
				$name->setCellValue("I{$i}", $order['region_name_chs']);
				$name->setCellValue("J{$i}", $order['excel_row']['final_fee']);
				$name->setCellValue("K{$i}", $order['excel_row']['weight']);
				if ( $order['shipping_weight'] != null && $order['shipping_weight'] != 0) {
					$name->setCellValue("L{$i}", $order['shipping_weight']);
				}else {
					$name->getStyle("L{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
					$name->getStyle("L{$i}")->getFill()->getStartColor()->setARGB('FFFF0000');
					$name->setCellValue("L{$i}", 0);
				}
	
				$name->setCellValue("M{$i}", round((double)$order['calculated_fee'],4));
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
				foreach ($order as $k => $col){
					if  ($k == 'tracking_number' ) {
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
			$name->setTitle('运费正常列表');
			$name->setCellValue('A1', "ERP发货日期");
			$name->setCellValue('B1', "运单号");
			$name->setCellValue('C1', "订单号");
			$name->setCellValue('D1', "淘宝订单号");
			$name->setCellValue('E1', "收款方式");
			$name->setCellValue('F1', "组织");
			$name->setCellValue('G1', "仓库");
			$name->setCellValue('H1', "ERP重量");
			$name->setCellValue('I1', "ERP金额");
			$name->setCellValue('J1', "账单重量");
			$name->setCellValue('K1', "账单金额");
			$name->setCellValue('L1', "账单金额-ERP金额");
			$name->setCellValue('M1', "国家");
			$name->setCellValue('N1', "快递公司发货日期");
			$name->setCellValue('O1', "快递类型");
			$name->setCellValue('P1', "送达地邮编");
			$i = 2;
			foreach ($this->order_list as $order) {
				$name->setCellValue("A{$i}", $order['shipping_time']);
				$name->setCellValueExplicit("B{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
				$name->setCellValueExplicit("C{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
				$name->setCellValueExplicit("D{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
				$name->setCellValue("E{$i}", $order['pay_name']);
				$name->setCellValue("F{$i}", $order['name']);
				$name->setCellValue("G{$i}", $order['facility_name']);
				$name->setCellValue("H{$i}", $order['shipping_weight']);
				$name->setCellValue("I{$i}", $order['calculated_fee']);
				$name->setCellValue("J{$i}", $order['excel_row']['weight']);
				$name->setCellValue("K{$i}", $order['excel_row']['final_fee']);
				$name->setCellValue("L{$i}", $order['fee_diff']);
				$name->setCellValue("M{$i}", $order['region_name_chs']);
				$name->setCellValue("N{$i}", $order['excel_row']['date']);
				$name->setCellValue("O{$i}", $order['excel_row']['type']);
				$name->setCellValue("P{$i}", $order['zipcode']);
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