<?php 
/**
 * 运费模板指定
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
    !empty($_REQUEST['act']) && in_array($_REQUEST['act'], array('assign','update'))
    ? $_REQUEST['act'] 
    : null ;
    
// 当前页码
$page = 
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;

// 信息
$info = 
    isset($_REQUEST['info'])
    ? $_REQUEST['info'] 
    : false ;
    
// 每页记录数 
$page_size = 10;   

// 消息
if ($info) {
    $smarty->assign('message', $info);
}

// 产品列表
$product_list = $db->getAll("SELECT product_id, sku_id, CONCAT_WS('_', product_id, sku_id) as code, name FROM distribution_product_mapping WHERE status = 'up' ORDER BY productcat_id ASC");
$product_list = Helper_Array::toHashmap((array)$product_list, 'code', 'name');

// 运费模板列表
$postage_list = $db->getAll("SELECT postage_id, description FROM distribution_postage"); 
$postage_list = Helper_Array::toHashmap((array)$postage_list, 'postage_id', 'description');


// 添加
if ($act=='assign' && isset($_POST)) {
    do {
        if (empty($_POST['products']) || !is_array($_POST['products'])) {
            $smarty->assign('message', '没有选择商品');
            break;
        }
       
        if (empty($_POST['postages']) || !is_array($_POST['postages'])) {
            $smarty->assign('message', '没有指定运费模板');
            break;
        }
       
        foreach ($_POST['products'] as $code) {
            $prod_sku_id = explode('_', $code);
            $product_id = $prod_sku_id[0]; 
            $sku_id = $prod_sku_id[1];
            $db->query("DELETE FROM distribution_product_postage WHERE product_id = '{$product_id}' AND sku_id = '{$sku_id}'");
            foreach ($_POST['postages'] as $postage_id) {
                $db->query("INSERT INTO distribution_product_postage (product_id,sku_id,postage_id) VALUES ('{$product_id}','{$sku_id}','{$postage_id}')", 'SILENT');
            }
        }
       
        header("Location: distribution_postage_assign.php?info=". urlencode("更新成功"));
        exit;
    } while (false);
}
// 编辑
else if ($act=='update') {
    $product_id = isset($_REQUEST['product_id']) ? $_REQUEST['product_id'] : $_REQUEST['product_id'] ;
    $sku_id = isset($_REQUEST['sku_id']) ? $_REQUEST['sku_id'] : '';
    if (isset($product_list[$product_id.'_'.$sku_id])) {
        $product_checked = array($product_id.'_'.$sku_id);
        $postage_checked = $db->getCol("SELECT postage_id FROM distribution_product_postage WHERE product_id = '{$product_id}' AND sku_id = '{$sku_id}'");
        $smarty->assign('product_checked', $product_checked);
        $smarty->assign('postage_checked', $postage_checked);
    }
    else {
        $smarty->assign('message', '不存在该产品');
    }
}

$url = 'distribution_postage_assign.php';

// 总数
$sql = "
    SELECT COUNT(*) FROM distribution_product_mapping AS pm
    WHERE EXISTS(SELECT 1 FROM distribution_product_postage WHERE product_id = pm.product_id AND sku_id = pm.sku_id LIMIT 1) 
";
$total = $db->getOne($sql);
if ($total) {
    // 构造分页
    $total_page = ceil($total/$page_size);  // 总页数
    $page = max($page, 1);
    $page = min($page, $total_page);
    $offset = ($page - 1) * $page_size;
    $limit = $page_size; 
    
    // 已分配运费模板的商品 
    $list = $db->getAll("
        SELECT pm.* FROM distribution_product_mapping AS pm 
        WHERE EXISTS(SELECT 1 FROM distribution_product_postage WHERE product_id = pm.product_id AND sku_id = pm.sku_id LIMIT 1)
        LIMIT {$offset}, {$limit}
    ");
    if ($list) {
    	$sql = "
    	   SELECT p.postage_id, p.description 
    	   FROM distribution_product_postage pp 
    	       LEFT JOIN distribution_postage p ON p.postage_id = pp.postage_id 
    	   WHERE pp.product_id = '%s' AND pp.sku_id = '%s'
        ";
        foreach ($list as $key=>$item) {
            $list[$key]['postage'] = $db->getAll(sprintf($sql, $item['product_id'], $item['sku_id']));        	
        }
    }

    // 分页
    $pagination = new Pagination(
        $total, $page_size, $page, 'page', $url, null, $filter
    );
    
    $smarty->assign('pagination', $pagination->get_simple_output());  // 分页
}

$smarty->assign('postage_list', $postage_list);
$smarty->assign('product_list', $product_list);
$smarty->assign('list', $list);
$smarty->assign('filter', $filter);
$smarty->display('oukooext/distribution_postage_assign.htm');
