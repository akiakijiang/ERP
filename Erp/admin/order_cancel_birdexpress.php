<?php
/**
 * 用于物流宝订单取消
 * by qhu 2015.05.19
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
	$return = array();
	
	$sql = "
			SELECT eoi.taobao_order_sn, eoi.order_type_id, eoi.distributor_id
			from ecshop.ecs_order_info eoi
			where eoi.order_id='{$order_id}' limit 1";
	$exist_cancel = $db->getRow($sql);
	
	if(!empty($exist_cancel)){
		$distributor_id=$exist_cancel['distributor_id'];
		//百威业务组织下只有以下店铺需加取消订单判断：'百威英博官方旗舰店','百威啤酒官方旗舰店'
		//金佰利业务组织下只有以下店铺需加取消订单判断：好奇官方旗舰店
		//雀巢业务组织下只有以下店铺需加取消订单判断：雀巢官方旗舰店
		if(!($distributor_id=='1921' || $distributor_id=='2524' || $distributor_id=='317' || $distributor_id== '2507' || $distributor_id=='177')){
			$return['flag'] = 'SUCCESS';
			$return['message'] = '取消成功';
			print $json->encode($return);
		    exit;
		}
		
		$order_type=$exist_cancel['order_type_id'];
		$out_biz_code=$exist_cancel['taobao_order_sn']; //销售订单的取消
		if($order_type=='RMA_RETURN'){
			//退货订单的取消
			$out_biz_code=$exist_cancel['taobao_order_sn'].'-t';
		}
		if($order_type=='RMA_EXCHANGE'){
			//换货订单的取消
			$out_biz_code=$exist_cancel['taobao_order_sn'].'-h';
		}
			
		$sql = "SELECT indicate_status from ecshop.express_bird_indicate  where out_biz_code='{$out_biz_code}' limit 1";
		$can_cancel =  $db->getOne($sql);
		if(!empty($can_cancel)){
			if($can_cancel=='等待推送' || $can_cancel=='等待推送时取消成功'){
				//订单已经存在于中间表中，但是还没推送，可以直接取消，取消之后不会再推送，不用调用取消订单接口
				$sql = "
						update
						ecshop.express_bird_indicate set
						indicate_status='等待推送时取消成功', logistics_status='取消订单不用发货', last_updated_stamp=NOW()
						where out_biz_code='{$out_biz_code}' ";
				$db->query($sql);
				
				$return['flag'] = 'SUCCESS';
				$return['message'] = '取消成功';
				
			}else if($can_cancel=='推送成功'){
				//订单已经推送成功，需要调用取消订单接口
				//通过调用erpsync里面的方法来调用接口 
				$sql = "
						SELECT application_key,order_code 
						from ecshop.express_bird_indicate  
						where  out_biz_code='{$out_biz_code}' limit 1";
				$order =  $db->getRow($sql);
				
				$param_info = array(		
					'applicationKey' => $order['application_key'],
					'orderCode' => $order['order_code']
				);
//				$url='http://localhost:8080/erpsync/SyncBirdExpressService?wsdl'; //本地
				$url='http://192.168.0.21:38080/erpsync/SyncBirdExpressService?wsdl';  //线上
				
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
					$sql = "
						update
						ecshop.express_bird_indicate set
						indicate_status='推送成功后取消成功', logistics_status='取消订单不用发货', last_updated_stamp=NOW()
						where out_biz_code='{$out_biz_code}' ";
					$db->query($sql);
				
					$return['flag'] = 'SUCCESS';
					$return['message'] = '取消成功';	
						
			 	}else{
			 		//调用取消订单接口失败
			 		$return['flag'] = 'ERROR';
					$return['message'] = '订单已进入菜鸟物流发货流程，不能取消!';	
			 	}			 	
			}else if($can_cancel=='推送成功后取消成功'){
				//推送成功后取消成功之后，ERP中恢复订单，再次取消
				$return['flag'] = 'SUCCESS';
				$return['message'] = '取消成功';
			}else{
				$return['flag'] = 'SUCCESS';
				$return['message'] = '取消成功';
			}
		}else{
			//还没存到订单中间表，不用调用取消订单接口
			$return['flag'] = 'SUCCESS';
			$return['message'] = '取消成功';
		}
		
	}else{
		$return['flag'] = 'ERROR';
		$return['message'] = '找不到对应订单';
	}
	
	print $json->encode($return);
    exit;
}
?>