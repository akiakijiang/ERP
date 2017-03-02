<?php
/**
 * 批量打印拣货单
 * 
 * @author ljzhou 2013.07.17
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
admin_priv('inventory_picking');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once('includes/lib_sinri_DealPrint.php');

$sinri_time_stat=array();

$sinri_time_stat[0]=microtime(true);

$AorB=mt_rand(0,1);

$taxonomy = array();

// var_dump($_REQUEST);die();

// province_list
$province_list_tmp=$slave_db->getAll("select region_id,region_name from ecshop.ecs_region where region_type=1 and parent_id=1");
foreach ($province_list_tmp as $row) {
	$province_list[$row['region_id']]=$row['region_name'];
}
$smarty->assign('province_list',$province_list);

$sep_distributor_ids=array();
if($_SESSION ['party_id']){
	switch ($_SESSION ['party_id']) {
		case '65617':
			$sep_distributor_ids=array(2071,2088);
			break;
		case '65562':
			$sep_distributor_ids=array(2104);
			break;
		case '65609':
			$sep_distributor_ids=array(2333);
			break;
		case '65558':
			$sep_distributor_ids=array(313,317,1161,1177,2147,2522,2563,2649,2650,2389);
			break;
	}
}

$smarty->assign('sep_distributor_ids',$sep_distributor_ids);

/**
All Hail Sinri Edogawa!
聚划算的临时功能要把单品列出来用的
**/
$TargetSingles=array();
if($_REQUEST['Sinri_SM_FILTER']){
    $Sinri_SM_FILTER=$_REQUEST['Sinri_SM_FILTER'];
}

// 所处仓库
$facility_list = get_user_facility();//var_dump($facility_list);die();
if (empty($facility_list)) {
	die('没有仓库权限');
}
//当前所在组织
$party_id = $_SESSION ['party_id'];

//配送方式
$shipping_list = Helper_Array::toHashmap(getShippingTypes(), 'shipping_id', 'shipping_name');

// 查询每页显示数列表
$page_size_list = array(
	'5' => '5条',
	'10' => '10条',
	'20' => '20条',
	'50' => '50条', 
	'100' => '100条',
	//'65535' => '不限制'
);
$taxonomy['page_size'] = $page_size_list;

$sort_time_list= array(
	'order_time' => '下单时间',
	'reserved_time' => '预定时间',
	'confirm_time' => '确认时间',
);

$taxonomy['sort_time'] = $sort_time_list;

$goods_type_list= array(
	'goods_type_simple' => '单品',
	'goods_type_multy' => '多品',
);

$taxonomy['goods_type_list'] = $goods_type_list;

$tracking_type_list= array(
	'tracking_type_simple' => '单个扫描',
	'tracking_type_multy' => '批量扫描（请切换到具体快递，并且要确保面单号是连续的）',
);

$taxonomy['tracking_type_list'] = $tracking_type_list;

// 配送状态 
$shipment_status_list = array(
	'SHIPMENT_INPUT' => '待配货',
	'SHIPMENT_PICKED' => '已完成二次分拣',
);

global $db;
global $slave_db;
// 分销商
$distributor_list =
	array('0'=>'没有分销商') + 
	Helper_Array::toHashmap((array)$db->getAll("select distributor_id,name from distributor where status='NORMAL'"), 'distributor_id','name');

// 请求 如果请求为split的话就干拆分订单的活T_T
$act = isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('split','search')) 
    ? $_REQUEST['act'] 
    : NULL ;
// 库存预定状态 -- 预定成功的才可参与批拣
$reserved_status ='Y';
// 配送状态
$shipment_status = isset($_REQUEST['shipment_status']) && in_array($_REQUEST['shipment_status'], $shipment_status_list)
	? $_REQUEST['shipment_status']
	: 'SHIPMENT_INPUT';
// 当前页码
$page =  is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;
// 每页多少记录数
	/* All Hail Sinri Edogawa ! */
$page_size = is_numeric($_REQUEST['size']) && $_REQUEST['size'] > 0
    ? $_REQUEST['size']
    : 0 ;
if($page_size == 0){
	$page_size=is_numeric($_REQUEST['SINRI_PAGE_SIZE_VALUE']) && $_REQUEST['size']>0
	? $_REQUEST['SINRI_PAGE_SIZE_VALUE']
	: 20;
}
	/* ! All Hail Sinri Edogawa */
// 排序方式
$sort_method = isset($_REQUEST['sort']) && trim($_REQUEST['sort'])
    ? $_REQUEST['sort']
    : 'order_time';
// 消息
$message = isset($_REQUEST['message']) && trim($_REQUEST['message'])
    ? $_REQUEST['message']
    : false;
//商品id
$goods_id =  isset($_REQUEST['goods_id']) && trim($_REQUEST['goods_id'])
    ? $_REQUEST['goods_id']
    : false;
$style_id = isset($_REQUEST['style_id']) && trim($_REQUEST['style_id'])
	? trim($_REQUEST['style_id'])
	: 0;
//套餐商家
$code = isset($_REQUEST['code']) && trim($_REQUEST['code'])
	? trim($_REQUEST['code'])
	: false;    
