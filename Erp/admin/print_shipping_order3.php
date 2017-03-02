<?php

/**
 * 打印快递面单
 */

$t=array();
$t[0]=microtime(true);

define('IN_ECS', true);
// require_once ('includes/init.php');
require_once ('includes/mini_init.php');

$t[1]=microtime(true);

require_once ("function.php");

$t[2]=microtime(true);

include_once ('includes/lib_order.php');

$t[3]=microtime(true);

require_once (ROOT_PATH . 'includes/debug/lib_log.php');

$t[4]=microtime(true);

require_once ("PartyXTelephone.php");
require_once('includes/lib_sf_arata_insure.php');

$t[5]=microtime(true);

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
$type = trim($_REQUEST['type']);
//使用京东货到付款店铺
$jd_distributor_ids = array('2836');

$sinri_plus = array();

$arata=(isset($_REQUEST['arata'])?$_REQUEST['arata']:0);
//热敏面单打印时，判断是原始运单还是追加运单，来决定使用不同的运单号
$isAdd=(isset($_REQUEST['isAdd'])?$_REQUEST['isAdd']:0);
//再次打印追加的热敏面单时，判断再次打印的是第几次追加的面单
$pici=(isset($_REQUEST['pici'])?intval($_REQUEST['pici']):0);
// 从哪个页面过来
$from_url=(isset($_REQUEST['from_url'])?$_REQUEST['from_url']:'');
Qlog::log('from_url:'.$from_url);
$order_id = intval($_REQUEST['order_id']);
$service_goods_back = $_REQUEST['service_goods_back'];
$carrier_id = intval($_REQUEST['carrier_id']);
if ($service_goods_back == 1) {
	$sql = "SELECT *, o.party_id, o.facility_id,o.shipping_id,o.goods_amount,o.taobao_order_sn,o.bonus,
			 IF(sgs.carrier_name, sgs.carrier_name, carrier_id) AS carrier_id, order_sn,ccm.city_code,
			(select ifnull(d.name,'') from ecshop.distributor d where o.distributor_id = d.distributor_id limit 1) as distributor_name
			FROM {$ecs->table('order_info')} o 
			INNER JOIN romeo.order_shipment os ON convert(o.order_id using utf8)=os.ORDER_ID
			INNER JOIN romeo.shipment rs ON rs.SHIPMENT_ID = os.SHIPMENT_ID and rs.SHIPPING_CATEGORY = 'SHIPPING_SEND'
			LEFT JOIN {$ecs->table('shipping')} s ON s.shipping_id = o.shipping_id
			LEFT JOIN service_order_goods sog ON sog.order_id = o.order_id
			LEFT JOIN service_goods_shipping sgs ON sgs.service_id = sog.service_id
		    LEFT JOIN ecshop.ecs_city_code_mapping ccm ON ccm.city_id = o.city -- 顺丰快递查出城市编码 
		    LEFT JOIN romeo.party p on p.party_id = o.party_id
			WHERE o.order_id = '{$order_id}'
		";
} else {
	// TODO:select * 去掉，精确到字段，有大坑，o.address和d.address重名，暂时子查询
	$sql = "SELECT *, o.party_id, o.facility_id, order_sn,o.shipping_id,o.goods_amount,o.taobao_order_sn,o.bonus,ccm.city_code,
			(select ifnull(d.name,'') from ecshop.distributor d where o.distributor_id = d.distributor_id limit 1) as distributor_name
			FROM {$ecs->table('order_info')} o 
			inner JOIN romeo.order_shipment os ON convert(o.order_id using utf8)=os.ORDER_ID
			INNER JOIN romeo.shipment rs ON rs.SHIPMENT_ID = os.SHIPMENT_ID  and rs.SHIPPING_CATEGORY = 'SHIPPING_SEND'
			LEFT JOIN {$ecs->table('shipping')} s ON s.shipping_id = o.shipping_id
			LEFT JOIN ecshop.ecs_city_code_mapping ccm ON ccm.city_id = o.city -- 顺丰快递查出城市编码
			LEFT JOIN romeo.party p on p.party_id = o.party_id
			WHERE o.order_id = '{$order_id}'
		";
}
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
	$item_sql = " SELECT A.ORDER_ID,@rank:=@rank+1 as pm  
			     FROM     
			     ( SELECT bpm.shipment_id,os2.ORDER_ID from romeo.out_batch_pick_mapping bpm  
			INNER JOIN romeo.order_shipment os2 on os2.shipment_id = bpm.shipment_id 
			INNER JOIN romeo.out_batch_pick_mapping bpm2 on bpm2.batch_pick_id = bpm.batch_pick_id
			INNER JOIN romeo.order_shipment os on os.SHIPMENT_ID = bpm2.shipment_id
			where os.ORDER_ID = '{$order_id}'
			     ) A,(SELECT @rank:=0) B   ";
	$rank = $db->getAll($item_sql);
	foreach($rank as $key=>$value){
		if($value['ORDER_ID'] == $order_id){
			$smarty->assign('rank', $value['pm']);
			break;
		}
	}
	
	$sql = "SELECT 
				'out' as tc_code,
				if(group_name is null or group_name='',
				concat_ws('_',goods_name,goods_number),
				concat_ws('_',group_name,group_number)) as sku_num 
			FROM ecshop.ecs_order_goods 
			where order_id = '{$order_id}' limit 1 ";
}else{
	//检查合并订单
	$order_id_arr = $db->getCol("SELECT DISTINCT os2.order_id
         from romeo.order_shipment os1
		inner join romeo.order_shipment os2 on os1.shipment_id = os2.shipment_id 
        where os1.order_id = '{$order_id}' ");
	$order_id_str = implode("','",$order_id_arr);	
	$sql = "SELECT '' as tc_code,
		concat_ws('-',if(gs.barcode is null or gs.barcode='' ,g.barcode,gs.barcode),sum(og.goods_number)) as sku_num 
	from ecshop.ecs_order_goods og 
	left join ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id and gs.style_id = og.style_id and gs.is_delete=0
	left join ecshop.ecs_goods g on g.goods_id = og.goods_id 
	where og.order_id in ('{$order_id_str}') 
	group by og.goods_id,og.style_id ";
}
$goods = $db->getAll($sql); 
if(count($goods)<=5){
	$smarty->assign('goods', $goods);  // 面单上添加对应商品条码及数量
}
if(empty($order['tel']))$order['tel']='';
if(empty($order['mobile']))$order['mobile']=$order['tel'];
// 检查订单合并 计算总的订单金额
if (!empty ($order)) {
	$amoutSQL = "SELECT sum(tmp.order_amount) order_amount from (
	            select o.order_amount
	            from romeo.order_shipment os1
				inner join romeo.order_shipment os2 on os1.shipment_id = os2.shipment_id
				inner join ecshop.ecs_order_info o on o.order_id  =  cast(os2.order_id as unsigned)
	            where os1.order_id = '{$order['order_id']}'
	          group by o.order_id ) as tmp
	       ";

	$order_total_amount = $db->getOne($amoutSQL);

	$order['order_amount'] = $order_total_amount;	
	
	$tracking_number = '';
	if($isAdd==0){
		$sql = "SELECT tracking_number from ecshop.ecs_order_info oi 
				  inner join romeo.order_shipment os on convert(oi.order_id using utf8) = os.order_id
				  inner join romeo.shipment s on os.shipment_id = s.shipment_id  AND s.SHIPPING_CATEGORY = 'SHIPPING_SEND'
				  		where oi.order_id = '{$order['order_id']}'";
		$tracking_number = $db->getOne($sql);
	}else{
		
		//对追加面单的首次打印
		if($pici==0){
			$sql = "SELECT tracking_number 
				from 
					ecshop.ecs_order_info oi 
				  	inner join romeo.order_shipment os on convert(oi.order_id using utf8) = os.order_id
				  	inner join romeo.shipment s on os.shipment_id = s.shipment_id AND s.SHIPPING_CATEGORY = 'SHIPPING_SEND'
				 where oi.order_id = '{$order['order_id']}'
				  	ORDER BY s.CREATED_STAMP desc";
			
			//所有热敏运单号中时间最近的就是最新追加的单号	
			$result=$db->getAll($sql);
			$trackArr = array();
			foreach ($result as $re) {
				array_push($trackArr,$re['tracking_number']);
			}
			$trackStr = implode("','", $trackArr);
			$sql1 = "SELECT tracking_number from ecshop.thermal_express_mailnos where tracking_number in ('{$trackStr}')";
			$trackingArr = $db->getAll($sql1);
			$trackArrNew = array();
			foreach ($trackingArr as $tr) {
				array_push($trackArrNew,$tr['tracking_number']);
			}
			if(sizeof($result)==sizeof($trackingArr)){
				$tracking_number = $result[0]['tracking_number'];
			}else{
				for($i=0;$i<sizeof($result);++$i){ 
					if(in_array($result[$i]['tracking_number'],$trackArrNew)){
						$tracking_number = $result[$i]['tracking_number'];
						break;
					}
				} 	
			}
				  	
		}else{
			//对追加面单的再次打印
			$sql = "SELECT tracking_number 
				from 
					ecshop.ecs_order_info oi 
				  	inner join romeo.order_shipment os on convert(oi.order_id using utf8) = os.order_id
				  	inner join romeo.shipment s on os.shipment_id = s.shipment_id  AND s.SHIPPING_CATEGORY = 'SHIPPING_SEND'
				 where oi.order_id = '{$order['order_id']}'
				  	ORDER BY s.CREATED_STAMP";
			
			//所有热敏运单号中按时间排序，第$pici次追加的即使所需热敏面单号	
			
			$result=$db->getAll($sql);
			$trackArr = array();
			foreach ($result as $re) {
				array_push($trackArr,$re['tracking_number']);
			}
			$trackStr = implode("','", $trackArr);
			$sql1 = "SELECT tracking_number from ecshop.thermal_express_mailnos where tracking_number in ('{$trackStr}')";
			$trackingArr = $db->getAll($sql1);
			$trackArrNew = array();
			foreach ($trackingArr as $tr) {
				array_push($trackArrNew,$tr['tracking_number']);
			}
			if(sizeof($result)==sizeof($trackingArr)){
				$tracking_number = $result[$pici]['tracking_number'];
			}else{
				$tracking_num = $result[$pici]['tracking_number'];
				if(in_array($tracking_num,$trackArrNew)){
					$tracking_number=$tracking_num;
				}
				else{
					for($i=$pici;$i<sizeof($result);++$i){ 
						if(in_array($result[$i]['tracking_number'],$trackArrNew)){
							$tracking_num = $result[$i]['tracking_number'];
							break;
						}
					} 	
				}
			}
		}
	}
	
}
$order['goods_amount'] = $order['goods_amount'] + $order['bonus'];

