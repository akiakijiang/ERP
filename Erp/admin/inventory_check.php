<?php
/**
 * 库存查询 必须为具体业务组 (耗材库存;非耗材库存清单)
 * (request:cat_id/goods_id/validate_id/facility_id + 有无库存; ) (--当前业务组下，业务组拥有仓库及用户拥有仓库权限)
 */
define ( 'IN_ECS', true );
require ('includes/init.php');
require ("function.php");
require_once(ROOT_PATH. 'includes/lib_order.php');
require_once(ROOT_PATH. 'RomeoApi/lib_inventory.php');
require_once('includes/lib_product_code.php');
require_once('includes/debug/lib_log.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

//限制业务组必须具体
$session_party = $_SESSION['party_id'];
$sql = "select IS_LEAF from romeo.party where party_id = '{$session_party}' limit 1";
$is_leaf = $db->getOne($sql);
if($is_leaf == 'N'){
	die("请先选择具体业务组后再查询库存");
}

$cond = false;
// 权限判断
if (check_goods_common_party ()) {
	admin_priv ( 'cg_storage_common' );	
	$cond = true;
} else {
	admin_priv ( 'cw_finance_storage_main', 'cg_storage', 'purchase_order' );
}
$can_view_storage_number = false;
if( check_admin_priv ( 'ck_view_storage_number' )) {
	$can_view_storage_number = true;
}

//$sql_time_start1 = microtime(true);
//获取分类
$exclude = array(1 ,119 ,166 ,179 ,260, 336, 341, 613, 616, 615, 414, 597, 1071, 825, 837, 979, 1073, 1158, 1159, 1498, 1515, 1516, 2329);
$sql = "SELECT c.cat_id, if(pa.cat_name != p.name,CONCAT_WS('_',pa.cat_name,c.cat_name),c.cat_name) as name, c.parent_id,  COUNT(s.cat_id) AS has_children 
		 FROM ecshop.ecs_category AS c  
		 LEFT JOIN ecshop.ecs_category AS s ON s.parent_id=c.cat_id  
		 LEFT JOIN ecshop.ecs_category AS pa ON c.parent_id=pa.cat_id  
		 LEFT JOIN romeo.party p on p.party_id = c.party_id  
		where c.is_delete = 0  
		and c.party_id = ".$_SESSION['party_id']." and c.parent_id not in (2245,0)  
		GROUP BY c.cat_id HAVING has_children =0  
		ORDER BY c.parent_id,c.sort_order ASC ";	
$cat_list = $db->getAll($sql);
$category_list = array();
foreach ($cat_list as $key=>$value) {
	if(in_array($value['cat_id'],$exclude)){
		unset($cat_list[$key]);
	}else{
		$category_list[$value['cat_id']] = $value['name'];
	}
}
//$sql_time_end1 = microtime(true);
//QLOG::LOG("INVENTORY_CHECK_SQL1_USE TIME:".($sql_time_end1-$sql_time_start1));

$type = $_REQUEST ['type']; //导出库存清单,导出耗材库存
if ($type=="导出耗材库存" || $type == '导出库存清单' ) {
	admin_priv ( '4cw_finance_storage_main_csv', '5cg_storage_csv' );
}

//仓库权限：业务组拥有仓库且用户拥有仓库权限
$facility_user_list = array_intersect_assoc(get_available_facility(),get_user_facility());
$facility_user_str = implode("','",array_keys($facility_user_list));
if($type=="导出耗材库存") {	
//	$sql_time_start2 = microtime(true); 
	//业务组织、商品名称、商品条码、库位、仓库、ERP库存
	$sql = "SELECT p.name,f.facility_name,pm.product_id,ii.facility_id,
			g.goods_name,g.barcode,
			ifnull((select il.location_barcode from romeo.inventory_location il
				left join romeo.location l ON il.location_id = l.location_id
				where il.facility_id = f.facility_id and il.product_id = pm.product_id and l.location_type = 'IL_LOCATION'
				limit 1),'') as location_barcode,
			ii.status_id,sum(ii.quantity_on_hand_total) as total_number
			FROM ecshop.ecs_goods AS g
			inner join ecshop.ecs_category ec on g.cat_id = ec.cat_id
			inner JOIN romeo.product_mapping AS pm ON g.goods_id = pm.ecs_goods_id
			inner JOIN romeo.inventory_item AS ii ON pm.product_id = ii.product_id
			inner JOIN romeo.facility f ON ii.facility_id = f.facility_id
			inner join romeo.party p on convert(g.goods_party_id using utf8)= p.party_id
			and p.is_leaf='Y' and p.status = 'ok' 
			and p.parent_party_id != '65542'and p.party_id not in('65546','65599','65556','65559')
			WHERE g.is_delete = 0 
			AND ii.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED')
			AND ii.QUANTITY_ON_HAND_TOTAL > 0
			and ec.cat_name like '%耗材%' "; 
	if(!$cond) {
		$sql .= " AND g.goods_party_id = {$session_party}
			AND ii.facility_id in ('{$facility_user_str}') "; 	
	}
	$sql .= "GROUP BY pm.product_id,ii.status_id,ii.facility_id,p.party_id
			ORDER BY g.goods_party_id,ii.facility_id,g.goods_id" ;
//	Qlog::log("inventory_check_haocai_sql:".$sql);
	$consumer_goods_list = $db->getAll($sql);
	
	foreach($consumer_goods_list as $key=>$consumer_goods ) {
		// 屏蔽数量
		if(!$can_view_storage_number) {
			$consumer_goods_list[$key]['total_number'] = '***';
		} 		
	}
//	$sql_time_end2 = microtime(true); 
//	QLOG::LOG("INVENTORY_CHECK_SQL2_USE TIME:".($sql_time_end2-$sql_time_start2));
	$smarty->assign ( 'consumer_goods_list', $consumer_goods_list );
}

if (($_REQUEST ['act']  == '搜索' && trim ( $_REQUEST ['act'] ) == '搜索') || ($type=="导出库存清单") ) {
	//搜索条件有cat_id ,inner join ecshop.ecs_goods / and g.cat_id 
	$condition_1 = "";
	$category_id = trim ($_REQUEST ['category_id']);
	if ($category_id != -1 && $category_id !== null ) {
		$condition_1 .= " AND g.cat_id= {$category_id} ";
	}
	//搜索条件有barcode,goods_name,productCode  --> goods_style_id 
	$goods_style_id_array = array();
	$condition_2 = "";
	$barcode = trim ($_REQUEST ['barcode'] );
	$goods_name = trim ( $_REQUEST ['goods_name'] );
	$productCode =trim($_REQUEST['productCode']);
	if ($barcode != '') {
		$condition_2 .= " AND if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) = '{$barcode}' ";
	}
	if ($goods_name != '') {
		$condition_2 .= " AND CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, ''))) LIKE '%{$goods_name}%'";
	}
    if ($productCode !== '') {
    	$ref = explode("_",$productCode);
//        $goods_id = $ref[0];
//        $style_id = $ref[1] ? $ref[1] : 0;
        if($ref[1]){
        	$condition_2 .=" AND CONCAT_WS('_', g.goods_id, ifnull(gs.style_id,0)) = '{$productCode}' ";
        }else{
        	$condition_2 .=" AND g.goods_id = {$productCode} ";
        }
	}
//	$sql_time_start3 = microtime(true); 
	if($condition_2!=""){
		//select goods_id,product_id
		$ref_fields_goods = $refs_goods = array();
		$sql ="SELECT CONCAT_WS('_', g.goods_id, ifnull(gs.style_id,0)) AS goods_style_id
			 from ecshop.ecs_goods g  
			 left join ecshop.ecs_goods_style gs on gs.goods_id = g.goods_id and gs.is_delete=0 
			 left join ecshop.ecs_style s on s.style_id = gs.style_id
			 left join romeo.product_mapping pm on pm.ecs_goods_id = g.goods_id and ifnull(gs.style_id,0) = pm.ecs_style_id  
			 where pm.PRODUCT_MAPPING_ID is not null and g.goods_party_id = {$session_party} and g.is_delete = 0 ".$condition_2;
//		Qlog::log("inventory_check_goods_infos_sql:".$sql);
		$goods_infos = $db->getAllRefby($sql,array('goods_style_id'),$ref_fields_goods, $refs_goods, false); 
		unset($refs_goods);
		$goods_style_id_array = $ref_fields_goods['goods_style_id'];
	}
	 
	if($condition_2!="" && empty($goods_infos)){
		die("所查商品并不存在于该业务组，请检查商品是否已经下架或业务组选择是否正确");
	} 
	 
	//搜索条件有facility_id --> $select_facility
	$select_facility = "";
	$available_facility = $_REQUEST ['available_facility'];
	if ($available_facility != - 1 && $available_facility != '') {
		$select_facility = $available_facility;
	}else{
		$select_facility = $facility_user_str;
	}
	// 搜索条件有全新/二手  
	$condition_3 = "";
	$is_new = $_REQUEST ['is_new'];
	if ($is_new != - 1 && $is_new !== null) {
		$condition_3 .= " AND ii.status_id = '{$is_new}'";
	}
	//只显示有库存 --> 决定最终goods_style_facility_id来源数据 （只显示有货：ii可用库存>0的库存商品为依据；显示所有：存在ii记录的商品与有订单信息的商品并集作为依据）
	$is_show_urikitamono = empty($_REQUEST['is_show_urikitamono'])?0:1;
	
	//select order_goods info   -- 1 month
//	$sql_time_start4 = microtime(true);
	$ref_fields_orders = $refs_orders = array();
	if($is_new !="INV_STTS_USED") {
		$sql = "SELECT 
		        CONCAT_WS('_', og.goods_id, og.style_id, o.facility_id,'INV_STTS_AVAILABLE') AS goods_style_facility_status_id,
		        o.order_status,
		        sum(og.goods_number) as order_number
			FROM
		       ecshop.ecs_order_info o  use index (order_info_multi_index)
		       inner join ecshop.ecs_order_goods AS og ON o.order_id = og.order_id "
		       // goods_style_id_array
	            .(empty($goods_style_id_array)?'':" AND  CONCAT_WS('_', og.goods_id, og.style_id) in ('".implode("','",$goods_style_id_array)."') ") ."
			WHERE
				o.order_status in (0,1) AND o.shipping_status IN (0,13,16)                            
	            AND o.order_type_id in ('SALE','RMA_EXCHANGE','SHIP_ONLY') AND o.party_id = {$session_party}
	            AND o.facility_id in ('{$select_facility}') " 
	            // cat_id
	            .($condition_1==""?'':" AND exists(select 1 from ecshop.ecs_goods g where g.goods_id = og.goods_id and g.is_delete = 0 {$condition_1}) ")."
	            AND not exists(select 1 from romeo.inventory_item_detail iid where iid.order_goods_id = convert(og.rec_id using utf8) limit 1) 
				AND o.order_time >=date_add(now(),interval -1 month)
			-- group BY goods_style_facility_status_id,o.order_status
			group by og.goods_id, og.style_id, o.facility_id,o.order_status order by null
			"."-- ".__FILE__." Line ".__LINE__.PHP_EOL;
//		Qlog::log("inventory_check_order_infos_sql:".$sql);
		$db->getAllRefby($sql, array('goods_style_facility_status_id'), $ref_fields_orders, $refs_orders, false);
	}
//	$sql_time_end4 = microtime(true); 
//	QLOG::LOG("INVENTORY_CHECK_SQL4_USE TIME:".($sql_time_end4-$sql_time_start4));
	
	//select  inventory_item  + goods_inventory_reserved
//	$sql_time_start5 = microtime(true);
	$ref_fields_ii_freezes = $refs_ii_freezes = array();
	$sql = "SELECT concat_ws('_',pm.ecs_goods_id,pm.ecs_style_id,ii.facility_id,ii.STATUS_ID) as goods_style_facility_status_id,  
			ii.inventory_item_type_id as is_serial,
			sum(ii.QUANTITY_ON_HAND_TOTAL) as storage_count,egir.reserve_number,bzp.item_number,bzp.is_fragile,bzp.spec
		from ecshop.ecs_goods g 
		LEFT JOIN ecshop.ecs_goods_style gs on gs.goods_id = g.goods_id and gs.is_delete=0
		INNER JOIN romeo.product_mapping AS pm ON pm.ecs_goods_id = g.goods_id and pm.ecs_style_id = ifnull(gs.style_id,0)
		INNER JOIN romeo.inventory_item ii on ii.product_id = pm.product_id ".($is_show_urikitamono==1?"":"AND ii.QUANTITY_ON_HAND_TOTAL > 0 ")."
		LEFT JOIN ecshop.ecs_goods_inventory_reserved egir ON egir.goods_id = pm.ecs_goods_id
			 AND egir.style_id = pm.ecs_style_id AND egir.facility_id = ii.facility_id  
			 AND egir.party_id = {$session_party} AND egir.status = 'OK' AND ii.STATUS_ID = 'INV_STTS_AVAILABLE'
		left join ecshop.brand_zhongliang_product as bzp on bzp.barcode = if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) 
		where g.goods_party_id={$session_party} and ii.facility_id in ('{$select_facility}') {$condition_1} "
		//全新
		.($condition_3==""?" AND ii.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED') ": $condition_3 )
        // goods_style_id_array
        .(empty($goods_style_id_array)?'':" AND  CONCAT_WS('_', pm.ecs_goods_id, pm.ecs_style_id) in ('".implode("','",$goods_style_id_array)."') ") ."
		GROUP BY goods_style_facility_status_id
		"."-- ".__FILE__." Line ".__LINE__.PHP_EOL;
//	QLog::log("inventory_check_ii_freezes_sql:".$sql);
	$db->getAllRefby($sql, array('goods_style_facility_status_id'), $ref_fields_ii_freezes, $refs_ii_freezes, false);
//	$sql_time_end5 = microtime(true); 
//	QLOG::LOG("INVENTORY_CHECK_SQL5_USE TIME:".($sql_time_end5-$sql_time_start5));
	
	// select  supplier_return_request
//	$sql_time_start6 = microtime(true);
	$ref_fields_suppliers = $refs_suppliers = array();
	$sql = "SELECT 
              sum(temp.num) as supplier_return_number,
              CONCAT_WS('_', temp.goods_id, temp.style_id,temp.facility_id,temp.status_id) AS goods_style_facility_status_id
        from 
		    (  select 
		             eog.goods_number + sum(ifnull(iid.quantity_on_hand_diff,0)) as num,
		             eog.goods_id,eog.style_id,eoi.facility_id,eog.status_id,eoi.order_id,eoi.order_time
		       FROM  
		            ecshop.ecs_order_info AS eoi force index (order_info_multi_index)
		   	        INNER JOIN ecshop.ecs_order_goods AS eog ON eoi.order_id = eog.order_id 
                    inner join romeo.supplier_return_request_gt srrg on srrg.SUPPLIER_RETURN_GT_SN = eoi.order_sn
                    inner join romeo.supplier_return_request srr on srr.supplier_return_id = srrg.supplier_return_id 
		            LEFT  JOIN  romeo.inventory_item_detail iid on iid.order_goods_id = convert(eog.rec_id using utf8)
                    WHERE eoi.party_id = {$session_party}
                    and eoi.order_time > SUBDATE(now(),INTERVAL 1 MONTH) -- Limited under the command of His Highness Mjzhou.
			        AND  eoi.facility_id in ('{$select_facility}')"
			        //全新
					.($condition_3==""?" AND eog.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED') ": " AND eog.STATUS_ID='{$is_new}' " )
			        // cat_id
		            .($condition_1==""?'':" AND exists(select 1 from ecshop.ecs_goods g where g.goods_id = eog.goods_id {$condition_1})")
		            // goods_style_id_array
        			.(empty($goods_style_id_array)?'':" AND  CONCAT_WS('_', eog.goods_id, eog.style_id) in ('".implode("','",$goods_style_id_array)."') ") ."
		            AND   eoi.order_type_id in ( 'SUPPLIER_RETURN','SUPPLIER_TRANSFER')  
                    AND   srr.status in ('EXECUTING','CREATED')
                    AND  srr.check_status != 'DENY'
			   GROUP BY eog.rec_id,eog.status_id
		     )  as temp    
        GROUP BY goods_style_facility_status_id 
        "."-- ".__FILE__." Line ".__LINE__.PHP_EOL;
//	QLog::log("inventory_check_suppliers_sql:".$sql);
	$db->getAllRefby($sql, array('goods_style_facility_status_id'), $ref_fields_suppliers, $refs_suppliers, false);
//	$sql_time_end6 = microtime(true); 
//	QLOG::LOG("INVENTORY_CHECK_SQL6_USE TIME:".($sql_time_end6-$sql_time_start6));		
	

	$ref_fields_variance = $refs_variance = array();
	$sql = "select temp.order_type_id,CONCAT_WS('_', temp.goods_id, temp.style_id,temp.facility_id,temp.status_id) AS goods_style_facility_status_id,temp.variance_num 
		from ( select (og.goods_number+ifnull(sum(iid.quantity_on_hand_diff),0))as variance_num ,eoi.order_type_id,eoi.facility_id,og.status_id,og.goods_id,og.style_id
			from ecshop.ecs_order_info eoi
			inner join ecshop.ecs_order_goods og on eoi.order_id = og.order_id
			inner join ecshop.ecs_goods eg on og.goods_id = eg.goods_id
			left join ecshop.ecs_goods_style egs on egs.goods_id = og.goods_id and egs.style_id = og.style_id and egs.is_delete=0
			left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
			where order_type_id = 'VARIANCE_MINUS' and eoi.party_id = {$session_party}
				and eoi.order_time > SUBDATE(now(),INTERVAL 1 MONTH)
				AND  eoi.facility_id in ('{$select_facility}')"
		        //全新
				.($condition_3==""?" AND og.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED') ": " AND og.STATUS_ID='{$is_new}' " )
		        // cat_id
		        .($condition_1==""?'':" AND exists(select 1 from ecshop.ecs_goods g where g.goods_id = og.goods_id {$condition_1})")
		        // goods_style_id_array
				.(empty($goods_style_id_array)?'':" AND  CONCAT_WS('_', og.goods_id, og.style_id) in ('".implode("','",$goods_style_id_array)."') ") ."
		group by og.rec_id
		) as temp GROUP BY goods_style_facility_status_id 
        HAVING variance_num != 0  "."-- ".__FILE__." Line ".__LINE__.PHP_EOL;
	$db->getAllRefby($sql, array('goods_style_facility_status_id'), $ref_fields_variance, $refs_variance, false);
	
	//select batch_order_info   -c
//	$sql_time_start7 = microtime(true);
	$ref_fields_batchs = $refs_batchs = array();
	if($is_new !="INV_STTS_USED") {
		$sql = "SELECT 
              	sum(temp.num) as purchase_number,CONCAT_WS('_', temp.goods_id, temp.style_id,temp.facility_id,'INV_STTS_AVAILABLE') AS goods_style_facility_status_id
			from 
			      (  select og.goods_number - sum(ifnull(iid.quantity_on_hand_diff,0)) as num,og.goods_id,og.style_id,o.facility_id
			    	 FROM
			    	     ecshop.ecs_order_info AS o 
			    	     inner join ecshop.ecs_batch_order_mapping bom on o.order_id = bom.order_id
			   		     INNER JOIN ecshop.ecs_order_goods AS og ON o.order_id = og.order_id
			   		     INNER JOIN romeo.product_mapping pm on pm.ecs_goods_id = og.goods_id and pm.ecs_style_id = og.style_id 
			             left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8) 
			    	WHERE
						 o.order_type_id in ('PURCHASE','PURCHASE_TRANSFER') AND bom.is_cancelled = 'N' AND bom.is_over_c = 'N' AND bom.is_in_storage = 'N'
			    		 AND o.order_status <> 5  AND o.party_id = {$session_party} AND o.facility_id in ('{$select_facility}')"
				        // cat_id
			            .($condition_1==""?'':" AND exists(select 1 from ecshop.ecs_goods g where g.goods_id = og.goods_id {$condition_1})")
			            // goods_style_id_array
	        			.(empty($goods_style_id_array)?'':" AND  CONCAT_WS('_', og.goods_id, og.style_id) in ('".implode("','",$goods_style_id_array)."') ") ."
						 group by og.rec_id
			) as temp    
			GROUP BY goods_style_facility_status_id
			"."-- ".__FILE__." Line ".__LINE__.PHP_EOL;
//		QLog::log("inventory_check_batchs_sql:".$sql);
		$db->getAllRefby($sql, array('goods_style_facility_status_id'), $ref_fields_batchs, $refs_batchs, false);
	}
