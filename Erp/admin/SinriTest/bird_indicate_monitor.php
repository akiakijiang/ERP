<?php
define('IN_ECS',true);
require('../includes/init.php');
require_once('monitor_tools.php');

$monitor_header = new MonitorHeader("菜鸟指示订单监控页",array('order_sn','taobao_order_sn'));
$smarty -> assign('monitor_header',$monitor_header);

// $order_sn=trim($_REQUEST['order_sn']);
if(empty($_REQUEST['order_sn'])&&empty($_REQUEST['taobao_order_sn'])){
	$smarty->assign('msg','【菜鸟】请输入taobao_order_sn或order_sn');
}else{
	if(!empty($_REQUEST['order_sn'])){
		$order_sn=$_REQUEST['order_sn'];
	}else{
		$sql="select order_sn from ecshop.ecs_order_info where taobao_order_sn='{$_REQUEST['taobao_order_sn']}'";
		$order_sn=$db->getOne($sql);
	}
	$sql="select count(*) from ecshop.ecs_order_info where order_sn='{$order_sn}'";
	$order_count=$db->getOne($sql);
	if(empty($order_count) || $order_count<=0){
		$smarty->assign('smg','请输入正确的order_sn或taobao_order_sn!');
	}else{
		$smarty->assign('monitor_data',getBirdOrderInfo($order_sn));
	}
}
$smarty->display('SinriTest/common_monitor.htm');

