<?php
define('IN_ECS', true);
require_once __DIR__.'/lib_bw_order.php';
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

if(!in_array($_SESSION['admin_name'],array(
	'ljni',
	'xrlao',
	'lyma',
	'lxiao'
))){
	die('没有权限，找劳祥睿(QQ: 85383087 MOBILE: 18072996560) 还有马利亚和肖丽也能伸出触手。');
}

if($_POST['act']=='kill' && !empty($_POST['order_ta'])){
	$orders=preg_split('/[\s,]+/', $_POST['order_ta']);
	echo "<h3>To kill those orders</h3>";
	echo "<p>".implode(',', $orders)."</p>";
	
	$afx=BWOrderAgent::killOrders($orders);

	if($afx===false){
		echo "<h3>FAILED</h3>";
		Qlog::log('[BWSHOP KILLER]To kill those orders: '.implode(',', $orders).' killed=false BY '.$_SESSION['admin_name']);
	}else{
		echo "<h3>KILLED ".$afx." ORDERS</h>";
		Qlog::log('[BWSHOP KILLER]To kill those orders: '.implode(',', $orders).' killed='.$afx.' BY '.$_SESSION['admin_name']);
	}

	exit();
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>删单触手 - BWSHOP</title>
</head>
<body>
	<h1>BWSHOP删单工具</h1>
	<p>劳祥睿专用删单工具</p>
	<p>因为是无条件删单，</p>
	<hr>
	<form method="POST" target='res_iframe'>
		<p>
			上面填入BW订单号，（不是ERP里的订单号也不是外部平台的订单号），可以在监控页查到。
		</p>
		<p>
			用空格或英文逗号【,】分隔。
		</p>
		<input type="hidden" name="act" value="kill">
		<textarea name="order_ta" style="width:80%;height:100px;"></textarea>
		<p>
			确认没错了就<input type="submit" value="确认">。
		</p>
	</form>
	<hr>
	<iframe src="" name="res_iframe" style="width:100%;height:300px;"></iframe>
</body>
</html>