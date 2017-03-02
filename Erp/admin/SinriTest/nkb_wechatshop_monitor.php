<?php
define('IN_ECS',true);
require('../includes/init.php');
require_once('monitor_tools.php');

$monitor_header = new MonitorHeader("尿裤宝订单监控页",array('order_sn','taobao_order_sn','from_order_id'));
$smarty -> assign('monitor_header',$monitor_header);

// $order_sn=trim($_REQUEST['order_sn']);
if(empty($_REQUEST['order_sn'])&&empty($_REQUEST['taobao_order_sn'])&&empty($_REQUEST['from_order_id'])){
	$smarty->assign('msg','【尿裤宝】请输入taobao_order_sn或order_sn或from_order_id');
}else{
	if(!empty($_REQUEST['order_sn'])){
		$order_sn=$_REQUEST['order_sn'];
		echo($order_sn.'ONE');
	}elseif(!empty($_REQUEST['taobao_order_sn'])){
		$sql="select order_sn from ecshop.ecs_order_info where taobao_order_sn='{$_REQUEST['taobao_order_sn']}'";
		$order_sn=$db->getOne($sql);
		$taobao_order_sn=$_REQUEST['taobao_order_sn'];
	}elseif(!empty($_REQUEST['from_order_id'])) {
		$from_order_id=$_REQUEST['from_order_id'];
	}
	// $sql="select count(*) from ecshop.ecs_order_info where order_sn='{$order_sn}'";
	// $order_count=$db->getOne($sql);
	// if(empty($order_count) || $order_count<=0){
	// 	$smarty->assign('smg','请输入正确的order_sn或taobao_order_sn或from_order_id!');
	// }else{
		$smarty->assign('monitor_data',getNkbOrderInfo($order_sn,$taobao_order_sn,$from_order_id));
	// }
}
$smarty->display('SinriTest/common_monitor.htm');

