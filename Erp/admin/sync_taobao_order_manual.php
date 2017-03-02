<?php
/**
 * 最优快递信息分析
 */
define ( 'IN_ECS', true );
require ('includes/init.php');
require_once ('includes/lib_sync_order_manual.php');
if(!in_array($_SESSION['admin_name'],array('qyyao'))){
	echo '不好意思，您没有权限！';
	return;
}



$orderId = $_REQUEST ['orderId']; 
$method = $_REQUEST  ['method']; 
$appkey = $_REQUEST  ['appkey']; 
$hours = $_REQUEST   ['hours']; 
$days = $_REQUEST    ['days']; 
$endDate = $_REQUEST ['endDate']; 
$group = $_REQUEST   ['group']; 
$other = $_REQUEST   ['other']; 

$response_msg = $method ;

echo $method;
static $method_arr =  array(
	'TaobaoOrderTransfer',
	'SyncTaobaoItemStockFromJushita',
	'SyncFinishedOrder',
	'FindBestShipping',
	'FindGiftOrderGoodsList' , 
	'SyncTaobaoItem' , 
	'SyncTaobaoOrder' , 
	'SyncJdOrder' , 
	'JdOrderTransfer' , 
	'SyncJdProduct' , 
	'SyncJdGoodsStock' , 
);




if( empty($method) || $method == ''  ){
	
}
elseif( !in_array($method, $method_arr ) ) {
	$response_msg = '没有该方法可以调用';
}
else if( $method == 'TaobaoOrderTransfer' ){
	$response_msg = $response_msg. TaobaoOrderTransfer($appkey, $days );
}
else if( $method == 'SyncTaobaoItemStockFromJushita' ){
	$response_msg = $response_msg. SyncTaobaoItemStockFromJushita($appkey);
}
else if( $method == 'SyncFinishedOrder' ){
	$response_msg = '该方法暂不提供调用';
	
}
else if( $method == 'FindBestShipping' ){
	$response_msg = '该方法暂不提供调用';
}
else if( $method == 'FindGiftOrderGoodsList' ){
	$response_msg =$response_msg. FindGiftOrderGoodsList($orderId);
}
else if( $method == 'SyncTaobaoItem' ){
	$response_msg =$response_msg. SyncTaobaoItem($orderId);
}
else if( $method == 'SyncTaobaoOrder' ){
	$response_msg =$response_msg. SyncTaobaoOrder( $appkey , $hours, $endDate);
}
else if( $method == 'SyncJdOrder' ){
	$response_msg =$response_msg. SyncJdOrder( $appkey , $hours);
}
else if( $method == 'JdOrderTransfer' ){
	$response_msg =$response_msg. JdOrderTransfer( $appkey , $hours );
}
else if( $method == 'SyncJdProduct' ){
	$response_msg =$response_msg. SyncJdProduct( $appkey , $hours );
}
else if( $method == 'SyncJdGoodsStock' ){
	$response_msg =$response_msg. SyncJdGoodsStock( $appkey);
}





$smarty->assign('response_msg', $response_msg);
$smarty->assign('method_arr', $method_arr);
$smarty->display ( 'oukooext/sync_taobao_order_manual.htm' );


?>