// 获取屏蔽号码
convert_mask_phone($order, 'get');
// update order mixed status 
// include_once ('includes/lib_order_mixed_status.php');
// update_order_mixed_status($order['order_id'], array (
// 	'shipping_bill_status' => 'printed'
// ), 'worker');

if(!empty($order['CARRIER_ID'])) {
	$carrier_id = $order['CARRIER_ID'];
}else{
	$carrier_id = $db->getOne("SELECT CARRIER_ID from romeo.shipment s
			inner join romeo.order_shipment os on os.shipment_id = s.shipment_id	
			where order_id = '{$order['order_id']}'  AND s.SHIPPING_CATEGORY = 'SHIPPING_SEND' limit 1 ");
}
if($carrier_id=='62'){
	$sql = "SELECT order_payment from ecshop.sync_jd_order_info where order_id= '{$order['taobao_order_sn']}' ";
	$order['order_payment'] =$db->getOne($sql); 
	if(empty($order['order_payment'])){
		$order['order_payment'] = $order['order_amount'];
	}
}


//$carrier_id = 53;
Qlog::log('PSO3 input order_id='.$order_id.' order_id in sql is '.$order['order_id'].' carrier_id='.$carrier_id);
// 没有指定发货面单类型，从订单快递公司读取
//根据仓库ID确定公司发货地址
$facility_address = facility_convert($order['facility_id']);
if ($type == '') {
    switch ($carrier_id) {
        case 3: //圆通
            $type = 'yto';
            break;
        case 5: // 宅急送
            $type = 'zjs';
            break;
        case 15: //宅急送COD
            $type = 'zjs-x';
            break;
        case 6:
            $type = 'fedex';
            break;
        case 43://EMS经济型
            $type = 'ems-cheap';
        	break;
        case 9: //邮政EMS
            if($order['shipping_id'] == 118){
        		$type = 'ems-cheap';
        	}
        	else{
        	    $type = 'ems';
        	}
            break;
        case 55://EMS到付
            $type = 'ems-df';
        	break;
        case 44: //顺丰快递（陆运）
            if(in_array($facility_address,array('19568548','185963147','79256821'))){
              $type='dw-sf';	
            }else{
              $type='sf';
            }
        	$order['diff'] = '顺丰陆运';
        	break;
        case 10: //顺风快递
             if(in_array($facility_address,array('19568548','185963147','79256821'))){
              $type='dw-sf';	
            }else{
              $type='sf';
            }
            $order['diff'] = '顺丰空运';
            break;
        case 53: //顺丰到付
            $order['diff'] = '顺丰到付';
            $type='sf-df';
            break;
        case 59: //顺丰COD-babynes
            $order['diff'] = '代收货款';
            $type='sf';
            break;
        case 17: //顺丰快递COD
	        if(!in_array($order['party_id'],array('65569','65581'))){
	        	$order['monthly_payment_no']='0216303909';
	        }else{
	        	$order['monthly_payment_no']='7698041295';
	        }
	        $type = 'sf-x';
	        break;
        case 20: //申通
            $type = 'sto' ;
            break; 
        case 54://申通到付
            $type = 'sto-df' ;
            break;
        case 31://申通货到付款
		    $type = 'sto-x'; 
 			break;
        case 28: //汇通
          	$type = 'htky';
          	break;
        case 29://韵达
        	$type = 'yunda';
        	break;
        case 41://中通快递
	        if('19568548' == $facility_address){
	          $type = 'dw-zto';
	        }else{
	          $type = 'zto';
	        }
        	break;
        case 52: //中通到付
            $type = 'zto-df';
            break;
        case 49://天天快递
    	    $type = 'tt';
    	    break;
    	case 46://德邦物流
    	    $type = 'db';
    	    break;
    	case 18: //龙邦快递
            $type = 'ibex';
            break;
        case 19: //龙邦快递COD
            $type = 'ibex-x';
            break;
        case 37: //跨越速运
            $type = 'ky';
            break;
        case 35: //速尔快递
            // 勾庄
            if('141796325' == $facility_address){
	          $type = 'gz-suer';
	        }else {
	          $type = 'suer';
	        }
            break;
        case 13: //万象物流
            $type = 'fh';
            break;
        case 16: //万象物流COD
            $type = 'fh-x';
            break;
        case 36: //E邮宝
            $type = 'eyb';
            break;
        case 45://中国邮政小包
    		$type = 'zyz';
    		break;
    	case 14: //邮政COD
        	 $type = 'ems-x'; 
            break;
        case 21: //DHL
            $type = 'dhl';
            break;
        case 22: //UPS
            $type = 'ups';
            break;
        case 23: //TNT
            $type = 'tnt';
            break;
        case 24: //FEDEX
            $type = 'fedex';
            break;
        case 58://宅急便
        	$type = 'tkb';
        	break;
        case 60://金佰利顺丰
        	if(in_array($facility_address,array('19568548','185963147','79256821'))){
              $type = 'dw-sf';	
            }else {
              $type = 'sf';
   			}	
        	$order['diff'] = '顺丰空运';
        	
        	break;
        case 62://京东COD
			$type='jd-cod';
			break;        	
        case 61://速达快递
        	$type='sd';
        	break;
        case 63://京东配送
			$type='jdps';
			break; 
        default:
        	Qlog::log('PSO3 IN DEFAULT carrier_id='.$carrier_id);
            header("location: print_shipping_order.php?order_id={$order_id}");
            die();
    }
}
if($type == 'dw-sf'){
	$order['monthly_payment_no'] = '7698041295';
}
// 查询省、城市
$provinceSQL = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['province']}'";
$order['province'] = $db->getOne($provinceSQL);

