<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');
admin_priv('haiguan_goods_info');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

/*
 * 跨境购商品信息维护 by hzhang1 2015-07-24
 */
$act = $_REQUEST ['act'];
$req = isset($_REQUEST['request']) && 
    in_array($_REQUEST['request'], array('ajax')) 
    ? $_REQUEST['request'] 
    : null;
    
/*
 * 处理ajax请求 by hzhang1 2015-07-24
 */
if ($req == 'ajax')
{
    $json = new JSON;
    switch ($act) 
    { 
        case 'get_select_shop':
            $nick = $_REQUEST['q'];
            $sql = "
			select nick from ecshop.taobao_shop_conf where  nick like '%{$nick}%' 
			";
			$result=$GLOBALS['db']->getAll($sql);
			
            if ($result)
                print $json->encode($result);
            else
                print $json->encode(array('error' => '店铺不存在'));
            break;
        case 'get_select_goods':
            $goods_name = $_GET['pro_name'];
            $limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 40 ;
            print $json->encode(distribution_get_goods_list(NULL, NULL, $_POST['q'], $limit));  
            break;
    }
    exit;
}


$result = call_user_func('search_goods',$_GET);

$smarty->assign('goods_list',$result['order_list']);
$smarty->assign('message_error',$message_error);
$smarty->assign('message_success',$message_success);
$smarty->assign('Pager',$result['Pager']);

$smarty->display ( 'kuajing_items.html' );

function getCondition() {
	global $ecs;
	$result = array();
	$condition = "";
	$barcode = trim ( $_REQUEST ['barcode'] );
	$goods_name = trim ( $_REQUEST ['goods_name'] );
	$item_code = $_REQUEST ['item_code'];
	$start_time = $_REQUEST ['start_time'];
	$end_time = $_REQUEST ['end_time'];

	if ($barcode != '') {
		$condition .= " AND nick LIKE '%{$barcode}%' ";
	}
	if ($goods_name != '') {
		$condition .= " AND goods_name LIKE '%{$goods_name}%' ";
	}
	if ($item_code != '') {
		$condition .= " AND item_code = '{$item_code}' ";
	}
	if ($start_time != '') {
		$condition .= " AND last_updated_stamp > '{$start_time}' ";
	}
	if ($end_time != '') {
		$condition .= " AND last_updated_stamp < '{$end_time}' ";
	}

	$result['simple_cond'] = $condition;
	
	return $result;
}


function search_goods($args) {
	global $db;
	$cond = getCondition();
	$session_party_id = $_SESSION['party_id'];
	$page = intval($args['page']);
	$page = max(1, $page);
	$limit = 10;
	$offset = $limit * ($page-1);
	$goods_list = array();
    
    $sqlc = "select count(1) from ecshop.kuajing_bird_product kj 
    		INNER JOIN ecshop.taobao_shop_conf tb
			on kj.applicationkey = tb.application_key
			where 1 {$cond['simple_cond']} ";	
	$total = $db ->getOne($sqlc);
	
	$sql = "select * from ecshop.kuajing_bird_product kj
			INNER JOIN ecshop.taobao_shop_conf tb
			on kj.applicationkey = tb.application_key
			where 1 {$cond['simple_cond']} order by kj.last_updated_stamp desc LIMIT {$limit} OFFSET {$offset}";
	$simple_goods_list = $db->getAll($sql);
	$args['Pager'] = pager($total,$limit,$page);
	$args['order_list'] = $simple_goods_list;
	return $args;
}

function distribution_get_goods_list($top_cat_id = 0, $cat_id = 0, $keyword = '', $limit = 100)
{
    $conditions = '';
    if ($top_cat_id > 0) {
       $conditions = " AND g.top_cat_id = '{$top_cat_id}'";
    }
    if ($cat_id > 0) {
       $conditions .= " AND g.cat_id = '{$cat_id}'";
    }
    if (trim($keyword)) {
        $keyword = mysql_like_quote($keyword);
        $conditions .= " AND g.goods_name LIKE '%{$keyword}%'"; 
    }                
    
    $sql = "
        SELECT 
            g.goods_id, g.cat_id, gs.style_id, 
            CONCAT_WS(' ', g.goods_name, IF( gs.goods_color = '', s.color, gs.goods_color) ) as goods_name
        FROM 
            {$GLOBALS['ecs']->table('goods')} AS g 
            LEFT JOIN {$GLOBALS['ecs']->table('goods_style')} AS gs ON gs.goods_id = g.goods_id
            left join {$GLOBALS['ecs']->table('style')} as s on gs.style_id = s.style_id
        WHERE 
            ( g.is_on_sale = 1 AND g.is_delete = 0 ) " ." {$conditions}
        LIMIT {$limit}
    ";
    return $GLOBALS['db']->getAll($sql);
}
?>




























