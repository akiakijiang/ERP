<?php

define('IN_ECS', true);
require ('includes/init.php');
require ("function.php");
include_once ('includes/lib_order.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once ("PartyXTelephone.php");
require_once('includes/lib_sf_arata_insure.php');

//上海仓
$shanghaicang = array('194788297','149849259','176053000','137059424','120801050','137059426','12768420', '19568549', '22143846', '22143847','24196974', '3633071', '69897656', '81569822', '81569823', '83972713', '83972714','119603093','119603091');

$dongguancang = array('105142919','19568548','3580046','3580047','49858449','53316284','76065524','83077349');
$beijingcang = array('100170589','42741887','79256821','83077350','119603092');
$chengducang = array("137059428","137059431");
$jiaxingcang = array('149849256');
$wuhancang = array('137059427');
$kangbeifengxian = array('119603094');
$wanlinbeijingcang = array('253372945');
$huaqingwuhancang = array('253372944');
$sql="select facility_id from romeo.facility where is_out_ship = 'Y'";
$waibaocang = $db->getCol($sql);
$shuiguocang = array('185963128','185963130','185963132','185963134','185963136','185963138','185963140','185963142','185963147','185963148');
//使用京东货到付款店铺
$jd_distributor_ids = array('1950','2010');
$sinri_plus = array();

$arata=(isset($_REQUEST['arata'])?$_REQUEST['arata']:0);
$order_id = intval($_REQUEST['order_id']);
$tracking_number = trim($_REQUEST['tracking_number']);
// TODO:select * 去掉，精确到字段，有大坑，o.address和d.address重名，暂时子查询
$sql = "
	SELECT *, o.party_id, o.facility_id, order_sn,o.shipping_id,o.goods_amount,o.taobao_order_sn,o.bonus,ccm.city_code,
	(select ifnull(d.name,'') from ecshop.distributor d where o.distributor_id = d.distributor_id limit 1) as distributor_name
	FROM ecshop.ecs_order_info o 
	INNER JOIN romeo.order_shipment os ON convert(o.order_id using utf8)=os.ORDER_ID
	INNER JOIN romeo.shipment rs ON rs.SHIPMENT_ID = os.SHIPMENT_ID and rs.SHIPPING_CATEGORY = 'SHIPPING_SEND'
	LEFT JOIN ecshop.ecs_shipping s ON s.shipping_id = o.shipping_id
	LEFT JOIN ecshop.ecs_city_code_mapping ccm ON ccm.city_id = o.city  
	LEFT JOIN romeo.party p on p.party_id = o.party_id
	WHERE o.order_id = '{$order_id}'
";

$order = $db->getRow($sql);

$SendNowtime = date("Y-m-d H:i:s");
$order['sendIDUser'] = '';
$order['sendIDCard'] = '';
//12.12-12.20期间电商大会 嘉兴市和郑州市需身份证信息
if($SendNowtime <='2015-12-20 23:59:59'  && ($order['city']==127 || $order['city']==189)){
	if(in_array($order['facility_id'],array('194788297','19568549'))){
		$order['sendIDUser'] = '杨观链';
		$order['sendIDCard'] = '360421199005143630';
	}elseif($order['facility_id'] =='137059426' ){
		$order['sendIDUser'] = '孙亚威';
		$order['sendIDCard'] = '342401199107136514';
	}elseif($order['facility_id'] =='137059427'){
		$order['sendIDUser'] = '张佳男';
		$order['sendIDCard'] = '412825199404101511';
	}elseif($order['facility_id'] =='79256821'){
		$order['sendIDUser'] = '王庆明';
		$order['sendIDCard'] = '110223198208087270';
	}
}

if(in_array($order['facility_id'],$waibaocang)){
	$sql = "select 'out' as tc_code,if(group_name is null or group_name='',concat_ws('_',goods_name,goods_number),concat_ws('_',group_name,group_number)) as sku_num " .
		" from ecshop.ecs_order_goods where order_id = '{$order_id}' ";
}else{
	//检查合并订单
	$order_id_arr = $db->getCol("select DISTINCT os2.order_id
         from romeo.order_shipment os1
		inner join romeo.order_shipment os2 on os1.shipment_id = os2.shipment_id 
        where os1.order_id = '{$order_id}' ");
	$order_id_str = implode("','",$order_id_arr);	
	$sql = "select '' as tc_code,concat_ws('-',if(gs.barcode is null or gs.barcode='' ,g.barcode,gs.barcode),sum(og.goods_number)) as sku_num from ecshop.ecs_order_goods og " .
	" left join ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id and gs.style_id = og.style_id and gs.is_delete=0 " .
	" left join ecshop.ecs_goods g on g.goods_id = og.goods_id " .
	" where og.order_id in ('{$order_id_str}') " .
	" group by og.goods_id,og.style_id ";
}
$goods = $db->getAll($sql); 
if(count($goods)<=5){
	$smarty->assign('goods', $goods);  // 面单上添加对应商品条码及数量
}

if(empty($order['tel']))$order['tel']='';
if(empty($order['mobile']))$order['mobile']=$order['tel'];
// 检查订单合并 计算总的订单金额
if (!empty ($order)) {
	$amoutSQL = "
         select sum(tmp.order_amount) order_amount from (
            select o.order_amount
             from romeo.order_shipment os1
			inner join romeo.order_shipment os2 on os1.shipment_id = os2.shipment_id
			inner join ecshop.ecs_order_info o on o.order_id  =  cast(os2.order_id as unsigned)
            where os1.order_id = '{$order['order_id']}'
          group by o.order_id ) as tmp
       ";

	$order['order_amount'] = $db->getOne($amoutSQL);
}
$order['goods_amount'] = $order['goods_amount'] + $order['bonus'];


// 获取屏蔽号码
convert_mask_phone($order, 'get');


if(!empty($order['CARRIER_ID'])) {
	$carrier_id = $order['CARRIER_ID'];
}else{
	$carrier_id = $db->getOne("select CARRIER_ID from romeo.shipment s
			inner join romeo.order_shipment os on os.shipment_id = s.shipment_id	
			where order_id = '{$order['order_id']}' limit 1 ");
}
if($carrier_id=='62'){//京东COD属于货到付款，需预付款金额（并不等于订单金额）
	$sql = "select order_payment from ecshop.sync_jd_order_info where order_id= '{$order['taobao_order_sn']}' ";
	$order['order_payment'] =$db->getOne($sql); 
	if(empty($order['order_payment'])){
		$order['order_payment'] = $order['order_amount'];
	}
}

$facility_address = facility_convert($order['facility_id']);

// 查询省、城市
$provinceSQL = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['province']}'";
$order['province'] = $db->getOne($provinceSQL);

$citySQL = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['city']}'";
$order['city'] = $db->getOne($citySQL);

$districtSQL = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['district']}'";
$order['district'] = $db->getOne($districtSQL);

