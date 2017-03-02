<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once ROOT_PATH . 'data/master_config.php';
require_once('orderV2/cls_sales_order_header.php');
require_once('bwshop/lib_bw_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
global $db;
admin_priv('customer_service_manage_order', 'order_view');

define('ERPSYNC_WEBSERVICE_URL',$erpsync_webservice_url);

$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):false;
if($act){
	$json = new JSON;
	if($act == "bird_cancel"){
		$taobao_order_sn = $_REQUEST['taobao_order_sn'];
		$facility_id = $_REQUEST['facility_id'];
		$order_sn = $_REQUEST['order_sn'];
		$pos = strpos($order_sn, '-');
		$out_biz_code="";
		if($pos === false){
			$out_biz_code = $taobao_order_sn;
		}else{
			$out_biz_code = $taobao_order_sn.substr($order_sn,$pos);
		}
	    $sql = "
				SELECT application_key,order_code 
				from ecshop.express_bird_indicate  
				where  out_biz_code='{$out_biz_code}' and indicate_status in('推送成功') limit 1";
		$order =  $db->getRow($sql);
		
		$param_info = array(		
			'applicationKey' => $order['application_key'],
			'orderCode' => $order['order_code']
		);
//		$url='http://localhost:8080/erpsync/SyncBirdExpressService?wsdl'; //本地
		$url=ERPSYNC_WEBSERVICE_URL.'SyncBirdExpressService?wsdl';  //线上
		
		$response = null;
		try {
			$client = new SoapClient($url);
			$response = $client->cancelWlbOrderByERP($param_info);
		}catch (Exception $e) {
			$return['flag'] = 'ERROR';
			$return['message'] = '连接异常，请联系ERP!';	
			print $json->encode($return);
			exit;
		}
	 	
	 	if($response->return){
	 		//调用取消订单接口成功
	 		if($taobao_order_sn){
	 			$sql ="update ecshop.ecs_order_info set facility_id='{$facility_id}' where order_sn='{$order_sn}'";
	 			$db->query($sql);
	 			$sql = "
				update
				ecshop.express_bird_indicate set
				indicate_status='推送成功后转回ERP发货', logistics_status='转回ERP发货', last_updated_stamp=NOW()
				where out_biz_code='{$out_biz_code}' ";
	 		}else{
	 			$sql = "
				update
				ecshop.express_bird_indicate set
				indicate_status='推送成功后取消成功', logistics_status='取消订单不用发货', last_updated_stamp=NOW()
				where out_biz_code='{$out_biz_code}' ";
	 		}
			
			$db->query($sql);
		
			$return['flag'] = 'SUCCESS';
			$return['message'] = '取消成功';	
				
	 	}else{
	 		//调用取消订单接口失败
	 		$return['flag'] = 'ERROR';
			$return['message'] = '订单已进入菜鸟物流发货流程，不能取消!';	
	 	}
	 	print $json->encode($return);
	}
	exit;
}

