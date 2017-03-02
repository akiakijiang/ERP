<?php
/**
 * 批量转仓
 */
set_include_path(get_include_path() . PATH_SEPARATOR .ROOT_PATH. 'admin/includes/Classes/');
require_once(ROOT_PATH . 'admin/includes/Classes/PHPExcel.php');
require_once(ROOT_PATH . 'admin/includes/Classes/PHPExcel/IOFactory.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

class UpdateFacilityExpress{
	//问题单
	protected $error_order = array();
	function check_data($order_id_array,$new_facility_id,$new_shipping_id){
		$order_id_str = implode(",",$order_id_array);
		global $db;
		$sql = "select oi.order_id,order_sn,shipping_status,order_status,oi.pay_status,count(distinct os.order_id) as count_order, 
				 f.is_out_ship,oi.shipping_id,p.is_cod,p.pay_name,oi.facility_id,f.facility_name as old_facility,oi.shipping_name as old_shipping, 
				 (select facility_name from romeo.facility where facility_id = '{$new_facility_id}' and is_closed='N' limit 1) as new_facility, 
				 (select shipping_name from ecshop.ecs_shipping where shipping_id = {$new_shipping_id} and enabled = 1 limit 1 ) as new_shipping  
			 from ecshop.ecs_order_info oi  
			 left join romeo.shipment s on s.primary_order_id = convert(oi.order_id using utf8) and s.shipping_category = 'SHIPPING_SEND' 
			 left join romeo.order_shipment os on os.shipment_id = s.shipment_id  
			 left join romeo.facility f on f.facility_id = oi.facility_id  
			 left join ecshop.ecs_payment p on p.pay_id = oi.pay_id  
			 WHERE oi.order_id in ({$order_id_str}) and (oi.shipping_id !={$new_shipping_id} or oi.facility_id != '{$new_facility_id}')
			 group by oi.order_id ";	
//			 QLog::log("sql2:".$sql);
		$order_infos = $db->getAll($sql);
		$real_orders = array(); 
		$sql = "SELECT support_cod,support_no_cod,default_carrier_id,shipping_name,shipping_id FROM ecshop.ecs_shipping 
			 WHERE enabled = 1 and shipping_id = {$new_shipping_id} ";
//		QLog::log("sql2:".$sql);
		$new_shipping = $db->getRow($sql);
		if(!empty($order_infos)){
			foreach($order_infos as $order_info){
				if($order_info['shipping_status'] !=0 || $order_info['count_order']>1 || ($order_info['is_out_ship'] =='Y' && $order_info['order_status'] !=0)){
					$order_info['note'] = '待配货，非合并，如果为外包仓只能转未确认订单';
					$this->error_order[$order_info['order_sn']] = $order_info;
					continue;
				}else{
					if($order_info['shipping_id']!=$new_shipping_id && ((!$new_shipping['support_cod'] && !$new_shipping['support_no_cod'])||($order_info['is_cod'] != $new_shipping['support_cod']))){
						$order_info['note'] = '支付方式与快递不兼容,'.$order_info['pay_name'];
						$this->error_order[$order_info['order_sn']] = $order_info;
						continue;
					}
					$real_orders[$order_info['order_id']] =$order_info;
				}
			}
		}
		//update facility_express
		if(!empty($real_orders)){
			$flag = $this->update_facility_express($real_orders,$new_facility_id,$new_shipping);
			foreach($real_orders as $real_order){
				if(!$flag){
					$real_order['note'] = '转仓转快递失败';
					$this->error_order[$real_order['order_sn']] = $real_order;
				}elseif($real_order['facility_id'] != $new_facility_id){
					$flag2 = $this->check_reserve_success($real_order,$new_facility_id);
					if(!$flag2) continue;
				}
			}
		}
	}
	
	/**
	 * 订单取消预定-无论订单本身是否预定成功
	 */
	function check_reserve_success($row,$new_facility_id){
		global $db;
		$flag = true;
		//加锁限制
		$lock_file_from = get_file_lock_path($row['order_id'], 'pick_merge');
		$lock_file_point_from = fopen($lock_file_from, "w+");
		if(flock($lock_file_point_from, LOCK_EX|LOCK_NB, $would_block_ref)){
//			sleep(3);
			$sql = "select ir.status as ir_status,ird.status as ird_status,ir.order_inv_reserved_id from romeo.order_inv_reserved ir " .
				" left join romeo.order_inv_reserved_detail ird on ird.order_inv_reserved_id = ir.order_inv_reserved_id " .
				" where ir.order_id = '{$row['order_id']}' and ir.facility_id != '{$new_facility_id}' limit 1 ";
			$reserved_id = $db->getRow($sql);
			$sql_array = array();
			if(!empty($reserved_id)){
				$sql = "SELECT sum(RESERVED_QUANTITY) quantity,iis.inventory_summary_id
					FROM romeo.order_inv_reserved_detail   iid 
					inner join romeo.inventory_summary iis on iis.product_id = iid.PRODUCT_ID and iis.facility_id = iid.facility_id and iis.status_id = iid.status_id
					where ORDER_INV_RESERVED_ID = '{$reserved_id['order_inv_reserved_id']}'
					GROUP BY iid.PRODUCT_ID,iid.STATUS_ID,iid.FACILITY_ID 
					having quantity >0";
				$summarys = $db->getAll($sql);
				$sql_array[] = "delete from romeo.order_inv_reserved_detail where ORDER_INV_RESERVED_ID = '{$reserved_id['order_inv_reserved_id']}' ";
				$sql_array[] = "delete from romeo.order_inv_reserved where ORDER_INV_RESERVED_ID = '{$reserved_id['order_inv_reserved_id']}' ";
				if(!empty($summarys)){
					foreach($summarys as $summary){
						$sql_array[] = "update romeo.inventory_summary  set AVAILABLE_TO_RESERVED=AVAILABLE_TO_RESERVED+{$summary['quantity']} where INVENTORY_SUMMARY_ID ='{$summary['inventory_summary_id']}'";
					}
				}
				$db->start_transaction();
				$flaga = true;
				foreach($sql_array as $sqla){
					if(!$db->query($sqla)){
				        $flaga = false;
				        break;
				    }
				}
				if($flaga){
					$db->commit();
				}else{
					$db->rollback();
					$row['note'] = "转仓时取消预定失败";	
				   	$this->error_order[$row['order_sn']] = $row;
				   	$flag = false;
				}  
			}
	    	flock($lock_file_point_from, LOCK_UN);
	    	fclose($lock_file_point_from);
			unlink($lock_file_from);
			if(file_exists($lock_file_from)){
				QLog::log("order batch change facility lock for order_id = ".$row['order_id']." failed to release ");
			}
		}else{
			$row['note'] = "转仓时遇见合并/批拣";
			$this->error_order[$row['order_sn']] = $row;
			$flag = false;
			fclose($lock_file_point_from);
		}
		return $flag;
			
	} 
	/**
	 * 更新快递方式
	 */
	function update_facility_express($real_orders,$new_facility_id,$new_shipping){
		global $db;
		$order_ids = implode(",",array_keys($real_orders));
		//更新仓库
		$arr_sql[] = " update ecshop.ecs_order_info SET facility_id = '{$new_facility_id}' WHERE order_id in ({$order_ids}) and facility_id != '{$new_facility_id}' ";
		
		//更新快递
		$arr_sql[] = " update ecshop.ecs_order_info oi " .
				" left join ecshop.ecs_carrier_bill ecb on ecb.bill_id = oi.carrier_bill_id " .
				" left join romeo.shipment s on s.primary_order_id = convert(oi.order_id using utf8) " .
				" set oi.shipping_id = {$new_shipping['shipping_id']} ,oi.shipping_name ='{$new_shipping['shipping_name']}'," .
				" s.shipment_type_id = {$new_shipping['shipping_id']} ,s.carrier_id ={$new_shipping['default_carrier_id']}, " .
				" ecb.carrier_id = {$new_shipping['default_carrier_id']},LAST_UPDATE_TX_STAMP = NOW(),LAST_MODIFIED_BY_USER_LOGIN ='{$_SESSION['admin_name']}' " .
				" where oi.order_id in ({$order_ids}) and oi.shipping_id != {$new_shipping['shipping_id']} and oi.shipping_status = 0 ";
		//插入action
		$action=array();
		foreach($real_orders as $order_id=>$status){
			$action[] = " ('{$order_id}','{$_SESSION['admin_name']}',{$status['order_status']},{$status['shipping_status']},{$status['pay_status']},now(),'".'从'.$status['old_facility'].$status['old_shipping'].'改为'.$status['new_facility'].$status['new_shipping']."') ";
		}
		$arr_sql[] = "insert into ecshop.ecs_order_action(order_id,action_user,order_status,shipping_status,pay_status,action_time,action_note) values ".implode(",",$action).";";	
		
		$db->start_transaction();
		$flag = true;
		foreach($arr_sql as $sql){
			if(!$db->query($sql)){
		        $flag = false;
		        break;
		    }
		}
		if($flag){
			$db->commit();
		}else{
			$db->rollback();
		}  
		return $flag;
	} 
	
	/**
	 * 将异常数据输出
	 * @param string $file_name 导出excel文件名
	 */
	function export_data_excel($file_name) {
		if(!empty($this->error_order)){
			$cell_nos = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','','X','Y','Z');
			$excel = new PHPExcel();
		    $excel->getProperties()->setTitle($file_name);
		    $sheet_no = 1;
	    
	    	if ($sheet_no == 1 ) {
	        	$name = '$sheet';
	        	$name = $excel->getActiveSheet();
	       	} else {
	        	$name = '$sheet'.$sheet_no;
	        	$name = $excel->createSheet();
	        }
	        $sheet_no++;
	       	$name->setTitle('问题订单列表');
			$name->setCellValue('A1', "订单号");
			$name->setCellValue('B1', "发货状态");
			$name->setCellValue('C1', "原仓库");
			$name->setCellValue('D1', "新仓库");
			$name->setCellValue('E1', "原快递");
			$name->setCellValue('F1', "新快递");
			$name->setCellValue('G1', "备注");
	        $i = 2;
	        foreach ($this->error_order as $order) {
	           	$name->setCellValue("A{$i}", $order['order_sn'] );
	           	$name->setCellValue("B{$i}", $order['shipping_status']);
	           	$name->setCellValue("C{$i}", $order['old_facility']);
	           	$name->setCellValue("D{$i}", $order['new_facility']);
	           	$name->setCellValue("E{$i}", $order['old_shipping']);
	           	$name->setCellValue("F{$i}", $order['new_shipping']);
	           	$name->setCellValue("G{$i}", $order['note']);
	           	
	          	$i++;
	       	}
	    
		  	if (!headers_sent()) {
		   		header('Content-Type: application/vnd.ms-excel');
	            header('Content-Disposition: attachment;filename="'.$file_name.'.xlsx"');
	            header('Cache-Control: max-age=0');
	            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
	            $output->setOffice2003Compatibility(true);
		      	$output->save('php://output');
		      	exit;
		  	}
		}
	}
}    
    
?>