$sql = "select p.name from romeo.party p
		left join ecshop.ecs_order_info oi on oi.party_id = p.party_id
		where oi.order_id = '{$order_id}'
";
$order['party_name'] = $db->getOne($sql);
if('65565' == $order['party_id']){
   //乐贝蓝光
   $order['c_tel'] = '0571-28181301';
}else{
   $order['c_tel'] = sinri_get_telephone_for_order_to_print($order_id);
}

// 备注
$order['remarks'] = '';


/**
【ERP需求】 以业务组织为出发维度，对该业务组织发出去的快递单上的联系方式进行更新
ljni@leqee.com 20140429
**/
// 海蓝之谜电话比较特殊
if($order['party_id'] == '65628') {
	$tel_qh = substr($order['c_tel'], 0,3);
    $tel_dh = substr($order['c_tel'], 4);
} else {
	$tel_qh = substr($order['c_tel'], 0,4);
    $tel_dh = substr($order['c_tel'], 5);
}

$smarty->assign('tel_qh',$tel_qh);
$smarty->assign('tel_dh',$tel_dh);

$order['p_time'] = date("Y    m    d ");

$order['goods_type']=sinri_get_goods_type_for_party_to_print($order['party_id'],$order['distributor_id']);

//金额大写转换
$money = Change($order['order_amount']);


