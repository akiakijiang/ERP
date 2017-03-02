<?php
/**
 * 码托解绑
 * 
 * @author jwli 2016.02.24
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');

$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):false;
$exist='0'; //初始化，0为不重复，1为重复
$json = new JSON;  

switch ($act) 
{ 
	case "check_tracking_unbind":
	unset($_REQUEST['act']);
    // $_POST的数据会传递给回调函数
    $result = call_user_func($act, isset($_POST) ? $_POST : null);
    $json = new JSON;
    print $json->encode($result);
    exit;
	break;
}
 /**
 * 解绑码托  jwli 2016.03.01
 */
 function check_tracking_unbind($args) {    
    if (!empty($args)) extract($args);
    $tracking_no = trim($tracking_no);
    $result = tracking_unbind($tracking_no);
    return $result;
 }

/*
 * 解绑码托 jwli 2016.03.01
 * add physical_facility 2016.04.07
 */
 function tracking_unbind($tracking_no){
	global $db;
    //扫描快递单号时再次检查码托是否存在 且 未发货
     $sql = "select psm.shipment_id,p.ship_status,oi.shipping_status,s.status,psm.pallet_no  
		from romeo.pallet_shipment_mapping psm  
		inner join romeo.shipment s  on s.shipment_id = psm.shipment_id  
		inner join romeo.pallet p on psm.pallet_no = p.pallet_no 
        inner join romeo.order_shipment os on s.shipment_id = os.shipment_id  
        inner join ecshop.ecs_order_info oi on oi.order_id = cast(os.order_id as unsigned) 
		where tracking_number = '{$tracking_no}' and psm.bind_status = 'BINDED' limit 1 ";   
    $tra_sql = $db->getRow($sql);
    if(empty($tra_sql)){
		$result['error'] = "该快递单号不存在或者已经解绑！";
		$result['success'] = false;
    } else if($tra_sql["ship_status"]=="SHIPPED"){
        $result['error'] = "该快递单对应码托已经发货！";
        $result['success'] = false; 
    } else if($tra_sql["shipping_status"] == 1 || $tra_sql['status']=='SHIPMENT_SHIPPED'){
		$result['error'] = "解绑失败，该快递单已发货/对应订单已经发货！";
		$result['success'] = false;
	} else{
		$sql = "select count(*) from romeo.pallet_shipment_mapping " .
			" where pallet_no = '{$tra_sql['pallet_no']}' and bind_status = 'BINDED' ";
   		$bind_count = $db->getOne($sql);
   		$db->start_transaction();
   		$sql_1 = "update `romeo`.`pallet` set shipping_id = null,physical_facility = '其他仓' where pallet_no = '{$tra_sql['pallet_no']}' ";
   		$sql_2 = "update `romeo`.`pallet_shipment_mapping` set bind_status = 'UNBINDED' ,unbind_user = '{$_SESSION['admin_name']}' ,unbind_time = now() where shipment_id = '{$tra_sql['shipment_id']}'";
     	
     	if($bind_count==1){
     		if($db->query($sql_1) && $db->query($sql_2)){
     			$db->commit();
     			$result['success'] = true;
     		}else{
     			$db->rollback();
	     		$result['error'] = "解绑失败，系统异常！";
				$result['success'] = false;
     		}
     	}else{
     		if($db->query($sql_2)){
     			$db->commit();
     			$result['success'] = true;
     		}else{
     			$db->rollback();
	     		$result['error'] = "解绑失败，系统异常！";
				$result['success'] = false;
     		}
     	}
    }
    return $result;
 }

$smarty->display('shipment/pallet_unbind.htm');

?>