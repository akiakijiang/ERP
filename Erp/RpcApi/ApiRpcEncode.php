<?php
/**
 * @package PHP Rpc
 * @author  С
 * @version 0.5
 * @example ApiRpcEncode($objRequest)
 */
require_once('RpcEncode.php');
function ApiRpcEncode($objRequest,$sType){
	$objRpc			=	new RpcEncode($objRequest,$sType);
	return $objRpc->EncodeRequest();
}

//print_r(ApiRpcEncode('2788480c43fb421fa153a917fe67cfd6','string'));
//echo('<br>');
//print_r(ApiRpcEncode('19c4a04a6d4e4b8c843a15dd361e0012','string'));
?>