$citySQL = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['city']}'";
$order['city'] = $db->getOne($citySQL);

$districtSQL = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['district']}'";
$order['district'] = $db->getOne($districtSQL);

// 寄发票，需要写发票地址
if ($_REQUEST['act'] == 'send_invoice') {
	if ($order['inv_address'] != '') {
		$order['province'] = '';
		$order['city'] = '';
		$order['district'] = '';
		$order['tel'] = '';
		$order['address'] = $order['inv_address'];
	}
}
$sql = "SELECT p.name from romeo.party p
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

// 哥伦布计划
if($order['party_id'] == '65629') {
	$order['party_name'] = $order['distributor_name'];
}

// 备注
$order['remarks'] = '';
$order['need_insure'] = false;
$order['is_sf_cod'] = false;
// sf cod，babynes的特殊日志
$order['sf_cod_note'] = false;

//var_dump($order);
/**
 * babynes 有机器的保价  价值2188
 * cod的写上卡号，金额：代收全额
 * 备注：请携带pos机前往送货
 */ 
if($order['party_id'] == '65622') {

	 $has_robot = has_babynes_robot($order['order_id']);
	 $tracking_number_count = get_order_tracking_number_count($order['order_id']);

	 Qlog::log('$tracking_number_count:'.$tracking_number_count.' $has_robot:'.$has_robot);
	 // 有机器并且还没打过面单才保价，并且价格设置为 2188,第二个面单及以上为 order_amount - 2188（为了各种修改异常逻辑，保险起见）
	 // 无机器一律设置为 order_amount
	 $order['need_insure'] = false;
	 $order['declared_value'] = $order['order_amount'];
	 if($has_robot ) {
	 	if($tracking_number_count==0) {
	 		$order['need_insure'] = true;
	 		$order['declared_value'] = '2188';
	 	}else if($tracking_number_count == 1) {
	 		/** 恶心的逻辑，由于批捡打面单是先update tracking_number再打印，所以面单数为1其实还是第一次打，
	 		    而追加面单是先打印后update tracking_number，此时虽然面单数为1，但是第二次打了
	 		    为了区别这2个打印，特引入 从哪边进来的变量
	 		 */
	 		if($from_url == 'batch_print') {
	 			$order['need_insure'] = true;
	 		    $order['declared_value'] = '2188';
	        } else {
	        	$order['declared_value'] = $order['order_amount'] - 2188;
	        }
	 	}else {
	 	    $order['declared_value'] = $order['order_amount'] - 2188;
	 	}
	 } 
	 // 无机器
	 else {
         $order['declared_value'] = $order['order_amount'];
	 }
	 
	 $order['sf_cod_note'] = '本包裹'.$order['declared_value'].'    元，订单全额'.$order['order_amount'].'元';
	 
	 // cod 
	 if(in_array($carrier_id,array('59'))) {
	 	$order['monthly_payment_no_recover'] = $order['monthly_payment_no'] = '0213892391';
	 	
	 	$order['is_sf_cod'] = true;
	 	$order['remarks'] = '，请携带pos机前往并提前一小时联系客户';
	 }
}

