<?php
/**
 * 库位查询
 * 
 * @author yzhang@leqee.com
 * @copyright 2010.12 leqee.com
 */

define('IN_ECS', true);
require_once ('includes/init.php');
admin_priv('search_product_facility_location');
require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'includes/cls_json.php');
include_once (ROOT_PATH . 'admin/function.php');
// include_once (ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');//By Sinri
require_once (ROOT_PATH . 'includes/helper/array.php');

// 请求
$request = isset($_REQUEST['request']) && in_array($_REQUEST['request'], array(
    'ajax'
)) ? $_REQUEST['request'] : null;

$act = isset($_REQUEST['act']) && in_array($_REQUEST['act'], array(
    'search_pfl'
)) ? $_REQUEST['act'] : null;

/*
 * 处理ajax请求
 */
if ($request == 'ajax') {
    $json = new JSON();
    
    switch ($act) {
        // 查询库位
        case 'search_pfl':
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $style_id = isset($_REQUEST['style_id']) ? intval($_REQUEST['style_id']) : 0;
            
            $spfl = search_product_facility_location($goods_id, $style_id);
            //p($spfl);
            if (empty($spfl)) {
                $spfl = array(
                    'error' => '没有数据，请换个条件重试。'
                );
            }
            echo $json->encode($spfl);
            break;
    }
    
    die();
}

/**
 * 执行 sql 查询
 * 
 * @param int $goods_id
 * @param int $style_id
 */
function search_product_facility_location($goods_id, $style_id = 0)
{
    $db = $GLOBALS['db'];
    $r = array();
    $sql = "SELECT pfl.LOCATION_SEQ_ID, f.FACILITY_NAME, pfl.MIN_QUANTITY, pfl.MOVE_QUANTITY, pfl.FACILITY_ID 
			FROM romeo.product_facility_location pfl 
				LEFT JOIN romeo.facility f ON pfl.FACILITY_ID = f.FACILITY_ID 
			WHERE GOODS_ID = '$goods_id' AND STYLE_ID = '$style_id' ";
    $query = $db->query($sql);
    if ($query !== false) {
        while ($row = mysql_fetch_assoc($query)) {
            $r[] = $row;
        }
    }
    return $r;
}

// 显示界面
$smarty->display('oukooext/search_product_facility_location.htm');