// 取消合并发货
if ($act=='split') {
	admin_priv('shipment_split');
	$shipment_id=$_REQUEST['shipment_id'];
	if ($shipment_id) {
		try {
			$handle=soap_get_client('ShipmentService');
			$handle->splitShipmentByShipmentId(array('shipmentId'=>$shipment_id));
		}
		catch (Exception $e) {
			$smarty->assign('message','取消合并发货异常：'. $e->getMessage());
		}
	}
	else {
		$smarty->assign('message','没有发货单号');
	}
} else if($act=='search'){
	$sinri_shall_list=true;
} else {
	$sinri_shall_list=false;
}
$smarty->assign('sinri_shall_list',$sinri_shall_list);
//$smarty->assign('act',$act);

//Sinri Goods Type
$sel_goods_types=isset($_REQUEST['checkbox_goods_type'])?$_REQUEST['checkbox_goods_type']:$goods_type_list;
$smarty->assign('sinri_test_goods_types',$sel_goods_types);

//Sinri deal with distributor//GET D_ID_LIST
$ids=array();
foreach($distributor_list as $key=>$item){
	// if(!in_array($key, $sep_distributor_ids)){
		$ids[]=$key;
	// }
}
$distributor_ids = isset($_REQUEST['checkbox_distributor']) // && isset($distributor_list[$_REQUEST['checkbox_distributor']])
        ? $_REQUEST['checkbox_distributor']
        : $ids;
$smarty->assign('sinri_test_distributor_ids',$distributor_ids);
// Goods Type
$SingleMulti = isset($_REQUEST['checkbox_goods_type'])
		? $_REQUEST['checkbox_goods_type']
		: array('goods_type_simple','goods_type_multy');
//pp($SingleMulti);

$smarty->assign('SingleMulti',$SingleMulti);

$shipping_id= isset($_REQUEST['checkbox_shipping']) && isset($shipping_list[$_REQUEST['checkbox_shipping']]) 
        ? $_REQUEST['checkbox_shipping']
        : NULL;

$PARTY_ID_IN_REQUEST= isset($_REQUEST['party_id']) && trim($_REQUEST['party_id'])
        ? $_REQUEST['party_id']
        : $_SESSION['party_id'];

checkPartyLeaf($PARTY_ID_IN_REQUEST);

//Sinri deal with facility
$ids=array();
foreach($facility_list as $key=>$item){
	$ids[]=$key;
}
$facility_ids=isset($_REQUEST['facility_id']) //&& isset($facility_list[$_REQUEST['facility_id']]) 
        ? $_REQUEST['facility_id'] 
        : array($ids[0]);// changed from $ids to present form
$smarty->assign('sinri_test_facility_ids',$facility_ids);

//Sinri Add Region Filter for first sql
$province=isset($_REQUEST['province'])
		? implode(',', $_REQUEST['province'])
		: '';
$smarty->assign('province',$province);
$city=isset($_REQUEST['city'])
		? implode(',', $_REQUEST['city'])
		: '';
$smarty->assign('city',$city);

$start_validity_time=isset($_REQUEST['start_validity_time'])
		? $_REQUEST['start_validity_time']
		: date("Y-m-d", time()-3600*24*15); //15 days ago
$smarty->assign('start_validity_time',$start_validity_time);

$end_validity_time=isset($_REQUEST['end_validity_time'])
		? $_REQUEST['end_validity_time']
		: date("Y-m-d", time()); //15 days ago
$smarty->assign('end_validity_time',$end_validity_time);

$ask_order_sn=isset($_REQUEST['order_sn'])
		? $_REQUEST['order_sn']
		: '';
$smarty->assign('order_sn',$ask_order_sn);

// 过滤条件
$filter = array(
    'size' => $page_size,// 每页多少条记录
	'party_id' =>$PARTY_ID_IN_REQUEST,// 组织
    'facility_ids' => $facility_ids,// 仓库
    'shipping_id' =>$shipping_id,// 配送方式
	'distributor_ids' => $distributor_ids, // 分销商
	'shipment_status'=> $shipment_status,// 配送状态
    'reserved_status'=> $reserved_status,// 是否已预定库存
    'goods_id' => $goods_id,
    'style_id' => $style_id,
    'code' => $code,
	'SingleMulti' => $SingleMulti,/* 邪恶的大鲵添加的单品鉴定 */
);
/*
All Hail Sinri Edogawa!
好像是用来做单品的特化的
*/
if(isset($Sinri_SM_FILTER)){
    $filter['SM_FILTER']=$Sinri_SM_FILTER;
}
// 链接
$url = 'shipment_listV5.php';
$url = add_param_in_url($url, 'size', $filter['size']);
$goods_sql = "";
if (!empty($filter['goods_id'])) {
	$sql_s = "";
	if (!empty($filter['style_id'])) {
		$goods_sql = " and not exists (select 1 from ecshop.ecs_order_goods og 
			where oi.order_id = og.order_id and og.goods_id is not null and (og.goods_id, og.style_id) 
				not in (({$filter['goods_id']}, {$filter['style_id']})) limit 1)";
	} else {
		$goods_sql = " and not exists (select 1 from ecshop.ecs_order_goods og
            where oi.order_id = og.order_id and og.goods_id is not null and og.goods_id <> '{$filter['goods_id']}' limit 1)";
	}
	
} elseif (!empty($filter['code'])) {
	$goods_sql = " and exists (
        select 1 from ecshop.order_attribute oa 
        where oi.order_id = oa.order_id 
			and oa.attr_name = 'TAOBAO_ITEM_MEAL_NAME_EX' 
			and oa.attr_value REGEXP '".$filter['code']. ';'."') 
	";
}
// 从所有预订成功的订单中取得待发货列表
// oi.shipping_status = '0'  0 => '待配货',
// oi.order_status = '1' 1 => '已确认',
$sql_having = '';
if($SingleMulti == array('goods_type_simple','goods_type_multy')) {
	// $sql_having = ' having sku_num >=1 ';
} else if($SingleMulti == array('goods_type_simple')) {
	$sql_having = ' having sku_num =1 ';
} else if($SingleMulti == array('goods_type_multy')) {
	$sql_having = ' having sku_num >1 ';
}
Qlog::log('start shipment_listV5:page_size:'.$page_size);
Qlog::log('$sql_having:'.$sql_having);