//热敏面单模板设定
if($arata==1){
	if(strlen($order['city'])>20){
		$order['bigPen'] = $order['province'].$order['district'];
	}elseif(in_array($order['province'],array('北京','上海','天津','重庆'))){
		$order['bigPen'] = $order['province'].$order['city'];	
	}else{
		$order['bigPen'] = $order['province'].$order['city'].$order['district'];		
	}
	if($carrier_id==41){
		$sql = "SELECT attr_value from ecshop.order_attribute where order_id = {$order_id} and attr_name='ztoBigPen' ";
		$zto_mark = $db->getOne($sql);
		if(!empty($zto_mark)) $order['bigPen'] = $zto_mark;
		$tpl='waybill/zto-arata.htm';
		if(in_array($facility_address, $shanghaicang)){
			$arata=array(
				'ztoSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'上海市场部',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '上海市场部已验视';
		}elseif(in_array($facility_address, $beijingcang)){
			$arata=array(
				'ztoSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'北京业务部',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '北京业务部已验视';
		}elseif(in_array($facility_address, $jiaxingcang)){
			$arata=array(
				'ztoSender'=>'拼好货商城',
				'sentBranch'=>'拼好货商城',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>'生鲜',
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '嘉兴市秀洲区嘉欣丝绸工业园24号';
		}elseif(in_array($facility_address, $chengducang)){
			$arata=array(
				'ztoSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'成都市场部',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$order['c_tel'] ='028-85712080';
			$smarty->assign('arata',$arata);
			$order['company_address'] = '成都市场部已验视';
		}elseif(in_array($facility_address,$waibaocang) || in_array($facility_address,$shuiguocang)){
			$arata=array(
				'ztoSender'=>'乐其',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			if(in_array($facility_address,array('185963127','185963128'))){
				$arata['sentBranch']='北京';
				$order['company_address'] = '北京市大兴区小龙庄路63号';
			}elseif(in_array($facility_address,array('185963131','185963132'))){
				$arata['sentBranch']='浙江嘉兴';
				$order['company_address'] = '浙江省嘉兴市南湖区';
			}elseif(in_array($facility_address,array('185963133','185963134'))){
				$arata['sentBranch']='上海青浦';
				$order['company_address'] = '上海市青浦区青赵公路5009号';
			}elseif(in_array($facility_address,array('185963137','185963138'))){
				$arata['sentBranch']='江苏苏州';
				$order['company_address'] = '江苏省苏州市高新区';
			}elseif(in_array($facility_address,array('185963139','185963140'))){
				$arata['sentBranch']='四川成都';
				$order['company_address'] = '四川省成都市新都区';
			}elseif(in_array($facility_address,array('185963141','185963142'))){
				$arata['sentBranch']='湖北武汉';
				$order['company_address'] = '湖北省武汉市东西湖区';
			}elseif(in_array($facility_address,array('185963146','185963147'))){
				$arata['sentBranch']='广东深圳';
				$order['company_address'] = '广东省深圳市光明新区';
			}elseif(in_array($facility_address,array('185963129','185963130'))){
				$arata['sentBranch']='广东广州';
				$order['company_address'] = '广东省广州市';
			}elseif(in_array($facility_address,array('185963135','185963136'))){
				$arata['sentBranch']='江苏南京';
				$order['company_address'] = '江苏省南京市江宁区';
			}
			$smarty->assign('arata',$arata);
		}
	}else if($carrier_id==20){
		$tpl='waybill/sto-arata.htm';
		if(in_array($facility_address, $beijingcang)){
			$arata=array(
				'stoSender'=>'金百合在线',
				'sentBranch'=>'北京',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '北京市通州区张家湾镇西定福庄182号 01069578550';
		}elseif(in_array($facility_address, $dongguancang)){
			$arata=array(
				'stoSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'东莞',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '广东东莞';
		}
	}else if($carrier_id==28){
		$tpl='waybill/ht-arata.htm';
		if(in_array($facility_address, $shanghaicang)){
			$arata=array(
				'htSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'青浦二部',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '青浦二部已验视';
		}elseif(in_array($facility_address, $beijingcang)){
			$arata=array(
				'htSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'北京通州',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '北京市通州区张家湾镇西定福庄182号';
		}elseif(in_array($facility_address, $kangbeifengxian)){
			$arata=array(
				'htSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'上海奉贤',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '上海市奉贤区金钱公路888号';
		}elseif(in_array($facility_address,$waibaocang) || in_array($facility_address,$shuiguocang)){
			$arata=array(
				'htSender'=>'乐其-'.$order['party_name'],
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			if(in_array($facility_address,array('185963128'))){
				$arata['sentBranch']='北京';
				$order['company_address'] = '北京市大兴区小龙庄路63号';
			}elseif(in_array($facility_address,array('185963127'))){//北京汇通外包  使用新地址，面单获取数据 使用的是 电商服务北京仓
				$arata['sentBranch']='北京';
				$order['company_address'] = '北京市大兴区芦求路后莘庄村立阳路临12号';
			}elseif(in_array($facility_address,array('185963131'))){ // 嘉兴水果仓-外包汇通现由 汇通人员接管，用于【金佰利外包发货流程】
				$arata['sentBranch']='浙江嘉兴';
				$order['company_address'] = '';//嘉兴市南湖区大桥镇工业园区欧嘉路与明新路交叉口
			}elseif(in_array($facility_address,array('185963132'))){
				$arata['sentBranch']='浙江嘉兴';
				$order['company_address'] = '浙江省嘉兴市南湖区';
			}elseif(in_array($facility_address,array('185963134'))){
				$arata['sentBranch']='上海青浦';
				$order['company_address'] = '上海市青浦区青赵公路5009号';
			}elseif(in_array($facility_address,array('185963133'))){ // 上海水果仓-外包汇通现由 汇通人员接管，用于【金佰利&雀巢外包发货流程】
				$arata['sentBranch']='上海青浦';
				$order['company_address'] = '上海市青浦区香大路948号';
				$order['c_tel'] = '021-59229991';
			}elseif(in_array($facility_address,array('185963137','185963138'))){
				$arata['sentBranch']='江苏苏州';
				$order['company_address'] = '江苏省苏州市高新区';
			}elseif(in_array($facility_address,array('185963139','185963140'))){
				$arata['sentBranch']='四川成都';
				$order['company_address'] = '四川省成都市新都区';
			}elseif(in_array($facility_address,array('185963141','185963142'))){
				$arata['sentBranch']='湖北武汉';
				$order['company_address'] = '湖北省武汉市东西湖区';
			}elseif(in_array($facility_address,array('185963146','185963147'))){
				$arata['sentBranch']='广东深圳';
				$order['company_address'] = '广东省深圳市光明新区';
			}elseif(in_array($facility_address,array('185963129','185963130'))){
				$arata['sentBranch']='广东广州';
				$order['company_address'] = '广东省广州市';
			}elseif(in_array($facility_address,array('185963135','185963136'))){
				$arata['sentBranch']='江苏南京';
				$order['company_address'] = '江苏省南京市江宁区';
			}
			$smarty->assign('arata',$arata);
		}elseif(in_array($facility_address, $wanlinbeijingcang)){
			$arata=array(
				'htSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'北京通州',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '';//北京市通州区张家湾镇西定福庄182号
		}
	}else if ($carrier_id==5){
		$tpl='waybill/zjs-arata.htm';
		if(in_array($facility_address,$waibaocang) || in_array($facility_address,$shuiguocang)){
			$arata=array(
				'zjsSender'=>'乐其-'.$order['party_name'],
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			if(in_array($facility_address,array('185963127','185963128'))){
				$arata['sentBranch']='北京';
				$order['company_address'] = '北京市大兴区小龙庄路63号';
			}elseif(in_array($facility_address,array('185963131','185963132'))){
				$arata['sentBranch']='浙江嘉兴';
				$order['company_address'] = '浙江省嘉兴市南湖区';
			}elseif(in_array($facility_address,array('185963133','185963134'))){
				$arata['sentBranch']='上海青浦';
				$order['company_address'] = '上海市青浦区青赵公路5009号';
			}elseif(in_array($facility_address,array('185963137','185963138'))){
				$arata['sentBranch']='江苏苏州';
				$order['company_address'] = '江苏省苏州市高新区';
			}elseif(in_array($facility_address,array('185963139','185963140'))){
				$arata['sentBranch']='四川成都';
				$order['company_address'] = '四川省成都市新都区';
			}elseif(in_array($facility_address,array('185963141','185963142'))){
				$arata['sentBranch']='湖北武汉';
				$order['company_address'] = '湖北省武汉市东西湖区';
			}elseif(in_array($facility_address,array('185963146','185963147'))){
				$arata['sentBranch']='广东深圳';
				$order['company_address'] = '广东省深圳市光明新区';
			}elseif(in_array($facility_address,array('185963129','185963130'))){
				$arata['sentBranch']='广东广州';
				$order['company_address'] = '广东省广州市';
			}elseif(in_array($facility_address,array('185963135','185963136'))){
				$arata['sentBranch']='江苏南京';
				$order['company_address'] = '江苏省南京市江宁区';
			}
			$smarty->assign('arata',$arata);
		}
	}else if($carrier_id==3){
		$tpl='waybill/yto-arata.htm';
		$sql = "select attr_value from ecshop.order_attribute where attr_name='ytoBigPen' and order_id = '{$order_id}' and attr_value!='' limit 1";
		$ytoBigPen =  $db->getOne($sql);
		if(!empty($ytoBigPen)) $order['bigPen'] = $ytoBigPen;
		if(in_array($facility_address, $wuhancang)){
			$arata=array(
				'ytoSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'湖北武汉',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '湖北省武汉市东西湖区张柏路210号';
		}elseif(in_array($facility_address,$huaqingwuhancang)){
			$arata=array(
				'ytoSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'湖北武汉',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '';//湖北省武汉市东西湖区张柏路220号
		}elseif(in_array($facility_address, $dongguancang)){
			$arata=array(
				'ytoSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'广东东莞',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '长安镇步步高大道126号';
		}elseif(in_array($facility_address,$waibaocang) || in_array($facility_address,$shuiguocang)){
			$arata=array(
				'ytoSender'=>'乐其-'.$order['party_name'],
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			if(in_array($facility_address,array('185963127','185963128'))){
				$arata['sentBranch']='北京';
				$order['company_address'] = '北京市大兴区小龙庄路63号';
			}elseif(in_array($facility_address,array('185963131'))){ // 嘉兴外包仓
				$arata['sentBranch']='浙江嘉兴';
				$order['company_address'] = '';//嘉兴市南湖区大桥镇工业园区欧嘉路与明新路交叉口
			}elseif(in_array($facility_address,array('185963132'))){
				$arata['sentBranch']='浙江嘉兴';
				$order['company_address'] = '浙江省嘉兴市南湖区';
			}elseif(in_array($facility_address,array('185963133','185963134'))){
				$arata['sentBranch']='上海青浦';
				$order['company_address'] = '上海市青浦区青赵公路5009号';
			}elseif(in_array($facility_address,array('185963137','185963138'))){
				$arata['sentBranch']='江苏苏州';
				$order['company_address'] = '江苏省苏州市高新区';
			}elseif(in_array($facility_address,array('185963139','185963140'))){
				$arata['sentBranch']='四川成都';
				$order['company_address'] = '四川省成都市新都区';
			}elseif(in_array($facility_address,array('185963141','185963142'))){
				$arata['sentBranch']='湖北武汉';
				$order['company_address'] = '湖北省武汉市东西湖区';
			}elseif(in_array($facility_address,array('185963146','185963147'))){
				$arata['sentBranch']='广东深圳';
				$order['company_address'] = '广东省深圳市光明新区';
			}elseif(in_array($facility_address,array('185963129','185963130'))){
				$arata['sentBranch']='广东广州';
				$order['company_address'] = '广东省广州市';
			}elseif(in_array($facility_address,array('185963135','185963136'))){
				$arata['sentBranch']='江苏南京';
				$order['company_address'] = '江苏省南京市江宁区';
			}
			$smarty->assign('arata',$arata);
		}
	}else if($carrier_id == 61){
		$tpl='waybill/sd-arata.htm';
		if(in_array($facility_address, $beijingcang)){
			$arata=array(
				'sentBranch'=>'北京通州',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '北京市通州区张家湾镇西定福庄182号';
		}
	}else if($carrier_id == 29){
		$sql = " select * from ecshop.ecs_order_yunda_mailno_apply " .
		    " where tracking_number in ('{$tracking_number}')";
		$yunda_order = $db->getRow($sql);
		if(in_array($order['facility_id'],array('24196974','137059426'))){
		    $apply_mails=$yunda_order['pdf_info'];
			$smarty->assign('pdf_infos',$apply_mails);
			$tpl='waybill/yd-arata.htm';
		}elseif(in_array($facility_address, $shanghaicang)){
			$arata=array(
				'ydSender'=>'乐其-'.$order['party_name'],
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('yunda_order',$yunda_order);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '上海市青浦区';
			$tpl='waybill/yunda-arata.htm';
		}elseif(in_array($facility_address,$waibaocang) || in_array($facility_address,$shuiguocang)){
			$arata=array(
				'ydSender'=>'乐其-'.$order['party_name'],
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			if(in_array($facility_address,array('185963127','185963128'))){
				$order['company_address'] = '北京市大兴区';
			}elseif(in_array($facility_address,array('185963131','185963132'))){
				$order['company_address'] = '浙江省嘉兴市';
			}elseif(in_array($facility_address,array('185963133','185963134'))){
				$order['company_address'] = '上海市青浦区';
			}elseif(in_array($facility_address,array('185963137','185963138'))){
				$order['company_address'] = '江苏省苏州市';
			}elseif(in_array($facility_address,array('185963139','185963140'))){
				$order['company_address'] = '四川省成都市';
			}elseif(in_array($facility_address,array('185963141','185963142'))){
				$order['company_address'] = '湖北省武汉市';
			}elseif(in_array($facility_address,array('185963146','185963147'))){
				$order['company_address'] = '广东省深圳市';
			}elseif(in_array($facility_address,array('185963129','185963130'))){
				$order['company_address'] = '广东省广州市';
			}elseif(in_array($facility_address,array('185963135','185963136'))){
				$order['company_address'] = '江苏省南京市';
			}
			$smarty->assign('yunda_order',$yunda_order);
			$smarty->assign('arata',$arata);
			$tpl='waybill/yunda-arata.htm';
		}
	}else if ($carrier_id == 10 || $carrier_id==44){
		$tpl='waybill/sf-arata.htm';
		$order['sf_note'] = false;
		if($order['party_id']=='65670'){//65670
			$order['sf_note'] = true;
		}
		// var_dump($facility_address);die();
		if (in_array($order['facility_id'], array('137059426','120801050','137059424','176053000'))) { //上海精品仓
			$arata=array(
				'sentBranch'=>'LEQEE',
				'tracking_number'=>$tracking_number,
				'date'=>date('Y-m-d'),
				'time'=>date('Y-m-d H:i'),
				'sender'=>'LEQEE',
				'sf_account'=>'0219175065',
				'sf_payment_method'=>'寄付月结转第三方付',
				'sf_tp_areacode'=>'021LK',
			);
			
			$order['company_address']='上海市青浦区新丹路359号5栋4楼';//精品
			$order['send_addr_code']='021'; //仓库地址码
			$order['station_no']=$order['city_code'];//收件地址码
			
			//保价
			$order['insurance']=SFArataInsure::getInsuranceForOrder($order_id);//$order['order_amount'];//0;//0 is not in insurance
			$order['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($order['is_sf_cod']==false){
				$order['is_sf_cod']=false;
				$order['sf_cod_note']='￥0.00';
			}
		}elseif($order['facility_id']=='185963134'){//水果上海仓
			$arata=array(
				'sentBranch'=>'LEQEE',
				'tracking_number'=>$tracking_number,
				'date'=>date('Y-m-d'),
				'time'=>date('Y-m-d H:i'),
				'sender'=>'LEQEE',
				'sf_account'=>'0219175065',
				'sf_payment_method'=>'寄付月结',
				'sf_tp_areacode'=>'',
			);
			
			$order['company_address']='上海市青浦区青赵公路5009号北面车间'; 
			$order['send_addr_code']='021LK'; //仓库地址码
			$order['station_no']=$order['city_code'];//收件地址码
			
			//保价
			$order['insurance']=SFArataInsure::getInsuranceForOrder($order_id);//$order['order_amount'];//0;//0 is not in insurance
			$order['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($order['is_sf_cod']==false){
				$order['is_sf_cod']=false;
				$order['sf_cod_note']='￥0.00';
			}
		}elseif ($order['facility_id']=='3580047') {//乐其东莞仓
			$arata=array(
				'sentBranch'=>'LEQEE',
				'tracking_number'=>$tracking_number,
				'date'=>date('Y-m-d'),
				'time'=>date('Y-m-d H:i'),
				'sender'=>'LEQEE',
				'sf_account'=>'7698041295',
				'sf_payment_method'=>'寄付月结',
				'sf_tp_areacode'=>'',
			);
			
			$order['company_address']='广东省东莞市长安镇步步高大道126号';//水果是  徐春建 13764432969
			$order['send_addr_code']='769FF'; //仓库地址码
			$order['station_no']=$order['city_code'];//收件地址码
			
			//保价
			$order['insurance']=SFArataInsure::getInsuranceForOrder($order_id);//$order['order_amount'];//0;//0 is not in insurance
			$order['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($order['is_sf_cod']==false){
				$order['is_sf_cod']=false;
				$order['sf_cod_note']='￥0.00';
			}
		}elseif (in_array($order['facility_id'], array('185963138'))) { //双11苏州水果仓-正常
			$arata=array(
				'sentBranch'=>'LEQEE',
				'tracking_number'=>$tracking_number,
				'date'=>date('Y-m-d'),
				'time'=>date('Y-m-d H:i'),
				'sender'=>'LEQEE',
				'sf_account'=>'0219175065',
				'sf_payment_method'=>'寄付月结转第三方付',
				'sf_tp_areacode'=>'021LK',
			);
			
			$order['company_address']='江苏省苏州市高新区浒墅关工业园青花路128号安博物流园4号库';
			$order['send_addr_code']='512'; //仓库地址码
			$order['station_no']=$order['city_code'];//收件地址码
			
			//保价
			$order['insurance']=SFArataInsure::getInsuranceForOrder($order_id);//$order['order_amount'];//0;//0 is not in insurance
			$order['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($order['is_sf_cod']==false){
				$order['is_sf_cod']=false;
				$order['sf_cod_note']='￥0.00';
			}
		}elseif (in_array($order['facility_id'], array('19568549','194788297'))) { // 嘉善仓（电商服务嘉善仓，电商服务上海仓）
			$arata=array(
				'sentBranch'=>'LEQEE',
				'tracking_number'=>$tracking_number,
				'date'=>date('Y-m-d'),
				'time'=>date('Y-m-d H:i'),
				'sender'=>'LEQEE',
				'sf_account'=>'7698041295',
				'sf_payment_method'=>'寄付月结转第三方付',
				'sf_tp_areacode'=>'E769FF',
			);
			
			$order['company_address']='浙江省嘉善县惠民街道松海路88号晋亿物流集团2号仓库';//嘉善仓
			$order['send_addr_code']='573TH'; //仓库地址码
			$order['station_no']=$order['city_code'];//收件地址码
			
			//保价
			$order['insurance']=SFArataInsure::getInsuranceForOrder($order_id);//$order['order_amount'];//0;//0 is not in insurance
			$order['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			
			//不到转寄
			$order['sf_note_js'] = "不到转寄";
			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($order['is_sf_cod']==false){
				$order['is_sf_cod']=false;
				$order['sf_cod_note']='￥0.00';
			}
		}elseif (in_array($order['facility_id'], array('253372943'))) { // 水果深圳仓
			$arata=array(
				'sentBranch'=>'LEQEE',
				'tracking_number'=>$tracking_number,
				'date'=>date('Y-m-d'),
				'time'=>date('Y-m-d H:i'),
				'sender'=>'LEQEE',
				'sf_account'=>'7698041295',
				'sf_payment_method'=>'寄付月结转第三方付',
				'sf_tp_areacode'=>'E769FF',
			);
			
			$order['company_address']='广东省深圳市光明新区塘家大道6号'; 
			$order['send_addr_code']='755H'; //仓库地址码
			$order['station_no']=$order['city_code'];//收件地址码
			
			//保价
			$order['insurance']=SFArataInsure::getInsuranceForOrder($order_id);//$order['order_amount'];//0;//0 is not in insurance
			$order['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			
			//不到转寄
			$order['sf_note_js'] = "不到转寄";
			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($order['is_sf_cod']==false){
				$order['is_sf_cod']=false;
				$order['sf_cod_note']='￥0.00';
			}
		}elseif($order['facility_id']=='185963128'){//水果北京仓
			$arata=array(
				'sentBranch'=>'LEQEE',
				'tracking_number'=>$tracking_number,
				'date'=>date('Y-m-d'),
				'time'=>date('Y-m-d H:i'),
				'sender'=>'LEQEE',
				'sf_account'=>'7698041295',
				'sf_payment_method'=>'寄付月结转第三方付',
				'sf_tp_areacode'=>'E769FF',
			);
			
			$order['company_address']='北京市大兴区后辛庄村立阳路临12号';
			$order['send_addr_code']='010BJJ'; //仓库地址码
			$order['station_no']=$order['city_code'];//收件地址码
			
			//保价
			$order['insurance']=SFArataInsure::getInsuranceForOrder($order_id);//$order['order_amount'];//0;//0 is not in insurance
			$order['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($order['is_sf_cod']==false){
				$order['is_sf_cod']=false;
				$order['sf_cod_note']='￥0.00';
			}
		}
		
		if($order['party_id']=='65690'){//La Prairie 莱珀妮  65690
			$order['company_address']='上海市青浦区新丹路359号5栋5楼';
			$arata['sender']='';
			$arate['sentBranch'] = '';
		}
		if($carrier_id==10){
			$arata['service_type'] ='顺丰次日';
		}else if($carrier_id==44){
			$arata['service_type'] ='顺丰隔日';
		}else{
			$arata['service_type'] ='顺丰特惠';
		}
		$smarty->assign('arata',$arata);
	}
}
$smarty->assign('order', $order); // 订单信息
$smarty->display($tpl);
/**
 * 查询不同组织的配送模板
 * 模板的定义应该为   组织ID#仓库ID_模板名,  如   4#74539_ems-x.htm, 120_fh-x.htm
 * 
 * @param int $party_id 订单的party_id
 * @param string $tpl 基础模版名
 */
function print_shipping_order_search_template($party_id, $facility_id, $tpl) 
{
	global $smarty;
	$dir = 'waybill/';
	// 可以容忍的party_id
	$PARTY_ID = array (
		$party_id
	);
    
    
	foreach (array_keys(party_list(PARTY_ALL)) as $parent_party_id) { // 取得订单PARTY的父PARTY
		if (party_check($parent_party_id, $party_id)) {
			array_push($PARTY_ID, $parent_party_id);
			break;

		}
	}
	// 可容忍的facility_id
	$FACILITY_ID = array (
		$facility_id
	);
	// 配置搜索模板的级别
	$levels = array (
		'PARTY_ID' => $PARTY_ID,
		'FACILITY_ID' => $FACILITY_ID
	);
    
	for ($i = count($levels); $i > 0; $i--) {
		$level = array_slice($levels, 0, $i, TRUE);
		if (isset ($level['FACILITY_ID'])) {
			foreach ($level['PARTY_ID'] as $pid) {
				foreach ($level['FACILITY_ID'] as $fid) {
					$htm = $pid . '#' . $fid . '_' . $tpl;
					if ($smarty->template_exists($dir . $htm)) {
						$tpl = $htm;
						break;
					}
				}
			}
		} else {
			foreach ($level['PARTY_ID'] as $pid) {
				$htm = $pid . '_' . $tpl;
				if ($smarty->template_exists($dir . $htm)) {
					$tpl = $htm;
					break;
				}
			}
		}
	}

	return $dir . $tpl;
}
function Change($order_amount = 0){
    //$str = "零壹贰叁肆伍陆柒捌玖";
    $str = array(   "0" => '零',
                    "1" => '壹',
                    "2" => '贰',
                    "3" => '叁',
                    "4" => '肆',
                    "5" => '伍',
                    "6" => '陆',
                    "7" => '柒',
                    "8" => '捌',
                    "9" => '玖',
                  );
    
    $order_amount = str_pad(strval(round($order_amount)), 5, '0', STR_PAD_LEFT); 
    $order_amount_arr = str_split($order_amount);

	foreach ($order_amount_arr as $singleChar) {
		$money .= $str[$singleChar];
	}

	return $money;
}
?>