//	$sql_time_end7 = microtime(true); 
//	QLOG::LOG("INVENTORY_CHECK_SQL7_USE TIME:".($sql_time_end7-$sql_time_start7));
	
//	$sql_time_start8 = microtime(true); 
	// 取得待每一种商品的库位
	$sql = "SELECT 
			concat_ws('_',pm.ecs_goods_id,pm.ecs_style_id,il.facility_id,il.status_id) as goods_style_facility_status_id,
			group_concat(distinct il.location_barcode SEPARATOR ';') as location_barcode
		FROM romeo.inventory_location il 
			inner join romeo.location l on il.location_id = l.location_id
			left join romeo.product_mapping  pm on il.product_id = pm.product_id
		WHERE il.party_id = {$session_party}
			AND il.facility_id in ('{$select_facility}')
			AND il.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED') AND il.goods_number > 0  "
			.(empty($goods_style_id_array)?'':" AND CONCAT_WS('_', pm.ecs_goods_id, pm.ecs_style_id) in ('".implode("','",$goods_style_id_array)."') ")."
			AND l.location_type = 'IL_LOCATION'
		group by goods_style_facility_status_id
	"."-- ".__FILE__." Line ".__LINE__.PHP_EOL;
	$location_barcode_value_list = array();
    $db->getAllRefby($sql, array('goods_style_facility_status_id'), $location_barcode_key, $location_barcode_value_list,false);
