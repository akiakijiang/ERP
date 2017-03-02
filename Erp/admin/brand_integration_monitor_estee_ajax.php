<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once('brand_integration_monitor_estee_func.php');

/*$_REQUEST = array(
	"type" => 'Header',
	"action" => 'AddPOFromOld',
	"header_id" => '456',
	"new_PO_data" => array(
		'document_id' => '784230967271730-M5',
		'lines' => array(
			array(
				'material_number' => '708A056000',
				'quantity' => 1,
				'type' => 'SS'
				)
			)
		),
	"note" => '物料号计划外更改,修改后重发'
	);*/

/*$_REQUEST = array(
	"type" => 'Header',
	"action" => 'GenerateZRTO',
	"header_id" => '457',
	"document_id" => '784230967271730-tm3',
	"note" => '生成退单测试'
	);*/

/*$_REQUEST = array(
	"type" => 'Order',
	"action" => 'GetData',
	"estee_order_id" => '4306'
	);*/

$cls_name = "ClsEstee".$_REQUEST['type']."Data";
$result = $cls_name::$_REQUEST['action']();
//print_r($result);
die (json_encode($result));

class ClsEsteeErpOrderData{
	static public function MarkOrder(){
		$order_id = $_REQUEST['order_id'];
		global $db;
		$sql = "select remark from ecshop.brand_estee_erp_order_remark where order_id = '{$order_id}';";
		$remark = $db->getOne($sql);
		if(isset($remark)){
			$remark = $remark . ";" . $_REQUEST['note'];
			$sql = "update ecshop.brand_estee_erp_order_remark set remark = '{$remark}', remarker='{$_SESSION['admin_name']}', last_updated_stamp = NOW() where order_id = '{$order_id}'";
		}else{
			$remark = $_REQUEST['note'];
			$sql = "insert into ecshop.brand_estee_erp_order_remark (order_id, remark_type, remark, remarker, created_stamp, last_updated_stamp) 
						values ('{$order_id}', 'ToSet', '{$_REQUEST['note']}', '{$_SESSION['admin_name']}', NOW(), NOW());";
		}
		if($db->query($sql)){
			$result = array('err_no' => 0, 'msg' => '备注添加成功', 'remark'=>$remark, 'remarker'=>$_SESSION['admin_name']);
		}else{
			$result = array('err_no' => 1, 'msg' => '备注添加失败');
		}
		return $result;
	}
}
class ClsEsteeOrderData{
	static public function GetData1(){
		$estee_order_id = $_REQUEST['estee_order_id'];
		global $db;
		$sql = "select order_type, order_date, pricing_date, shipping_fee,created_stamp from ecshop.brand_estee_order where estee_order_id = '{$estee_order_id}';";
		$order = $db->getRow($sql);
		$sql = "SELECT material_number, quantity, goods_type, goods_price, back_count from ecshop.brand_estee_order_goods where estee_order_id = '{$estee_order_id}';";
		$order_goods = $db->getAll($sql);
		$order['goods_count'] = count($order_goods);
		$order['goods'] = $order_goods;
		return $order;
	}

	static public function GetData(){
		$estee_order_id = $_REQUEST['estee_order_id'];
		global $db;
		$sql = "SELECT boo.document_id, boo.order_type, boo.order_date, boo.pricing_date, boo.shipping_fee,
					boog.*
				FROM ecshop.brand_estee_order boo
				LEFT JOIN ecshop.brand_estee_order_goods boog on boo.estee_order_id = boog.estee_order_id
				where boo.order_id = 4562009;";
		$all_data = $db->getAllRefBy($sql, array('goods_type'), $goods_types, $order_data_ori);
		print_r($goods_types);
		print_r($order_data_ori);
		print_r($all_data);
		$order_data = array(
			'document_id' => $all_data[0]['document_id'],
			'order_type' => $all_data[0]['order_type'],
			'order_date' => $all_data[0]['order_date'],
			'pricing_date' => $all_data[0]['pricing_date'],
			'shipping_fee' => $all_data[0]['shipping_fee'],
			'grouped_goods_list' => $order_data_ori['goods_type']);
		print_r($order_data);


		$sql = "SELECT *
				FROM ecshop.brand_estee_header boh
				LEFT JOIN ecshop.brand_estee_line bol on boh.estee_header_id = bol.header_id
				WHERE boh.order_id = 4562009;";
	}
}

