<?php 

/**
 * 库存预定状态表
 * 
 * @author yxiang@oukoo.com
 * @copyright 2010 ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('inventory_reserved');
require_once('function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');

//
$status =
    isset($_REQUEST['status']) && !empty($_REQUEST['status'])
    ? $_REQUEST['status']
    : NULL;
// 请求
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('search')) 
    ? $_REQUEST['act'] 
    : NULL ;

// 当前页码
$page = 
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;

// 每页多少记录数
$page_size =
    is_numeric($_REQUEST['size']) && $_REQUEST['size'] > 0
    ? $_REQUEST['size']
    : 20 ;
    
// 消息
$message =
    isset($_REQUEST['message']) && trim($_REQUEST['message'])
    ? $_REQUEST['message']
    : false;

    
// 查询每页显示数列表
$page_size_list = array('20' => '20', '50' => '50', '100' => '100', '65535' => '不分页');

// 所处仓库
$facility_list = get_user_facility();
if (empty($facility_list)) {
	die('没有仓库权限'); 
}

// 过滤条件
$filter = array(
    // 每页多少条记录
    'size' => $page_size,
    // 是否已预订成功
    'status' => $status,
    // 仓库
    'facility_id' => 
        isset($_REQUEST['facility_id']) && isset($facility_list[$_REQUEST['facility_id']]) 
        ? $_REQUEST['facility_id'] 
        : NULL ,
);

// 链接
$url = 'inventory_reserved_goods.php';
foreach ($filter as $k => $v) {
	if ($v) {
		$url = add_param_in_url($url, $k, $v);
	}
}

// 查询条件
$where = $join = '';
if (isset($filter['status']) && in_array($filter['status'],array('Y','N'))) {
    $join .= " AND r.STATUS = '{$filter['status']}'";
}
if ($filter['facility_id']) {
	$where .= " AND r.FACILITY_ID = '{$filter['facility_id']}'";
} else {
	$where .= " AND ". facility_sql('r.FACILITY_ID');
}
$sql_from = "
	from 
		romeo.order_inv_reserved p
        inner join romeo.order_inv_reserved_detail r on r.ORDER_INV_RESERVED_ID=p.ORDER_INV_RESERVED_ID {$join}
		inner join ecshop.ecs_order_goods og on og.order_id=r.ORDER_ID and og.rec_id=r.ORDER_ITEM_ID
    where ". party_sql('p.PARTY_ID') ." $where
";
$sql = "
    select
        r.PRODUCT_ID, r.STATUS_ID, r.FACILITY_ID, r.ORDER_ITEM_ID, r.GOODS_NUMBER, r.RESERVED_QUANTITY,
        concat_ws('_', r.PRODUCT_ID, r.STATUS_ID) as idx,
        p.STATUS as ORDER_INV_RES_STATUS, p.ORDER_ID,
        og.goods_name, og.goods_id, og.style_id
    {$sql_from} 
";
$result=$db->getAllRefby($sql,array('idx','ORDER_ID'),$ref_fields,$ref_rowset);
if ($result) {
	// 构造分页
	$total = count($ref_rowset['idx']);
	$total_page = ceil($total/$page_size);  // 总页数
	$page = max($page, 1);
	$page = min($page, $total_page);
	$offset = ($page - 1) * $page_size;
	$limit = $page_size;

	// 取得当页显示的订单列表
    $list=array();
	$productIds=array();
    $i=0;
	$rowset = array_slice($ref_rowset['idx'], $offset, $limit);
    foreach ($rowset as $group) {
        $list[$i]=reset($group);
    	// 库存状态名
    	$list[$i]['statusName']=$_CFG['adminvars']['inventory_status_id'][$list[$i]['STATUS_ID']];
        // 参与预订的订单 
        $list[$i]['orderTotal']=0;
        // 预订成功订单 
        $list[$i]['orderReserved']=0;
        // 预订失败订单 
        $list[$i]['orderUnreserved']=0;

        $order_reserved_a=$order_reserved_y=$order_reserved_n=array();
        foreach($group as $row) {
            if(!isset($order_reserved_a[$row['ORDER_ID']])) {
                $list[$i]['orderTotal']++; 
                $order_reserved_a[$row['ORDER_ID']]=true;
            }
            if($row['ORDER_INV_RES_STATUS']=='N' && !isset($order_reserved_n[$row['ORDER_ID']])) {
                $list[$i]['orderUnreserved']++;
                $order_reserved_n[$row['ORDER_ID']]=true;
            }
            else if($row['ORDER_INV_RES_STATUS']=='Y' && !isset($order_reserved_y[$row['ORDER_ID']])) {
                $list[$i]['orderReserved']++; 
                $order_reserved_y[$row['ORDER_ID']]=true;
            }
        }

        $productIds[$list[$i]['PRODUCT_ID']]=true;
        $i++;
    }
	
	// 取得库存总表
	$request=array(
		'statusId'=>array('INV_STTS_AVAILABLE','INV_STTS_USED'),
		'facilityId'=>array_keys($facility_list),
		'productId'=>array_keys($productIds),
	);
	$handle=soap_get_client('InventoryService');
	$response=$handle->getInventorySummaryByCondition($request);
	$inventorySummaryAssoc=array();
	if(is_object($response->return->InventorySummary)) {
		$obj=$response->return->InventorySummary;
		$primaryKey = $obj->statusId.'_'.$obj->productId;
		$inventorySummaryAssoc[$primaryKey]=$response->return->InventorySummary;
	}
	else if (is_array($response->return->InventorySummary)) {
		foreach($response->return->InventorySummary as $obj) {
			$primaryKey = $obj->statusId.'_'.$obj->productId;
			$inventorySummaryAssoc[$primaryKey]=$obj;
		}
	}
	
	// 取得每个商品的库存总表记录
	foreach($list as $key=>$goods) {
		$primaryKey = $goods['STATUS_ID'].'_'.$goods['PRODUCT_ID'];
		if(isset($inventorySummaryAssoc[$primaryKey])) {
			$list[$key]['availableToReserved'] = $inventorySummaryAssoc[$primaryKey]->availableToReserved;
			$list[$key]['stockQuantity'] = $inventorySummaryAssoc[$primaryKey]->stockQuantity;
		}
	}

    // 分页
    $pagination = new Pagination(
        $total, $page_size, $page, 'page', $url, null, $filter
    );

	$smarty->assign('total', $total);  // 总数
	$smarty->assign('list', $list);    // 当前页列表
    $smarty->assign('pagination', $pagination->get_simple_output());  // 分页	
}


$smarty->assign('url', $url);
$smarty->assign('filter', $filter);                          // 筛选条件
$smarty->assign('page_size_list', $page_size_list);          // 每页显示数列表
$smarty->assign('facility_list', $facility_list);            // 仓库列表

$smarty->display('oukooext/inventory_reserved_goods.htm');

