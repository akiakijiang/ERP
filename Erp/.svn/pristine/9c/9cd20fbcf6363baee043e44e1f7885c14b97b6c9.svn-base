<?php
/**
* 记录系统里的各种打印行为 
* Class of lIbrary for recording print Action
* All Hail Sinri Edogawa!
* Since 2015 Dec 16th
*/
class LibPrintAction
{

	static $support_type_list=array(
		'BATCH_PICK', // 打印批拣单
		'BATCH_SHIPMENT', // 打印一个批次的发货单
		'SHIPMENT', // 打印单独一个发货单
		'BATCH_THERMAL', // 打印一个批次的热敏面单
		'THERMAL', // 打印一个单独的热敏面单
		'ADD_BATCH_THERMAL', // 打印批量追加的热敏面单
		'ADD_THERMAL', // 打印单个追加的热敏面单
		'BATCH_BILL', // 打印一个批次的普通面单
		'BILL', // 打印单个的普通面单
		'ADD_BATCH_BILL', // 打印批量追加的普通面单
		'ADD_BILL', // 打印单个追加的普通面单
		'INVOICE', // 打印发票
	);

	public static function checkType($type){
		if(in_array($type,LibPrintAction::$support_type_list)){
			return true;
		}else{
			return false;
		}
	}

	public static function addPrintRecord($type,$item,$order_sn=''){
		global $db;
		if(!LibPrintAction::checkType($type) || empty($item)){
			return false;
		}
		$sql="INSERT INTO ecshop.ecs_print_action (
				print_action_id,
				print_type,
				print_item,
				order_sn,
				create_user,
				create_time
			) values (
				null,
				'{$type}',
				'{$item}',
				'{$order_sn}',
				'{$_SESSION['admin_name']}',
				now()
			)
		";
		$pa_id=$db->exec($sql);
		return $pa_id;
	}

	public static function getRecordsForItemOfType($type,$item){
		global $db;
		$sql="SELECT * 
			FROM ecshop.ecs_print_action 
			WHERE print_type='{$type}' 
			and print_item='{$item}'
			order by create_time asc
		";
		$lines=$db->getAll($sql);
		return $lines;
	}

	public static function mergeRecordsForArataShipments($BPSN,$shipments){
		global $db;

		$fin_shipments=array();

		$tracking_numbers=array();
		foreach ($shipments as $shipment) {
			$tracking_numbers[]=$shipment['tracking_number'];
			$fin_shipments[$shipment['tracking_number']]=$shipment;
			$fin_shipments[$shipment['tracking_number']]['countNum']=0;
			$fin_shipments[$shipment['tracking_number']]['PRINT_USER']='';
			$fin_shipments[$shipment['tracking_number']]['PRINT_TIME']='';
		}
		$tracking_number_string="'".implode("','",$tracking_numbers)."'";

		$sql="SELECT * FROM ecshop.ecs_print_action 
			WHERE (print_type='BATCH_THERMAL' and print_item='$BPSN')
			OR (print_type='THERMAL' and print_item in ($tracking_number_string))
			order by create_time asc
		";
		$all_rec=$db->getAll($sql);
		$base_count=0;
		$last_batch_time="";
		$last_batch_user="";
		foreach ($all_rec as $rec) {
			if($rec['print_type']=='BATCH_THERMAL'){
				$base_count+=1;
				$last_batch_time=$rec['create_time'];
				$last_batch_user=$rec['create_user'];
			}elseif($rec['print_type']=='THERMAL'){
				$fin_shipments[$rec['print_item']]['PRINT_TIME']=$rec['create_time'];
				$fin_shipments[$rec['print_item']]['PRINT_USER']=$rec['create_user'];
				$fin_shipments[$rec['print_item']]['countNum']+=1;
			}
		}
		foreach ($fin_shipments as $key => $value) {
			$fin_shipments[$key]['countNum']+=$base_count;
			if($fin_shipments[$key]['PRINT_TIME']<$last_batch_time){
				$fin_shipments[$key]['PRINT_USER']=$last_batch_user;
				$fin_shipments[$key]['PRINT_TIME']=$last_batch_time;
			}	
		}
		//var_dump($fin_shipments);
		return array_values($fin_shipments);
	}
}