class ClsEsteeHeaderData{
	static public $party_brand_mapping = array(
		"65628" => '10',
		"65639" => '11'
	);
	static public function GetData(){
		$header_id = $_REQUEST['header_id'];
		global $db;
		$sql = "select estee_header_id, document_id, order_type, condition_value, condition_type, order_date, requested_delivery_date, pricing_date from ecshop.brand_estee_header where estee_header_id = '{$header_id}';";
		$header = $db->getRow($sql);
		$sql = "SELECT estee_line_id, material_number, quantity, order_type, ifnull(condition_type,'') as condition_type, discount_amount from ecshop.brand_estee_line where header_id = '{$header_id}';";
		$lines = $db->getAll($sql);
		$header['line_count'] = count($lines);
		$header['lines'] = $lines;
		return $header;
	}
	static public function EditPO(){
		//print_r("expression");
		//print_r($_REQUEST);
		$header_id = $_REQUEST['header_id'];
		$PO_data = $_REQUEST['edit_PO_data'];
		$note = $_REQUEST['note'];
		$result = array('err_no' => 0, 'msg' => '');
		global $db;
		$sql = "select document_id, order_type from ecshop.brand_estee_header where estee_header_id = '{$header_id}';";
		$header_data = $db->getRow($sql);
		$is_saleable_PO = strpos($header_data['document_id'], 'P') === false;
		//$requested_delivery_date = date('Y-m-d H:m:s');
		if(ClsEsteeHeaderData::CheckLines($header_data['order_type'], $is_saleable_PO, $PO_data['lines'], $result)){
			$db->start_transaction();
			$sql = "update ecshop.brand_estee_header 
					set order_date = '{$PO_data['order_date']}', requested_delivery_date = NOW(), pricing_date = '{$PO_data['pricing_date']}', 
						last_updated_stamp = NOW()
					where estee_header_id = '{$header_id}';";
			if(!$db->query($sql)){
				$result = array('err_no' => 1, 'msg' => '修改失败: ' . $sql);
			}

			if($result['err_no'] === 0){
				foreach ($PO_data['lines'] as $key => $line) {
					$sql = "update ecshop.brand_estee_line 
							set material_number = '{$line['material_number']}', quantity = '{$line['quantity']}', 
								order_type = '{$line['type']}', condition_type = '{$line['condition_type']}', discount_amount = '{$line['discount_amount']}'
							where estee_line_id = '{$line['estee_line_id']}';";
					if(!$db->query($sql)){
						$result = array('err_no' => 1, 'msg' => '修改失败: ' . $sql);
						break;
					}
				}
			}

			if($result['err_no'] === 0){
				$action = array(
					"header_id" => $header_id,
					"action_note" => $_REQUEST['note']
					);
				ClsEsteeHeaderData::InsertAction($action);
				$db->commit();
			}else{
				$db->rollback();
			}
		} else {
			$result = array('err_no' => 1, 'msg' => '修改失败: 请检查商品级别的order_type是否有误。' );
		}
		return $result;
	}
	static public function AddPOFromOld(){
		$party_brand_mapping = self::$party_brand_mapping;
		$old_header_id = $_REQUEST['header_id'];
		$new_PO_data = $_REQUEST['new_PO_data'];
		$note = $_REQUEST['note'];
		$result = array('err_no' => 0, 'msg' => '');
		global $db;
		$sql = "select order_id, document_id, order_type, order_date, condition_value, condition_type,reason_code from ecshop.brand_estee_header where estee_header_id = '{$old_header_id}';";
		$header_data = $db->getRow($sql);
		$is_saleable_PO = strpos($header_data['document_id'], 'P') === false;
		//$requested_delivery_date = date('Y-m-d H:m:s');
		if(ClsEsteeHeaderData::CheckPONo($old_header_id, $new_PO_data['document_id'], $result)
			 && ClsEsteeHeaderData::CheckLines($header_data['order_type'], $is_saleable_PO, $new_PO_data['lines'], $result)){
			$db->start_transaction();
			$sql = "insert into ecshop.brand_estee_header 
					(party_id,brand_id,order_id, document_id, order_type, order_date, requested_delivery_date, pricing_date, condition_value, condition_type, sync_status, time_outs, created_stamp, last_updated_stamp,reason_code)
					values ('{$_SESSION['party_id']}','{$party_brand_mapping[$_SESSION['party_id']]}',{$header_data['order_id']}, '{$new_PO_data['document_id']}', '{$header_data['order_type']}', 
						'{$header_data['order_date']}', NOW(), '{$new_PO_data['pricing_date']}', '{$header_data['condition_value']}', '{$header_data['condition_type']}', 'INIT', 0, NOW(), NOW(),'{$header_data['reason_code']}');";
			$header_id = $db->query($sql);
			if(!$header_id){
				$result = array('err_no' => 1, 'msg' => '生成失败: ' . $sql);
			}else{
				$header_id = $db->insert_id();
			}

			if($result['err_no'] === 0){
				global $db;
				$sql = "insert into ecshop.brand_estee_header_attribute (header_id, attr_name, attr_value, created_stamp) VALUES ({$header_id}, 'MANUAL', '{$note}', NOW());";
				if(!$db->query($sql)){
					$result = array('err_no' => 1, 'msg' => '生成失败: ' . $sql);
				}
			}

			if($result['err_no'] === 0){
				$count = 1;
				$sql = "insert into ecshop.brand_estee_line (header_id, line_number, material_number, quantity, order_type, condition_type, discount_amount) values ";
				foreach ($new_PO_data['lines'] as $key => $line) {
					$sql_values[] = "({$header_id}, {$count}, '{$line['material_number']}', '{$line['quantity']}', '{$line['type']}', '{$line['condition_type']}', '{$line['discount_amount']}')";
					$count++;
				}
				$sql .= implode(",", $sql_values);
				if(!$db->query($sql)){
					$result = array('err_no' => 1, 'msg' => '生成失败: ' . $sql);
				}
			}

			if($result['err_no'] === 0){
				$action = array(
					"header_id" => $header_id,
					"action_note" => $_REQUEST['note']
					);
				ClsEsteeHeaderData::InsertAction($action);
				$db->commit();
			}else{
				$db->rollback();
			}
		}
		return $result;
	}

