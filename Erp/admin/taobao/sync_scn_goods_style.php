<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . "includes/cls_json.php");
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

//验证权限
admin_priv('sync_scn_goods_style');

$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):'';
if($act == 'change'){
	$json = new JSON();
	$application_key = isset($_REQUEST['application_key'])?trim($_REQUEST['application_key']):'';
	$is_items_update = isset($_REQUEST['is_items_update'])?trim($_REQUEST['is_items_update']):'';
	$sql = "select * from ecshop.taobao_shop_conf where  is_stock_update = '$is_items_update' and application_key = '$application_key'";
	// Qlog::log($sql);
	//$result = $db->getRow($sql);
	$result = "fail";
	if(!$db->getRow($sql)){
		$sql = "update ecshop.taobao_shop_conf set is_stock_update = '$is_items_update' where application_key = '$application_key'";
		// Qlog::log($sql);
		$record_sql = "INSERT INTO ecshop.risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            			"VALUES ('{$_SESSION['admin_name']}', 'update', NOW(), 'sync_scn_goods_style.php', 'sync_scn_goods_style', '".mysql_real_escape_string($sql)."', '{$_POST['comment']}')";
		if($result = $db->query($sql)){
			$db->query($record_sql);
			$result = "success";
		}
	}
	print $json->encode($result);
	exit();
}elseif ($act == "query_application_status"){
	$json = new JSON();
	$application_key = isset($_REQUEST['application_key'])?trim($_REQUEST['application_key']):'';
	$sql = "select is_stock_update from ecshop.taobao_shop_conf where application_key = '$application_key'";
	$result = $db->getOne($sql);
	if(!$result){
		$result = "NONE";
	}
	print $json->encode($result);
	exit();
}elseif($act == 'number_update'){
	$json = new JSON();
	$outer_id = isset($_REQUEST['outer_id'])?trim($_REQUEST['outer_id']):'';
	$field = isset($_REQUEST['field'])?trim($_REQUEST['field']):'';
	$number = isset($_REQUEST['number'])?trim($_REQUEST['number']):'';
	$sql = "SELECT * FROM ecshop.sync_scn_goods_style WHERE VendorSkuId = '{$outer_id}'";
	//Qlog::log($sql);
	$result = "数据更新成功";
	if($db->getRow($sql)){
		$sql = "UPDATE ecshop.sync_scn_goods_style SET {$field} = $number WHERE VendorSkuId = '{$outer_id}'";
		//Qlog::log($sql);
		if(!$db->query($sql)){
			$result = "数据更新失败";
		}
	}else{
		$result = "在数据表内找不到该商品";
	}
	print $json->encode($result);
	exit();
}elseif($act == 'sync_update'){
	//$json = new JSON();
	$is_sync = isset($_REQUEST['is_sync'])?trim($_REQUEST['is_sync']):false;
	$item_outer_id = isset($_REQUEST['item_outer_id'])?trim($_REQUEST['item_outer_id']):'';
	
	if($item_outer_id != ''){
		$sql1 = "SELECT * FROM ecshop.sync_scn_goods_style WHERE VendorSkuId = '{$item_outer_id}'";
		//$result = 'fail';
		if($db->getRow($sql1)){
			$sql = "UPDATE ecshop.sync_scn_goods_style SET is_sync = $is_sync WHERE VendorSkuId = '{$item_outer_id}'";
			// Qlog::log($sql);
			$db->query($sql);
		}
	}
	/*print $json->encode($result);
	exit();*/
}

$smarty->assign('status_list',array(
	'ALL' => '不选',
	'true'  =>  '正确',
	'false' =>  '错误',
));
$smarty->assign('approve_status_list',array(
	'ALL' => '不选',
	'up'  =>  '上架',
	'down' =>  '下架',
));

$application_list = get_taobao_shop_nicks();
$smarty->assign('application_list', $application_list);

$size = 20;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;
if ($csv == null) {
    $limit = " LIMIT $size";
    $offset = " OFFSET $start";
}