function getNkbOrderInfo($order_sn,$taobao_order_sn,$from_order_id){
		if($from_order_id==null){
				if($order_sn!==null){
					echo($order_sn.'one');
					// ecshop.sync_weixin_nkb_order_goods
					$sql="SELECT swnog.* FROM ecshop.ecs_order_info eoi 
						INNER JOIN ecshop.sync_weixin_nkb_order_goods swnog
						ON  eoi.taobao_order_sn = CONCAT(swnog.delivery_order_number,'_',swnog.from_order_id)
						WHERE eoi.order_sn='{$order_sn}'";//5172513674
					$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
						'【尿裤宝】订单中间表[ecshop.sync_weixin_nkb_order_goods]',$sql,'delivery_order_number');
					$nkb_order_dates[] = $result['monitor_info'];

					// ecshop.sync_weixin_nkb_order_info
					$sql="SELECT swnoi.* FROM ecshop.ecs_order_info eoi 
						INNER JOIN ecshop.sync_weixin_nkb_order_goods swnog
						ON  eoi.taobao_order_sn = CONCAT(swnog.delivery_order_number,'_',swnog.from_order_id)
						INNER JOIN ecshop.sync_weixin_nkb_order_info swnoi
						ON swnog.delivery_order_number = swnoi.delivery_order_number
						WHERE eoi.order_sn='{$order_sn}'";//5172513674
					$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
						'【尿裤宝】订单商品中间表[ecshop.sync_weixin_nkb_order_info]',$sql,'delivery_order_number');
					$nkb_order_dates[] = $result['monitor_info'];

				}elseif($taobao_order_sn!==null&&$order_sn==null&&$from_order_id==null){ //还未同步进来，只到达中间表
					// ecshop.sync_weixin_nkb_order_goods
					$sql="SELECT swnog.* FROM  ecshop.sync_weixin_nkb_order_goods swnog
							WHERE CONCAT(swnog.delivery_order_number,'_',swnog.from_order_id) ='{taobao_order_sn}'";
					$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
						'【尿裤宝】订单中间表[ecshop.sync_weixin_nkb_order_goods]',$sql,'delivery_order_number');
					$nkb_order_dates[] = $result['monitor_info'];

					// ecshop.sync_weixin_nkb_order_info
					$sql="SELECT swnoi.* FROM ecshop.sync_weixin_nkb_order_info swnoi
						INNER JOIN ecshop.sync_weixin_nkb_order_goods swnog
						ON swnog.delivery_order_number = swnoi.delivery_order_number
						WHERE CONCAT(swnog.delivery_order_number,'_',swnog.from_order_id)='{taobao_order_sn}'";
					$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
						'【尿裤宝】订单商品中间表[ecshop.sync_weixin_nkb_order_info]',$sql,'delivery_order_number');
					$nkb_order_dates[] = $result['monitor_info'];

				}

				// ecshop.ecs_order_info
				$sql = "SELECT * FROM ecshop.ecs_order_info WHERE order_sn='{$order_sn}'";
				$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'【尿裤宝订单】总信息表[ecshop.ecs_order_info]', $sql, 'order_id');
				$nkb_order_dates[] = $result['monitor_info'];

				// ecshop.order_attribute
				$sql="SELECT oa.* FROM  ecshop.ecs_order_info eoi
						INNER JOIN ecshop.order_attribute oa
						ON oa.order_id = eoi.order_id 
						WHERE eoi.order_sn='{$order_sn}'";
				$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'【尿裤宝】尿裤宝订单属性表[ecshop.order_attribute]',$sql,'attribute_id');
				$nkb_order_dates[] = $result['monitor_info'];

				// ecshop.ecs_order_goods
				$sql="SELECT eog.* FROM ecshop.ecs_order_info eoi
						INNER JOIN  ecshop.ecs_order_goods eog
						ON eog.order_id = eoi.order_id 
						WHERE eoi.order_sn = '{$order_sn}'";
				$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'【尿裤宝】尿裤宝订单商品表[ecshop.ecs_order_goods]',$sql,'rec_id');
				$nkb_order_dates[] = $result['monitor_info'];

				// ecshop.order_goods_attribute
				$sql="SELECT oga.* 
					FROM ecshop.ecs_order_info eoi
					INNER JOIN ecshop.ecs_order_goods eog ON eog.order_id = eoi.order_id 
					INNER JOIN ecshop.order_goods_attribute oga ON oga.order_goods_id = eog.rec_id
					WHERE eoi.order_sn='{$order_sn}'";
				$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'【尿裤宝】订单商品属性表[ecshop.order_goods_attribute]',$sql,'order_goods_attribute_id');
				$nkb_order_dates[] = $result['monitor_info'];

				// ecshop.ecs_weixin_order_mapping
				$sql="SELECT ewom.* FROM ecshop.ecs_order_info eoi
					INNER JOIN  ecshop.ecs_weixin_order_mapping ewom
					ON ewom.order_id = eoi.order_id 
					WHERE eoi.order_sn='{$order_sn}'";//5520926273
				$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
					'【尿裤宝】尿裤宝订单映射表[ecshop.ecs_weixin_order_mapping]',$sql,'mapping_id');
				$nkb_order_dates[] = $result['monitor_info'];

		}elseif($from_order_id!==null&&$taobao_order_sn==null&&$order_sn==null){
			// ecshop.sync_weixin_nkb_order_info
			$sql="SELECT swnoi.* FROM ecshop.sync_weixin_nkb_order_info  swnoi
					INNER JOIN ecshop.sync_weixin_nkb_order_goods swnog
					ON swnog.delivery_order_number = swnoi.delivery_order_number
					WHERE swnog.from_order_id='{$from_order_id}'";//NKB196460
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【尿裤宝】订单中间表[ecshop.sync_weixin_nkb_order_info]',$sql,'delivery_order_number');
			$nkb_order_dates[] = $result['monitor_info'];

			// ecshop.sync_weixin_nkb_order_goods
			$sql="SELECT swnog.* FROM ecshop.sync_weixin_nkb_order_goods  swnog
				 WHERE swnog.from_order_id='{$from_order_id}'";//NKB196460
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【尿裤宝】订单中间表[ecshop.sync_weixin_nkb_order_goods]',$sql,'delivery_order_number');
			$nkb_order_dates[] = $result['monitor_info'];

			$sql="SELECT delivery_order_number FROM ecshop.sync_weixin_nkb_order_goods WHERE from_order_id='{$from_order_id}'";
			$query = mysql_query($sql);
	 		$columnNum = mysql_num_rows($query);//结果条数
	 		for($i=0;$i<$columnNum;$i++){
					$delivery_order_number = mysql_result($query,$i,'delivery_order_number');
					$all_order[$i] = $delivery_order_number.'_'.$from_order_id;				
				}
			$all_order = implode("','",$all_order); //所有的taobao_order_sn
			echo('START'.$all_order);

			// ecshop.ecs_order_info
			$sql = "SELECT * FROM ecshop.ecs_order_info WHERE taobao_order_sn IN ('{$all_order}') ";
			$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【尿裤宝订单】总信息表[ecshop.ecs_order_info]', $sql, 'order_id');
			$nkb_order_dates[] = $result['monitor_info'];

			// ecshop.order_attribute
			$sql="SELECT oa.* FROM  ecshop.ecs_order_info eoi
					INNER JOIN ecshop.order_attribute oa
					ON oa.order_id = eoi.order_id 
					WHERE eoi.taobao_order_sn IN ('{$all_order}')";
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【尿裤宝】尿裤宝订单属性表[ecshop.order_attribute]',$sql,'attribute_id');
			$nkb_order_dates[] = $result['monitor_info'];

			// ecshop.ecs_order_goods
			$sql="SELECT eog.* FROM ecshop.ecs_order_info eoi
					INNER JOIN  ecshop.ecs_order_goods eog
					ON eog.order_id = eoi.order_id 
					WHERE eoi.taobao_order_sn IN ('{$all_order}')";
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【尿裤宝】尿裤宝订单商品表[ecshop.ecs_order_goods]',$sql,'rec_id');
			$nkb_order_dates[] = $result['monitor_info'];

			// ecshop.order_goods_attribute
			$sql="SELECT oga.* 
				FROM ecshop.ecs_order_info eoi
				INNER JOIN ecshop.ecs_order_goods eog ON eog.order_id = eoi.order_id 
				INNER JOIN ecshop.order_goods_attribute oga ON oga.order_goods_id = eog.rec_id
				WHERE eoi.taobao_order_sn IN ('{$all_order}')";
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【尿裤宝】订单商品属性表[ecshop.order_goods_attribute]',$sql,'order_goods_attribute_id');
			$nkb_order_dates[] = $result['monitor_info'];

			// ecshop.ecs_weixin_order_mapping
			$sql="SELECT ewom.* FROM ecshop.ecs_order_info eoi
				INNER JOIN  ecshop.ecs_weixin_order_mapping ewom
				ON ewom.order_id = eoi.order_id 
				WHERE eoi.taobao_order_sn IN ('{$all_order}')";//5520926273
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【尿裤宝】尿裤宝订单映射表[ecshop.ecs_weixin_order_mapping]',$sql,'mapping_id');
			$nkb_order_dates[] = $result['monitor_info'];
		}
	return $nkb_order_dates;
}

?>