	static public function GenerateZRTO(){
		$party_brand_mapping = self::$party_brand_mapping;
		$result = array('err_no' => 0, 'msg' => '');
		//header
		$sale_header_id = $_REQUEST['header_id'];
		$rma_document_id = $_REQUEST['document_id'];
		$note = $_REQUEST['note'];
		
		global $db;
		$sql = "select order_id, document_id, order_type, order_date, requested_delivery_date, pricing_date, condition_value from ecshop.brand_estee_header where estee_header_id = '{$sale_header_id}';";
		$header = $db->getRow($sql);
		if($header['order_type'] !== 'Z3OS'){
			$result = array('err_no' => 1, 'msg' => '非Z3OS订单无法生成ZRTO订单');
		}
		
		if($result['err_no'] === 0){
			$sql = "select line_number, material_number, quantity, order_type as type, condition_type, discount_amount from ecshop.brand_estee_line where header_id = {$sale_header_id}";
			$lines = $db->getAll($sql);
		}
		
		if(!empty($lines)){
			foreach($lines as $key=>$line){
				if(in_array($lines[$key]['type'],array('SS','PS','SP'))){
					switch ($lines[$key]['type']) {
						case 'SS':
							$lines[$key]['type'] = 'RS';
							break;
						case 'PS':
							$lines[$key]['type'] = 'RP';
							break;
						case 'SP':
							$lines[$key]['type'] = 'SR';
							break;
						default:
							break;
					}
				}else{
					$result = array('err_no' => 1, 'msg' => '原Z3OS订单lines记录的order_type不合法');
					break;
				}
			}
		}else{
			$result = array('err_no' => 1, 'msg' => '原Z3OS订单无lines记录');
		}
		
		$is_saleable_PO = strpos($header['document_id'], 'P') === false;
		if(ClsEsteeHeaderData::CheckPONo($sale_header_id, $rma_document_id, $result)
			 && ClsEsteeHeaderData::CheckLines('ZRTO', $is_saleable_PO, $lines, $result)){
			$db->start_transaction();
			$sql = "insert into ecshop.brand_estee_header 
					(party_id,brand_id,order_id, document_id, order_type, order_date, requested_delivery_date, pricing_date, condition_value, sync_status, time_outs, created_stamp, last_updated_stamp,reason_code)
					values ('{$_SESSION['party_id']}','{$party_brand_mapping[$_SESSION['party_id']]}',{$header['order_id']}, '{$rma_document_id}', 'ZRTO', 
						'{$header['order_date']}', NOW(), '{$header['pricing_date']}', '{$header['condition_value']}', 'INIT', 0, NOW(), NOW(),'Z20');";
			$header_id = $db->query($sql);
			if(!$header_id){
				$result = array('err_no' => 1, 'msg' => '生成失败: ' . $sql);
			}else{
				$header_id = $db->insert_id();
			}

			if($result['err_no'] === 0){
				global $db;
				$sql = "insert into ecshop.brand_estee_header_attribute (header_id, attr_name, attr_value, created_stamp) VALUES ({$header_id}, 'MANUAL', '{$note}', NOW());";
				if(!$db->query($sql)){
					$result = array('err_no' => 1, 'msg' => '生成失败: ' . $sql);
				}
			}

			if($result['err_no'] === 0){
				$count = 1;
				$sql = "insert into ecshop.brand_estee_line (header_id, line_number, material_number, quantity, order_type, condition_type, discount_amount) values ";
				foreach ($lines as $key => $line) {
					$sql_values[] = "({$header_id}, {$count}, '{$line['material_number']}', '{$line['quantity']}', '{$line['type']}', '{$line['condition_type']}', '{$line['discount_amount']}')";
					$count++;
				}
				$sql .= implode(",", $sql_values);
				if(!$db->query($sql)){
					$result = array('err_no' => 1, 'msg' => '生成失败: ' . $sql);
				}
			}

			if($result['err_no'] === 0){
				$db->commit();
				//$db->rollback();
			}else{
				$db->rollback();
			}
		}
		return $result;	
	}