//    $sql_time_end8 = microtime(true); 
//	QLOG::LOG("INVENTORY_CHECK_SELECT_USE TIME:".($sql_time_end8-$sql_time_start3));
	
//	$sql_time_start9 = microtime(true); 
	$goods_style_facility_status_ids = array();
	$array = array();
	if($ref_fields_ii_freezes['goods_style_facility_status_id']) $array[] = $ref_fields_ii_freezes['goods_style_facility_status_id'];
	if($ref_fields_orders['goods_style_facility_status_id']) $array[] = $ref_fields_orders['goods_style_facility_status_id'];
	if($ref_fields_suppliers['goods_style_facility_status_id']) $array[] = $ref_fields_suppliers['goods_style_facility_status_id'];
	if($ref_fields_variance['goods_style_facility_status_id']) $array[] = $ref_fields_variance['goods_style_facility_status_id'];
	if($ref_fields_batchs['goods_style_facility_status_id']) $array[] = $ref_fields_batchs['goods_style_facility_status_id'];
	if($is_show_urikitamono==0){//只显示 有库存，数据条码从ii决定
		$goods_style_facility_status_ids = $ref_fields_ii_freezes['goods_style_facility_status_id'];
	}elseif(count($array) == 0){
		$goods_style_facility_status_ids = array();
	}elseif(count($array) == 1){
		$goods_style_facility_status_ids = $array[0];
	}elseif(count($array) == 2){
		$goods_style_facility_status_ids = array_unique(array_merge($array[0],$array[1]));
	}elseif(count($array) == 3){
		$goods_style_facility_status_ids = array_unique(array_merge($array[0],$array[1],$array[2]));
	}elseif(count($array) == 4){
		$goods_style_facility_status_ids = array_unique(array_merge($array[0],$array[1],$array[2],$array[3]));
	}else{
		$goods_style_facility_status_ids = array_unique(array_merge($array[0],$array[1],$array[2],$array[3],$array[4]));
	}
	
	unset($array);
	unset($ref_fields_ii_freezes);
	unset($ref_fields_suppliers);
	unset($ref_fields_variance);
	unset($ref_fields_batchs);
	if(empty($goods_style_facility_status_ids)){
		$goods_list = array();
	}else{
		$goods_list = array();
		$good = array();
		foreach ($goods_style_facility_status_ids as $goods_style_facility_status_id ) {
			$ref = explode("_",$goods_style_facility_status_id);
			$goods_id = $ref[0];
			$style_id = $ref[1];
			$facility_id = $ref[2];
			$sql = "SELECT pm.ecs_goods_id as goods_id,ifnull(gs.style_id,0) as style_id,CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, '')))as goods_name,
					IF(pm.ecs_style_id ='' OR pm.ecs_style_id = NULL,pm.ecs_goods_id,concat_ws('_',pm.ecs_goods_id,pm.ecs_style_id)) as productCode,
					if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) as barcode ,g.goods_code,g.shop_price,c.cat_name 
				 from ecshop.ecs_goods g  
				 left join ecshop.ecs_category c on g.cat_id = c.cat_id
				 left join ecshop.ecs_goods_style gs on gs.goods_id = g.goods_id and gs.is_delete=0 
				 left join ecshop.ecs_style s on s.style_id = gs.style_id 
				 left join romeo.product_mapping pm on pm.ecs_goods_id = g.goods_id and ifnull(gs.style_id,0) = pm.ecs_style_id 
				 where g.goods_id = {$goods_id} and g.is_delete=0 and ifnull(gs.style_id,0) = {$style_id} limit 1 ";
			$good = $db->getRow($sql);	
			if(empty($good)) continue;
			$good['facility_id'] =$facility_id;
			$good['facility_name'] = facility_mapping ($facility_id);	
			$good['status_id'] = ($ref[5]  == 'AVAILABLE')?'INV_STTS_AVAILABLE':'INV_STTS_USED';
			$good['is_serial'] = $refs_ii_freezes['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['is_serial'];
			$good['storage_count'] = $refs_ii_freezes['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['storage_count']?$refs_ii_freezes['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['storage_count']:0;
			$good['reserve_number'] = $refs_ii_freezes['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['reserve_number'];
			$good['supplier_return_number'] = $refs_suppliers['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['supplier_return_number']?$refs_suppliers['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['supplier_return_number']:0;
			$good['variance_number'] = $refs_variance['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['variance_num']?$refs_variance['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['variance_num']:0;
			$good['purchase_number'] = $refs_batchs['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['purchase_number'];
			if($temp_order = $refs_orders['goods_style_facility_status_id'][$goods_style_facility_status_id][0]){
				($temp_order['order_status']==0)?($good['unconfirmed_number']=$temp_order['order_number']):($good['order_number']=$temp_order['order_number']);
			}
			if($temp_order = $refs_orders['goods_style_facility_status_id'][$goods_style_facility_status_id][1]){
				($temp_order['order_status']==0)?($good['unconfirmed_number']=$temp_order['order_number']):($good['order_number']=$temp_order['order_number']);
			}
			$good['AVAILABLE_TO_RESERVED']= $good['storage_count'] - $good['reserve_number'] - $good['order_number'] - $good['unconfirmed_number']- $good['supplier_return_number']-$good['variance_number'];
			$good['location_barcode'] = $location_barcode_value_list['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['location_barcode'];
			
			//item_number,is_fragile
			if($session_party=='65625'){
//				bzp.item_number,bzp.is_fragile,bzp.spec
				$good['item_number'] = $refs_ii_freezes['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['item_number'];
				$good['is_fragile'] = $refs_ii_freezes['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['is_fragile'];
				$good['spec'] = $refs_ii_freezes['goods_style_facility_status_id'][$goods_style_facility_status_id][0]['spec'];
			}
			unset($refs_ii_freezes['goods_style_facility_status_id'][$goods_style_facility_status_id]);
			unset($refs_suppliers['goods_style_facility_status_id'][$goods_style_facility_status_id]);
			unset($refs_variance['goods_style_facility_status_id'][$goods_style_facility_status_id]);
			unset($refs_batchs['goods_style_facility_status_id'][$goods_style_facility_status_id]);
			unset($refs_orders['goods_style_facility_status_id'][$goods_style_facility_status_id]);
			$goods_list[] = $good;
		}
		unset($refs_ii_freezes); 
		unset($refs_suppliers);
		unset($refs_variance);
		unset($refs_batchs);
		unset($refs_orders);
	}
//	$sql_time_end9 = microtime(true); 
//	QLOG::LOG("MERGE_goods_style_facility_id TIME:".($sql_time_end9-$sql_time_start9));
//	QLOG::LOG("INVENTORY_CHECK_USE_ALL_TIME:".($sql_time_end9-$sql_time_start3)." 
//			  category_id : ".$_REQUEST ['category_id']." ; barcode : ".$_REQUEST ['barcode']." ; goods_name : ".$_REQUEST ['goods_name']." ; 
//			  productCode : ".$_REQUEST['productCode']." ; available_facility:".$_REQUEST ['available_facility']." ; 
//			  is_new:".$_REQUEST ['is_new']." ; is_show_urikitamono:".$_REQUEST['is_show_urikitamono']);
}

$smarty->assign('goods_list', $goods_list);


$smarty->assign('is_show_urikitamono',empty($is_show_urikitamono)?0:1);

// 第三方仓库要屏蔽商品分类搜索 ljzhou 2013.04.23
$is_third_party_warehouse = false;
if (check_admin_priv ( 'third_party_warehouse' ) && ($_SESSION ['action_list'] != 'all')) {
	$is_third_party_warehouse = true;
}

$smarty->assign ( 'is_third_party_warehouse', $is_third_party_warehouse );
$smarty->assign ( 'category_list',$category_list);
$smarty->assign ( 'available_facility', $facility_user_list);
$smarty->assign ( 'party_id',$_SESSION['party_id'] );
if ($type == '导出库存清单') {
	// 生成Excel文档
        set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
        require_once 'PHPExcel.php';
        require_once 'PHPExcel/IOFactory.php';
        $filename = "库存清单".(empty($is_show_urikitamono)?"(仅有货)":"(有货和无货)");
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($filename);        
        $sheet = $excel->getActiveSheet();
        $sheet->setCellValue('A1', "分类名称");
        $sheet->setCellValue('B1', "商品名称");
        $sheet->setCellValue('C1', "商家编码");
        $sheet->setCellValue('D1', "商品编码");
        $sheet->setCellValue('E1', "商品货号");
        $sheet->setCellValue('F1', "商品金额");
        $sheet->setCellValue('G1', "串号控制");
        $sheet->setCellValue('H1', "新旧");
        $sheet->setCellValue('I1', "仓库名称");
        $sheet->setCellValue('J1', "库位");
        $sheet->setCellValue('K1', "库存量");
        $sheet->setCellValue('L1', "仓库冻结量");
        $sheet->setCellValue('M1', "销售订单订购数(近一个月)");
        $sheet->setCellValue('N1', "未确认数(近一个月)");
        $sheet->setCellValue('O1', "-gt需出库数(近一个月)");
        $sheet->setCellValue('P1', "-V需出库数(近一个月)");
        $sheet->setCellValue('Q1', "可用库存量");
        $sheet->setCellValue('R1', "采购未入库量");
        if ($_SESSION['party_id'] == 65625){
        $sheet->setCellValue('S1', "物料码");
        $sheet->setCellValue('T1', "是否为易碎品");
        $sheet->setCellValue('U1', "箱规");
        }
        $i=2;
        foreach ($goods_list as $item) {   
            $sheet->setCellValue("A{$i}", $item['cat_name']);
            $sheet->setCellValue("B{$i}", $item['goods_name']);
            $sheet->setCellValue("C{$i}", $item['productCode']);
            $sheet->setCellValue("D{$i}", "'".$item['barcode']);
            $sheet->setCellValue("E{$i}", $item['goods_code']);
            $sheet->setCellValue("F{$i}", $item['shop_price']);
            if( $item['is_serial'] == SERIALIZED){
                 $sheet->setCellValue("G{$i}", '是');
            }else {
            	 $sheet->setCellValue("G{$i}", '否');
            }
            if($item['status_id'] == INV_STTS_AVAILABLE){
               $sheet->setCellValue("H{$i}", '全新');
            }else {
               $sheet->setCellValue("H{$i}", '二手');
            }
            $sheet->setCellValue("I{$i}", $item['facility_name']);
            $sheet->setCellValue("J{$i}", $item['location_barcode']);
            $sheet->setCellValue("K{$i}", $item['storage_count']);
            $sheet->setCellValue("L{$i}", $item['reserve_number']);
            $sheet->setCellValue("M{$i}", $item['order_number']);
            $sheet->setCellValue("N{$i}", $item['unconfirmed_number']);
            $sheet->setCellValue("O{$i}", $item['supplier_return_number']);
            $sheet->setCellValue("P{$i}", $item['variance_number']);
            $sheet->setCellValue("Q{$i}", $item['AVAILABLE_TO_RESERVED']);
            $sheet->setCellValue("R{$i}", $item['purchase_number']);
            if ($_SESSION['party_id'] == 65625){
            $sheet->setCellValue("S{$i}", $item['item_number']);
            if ($item['is_fragile'] == 1){
            $sheet->setCellValue("T{$i}", '是');
            }else {
            $sheet->setCellValue("T{$i}", '否');
            }
            $sheet->setCellValue("U{$i}", $item['spec']);
            }
            $i++;
        }
        if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $output->save('php://output');
            exit;
        }
} elseif ($type == '导出耗材库存') {	
	admin_priv ( '4cw_finance_storage_main_csv', '5cg_storage_csv' );
	header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "导出耗材库存" ) . ".csv" );
	$out = $smarty->fetch ( 'oukooext/consumer_goods_csv.htm' );
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
} else {
	$smarty->display ( 'oukooext/inventory_check.htm' );
}

?>
