<?php

require_once(ROOT_PATH.'/RomeoApi/lib_soap.php');

global $erp_sync_soapclient;
global $erp_sync_jd_soapclient;

global $erp_taobao_sync_soapclient;


$erpsync_http_auth_array['trace'] = true;
if(defined('ERPSYNC_HTTP_USER') && ERPSYNC_HTTP_USER) $erpsync_http_auth_array['login'] = ERPSYNC_HTTP_USER;
if(defined('ERPSYNC_HTTP_PASS') && ERPSYNC_HTTP_PASS) $erpsync_http_auth_array['password'] = ERPSYNC_HTTP_PASS;

$erp_sync_soapclient = new SoapClient(ERPSYNC_WEBSERVICE_URL."SyncTaobaoService?wsdl", $erpsync_http_auth_array);   //同步淘宝service
$erp_sync_jd_soapclient = new SoapClient(ERPSYNC_WEBSERVICE_URL."SyncJdService?wsdl", $erpsync_http_auth_array);  //同步京东service


//$erp_taobao_sync_soapclient = new SoapClient(SYNCJUSHITA_WEBSERVICE_URL."SyncTaobaoService?wsdl", $erpsync_http_auth_array);


/*
 * 查看订单的最优快递
 * 
 * */
function findOrderBestShip($order_id){
	global $erp_sync_soapclient,$db;

	try{
		$bestShip = $erp_sync_soapclient->FindBestShipping(array('orderId'=>$order_id));
		$bestShip = $bestShip->return;
//		var_dump($bestShip);
	}catch(Exception $e){
		var_dump($e->getMessage());
	}
	
	$bestShip = ob2ar($bestShip);
	
	$data = array();
	// 如果单个对象，则统一返回数组
	if(!isset($bestShip[0])) {
		$data[] = $bestShip;
	} else {
		$data = $bestShip;
	}
//    var_dump($data);
    return $data;
}

/*
 * 淘宝转化订单
 * 
 * */
function TaobaoOrderTransfer( $appkey , $days ){
	global $erp_sync_soapclient,$db;
    $response_msg = '调用结果：';
    
    if(isNullOrEmpty($appkey)){
    	$response_msg = $response_msg. '参数appkey不能为空';
    	return $response_msg;
    }
    if(isNullOrEmpty($days)){
    	$response_msg = $response_msg. '参数days不能为空';
    	return $response_msg;
    }
    
    
	try{
		$transOrder = $erp_sync_soapclient->TaobaoOrderTransfer(array('applicationKey'=>$appkey,'days'=>$days));
		$response_msg = $response_msg.'返回值->'. $transOrder->return;
	}catch(Exception $e){
		$response_msg= $response_msg.$e->getMessage();
	}
    return $response_msg;
}


/*
 * 淘宝同步库存
 * 
 * */
function SyncTaobaoItemStockFromJushita( $appkey ){
	global $erp_sync_soapclient,$db;
    $response_msg = '调用结果：';
    
    if(isNullOrEmpty($appkey)){
    	$response_msg = $response_msg. '参数appkey不能为空';
    	return $response_msg;
    }
	
	try{
		$transOrder = $erp_sync_soapclient->SyncTaobaoItemStockFromJushita(array('applicationKey'=>$appkey ));
		$response_msg = $response_msg.'返回值->'. $transOrder->return;
	}catch(Exception $e){
		$response_msg= $response_msg.$e->getMessage();
	}
    return $response_msg;
}


/*
 * 查找赠品调度
 * 
 * */
function FindGiftOrderGoodsList( $orderId ){
	global $erp_sync_soapclient,$db;
    $response_msg = '调用结果：';
    
    if(isNullOrEmpty($orderId)){
    	$response_msg = $response_msg. '参数orderId不能为空';
    	return $response_msg;
    }
	
	try{
		$transOrder = $erp_sync_soapclient->FindGiftOrderGoodsList(array('orderId'=>$orderId  , 'updateLimitNumber'=>'false'));
		$response_msg = $response_msg.'返回值->'. json_encode($transOrder->return);
	}catch(Exception $e){
		$response_msg= $response_msg.$e->getMessage();
	}
    return $response_msg;
}


/*
 * 同步淘宝订单
 * 
 * */