	static public function GenerateZRTOSR(){
		$party_brand_mapping = self::$party_brand_mapping;
		$result = array('err_no' => 0, 'msg' => '');
		//header
		$sale_header_id = $_REQUEST['header_id'];
		$rma_document_id = $_REQUEST['document_id'];
		$shipping_fee = $_REQUEST['shipping_fee'];
		$note = $_REQUEST['note'];		
//		$rma_document_id = $rma_document_id.'TF';

		if(empty($shipping_fee) || $shipping_fee <= 0 || !is_numeric($shipping_fee)) {
			$result = array('err_no' => 1, 'msg' => '邮费必须为正数');
		} else {
			$shipping_fee = round($shipping_fee/1.17,2);
		}
		if($result['err_no'] === 0) {
			if(!preg_match('/TF$/',$rma_document_id)) {
				$result = array('err_no' => 1, 'msg' => '退运费的PO号必须以TF结尾');
			}
		}
		global $db;
		if($result['err_no'] === 0) {
			$sql = "select order_id, document_id, order_type, order_date, requested_delivery_date, pricing_date, condition_value from ecshop.brand_estee_header where estee_header_id = '{$sale_header_id}';";
			$header = $db->getRow($sql);
			if($header['order_type'] !== 'Z3OS'){
				$result = array('err_no' => 1, 'msg' => '非Z3OS订单无法生成ZRTO订单'.$sale_header_id);
			}
		}
		if($result['err_no'] === 0){
			$sql = "select line_number, material_number, quantity, order_type as type, condition_type, discount_amount from ecshop.brand_estee_line where header_id = {$sale_header_id}";
			$lines = $db->getAll($sql);		
			$count = 0;
			if(!empty($lines)){
				foreach($lines as $key=>$line){
					if(in_array($lines[$key]['type'],array('SS','PS','SP'))){
//						if($lines[$key]['type'] == 'SP') {
//							$count = $count + 1;
//						}
					}else{
						$result = array('err_no' => 1, 'msg' => '原Z3OS订单lines记录的order_type不合法');
						break;
					}
				}
//				if($count < 1) {
//					$result = array('err_no' => 1, 'msg' => '原Z3OS订单lines记录中无SP类型');
//				}
			}else{
				$result = array('err_no' => 1, 'msg' => '原Z3OS订单无lines记录'.$sale_header_id);
			}
		}
		$is_saleable_PO = strpos($header['document_id'], 'P') === false;
		if(ClsEsteeHeaderData::CheckPONo($sale_header_id, $rma_document_id, $result)
			 && ClsEsteeHeaderData::CheckLines('ZRTO', $is_saleable_PO, $lines, $result)){
			$db->start_transaction();
			$sql = "insert into ecshop.brand_estee_header 
					(party_id, brand_id, order_id, document_id, order_type, order_date, requested_delivery_date, pricing_date, condition_value, sync_status, time_outs, created_stamp, last_updated_stamp,reason_code)
					values ('{$_SESSION['party_id']}','{$party_brand_mapping[$_SESSION['party_id']]}', {$header['order_id']}, '{$rma_document_id}', 'ZRTO', 
						'{$header['order_date']}', NOW(), '{$header['pricing_date']}', '{$header['condition_value']}', 'INIT', 0, NOW(), NOW(),'Z20');";
			$header_id = $db->query($sql);
			if(!$header_id){
				$result = array('err_no' => 1, 'msg' => '生成失败: ' . $sql);
			}else{
				$header_id = $db->insert_id();
			}

			if($result['err_no'] === 0){
				global $db;
				$sql = "insert into ecshop.brand_estee_header_attribute (header_id, attr_name, attr_value, created_stamp) VALUES ({$header_id}, 'MANUAL', '{$note}', NOW());";
				if(!$db->query($sql)){
					$result = array('err_no' => 1, 'msg' => '生成失败: ' . $sql);
				}				
			}

			if($result['err_no'] === 0){
				$party_shipping_mapping = array(
					"65628" => 'SP00000028',
					"65639" => 'SP00000018'
				);
				$sql = "insert into ecshop.brand_estee_line (header_id, line_number, material_number, quantity, order_type, condition_type, discount_amount) values ({$header_id}, 1, '{$party_shipping_mapping[$_SESSION['party_id']]}', '1', 'SR', 'ZDLC', '$shipping_fee')";					

				if(!$db->query($sql)){
					$result = array('err_no' => 1, 'msg' => '生成失败: ' . $sql);
				}
			}

			if($result['err_no'] === 0){
				$db->commit();
			}else{
				$db->rollback();
			}
		}
		return $result;	
	}	
	