function getBirdOrderInfo($order_sn){
	$sql = "SELECT order_type_id FROM ecshop.ecs_order_info WHERE order_sn='{$order_sn}'";
	$query = mysql_query($sql);
	$type = mysql_result($query,0); //获取订单类型
	
// ================================销售订单，采购订单，供应商退货订单==============================================
	if ($type=="SALE") { //销售订单 
		$var_sql = "eoi.taobao_order_sn";	
		$sql = "SELECT se.change_order_id,se.back_order_id,oi.taobao_order_sn,se.service_type,se.order_id
  			FROM ecshop.service se
  			inner join ecshop.ecs_order_info oi on oi.order_id = se.order_id
   			where oi.order_sn ='{$order_sn}'";
		$query_back = mysql_query($sql);
		$o_order_id = mysql_result($query_back,0,'order_id');//原订单order_id
 		$columnNum = mysql_num_rows($query_back);//结果条数
		if ($columnNum==0) { //若不含退换货订单
			// $all_order = '';
			$str_bc_sn = "eoi.taobao_order_sn";

			// ecshop.ecs_order_info
			$sql = "SELECT * FROM ecshop.ecs_order_info WHERE order_sn ='{$order_sn}'"; //由于测试数据存在order_id=0
			$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟订单】总信息表[ecshop.ecs_order_info]', $sql, 'order_id');
			$service_for_generate[] = $result['monitor_info'];

			// ecshop.express_bird_indicate
			$sql = "SELECT * FROM ecshop.express_bird_indicate ebi
				INNER JOIN ecshop.ecs_order_info eoi
				ON ebi.out_biz_code ={$str_bc_sn}
				WHERE eoi.order_sn = '{$order_sn}'"; 
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟】物流宝订单信息表[ecshop.express_bird_indicate]',$sql,'indicate_id');
			$service_for_generate[] = $result['monitor_info'];
			
			
			// ecshop.express_bird_indicate_detail
			$sql="SELECT * FROM ecshop.express_bird_indicate_detail ebid
				INNER JOIN ecshop.ecs_order_info eoi
				ON ebid.out_biz_code ={$str_bc_sn}
				WHERE eoi.order_sn = '{$order_sn}'"; 
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟】物流宝订单商品信息表[ecshop.express_bird_indicate_detail]',$sql,'indicate_detail_id');
			$service_for_generate[] = $result['monitor_info'];

			// ecshop.express_bird_product
			$sql="SELECT * FROM express_bird_product ebp 
				INNER JOIN express_bird_indicate_detail ebid 
				ON ebp.bird_item_id = ebid.item_id 
				INNER JOIN ecs_order_info eoi 
				ON ebid.out_biz_code ={$str_bc_sn}
				WHERE eoi.order_sn = '{$order_sn}'"; 
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟】菜鸟商品信息表[ecshop.express_bird_product]',$sql,'bird_product_id');
			$service_for_generate[] = $result['monitor_info'];

			// ecshop.express_bird_inventory
			$sql="SELECT * FROM express_bird_inventory ebi 
				INNER JOIN express_bird_indicate_detail ebid 
				ON ebi.item_id = ebid.item_id 
				INNER JOIN ecs_order_info eoi 
				ON ebid.out_biz_code ={$str_bc_sn}
				WHERE eoi.order_sn = '{$order_sn}'"; 
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟】菜鸟商品库存详情表[ecshop.express_bird_inventory]',$sql,'inventory_id');
			$service_for_generate[] = $result['monitor_info'];

			// ecshop.express_bird_actual
			$sql="SELECT * FROM express_bird_actual eba 
				INNER JOIN express_bird_indicate ebi
				ON eba.order_code = ebi.order_code 
				INNER JOIN ecs_order_info eoi 
				ON ebi.out_biz_code ={$str_bc_sn}
				WHERE eoi.order_sn = '{$order_sn}'"; 
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟】物流宝订单实绩表[ecshop.express_bird_actual]',$sql,'order_code');
			$service_for_generate[] = $result['monitor_info'];

			// ecshop.express_bird_actual_detail
			$sql="SELECT * FROM express_bird_actual_detail ebad 
				INNER JOIN express_bird_indicate ebi
				ON ebad.order_code = ebi.order_code 
				INNER JOIN ecs_order_info eoi 
				ON ebi.out_biz_code ={$str_bc_sn}
				WHERE eoi.order_sn = '{$order_sn}'"; 
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟】物流宝订单实绩详情表[ecshop.express_bird_actual_detail]',$sql,'order_goods_id');
			$service_for_generate[] = $result['monitor_info'];


		}elseif ($columnNum>0) {
			for($i=0;$i<$columnNum;$i++){
				$service_type = mysql_result($query_back,$i,'service_type');
				if ($service_type=='1') { //换货
					$all_order_array[2*$i+1] = mysql_result($query_back,$i,'back_order_id');//从第二个
					$all_order_array[2*$i+2] = mysql_result($query_back,$i,'change_order_id');
				}else if($service_type=='2'){ //退货
					$all_order_array[$i+1] = mysql_result($query_back,$i,'back_order_id');
				}
			}
			$taobao_bc = mysql_result($query_back,0,'taobao_order_sn');//he
			if($all_order_array!='' && $all_order_array!=null){
					$str_bc=implode("','",$all_order_array);
					$sql_bc="SELECT order_sn FROM ecshop.ecs_order_info WHERE order_id IN ('{$str_bc}')";
					$query_bc = mysql_query($sql_bc);

					for($i=0;$i<$columnNum;$i++){
						$order_bc= mysql_result($query_bc,$i,'order_sn');
						$start_bc = stripos($order_bc, '-');// 第一次出现 - 的位置
						$more_bc = substr($order_bc , $start_bc);//开始截取 - 及后面所有的字符
						$taobao_bc.=$more_bc;	//给taobao_order_sn后面加N个-t或者-h，N至少为1
						$all_bc_array[$i+1]=$taobao_bc;//将退货订单的taobao_order_sn放入
						$taobao_bc=mysql_result($query_back,0,'taobao_order_sn');
					}
					$all_order_array[0]=$o_order_id;//原订单的order_id放数组第一个
					$all_bc_array[0]=$taobao_bc;//将原订单的taobao_order_sn放数组第一位
					$all_order = implode("','",$all_order_array); // 所有的退货和换货的id
					$str_bc_sn = implode("','",$all_bc_array);//所有的退货和换货的taobao_order_sn
				}
			// ecshop.ecs_order_info
			$sql = "SELECT * FROM ecshop.ecs_order_info WHERE order_sn='{$order_sn}' OR order_id IN ('{$all_order}') ";
			$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟订单】总信息表[ecshop.ecs_order_info]', $sql, 'order_id');
			$service_for_generate[] = $result['monitor_info'];

			// ecshop.express_bird_indicate
			$sql="SELECT * FROM ecshop.express_bird_indicate ebi
				INNER JOIN ecshop.ecs_order_info eoi
				ON ebi.out_biz_code IN ('{$str_bc_sn}') 
				WHERE eoi.order_sn = '{$order_sn}' OR eoi.order_id IN ('{$all_order}') ";  
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟】物流宝订单信息表[ecshop.express_bird_indicate]',$sql,'indicate_id');
			$service_for_generate[] = $result['monitor_info'];
			
			
			// ecshop.express_bird_indicate_detail
			$sql="SELECT * FROM ecshop.express_bird_indicate_detail ebid
				INNER JOIN ecshop.ecs_order_info eoi
				ON ebid.out_biz_code IN ('{$str_bc_sn}')
				WHERE eoi.order_sn = '{$order_sn}' OR eoi.order_id IN ('{$all_order}') "; 
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟】物流宝订单商品信息表[ecshop.express_bird_indicate_detail]',$sql,'indicate_detail_id');
			$service_for_generate[] = $result['monitor_info'];


			// ecshop.express_bird_product
			$sql="SELECT * FROM express_bird_product ebp 
				INNER JOIN express_bird_indicate_detail ebid 
				ON ebp.bird_item_id = ebid.item_id 
				INNER JOIN ecs_order_info eoi 
				ON ebid.out_biz_code IN ('{$str_bc_sn}')
				WHERE eoi.order_sn = '{$order_sn}' OR eoi.order_id IN ('{$all_order}')  "; 
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟】菜鸟商品信息表[ecshop.express_bird_product]',$sql,'bird_product_id');
			$service_for_generate[] = $result['monitor_info'];

			// ecshop.express_bird_inventory
			$sql="SELECT * FROM express_bird_inventory ebi 
				INNER JOIN express_bird_indicate_detail ebid 
				ON ebi.item_id = ebid.item_id 
				INNER JOIN ecs_order_info eoi 
				ON ebid.out_biz_code IN ('{$str_bc_sn}') 
				WHERE eoi.order_sn = '{$order_sn}' OR eoi.order_id IN ('{$all_order}')  "; 
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟】菜鸟商品库存详情表[ecshop.express_bird_inventory]',$sql,'inventory_id');
			$service_for_generate[] = $result['monitor_info'];

			// ecshop.express_bird_actual
			$sql="SELECT * FROM express_bird_actual eba 
				INNER JOIN express_bird_indicate ebi
				ON eba.order_code = ebi.order_code 
				INNER JOIN ecs_order_info eoi 
				ON ebi.out_biz_code IN ('{$str_bc_sn}') 
				WHERE eoi.order_sn = '{$order_sn}' OR eoi.order_id IN ('{$all_order}') "; 
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟】物流宝订单实绩表[ecshop.express_bird_actual]',$sql,'order_code');
			$service_for_generate[] = $result['monitor_info'];

			// ecshop.express_bird_actual_detail
			$sql="SELECT * FROM express_bird_actual_detail ebad 
				INNER JOIN express_bird_indicate ebi
				ON ebad.order_code = ebi.order_code 
				INNER JOIN ecs_order_info eoi 
				ON ebi.out_biz_code IN ('{$str_bc_sn}') 
				WHERE eoi.order_sn = '{$order_sn}' OR eoi.order_id IN ('{$all_order}')  "; 
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【菜鸟】物流宝订单实绩详情表[ecshop.express_bird_actual_detail]',$sql,'order_goods_id');
			$service_for_generate[] = $result['monitor_info'];

		}
	}else{
		if ($type=="PURCHASE") {//采购订单
			$var_sql = "eoi.order_id";
		}else if ($type== "SUPPLIER_RETURN") {//供应商退货订单
			$var_sql = "eoi.order_id";
		}
		// ecshop.ecs_order_info
		$sql = "SELECT * FROM ecshop.ecs_order_info WHERE order_sn='{$order_sn}'";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【菜鸟订单】总信息表[ecshop.ecs_order_info]', $sql, 'order_id');
		$service_for_generate[] = $result['monitor_info'];
		// ecshop.express_bird_indicate
		$sql="SELECT * FROM ecshop.express_bird_indicate ebi
			INNER JOIN ecshop.ecs_order_info eoi
			ON {$var_sql} = ebi.out_biz_code 
			WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【菜鸟】物流宝订单信息表[ecshop.express_bird_indicate]',$sql,'indicate_id');
		$service_for_generate[] = $result['monitor_info'];
			
		// ecshop.express_bird_indicate_detail
		$sql="SELECT * FROM ecshop.express_bird_indicate_detail ebid
			INNER JOIN ecshop.ecs_order_info eoi
			ON {$var_sql} = ebid.out_biz_code 
			WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【菜鸟】物流宝订单商品信息表[ecshop.express_bird_indicate_detail]',$sql,'indicate_detail_id');
		$service_for_generate[] = $result['monitor_info'];

		// ecshop.express_bird_product
		$sql="SELECT * FROM express_bird_product ebp 
			INNER JOIN express_bird_indicate_detail ebid 
			ON ebp.bird_item_id = ebid.item_id 
			INNER JOIN ecs_order_info eoi 
			ON ebid.out_biz_code = {$var_sql}
			WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【菜鸟】菜鸟商品信息表[ecshop.express_bird_product]',$sql,'bird_product_id');
		$service_for_generate[] = $result['monitor_info'];

		// ecshop.express_bird_inventory
		$sql="SELECT * FROM express_bird_inventory ebi 
			INNER JOIN express_bird_indicate_detail ebid 
			ON ebi.item_id = ebid.item_id 
			INNER JOIN ecs_order_info eoi 
			ON ebid.out_biz_code = {$var_sql}
			WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【菜鸟】菜鸟商品库存详情表[ecshop.express_bird_inventory]',$sql,'inventory_id');
		$service_for_generate[] = $result['monitor_info'];

		// ecshop.express_bird_actual
		$sql="SELECT * FROM express_bird_actual eba 
			INNER JOIN express_bird_indicate ebi
			ON eba.order_code = ebi.order_code 
			INNER JOIN ecs_order_info eoi 
			ON ebi.out_biz_code = {$var_sql}
			WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【菜鸟】物流宝订单实绩表[ecshop.express_bird_actual]',$sql,'order_code');
		$service_for_generate[] = $result['monitor_info'];

		// ecshop.express_bird_actual_detail
		$sql="SELECT * FROM express_bird_actual_detail ebad 
			INNER JOIN express_bird_indicate ebi
			ON ebad.order_code = ebi.order_code 
			INNER JOIN ecs_order_info eoi 
			ON ebi.out_biz_code = {$var_sql}
			WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【菜鸟】物流宝订单实绩详情表[ecshop.express_bird_actual_detail]',$sql,'order_goods_id');
		$service_for_generate[] = $result['monitor_info'];
	} 
	return $service_for_generate;
}

?>