//Qlog::log('request='.$_REQUEST['request']);
if($_REQUEST['request'] == 'search'){
	$condition = get_condition();
	$outer_Conditon = "";
	//Qlog::log('outer_status='.$_REQUEST['outer_status']);
	if ( isset($_REQUEST['outer_status']) && $_REQUEST['outer_status'] != 'ALL' ) {
		if ( $_REQUEST['outer_status'] == 'true' ) {
			$outer_Conditon .= " HAVING outer_status = '正确'";
		}else{
			$outer_Conditon .= " HAVING outer_status = '错误'";
		}
	}
	$sql = "SELECT '名鞋库NB' AS nick, ti.title, ti.outer_id, ti.goods_id, ti.style_id, gs.barcode, 
				IF(dg.code IS NOT NULL,dg.name,IF(gs.style_id IS NULL,g.goods_name,CONCAT_WS(',',g.goods_name,s.color))) as gName, 
				IF(dg.code IS NOT NULL || (((ti.style_id=0 AND gs.style_id IS NULL) || ti.style_id = gs.style_id) and g.goods_name IS NOT NULL),'正确','错误') as outer_status, 
				ti.quantity, ti.reserve_number, ti.reserve_ratio, ti.warn_number, IF(ti.is_sync = 0,'不同步','同步') as is_sync, ti.approve_status, ti.last_update_stamp 
			FROM(SELECT CONCAT_WS(',',ssg.ItemName,ssgs.SizeName) AS title,
							ssgs.goods_id, 
							ssgs.style_id, 
							ssgs.VendorSkuId AS outer_id,
							ssgs.qoh as quantity,
							ssgs.reserve_number,
							ssgs.reserve_ratio,
							ssgs.warn_number,
							ssgs.is_sync,
							ssgs.approve_status,
							ssgs.last_update_stamp
						FROM ecshop.sync_scn_goods ssg LEFT JOIN ecshop.sync_scn_goods_style ssgs ON ssg.goods_id = ssgs.goods_id
						WHERE 1 ".$condition.") as ti 
			LEFT JOIN ecshop.ecs_goods_style gs ON ti.goods_id = gs.goods_id AND (ti.style_id=0 OR ti.style_id=gs.style_id)  and gs.is_delete=0
			LEFT JOIN ecshop.ecs_goods g ON ti.goods_id = g.goods_id 
			LEFT JOIN ecshop.ecs_style s ON gs.style_id = s.style_id 
			LEFT JOIN ecshop.distribution_group_goods dg ON ti.outer_id = dg.code 
			GROUP BY ti.goods_id,ti.style_id".$outer_Conditon;
	
	$sql1 = $sql.$limit.$offset;
	//Qlog::log($sql1);
	//var_dump($sql1);
	$taobao_items_list = $slave_db->getAll($sql1);
	$sql2 = "SELECT COUNT(*) FROM(".$sql.") as countItems";
			
	$count = $slave_db->getOne($sql2);
	$pager = setPager($count, $size, $page,"sync_scn_goods_style.php?request=search&goods_name=".trim($_REQUEST['goods_name'])."&outer_id=".$_REQUEST['outer_id'].
		"&application_nicks=".trim($_REQUEST['application_nicks'])."&outer_status=".$_REQUEST['outer_status']."&approve_status=".$_REQUEST['approve_status']);
		
	$smarty->assign('goods_name',trim($_REQUEST['goods_name']));
	$smarty->assign('outer_id',trim($_REQUEST['outer_id']));
	$smarty->assign('application_nicks',trim($_REQUEST['application_nicks']));
	$smarty->assign('approve_status',trim($_REQUEST['approve_status']));
	$smarty->assign('outer_status',trim($_REQUEST['outer_status']));
	
	$smarty->assign('pager', $pager);
	$smarty->assign('taobao_items_list',$taobao_items_list);
}

$smarty->display("taobao/sync_scn_goods_style.htm");

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
    $sql = "SELECT nick,application_key FROM ecshop.taobao_shop_conf WHERE shop_type = 'scn' AND party_id = 65611";
    $application_list = $db->getAll($sql);
    return $application_list;
}

/**fdc
 * 获得条件
 *
 */