	static public function ClosePO(){
		$result = array('err_no' => 0, 'msg' => 'PO废除成功');
		ClsEsteeHeaderData::UpdateHeader(array('header_status'=>'CLOSED'), $result);
		return $result;
	}

	static public function ReopenPO(){
		$result = array('err_no' => 0, 'msg' => 'PO恢复成功');
		ClsEsteeHeaderData::UpdateHeader(array('header_status'=>'OK'), $result);
		return $result;
	}

	static public function InitSyncStatus(){
		$result = array('err_no' => 0, 'msg' => 'PO初始化成功');
		ClsEsteeHeaderData::UpdateHeader(array('sync_status'=>'INIT'), $result);
		return $result;
	}

	static private function UpdateHeader($var_list = null, &$result){
		$header_id = $_REQUEST['header_id'];
		$note = "[监控界面更新PO]".$_REQUEST['note'];
		$action = ClsEsteeHeaderData::GetHeaderForInsertAction($header_id);
		$order_type = $action['order_type'];
		unset($action['order_type']);
		$action['action_note'] = $note;
		if(isset($action)){
			if(isset($var_list)){
				$sql_seg = "";
				$actions = $sqls = array();
				foreach ($var_list as $key => $value) {
					$sql_seg .= ($key . " = '" . $value . "',");
					$action[$key] = $value;
					switch ($key) {
						case 'sync_status':
							$action['action_type'] = 'SYNC';
							$sqls[] = "insert into ecshop.brand_estee_sync_record (header_id, record_type, description, created_stamp, last_updated_stamp) values ( {$header_id}, '" .$value. "', '".$note ."', NOW(), NOW());";
							break;
						case 'header_status':
							$action['action_type'] = 'STATUS_SET';
							break;
						default:
							$result = array('err_no' => 1, 'msg' => '待更新字段不存在['.$key.']');
							break;
					}
					$actions[] = $action;
				}
				//header
				$sqls[] = "update ecshop.brand_estee_header set " . $sql_seg . " last_updated_stamp = NOW() where estee_header_id = {$header_id};";

				//update
				global $db;
				foreach ($sqls as $sql) {
					$db->query($sql);
				}
				foreach ($actions as $action) {
					ClsEsteeHeaderData::InsertAction($action);
				}
				//actions
				GenerateAllowedActionsForHeader($order_type, $action['header_status'], $action['sync_status'], $result['action_list'], $result);
			}else{
				$result = array('err_no' => 1, 'msg' => 'PO无更新内容');
			}
		}else{
			$result = array('err_no' => 1, 'msg' => 'PO不存在');
		}
	}

