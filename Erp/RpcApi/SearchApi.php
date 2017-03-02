<?php
/**
 * @package Api
 * @author  lonce<czhang@oukoo.com>
 * @version 0.5
 * @example Api
 */
require_once("RpcController.php");

global $search_rpc_host, $search_rpc_path, $search_rpc_port;


/*个别方法配置*/
define('SEARCH_SIZE',10);
define('SEARCH_START_KEY',0);

if($sMethodName)
{
	$objRpcContext	=	new RpcContext($search_rpc_host, $search_rpc_path, $search_rpc_port);
	if($sMethodName	==	'Search'){
		require_once('SearchServiceClient.php');//模糊搜索
		$ObjSearch	=	new SearchServiceClient($objRpcContext);
	}elseif ($sMethodName	==	'AdvanceSearch'){
		require_once('SearchServiceClient.php');//高级搜索
		$ObjSearch	=	new SearchServiceClient($objRpcContext);
	}elseif ($sMethodName	==	'SearchBBS'){
		require_once('SearchServiceClient.php');//物品评论
		$ObjSearch	=	new SearchServiceClient($objRpcContext);
	}
}
?>