function SyncTaobaoOrder( $appkey , $hours, $endDate ){
	global $erp_taobao_sync_soapclient,$db;
    $response_msg = '调用结果：';
    
    if(isNullOrEmpty($appkey)){
    	$response_msg = $response_msg. '参数appkey不能为空';
    	return $response_msg;
    }
    if(isNullOrEmpty($hours)){
    	$response_msg = $response_msg. '参数hours不能为空';
    	return $response_msg;
    }
    if(isNullOrEmpty($endDate)){
    	$response_msg = $response_msg. '参数endDate不能为空';
    	return $response_msg;
    }
	
	try{
		$transOrder = $erp_taobao_sync_soapclient->SyncTaobaoOrder(array('applicationKey'=>$appkey  , 'hours'=>$hours , 'endDate'=>$endDate ));
		$response_msg = $response_msg.'返回值->'. $transOrder->return;
	}catch(Exception $e){
		$response_msg= $response_msg.$e->getMessage();
	}
    return $response_msg;
}



/*
 * 同步京东订单
 * 
 * */
function SyncJdOrder( $appkey , $hours ){
	global $erp_sync_jd_soapclient,$db;
    $response_msg = '调用结果：';
    
    if(isNullOrEmpty($appkey)){
    	$response_msg = $response_msg. '参数appkey不能为空';
    	return $response_msg;
    }
    if(isNullOrEmpty($hours)){
    	$response_msg = $response_msg. '参数hours不能为空';
    	return $response_msg;
    }
	
	try{
		$transOrder = $erp_sync_jd_soapclient->SyncJdOrder(array('applicationKey'=>$appkey  , 'hours'=>$hours  ));
		$response_msg = $response_msg.'返回值->'. $transOrder->return;
	}catch(Exception $e){
		$response_msg= $response_msg.$e->getMessage();
	}
    return $response_msg;
}

/*
 * 转化京东订单
 * 
 * */
function JdOrderTransfer( $appkey , $hours ){
	global $erp_sync_jd_soapclient,$db;
    $response_msg = '调用结果：';
    
    if(isNullOrEmpty($appkey)){
    	$response_msg = $response_msg. '参数appkey不能为空';
    	return $response_msg;
    }
    if(isNullOrEmpty($hours)){
    	$response_msg = $response_msg. '参数hours不能为空';
    	return $response_msg;
    }
	
	try{
		$transOrder = $erp_sync_jd_soapclient->JdOrderTransfer(array('applicationKey'=>$appkey  , 'hours'=>$hours  ));
		$response_msg = $response_msg.'返回值->'. $transOrder->return;
	}catch(Exception $e){
		$response_msg= $response_msg.$e->getMessage();
	}
    return $response_msg;
}

/*
 * 同步京东商品
 * 
 * */
function SyncJdProduct( $appkey , $hours ){
	global $erp_sync_jd_soapclient,$db;
    $response_msg = '调用结果：';
    
    if(isNullOrEmpty($appkey)){
    	$response_msg = $response_msg. '参数appkey不能为空';
    	return $response_msg;
    }
    if(isNullOrEmpty($hours)){
    	$response_msg = $response_msg. '参数hours不能为空';
    	return $response_msg;
    }
	
	try{
		$transOrder = $erp_sync_jd_soapclient->SyncJdProduct(array( 'hours'=>$hours , 'applicationKey'=>$appkey   ));
		$response_msg = $response_msg.'返回值->'. $transOrder->return;
	}catch(Exception $e){
		$response_msg= $response_msg.$e->getMessage();
	}
    return $response_msg;
}

/*
 * 同步京东库存
 * 
 * */
function SyncJdGoodsStock( $appkey  ){
	global $erp_sync_jd_soapclient,$db;
    $response_msg = '调用结果：';
    
    if(isNullOrEmpty($appkey)){
    	$response_msg = $response_msg. '参数appkey不能为空';
    	return $response_msg;
    }
	
	try{
		$transOrder = $erp_sync_jd_soapclient->SyncJdGoodsStock(array(  'applicationKey'=>$appkey   ));
		$response_msg = $response_msg.'返回值->'. $transOrder->return;
	}catch(Exception $e){
		$response_msg= $response_msg.$e->getMessage();
	}
    return $response_msg;
}



function ob2ar($obj) {
    if(is_object($obj)) {
        $obj = (array)$obj;
        $obj = ob2ar($obj);
    } elseif(is_array($obj)) {
        foreach($obj as $key => $value) {
            $obj[$key] = ob2ar($value);
        }
    }
    return $obj;
}   

/**
 * 判断字符串是否为空
 */
function isNullOrEmpty( $str ){
	if( empty($str) || $str == ''  ){
		return true;
	}
	return false;
}

?>