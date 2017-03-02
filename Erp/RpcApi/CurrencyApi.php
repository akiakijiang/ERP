<?php
/**
 * @package CurrencyApi 
 * @name 	RPC客户端
 * @author 	czhang
 * @datetime 2007-11-22 10:35
 * @copyright 0.5
 */
require_once("RpcController.php");
/*基本配置*/
define('currencyHOST', $membership_rpc_host);
define('currencyPATH', $membership_rpc_path);
define('currencyPORT', $membership_rpc_port);

/*
define('currencyHOST', '192.168.1.135');
define('currencyPATH', '/ouku/web/trunk/RpcService/RpcService.php');
define('currencyPORT', 80);

define('currencyHOST','122.224.141.183');
define('currencyPATH','RpcService/RpcService.php');
define('currencyPORT',81);
*/
$objRpcContext = new RpcContext(currencyHOST, currencyPATH, currencyPORT);
if($sMethodName == 'userIntegral'){
	require_once("currency/currencyClient.php");
	$objCurrency	=	new currencyClient($objRpcContext);
}elseif($sMethodName == 'createPoint'){
	require_once("currency/currencyClient.php");
	$objCurrency	=	new currencyClient($objRpcContext);
}elseif ($sMethodName == 'editPoint'){
	require_once("currency/currencyClient.php");
	$objCurrency	=	new currencyClient($objRpcContext);
}elseif ($sMethodName == 'userCurrencyList'){
	require_once("currency/currencyClient.php");
	$objCurrency	=	new currencyClient($objRpcContext);
}elseif ($sMethodName == 'userCurrencyCount'){
	require_once("currency/currencyClient.php");
	$objCurrency	=	new currencyClient($objRpcContext);
}
?>