// 惠氏

if($order['party_id'] == '65617') {
	 // cod 
	 
	 if(in_array($order['shipping_id'],array('135'))) {
	 	$order['monthly_payment_no_recover'] = $order['monthly_payment_no'] = '0213892391';
	 	$order['remarks'] = '，请携带pos机前往并提前一小时联系客户';
	 	$order['is_sf_cod'] = true;
	 	$order['sf_cod_note'] = '￥'.$order['goods_amount'];
	 	$order['cod_daofu'] = true; // 需要勾选收方付
	 }
}

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
//乐其电教的顺丰东莞快递order_type变为 点读机+配件，并且显示数量
if($order['party_id'] == 16 && $type == 'dw-sf' ){
	$order['goods_type'] = $order['goods_type'].'+配件<br/>';
	$sql = "SELECT SUM(goods_number) FROM ecshop.ecs_order_goods where order_id = {$order['order_id']}";
	$order['quantity'] = $db->getOne($sql);
}


if ('19568548' == $facility_address) { //电商服务东莞仓
	$order['company_zipcode'] = '523860';
	$order['shipping_worker'] = '刘寄军';
	$order['zhang'] = '<div style="position:absolute;font-size:40pt;top:190px;left:290px;">——</div>
	<div style="position:absolute;font-size:40pt;top:220px;left:278px;">|</div>
	<div style="position:absolute;font-size:10pt;top:230px;left:300px;">已&nbsp;&nbsp;&nbsp;验&nbsp;&nbsp;&nbsp;视</div>
	<div style="position:absolute;font-size:10pt;top:245px;left:300px;">单位：长安速递</div>
	<div style="position:absolute;font-size:10pt;top:260px;left:300px;">验视人：001</div>
	<div style="position:absolute;font-size:40pt;top:220px;left:393px;">|</div>
	<div style="position:absolute;font-size:40pt;top:250px;left:290px;">——</div>
    <div style="position:absolute;font-size:15pt;top:305px;left:395px;">√</div><!--寄件人付 -->
	<div style="position:absolute;font-size:15pt;top:325px;left:495px;">√</div><!--月结 -->
	<div style="position:absolute;font-size:15pt;top:385px;left:80px;">√</div><!--物品 -->
	<div style="position:absolute;font-size:15pt;top:405px;left:35px;">√</div><!--保价否 -->';
	$order['company_address'] = '广东　　　东莞　　　 　长安';
	$order['receiver_code'] = '047959'; // 收件员编号
}
elseif ('19568549' == $facility_address) { //电商服务上海仓
	$order['company_zipcode'] = '200050';
	$order['shipping_worker'] = '朱伟斌';
	$order['zhang'] = '
	<script type="text/javascript" src="../../../js/yuan.js"></script>
	';
	if ($type == 'zto') {
		$order['company_address'] = '上海市场部已验视<br>021-51876842';
	}
	elseif($type == 'htky'){
		$order['company_address'] ='青浦城区已验视<br>';
	}
	else{
		$order['company_address'] = '上海　　　上海市　　　  青浦';
	}
	
	if ($type == 'zyz') {
		$order['post_office'] = '香花邮局';
		$order['receive_zhang'] = '上海青浦大宗';//收寄章
	}

}
elseif ('42741887' == $facility_address) { //乐其北京仓
	$order['company_address'] = '北京　　　北京市　　　  通州';
}elseif ('137059428' == $facility_address) { //电商服务成都仓
	$order['from_city'] = '成都';
	if ($type == 'zyz') {
		$order['post_office'] = '双流';// 收寄局
	}

}elseif ('137059427' == $facility_address) { //电商服务武汉仓
	if ($type == 'yto') {
		$order['shipping_receiver_name'] = '江光义';// 揽件业务员
		$sinri_plus['rightdown_show_city'] = $order['city']." ".$order['district'];;// 面单右下角展示城市，区域信息
	}
}elseif ('149849256' == $facility_address) { //哥伦布嘉兴仓
	$order['company_address'] = '浙江省     嘉兴市     秀洲区<br> 嘉欣丝绸工业园24号';
	if ($type == 'zto') {
		$order['party_name'] = '拼好货商城';
//		$order['company_address']='嘉兴市秀洲区嘉欣丝绸工业园24号';
		$tpl = 'jx_zto.htm';
	}
}elseif ('141796325' == $facility_address) { //杭州勾庄仓（外包仓）
//	$order['company_address'] = '浙江省     杭州市     余杭区<br> 良渚行宫塘西区9号';
	$order['company_address'] = '浙江省     嘉兴市     秀洲区<br> 嘉欣丝绸工业园24号';
	if ($type == 'htky') {
		$order['shipping_receiver_name'] = '';// 揽件业务员
		$order['show_big_city_district'] = false;// 展示城市区的大字
	} else if ($type == 'zto') {
		//勾庄仓据说还要用，只不过临时地址修改于嘉兴仓一致。废弃141796325_zto.htm
		$order['party_name'] = '拼好货商城';
		$tpl = 'jx_zto.htm';
	} else if ($type == 'suer') {
		//暂只用zto，故速尔还保留这历史模样
		$order['party_name'] = '拼好货商城';
		$order['c_tel']='0571-28329319';
		$order['company_address']='杭州市余杭区行宫塘西区9号';
	}
}


