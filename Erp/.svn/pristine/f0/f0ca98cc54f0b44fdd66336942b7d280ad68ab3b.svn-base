<?php
/*
 * Created on 2013-10-10
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('includes/taobaosdk/top/TopClient.php');
require_once('includes/taobaosdk/lotusphp_runtime/Logger/Logger.php');
require_once('includes/taobaosdk/top/request/LogisticsOfflineSendRequest.php');
require_once ('includes/debug/lib_log.php');
function SyncWaybillaction($rec,$taobao_shop_id,$companycode){
	$api_params = get_api_params($taobao_shop_id);
	//$c = new TopClient;
	//arla爱氏晨曦旗舰店
//    $c->appkey = "12491794";
//    $c->secretKey = "afc3d65ae1e7b850a8de129810ee695a";
//    $sessionKey = "61002127b2800efc9b84128a0c635cb3762e5c396db28681677695020";
  /*
    $c->appkey = $api_params['app_key'];
    $c->secretKey = $api_params['app_secret'];
    $sessionKey = $api_params['session_id'];
    Qlog::log($api_params['app_key'].','.$api_params['app_secret'].','. $api_params['session_id']);
	$req = new LogisticsOfflineSendRequest;
	$req->setTid($rec['order_sn']);
	$req->setCompanyCode($companycode);
	$req->setOutSid($rec['bill_sn']);

	$resp = $c->execute($req, $sessionKey);
    return $resp;
   */
   
    // 2014-06-16 jwang  改用聚石塔
    $client = new SoapClient(SYNCJUSHITA_WEBSERVICE_URL . "SyncTaobaoService?wsdl");
    $request = array(
                    'applicationKey' =>$api_params['application_key'],
                    'tid'=>$rec['order_sn'],
    				'sub_tid'=>'',
   					'is_split'=>'0',
                    'company_code'=>$companycode,
                    'out_sid'=>$rec['bill_sn'],
                    'username'=>JSTUsername,'password'=>md5(JSTPassword),
    );
    $response = $client->SyncTaobaoOrderDeliverySend($request)->return;
    return $response;
}
function get_api_params($taobao_shop_id){
	global $db;
	    $sql ="SELECT params.app_key,params.app_secret,params.session_id,conf.nick,conf.application_key from taobao_api_params params
               LEFT JOIN  taobao_shop_conf conf ON conf.taobao_api_params_id = params.taobao_api_params_id
               WHERE taobao_shop_conf_id = '{$taobao_shop_id}' "; 
    $shop =$db->getRow($sql);
    Qlog::log('----taobao_shop_conf-----'.$shop['nick']);
    return $shop;
}
?>
