<?php 

/**
 * 淘宝外包发货管理
 * 
 */
 
define('IN_ECS', true);
require('../includes/init.php');
admin_priv('taobao_out_ship_goods');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');


$client = new SoapClient(SYNCJUSHITA_WEBSERVICE_URL . "SyncTaobaoService?wsdl");

//var_dump(get_group_list(0,3)); die();

$request = // 请求 
    isset($_REQUEST['request']) ? trim($_REQUEST['request']) : null ;
$act =     // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('add', 'update','check')) 
    ? $_REQUEST['act'] 
    : null ;
$info =    // 返回的信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ;
$page =    // 分页
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
/*
 * 处理ajax请求
 */
if ($request == 'ajax')
{
	
    $json = new JSON;
    switch ($act) 
    {
        // 验证是否为套餐
        case 'check':
        	$outer_id = $_POST['outer_id'];
			if(strcasecmp(substr($outer_id,0,3),'TC-') == '0'){
				$sql = "select count(*) from ecshop.distribution_group_goods where code = '".$outer_id."' and party_id = '".$_SESSION['party_id']."'";
			}else{
				
				$array = explode("_",$outer_id);
				if(count($array) == 1 ){
//					Qlog::log(current($array));
					$sql = "select count(*) from ecshop.ecs_goods where goods_id = '".current($array)."' and goods_party_id = '".$_SESSION['party_id']."'";
					
				}else if(next($array)=='0'){
					$sql = "select count(*) from ecshop.ecs_goods where goods_id = '".prev($array)."' and goods_party_id = '".$_SESSION['party_id']."'";
				}else{
					$sql = "select count(*) from ecshop.ecs_goods_style where goods_id = '".prev($array)."' and style_id = '".next($array)."' and is_delete=0";
				}
			}	
            $count = $db->getOne($sql);
//            Qlog::log($count);
            if ($count == 0) {
            	$result = false;
            }else {
            	$result = true;
            }
        break;
    }
    print $json->encode($result);
    exit;
}

/*
 * 处理post请求
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && $act) {
	$client = new SoapClient(SYNCJUSHITA_WEBSERVICE_URL . "SyncTaobaoService?wsdl");
    switch ($act) {  
     	/*添加商品*/
        case 'add' :

        $create_user = $_SESSION['admin_name'];
        $update_user = $_SESSION['admin_name'];
        $item = $_REQUEST['item'];
        $request = array(
                    'applicationKey' => $item[application_key],
                    'partyId'=>$_SESSION[party_id],
                    'startTime'=>$item[start_time],
        			'endTime' => $item[end_time],
        			'outShipGoodsId' => '-1',
        			'outerId' => $item[outer_id],
        			'outerNumber' => $item[out_number],
        			'outShipId' => $item[out_ship_id],
        			'adminName' => $create_user,
                    'username'=>JSTUsername,
                    'password'=>md5(JSTPassword),
        );
        $client->insertOrUpdateEcsOutShipGoods($request);
         
        break;
        
        
        
        /* 编辑商品 */
        case 'update' :
        
        $item = $_REQUEST['item'];
        $update_user = $_SESSION['admin_name'];        
        $request = array(
                    'applicationKey' => $item[application_key],
                    'partyId'=>$_SESSION[party_id],
                    'startTime'=>$item[start_time],
        			'endTime' => $item[end_time],
        			'outShipGoodsId' => $item[out_ship_goods_id],
        			'outerId' => $item[outer_id],
        			'outerNumber' => $item[out_number],
        			'outShipId' => $item[out_ship_id],
        			'adminName' => $update_user,
                    'username'=>JSTUsername,
                    'password'=>md5(JSTPassword),
        );
        $client->insertOrUpdateEcsOutShipGoods($request);
         
        break;
    }  
}

// 编辑模式
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	$group =  $db->getRow("SELECT * FROM ecshop.ecs_out_ship_goods WHERE out_ship_goods_id = ".$_GET['id']);
  	$smarty->assign('update', $group); 	 
}

$application_list = get_taobao_shop_nicks();
$smarty->assign('application_list', $application_list);

$smarty->assign('application_nicks',trim($_REQUEST['application_nicks']));
/**
 * 取得淘宝店铺信息
 * 
 */
function get_taobao_shop_nicks() {
    $application_list = get_taobao_shop_list(); //取得taobao店铺信息
    $application_nicks = array();
    foreach ($application_list as $application) {
        $application_nicks[$application['application_key']] = $application['nick'];
    }
    return $application_nicks;
}

function get_taobao_shop_list() {
    global $db;
    $sql = "SELECT nick,application_key FROM ecshop.taobao_shop_conf " .
    		" WHERE shop_type = 'taobao' and party_id = '".$_SESSION['party_id']."'";
    $application_list = $db->getAll($sql);
    return $application_list;
}

function get_group_list($offset=0,$limit=0) {
	global $client;
	$request = array('partyId'=>$_SESSION[party_id],'username'=>JSTUsername,'password'=>md5(JSTPassword));
	$result = $client->selectEcsOutShipGoodByPartyId($request)->return;
	if($result == null) {
		return array();
	}
	
	$shop_list = get_taobao_shop_nicks();
	$shippingTypes = getShippingTypes();
	
	$ret = array();
	
	if(!is_array($result)) {
		$result = (array)$result;
		$arr = $result;
		$arr["nick"] = $shop_list[$arr["application_key"]];
		$arr["shipping_name"] = $shippingTypes[$arr["out_ship_id"]]["shipping_name"];
		$ret[] = $arr;
		return $ret;
	}
		
	for($i=0; $i<$limit && $i<count($result); ++$i){
		$arr = (array)$result[$i+$offset];
		$arr["nick"] = $shop_list[$arr["application_key"]];
		$arr["shipping_name"] = $shippingTypes[$arr["out_ship_id"]]["shipping_name"];
		$ret[] = $arr;
	}
	
	return $ret;
	
}

function get_group_total() {
	global $client;
	$request = array('partyId'=>$_SESSION[party_id],'username'=>JSTUsername,'password'=>md5(JSTPassword));
	$result = $client->selectEcsOutShipGoodByPartyId($request)->return;
	if($result == null) {
		return 0;
	}
	return count($result);
}

//添加选择快递方式的选项。   by qxu 2013-6-25
$get_shippings = getShippingTypes();
//所有可用的快递方式
$smarty->assign('get_shippings', $get_shippings);

//总记录数
$total = get_group_total();
// 分页 
$page_size = 10;  // 每页数量
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size;
//商品列表
$group_list = get_group_list($offset,$limit);

$pagination = new Pagination($total, $page_size, $page, 'page', $url = 'taobao_out_ship_goods.php', null);
$smarty->assign('total', $total);  // 总数
$smarty->assign('group_list', $group_list);  //商品列表
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
$smarty->display("taobao/taobao_out_ship_goods.htm");

?>