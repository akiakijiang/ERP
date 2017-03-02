<?php
/**
 * 双十一专用--批量生成批捡单
 * 
 * @author ljzhou 2013.10.30
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

if(!in_array($_SESSION['admin_name'],array('ljzhou','hbai','qdi','cywang')))
{
	die('没有权限');
}
ini_set('max_execution_time', '0');

// 开始具体的筛选
$sql = "
    select 
        oi.party_id,oi.facility_id,oi.shipping_id,es.default_carrier_id
    from
        ecshop.ecs_order_info oi
        inner join ecshop.ecs_shipping es ON oi.shipping_id = es.shipping_id
        inner join romeo.order_shipment m on m.ORDER_ID=CONVERT(oi.ORDER_ID using utf8)
        inner join romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID
        inner join romeo.order_inv_reserved r on oi.order_id = r.order_id
    where
        r.STATUS = 'Y' and s.STATUS = 'SHIPMENT_INPUT' and s.shipping_category = 'SHIPPING_SEND' 
        and oi.shipping_status = '0' and oi.order_status = '1'
        group by oi.party_id,oi.facility_id,s.carrier_id order by oi.party_id desc";
 // Qlog::log('$sql_party_facility:'.$sql);
$party_facilitys = $db->getAll($sql);
$start_time = microtime(true);
pp('start now');
pp('当前party_id:');
pp($_SESSION ['party_id']);
$foreach_num=0;
foreach($party_facilitys as $party_facility) {
	$party_id = $party_facility['party_id'];
	$facility_id = $party_facility['facility_id'];
	$shipping_id = $party_facility['shipping_id'];
	$carrier_id = $party_facility['default_carrier_id'];
	if(!in_array($party_id,array('65574','65553'))) {
		continue;
	}
	pp($party_id.' '.$facility_id.' '.$carrier_id);
	
	$_SESSION ['party_id'] = $party_id;
	$taxonomy = array();
	
	/**
	All Hail Sinri Edogawa!
	聚划算的临时功能要把单品列出来用的
	**/
	$TargetSingles=array();
	
	// 查询每页显示数列表
	$page_size_list = array(
		'5' => '5条',
		'10' => '10条',
		'20' => '20条',
		'50' => '50条', 
		'100' => '100条',
		'65535' => '不限制'
	);
	$taxonomy['page_size'] = $page_size_list;
	
	$sort_time_list= array(
		'reserved_time' => '预定时间',
		'confirm_time' => '确认时间',
		'order_time' => '下单时间',
	);
	
	$taxonomy['sort_time'] = $sort_time_list;
	
	$goods_type_list= array(
		'goods_type_simple' => '单品',
		'goods_type_multy' => '多品',
	);
	
	$taxonomy['goods_type_list'] = $goods_type_list;
	
	
	$taxonomy['tracking_type_list'] = $tracking_type_list;
	
	// 配送状态 
	$shipment_status_list = array(
		'SHIPMENT_INPUT' => '待配货',
		'SHIPMENT_PICKED' => '已完成二次分拣',
	);
	
	//配送方式
	$carrier_list = 
		Helper_Array::toHashmap(getCarriers(), 'carrier_id', 'name');
		
	// 分销商
	$distributor_list =
		array('0'=>'没有分销商') + 
		Helper_Array::toHashmap((array)$slave_db->getAll("select distributor_id,name from distributor where status='NORMAL'"), 'distributor_id','name');
	
	// 请求 如果请求为split的话就干拆分订单的活T_T
	$act = 
	    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('split','search')) 
	    ? $_REQUEST['act'] 
	    : NULL ;
	// 库存预定状态 不明觉厉，好像也用不到的样子，随它去好了
	$reserved_status =
		isset($_REQUEST['reserved_status']) && in_array($_REQUEST['reserved_status'], array('Y','N'))
		? $_REQUEST['reserved_status']
		: 'Y';
	
	// 当前页码
	$page = 
	    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
	    ? $_REQUEST['page'] 
	    : 1 ;
	// 每页多少记录数
		/* All Hail Sinri Edogawa ! */
	$page_size = 100;
	
		/* ! All Hail Sinri Edogawa */
	// 排序方式
	$sort_method =
		isset($_REQUEST['sort']) && trim($_REQUEST['sort'])
	    ? $_REQUEST['sort']
	    : 'reserved_time';
	
	//$smarty->assign('act',$act);
	
	//Sinri Goods Type
	$sel_goods_types=isset($_REQUEST['checkbox_goods_type'])?$_REQUEST['checkbox_goods_type']:$goods_type_list;
	
	//Sinri deal with distributor//GET D_ID_LIST
	//unset($ids);
	$ids=array();
	foreach($distributor_list as $key=>$item){
		$ids[]=$key;
	}
	$distributor_ids =
	        isset($_REQUEST['checkbox_distributor']) // && isset($distributor_list[$_REQUEST['checkbox_distributor']])
	        ? $_REQUEST['checkbox_distributor']
	        : $ids;
	// Goods Type
	$SingleMulti = 
			isset($_REQUEST['checkbox_goods_type'])
			? $_REQUEST['checkbox_goods_type']
			: array('goods_type_simple','goods_type_multy');
	//pp($SingleMulti);
	
	$PARTY_ID_IN_REQUEST=
			isset($_REQUEST['party_id']) && trim($_REQUEST['party_id'])
	        ? $_REQUEST['party_id']
	        : $_SESSION['party_id'];
	
	//Sinri deal with facility
