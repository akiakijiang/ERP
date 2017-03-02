<?php
/**
 * 供价管理
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
admin_priv('distribution_provide_price');
require_once("function.php");
require_once("pagination.php");
require_once(ROOT_PATH. 'includes/helper/array.php');

$act = $_REQUEST['act'];


/* 添加动作 */
if ($act == 'add_submit') {
    $row = $_POST['row'];

    do {
        if (!floatval($row['price'])) {
            $smarty->assign('message', '没有输入供价');
            break;
        }
        if (!intval($row['provider_id'])) {
            $smarty->assign('message', '没有选择供应商');
            break;
        }
        
        // 取得商品
        $goods = $db->getRow("SELECT goods_name, goods_id FROM {$ecs->table('goods')} WHERE goods_id = '{$row['goods_id']}'");
        if ($goods) {
            // 如果限定了商品颜色
        	if ($row['style_id'] > 0) {
    			$sql = "
    	           SELECT 
    	               gs.style_price, IF(gs.goods_color = '', s.color, gs.goods_color) AS color, gs.sale_status,
    	               s.style_id, s.value
    	           FROM {$ecs->table('goods_style')} AS gs 
    	               INNER JOIN {$ecs->table('style')} AS s ON gs.style_id = s.style_id
    	           WHERE gs.goods_id = '{$goods['goods_id']}' AND gs.sale_status = 'normal' AND s.style_id = '{$row['style_id']}'
    			";
                $style = $db->getRow($sql);
                
    			if (!$style) { 
    				$smarty->assign('message', '该颜色的商品没有找到或已经下架了');
    			    break;
    			}
    				
				$goods['goods_name']  = $goods['goods_name'].' '.$style['color'];
        	}
        } else {
        	$smarty->assign('message', '该商品没有找到');
        	break;
        }

        $row['goods_name'] = $goods['goods_name'];
        $db->autoExecute('provide_price', $row, 'INSERT', '', 'SILENT');
    	if ($db->errno() == 1062) {
    		header("Location:distribution_provide_price.php?message=".urlencode("已经有相同的商品了"));
    		exit;		
    	}
    	// 添加成功
        header("Location:distribution_provide_price.php?message=".urlencode("添加成功"));
        exit;
        
    } while (false);
    
    $smarty->assign('edit', $row);
} 

/* 更新页面 */
elseif ($act == 'update') {
    $goods_id = (int)$_REQUEST['goods_id'];
    $style_id = (int)$_REQUEST['style_id'];
    $row = $db->getRow("SELECT * FROM provide_price WHERE goods_id = '{$goods_id}' AND style_id = '{$style_id}'");
    if ($row) {
        // 取得供应商名
        $row['provider_name'] = $db->getOne("SELECT provider_name FROM {$ecs->table('provider')} WHERE provider_id = {$row['provider_id']}");
    }
    $smarty->assign('edit', $row);
} 

/* 更新动作 */
elseif ($act == 'update_submit') {
    $row = (array)$_POST['row'];
    $goods_id = (int)$_REQUEST['goods_id'];
    $style_id = (int)$_REQUEST['style_id'];
    unset($row['goods_id'], $row['style_id']);

    if (!$row['price']) {
        header("Location:distribution_provide_price.php?message=".urlencode("没有输入供价"));
        exit;
    }
    
    $db->autoExecute('provide_price', $row, 'UPDATE', " goods_id = '{$goods_id}' AND style_id = '{$style_id}' " );
    header("Location:distribution_provide_price.php?message=".urlencode("更新成功"));
    exit;
}

// 取得商品供价列表
$sql = "
    SELECT pp.*, p.provider_name FROM provide_price AS pp
    LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = pp.goods_id
    LEFT JOIN {$ecs->table('provider')} AS p ON p.provider_id = pp.provider_id
    WHERE ". party_sql('g.goods_party_id');
$result = $db->query($sql);
if ($result) {
	$list = array();
	while ($row = $db->fetchRow($result)) {
		$row['product_code'] = encode_goods_id($row['goods_id'], $row['style_id']);  // 商品编码
		$list[] = $row;
	}	
}

// 可添加的商品列表
// FIXME: 现在没有做可以选择商品style的功能
$sql = "
    SELECT goods_name, goods_id FROM {$ecs->table('goods')} 
    WHERE ". party_sql('goods_party_id');
$goods_list = Helper_Array::toHashmap((array)$db->getAll($sql), 'goods_id', 'goods_name');

$smarty->assign('list', $list);
$smarty->assign('goods_list', $goods_list);  // 商品列表
$smarty->display('distributor/distribution_provide_price.htm');