$sql_from_province=empty($province)?"":" and oi.province in (".$province.") ";
$sql_from_city=empty($city)?"":" and oi.city in (".$city.") ";

$sql_from_order_sn=empty($ask_order_sn)?"":" and oi.order_sn = '{$ask_order_sn}' ";

$sql_from_time_limit=" and oi.order_time >= '{$start_validity_time} 00:00:00' 
	and oi.order_time <= '{$end_validity_time} 23:59:59' ";

$sql_from_shipping=(empty($shipping_id)?'':" AND s.shipment_type_id = '{$shipping_id}' ");

if(!empty($distributor_ids) /*$party_id==65558*/){
	$sql_from_distributor=' and oi.distributor_id in ('.implode(',', $distributor_ids).') ';
}else{
	$sql_from_distributor='';
}

//From 20150923 Evening, use new SQL, All Hail Sinri Edogawa

//$sql_from_facility=facility_sql('oi.facility_id');
// $fids=array_keys($facility_list);
$sql_from_facility="oi.facility_id in (".implode(',', $facility_ids).") ";

$outShip_facility_list = $db->getCol("select facility_id from romeo.facility where is_out_ship = 'Y' ");
$outShip_facility = implode("','", $outShip_facility_list);

$sql_from_party=" oi.party_id=".$PARTY_ID_IN_REQUEST." "; //party_sql('oi.party_id');
$sql_from = "SELECT 
	    s.SHIPMENT_ID, oi.shipping_id, s.PARTY_ID, s.PRIMARY_ORDER_ID, count(distinct concat(og.goods_id,'_',og.style_id)) as sku_num
	FROM
	    romeo.order_inv_reserved r
	INNER JOIN romeo.order_shipment m ON m.ORDER_ID = CONVERT( r.ORDER_ID USING UTF8)
	INNER JOIN romeo.shipment s ON s.SHIPMENT_ID = m.SHIPMENT_ID
	INNER JOIN ecshop.ecs_order_info oi use index(order_info_multi_index,order_sn) ON oi.order_id = r.order_id
		AND oi.facility_id = r.facility_id
	INNER JOIN ecshop.ecs_order_goods og ON oi.order_id = og.order_id
	WHERE
	    r.STATUS = '$reserved_status'
	        AND s.STATUS = 'SHIPMENT_INPUT'
	        AND s.shipping_category = 'SHIPPING_SEND'
	        AND oi.shipping_status = 0
	        AND oi.order_status = 1
	        AND NOT EXISTS( SELECT 1 FROM romeo.batch_pick_mapping bm WHERE bm.shipment_id = s.shipment_id LIMIT 1)
	        AND {$sql_from_facility}
	        AND {$sql_from_party}
	        AND oi.FACILITY_ID NOT IN ('{$outShip_facility}')
	        {$sql_from_province} {$sql_from_city} {$sql_from_time_limit} {$sql_from_order_sn} 
	        {$sql_from_distributor}
	        AND (oi.shipping_id != 51 or (oi.shipping_id = 51 and s.tracking_number is not null and s.tracking_number != ''))
	        -- {$sql_from_shipping}
	        {$goods_sql}
	GROUP BY s.SHIPMENT_ID
	$sql_having
	ORDER BY oi.order_time, sku_num
	LIMIT 5000
".' -- ShipmentListV5 '.__LINE__.PHP_EOL;


$sinri_time_stat[1]=microtime(true);

Qlog::log('start sql_from: '.$sql_from);
$time_0 = microtime(true);
//die();//pp($sql_from);
$ref_fields=$ref_rowset=array();
$list=$db->getAllRefby($sql_from,array('SHIPMENT_ID'),$ref_fields,$ref_rowset);
//pp('开始总数list:');pp($list);//pp('ref_fields:');pp($ref_fields);//pp('ref_rowset:');pp($ref_rowset);
$sinri_time_stat[2]=microtime(true);
$time_1 = microtime(true);
// Qlog::log('SHIPMENT_LIST_V5_FIRST_SQL ['.($AorB?'old':'new').'] TIME='.($time_1-$time_0).'s memory_get_usage='.memory_get_usage().'B');

/**
THE FIRST SQL OVER, GO TO PROCESS SINCE HERE
**/