if ($type == 'yto') {
	if(!isset($order['shipping_receiver_name'])) {
		$order['shipping_receiver_name'] = '张银';// 揽件业务员
	}
}
if ($type == 'htky') {
	if(!isset($order['shipping_receiver_name'])) {
		$order['shipping_receiver_name'] = '陶勇';// 揽件业务员
	}
	if(!isset($order['show_big_city_district'])) {
		$order['show_big_city_district'] = true;// 展示城市区的大字
	}
}

if($order['party_id']==64){
	$order['company_address']='广东省东莞市长安镇步步高大道126号乐其仓库';
}

/**
	FACILITY_ID	FACILITY_NAME
1	100170589	依云HOD北京
2	42741887	乐其北京仓
3	79256821	电商服务北京仓
4	83077350	通用商品北京仓

**/
if(in_array($facility_address, $beijingcang)){
	/*
	北京仓申通快递面单右下角增加到达城市打印项：
	打印内容：市+区 或 县+区
	打印格式：48号加粗
	【ERP需求】北京仓申通快递到达城市打印设置
	*/
	//申通快递 $carrier_id=20,54,31
	if($carrier_id==20 || $carrier_id==54 || $carrier_id==31){
		$sinri_plus['20140401_BJST_01']=$order['province']." ".$order['city']." ".$order['district'];//$order['province']." ".
	}
}

