<?php

/**
 * 打印快递面单
 *
 * @param $_REQUEST::type,order_id,service_goods_back,carrier_id,act
 * 
 * $Id: print_shipping_order3.php 66248 2014-05-10 02:21:28Z ljni $
 */

define('IN_ECS', true);
require ('includes/init.php');
require ("function.php");
include_once ('includes/lib_order.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once ("PartyXTelephone.php");

$tpl=$_REQUEST['tpl'];
$act=$_REQUEST['act'];

$order=array(
	'consignee'=>"签收人",
	'province'=>"四川省",
	'city'=>"成都市",
	'district'=>"武侯区",
	'address'=>"诸葛诈术研究学园都市思德路974号",
	'mobile'=>"09060402692",
	'tel'=>"0081-22222222",
	'company_address'=>"上海市,上海市,青浦区 华新镇华志路123号",
	'c_tel'=>"0081-11111111",
	'party_name'=>"测试页测试组织",
	'taobao_order_sn'=>'ALLAHU AKBAR',
	'order_sn'=>'OSNOSNOSN',
	'goods_type'=>'油炸食品',
);

$arata=array(
	'stoSender'=>'Leqee Empire',
	'sentBranch'=>'上海市场部',
	'tracking_number'=>'100000000016',		   
	'service_type'=>'物流产品',
);

$today_mon=idate("m");
$today_day=idate("d");

$smarty->assign('order',$order);
$smarty->assign('arata',$arata);
$smarty->assign('today_mon',$today_mon);
$smarty->assign('today_day',$today_day);

if(empty($tpl)){
	?>
	<p>感谢您协助ERP测试快递面单的打印！在本页面可以设定面单类型，打印测试页</p>
	<p>
		可以选择快捷测试方式：
		<form> <input type='hidden' name='tpl' value="takkyuubin"><input type='hidden' name='act' value="one"><input type='submit' value='宅急便'></form>
		<form> <input type='hidden' name='tpl' value="htky"><input type='hidden' name='act' value="one"><input type='submit' value='汇通'></form>
	</p>
	<p>
		也可以自行输入打印模板代码（ERP测试功能，请在ERP员工指导下使用）：
		<form> <input type='text' name='tpl' value=""><input type='hidden' name='act' value="one"><input type='submit' value='递交'></form>
	</p>
	<hr>
		<p>
			模拟批拣的格式测试：<!-- https://erp_minus_oukoo_erp.i9i8.com/admin/print_shipping_orders.php?order_id=4307516,4307516 -->
			<form> 
				<input type='text' name='tpl' value="">
				<input type='hidden' name='act' value="batch">
				<input type='submit' value='递交'>
			</form>
		</p>
	<hr>
	<p>
		用于ERP调试的打印的快递面单的各项信息如下：
	</p>
	<textarea cols='150' rows='20' readonly='readonly' style="border: none;"><?php echo "order as ";print_r($order); ?></textarea>
	<hr>
	<p>
		FOR ERP DEBUG ONLY! PRINT SHIPPING FOR ORDER<br>
		<form action='print_shipping_order3.php'>
			ORDER ID:<input type='text' name='order_id'>
			ARATA(0/1):<input type='text' name='arata' value="0">
			TRACKING NUMBER (FOR ARATA):<input type='text' name='tracking_number' value="">
			<input type='submit' value='print'>
		</form>
	</p>
	<p>
		FOR ERP DEBUG ONLY! PRINT SHIPPING FOR SHIPMENT<br>
		<!-- http://localhost/erp_minus_oukoo_erp/admin/shipment_print_for_batch_pick.php?print=1&shipment_id=100001271 -->
		<form action="shipment_print_for_batch_pick_new.php" method='POST'>
			SHIPMENT ID:<textarea name='shipment_id'></textarea>
			<input type="hidden" name='print' value='1'>
			<input type='submit' value="print">USE 140421-0111 FOR TEST(104769624,104769626,104772461,104772462)
		</form>
	</p>
	<?php
	die();
}else{
	if($act=='one'){
		$tpl='waybill/'.$tpl.'.htm';
		$smarty->display($tpl);
	}else if($act=='batch'){
		$url="print_shipping_order_test.php?tpl=".$tpl."&act=one";
		$smarty->assign('urls',array($url,$url,$url));
		$smarty->display('waybill/BillListForTest.htm');
	}
}


?>