	static private function CheckPONo($old_header_id, $PO_No2, &$result){
		$sql = "SELECT boh2.document_id
				from ecshop.brand_estee_header boh1 
				LEFT JOIN ecshop.brand_estee_header boh2 on boh1.order_id = boh2.order_id
				where boh1.estee_header_id = '{$old_header_id}';";
		global $db;
		$existed_POs = $db->getCol($sql);
		assert(count($existed_POs));

		if(in_array($PO_No2, $existed_POs)){			
			$result['err_no'] = 1;
			$result['msg'] = "PO号[" . $PO_No2 . "]已存在，请修改!";
		}else if (strncmp($existed_POs[0], $PO_No2, 15) !== 0){
			$result['err_no'] = 2;
			$result['msg'] = "PO号[" . $PO_No2 ."]前15位(Tmall订单号)必须与当前PO相同";
		}else{
			//
		}
		return $result['err_no'] === 0;
	}

	static private function CheckLines($header_order_type, $is_saleable_PO, &$lines, &$result){
		$line_type = $ship_line_type = 'ERR';		
		$result = array('err_no' => 0, 'msg' => '');
		if($header_order_type === 'Z3OS'){
			$line_type = $is_saleable_PO ? 'SS' : 'PS';
//			$line_types = array('SS', 'PS','CO','TS','FS','VC','GW','FG','DN');
			$ship_line_type = 'SP';
		}else if($header_order_type === 'ZRTO'){
			$line_type = $is_saleable_PO ? 'RS' : 'RP';
//			$line_types = array('RS', 'RP','GR','RG','RC','RF');
			$ship_line_type = 'SR';
		}else{
			$result['err_no'] = 1;
			$result['msg'] .= "PO类型[" . $header_order_type . "]异常，请联系ERP!";
		}

		if($result['err_no'] === 0){
			$material_numbers = array();
			if(count($lines)){
				foreach ($lines as $key => &$line) {
					if(!in_array($line['material_number'],array('SP00000028','SP00000018','SP00000019','SP00000029'))) {
						$material_numbers[] = $line['material_number'];
						$line['type'] = $line_type;
//						if(!in_array($line['order_type'],$line_types)) {
//							$result['err_no'] = 5;
//							$result['msg'] .= "无".$line['type']."order_type";
//						} else {
//							$line['type'] = $line['order_type'];
//						}
					} else {
						$line['type'] = $ship_line_type;
					}			
				}
				$material_numbers_str = implode("','", $material_numbers);
				global $db;
				$sql = "SELECT material_number from ecshop.brand_estee_product where material_number in('{$material_numbers_str}');";
				$material_number_ok = $db->getCol($sql);
				$material_number_diff = array_diff($material_numbers, $material_number_ok);
				if(!empty($material_number_diff)){
					$result['err_no'] = 3;
					$result['msg'] .= "Material Number[" . implode(",", $material_number_diff) . "]未维护，请检查是否输入错误或者维护之!";
				}
			}else{
				$result['err_no'] = 4;
				$result['msg'] .= "无line记录!";
			}
		}

		return $result['err_no'] === 0;
	}

