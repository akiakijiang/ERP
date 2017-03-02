<?php
/**
 * 京东货到付款热敏接口对接
 */
require_once('init.php');
require_once('admin/function.php');


//function jd_applyBillCode($distributor_id,$count=1){
function jd_applyBillCode($distributor_id,$count=100){
	global $db;	
	include_once(ROOT_PATH . 'RomeoApi/lib_soap.php');

	$erpsync_http_auth_array['trace'] = true;
	if(defined('ERPSYNC_HTTP_USER') && ERPSYNC_HTTP_USER) $erpsync_http_auth_array['login'] = ERPSYNC_HTTP_USER;
	if(defined('ERPSYNC_HTTP_PASS') && ERPSYNC_HTTP_PASS) $erpsync_http_auth_array['password'] = ERPSYNC_HTTP_PASS;
	
	
	//使用京东货到付款店铺
	$jd_distributor_ids = array('2836');
	if(!in_array($distributor_id,$jd_distributor_ids)){
		return false;
	}
	$sql="select tc.application_key,ap.customer_code,ap.taobao_api_params_id from ecshop.taobao_shop_conf tc
		INNER JOIN ecshop.taobao_api_params ap on tc.taobao_api_params_id = ap.taobao_api_params_id
		where tc.status='OK' and tc.shop_type = '360buy' and tc.distributor_id = {$distributor_id} ";
	$taobaoShop = $db->getRow($sql);
	if(empty($taobaoShop)){
		return  false;
	}
	try{
		$soapclient = new SoapClient(ERPSYNC_WEBSERVICE_URL."SyncJdService?wsdl",$erpsync_http_auth_array);
		$request=array("applicationKey"=>$taobaoShop['application_key'],"preNum"=>$count,"customerCode"=>$taobaoShop['customer_code']);
		$response=$soapclient->SyncJdBillCode($request);
	}catch(Exception $e){
		die("请将此连接中断信息告知ERP，谢谢~");
	}
	return true;	
	
}

?>