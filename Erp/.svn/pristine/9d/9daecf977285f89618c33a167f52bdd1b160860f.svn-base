<?php
define ( 'IN_ECS', true );
require_once ('../includes/init.php');
require_once ('../includes/lib_invoice.php');
require_once ('../distribution.inc.php');
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
admin_priv ( 'print_invoice' );

  //global $db;

	//"select id from ecshop.ecs_invoice_addr where order_id ".db_create_in($array_order_ids);
	$id=$_REQUEST['id'];
	//$sql = "select id from ecshop.ecs_invoice_addr where order_id = '{$order_id}' limit 1";
	//$id = $db->getOne($sql);
	
	$admin_name = $_SESSION['admin_name'];
	$itemId = array('id' => $id,
					'admin_name' => $admin_name
					);
	$invoicePrint = getInvoicePrint($itemId);

	/*
	65553	雀巢
	65559	每伴
	65568	欧世蒙牛
	65574	金宝贝
	*/
	/*
	$party_list = array('65553','65574','65559','65568');
	if (in_array($invoicePrint[0]['party_id'], $party_list)) {
		$start_addr['p'] = "浙江省";
		$start_addr['c'] = "杭州市";
		$start_addr['d'] = "滨江区";
		$start_addr['addr'] = "滨兴路301号A3B区5楼";
	} else {
		$start_addr['p'] = "广东省";
		$start_addr['c'] = "东莞市";
		$start_addr['d']= "长安镇";
		$start_addr['addr'] = "乌沙步步高大道126号一楼101室";
	}
	$start_addr['tel'] = "0571-28189801";
	*/
	/**
	需求内容：
1.  请ERP同学在财务管理-销售发票管理-打印补寄发票界面，业务组ECCO，调整下顺丰面单打印信息，
	寄件地址改为杭州市滨江区江虹路611号1号楼402室，
	电话改为0571-28189826，
	收件人的详细信息，地区代码目的地处为空，
	付款方式改为第三方付，
	月结账号处改为7698041295 ，
	第三方付款地区改为转769FF；
2.  业务组金宝贝，雀巢，雀巢母婴，保乐力加，百事，大王，每伴，百威，支付方式为blackmores-京东，调整下圆通面单打印信息，
	单位名称改为上海乐麦网络科技有限公司，
	寄件地址改为杭州市滨江区江虹路611号1号楼402室，
	电话改为0571-28189826；
	**/
	$party_list = array(
		'65553',
		'65574',
		'65559',
		//'65568',
		'65551',
		'65608',
		'65616',
		'65562',
		'65614'
	);
	if (in_array($invoicePrint[0]['party_id'], $party_list)) {
		$start_addr['p'] = "浙江省";
		$start_addr['c'] = "杭州市";
		$start_addr['d'] = "滨江区";
		$start_addr['addr'] = "江虹路611号1号楼402室";
		$SINRI_FROM_COMPANY="HZCGP";
	} else {
		$start_addr['p'] = "广东省";
		$start_addr['c'] = "东莞市";
		$start_addr['d']= "长安镇";
		$start_addr['addr'] = "乌沙步步高大道126号一楼101室";
		$SINRI_FROM_COMPANY="GDDG";
	}
	$start_addr['tel'] = "0571-28189819";
	$smarty->assign("start_addr", $start_addr);
	$smarty->assign('SINRI_FROM_COMPANY',$SINRI_FROM_COMPANY);
	if($invoicePrint[0]['carrier_id']==3)
	{
		$smarty->assign ('invoicePrint', $invoicePrint);
		$smarty->display ('invoice_manage/yt.htm' );
	}
	if($invoicePrint[0]['carrier_id']==9)
	{
		$smarty->assign ('invoicePrint', $invoicePrint);
		$smarty->display ('invoice_manage/ems.htm' );
	}
	if($invoicePrint[0]['carrier_id']==38)
	{
		$smarty->assign ('invoicePrint', $invoicePrint);
		$smarty->display ('invoice_manage/ghx.htm' );
	}	
	if($invoicePrint[0]['carrier_id']==20)
	{
		$smarty->assign ('invoicePrint', $invoicePrint);
		$smarty->display ('invoice_manage/sto.htm' );
	}
	if($invoicePrint[0]['carrier_id']==10)
	{
		$smarty->assign ('invoicePrint', $invoicePrint);
		$smarty->display ('invoice_manage/sf.htm' );
	}

?>