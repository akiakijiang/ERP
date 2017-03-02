<?php 

/**
 * 查看拣选任务
 * 
 * @author yxiang@leqee.com
 * @copyright 2010 leqee.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
admin_priv('inventory_picklist');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');


// 请求
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('search','cancel')) 
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

if ($message) {
    $smarty->assign('message',$message);    
}
    
// 所处仓库
$facility_list = get_user_facility();
if (empty($facility_list)) {
	die('没有仓库权限'); 
}

// 配送方式
$status_list = array(
    'PICKLIST_INPUT'=>'未打印', 
    'PICKLIST_PRINTED'=>'已打印',
    'PICKLIST_ASSIGNED'=>'已经分配面单',
    'PICKLIST_PICKED'=>'已完成拣选', 
    'PICKLIST_CANCELLED'=>'已取消'
);
// 查询每页显示数列表
$page_size_list = array(
    '20'=>'20', 
    '50'=>'50', 
    '100'=>'100',
    '65535'=>'不分页'
);

// 过滤条件
$filter = array(
    // 每页多少条记录
    'size' => 
        $page_size,
    // 仓库
    'facility_id' => 
        isset($_REQUEST['facility_id']) && isset($facility_list[$_REQUEST['facility_id']]) 
        ? $_REQUEST['facility_id'] 
        : NULL ,
    // 状态
    'status' =>
        isset($_REQUEST['status']) && isset($status_list[$_REQUEST['status']]) 
        ? $_REQUEST['status'] 
        : 'PICKLIST_INPUT' ,
);

// 链接
$url = 'picklist_list.php';
foreach ($filter as $k => $v) {
	if ($v) {
		$url = add_param_in_url($url, $k, $v);
	}
}

// 取消批拣任务
if ($act == 'cancel') {
    try {
        $handle = soap_get_client('PicklistService');
        $handle->cancelPicklist(array(
            'picklistId'=>$_REQUEST['PICKLIST_ID'],
            'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
        ));
        header("Location: ". add_param_in_url($url,'message','操作成功'));
        exit;
    }
    catch (Exception $e) {
        $smarty->assign('message',$e->getMessage());    
    }
}


if ($filter['facility_id']) {
	$condition .= " AND p.facility_id = '{$filter['facility_id']}'";
} else {
	$condition .= " AND ". facility_sql('p.facility_id');
}

// 
$sql_from = "
	from
        romeo.picklist as p
	where
        p.STATUS = '{$filter['status']}' $condition
    ORDER BY
        p.CREATED_STAMP DESC, p.FACILITY_ID
";

// 构造分页
$total = $db->getOne("select count(*) ".$sql_from); // 总记录数
$total_page = ceil($total/$page_size);  // 总页数
$page = max(1, min($page, $total_page));
$offset = ($page-1)*$page_size;
$limit = $page_size;
if ($total > 0) {
    // 分页查询
    $links=array(
        array(
            'sql'=>"select * from romeo.shipment where :in",
            'source_key'=>'PICKLIST_ID',
            'target_key'=>'PICKLIST_ID',
            'mapping_name'=>'shipment',
            'type'=>'HAS_MANY',
        ),
    );
    $list = $db->findAll("select * $sql_from limit $offset, $limit", $links);

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
$smarty->assign('facility_list', $facility_list);            // 用户所处仓库列表
$smarty->assign('status_list', $status_list);                // 各种状态列表 

$smarty->display('shipment/picklist_list.htm');
