<?php

/**
 * 分销商品售价管理
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
admin_priv('distribution_sale_price');
require_once("function.php");
require_once("pagination.php");
require_once(ROOT_PATH. 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
include_once(ROOT_PATH . 'RomeoApi/lib_currency.php');

$act = $_REQUEST['act'];

// 查询条件
$distributor_id = 
    isset($_POST['row']['distributor_id']) ? 
    $_POST['row']['distributor_id'] : 
    ( isset($_REQUEST['distributor_id']) ? $_REQUEST['distributor_id'] : '' ) ;
        
$row = $_POST['row'];

// 可添加的商品列表
// FIXME: 现在没有做可以选择商品style的功能
$sql = "
    SELECT goods_name, goods_id FROM {$ecs->table('goods')} 
    WHERE ". party_sql('goods_party_id');
$goods_list = Helper_Array::toHashmap((array)$db->getAll($sql), 'goods_id', 'goods_name');


// 获取分销商列表
$distirbutor_list = Helper_Array::toHashmap((array)distribution_get_distributor_list(), 'distributor_id', 'name');
$distirbutor_list = array('0' => '- 不限分销商', 'TMALL' => '- 天猫', 'MARKET' => '- 集市') + $distirbutor_list;

/* 添加动作 */
if ($act == 'add_submit') {
    $row = $_POST['row'];

    do {
        if (!floatval($row['price'])) {
            $smarty->assign('message', '没有输入售价');
            break;
        }
        
        if ($row['distributor_id'] == '') {
        	$smarty->assign('message', '没有选择分销商');
            break;
        }elseif($row['distributor_id'] == 'TMALL' || $row['distributor_id'] == 'MARKET'){ //查找天猫集市分销商
        	$sql = "select distributor_id from ecshop.distributor where shop_type = '{$row['distributor_id']}' and party_id = '{$_SESSION['party_id']}'";
//        	Qlog::log($sql);
        	$distributor_id_list = $db->getAll($sql);
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
		
		$sql = "select d.name, pa.currency 
				from ecshop.distributor d 
				inner join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id 
				inner join romeo.prepayment_account pa on md.main_distributor_id = pa.supplier_id and pa.prepayment_account_type_id = 'DISTRIBUTOR'
				where d.distributor_id = %d ";
		
        $row['goods_name'] = $goods['goods_name'];
        $row['created'] = $row['updated'] = date('Y-m-d H:i:s');
        if(is_numeric($row['distributor_id'])){
        	$Dcurrency = $db->getRow(sprintf($sql, $row['distributor_id']));
        	if($Dcurrency && $Dcurrency['currency'] != $row['currency']){
        		$smarty->assign('message', '该分销商的币种为'.$Dcurrency['currency']);
        		break;
        	}
        	$db->autoExecute('distribution_sale_price', $row, 'INSERT', '', 'SILENT');
        }else if($distributor_id_list){
        	$message = "";
        	foreach ( $distributor_id_list as $distributor_id ) {
        		$Dcurrency = $db->getRow(sprintf($sql, $distributor_id['distributor_id']));
	        	if($Dcurrency && $Dcurrency['currency'] != $row['currency']){
	        		$message .= '分销商'.$Dcurrency['name'].'的币种为'.$Dcurrency['currency'].',';
	        		continue;
	        	}
        		$row['distributor_id'] = $distributor_id['distributor_id'];
       			$db->autoExecute('distribution_sale_price', $row, 'INSERT', '', 'SILENT');
			}
			if($message != ""){
				$smarty->assign('message', $message.'设置预存款失败');
				break;
			}
        }
        
    	// 添加成功
        header("Location:distribution_sale_price.php?distributor_id={$distributor_id}&message=".urlencode("添加成功"));
        exit;
        
    } while (false);
    
    $smarty->assign('edit', $row);
} 

/* 更新页面 */
elseif ($act == 'update') {
    $pkv = (int)$_REQUEST['id'];
    $row = $db->getRow("SELECT * FROM distribution_sale_price WHERE distribution_sale_price_id = '{$pkv}'");
    $smarty->assign('edit', $row);
}

/* 更新动作 */
elseif ($act == 'update_submit') {
    $row = (array)$_POST['row'];
    $pkv = (int)$row['distribution_sale_price_id'];
    unset($row['goods_id'], $row['style_id'], $row['distributor_id']);  // 不能更改商品和分销商

    if (!$row['price']) {
        header("Location:distribution_sale_price.php?distributor_id={$distributor_id}&message=".urlencode("没有输入供价"));
        exit;
    }
    
    $row['updated'] = date('Y-m-d H:i:s');
    $db->autoExecute('distribution_sale_price', $row, 'UPDATE', " distribution_sale_price_id = '{$pkv}'" );
    header("Location:distribution_sale_price.php?distributor_id={$distributor_id}&message=".urlencode("更新成功"));
    exit;
}elseif($act == 'search'){
	$search_goods_id=isset($_REQUEST['search_goods_id']) ? $_REQUEST['search_goods_id'] : '0'; 
	
//	Qlog::log('distributor_id='.$distributor_id);
	$condition = "";
	if($distributor_id == 'TMALL' || $distributor_id == 'MARKET'){ //查找天猫集市分销商
    	$sql = "select distributor_id from ecshop.distributor where shop_type = '{$distributor_id}' and party_id = '{$_SESSION['party_id']}'";
//        Qlog::log($sql);
    	$distributor_id_list = $db->getAll($sql);
    	$distributor_list = Helper_Array::getCols($distributor_id_list, 'distributor_id');
    	$condition = " AND distributor_id in (".implode(',',$distributor_list).") ";
    }elseif(is_numeric($distributor_id)){
    	$condition = " AND distributor_id = {$distributor_id} ";
    }
	
    $sql = "
        SELECT sp.* FROM distribution_sale_price AS sp
        LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = sp.goods_id
        WHERE ". party_sql('g.goods_party_id') ." 
        	".$condition." and ( g.goods_id='{$search_goods_id}' or {$search_goods_id} = 0 )
        ORDER BY goods_id, style_id, valid_from ASC
    ";
//    Qlog::log($sql);
    $result = $db->query($sql);
    if ($result) {
    	$list = array();
    	while ($row = $db->fetchRow($result)) {
    		$row['product_code'] = encode_goods_id($row['goods_id'], $row['style_id']);  // 商品编码
    		$row['distributor_name'] = $distirbutor_list[$row['distributor_id']];
    		$list[] = $row;
    	}
    }
}


$smarty->assign('list', $list);  // 售价列表
$smarty->assign('goods_list', $goods_list);  // 商品列表
$smarty->assign('distirbutor_list', $distirbutor_list);  // 分销商列表
$smarty->assign('currency_list', get_currencys());  //币种列表
$smarty->assign('filter', array('distributor_id' => $distributor_id));  // 过滤条件
$smarty->display('distributor/distribution_sale_price.htm');
