<?php 
/**
 * 运费维护
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
require_once('function.php');
admin_priv('distribution_postage_manage');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');

// 操作
$act = 
    !empty($_REQUEST['act']) && in_array($_REQUEST['act'], array('add','update'))
    ? $_REQUEST['act'] 
    : null ;
    
// 当前页码
$page = 
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;

//$filter = array('type'=>$type);

// 信息
$info = 
    isset($_REQUEST['info'])
    ? $_REQUEST['info'] 
    : false ;
    
// 每页记录数 
$page_size = 20;   

// 消息
if ($info) {
    $smarty->assign('message', $info);
}

// 地区
$region_list = $db->getAll("SELECT region_id, region_name FROM ecs_region WHERE region_type = 1");
$region_list = Helper_Array::toHashmap((array)$region_list, 'region_id', 'region_name');

// 配送方式
$shipping_list = getShippingTypes();
$shipping_list = Helper_Array::toHashmap((array)$shipping_list, 'shipping_id', 'shipping_name');

if (!is_null($act)) {
    // 添加
    if ($act=='update' && isset($_POST['postage']))	{
        do {
            $postage = $_POST['postage'];
            array_map('trim', $postage);
            
            if (empty($postage['description'])) {
                $smarty->assign('message', '请填写描述信息，有助于识别');
                break;  
            }
            
            if (empty($postage['region_id']) || !is_array($postage['region_id'])) {
                $smarty->assign('message', '没有选择地区');
                break;	
            } 
    		$postage['region_id'] = implode(',', $postage['region_id']);
    		
            if (empty($postage['shipping_id']) || !is_array($postage['shipping_id'])) {
                $smarty->assign('message', '没有选择快递');
                break;	
            }
            $postage['shipping_id'] = implode(',', $postage['shipping_id']);

            if (!is_numeric($postage['post_fee'])) {
                $smarty->assign('message', '单价运费维护有误');
                break;
            }
            
            if (!empty($postage['extra_fee']) && !is_numeric($postage['extra_fee'])) {
                $smarty->assign('message', '超出运费维护有误');
                break;
            }

            array_map(array($db, 'escape_string'), $postage);
            $postage['created_by_user_login'] = $_SESSION['admin_name'];
            $postage['created'] = date('Y-m-d H:i:s');

            $result = $db->autoExecute('distribution_postage', $postage, 'INSERT', '', 'SILENT');
            if ($result) {	
                header("Location: distribution_postage_manage.php?info=". urldecode("保存成功")); 
                exit;
            }
            else {
            	$smarty->assign("保存失败了". $db->error());
            }
        } while (false);
    }
}

$url = 'distribution_postage_manage.php';

// 总数
$total = $db->getOne("SELECT count(*) FROM distribution_postage");
if ($total) {

    // 构造分页
    $total_page = ceil($total/$page_size);  // 总页数
    $page = max($page, 1);
    $page = min($page, $total_page);
    $offset = ($page - 1) * $page_size;
    $limit = $page_size; 
    
    // 列表
    $postage_list = $db->getAll("SELECT * FROM distribution_postage LIMIT {$offset}, {$limit}");

    // 取得配送地址
    /*
    $region_id_array = Helper_Array::getCols($postage_list, 'region_id');
    $sql = "SELECT region_id, region_name FROM ecs_region WHERE region_id ". db_create_in($region_id_array);
    $ref_fields = $ref_rowset = array();
    $db->getAllRefby($sql, array('region_id'), $ref_fields, $ref_rowset);
    foreach ($postage_list as $key => $postage) {
        if (isset($ref_rowset['region_id'][$postage['region_id']])) {
            $postage_list[$key]['region_name'] = $ref_rowset['region_id'][$postage['region_id']][0]['region_name'];
        }
    }
    */

    // 分页
    $pagination = new Pagination(
        $total, $page_size, $page, 'page', $url, null, $filter
    );
    
    $smarty->assign('pagination', $pagination->get_simple_output());  // 分页
}

$smarty->assign('region_list', $region_list);
$smarty->assign('shipping_list', $shipping_list);
$smarty->assign('postage_list', $postage_list);
$smarty->assign('filter', $filter);
$smarty->display('oukooext/distribution_postage_manage.htm');
