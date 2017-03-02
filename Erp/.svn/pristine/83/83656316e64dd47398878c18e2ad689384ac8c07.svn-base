<?php
/**
 * @package giftTicketApi 
 * @name 	RPC客户端
 * @author 	czhang
 * @datetime 2007-11-22 10:35
 * @copyright 0.5
 */
/*基本配置*/
require_once("RpcController.php");

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

if($sMethodName == 'useGiftTicket'){
	require_once("giftTicket/giftTicketClient.php");
	$objCurrency	=	new giftTicketClient($objRpcContext);
}

if($sMethodName == 'textGiftTicket'){
	require_once("giftTicket/giftTicketClient.php");
	$objCurrency	=	new giftTicketClient($objRpcContext);
}
if($sMethodName	== 'userGiftTicketList'){
	require_once("giftTicket/giftTicketClient.php");
	$objCurrency	=	new giftTicketClient($objRpcContext);
}
if($sMethodName	== 'userGiftTicketCount'){
	require_once("giftTicket/giftTicketClient.php");
	$objCurrency	=	new giftTicketClient($objRpcContext);
}
if($sMethodName	== 'grantorGiftTicket'){
	require_once("giftTicket/giftTicketClient.php");
	$objCurrency	=	new giftTicketClient($objRpcContext);
}
if($sMethodName == 'getUnusedGiftTicketCode'){
    require_once("giftTicket/giftTicketClient.php");
    $objCurrency    =   new giftTicketClient($objRpcContext);
}
if($sMethodName == 'getAndGrantTicketCode'){
    require_once("giftTicket/giftTicketClient.php");
    $objCurrency    =   new giftTicketClient($objRpcContext);
}
if($sMethodName == 'getAllGiftTicketConfig'){
    require_once("giftTicket/giftTicketClient.php");
    $objCurrency    =   new giftTicketClient($objRpcContext);
}
if($sMethodName == 'getGiftTicketConfig'){
    require_once("giftTicket/giftTicketClient.php");
    $objCurrency    =   new giftTicketClient($objRpcContext);
}
?>