//	$ids=array();
//	foreach($facility_list as $key=>$item){
//		$ids[]=$key;
//	}
//	$facility_ids=isset($_REQUEST['facility_id']) //&& isset($facility_list[$_REQUEST['facility_id']]) 
//	        ? $_REQUEST['facility_id'] 
//	        : $ids;
	
	$facility_ids = array($facility_id);
	// 过滤条件
	$filter = array(
	    // 每页多少条记录
	    'size' => 
	        $page_size,
		// 组织
		'party_id' =>$PARTY_ID_IN_REQUEST,
	    // 仓库
	    'facility_ids' => $facility_ids,
		/*
			//被残暴的大鲵干掉了
	        isset($_REQUEST['facility_id[]']) && isset($facility_list[$_REQUEST['facility_id[]']]) 
	        ? $_REQUEST['facility_id[]'] 
	       : NULL ,
		*/
	    // 配送方式
	    'carrier_id' =>$carrier_id,
	    // 分销商
		'distributor_ids' => $distributor_ids,
		/*
			//被残暴的大鲵干掉了
	        isset($_REQUEST['distributor_id']) && isset($distributor_list[$_REQUEST['distributor_id']])
	        ? $_REQUEST['distributor_id']
	        : NULL,
		*/
	    // 配送状态
		'shipment_status'=>
	        	$shipment_status,
	    // 是否已预定库存
	    'reserved_status'=>
	            $reserved_status,
	    'goods_id' => $goods_id,
	    'style_id' => $style_id,
	    'code' => $code,
		/* 邪恶的大鲵添加的单品鉴定 */
		'SingleMulti' => $SingleMulti,
	);
	
	Qlog::log('start shipment_listV5:page_size:'.$page_size);
	
	$tongji = 'party_id:'.$party_id.' facility_id:'.$facility_id.' init_page_size:'.$page_size.' fiter_type:'.implode($SingleMulti);
	
	
	if($page_size > 100) {
		$page_size=100;
	}
	$shipment_size = $page_size+100; // 筛选发货单的数量
	$product_key_size = 6000; // 商品sku种类
	
	// 对每个product_key的优先级做个赋值
	$sort_product_key = array();
	
	$SingleMulti = array('goods_type_simple','goods_type_multy');
	// 如果不是选择具体的商品组合（单品）
	Qlog::log('multity');
	
	if($SingleMulti == array('goods_type_simple','goods_type_multy')) {
		$product_key_category = ' having res.product_nums >= 1 ';
	} else if($SingleMulti == array('goods_type_simple')) {
		$product_key_category = ' having res.product_nums = 1 ';
	} else if($SingleMulti == array('goods_type_multy')) {
		$product_key_category = ' having res.product_nums > 1 ';
	}
	
	// 热门商品筛选 或者 具体的某个组合（单品）
	$sql_hot_goods  ="select res.product_key,res.product_nums,count(res.shipment_id) as order_nums
		from (
		select os.shipment_id,count(DISTINCT(pm.product_id)) as product_nums,group_concat(DISTINCT(pm.product_id) order by pm.product_id) as product_key
		from ecshop.ecs_order_info oi
		inner join ecshop.ecs_order_goods og ON oi.order_id =og.order_id
		inner join romeo.product_mapping pm ON og.goods_id =pm.ecs_goods_id and og.style_id= pm.ecs_style_id
		inner join romeo.order_shipment os ON convert(oi.order_id using utf8)=os.order_id
		inner join romeo.shipment s ON os.shipment_id = s.shipment_id
		inner join romeo.order_inv_reserved r on oi.order_id = r.order_id
		where 
		        r.STATUS = 'Y' and oi.shipping_status = '0' and oi.order_status = '1' 
		        and s.carrier_id='{$carrier_id}' and oi.facility_id = '$facility_id'
		        -- 未批捡
		        and not exists (select 1 from romeo.batch_pick_mapping bm where bm.shipment_id = os.shipment_id limit 1)
		        and ". facility_sql('oi.FACILITY_ID') ." and ". party_sql('r.PARTY_ID') ."
		group by os.shipment_id
		) as res 
		group by res.product_key
		$product_key_category
		order by order_nums desc
		limit $product_key_size
	";
	// Qlog::log('hot_product_key sql:'.$sql_hot_goods);
	$product_keys = $db->getAll($sql_hot_goods);
	
	$while_num = 0;
	while(!empty($product_keys)) {
		
		$hot_product_keys = array();
		$order_sum = 0;
		foreach($product_keys as $key=>$product_key) {
			if($order_sum > $shipment_size) {
				break;
			}
			$order_sum  += $product_key['order_nums'];
			$hot_product_keys[] = $product_key['product_key'];
			$sort_product_key[$product_key['product_key']] = $product_key['order_nums'];// 对product_key的优先级进行赋值
		}
		if(!empty($hot_product_keys)) {
			$hot_goods = " having product_key ".db_create_in($hot_product_keys);
		}
			
		
		Qlog::log('$hot_goods:'.$hot_goods);
		
		// 开始具体的筛选
		$sql_from = "
		    select 
		        s.SHIPMENT_ID,s.CARRIER_ID,s.PARTY_ID,s.PRIMARY_ORDER_ID, 
		        -- count(distinct(pm.product_id)) as sku_num, 
		        group_concat(distinct(pm.product_id) order by pm.product_id) as sku_num,
		        group_concat(distinct(pm.product_id) order by pm.product_id) as product_key,
		        if(oi.handle_time = 0, oi.order_time, FROM_UNIXTIME(oi.handle_time)) handle_time
		    from
		        ecshop.ecs_order_info oi
		        inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
		        inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
		        inner join romeo.order_shipment m on m.ORDER_ID=CONVERT(oi.ORDER_ID using utf8)
		        inner join romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID
		        inner join romeo.order_inv_reserved r on oi.order_id = r.order_id
		    where
		        r.STATUS = 'Y' and s.STATUS = 'SHIPMENT_INPUT' and s.shipping_category = 'SHIPPING_SEND' 
		        and oi.shipping_status = '0' and oi.order_status = '1'
		        and s.carrier_id='{$carrier_id}' and oi.facility_id = '$facility_id'
		        -- 订单处理时间已到
		        and (oi.handle_time = 0 or oi.handle_time < UNIX_TIMESTAMP())
		        -- 未打印
		        and not exists (select 1 from order_mixed_status_history WHERE order_id = oi.order_id AND pick_list_status = 'printed' AND created_by_user_class = 'worker' LIMIT 1)
		        -- 未批捡
		        and not exists (select 1 from romeo.batch_pick_mapping bm where bm.shipment_id = s.shipment_id limit 1)
		        -- 所有订单都预定了
		        and not exists (select 1 from romeo.order_inv_reserved r2 left join romeo.order_shipment os2 ON r2.order_id=os2.order_id where os2.shipment_id=s.shipment_id and r2.status='N' limit 1)
		        -- 具体单品直接筛选
		        $simple_goods_fiter
		        and ". facility_sql('oi.FACILITY_ID') ." and ". party_sql('r.PARTY_ID') ." 
		        group by 
		        s.SHIPMENT_ID
		    -- 在热门商品里面
		    $hot_goods
		    -- limit $shipment_size
		";
		// Qlog::log('start sql_from:sql:'.$sql_from);
		//die();
		//pp($sql_from);
		$ref_fields=$ref_rowset=array();
		$list=$db->getAllRefby($sql_from,array('SHIPMENT_ID'),$ref_fields,$ref_rowset);
		Qlog::log('start sql_from end:');
		
		
		if ($list) {
		    // 对优先级进行赋值
			$sort_nums = array();
			foreach($list as $key=>$item) {
				if(empty($sort_product_key[$item['product_key']])) {
					$sort_product_key[$item['product_key']] = 0;
				}
				$list[$key]['sort_num'] = $sort_product_key[$item['product_key']];
				$sort_nums[] = $sort_product_key[$item['product_key']];
			}
			// 数量多的排前面
			array_multisort( $sort_nums, SORT_DESC, $list);
		    
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
		    $sql="
		        select 
		            o.order_id,o.order_status,o.pay_status,o.shipping_status,o.facility_id,o.shipping_id,o.shipping_name,
		            o.order_time,o.order_sn,o.consignee,o.distributor_id,
		        	IF( o.order_type_id = 'SALE', 
		        		(	SELECT 	a.action_time
							FROM 	ecshop.ecs_order_action a
							WHERE 	a.order_id =  o.order_id
							AND 	a.order_status = '1'
							AND NOT EXISTS (
								SELECT 		b.action_time
								FROM 		ecshop.ecs_order_action b
								WHERE 		b.order_id = o.order_id
								AND 		b.order_status =  '2'
								AND 		b.action_time > a.action_time
								ORDER BY 	b.action_time DESC 
								LIMIT 1
							)
							ORDER BY a.action_time ASC 
							LIMIT 1
		        		), 
		        		o.order_time ) AS confirm_time,
		            r.STATUS as RESERVED_STATUS, r.RESERVED_TIME AS reserved_time, m.SHIPMENT_ID
		        from
		            romeo.order_shipment m
		            left join romeo.order_inv_reserved r on r.ORDER_ID = m.ORDER_ID
		            left join ecshop.ecs_order_info as o on o.order_id = m.ORDER_ID
		        where
		            m.SHIPMENT_ID ".db_create_in($ref_fields['SHIPMENT_ID']);
		    
		    // Qlog::log('start first filter:sql:'.$sql);
		    
		    $result=$db->getAllRefby($sql,array('SHIPMENT_ID'),$ref_fields1,$ref_rowset1);
		    Qlog::log('start first filter:end:');
		    
		//	    pp('result:');pp($result);
		//	    pp('ref_fields1:');pp($ref_fields1);
		//	    pp('ref_rowset1:');pp($ref_rowset1);
		    if ($result) {
		        $unset=array();
		        foreach($list as $key=>$item) {
		        	$list[$key]['printed']=0;
		        	$sort['printed'][$key]=0;
		              
		            $shipment_id=$item['SHIPMENT_ID'];
		            if (isset($ref_rowset1['SHIPMENT_ID'][$shipment_id])) {
			            // 发货单打印状态（由订单打印状态判断）
			            $order_list = &$ref_rowset1['SHIPMENT_ID'][$shipment_id];
			            
			            
			            foreach($order_list as $order_item) {
		
							//大鲵开始改造了V5！
							if(isset($order_item[$sort_method])){
								$sort[$sort_method][$key]=$order_item[$sort_method];
							}
							if (!isset($sort[$sort_method][$key]) || !$order_item[$sort_method]){
								$sort[$sort_method][$key]=$order_item['reserved_time'];
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
						if (isset($carrier_list[$list[$key]['CARRIER_ID']])) {
							$taxonomy['carrier'][$list[$key]['CARRIER_ID']]++;
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
		        if(!empty($unset)){
		            foreach($unset as $k) {
		                unset($list[$k]);
		                unset($sort['printed'][$k]);
		                unset($sort[$sort_method][$k]);
		            }
		        }
		    }
			
		
		    Qlog::log('start filter SM list_count:'.count($list));
		
		    // 如果需要统计商品数量排行
		    $sql  = "select res.goods_name,res.product_key,count(res.shipment_id) as sku_sum
					from
					(select 
					        group_concat(distinct(g.goods_name) separator '</br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') as goods_name,s.shipment_id,group_concat(distinct(pm.product_id) order by pm.product_id) as product_key
					    from
					        ecshop.ecs_order_info oi
					        inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
					        left  join ecshop.ecs_goods g ON og.goods_id = g.goods_id
					        inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
					        inner join romeo.order_shipment m on m.ORDER_ID=oi.ORDER_ID
					        inner join romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID
					    where s.shipment_id ".db_create_in($ref_fields['SHIPMENT_ID']).
					" group by s.shipment_id 
					) as res
		            group by res.product_key
		            order by sku_sum desc";
			// Qlog::log('$simple_tongji sql:'.$sql);
			$TS = $db->getAll($sql);
		//		pp($TS);
		
		    Qlog::log('end filter SM');
		    
		    Qlog::log('start condition filter:shipment_list_filter ');
		
			/**
			All Hail Sinri Edogawa!
			End of Juhuasuan Mode Code
			**/
		
		    // 排序
		    // 1. 按是否已打印拣货单
		    // 暂时不按预定排序
		//    if (!empty($sort['printed']) || !empty($sort[$sort_method])) {
		//        array_multisort( $sort['printed'], SORT_ASC,$sort[$sort_method], SORT_ASC, $list);
		//    }
		    Qlog::log('start condition filter:shipment_list_filter ');
		    
			// 根据条件过滤======================================================================================================================================================================================
		    $errlist=shipment_list_filter($list,$PARTY_ID_IN_REQUEST,$facility_ids, $filter);
		
		    // 最后对合法的$list进行2次筛选，使得最后得到的shipment数量尽可能多 ljzhou 2013-09-15
		    $fiter_shipment_ids = array();
		    foreach($list as $key=>$value) {
		    	$fiter_shipment_ids[] = $list[$key]['SHIPMENT_ID'];
		    }
		    Qlog::log('end condition filter:shipment_list_filter ');
		
		
			$good_shipment_idss = array();
			$good_shipment_ids = $fiter_shipment_ids;
		
		    // 得到本批次的sku总数
		    $sku_nums = get_sku_nums($good_shipment_ids);
			Qlog::log('end get_sku_nums ');
			
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
		    Qlog::log('start get_shipments_takiruka_info ');
		    
		    $smarty->assign('sinri_errlist',$errlist);
			if(sizeof($errlist)){
//				pp($errlist);
				$sinri_errinfo=get_shipments_takiruka_info($errlist,$PARTY_ID_IN_REQUEST,$facility_ids);
				pp('上架特殊处理');
//				pp($sinri_errinfo);
				//上架特殊处理：先update一下库位数量+50000，下面的函数都是insert
				foreach($sinri_errinfo as $info) {
					if(!empty($info['barcode'])) {
						auto_grouding_location($party_id,$facility_id,$info['product_id'],$info['barcode'],'2J-C-02-61');
					} else {
						pp('条码没有维护');
						pp($info);
						Qlog::log('条码没有维护: product_id:'.$info['product_id']);
					}
		//            break;
				}
		    } else {
		    }
			Qlog::log('end get_shipments_takiruka_info ');
		
		
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
		    
		    pp('最后的筛选结果：');
//		    pp($list);
		    $fiter_shipment_ids2 = array();
		    foreach($list as $key=>$value) {
		    	$fiter_shipment_ids2[] = $list[$key]['SHIPMENT_ID'];
		    }
//		    pp('shipment_ids:');
//		    pp($fiter_shipment_ids2);
		    
		    $start_time2 = microtime(true);
		    $bpsn=record_shipments_to_batch_pick($fiter_shipment_ids2,$_SESSION['admin_name']);
		    if($bpsn['bpsn'] != 0) {
		    	get_batch_pick_path_merged($bpsn['bpsn']);
		    } else {
		    	pp('生成批捡单失败');
		    	Qlog::log('生成批捡单失败');
		    	$end_time2 = microtime(true);
		        $cost_time2 = $end_time2-$start_time2;
		        Qlog::log('shipment_listV5_for_1111 basic_info:'.$tongji.' get_batch_pick_path_merged $bpsn:'.$bpsn['bpsn'].' cost_time:'.$cost_time2);
		    	break;
		    }
		    $end_time2 = microtime(true);
		    $cost_time2 = $end_time2-$start_time2;
		    Qlog::log('shipment_listV5_for_1111 basic_info:'.$tongji.' get_batch_pick_path_merged $bpsn:'.$bpsn['bpsn'].' cost_time:'.$cost_time2);
		    
		} else {
			pp('选个好组织非常重要的说。这个组织下面没有可以筛选的东西。party_id:'.$party_id);
			Qlog::log("选个好组织非常重要的说,这个组织下面没有可以筛选的东西。party_id:".$party_id);
			break;// break while
		}

		$while_num++;
		if($while_num == 2) {
//			break;
		}
		
		// 要动态计算，防止死循环
		$product_keys = $db->getAll($sql_hot_goods);
		
	} // end while

		
	$tongji .= $tongji.' end_page_size:'.$total.' bpsn:'.$bpsn['bpsn'];
	$end_time = microtime(true);
	$cost_time = $end_time-$start_time;
	Qlog::log('shipment_listV5_for_1111 basic_info:'.$tongji.' cost_time:'.$cost_time);
	
		
	$foreach_num++;
	if($foreach_num == 2) {
//		break;
	}
	// just test once
} // end foreach party_facility

/**
 * 根据查询条件过滤订单列表
 *
 * @param array $list 订单列表
 */
function shipment_list_filter(& $list, $PARTY_ID_IN_REQUEST,$facility_ids, $filter = array()) {
    if (empty($list) || empty($filter)) return;

	$error_shipments=array();
	// 批量处理好，得到单个shipment_id容器预定数量不够的
	$check_shipment_ids = array();
    foreach($list as $key=>$value) {
    	$check_shipment_ids[] = $list[$key]['SHIPMENT_ID'];
    }
    $esid=check_shipments_tariruka($check_shipment_ids,$PARTY_ID_IN_REQUEST,$facility_ids);
    $error_shipments = $esid;

    foreach ($list as $key => $item) {
        $flag = true;

        if(in_array($item['SHIPMENT_ID'],$esid)) {
        	$flag=false;
        	$error_shipments[] = $item['SHIPMENT_ID'];
        }

        if ($flag && isset($filter['party_id'])) {
        	$flag = $flag && ($item['PARTY_ID'] == $filter['party_id']);
        }
		if ($flag && isset($filter['facility_ids'])) {
			$flag_f=false;
			foreach($filter['facility_ids'] as $ffi){
				$flag_f=$flag_f || ($item['FACILITY_ID'] == $ffi);
			}
			$flag=$flag && $flag_f;
		}
        if ($flag && isset($filter['carrier_id'])) {
            $flag = $flag && ($item['CARRIER_ID'] == $filter['carrier_id']);
        }
		if ($flag && isset($filter['distributor_ids'])) {
			$flag_d=false;
			foreach($filter['distributor_ids'] as $fdi){
				$flag_d = $flag_d || ($item['DISTRIBUTOR_ID'] ==  $fdi);
			}
			$flag=$flag && $flag_d;
		}
		
        if (!$flag) {
            unset($list[$key]);
        }
    }
    
    return $error_shipments;
}

?>