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
    
//var_dump($_REQUEST);
/*
 * 处理ajax请求 by hzhang1 2015-07-24
 */
if ($req == 'ajax')
{
//	var_dump($act);
    $json = new JSON;
    switch ($act) 
    { 
        case 'get_select_shop':
            $nick = $_REQUEST['q'];
            $sql = "
			select nick from ecshop.taobao_shop_conf where  nick like '%{$nick}%' and party_id = '{$_SESSION['party_id']}'
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
//            $sql = "
//				select eg.goods_id, eg.cat_id, egs.style_id, 
//            concat_ws(' ', eg.goods_name, if( egs.goods_color = '', es.color, egs.goods_color) ) as goods_name
//			from ecshop.ecs_goods eg
//			left join ecshop.ecs_goods_style egs on eg.goods_id = egs.style_id
//			left join ecshop.ecs_style es on es.style_id = egs.style_id 
//			where ( eg.is_on_sale = 1 and eg.is_delete = 0 ) and eg.goods_name like  '%{$goods_name}%' limit 10
//			";
//			echo $sql;
//			$result=$GLOBALS['db']->getAll($sql);
//			
//            if ($result)
//                print $json->encode($result);
//            else
//                print $json->encode(array('error' => '商品不存在'));
            break;
        case 'insert_kjg_goods':
            $session_party_id = $_SESSION['party_id'];
		 	$session_aciton_user = $_SESSION['admin_name'];
		 	$application_key = $_REQUEST['application_key'];
		 	$product_id = $_REQUEST['product_id'];
		 	$goods_name = $_REQUEST['goods_name'];
		 	$outer_id = $_REQUEST['outer_id'];
		 	$unit = $_REQUEST['unit'];
		 	$price = $_REQUEST['price'];
		 	$rate = $_REQUEST['rate'];
		 	$vat_rate = $_REQUEST['vat_rate'];
		 	$declaration_facility = $_REQUEST['declaration_facility'];
		 	$package_flag = $_REQUEST['package_flag'];
		 	
			global $db;						
			try{						  
				$client = new SoapClient($erpsync_webservice_url.'SyncKuajinggouService?wsdl');
				$request=array("productId"=>$product_id);
				$response=$client->SearchKuajinggouProduct($request);
//				var_dump($response->return);
				if($response->return == $declaration_facility) {
					$sql = "insert into ecshop.haiguan_goods(party_id,application_key,product_id,goods_name,outer_id,unit,price,rate,vat_rate,declaration_facility,package_flag,action_user,created_stamp,last_updated_stamp) 
					values('{$session_party_id}','{$application_key}','{$product_id}','{$goods_name}','{$outer_id}','{$unit}','{$price}','{$rate}','{$vat_rate}','{$declaration_facility}','{$package_flag}','{$session_aciton_user}',now(),now())";
					if($db->query($sql)) {
						$return['flag'] = 'SUCCESS';
						$return['message'] = '新建成功';
					} else {
						$return['flag'] = 'FAIL';
						$return['message'] = '插入数据失败，请重试！';
					}				
				} else {
					$return['flag'] = 'FAIL';
					$return['message'] = '货号 =' . $product_id . '对应的申报系统仓库错误！	具体信息如下：' . $response->return;		
				}			
			}catch(Exception $e){
				$message = ''.$e->getMessage();
				$return['flag'] = 'FAIL';
				$return['message'] = 'exception ';
			}
			print $json->encode($return);
			exit;
			break;	
			
		case 'update_kjg_goods':
			$session_action_user = $_SESSION['admin_name'];
		 	$product_id = $_REQUEST['product_id'];
		 	$outer_id = $_REQUEST['outer_id'];
		 	$price = $_REQUEST['price'];
		 	$rate = $_REQUEST['rate'];
		 	$vat_rate = $_REQUEST['vat_rate'];
		 	global $db;
			$sql = "select 1 from  ecshop.haiguan_goods where product_id = '{$product_id}' and  outer_id='{$outer_id}' limit 1";
			if($db->getOne($sql)) {
				$sql = "update ecshop.haiguan_goods set price='{$price}',rate='{$rate}',vat_rate='{$vat_rate}',action_user = '{$session_action_user}', 
						last_updated_stamp=now() where product_id='{$product_id}' and outer_id='{$outer_id}'";
			}
			if($db->query($sql)) {
				$return['flag'] = 'SUCCESS';
				$return['message'] = '关闭成功';
			} else {
				$return['flag'] = 'FAIL';
				$return['message'] = '修改失败，请重试！';
			}
			print $json->encode($return);
			exit;	
			break;
		case 'delete_kjg_goods':
			$product_id = $_REQUEST['product_id'];
		 	$outer_id = $_REQUEST['outer_id'];
			global $db;
			$sql = "delete from ecshop.haiguan_goods where product_id='{$product_id}' and outer_id='{$outer_id}' limit 1";
			if($db->query($sql)) {
				$return['flag'] = 'SUCCESS';
				$return['message'] = '关闭成功';
			} else {
				$return['flag'] = 'FAIL';
				$return['message'] = '删除失败，请重试！';
			}
			print $json->encode($return);
			exit;	
			break;
    }
    exit;
}


$result = call_user_func('search_goods',$_GET);

$smarty->assign('declaration_facility_list',$_CFG['adminvars']['declaration_facility']);
$smarty->assign('goods_list',$result['order_list']);
$smarty->assign('message_error',$message_error);
$smarty->assign('message_success',$message_success);
$smarty->assign('Pager',$result['Pager']);

if ($_REQUEST['type'] == '商品导出CSV') {
	header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "商品导出CSV" ) . ".csv" );
	$out = $smarty->fetch ( 'oukooext/haiguan_goods_csv.htm' );
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
}

$smarty->display ( 'haiguan_goods_info.html' );

function getCondition() {
	global $ecs;
	$result = array();
	$condition = "";
	$barcode = trim ( $_REQUEST ['barcode'] );
	$goods_name = trim ( $_REQUEST ['goods_name'] );
	$product_id = $_REQUEST ['product_id'];
	$outer_id = $_REQUEST ['outer_id'];
	$start_time = $_REQUEST ['start_time'];
	$end_time = $_REQUEST ['end_time'];

	if ($barcode != '') {
		$condition .= " AND nick LIKE '%{$barcode}%' ";
	}
	if ($goods_name != '') {
		$condition .= " AND goods_name LIKE '%{$goods_name}%' ";
	}
	if ($product_id != '') {
		$condition .= " AND product_id LIKE '%{$product_id}%' ";
	}
	if ($outer_id != '') {
		$condition .= " AND outer_id LIKE '%{$outer_id}%' ";
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
	$limit = 6;
	$offset = $limit * ($page-1);
	$goods_list = array();
    
    $sqlc = "select count(1) from ecshop.haiguan_goods kjg 
			where party_id = '{$_SESSION['party_id']}' {$cond['simple_cond']} ";	
	$total = $db ->getOne($sqlc);
	
	$sql = "select * from ecshop.haiguan_goods kjg 
			where party_id = '{$_SESSION['party_id']}' {$cond['simple_cond']} order by kjg.last_updated_stamp desc LIMIT {$limit} OFFSET {$offset}";
	$simple_goods_list = $db->getAll($sql);
	$args['Pager'] = Pager($total,$limit,$page);
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
            ( g.is_on_sale = 1 AND g.is_delete = 0 ) and g.goods_party_id = '{$_SESSION['party_id']}' " ." {$conditions}
        LIMIT {$limit}
    ";
    return $GLOBALS['db']->getAll($sql);
}
?>




