/*
 * 速尔快递面单打印的时候添加大头字
 */
if($carrier_id==35){
	$sinri_plus['20150421_SUER_01']=$order['province']." ".$order['city']." ".$order['district'];
}
 

if (in_array($facility_address, $shanghaicang)) {
	//圆通面单打印的时候打印上大头字（xx市，xx区）,和目前上海仓库中通快递大头字打印的一样，包括打印大头字内容和面单上的打印位置。
	if($carrier_id==3){
		$sinri_plus['rightdown_show_city']=$order['city']." ".$order['district'];
	}
}
if($carrier_id==41){
	$sh_east=array('浦东新区','南汇区','奉贤区','崇明区');
	$sh_west=array('虹口区','闸北区','长宁区','黄浦区','闵行区','松江区','静安区','普陀区','杨浦区','嘉定区','徐汇区','卢湾区','宝山区','青浦区','金山区');
	if(in_array($order['city'],$sh_east)){
		$sh_east_or_west='沪东';
	}
	if(in_array($order['city'],$sh_west)){
		$sh_east_or_west='沪西';
	}
	$sinri_plus['20140710_SHZT_01']=$sh_east_or_west;
}

if (in_array($facility_address, $dongguancang)) {
	//东莞中通添加已验视字样
	if($carrier_id==41 || $carrier_id==52){
		$sinri_plus['20140514_DGZT_01']='已验视';
	}
	if($carrier_id==20 || $carrier_id==31 || $carrier_id==54){
		$sinri_plus['20140529_DGST_01']='已验视';
		$sinri_plus['20140401_DGST_02']=$order['province']." ".$order['city']." ".$order['district'];//$order['province']." ".
	}
}
$tpls = array (
	'zjs' => 'zjs.htm',
	'zjs-x' => 'zjs-x.htm',
	'ems' => 'ems-x.htm',
	'ems-x' => 'ems-x-cod.htm',
	'ems-df' => 'ems-df.htm',
	'fedex' => 'fedex.htm',
	'yto' => 'yto.htm',
	'sf' => 'sf.htm',
	'sf-df' => '19568549sf.htm',
	'dw-sf' => 'dw-sf.htm',
	'sf-x' => 'sf-x.htm',
	'fh' => 'fh.htm',
	'fh-x' => 'fh-x.htm',
	'ibex' => 'ibex.htm',
	'ibex-x' => 'ibex-x.htm',
	'sto' => 'sto.htm',
	'sto-df' => 'sto-df.htm',
	'htky' => 'htky.htm',
	'yunda' => 'yunda.htm',
	'sto-x' => '65540#19568548st-x.htm',
	'suer' => 'suer.htm',
	'gz-suer' => 'gz-suer.htm',
	'ems-sn' => 'ems-sn.htm',
	'ky' => 'ky.htm',
	'zto' => 'zto.htm',
	'zto-df' => 'zto-df.htm',
	'dw-zto' => 'dw-zto.htm',
	'eyb' => 'eyb.htm',
	'ems-cheap' => 'ems-cheap.htm',
	'zyz' => 'zyz.htm',
	'db' => 'db.htm',
	'tt' =>'tt.htm',
	'tkb' => 'takkyuubin.htm',
	'bj-sf' => 'bj-sf.htm',
	'jd-cod' =>'jd-cod-arata.htm',
	'jdps' =>'jdps-arata.htm',
	'sd' =>'sd-arata.htm',
);
//金额大写转换
$money = Change($order['order_amount']);

