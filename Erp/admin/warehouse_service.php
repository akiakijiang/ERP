<?php
/**
 * 仓库退货 登记 + 验货  
 * searchRegister 查询记录
 * addRegister 退货登记添加
 */
define('IN_ECS', true);

require('includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH.'includes/debug/lib_log.php');
include_once('function.php');
global $db;

$service_type_all = array(
	'whole'=>'原单退回',
	'part'=>'顾客自退',
);
$service_status_all = array(
	'INIT'=>'已登记',
	'CHECKED'=>'已验货',
	'RECOVER'=>'已入库',
	'REJECT'=>'已拒绝',
);
$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):'searchRegister'; // 默认查询

if($act=='searchRegister'){
	// 查询条件
	$condition = getCondition();
	
	$sql = "select * from ecshop.warehouse_service where {$condition} ";
	$register_infos = $db->getAll($sql);
	$register_count = count($register_infos);
	if($register_count==0) {
		$message="没有查询到相关记录";
	}
	$smarty->assign('register_count',$register_count);
	$smarty->assign('register_infos',$register_infos);
}else if($act=='addRegister'){ 
	//登记信息
	$tracking_number = trim($_REQUEST['tracking_number']);
	$sender_name = trim($_REQUEST['sender_name']);
	$sender_phone = trim($_REQUEST['sender_phone']);
	$service_type = $_REQUEST['service_type'];
	$registrant_name = $_SESSION['admin_name'];
	
	$message = '';
	if(!($tracking_number && $sender_name && $sender_phone && $service_type)){
		$message="登记信息不完整,添加失败".$tracking_number.$sender_name.$sender_phone.$service_type;
	}else{
		$sql = "select s.shipment_id,oi.order_id,oi.order_status,oi.shipping_status,oi.order_sn,oi.party_id  
			from romeo.shipment s  
			inner join romeo.order_shipment os on os.shipment_id = s.shipment_id  
			inner join ecshop.ecs_order_info oi on oi.order_id = cast(os.order_id as unsigned)  
			where s.tracking_number = '{$tracking_number}' limit 1  ";
		$original_info = $db->getRow($sql);
		$sql = "select count(*) from ecshop.warehouse_service where tracking_number = '{$tracking_number}' limit 1 ";
		$count_tn = $db->getOne($sql);
		if(empty($original_info) && $service_type=='whole'){
			$message="此快递单号并未匹配到原销售订单，不属于原单退回，请核实";
		}else if(!empty($original_info) && $service_type=='part'){
			$message="此快递单号存在原销售订单，不属于客户自退，请核实";
		}else if($service_type=='whole' && !empty($original_info) && $original_info['order_status']!=1 && $original_info['shipping_status'] != 1){
			$message="原销售订单状态并非“已确认，已发货”，请核实";
		}else if($count_tn>0){
			$message = "此快递单号已经登记过系统，请查询核实";
		}
		if($message==''){
			if($service_type=='whole'){
				//原单退回：根据快递单号 在系统中存在发货且无退货记录订单 
				$original_id = $original_info['order_id'];
				$original_sn = $original_info['order_sn'];
				$original_party = $original_info['party_id'];
				$sql = "SELECT sum(iid.QUANTITY_ON_HAND_DIFF) from  ecshop.service s
					INNER JOIN romeo.inventory_item_detail iid on iid.ORDER_ID = CAST(s.order_id as UNSIGNED)
					where s.back_order_id = {$original_id} and cancellation_flag <> 'Y' and QUANTITY_ON_HAND_DIFF>0 "; //查询原销售订单是否存在已退货记录
				$count_inv = $db->getOne($sql);
				if($count_inv!=0){
					$message="原销售订单对应售后订单存在入库记录".$count_inv;
				}else{
					$insert_sql = "insert into ecshop.warehouse_service(party_id,tracking_number,sender_name,sender_phone,
					warehouse_service_type,warehouse_service_status,original_order_sn,registrant_name,register_time) 
					VALUES({$original_party},'{$tracking_number}','{$sender_name}','{$sender_phone}','whole','INIT'," .
							"'{$original_sn}','{$registrant_name}',now())";
					$db->query($insert_sql);
					$warehouse_service_id = $db->insert_id();
					if(empty($warehouse_service_id) || $warehouse_service_id<=0){
						$message = "插入信息时出现异常，请联系ERP";
					}
				}
			}else{//顾客自退： 快递单号并不存在于系统中
				$insert_sql = "insert into ecshop.warehouse_service(tracking_number,sender_name,sender_phone,
				warehouse_service_type,warehouse_service_status,registrant_name,register_time) 
				VALUES('{$tracking_number}','{$sender_name}','{$sender_phone}','part','INIT','{$registrant_name}',now())";
				$db->query($insert_sql);
				$warehouse_service_id = $db->insert_id();
				if(empty($warehouse_service_id) || $warehouse_service_id<=0){
					$message = "插入信息时出现异常，请联系ERP";
				}
			}
		}
	}
	$json = new JSON();
	print $json->encode($message);
	die();
}
$smarty->assign('message',$message);
$smarty->assign('service_type_all',$service_type_all);
$smarty->assign('service_status_all',$service_status_all);
$smarty->display('warehouse_service.htm');

function getCondition() {
    
    $tracking_number = trim($_REQUEST['tracking_number']);
    $service_type = $_REQUEST['service_type'];
    $service_status = $_REQUEST['service_status'];
    $registrant_name = trim($_REQUEST['registrant_name']);
    $start_date = strtotime($_REQUEST['register_time_start']) > 0 ?$_REQUEST['register_time_start']: '' ;
    $end_date = strtotime($_REQUEST['register_time_end']) > 0 ? $_REQUEST['register_time_end']: '';
    $condition = '';
    if ($start_date || $end_date) {
        $datetime = " register_time ";
        if ($start_date && $end_date) {
            $condition .= " {$datetime} BETWEEN '{$start_date}' "
                    ." AND DATE_ADD('{$end_date}',INTERVAL 1 DAY) " ;
        } elseif ($start_date) {
            $condition .= " AND {$datetime} > '{$start_date}' " ;
            $_REQUEST['register_time_end'] = date('Y-m-d');
        } else{
            $condition .= " AND {$datetime} BETWEEN DATE_ADD('{$end_date}',INTERVAL 1 DAY) " 
            		." AND DATE_ADD('{$end_date}',INTERVAL -7 DAY) ";
            $_REQUEST['register_time_start'] = date('Y-m-d', strtotime("-7 days", strtotime($end_date)));
        }
    }else{
    	$condition .= " register_time >= DATE_ADD(now(),INTERVAL -7 DAY) ";
    	$_REQUEST['register_time_end'] = date('Y-m-d');
    	$_REQUEST['register_time_start'] = date('Y-m-d', strtotime("-7 days"));
    }
    
    if ($tracking_number) {
        $condition .= " AND tracking_number = '{$tracking_number}' ";
    }
    $condition .= " AND warehouse_service_type = '{$service_type}' ";
    $condition .= " AND warehouse_service_status = '{$service_status}' ";
	if($registrant_name){
		$condition .= " AND registrant_name = '{$registrant_name}' ";
	}

    return $condition;
}

