<?php
/**
 * 双十一专用--批量打印拣货单
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

$taxonomy = array();

/**
All Hail Sinri Edogawa!
聚划算的临时功能要把单品列出来用的
**/
$TargetSingles=array();
pp($_REQUEST);

$one_product_key='';
if($_REQUEST['one_product_key']){
    $one_product_key=$_REQUEST['one_product_key'];
}

// 所处仓库
$facility_list = get_user_facility();
if (empty($facility_list)) {
	die('没有仓库权限');
}
//当前所在组织
$party_id = $_SESSION ['party_id'];


//配送方式
$carrier_list = 
	Helper_Array::toHashmap(getCarriers(), 'carrier_id', 'name');

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

// 分销商
$distributor_list =
	array('0'=>'没有分销商') + 
	Helper_Array::toHashmap((array)$slave_db->getAll("select distributor_id,name from distributor where status='NORMAL'"), 'distributor_id','name');

// 请求 如果请求为split的话就干拆分订单的活T_T
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('split','search')) 
    ? $_REQUEST['act'] 
    : NULL ;
// 配送状态
$shipment_status =
	isset($_REQUEST['shipment_status']) && in_array($_REQUEST['shipment_status'], $shipment_status_list)
	? $_REQUEST['shipment_status']
	: 'SHIPMENT_INPUT';
// 当前页码
$page = 
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;
// 每页多少记录数
	/* All Hail Sinri Edogawa ! */
$page_size =
    is_numeric($_REQUEST['size']) && $_REQUEST['size'] > 0
    ? $_REQUEST['size']
    : 0 ;
if($page_size == 0){
	$page_size=is_numeric($_REQUEST['SINRI_PAGE_SIZE_VALUE']) && $_REQUEST['size']>0
	? $_REQUEST['SINRI_PAGE_SIZE_VALUE']
	: 100;
}
	/* ! All Hail Sinri Edogawa */
// 消息
$message =
    isset($_REQUEST['message']) && trim($_REQUEST['message'])
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
	// 默认进去后不筛选
	$sinri_shall_list=false;
}
$smarty->assign('sinri_shall_list',$sinri_shall_list);
//$smarty->assign('act',$act);

//Sinri Goods Type
$sel_goods_types=isset($_REQUEST['checkbox_goods_type'])?$_REQUEST['checkbox_goods_type']:$goods_type_list;
$smarty->assign('sinri_test_goods_types',$sel_goods_types);

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
$smarty->assign('sinri_test_distributor_ids',$distributor_ids);
// Goods Type
// 默认单品勾选
$SingleMulti = 
		isset($_REQUEST['checkbox_goods_type'])
		? $_REQUEST['checkbox_goods_type']
		: array('goods_type_simple','goods_type_multy');
//pp($SingleMulti);

$carrier_id=
        isset($_REQUEST['checkbox_carrier']) && isset($carrier_list[$_REQUEST['checkbox_carrier']]) 
        ? $_REQUEST['checkbox_carrier']
        : NULL;

$PARTY_ID_IN_REQUEST=
		isset($_REQUEST['party_id']) && trim($_REQUEST['party_id'])
        ? $_REQUEST['party_id']
        : $_SESSION['party_id'];

//Sinri deal with facility
$ids=array();
foreach($facility_list as $key=>$item){
	$ids[]=$key;
}
$facility_ids=isset($_REQUEST['facility_id'])
        ? $_REQUEST['facility_id'] 
        : $ids;
$smarty->assign('sinri_test_facility_ids',$facility_ids);


// 过滤条件
$filter = array(
    // 每页多少条记录
    'size' => 
        $page_size,
	// 组织
	'party_id' =>$PARTY_ID_IN_REQUEST,
    // 仓库
    'facility_ids' => $facility_ids,
    // 配送方式
    'carrier_id' =>$carrier_id,
    // 分销商
	'distributor_ids' => $distributor_ids,
    // 配送状态
	'shipment_status'=>
        	$shipment_status,
    'goods_id' => $goods_id,
    'style_id' => $style_id,
    'code' => $code,
	/* 邪恶的大鲵添加的单品鉴定 */
	'SingleMulti' => $SingleMulti,
);
//pp($filter);
//die();

Qlog::log('start shipment_listV5:page_size:'.$page_size);

$tongji = 'party_id:'.$party_id.' facility_id:'.$facility_id.' init_page_size:'.$page_size.' fiter_type:'.implode($SingleMulti);

$start_time = microtime(true);

// 链接
$url = 'shipment_listV5_for_1111.php';	
$url = add_param_in_url($url, 'size', $filter['size']);

if($page_size > 40000) {
	$page_size=40000;
}
$shipment_size = $page_size+100; // 筛选发货单的数量
$product_key_size = 100; // 商品sku种类


// 为了提高前面的筛选效率，如果有仓库，快递信息时，直接加上过滤条件,避免最后过滤导致订单量不够
$filter_condition='';
if(!empty($filter['facility_ids'])) {
	$filter_condition .= " and oi.facility_id ".db_create_in($filter['facility_ids']);
}
if(!empty($filter['carrier_id'])) {
	$filter_condition .= " and s.carrier_id ='{$filter['carrier_id']}' ";
}
if(!empty($filter['distributor_ids'])) {
	$filter_condition .= " and oi.distributor_id ".db_create_in($filter['distributor_ids']);
}