if ($list) {
    // 统计总数
    
    // 排序用
    $sort=array(
        'printed'=>array(), 		// 是否已经打印了
    	'order_time'=>array(),		// 下单时间
    	'confirm_time'=>array(),	// 确认时间
    	'reserved_time'=>array(),	// 预定时间
    );

    // 取得Shipment对应的订单列表
    // 判断Shipment对应的订单，是不是每个订单都已经预订上了
    // 如果Shipemt中有一个订单没有预定库存（合并发货），说明这个Shipment不能发货
    $sql="SELECT 
            o.order_id,o.order_status,o.pay_status,o.shipping_status,o.facility_id,o.shipping_id,o.shipping_name,
            o.order_time,o.order_sn,o.consignee,o.distributor_id, 
            if(o.handle_time = 0, o.order_time, FROM_UNIXTIME(o.handle_time)) handle_time,
        	o.order_time AS confirm_time,
   --          IF( 
   --          	(
   --          		SELECT 1 
   --          		FROM order_mixed_status_history 
   --          		WHERE order_id = o.order_id 
   --          		AND pick_list_status = 'printed' 
   --          		AND created_by_user_class = 'worker' 
   --          		LIMIT 1
   --          	), 1, 0
			-- ) AS printed,
			0 as printed, -- close printed check by Sinri, 20151127
            r.STATUS as RESERVED_STATUS, r.RESERVED_TIME AS reserved_time, m.SHIPMENT_ID
        from
            romeo.order_shipment m
            left join ecshop.ecs_order_info as o on o.order_id = cast(m.ORDER_ID AS UNSIGNED)
            left join romeo.order_inv_reserved r on r.ORDER_ID = m.ORDER_ID
        where
            (o.handle_time = 0 or o.handle_time < UNIX_TIMESTAMP()) and m.SHIPMENT_ID ".db_create_in($ref_fields['SHIPMENT_ID']).' -- ShipmentListV5 '.__LINE__.PHP_EOL;
    
    Qlog::log('start first filter:');

    $time_0 = microtime(true);
    
    $result=$db->getAllRefby($sql,array('SHIPMENT_ID'),$ref_fields1,$ref_rowset1);
	//pp('result:');pp($result);
	//pp('ref_fields1:');pp($ref_fields1);
	//pp('ref_rowset1:');pp($ref_rowset1);
	$time_1 = microtime(true);
	Qlog::log('SHIPMENT_LIST_V5_SECOND_SQL TIME='.($time_1-$time_0).'s memory_get_usage='.memory_get_usage().'B');

	$sinri_time_stat[3]=microtime(true);

/**
THE SECOND SQL OVER GO PROCESS SINCE HERE
**/

    if ($result) {
        $unset=array();
        foreach($list as $key=>$item) {
        	$list[$key]['printed']=0;
        	$sort['printed'][$key]=0;
              
            $shipment_id=$item['SHIPMENT_ID'];
            if (isset($ref_rowset1['SHIPMENT_ID'][$shipment_id])) {
	            // 发货单打印状态（由订单打印状态判断）
	            $order_list = &$ref_rowset1['SHIPMENT_ID'][$shipment_id];
	            
	            // 批捡中已经打印过了的，过滤掉,防止和老流程冲突 ljzhou 2013-10-08
	            if($order_list[0]['printed']) {
	            	$unset[] = $key;
	            	continue;
	            }
	            
	            foreach($order_list as $order_item) {
	                // 该Shipment不是所有的订单都预订了
	                if ($order_item['RESERVED_STATUS']!='Y') {
	                    $unset[]=$key;
						continue 2;
	                }
	                // 该Shipment不是所有的订单都确认了
	     //            if ($order_item['order_status']!='1') {
	     //                $unset[]=$key;
						// continue 2;
	     //            }
	                // 判断该Shipment是不是已打印发货单
	                if ($order_item['printed']) {
	                    $list[$key]['printed']=1;
	                    $sort['printed'][$key]=1;
	                }
	                
	             	// 该shipment订单下单时间 //大鲵开始改造了V5！
					if(isset($order_item[$sort_method])){
						$sort[$sort_method][$key]=$order_item[$sort_method];
					}
					if (!isset($sort[$sort_method][$key]) || !$order_item[$sort_method]){
						$sort[$sort_method][$key]=$order_item['order_time'];
					}
					/* 大鲵改造版 */
					
	                // 判断配送的主订单
	                if ($item['PRIMARY_ORDER_ID']==$order_item['order_id']) {
	                	$list[$key]['FACILITY_ID']=$order_item['facility_id'];
	                	$list[$key]['DISTRIBUTOR_ID']=$order_item['distributor_id'];
	                }
	            }
	            
	            // 是否合并发货
	            if(count($order_list)>1){
	                $list[$key]['is_merge_shipment']=true;
	            }

	            // 订单列表
	            $list[$key]['order_list']=$order_list;

                // 按仓库分
				if (isset($facility_list[$list[$key]['FACILITY_ID']])) {
					$taxonomy['facility'][$list[$key]['FACILITY_ID']]++;
        		}
				// 按快递分
				if (isset($shipping_list[$list[$key]['shipping_id']])) {
					$taxonomy['shipping'][$list[$key]['shipping_id']]++;
        		}
        		// 按分销商分
        		if (isset($distributor_list[$list[$key]['DISTRIBUTOR_ID']])) {
					$taxonomy['distributor'][$list[$key]['DISTRIBUTOR_ID']]++;
        		}
        		// 按组织分
        		$taxonomy['party'][$list[$key]['PARTY_ID']]++;
            }
            else {
				$unset[]=$key;
            }
        }

        // 从列表中去掉有未预订上的
        if($unset!==array()){
            foreach($unset as $k) {
                unset($list[$k]);
                unset($sort['printed'][$k]);
                unset($sort[$sort_method][$k]);
            }
        }
    }

    $sinri_time_stat[4]=microtime(true);
	
    /**
    All Hail Sinri Edogawa!
	Start to Juhuasuan Mode Code
    **/
    Qlog::log('start filter SM');
    $SM_in_new_way=true;//!$AorB;//true;
    if($SM_in_new_way){
    	if($list){
	    	$sm_check_sids=array();
			foreach($list as $key=>$item){
				$sm_check_sids[$item['SHIPMENT_ID']]=$item['SHIPMENT_ID'];
			}
			$sm_check_sids_sql="'".implode("','", $sm_check_sids)."'";
	    	$sql="SELECT 
			    os.shipment_id, og.goods_id the_goods_id, og.style_id the_style_id, og.goods_name the_goods_name
			FROM
			    romeo.order_shipment os
			        INNER JOIN
			    ecshop.ecs_order_goods og ON CAST(os.order_id AS UNSIGNED) = og.order_id
			WHERE
			    os.shipment_id IN ({$sm_check_sids_sql})
	    	";
			$shipment_goods_style_list=$db->getAll($sql);
			$shipment_sku_mapping=array();
			if(!empty($shipment_goods_style_list)){
				foreach ($shipment_goods_style_list as $sgs_item) {
					if(!isset($shipment_sku_mapping[$sgs_item['shipment_id']])){
						$shipment_sku_mapping[$sgs_item['shipment_id']]=array();
					}
					$shipment_sku_mapping[$sgs_item['shipment_id']][$sgs_item['the_goods_id'].'_'.$sgs_item['the_style_id']]=$sgs_item;
					//$sgs_item['the_goods_id'].'_'.$sgs_item['the_style_id'];
				}
			}
			// echo "<pre>";
			// print_r($shipment_sku_mapping);
			// echo "</pre>";

			$SM=array();

			foreach ($list as $key => $item) {
				$list[$key]['SM']=(count($shipment_sku_mapping[$item['SHIPMENT_ID']])>1?"goods_type_multy":"goods_type_simple");
				$SM[$key]=$list[$key]['SM'];
				if($list[$key]['SM']=='goods_type_simple'){
					$list[$key]['SingleGoodsInfo']=array_values($shipment_sku_mapping[$item['SHIPMENT_ID']]);
					$list[$key]['SingleGoodsInfo']=$list[$key]['SingleGoodsInfo'][0];
					$outer_id=$list[$key]['SingleGoodsInfo']['the_goods_id'].'_'.$list[$key]['SingleGoodsInfo']['the_style_id'];
					if(!isset($TargetSingles[$outer_id])){
						$TargetSingles[$outer_id]=$list[$key]['SingleGoodsInfo'];
						$TargetSingles[$outer_id]['sum']=1;
					}else{
						$TargetSingles[$outer_id]['sum']+=1;
					}
				}
			}

			usort($TargetSingles,function($a,$b){
				return $b['sum']-$a['sum'];
			});

			$smarty->assign('sinri_test_singlemulti',$SM);
			$smarty->assign('Sinri_TargetSingles',$TargetSingles);

			// echo "<pre>";
			// print_r($SM);
			// echo "</pre>";
			// echo "<pre>";
			// print_r($TargetSingles);
			// echo "</pre>";


			
		}
    }else{
	    if($list){
			// Qlog::log('SM_NEW');
			//new
			$sm_check_sids=array();
			foreach($list as $key=>$item){
				$sm_check_sids[$item['SHIPMENT_ID']]=$item['SHIPMENT_ID'];
			}
			$sm_check_sids_sql="'".implode("','", $sm_check_sids)."'";
			$sql="SELECT
					os.SHIPMENT_ID,
				IF(
					count(
						DISTINCT og.goods_id,
						og.style_id
					)<= 1,
					'goods_type_simple',
					'goods_type_multy'
				) as type_name
				FROM
					ecshop.ecs_order_goods og
				INNER JOIN romeo.order_shipment os ON os.ORDER_ID = og.order_id
				WHERE
					os.SHIPMENT_ID IN({$sm_check_sids_sql})
				GROUP BY
					os.SHIPMENT_ID
			".' -- ShipmentListV5 '.__LINE__.PHP_EOL;
			$sm_check_list=$db->getAll($sql);
			$sm_check_mapping=array();
			foreach ($sm_check_list as $sm_check_list_item) {
				$sm_check_mapping[$sm_check_list_item['SHIPMENT_ID']]=$sm_check_list_item['type_name'];
			}
			foreach ($list as $key => $item) {
				$list[$key]['SM']=$sm_check_mapping[$item['SHIPMENT_ID']];
			}

	        foreach($list as $key=>$item){
	            if($list[$key]['SM']!='goods_type_simple') continue;
	            $SQL="SELECT
	                    og.goods_id,og.style_id
	                FROM ecshop.ecs_order_goods og
					INNER JOIN romeo.order_shipment os on os.ORDER_ID = og.order_id
					where os.SHIPMENT_ID ='".$item['SHIPMENT_ID']."' ".' -- ShipmentListV5 '.__LINE__.PHP_EOL;
	            $SQLED_GSIDS=$db->getAll($SQL);
	            $the_goods_id=$SQLED_GSIDS[0]['goods_id'];
	            $the_style_id=$SQLED_GSIDS[0]['style_id'];
	            $existed=false;
	            foreach ($TargetSingles as $keyts => $valuets) {
	                if($valuets['the_goods_id']==$the_goods_id && $valuets['the_style_id']==$the_style_id){
	                    $TargetSingles[$keyts]['sum']+=1;
	                    $list[$key]['SingleGoodsInfo']=array(
	                            'the_goods_id'=>$the_goods_id,
	                            'the_style_id'=>$the_style_id
	                        );
	                    $existed=true;
	                    break;
	                }
	            }
	            if(!$existed){
	                $TargetSingles[]=array(
	                        'the_goods_id'=>$the_goods_id,
	                        'the_style_id'=>$the_style_id,
	                        'sum'=>1
	                    );
	                $list[$key]['SingleGoodsInfo']=array(
	                        'the_goods_id'=>$the_goods_id,
	                        'the_style_id'=>$the_style_id
	                    );
	            }
	        }
	    }
	    $SM=array();
	    foreach($list as $key=>$item){
	        $SM[$key]=$item['SM'];
	    }
	    $smarty->assign('sinri_test_singlemulti',$SM);
	    $TSorder=array();
	    foreach ($TargetSingles as $tsk => $tsv) {
	        $SQL="SELECT
	                    goods_name
	                FROM
	                    ecshop.ecs_goods
	                WHERE
	                    ecshop.ecs_goods.goods_id = '".$tsv['the_goods_id']."'
	                LIMIT 1;".' -- ShipmentListV5 '.__LINE__.PHP_EOL;
	        $the_goods_name=$db->getOne($SQL);
	        $TargetSingles[$tsk]['the_goods_name']=$the_goods_name;
	        $TSorder[$tsk]=$tsv['sum'];
	    }
	    arsort($TSorder);
	    $TS=array();
	    foreach ($TSorder as $tsok => $tsov) {
	        $TS[]=$TargetSingles[$tsok];
	    }
	    $smarty->assign('Sinri_TargetSingles',$TS);
	}

	$sinri_time_stat[5]=microtime(true);

	/**
	All Hail Sinri Edogawa!
	End of Juhuasuan Mode Code
	**/
    // 排序
    // 1. 按是否已打印拣货单
    if (!empty($sort['printed']) || !empty($sort[$sort_method])) {
        array_multisort( $sort['printed'], SORT_ASC,$sort[$sort_method], SORT_ASC, $list);
    }
    Qlog::log('start condition filter:shipment_list_filter ');
$sinri_time_stat['5_1']=microtime(true);
	//pp('before');pp($list);


	/* All Hail Sinri Edogawa ! */
    if(isset($shipping_id)){
		$smarty->assign('sinri_best_shipping_id',$shipping_id);
		$filter['shipping_id']=$shipping_id;
    }else {
    	
	    $max_cid=0;
	    $max_c=-1;
	    foreach ($taxonomy['shipping'] as $id => $count) {
	    	if($max_c<$count){
	    		$max_c=$count;
	    		$max_cid=$id;
	    	}
	    }
    	$smarty->assign('sinri_best_shipping_id',$max_cid);
    	$filter['shipping_id']=$max_cid;
	}

	// 根据条件过滤====================
    $errlist=shipment_list_filter($list,$PARTY_ID_IN_REQUEST,$facility_ids, $filter);
	//pp('after');pp($list);//pp('2次筛选前list:');pp($list);
$sinri_time_stat['5_2']=microtime(true);
    // 最后对合法的$list进行2次筛选，使得最后得到的shipment数量尽可能多 ljzhou 2013-09-15
    $fiter_shipment_ids = array();
    foreach($list as $key=>$value) {
    	$fiter_shipment_ids[] = $list[$key]['SHIPMENT_ID'];
    }

	//pp('2次筛选后list:');pp($list);//pp('最后问题筛选拣货总数list:');pp($errlist);
	//pp('最后好的筛选拣货总数fiter_shipment_ids:');pp($fiter_shipment_ids);
    Qlog::log('start get_good_shipment_ids ');

	$good_shipment_idss = array();
	$result = array();
	$result = get_good_shipment_ids($fiter_shipment_ids,$good_shipment_idss,0,0,$page_size);
	$good_shipment_ids = $result['good_shipment_ids'];
	
	$last_pos = $result['last_pos'];
$sinri_time_stat['5_3']=microtime(true);
	//pp('最后结束筛选拣货总数good_shipment_ids:');pp($good_shipment_ids);
    // 得到本批次的sku总数
    $sku_nums = get_sku_nums($good_shipment_ids);
$sinri_time_stat['5_4']=microtime(true);	
	// 对筛选的拣货单能正常预订的特殊标记
	$goods_shipment_length = count($good_shipment_ids);
	foreach($list as $key=>$value) {
    	if(in_array($list[$key]['SHIPMENT_ID'],$good_shipment_ids)) {
    		$list[$key]['STORAGE_RESERVE'] = 'Y';
    	} else {
    		$list[$key]['STORAGE_RESERVE'] = 'N';
    	}
    }
    //pp('最后预订情况:');pp($list);

    $sinri_time_stat[6]=microtime(true);

    Qlog::log('start get_shipments_takiruka_info ');
    
    $smarty->assign('sinri_errlist',$errlist);
	if(sizeof($errlist)){
    	$smarty->assign('sinri_errinfo',get_shipments_takiruka_info($errlist,$PARTY_ID_IN_REQUEST,$facility_ids));
    } else {
    	$smarty->assign('sinri_errinfo', array());
    }
	
	/** All Hail Sinri Edogawa ! **/
	$smarty->assign('sku_nums',$sku_nums);
	$smarty->assign('sinri_size',$page_size);
	$smarty->assign('sinri_sort',$sort_method);
	$smarty->assign('sinri_test_filter',$filter);
	/** ! All Hail Sinri Edogawa **/

    // 构造分页
    $total=count($list); // 总记录数
    $total_page=ceil($total/$page_size);  // 总页数
    $page=max(1, min($page, $total_page));
    $offset=($page-1)*$page_size;
    $limit=$page_size;
    
    // 为了显示没预定成功的shipment,所以所需的条数要重新赋值
    //$limit = $last_pos;

    // 分页
    if($page_size<65535){
		$list=array_splice($list, $offset, $limit);
    }

    // 分页 //为了让URL能够瓦全，伪造一个Filiter
	$url_filter=array();
	foreach($filter as $key=>$item){
		if($key=='facility_ids'){			
			//$url_filter[$key]=implode(',',$item);
			$items="";
			foreach($item as $fi){
				$items+="[$fi]";
			}
			$url_filter[$key]=$items;
		}else if ($key=='distributor_ids'){
			$items="";
			foreach($item as $fi){
				$items+="[$fi]";
			}
			$url_filter[$key]=$items;
		} else if ($key=='SingleMulti'){
			$items="";
			foreach($item as $fi){
				$items+="[$fi]";
			}
			$url_filter[$key]=$items;
		}
		else {
		$url_filter[$key]=$item;
		} 
	}
    $pagination = new Pagination(
        $total, $page_size, $page, 'page', $url, null, $url_filter
    );
    
	$smarty->assign('total', $total);  // 总数
	$smarty->assign('list', $list);  // 当前页列表
	$smarty->assign('taxonomy', $taxonomy);  // 分类统计
    $smarty->assign('pagination', $pagination->get_simple_output());  // 分页	

    
} else {
	/*
	echo '<h1>当前组织下发货单已经处理完毕，请稍后再来！</h1>';
	echo "<hr>
		以下内容供ERP调查问题用，请仓库君无视。<br>
		<textarea readonly='readonly'>".$sql_from."</textarea>";
	die();
	*/
	$total=0;
	$list=array();

	$smarty->assign('total', $total);  // 总数
	$smarty->assign('list', $list);  // 当前页列表
	$smarty->assign('taxonomy', $taxonomy);  // 分类统计
    // $smarty->assign('pagination', $pagination->get_simple_output());  // 分页

	$sinri_time_stat[3]=microtime(true);
	$sinri_time_stat[4]=microtime(true);
	$sinri_time_stat[5]=microtime(true);
	$sinri_time_stat[6]=microtime(true);

}

