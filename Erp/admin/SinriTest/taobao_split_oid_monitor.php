<?php
define('IN_ECS', true);
require_once(__DIR__.'/../includes/init.php');

global $db;

function getRequest($name,$default=null){
	if(isset($_REQUEST[$name])){
		return $_REQUEST[$name];
	}else{
		return $default;
	}
}

function getTaobaoOids($tid){
	global $db;
	$sql="SELECT
			oid
		FROM
			ecshop.sync_taobao_order_goods
		WHERE
			tid = '{$tid}'
	";
	$stog_oids=$db->getCol($sql);
	return $stog_oids;
}

function getOrderOids($order_id){
	global $db;
	$sql="SELECT DISTINCT
		    og.out_order_goods_id
		FROM
		    ecshop.ecs_order_goods og
		WHERE
		    og.order_id = '{$order_id}'
	";
	$eog_oids=$db->getCol($sql);
	return $eog_oids;
}

function getTaobaoOrderMapping($tbsn){
	global $db;
	$sql="SELECT 
	    outer_order_sn, platform, shipping_status, tracking_numbers
	FROM
	    ecshop.ecs_order_mapping
	WHERE
	    outer_order_sn = '{$tbsn}'
    ";
    $info = $db->getRow($sql);
    return $info;
}

function getTNsOfOrder($order_id){
	global $db;
	$sql="SELECT 
	    tracking_number
	FROM
	    romeo.order_shipment os
	        INNER JOIN
	    romeo.shipment s ON os.shipment_id = s.shipment_id
	WHERE
	    s.status != 'SHIPMENT_CANCELLED'
	        AND os.order_id = '{$order_id}'
    ";
    $tns=$db->getCol($sql);
    return $tns;
}

function processWithTBSN($tbsn){
	global $db;
	if(empty($tbsn)){
		echo "不存在原始淘宝订单号：[".$tbsn."]";
		exit();
	}
	$stog_oids=getTaobaoOids($tbsn);
	$sql="SELECT order_id,order_sn,taobao_order_sn FROM ecshop.ecs_order_info WHERE taobao_order_sn='{$tbsn}' or taobao_order_sn like '{$tbsn}-%' ";
	$orders=$db->getAll($sql);
	foreach ($orders as $key => $order) {
		$orders[$key]['oids']=getOrderOids($order['order_id']);
	}
	?>
<!DOCTYPE html>
<html>
<head>
	<title>Taobao Split OID Monitor</title>
	<style type="text/css">
	table, td, th {
		border-collapse: collapse;
		border: 1px solid gray;
		padding:5px 20px;
	}
	</style>
</head>
<body>
	<?php

	$mapping=getTaobaoOrderMapping($tbsn);
	echo "<p>目前发货状态 shipping_status: [".$mapping['shipping_status']."]</p>";
	echo "<p>目前已同步到淘宝面单号 tracking_numbers: [".$mapping['tracking_numbers']."]</p>";
	echo "<p>以上信息部分平成二十七年九月的订单未登记全。</p>";

	$done_tns=implode('<br>',explode(',', $mapping['tracking_numbers']));

	echo "<table>";
	echo "<thead>";

	echo "<tr><th>快递面单号</th>";
	foreach ($orders as $key => $value) {
		$tns=getTNsOfOrder($value['order_id']);
		echo "<th>";
		echo implode('<br>', $tns);
		echo "</th>";
	}
	echo "</tr>"; 

	echo "<tr><th>{$tbsn}<br>的子订单号（OID）</th>";
	foreach ($orders as $key => $value) {
		echo "<th>".$value['order_id'].
			'<br>'.$value['order_sn'].
			'<br>'.$value['taobao_order_sn'].
			"<hr><a href='../order_edit.php?order_id={$value['order_id']}'>Order Edit</a>
			</th>";
	}
	echo "</tr>";
	
	echo "</thead>";
	echo "<tbody>";
	foreach ($stog_oids as $oid) {
		echo "<tr>";
		echo "<td>".$oid."</td>";
		foreach ($orders as $key => $value) {
			$ari='';
			$bcolor='white';
			foreach ($value['oids'] as $eg_oid) {
				if($eg_oid==$oid){
					$ari='Y';
					$bcolor='#EEEEEE';
					break;
				}
			}
			echo "<td style='background-color:{$bcolor};text-align: center;'>".$ari."</td>";
		}
		echo "</tr>";
	}
	echo "</tbody>";
	echo "</table>";
	?>
	
</body>
</html>
	<?php
	exit();
}

$act=getRequest('act','');
$tbsn=getRequest('tbsn','');
$osn=getRequest('order_sn','');
$oid=getRequest('order_id','');

if($act=='for_tb'){
	processWithTBSN($tbsn);
}elseif($act=='for_order_sn'){
	$sql="SELECT taobao_order_sn FROM ecshop.ecs_order_info WHERE order_sn = '{$osn}' ";
	$taobao_order_sn=$db->getOne($sql);
	$subtagindex=strpos($taobao_order_sn,'-');
	$pure_taobao_order_sn=$taobao_order_sn;
	if($subtagindex!==false){
		$pure_taobao_order_sn=substr($taobao_order_sn, 0,$subtagindex);
	}
	processWithTBSN($pure_taobao_order_sn);
}elseif($act=='for_order_id'){
	$sql="SELECT taobao_order_sn FROM ecshop.ecs_order_info WHERE order_id = {$oid} ";
	$taobao_order_sn=$db->getOne($sql);
	$subtagindex=strpos($taobao_order_sn,'-');
	$pure_taobao_order_sn=$taobao_order_sn;;
	if($subtagindex!==false){
		$pure_taobao_order_sn=substr($taobao_order_sn, 0,$subtagindex);
	}
	processWithTBSN($pure_taobao_order_sn);
}


?>
<!DOCTYPE html>
<html>
<head>
	<title>Taobao Split OID Monitor</title>
</head>
<body>
	<h1>淘宝多订单多面单分单详情监控</h1>
	<p>通过给定的原始淘宝订单号或者ERP单号来查整个外部订单的分单和多面单的商品分布情况。</p>
	<p>现行的淘宝多面单通道里，每一个面单必须包含一部分子订单号（OID）。如果各面单之间有冲突或者其合集不完整，均会导致发货同步出现问题，需要手动去淘宝后台发货。</p>
	<hr>
	<form target='result_iframe' style="display:inline-block;">
		原始淘宝订单号
		<input type='hidden' name='act' value='for_tb'>
		<input type='text' name='tbsn' value=''>
		<input type='submit'>
	</form>
	<form target='result_iframe' style="display:inline-block;">
		ERP订单号
		<input type='hidden' name='act' value='for_order_sn'>
		<input type='text' name='order_sn' value=''>
		<input type='submit'>
	</form>
	<form target='result_iframe' style="display:inline-block;">
		ERP订单ID
		<input type='hidden' name='act' value='for_order_id'>
		<input type='text' name='order_id' value=''>
		<input type='submit'>
	</form>
	<hr>
	<iframe name='result_iframe' src="" style="width:95%;height:400px;border: 1px solid green;"></iframe>
	<h3>分布表解读</h3>
	<p>
		灰底标Y的格子表明其纵向所属的ERP订单包含了横向所属的子订单。
	</p>
	<p>
		如果有子订单的某一行不是有且只有一个灰底标Y的格子，则该淘宝订单在多面单发货时会产生错误。需要手动去淘宝后台发货。
	</p>
	<p>
		这样的问题主要由手工拆单（比如录单）、删改原订单商品等人为原因造成。
	</p>
</body>
</html>