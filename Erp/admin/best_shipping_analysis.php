<?php
/**
 * 最优快递信息分析
 */
define ( 'IN_ECS', true );
require ('includes/init.php');
require_once ('includes/lib_best_shipping.php');

$order_id = $_REQUEST ['order_id']; 

if (empty($order_id)) {
	die ( "请输入order_id" );
}
if(!in_array($_SESSION['admin_name'],array('jche','ytchen','ljzhou','qyyao'))){
	admin_priv('best_shipping');
}

$best_shippings = findOrderBestShip($order_id);
//$best_shippings = getVirturlBestShip();
//var_dump($best_shippings);

get_format_best_shippings($best_shippings);
//var_dump($best_shippings);

// 系统设置的最优快递逻辑
$erp_best_shipping_info = get_erp_best_shipping_info($order_id);

$order_info = get_order_info($order_id);

$smarty->assign('erp_best_shipping_info', $erp_best_shipping_info);
$smarty->assign('best_shippings', $best_shippings);
$smarty->assign('order_info', $order_info);
$smarty->display ( 'oukooext/best_shipping_analysis.htm' );


function get_erp_best_shipping_info($order_id) {
	global $db;
	
	$sql =" 
    select oi.order_id,
	group_concat(distinct pfn.facility_name) as pf_facility_name,group_concat(distinct convert(pf.facility_id using utf8)) as pf_facility_id,
	group_concat(distinct rfn.facility_name) as rf_facility_name,group_concat(distinct convert(rf.facility_id using utf8)) as rf_facility_id,
	group_concat(distinct psn.shipping_name) as ps_shipping_name,group_concat(distinct convert(ps.shipping_id using utf8)) as ps_shipping_id,
	group_concat(distinct rsn.shipping_name) as rs_shipping_name,group_concat(distinct convert(rs.shipping_id using utf8)) as rs_shipping_id,
	group_concat(distinct dfn.facility_name) as df_facility_name,group_concat(distinct convert(df.facility_id using utf8)) as df_facility_id,
	group_concat(distinct drfn.facility_name) as drf_facility_name,group_concat(distinct convert(drf.facility_id using utf8)) as drf_facility_id,
	group_concat(distinct dsn.shipping_name) as ds_shipping_name,group_concat(distinct convert(ds.shipping_id using utf8)) as ds_shipping_id,
	group_concat(distinct drsn.shipping_name) as drs_shipping_name,group_concat(distinct convert(drs.shipping_id using utf8)) as drs_shipping_id,
	p.name as party_name,d.name as distributor_name,cf.facility_name as party_facility_name,cf.facility_id as party_facility_id,cs.shipping_name as party_shipping_name,cs.shipping_id as party_shipping_id,
	dcf.facility_name as distributor_facility_name,dcf.facility_id as distributor_facility_id,dcs.shipping_name as distributor_shipping_name,dcs.shipping_id as distributor_shipping_id
	from ecshop.ecs_order_info oi
	left join ecshop.ecs_party_assign_facility pf ON oi.party_id = pf.party_id
	left join romeo.facility pfn ON pf.facility_id = pfn.facility_id
	left join ecshop.ecs_party_region_assign_facility rf ON oi.party_id = rf.party_id and oi.province = rf.region_id
	left join romeo.facility rfn ON rfn.facility_id = rf.facility_id
	left join ecshop.ecs_party_assign_shipping ps ON oi.party_id = ps.party_id
	left join ecshop.ecs_shipping psn ON ps.shipping_id = psn.shipping_id
	left join ecshop.ecs_party_region_assign_shipping rs ON oi.party_id = rs.party_id and oi.province = rs.region_id
	left join ecshop.ecs_shipping rsn ON rs.shipping_id = rsn.shipping_id
	left join ecshop.ecs_distributor_assign_facility df ON oi.distributor_id = df.distributor_id
	left join romeo.facility dfn ON dfn.facility_id = df.facility_id
	left join ecshop.ecs_distributor_region_assign_facility drf ON oi.distributor_id = drf.distributor_id and oi.province = drf.region_id
	left join romeo.facility drfn ON drfn.facility_id = drf.facility_id
	left join ecshop.ecs_distributor_assign_shipping ds ON oi.distributor_id = ds.distributor_id
	left join ecshop.ecs_shipping dsn ON ds.shipping_id = dsn.shipping_id
	left join ecshop.ecs_distributor_region_assign_shipping drs ON oi.distributor_id = drs.distributor_id and oi.province = drs.region_id
	left join ecshop.ecs_shipping drsn ON drs.shipping_id = drsn.shipping_id
	left join romeo.party p ON oi.party_id = p.party_id
	left join ecshop.distributor d ON oi.distributor_id = d.distributor_id
	left join ecshop.taobao_shop_conf c ON oi.party_id = c.party_id
	left join romeo.facility cf ON c.facility_id = cf.facility_id
	left join ecshop.ecs_shipping cs ON c.shipping_id = cs.shipping_id
	left join ecshop.taobao_shop_conf dc ON oi.party_id = dc.party_id and oi.distributor_id = dc.distributor_id
	left join romeo.facility dcf ON dc.facility_id = dcf.facility_id
	left join ecshop.ecs_shipping dcs ON dc.shipping_id = dcs.shipping_id
	where oi.order_id='{$order_id}' group by oi.order_id";
//	var_dump($sql);
	$erp_best_shipping_info = $db->getRow($sql);
//	var_dump($erp_best_shipping_info);
	return $erp_best_shipping_info;
}