if(isset($_REQUEST['order_id'])){
	if(!isset($_REQUEST['detail_type'])){
		$order_id = $_REQUEST['order_id'];

		$warning_for_bwshop_order=BWOrderAgent::warningForConfirmErpOrder($order_id);

		$order_line = BWOrderAgent::getBWOrderInfoByErpOrderId($order_id);
		$order_header = new SalesOrderHeader(array('order_id' => $_REQUEST['order_id'], 'action_type'=>'query'));
		$order_header->QueryData();
		$is_kjg = false;
		//判断是否为跨境业务组
		$sql_party_ids = "select party_id from romeo.party where PARENT_PARTY_ID = '65658'";
		$fields_value = array();
		$ref = array();
		$party_ids = $db -> getAllRefby($sql_party_ids,array('party_id'), $fields_value, $ref);	
		if(in_array($order_header->party_id_,$fields_value['party_id'])) {
			$is_kjg = true;
		}
		$history= array();
		$last_history = array();
		if(!empty($order_line)){
			$order_line['custom_history']=json_decode($order_line['custom_history'],true);
			if(isset($order_line['custom_history']['mft']['history'])){
				$order_line_history = $order_line['custom_history']['mft']['history'];
				foreach($order_line_history as $key => $value){
					if(is_array($value)){
						foreach ($value as $name => $zhi){
							if($name == 'Status'){
								$history[$name] = BWOrderAgent::explainShippingStatus($zhi);
							}else{
								$history[$name] = $zhi;
							}
						}
						array_push($last_history,$history);
					}
				}
			}
		}
		// print_r($order_line_history);die();
		$apply_status = BWOrderAgent::explainApplyStatus($order_line['apply_status']);
		$shipping_status = BWOrderAgent::explainShippingStatus($order_line['shipping_status']);
		if(empty($order_line['tracking_number'])){
			$tracking_number = "运单号空";
		}else{
			$tracking_number = $order_line['tracking_number'];
		}
		$smarty->assign('order_line_bw',$order_line);
		$smarty->assign('history',$last_history);
		$smarty->assign('warning_for_bwshop_order',$warning_for_bwshop_order);
		
		if(empty($order_line)){
			$order_line_ = getKuajinggouOrderInfoByErpOrderId($order_id);
			$taobao_order_sn = $order_header->platform_info_->order_sn_;
			$flag_ = issendkuajinggou($order_id);
			$kjg_facility_list = getKjgOrderFacilities();
			if(in_array($flag_['facility_id'],$kjg_facility_list)){//嘉里保达仓222187982  宁波保达仓247410612
				$apply_status = empty($flag_)?"未推送申报系统":"已推送申报系统";
				$shipping_status = $order_line_['status'];
				if(empty($order_line_['logistics_no'])){
					$tracking_number = "运单号空";
				}else{
					$tracking_number = $order_line_['logistics_no'];
				}
				
				$haiguan_history_sql = "select * from ecshop.sync_kjg_order_status_log 
						where taobao_order_sn = '{$taobao_order_sn}' order by created_stamp";
				$haiguan_history = $db -> getAll($haiguan_history_sql);
			}
			$is_baoda = false;
			if(in_array($order_header->facility_id_,$kjg_facility_list)) {
				$is_baoda = true;
			}
		}
		$smarty->assign('is_baoda',$is_baoda);
		$smarty->assign('haiguan_history',$haiguan_history);
		$smarty->assign('is_kjg',$is_kjg);
		$smarty->assign('apply_status',$apply_status);
		$smarty->assign('shipping_status',$shipping_status);
		$smarty->assign('tracking_number',$tracking_number);
		$smarty->assign('order_header', $order_header);
		
		/*===以下代码是先判断订单的配送仓是否为【菜鸟仓】，不是菜鸟仓的进一步判断下曾经是否是菜鸟仓，而过是菜鸟仓则获取流转状态===*/
		$flag=is_in_express_bird($order_id);
		if($flag=="fail"){
			$smarty->assign('liuzhuan_status', 'fail');
		}else if($flag=="erp_send"){
			$smarty->assign('liuzhuan_status', 'erp_send');
		}else if($flag=="cancel_to_erp"){ //推送成功后转回ERP发货
			$smarty->assign('liuzhuan_status', 'cancel_erp_send');
		}else if($flag=="cancel_"){ //推送成功后转回ERP发货
			$smarty->assign('liuzhuan_status', 'cancel_');
		}else if($flag=="send_fail"){ //推送失败
			$smarty->assign('liuzhuan_status', 'send_fail');
		}else if($flag=="success"){
			$bird_order_status = getLiuzhuanStatus($order_id);
			if(!empty($bird_order_status)){
				$smarty->assign('liuzhuan_status', 'success');
				$smarty->assign('liuzhuan_indicate_status', $bird_order_status['indicate_status']);
				$smarty->assign('liuzhuan_logistics_status', $bird_order_status['logistics_status']);
				$smarty->assign('liuzhuan_err_message', $bird_order_status['err_message']);
			}else{
				$smarty->assign('liuzhuan_status', 'not_send');
			}
		}
		$smarty->display('order/order_edit_zjq_xf.htm');
	}else{

	}
}else{
	sys_msg("请输入order_id");
}

