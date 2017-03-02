<?php

/**
 * 批量导入模板下载与配送方式模糊查询
 */

define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'admin/distribution.inc.php');

//验证权限
//admin_priv("download_model");

$act = // 动作
isset ( $_REQUEST ['act'] ) && in_array ( $_REQUEST ['act'], array ('download_model_B2B','download_model_order','download_model_waybill','download_model_waybill_out','add_shipment_info','add_payment_info') ) ? $_REQUEST ['act'] : null;

if ($_SERVER ['REQUEST_METHOD'] == 'POST' && $act) {
	switch ($act) {
		case 'download_model_waybill' :
			{
				
				header ( "Content-type:application/vnd.ms-excel" );
				header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "运单批量导入模板" ) . ".csv" );
				$out = "订单编号,运单编号,快递\n";
				echo iconv ( "UTF-8", "GB18030", $out );
				exit ();
			}	
		case 'download_model_waybill_out' :
			{
				
				header ( "Content-type:application/vnd.ms-excel" );
				header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "外包仓运单批量导入模板" ) . ".csv" );
				$out = "淘宝订单编号,运单编号,发货快递编号\n";
				echo iconv ( "UTF-8", "GB18030", $out );
				exit ();
			}	
		case 'download_model_order_csv' :
			{
				header ( "Content-type:application/vnd.ms-excel" );
				header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "订单批量导入模板" ) . ".csv" );
				
				$out = "\n";
				$out .= "\t400000000000085,当当网(电教),许华,淘宝,\t400000000000085,,\t13968047991,浙江,杭州,余杭区,仓前镇仓溢绿苑30幢2单元102室,115,140,18007.066,18147.066,\t6901642051013,,14.476000,32,,\n";
//				$out = "编号,配送方式\n";
//				$sql = " select shipping_id,shipping_name from ecshop.ecs_shipping ";
//				$shipping_list = $db->getAll ( $sql );
//				foreach ( $shipping_list as $key => $shipping ) {
//					$out .= $shipping ['shipping_id'] . "," . $shipping ['shipping_name'] ."\n" ;
//				}
				echo iconv ( "UTF-8", "GB18030", $out );
				exit ();
				
			}
		case 'download_model_B2B' :
			{
				// xls,xlsx格式的
				//$title = array(0=>array('仓库名称','商品条码','退货数量','商品状态'));
				$title = array(0=>array('商品条码','出库数量','商品状态'));
				$data = array(0=>array('6936986833094','4','良品'),
				              1=>array('6936986832097','5','良品'));
				$file_name = 'B2B出库模板.xlsx';
				$type = array();
				for($i=0;$i<count($data[0]);$i++) {
					$type[] = 'string';
				}
				excel_export_model($title,$file_name,$data,$type,'B2B出库清单');
			}
		case 'download_model_order' :
			{
				// xls,xlsx格式的
				$title = array(0=>array('临时订单号','分销商名称','收货人','外部订单类型','外部订单号','联系电话','手机','省','市','区','详细地址','配送方式ID','快递费用','商品金额','订单红包','订单金额','单价','数量','商家编码','支付方式ID','发货仓库ID'));
				$data = array(0=>array('400000000000085','当当网(电教)','许华','淘宝','400000000000085','','13968047991','浙江','杭州','余杭区','仓前镇仓溢绿苑30幢2单元102室','115','140','18007.066','0.000','18147.066','14.476000','32','TC-4334808931','67','92718101'));
				$file_name = '订单批量导入模板.xlsx';
				$type = array();
				for($i=0;$i<count($data[0]);$i++) {
					$type[] = 'string';
				}
				excel_export_model($title,$file_name,$data,$type);
			}
			
		case 'add_shipment_info':
		{
			$shipping_name = $_POST['shipping_name'];
			$sql = " select shipping_id,shipping_name from ecshop.ecs_shipping where shipping_name like '%".$shipping_name."%'";
			$result = $db->getAll($sql);
			if($result)
           		echo json_encode($result);
           	else{
           		echo json_encode(array('error' => '没有匹配到结果'));
           	} 
		 	break;
		}
		
		case 'add_payment_info':
		{
			$pay_name = $_POST['pay_name'];
			$sql = "SELECT pay_id, IF(enabled=1, pay_name, CONCAT(pay_name, ' (已挂起)')) AS pay_name FROM {$ecs->table('payment')} WHERE (enabled = 1 OR enabled_backend = 'Y') AND pay_name like '%".$pay_name."%'";
			$result = $db->getAll($sql);
			if($result) 
				echo json_encode($result);
			else{
				echo json_encode(array('error' => '没有匹配到结果'));
			}
			exit();
			break;
		}		
	}
}