$sinri_time_stat[7]=microtime(true);

$smarty->assign('url', $url+"&act=search");
$smarty->assign('filter', $filter);                          // 筛选条件
$smarty->assign('page_size_list', $page_size_list);          // 每页显示数列表
$smarty->assign('sort_method', $sort_method);      			 // 默认排序方式
$smarty->assign('sort_method_list', $sort_method_list);      // 排序方式列表
$smarty->assign('facility_list', $facility_list);            // 用户所处仓库列表
$smarty->assign('shipping_list', $shipping_list);              // 配送方式列表 
$smarty->assign('distributor_list',$distributor_list);       // 分销商

$sinri_time_stat[8]=microtime(true);

$all_time=$sinri_time_stat[8]-$sinri_time_stat[0];
$time_1=$sinri_time_stat[1]-$sinri_time_stat[0];
$time_2=$sinri_time_stat[2]-$sinri_time_stat[1];
$time_3=$sinri_time_stat[3]-$sinri_time_stat[2];
$time_4=$sinri_time_stat[4]-$sinri_time_stat[3];
$time_5=$sinri_time_stat[5]-$sinri_time_stat[4];
$time_6=$sinri_time_stat[6]-$sinri_time_stat[5];
$time_7=$sinri_time_stat[7]-$sinri_time_stat[6];
$time_8=$sinri_time_stat[8]-$sinri_time_stat[7];