$tpl = isset($tpl)?$tpl:(isset ($tpls[$type]) ? $tpls[$type] : die("没有快递公司信息，\$tpls[\$type]为空"));
QLog::log('模板1='.$tpl.',carrier_id='.$carrier_id);
$tpl = print_shipping_order_search_template($order['party_id'], $order['facility_id'], $tpl);


//上海仓库顺丰面单换新
if(in_array($order['facility_id'],array('120801050','137059424','137059426','176053000','24196974','19568549','22143846','194788297')) && ($carrier_id == 10||$carrier_id == 44 ||$carrier_id == 59 || $carrier_id == 60 || ($order['shipping_id'] == 135 && $order['party_id'] == '65617' && $carrier_id == 17))){
   if(!isset($order['monthly_payment_no'])) {
   	   $order['monthly_payment_no'] = '7698041295';
   }
   //LM市场物资仓，BB市场物资仓，OR市场物资仓，上海精品仓，贝亲青浦仓，电商服务上海仓，乐其上海仓_2（原乐其杭州仓）  先行于其他shanghaicang，用新面单
   $tpl = 'waybill/sh-sf.htm';
}else if(in_array($order['facility_id'],$shanghaicang) && ($carrier_id == 10||$carrier_id == 44 ||$carrier_id == 59 || $carrier_id == 60 || ($order['shipping_id'] == 135 && $order['party_id'] == '65617' && $carrier_id == 17))){
   if(!isset($order['monthly_payment_no'])) {
   	   $order['monthly_payment_no'] = '7698041295';
   }
   $tpl = 'waybill/19568549_printed_sf.htm';
}
//高洁丝官方旗舰店
if($order['distributor_id'] == '2389' && $carrier_id == 60){
	$sql = "SELECT SUM(goods_number) FROM ecshop.ecs_order_goods where order_id = {$order['order_id']}";
	$order['quantity'] = $db->getOne($sql);
	$order['monthly_payment_no_recover'] = $order['monthly_payment_no'] = '0215126503';
	$order['center_number'] = "成本中心号：57409 IO号：1604290";
	// turn_no_recover表示要覆盖原来的号码,因上海新模板已经打印上这个号码
	$order['turn_no_recover'] = $order['turn_no'] = '转021AE';
	$order['weight'] = '2';
	$order['length'] = '40';
	$order['width'] = '20';
	$order['height'] = '16';
	$order['third_paid'] = "√";
	$order['sender_sign'] = '乐其';
}
if(!isset($order['turn_no'])){
   		$order['turn_no'] = '转769FF';
   }


//var_dump($order);
$smarty->assign('money',$money);

//ADDED 20140401 LJNI
$smarty->assign('sinri_plus',$sinri_plus);