function issendkuajinggou($order_id){
	global $db;
	$sql = "select eoi.facility_id from ecshop.sync_kjg_order_info koi
			INNER JOIN ecshop.ecs_order_info eoi on koi.taobao_order_sn = eoi.taobao_order_sn
			where eoi.order_id='{$order_id}' AND eoi.order_type_id='SALE'";
	$line=$db->getRow($sql);
	return $line;
}

function getKjgOrderFacilities() {
		global $db;
	$sql = "select facility_id from romeo.facility
			where is_closed != 'Y'  AND facility_type in ('BONDED_BAODA','BONDED_JIALI')";
	$facilit_list=$db->getCol($sql);
	return $facilit_list;
}
/*
 * 获取上传至跨境购平台的订单状态&运单号
 * by hzhang1 2016-01-11
 */
function getKuajinggouOrderInfoByErpOrderId($erp_order_id){
	global $db;
	$sql="SELECT koi.mft_no,koi.status,koi.logistics_no,
			eoi.order_id erp_order_id,
			eoi.order_sn erp_order_sn,
			eoi.order_status erp_order_status,
			eoi.pay_status erp_pay_status,
			eoi.shipping_status erp_shipping_status
		FROM ecshop.ecs_order_info eoi
		INNER JOIN ecshop.sync_kjg_order_status koi ON eoi.taobao_order_sn=koi.taobao_order_sn 
		AND eoi.order_type_id='SALE'
		WHERE eoi.order_id='{$erp_order_id}' order by koi.created_stamp desc";
	$line=$db->getRow($sql);
	return $line;
}

/*
 * 判断订单的配送仓是否是【菜鸟仓】
 * 如果不是【菜鸟仓】，则需要进一步判断此订单是否在indicate中间表存在记录
 * by hzhang1 2015-10-09
 */
function is_in_express_bird($order_id){
	global $db;
	/*===测试环境的菜鸟仓===*/
//	$sql="select 1 from ecshop.ecs_order_info eoi
//		where eoi.order_id='{$order_id}' and eoi.facility_id in ('144624934','144624935','144624936','144624937','144676339')";

	/*===正式环境的菜鸟仓===*/
	$sql="select 1 from ecshop.ecs_order_info eoi
		where eoi.order_id='{$order_id}' and eoi.facility_id in ('224734292','149849263','149849264','149849265','149849266','173433261','173433262','173433263','173433264','173433265')";
	
	$result=$db->getOne($sql);
	if(empty($result)){
		$sql ="select indicate_status from ecshop.ecs_order_info eoi
		INNER JOIN ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(eoi.order_sn,locate('-',eoi.order_sn))) = ebi.out_biz_code
		where eoi.order_id='{$order_id}' ";
		$result=$db->getOne($sql);
		if(!empty($result)){
			if($result == "由ERP发货无须推送"){
				return "erp_send";
			}else if($result == "推送成功后转回ERP发货"){
				return "cancel_to_erp";
			}else if($result == "推送成功后取消成功"){
				return "cancel_";
			}else if($result == "推送失败"){
				return "send_fail";
			}
		}else{
			return "fail";
		}
	}else{
		return "success";
	}
}

/*
 * 获取配送仓的订单的流转状态信息
 * by hzhang1 2015-10-09
 */
function getLiuzhuanStatus($order_id){
	global $db;
	/*===测试环境的菜鸟仓===*/
//	$sql="select eoi.order_id,eoi.facility_id,ebi.indicate_status,ebi.logistics_status,ebi.err_message from ecshop.ecs_order_info eoi
//		INNER JOIN ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(eoi.order_sn,locate('-',eoi.order_sn))) = ebi.out_biz_code
//		where eoi.order_id='{$order_id}' and eoi.facility_id in ('144624934','144624935','144624936','144624937','144676339')";
	
	/*===正式环境的菜鸟仓===*/
	$sql="select eoi.order_id,eoi.facility_id,ebi.indicate_status,ebi.logistics_status,ebi.err_message from ecshop.ecs_order_info eoi
		INNER JOIN ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn))) = ebi.out_biz_code
		where eoi.order_id='{$order_id}' and eoi.facility_id in ('224734292','149849263','149849264','149849265','149849266','173433261','173433262','173433263','173433264','173433265')";
	
	$line=$db->getRow($sql);
	return $line;
}
?>