// 对每个product_key的优先级做个赋值
$sort_product_key = array();


// 如果不是选择具体的商品组合（单品）
if(empty($one_product_key)) {
	Qlog::log('multity');
	// 得到热门商品的product_key,和排序信息
    $result = get_hot_product_key_detail($SingleMulti,$product_key_size,$shipment_size,$filter_condition);
    $hot_product_keys=$result['hot_product_keys'];
    $sort_product_key=$result['sort_product_key'];
} 
// 具体的商品组合
else {
	Qlog::log('single');
	$hot_product_keys = array($one_product_key);
}
Qlog::log('$hot_product_keys:'.implode($hot_product_keys,','));

// 开始筛选热门商品的shipment
$hot_shipment_res = get_hot_goods_shipment($hot_product_keys,$shipment_size,$filter_condition);

$list= $ref_fields = $ref_rowset = array();
$list = $hot_shipment_res['list'];
$ref_fields = $hot_shipment_res['ref_fields'];
$ref_rowset = $hot_shipment_res['ref_rowset'];

Qlog::log('start sql_from end:');

// 对优先级进行赋值
$sort_nums = array();
$sort_product = array();
foreach($list as $key=>$item) {
	if(empty($sort_product_key[$item['product_key']])) {
		$sort_product_key[$item['product_key']] = 0;
	}
	$list[$key]['sort_num'] = $sort_product_key[$item['product_key']];
	$sort_nums[] = $sort_product_key[$item['product_key']];
	$sort_product[] = $item['product_key'];
}
// 数量多的排前面,相同数量的按product_key排序
array_multisort( $sort_nums, SORT_DESC, $sort_product, SORT_ASC, $list);

if ($list) {
    // 统计总数
        
    // 开始筛选shipment详细信息
	$shipment_res = get_shipment_details($ref_fields['SHIPMENT_ID']);
	
	$result= $ref_fields1 = $ref_rowset1 = array();
	$result = $shipment_res['result'];
	$ref_fields1 = $shipment_res['ref_fields1'];
	$ref_rowset1 = $shipment_res['ref_rowset1'];
    Qlog::log('start first filter:end:');
    
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

        // 从列表中去掉异常的shipment_id
        if(!empty($unset)){
            foreach($unset as $k) {
                unset($list[$k]);
                unset($sort['printed'][$k]);
            }
        }
    }
	

    Qlog::log('start filter SM list_count:'.count($list));

    //得到数量排序后的商品
    $show_product_keys = get_sorted_product_keys($ref_fields['SHIPMENT_ID']);
    $smarty->assign('Sinri_TargetSingles',$show_product_keys);

    Qlog::log('end get_sorted_product_keys');
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
		$sinri_errinfo=get_shipments_takiruka_info($errlist,$PARTY_ID_IN_REQUEST,$facility_ids);
    	$smarty->assign('sinri_errinfo',$sinri_errinfo);
    } else {
    	$smarty->assign('sinri_errinfo', array());
    }
	Qlog::log('end get_shipments_takiruka_info ');
	
	/** All Hail Sinri Edogawa ! **/
	$smarty->assign('sku_nums',$sku_nums);
	$smarty->assign('sinri_size',$page_size);
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

    // 分页
	//为了让URL能够瓦全，伪造一个Filiter
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

    /* All Hail Sinri Edogawa ! */
    if(isset($carrier_id)){
		$smarty->assign('sinri_best_carrier_id',$carrier_id);
    }else {
	    $max_cid=0;
	    $max_c=-1;
	    foreach ($taxonomy['carrier'] as $id => $count) {
	    	if($max_c<$count){
	    		$max_c=$count;
	    		$max_cid=$id;
	    	}
	    }
    	$smarty->assign('sinri_best_carrier_id',$max_cid);
    	
	}
} else {
	die('<h1>选个好组织非常重要的说。这个组织下面没有可以筛选的东西。</h1>');
}

$tongji .= $tongji.' end_page_size:'.$total;
$end_time = microtime(true);
$cost_time = $end_time-$start_time;
Qlog::log('shipment_listV5_for_1111 basic_info:'.$tongji.' cost_time:'.$cost_time);

$smarty->assign('SingleMulti',$SingleMulti);
$smarty->assign('cost_time',$cost_time);
$smarty->assign('url', $url+"&act=search");
$smarty->assign('filter', $filter);                          // 筛选条件
$smarty->assign('page_size_list', $page_size_list);          // 每页显示数列表
$smarty->assign('facility_list', $facility_list);            // 用户所处仓库列表
$smarty->assign('carrier_list', $carrier_list);              // 配送方式列表 
$smarty->assign('distributor_list',$distributor_list);       // 分销商

$smarty->display('shipment/shipment_listV5_for_11111.htm');


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
			foreach($filter['facility_ids'] as $key=>$ffi){
				if($item['FACILITY_ID'] == $ffi) {
					$flag_f = true;
					break;
				}
			}
			
			$flag=$flag && $flag_f;
		}
        if ($flag && isset($filter['carrier_id'])) {
            $flag = $flag && ($item['CARRIER_ID'] == $filter['carrier_id']);
        }

		if ($flag && isset($filter['distributor_ids'])) {
			$flag_d=false;
			foreach($filter['distributor_ids'] as $key=>$fdi){
				if($item['DISTRIBUTOR_ID'] == $fdi) {
					$flag_d = true;
					break;
				}
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