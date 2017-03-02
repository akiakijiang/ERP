<?php
/**
 * 批量入库并且收货到上架容器
 * 
 * @author ljzhou
 */

define('IN_ECS', true);
require('includes/init.php');
require("function.php");

// 通用商品组织权限特殊判断 ljzhou 2013.07.03
if(check_goods_common_party()) {
	admin_priv('ck_in_storage_common');
} else {
	admin_priv('ck_in_storage', 'wl_in_storage');
}

// 判断入库模式
check_in_storage_mode(3);

// 导出csv的权限
$csv = $_REQUEST['csv'];
if ($csv) { admin_priv("admin_other_csv"); }

// 消息
$info = $_REQUEST['info'];

// 当提交搜索时,将搜索请求保存在session中
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['act']=='search')
{
    $_SESSION['batch_in_storage'] = array
    (
        'batch_order_sn' => $_REQUEST['batch_order_sn'],              // 批次采购单号
        'batch_in_time_from'  => $_REQUEST['batch_in_time_from'],      // 订购单时间
        'batch_in_time_to'  => $_REQUEST['batch_in_time_to']          // 订购单时间
    );
}
else
{
	$_SESSION['batch_in_storage'] = array
    (
        'batch_in_time_from'  => date('Y-m-d'),
        'batch_in_time_to'  => date('Y-m-d')
    );
}

/**
 * 搜索列表
 */
$condition = trim(get_batch_condition());

if (!$condition) sys_msg('请选择收货入库查询条件');
$sql = "
	SELECT
        o.*,p.name as party_name,f.facility_name
	FROM 
        {$ecs->table('batch_order_info')} AS o
    LEFT JOIN romeo.party p ON convert(o.party_id using utf8)= p.party_id
    LEFT JOIN romeo.facility f ON o.facility_id = f.facility_id
	WHERE
        true {$condition}
    ORDER BY o.order_time DESC, o.batch_order_id
";
$refs_value_order = $refs_order = array();
$search_orders = $db->getAllRefBy($sql, array('batch_order_id'), $refs_value_order, $refs_order, false);


$smarty->assign('search_orders', $search_orders);


// 取组织仓库权限和人仓库权限的交集 ljzhou 2013-04-20
$smarty->assign ( 'facility_id_list', array ('0' => '未指定仓库' ) + array_intersect_assoc(get_available_facility(),get_user_facility()) );
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('facility_name', facility_mapping($_SESSION['facility_id']));
$smarty->assign('info', $info);
$smarty->display('oukooext/batch_or_in_storage.htm');


/**
 * 根据session中的信息构造查询条件
 */
function get_batch_condition()
{
    $condition = "";

    // 从session 中获取检索条件
    $batch_order_sn = trim($_SESSION['batch_in_storage']['batch_order_sn']);
    $batch_in_time_from  = trim($_SESSION['batch_in_storage']['batch_in_time_from']);
    $batch_in_time_to  = trim($_SESSION['batch_in_storage']['batch_in_time_to']);
    $facility_id  = $_REQUEST['facility_id'];

    if ($batch_order_sn != '')
    {
        $condition .= " AND batch_order_sn LIKE '%{$batch_order_sn}%'";
    }
    
    // 起始时间
    if (!empty($batch_in_time_from))
    {
        $start = $batch_in_time_from;
        $condition .= " AND o.order_time > '{$start}' ";
    }
    
    // 结束时间
    if (!empty($batch_in_time_to))
    {
        $end = date('Y-m-d', strtotime("+1 day", strtotime($batch_in_time_to)));
        $condition .= " AND o.order_time < '{$end}' ";
    }
    
    if($facility_id != 0) {
    	$condition .= " AND o.facility_id = '{$facility_id}' ";
    }
    
    global $db;
    $outShip_facility_list = $db->getCol("select facility_id from romeo.facility where is_out_ship = 'Y' ");
	$outShip_facility = implode("','", $outShip_facility_list);
    $condition .= ' AND '. party_sql('o.party_id')." AND ".facility_sql("o.facility_id")." AND o.facility_id not in  ('{$outShip_facility}') ";
    
    return $condition;
}

?>