function get_condition(){
	global $db;
	extract($_REQUEST);
	$condition = "";
	if (isset($_REQUEST['goods_name']) && trim($_REQUEST['goods_name']) != '') {
        $condition .= "AND ssg.ItemName like '%".mysql_escape_string(trim($_REQUEST['goods_name']))."%'";
    }
    
    if ( isset($_REQUEST['outer_id']) && trim($_REQUEST['outer_id']) != '') {
        $condition .= " AND ssgs.VendorSkuId like '%".trim($_REQUEST['outer_id'])."%'";
    }
    
	$condition .= " AND ssgs.party_id = '65611'";
	
	if ( isset($_REQUEST['approve_status']) && trim($_REQUEST['approve_status']) != 'ALL' ) {
		$condition .= " AND ssgs.approve_status = '".mysql_escape_string(trim($_REQUEST['approve_status']))."'";
	}
	return $condition;
}

/*
 * 分页
 * 
 */
function setPager($total, $offset = 9, $page = null, $url = null, $back = 3, $label = 'page'){
    // lianxiwoo@hotmail | gmail | sohu | 163.com
    $page = null == $page ? 1 : $page;
    //$page = $page > 1 ? $page : (int) @$_GET[$label];
    $page = $page < 1 ? 1 : $page;

    $pages = ceil($total/$offset);
    $pages = $pages > 0 ? $pages : 1;
    $page = $page > $pages ? $pages : $page;
    
    $url = null == $url ? $_SERVER['REQUEST_URI'] : $url;
    $url = preg_replace("/([?|&])$label\=[0-9]*/", "\\1", $url);
    $url = str_replace(array("&&", "?&"), array('&', '?'), $url);

    $url .= strstr($url, '?')
    ? (substr($url, -1) == '?' ? '' : (substr($url, -1) == '&' ? '' : '&'))
    : '?';

    $ppp = '';
    #$ppp .= '<a href="'.$url.$label.'=1" target="" title="First Page 1" class="Pager">&#171</a> ';
    $ppp .= '<a href="'.$url.$label.'=1" target="" title="First Page 1" class="Pager">[首页]</a> ';
    if ($pages <= ($back*2 + 1))
    {
        for ($i=1; $i<=$pages; $i++)
        {
            //            $ppp .= '<a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'"'.($page==$i ? ' style="font-weight: bold; color: #FF00FF;"' : '').' class="Pager">['.$i.']</a>';
            if ($page == $i) {
                $ppp .= $i;
            } else {
                $ppp .= '<a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'" class="Pager">['.$i.']</a>';
            }
        }
    }else{
        $b = $back + 2;
        if ($page <= $b)
        {
            $fromfrom = 1;
            $toto = $back * 2 + 1;
        }elseif ($page > $pages - $b){
            $c = $back*2;
            $fromfrom = $pages - $c;
            $toto = $pages;
        }else{
            $fromfrom = $page - $back;
            $toto = $page + $back;
        }
        for ($i=$fromfrom; $i<=$toto; $i++)
        {
            //            $ppp .= '<a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'"'.($page==$i ? ' style="font-weight: bold; color: #FF00FF;"' : '').' class="Pager">['.$i.']</a>';
            if ($page == $i) {
                $ppp .= $i;
            } else {
                $ppp .= ' <a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'" class="Pager">['.$i.']</a> ';
            }
        }
    }
    #$ppp .= ' <a href="'.$url.$label.'='.$pages.'" target="" title="Last Page '.$pages.'" class="Pager">&#187</a>';
    $ppp .= ' <a href="'.$url.$label.'='.$pages.'" target="" title="Last Page '.$pages.'" class="Pager">[尾页]</a>';
    $ppp .= ' <input type="text" class="pagerInput" name="page" value="'.$page.'" size="5" onFocus="this.select();" onBlur="if(this.value != '.$page.' && this.value >= 1 && this.value <= '.$pages.'){location.href=\''.$url.$label.'=\' + this.value;}else{this.value = '.$page.';}" title=" 跳转 ">';
    $ppp .= ' ( 页数/记录数 :  '.$pages.'/'.$total.')';
    return $ppp;
}
?>