$time_stat=array(
	'all'=>$all_time,
	'init'=>$time_1,
	'1st'=>$time_2,
	'2nd'=>$time_3,
	'unset'=>$time_4,
	'SM'=>$time_5,
	'filter'=>$time_6,
	'res'=>$time_7,
	'fin'=>$time_8,
	'AorB'=>($AorB?'old':'new'),
);

Qlog::log('SHIPMENT_LIST_V5_TIMER: '.json_encode($time_stat));

$time_filter_sort=$sinri_time_stat['5_1']-$sinri_time_stat[5];
$time_filter_slf=$sinri_time_stat['5_2']-$sinri_time_stat['5_1'];
$time_filter_ggsi=$sinri_time_stat['5_3']-$sinri_time_stat['5_2'];
$time_filter_gsn=$sinri_time_stat['5_4']-$sinri_time_stat['5_3'];
$time_filter_tag=$sinri_time_stat[6]-$sinri_time_stat['5_4'];

$time_stat=array(
	'sort'=>$time_filter_sort,
	'slf'=>$time_filter_slf,
	'ggsi'=>$time_filter_ggsi,
	'gsn'=>$time_filter_gsn,
	'tag'=>$time_filter_tag,
);
Qlog::log('SHIPMENT_LIST_V5_FILTER_TIMER: '.json_encode($time_stat));

