<?php

require_once(ROOT_PATH.'/RomeoApi/lib_soap.php');

global $erp_sync_soapclient;

$erpsync_http_auth_array['trace'] = true;
if(defined('ERPSYNC_HTTP_USER') && ERPSYNC_HTTP_USER) $erpsync_http_auth_array['login'] = ERPSYNC_HTTP_USER;
if(defined('ERPSYNC_HTTP_PASS') && ERPSYNC_HTTP_PASS) $erpsync_http_auth_array['password'] = ERPSYNC_HTTP_PASS;

$erp_sync_soapclient = new SoapClient(ERPSYNC_WEBSERVICE_URL."SyncTaobaoService?wsdl", $erpsync_http_auth_array);

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
 * 转化订单
 * 
 * */
function TaobaoOrderTransfer(){
	global $erp_sync_soapclient,$db;
	$jinbaili = 'f1cfc3f7859f47fa8e7c150c2be35bfc';
	$quechao = '62f6bb9e07d14157b8fa75824400981f';

	try{
		$transOrder = $erp_sync_soapclient->TaobaoOrderTransfer(array('applicationKey'=>$quechao,'days'=>'500'));
		$transOrder = $transOrder->return;
		var_dump($transOrder);
	}catch(Exception $e){
		var_dump($e->getMessage());
	}
    return $transOrder;
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

?>