function get_format_best_shippings(&$best_shippings) {
	if(empty($best_shippings)) return null;
	
	global $db;
	foreach($best_shippings as $key=>$best_shipping) {
//		var_dump($best_shipping);
		$sql = "select ifnull(region_name,'') from ecshop.ecs_region where region_id = '{$best_shipping['province']}' limit 1";
//		var_dump($sql);
		$best_shippings[$key]['region_name'] = $db->getOne($sql);
		
		$sql = "select ifnull(facility_name,'') from romeo.facility where facility_id = '{$best_shipping['facilityId']}' limit 1";
		$best_shippings[$key]['facility_name'] = $db->getOne($sql);
		
		$sql = "select ifnull(shipping_name,'') from ecshop.ecs_shipping where shipping_id = '{$best_shipping['shipId']}' limit 1";
		$best_shippings[$key]['shipping_name'] = $db->getOne($sql);
	}
}

function get_order_info($order_id) {
	if(empty($order_id)) return null;
	
	global $db;
	$sql = "select oi.*,pr.region_name as province_name,cr.region_name city_name,dr.region_name district_name
	from ecshop.ecs_order_info oi
	left join ecshop.ecs_region pr ON oi.province = pr.region_id
	left join ecshop.ecs_region cr ON oi.city = cr.region_id
	left join ecshop.ecs_region dr ON oi.district = dr.region_id
	where oi.order_id = '{$order_id}' ";
	
	$order_info = $db->getRow($sql);
	
	return $order_info;
}


function getVirturlBestShip() {
	$data = array(
	    0 => 
	    array(
	      'calculable' =>  true,
          'arrived_weight' => 1,
          'carriage_id' => 70795,
          'carrier_id' => 44,
          'continued_fee' => '1.0000' ,
          'critical_weight' => '2.0000' ,
          'eighty_fee' => '0.0000' ,
          'facility_id' => '19568549' ,
          'file_fee' => '0.0000' ,
          'first_fee' => '1.0000' ,
          'first_weight' => '1.00' ,
          'hundred_fee' => '0.0000' ,
          'lowest_transit_fee' => '0.0000' ,
          'one_forty_fee' => '0.0000' ,
          'one_sixty_fee' => '0.0000' ,
          'one_twenty_fee' => '0.0000' ,
          'operation_fee' => '0.0000' ,
          'others_fee' => '0.0000' ,
          'region_id' => 839,
          'service_weight' => 1,
          'sixty_fee' => '0.0000' ,
          'time_arrived_weight' => 1,
          'tracking_fee' => '0.0000' ,
          'transit_fee' => '0.0000' ,
          'weighing_fee' => '0.0000' ,
	      'facilityId' => '19568549' ,
	      'failureCode' => '' ,
	      'failureMessage' => '' ,
	      'goodsListWeight' =>  2.32,
	      'isBestShipping' =>  true,
	      'optimalValue' =>  2.85,
	      'physicalAddress' => 'shanghai' ,
	      'province' => 12,
	      'regionId' => 0,
	      'shipId' => '44' ,
	      'shippingFee' =>  2.5,
	      'status' => 'success' ,
		  ),
	    1 => 
	    array(
	      'calculable' => false,
          'arrived_weight' => 2,
          'carriage_id' => 56447,
          'carrier_id' => 47,
          'continued_fee' => '2.0000' ,
          'critical_weight' => '2.0000' ,
          'eighty_fee' => '0.0000' ,
          'facility_id' => '19568549' ,
          'file_fee' => '0.0000' ,
          'first_fee' => '2.0000' ,
          'first_weight' => '2.00' ,
          'hundred_fee' => '0.0000' ,
          'lowest_transit_fee' => '0.0000' ,
          'one_forty_fee' => '0.0000' ,
          'one_sixty_fee' => '0.0000' ,
          'one_twenty_fee' => '0.0000' ,
          'operation_fee' => '0.0000' ,
          'others_fee' => '0.0000' ,
          'region_id' => 839,
          'service_weight' => 2,
          'sixty_fee' => '0.0000' ,
          'time_arrived_weight' => 2,
          'tracking_fee' => '0.0000' ,
          'transit_fee' => '0.0000' ,
          'weighing_fee' => '0.0000' ,
	      'facilityId' => '19568549' ,
	      'failureCode' => '' ,
	      'failureMessage' => '' ,
	      'goodsListWeight' =>  2.32,
	      'isBestShipping' =>  false,
	      'optimalValue' =>  0,
	      'physicalAddress' => 'shanghai' ,
	      'province' => 12,
	      'regionId' => 0,
	      'shipId' => '47' ,
	      'shippingFee' =>  0,
	      'status' => 'success',
		  )
	  );
	  
      return $data;
}

   
?>