	static private function GetHeaderForInsertAction($header_id){
		global $db;
		return $db->getRow("select estee_header_id header_id, order_type, sync_status, header_status from ecshop.brand_estee_header where estee_header_id = '{$header_id}'");
	}
	static private function GenerateActionList($header_id){
		global $db;
		$header_status = $db->getRow("select order_type, sync_status, header_status from ecshop.brand_estee_header where estee_header_id = '{$header_id}'");
		$action_list = array();
		//废除PO/恢复PO
		if($header_status['header_status'] =='OK')
			$action_list[] = array('name'=>'废除PO', 'action'=>'ClosePO');
		else if($header_status['header_status'] =='CLOSED')
			$action_list[] = array('name'=>'恢复PO', 'action'=>'ReopenPO');
		//重建PO
		$action_list[] = array('name'=>'重建PO', 'action'=>'AddPOFromOld');
		//补建退单
		if($header_status['order_type'] == 'Z3OS' && $header_status['sync_status'] == 'Finish' && $header_status['header_status'] =='OK') {
			$action_list[] = array('name'=>'补建退单', 'action'=>'GenerateZRTO');
			//补建退运费
			$action_list[] = array('name'=>'补建退运费', 'action'=>'GenerateZRTOSR');
		}
		//初始化同步
		if($header_status['sync_status'] == 'ConnectionErr' && $header_status['header_status'] =='OK')
			$action_list[] = array('name'=>'初始化同步', 'action'=>'InitSyncStatus');

		return $action_list;
	}
	static private function InsertAction($action){
		global $db;
		$action['action_user'] = $_SESSION['admin_name'];
		$keys = $values = array();
		assert(count($action) > 0);
		foreach ($action as $key => $value) {
			$keys[] = $key;
			$values[] = $value;
		}
		$sql = "insert into ecshop.brand_estee_header_action (" . implode(", ", $keys) . ", action_time) values ('" . implode("', '", $values) . "', NOW());";
		$db->query($sql);
	}
}

class ClsEsteeSyncData{
	static public function GetData(){
		global $db;
		$sql = "SELECT record_type, description, created_stamp from ecshop.brand_estee_sync_record where header_id = '{$_REQUEST['header_id']}' order by created_stamp";
		$result = $db->getAll($sql);
		return $result;
	}
}






?>