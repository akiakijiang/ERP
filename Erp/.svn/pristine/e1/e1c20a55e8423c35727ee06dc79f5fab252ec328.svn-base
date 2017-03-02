<?php
/*
合并订单强制拆迁办准字20141009
All Hail Sinri Edogawa!
*/
define('IN_ECS', true);
require_once('init.php');

$act=$_REQUEST['act'];
$order_id=$_REQUEST['order_id'];
if($act=='force_order_divorce'){

	if(!check_admin_priv('ORDER_DETAILS_FORCE_ORDER_DIVORCE')){
		die(json_encode(array('result'=>'no auth')));
	}
	if(force_orders_divorce($order_id)){
		die(json_encode(array('result'=>'done')));
	}else{
		die(json_encode(array('result'=>'failed')));
	}
}

/**
给定一个order_id
如果这货是一个合并的单子，可以找到shipment对应的一坨order
找到每个order当primary_order_id的shipment
修改shipment状态
恢复order——shipment
**/
function force_orders_divorce($order_id){
	$so=FOD_getOS_by_order($order_id);
	if($so && count($so)>1){
		//这货是合并 可拆
		$orderIds=array();
		foreach ($so as $soid => $soline) {
			$origin_shipment=FOD_getOriginShipmentOfOrder($soline['ORDER_ID']);
			if($origin_shipment && $origin_shipment!=''){
				FOD_recoverOS($soline['ORDER_ID'],$origin_shipment);
				$orderIds[]=$soline['ORDER_ID'];
			}
		}
		FOD_action_append($orderIds);
		return true;
	}else{
		return false;
	}
}

function FOD_getOS_by_order($order_id){
	global $db;
	$sql="SELECT SHIPMENT_ID FROM romeo.order_shipment where ORDER_ID='{$order_id}'";
	$sid=$db->getOne($sql);
	if($sid && $sid!=''){
		$sql="SELECT ORDER_ID,SHIPMENT_ID FROM romeo.order_shipment where SHIPMENT_ID='{$sid}'";
		return $db->getAll($sql);
	}else{
		return array();
	}
}

function FOD_getOriginShipmentOfOrder($order_id){
	global $db;
	$sql="SELECT SHIPMENT_ID from romeo.shipment s where s.PRIMARY_ORDER_ID='{$order_id}'";
	return $db->getOne($sql);
}

function FOD_recoverOS($order_id,$origin_shipment){
	global $db;
	$worker=$_SESSION['admin_name'];
	$restore_shipment_sql="UPDATE romeo.shipment 
		SET `STATUS`='SHIPMENT_INPUT',
			LAST_MODIFIED_BY_USER_LOGIN='{$worker}',
			LAST_UPDATE_STAMP=NOW(),LAST_UPDATE_TX_STAMP=NOW() 
		WHERE SHIPMENT_ID='{$origin_shipment}' and `STATUS`='SHIPMENT_CANCELLED'";
	$r1=$db->exec($restore_shipment_sql);
	$restore_os_sql="UPDATE romeo.order_shipment SET SHIPMENT_ID = '{$origin_shipment}' WHERE ORDER_ID='{$order_id}'";
	$r2=$db->exec($restore_os_sql);
}

function FOD_action_append($orderIds){
	    // 拆分订单增加操作日志 ljzhou 2012.12.19
	    require_once ('../function.php');
		//$orderIds = get_merge_order_ids($this->order_id_);
		$order_ids = implode(',',$orderIds);
		$orderSns = get_order_sns($order_ids);
		$order_sns = implode(',',$orderSns);
		global $db, $ecs;
        $sql = "SELECT order_id, order_status, pay_status, shipping_status, invoice_status "
              ." FROM {$ecs->table('order_info')} WHERE order_id in ({$order_ids})";
        $action_data_list = $db->getAll($sql);
        foreach ($action_data_list as &$value) {
        	# code...
        	$value['action_user'] = $_SESSION['admin_name'];
        	$value['action_time'] = date("Y-m-d H:i:s");
        	$value['action_note'] = "强行拆分订单：" . "{$order_sns}";
		      $set = array();
  		    foreach ($value as $k => $v) {
  		        $set[] = "$k = '$v'";
  		    }
  		    $set = join(", ", $set);
  		    $insert_sql="INSERT INTO ecshop.ecs_order_action SET $set ";
  		    $db->exec($insert_sql);
        }
		return true;
	}

?>