if($carrier_id==58){
	//Kuroneko Yamato
	$today_mon=idate("m");
	$today_day=idate("d");
	$smarty->assign('today_mon',$today_mon);
	$smarty->assign('today_day',$today_day);
	if(empty($order['company_address']))$order['company_address'] = '上海';
}

if($order['party_id']==65606 && $order['distributor_id']==2112){
	if($carrier_id==28){
		$tpl='waybill/dh-htky.htm';
	}else if($carrier_id==3){
		$tpl='waybill/dh-yto.htm';
	}else if($carrier_id==41){
		$tpl='waybill/dh-zto.htm';
	}
}
//热敏面单模板设定
if($arata==1){

	// WARNING: ARATA FORCE TRACKING NUMBER by Sinri
	if(!empty($_REQUEST['tracking_number'])){
		$tracking_number=$_REQUEST['tracking_number'];
	}

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
		$sql = "select attr_value from ecshop.order_attribute where attr_name='htBigPen' and order_id = '{$order_id}' and attr_value!='' limit 1";
		$htBigPen =  $db->getOne($sql);
		if(!empty($htBigPen)) $order['bigPen'] = $htBigPen;
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
				$order['company_address'] = '';//嘉兴市南湖区大桥镇工业园区欧嘉路与明新路交叉口 ，防止各科直接退货到外包仓
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
	}else if($carrier_id==13){
		$tpl='waybill/wx-arata.htm';
		if(in_array($facility_address, $shanghaicang)){
			$arata=array(
				'htSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'青浦二部',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '青浦二部已验视';
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
	}else if($carrier_id==62){
		$tpl='waybill/jd-cod-arata.htm';
		if(in_array($order['distributor_id'], $jd_distributor_ids) && $order['facility_id']=='185963138'){
			$arata=array(
				'sentBranch'=>'苏州',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
				'print_date' =>date('Y-m-d',time()),
			);
			$array = array('JDsendCode','siteId','siteName','sourcetSortCenterId','sourcetSortCenterName','originalCrossCode','originalTabletrolleyCode','targetSortCenterId',
				'targetSortCenterName','destinationCrossCode','destinationTabletrolleyCode','aging','agingName');
			foreach($array as $attr_name){
				$sql = "select attr_value from ecshop.order_attribute where order_id = {$order_id} and attr_name= '{$attr_name}'";
				$arata[$attr_name]= $db->getOne($sql);
			}
			print($arata);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '江苏苏州高新区浒墅关工业园';
		}
	}else if($carrier_id==63){
		$tpl='waybill/jdps-arata.htm';
		if(in_array($order['distributor_id'], $jd_distributor_ids)){
			$arata=array(
				'sentBranch'=>'上海',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
				'print_date' =>date('Y-m-d',time()),
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '上海市青浦区';
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
		}elseif(in_array($facility_address, $dongguancang)){
			$arata=array(
				'ytoSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'广东东莞',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '长安镇步步高大道126号';
		}elseif(in_array($facility_address,$huaqingwuhancang)){
			$arata=array(
				'ytoSender'=>'乐其-'.$order['party_name'],
				'sentBranch'=>'湖北武汉',
				'tracking_number'=>$tracking_number,		   
				'service_type'=>$order['goods_type'],
			);
			$smarty->assign('arata',$arata);
			$order['company_address'] = '';//湖北省武汉市东西湖区张柏路220号
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
		$sql = " select a.* from ecshop.ecs_order_yunda_mailno_apply a " .
		    " inner join romeo.order_shipment s on concat(s.shipment_id,0) = a.shipment_id " .
		    " where s.order_id in ('{$order_id}')";
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
			
			if($carrier_id==10)	{
				$order['sf_note_dg'] = "顺丰空运";
			}else{
				$order['sf_note_dg'] = "顺丰陆运";
			}
			
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
		}elseif (in_array($order['facility_id'], array('253372943'))) { //水果深圳仓
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

$t[6]=microtime(true);

$sinri_time=array(
	'0'=>'includes/init.php'.' = '.($t[1]-$t[0]),
	'1'=>'function.php'.' = '.($t[2]-$t[1]),
	'2'=>'includes/lib_order.php'.' = '.($t[3]-$t[2]),
	'3'=>'.../lib_log.php'.' = '.($t[4]-$t[3]),
	'4'=>'PartyXTelephone.php'.' = '.($t[5]-$t[4]),
	'5'=>'process'.' = '.($t[6]-$t[5]),
);

$smarty->assign('sinri_time',$sinri_time);

$smarty->display($tpl);

echo "<!--";
print_r($sinri_time);
echo "-->";

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