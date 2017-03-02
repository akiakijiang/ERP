<?php
/**
 * 用于金宝贝订单已传给基森后取消
 * by ytchen 2015.03.18
 */

define('IN_ECS', true);
require('includes/init.php');
require_once ROOT_PATH . 'data/master_config.php';
require("function.php");
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
global $db;

$act = $_REQUEST['act'];
$json = new JSON;
if ($act == "order_cancel") {
	$order_id = $_REQUEST['order_id'];
	$order_sn = $db->getOne("select order_sn from ecshop.ecs_order_info where order_id = '{$order_id}' limit 1");
	$return = array();
	$sql = "select cancel_info from ecshop.brand_gymboree_cancel_order where erp_order_id = '{$order_id}' limit 1";
	$exist_cancel = $db->getOne($sql);
	if(!empty($exist_cancel) && in_array($exist_cancel,array('T','F02','F01'))){
		if($exist_cancel =='T' || $exist_cancel=='F02'){
			$return['flag'] = 'WMS_SUCCESS';
			//取消成功，取消失败（已取消）
		}else if($exist_cancel =='F01'){
			$return['flag'] = 'SHIP';
			$return['message'] = '仓库已发货，不能取消';
			//取消失败（已发货）
		}
		print $json->encode($return);
    	exit;
	}
	$sql = "select soi.ShipmentID,soi.transfer_status soi_status,soi.transfer_note,soc.sales_order_confirm_id,soc.transfer_status soc_status " .
		" from ecshop.brand_gymboree_sales_order_info soi " .
		" left join ecshop.brand_gymboree_sales_order_confirm soc on soi.ShipmentID = soc.ShipmentID " .
		" where soi.shipmentID = '{$order_id}' limit 1";
	$sale_order = $db->getRow($sql);
	
	if(empty($sale_order) || $sale_order['soi_status']=='NORMAL'){
		$return['flag'] = 'WMS_SUCCESS';
	}else if($sale_order['soi_status']=='SENDED' && $sale_order['soc_status']==''){
		try {
			$sc = new SoapClient(GYMBOREE_WEBSERVICE_URL."?wsdl", array('location' => GYMBOREE_WEBSERVICE_URL,'connection_timeout'=>600)); //FILE_WSDL为要访问的https地址;
			$response = $sc->CancelStorageOut(array('strUserId'=>GYMBOREE_USER_ID,'strPassword'=>GYMBOREE_PASSWORD,'varReceipt_ID'=>$order_sn));
		}catch (Exception $e){
			$return['flag'] = 'FALSE';
			$return['message'] = '连接异常,请联系ERP';
			print $json->encode($return);
    		exit;
		}
		$str = $response->CancelStorageOutResult->any;
		if(strstr($str,"ErrorInfo") == null){
			$return['flag'] = 'FALSE';
			$return['message'] = '取消接口连接异常';
			print $json->encode($return);
    		exit;
		}
		$b= (stripos($str,"<ErrorInfo>"));
		$c= (stripos($str,"</ErrorInfo>"));
		$length = strlen("<ErrorInfo>");
		$response_info = substr($str,$b+$length,$c-$b-$length);
		if(in_array($response_info,array('T','F01','F02','F03','F04'))){
			if(!empty($exist_cancel)){
				$sql = "update ecshop.brand_gymboree_cancel_order set cancel_info='{$response_info}',updated_time=NOW() WHERE erp_order_id = '{$order_id}' ";
			}else{
				$sql = "insert into ecshop.brand_gymboree_cancel_order(erp_order_id,cancel_info,created_time,updated_time,erp_order_sn) values('{$order_id}','{$response_info}',now(),now(),'{$order_sn}') ";
			}
			$db->query($sql);
		}
		//取消结果(定值“T”或“F01”或“F02”或“F03”或“F04”，含义为“T”取消成功、“F01”取消失败（已发货）、“F02”取消失败（已取消）、“F03”取消失败（WMS中未找到）、“F04”取消失败（已关闭）,F04在接口定义中有但基森反馈不存在该状态)
		if($response_info =='T' || $response_info=='F02'){
			$return['flag'] = 'WMS_SUCCESS';
			//取消成功，取消失败（已取消）
		}else if($response_info =='F01'){
			$return['flag'] = 'SHIP';
			$return['message'] = '仓库已发货，不能取消';
			//取消失败（已发货）
		}else if($response_info =='F03'){
			$return['flag'] = 'ERROR';
			$return['message'] = '仓库系统中未找到';
			//状态错误，$return请联系ERP
		}else if($response_info =='F04'){
			$return['flag'] = 'ERROR';
			$return['message'] = '仓库系统已关闭';
			//状态错误，$return请联系ERP
		}else{
			$return['flag'] = 'FALSE';
			$return['message'] = '连接异常，请联系ERP，错误消息:'.$response_info;
		}
	}else{
		$return['flag'] = 'ERROR';
		$return['message'] = '订单状态异常';
	}	
	if($return['flag'] =='ERROR'){
		$sql = "update ecshop.brand_gymboree_sales_order_info set transfer_status = 'ERROR',transfer_note = '{$return['message']}',updated_time=now() where shipmentID = '{$order_id}' ";
		$db->query($sql);
	}
	print $json->encode($return);
    exit;
}else if($act=='update_gymboree_sale_order'){
	$order_id = $_REQUEST['order_id'];
	$sql = "select count(*) from ecshop.brand_gymboree_sales_order_info where shipmentID = '{$order_id}'";
	if($db->getOne($sql)==0){
		print $json->encode(true);
		exit;
	}
	$sql = "update ecshop.brand_gymboree_sales_order_info set transfer_status = 'ERP_CANCEL',updated_time=now() where shipmentID = '{$order_id}' ";
	if($db->query($sql)){
		print $json->encode(true);
	}else{
		print $json->encode(false);
	}
	exit;
}
?>