$smarty->display('shipment/shipment_listV5.htm');


/**
 * 根据查询条件过滤订单列表
 *
 * @param array $list 订单列表
 */
function shipment_list_filter(& $list, $PARTY_ID_IN_REQUEST,$facility_ids, $filter = array()) {
    if (empty($list) || empty($filter)) return;

    $time_0=microtime(true);
	
	$error_shipments=array();

	$useForEachStyle=0;//mt_rand(0,1);
    
    if(!$useForEachStyle){
    	$useTwoStepLocationCheck=true;//mt_rand(0,1);

	    $shipment_ids_for_tariru_check=array();
	    foreach ($list as $key => $item) {
	    	$shipment_ids_for_tariru_check[]=$item['SHIPMENT_ID'];
	    }
	    $location_check_start_time=microtime(true);
	    if($useTwoStepLocationCheck){
			$error_shipments=checkShipmentsProductsOnLocation($shipment_ids_for_tariru_check,$PARTY_ID_IN_REQUEST,$facility_ids);
	    }else{
	    	$error_shipments=check_shipments_tariruka($shipment_ids_for_tariru_check,$PARTY_ID_IN_REQUEST,$facility_ids);
	    }
	    $location_check_end_time=microtime(true);
	    Qlog::log('location_check '.($useTwoStepLocationCheck?'two_step':'one_step').' time: '.($location_check_end_time-$location_check_start_time));
	    Qlog::log('test_check_shipments_tariruka: '.json_encode($error_shipments));
    }

	
    foreach ($list as $key => $item) {
        $flag = true;

        //This is old For Each style
        if($useForEachStyle){
	        $esid=check_shipments_tariruka(array($item['SHIPMENT_ID']),$PARTY_ID_IN_REQUEST,$facility_ids);
	        Qlog::log(json_encode('for each '.$item['SHIPMENT_ID']).' --> '.json_encode($esid));
	        if(isset($esid) && sizeof($esid)>0){
	        	//print_r($esid);
	        	$flag=false;
	        	$error_shipments[]=$item['SHIPMENT_ID'];
	        }
        }else{
	        if(in_array($item['SHIPMENT_ID'], $error_shipments)){
	        	$flag=false;
	        }
	    }

        if ($flag && isset($filter['party_id'])) {
        	$flag = $flag && ($item['PARTY_ID'] == $filter['party_id']);
        }
		/*
		//被残暴的大鲵干掉了
        if ($flag && isset($filter['facility_id'])) {
            $flag = $flag && ($item['FACILITY_ID'] == $filter['facility_id']);
        }
		*/
		if ($flag && isset($filter['facility_ids'])) {
			//This is old replaced by Sinri Edogawa 20150924
			/*
			$flag_f=false;
			foreach($filter['facility_ids'] as $ffi){
				$flag_f=$flag_f || ($item['FACILITY_ID'] == $ffi);
			}
			*/
			$flag_f=in_array($item['FACILITY_ID'], $filter['facility_ids']);

			$flag=$flag && $flag_f;
		}
        if ($flag && isset($filter['shipping_id'])) {
            $flag = $flag && ($item['shipping_id'] == $filter['shipping_id']);
        }
		/*
		//被残暴的大鲵干掉了
        if ($flag && isset($filter['distributor_id'])) {
        	$flag = $flag && ($item['DISTRIBUTOR_ID'] == $filter['distributor_id']);
        }
		*/
		if ($flag && isset($filter['distributor_ids'])) {
			//This is old replaced by Sinri Edogawa 20150924
			/*
			$flag_d=false;
			foreach($filter['distributor_ids'] as $fdi){
				$flag_d = $flag_d || ($item['DISTRIBUTOR_ID'] ==  $fdi);
			}
			*/

			$flag_d=in_array($item['DISTRIBUTOR_ID'], $filter['distributor_ids']);

			$flag=$flag && $flag_d;
		}
		
		//邪恶的大鲵加上去的单品鉴定
		if($flag && isset($filter['SingleMulti'])){
			//This is old replaced by Sinri Edogawa 20150924
			/*
			$flag_sm=false;
			foreach($filter['SingleMulti'] as $fsm){
				$flag_sm = $flag_sm || ($item['SM'] ==  $fsm);
			}
			*/

			$flag_sm=in_array($item['SM'], $filter['SingleMulti']);

			$flag=$flag && $flag_sm;
		}
		
        /**
        All Hail Sinri Edogawa
		半夜聚划算~来自茨城的代码~
        **/
        if($flag && isset($filter['SM_FILTER'])) {
            $tf=false;
            if($item['SM']=='goods_type_simple'){
            	// echo $item['SingleGoodsInfo']['the_goods_id'].'-'.$item['SingleGoodsInfo']['the_style_id'].'=='.$filter['SM_FILTER'].PHP_EOL;
                if($item['SingleGoodsInfo']['the_goods_id'].'-'.$item['SingleGoodsInfo']['the_style_id']==$filter['SM_FILTER']){
                    $tf=true;
                }
            }
            $flag = $flag && $tf;
        }
		
		
        if (!$flag) {
            unset($list[$key]);
        }
    }

    // Qlog::log('final error shipments: '.json_encode($error_shipments));

    $time_1=microtime(true);

    Qlog::log('shipment_list_filter_fin ['.($useForEachStyle?'old':'new').'] '
    	.($time_1-$time_0).' s PARTY_ID: '.$PARTY_ID_IN_REQUEST.' user: '.$_SESSION['admin_name']
    	.' location_check '.($useTwoStepLocationCheck?'two_step':'one_step').' time: '.($location_check_end_time-$location_check_start_time)
    	.' shipment_count: '.count($list)
    );

    return $error_shipments;
}

function checkPartyLeaf($party_id){
	global $db;
	$sql="SELECT
            IS_LEAF
        FROM
            romeo.party p
        WHERE
            p.PARTY_ID = '$party_id'
        LIMIT 1
    ";
    $r=$db->getOne($sql);
    if($r=='N'){
       die('<h1>请选择具体的业务组织。╮(╯_╰)╭</h1>');
    }
}

?>