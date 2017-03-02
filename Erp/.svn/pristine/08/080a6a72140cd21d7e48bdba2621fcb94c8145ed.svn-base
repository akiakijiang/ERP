<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . "includes/cls_json.php");
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');

//验证权限
//admin_priv('miya_items_update');

$can_change_inventory_ratio = false;
if( check_admin_priv ( 'can_change_inventory_ratio' )) {
	$can_change_inventory_ratio = true;
	$smarty->assign('can_change_inventory_ratio',$can_change_inventory_ratio);
}

$tpl = 
array ('批量导入库存比例' => 
         array ('outer_id' => '商家编码', 
                'miya_inventory_ratio' => '蜜芽宝贝库存比例') );

$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):'';

if($act == 'change'){
	$json = new JSON();
	$application_key = isset($_REQUEST['application_key'])?trim($_REQUEST['application_key']):'';
	$is_items_update = isset($_REQUEST['is_items_update'])?trim($_REQUEST['is_items_update']):'';
	$sql = "select * from ecshop.taobao_shop_conf where  is_stock_update = '$is_items_update' and application_key = '$application_key'";
	$result = $db->getRow($sql);
	if(!$result){
		$sql = "update ecshop.taobao_shop_conf set is_stock_update = '$is_items_update' where application_key = '$application_key'";
		$result = $db->query($sql);
		$record_sql = "INSERT INTO ecshop.risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            			"VALUES ('{$_SESSION['admin_name']}', 'update', NOW(), 'miya_items_update.php', 'miya_items_update', '".mysql_real_escape_string($sql)."', '{$_POST['comment']}')";
        $db->query($record_sql);
		$result = "Success";
	}else{
		$result = "FAIL";
	}
	print $json->encode($result);
	exit();
}elseif ($act == "query_application_status"){
	$json = new JSON();
	$application_key = isset($_REQUEST['application_key'])?trim($_REQUEST['application_key']):'';
	$sql = "select is_stock_update from ecshop.taobao_fenxiao_shop_conf where application_key = '{$application_key}'";
	$result = $db->getOne($sql);
	if(!$result){
		$result = "NONE";
	}
	print $json->encode($result);
	exit();
} elseif($act == 'number_update'){   //更新预留等相关信息
	$json = new JSON();
	$sku_id = isset($_REQUEST['sku_id'])?trim($_REQUEST['sku_id']):'';
	$field = isset($_REQUEST['field'])?trim($_REQUEST['field']):'';
	$number = isset($_REQUEST['number'])?trim($_REQUEST['number']):'';
	$sql1 = "SELECT * FROM ecshop.sync_miya_items_sku WHERE sku_id = '{$sku_id}'";
	$result = "数据更新成功";
	if( $sku_id != '' && $db->getRow($sql1) ){
		$sql = "UPDATE ecshop.sync_miya_items_sku SET {$field} = '{$number}' WHERE sku_id = '{$sku_id}'";
		if(!$db->query($sql)){
			$result = "数据更新失败";
		}
	}else{
		$result = "在数据表内找不到该商品";
	}
	print $json->encode($result);
	exit();
} elseif($act == 'sync_update'){
	$is_sync = isset($_REQUEST['is_sync'])?trim($_REQUEST['is_sync']):false;
	$sku_id = isset($_REQUEST['sku_id'])?trim($_REQUEST['sku_id']):'';
	
	$sql1 = "SELECT * FROM ecshop.sync_miya_items_sku WHERE sku_id = '{$sku_id}'";
	$result = 'fail';
	if($sku_id != '' && $db->getRow($sql1)){
		$sql = "UPDATE ecshop.sync_miya_items_sku SET is_sync = $is_sync WHERE sku_id = '{$sku_id}'";
		$db->query($sql);
	}
} elseif($act == 'shop_update') {	//更新库存比例
	$json = new JSON();
	$application_key = isset($_REQUEST['application_key'])?trim($_REQUEST['application_key']):'';
	$field = isset($_REQUEST['field'])?trim($_REQUEST['field']):'';
	if($field=='shop_inventory_ratio') $field='inventory_ratio';
	$number = isset($_REQUEST['number'])?trim($_REQUEST['number']):'';
	$sql1 = "SELECT * FROM ecshop.taobao_shop_conf WHERE application_key = '{$application_key}'";
	$result = "数据更新成功";
	if( $application_key != '' && $db->getRow($sql1) ){
		$sql = "update ecshop.taobao_shop_conf set {$field}={$number},group_id='miya' where application_key='{$application_key}'";
		if(!$db->query($sql)){
			$result = "数据更新失败";
		}
	} else {
		$result = "找不到该店铺";
	}
	print $json->encode($result);
	exit();
} elseif($act =='download_ratio_template') {
	export_excel_template($tpl);
	exit();
} elseif($act == 'upload_ratio') {
	$message = "";
	$party_id = $_SESSION['party_id'];
	$result = before_upload_exam($tpl);
	if($result['message']){
			$message = $result['message'];
		}
	$rowset = $result['result']['批量导入库存比例'];
	if($rowset){ 
		foreach($rowset as $row) {
			$outer_id = $row['outer_id'];
			$miya_inventory_ratio = $row['miya_inventory_ratio'];
			
			if(!(is_numeric($miya_inventory_ratio) && $miya_inventory_ratio>=0 && $miya_inventory_ratio<=1)){
					$message .= "第".($key+1)."行的{$outer_id}蜜芽宝贝库存比例不在0到1之间 <br/>";
					continue;
			}
			
			$sql = "update ecshop.sync_miya_items_sku set inventory_ratio={$miya_inventory_ratio} where item_barcode='{$outer_id}' AND party_id = {$party_id}";
			
			$result = $db->query($sql);
			$sql = "update ecshop.sync_taobao_items set inventory_ratio={$taobao_inventory_ratio} where outer_id='{$outer_id}' AND party_id = {$party_id}";
			$result = $db->query($sql);
		}
		
	}
	if($message){
		$smarty ->assign("message",$message);
	}

}


$smarty->assign('status_list',array(
	'ALL' => '不选',
	'true'  =>  '正确',
	'false' =>  '错误',
));
$smarty->assign('approve_status_list',array(
	'ALL' => '不选',
	'onsale'  =>  '上架',
	'instock' =>  '下架',
));
$smarty->assign('is_sync_status_list',array(
	'ALL' => '不选',
	'0' => '不同步',	
	'1' => '同步'
));

$application_list = get_miya_shop_nicks();
$smarty->assign('application_list', $application_list);

$shop_inventory_ratio = get_shop_inventory_ratio();
$smarty->assign('shop_inventory_ratio',$shop_inventory_ratio);

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
	$sql = "select
				sc.nick,js.item_barcode,js.sku_id,js.goods_id,js.style_id,ji.name,
				if(js.style_id=0 and js.goods_id!=0,js.goods_id,concat_ws('_',js.goods_id,js.style_id)) as outer_id,
				IF(dg.code IS NOT NULL,dg.name,IF(gs.style_id IS NULL,g.goods_name,CONCAT_WS(',',g.goods_name,s.color))) as gName, 
				IF(dg.code IS NOT NULL || (((js.style_id=0 AND gs.style_id IS NULL) || js.style_id = gs.style_id) and g.goods_name IS NOT NULL),'正确','错误') as outer_status, 
				js.inventory_ratio,
				js.stock_quantity,js.reserve_number,js.reserve_ratio,js.inventory_warning,IF(js.is_sync = 0,'不同步','同步') as is_sync, js.last_updated_stamp
			from 
				ecshop.sync_miya_items_sku  js
				left join ecshop.sync_miya_items ji on js.sku_id=ji.sku_id
				left join ecshop.ecs_goods g on js.goods_id = g.goods_id
				left join ecshop.ecs_goods_style gs on g.goods_id=gs.goods_id and gs.style_id=js.style_id and gs.is_delete=0
				left join ecshop.ecs_style s on gs.style_id=s.style_id
				LEFT JOIN ecshop.distribution_group_goods dg ON js.item_barcode = dg.code
				LEFT JOIN ecshop.taobao_shop_conf sc on js.application_key = sc.application_key
			where
				1 ".$condition.
		   " group by js.sku_id" . $outer_Conditon;
		
	$sql1 = $sql.$limit.$offset;
	//Qlog::log($sql1);
	//var_dump($sql1);
	$miya_items_list = $db->getAll($sql1);
	//print_r($miya_items_list);
	$sql2 = "SELECT COUNT(*) FROM(".$sql.") as countItems";
			
	$count = $db->getOne($sql2);
	$pager = setPager($count, $size, $page,"miya_items_update.php?request=search&goods_name=".trim($_REQUEST['goods_name'])."&outer_id=".$_REQUEST['outer_id'].
		"&application_nicks=".trim($_REQUEST['application_nicks'])."&outer_status=".$_REQUEST['outer_status']."&approve_status=".$_REQUEST['approve_status']);
	$application_nicks = trim($_REQUEST['application_nicks']);
	$sql = "select is_stock_update from ecshop.taobao_shop_conf where application_key = '$application_nicks'";
	$change_stock_update = $db->getOne($sql);
	if($change_stock_update == "Y"){
		$change_stock_update = "停止同步";
	} else if($change_stock_update == "N"){
		$change_stock_update = "开启同步";
	}

	
	
	$smarty->assign('goods_name',trim($_REQUEST['goods_name']));
	$smarty->assign('outer_id',trim($_REQUEST['outer_id']));
	$smarty->assign('application_nicks',trim($_REQUEST['application_nicks']));
	$smarty->assign('approve_status',trim($_REQUEST['approve_status']));
	$smarty->assign('outer_status',trim($_REQUEST['outer_status']));
	$smarty->assign('pager', $pager);
	$smarty->assign('change_stock_update', $change_stock_update);
	$smarty->assign('miya_items_list',$miya_items_list);
}

$smarty->display("jd/miya_items_update.htm");

/**
 * 取得淘宝店铺信息
 * 
 */
function get_miya_shop_nicks() {
    $application_list = get_miya_shop_list(); //取得taobao店铺信息
    $application_nicks = array();
    foreach ($application_list as $application) {
        $application_nicks[$application['application_key']] = $application['nick'];
    }
    return $application_nicks;
}

function get_miya_shop_list() {
    global $db;
    $sql = "SELECT nick,application_key FROM ecshop.taobao_shop_conf WHERE shop_type = 'miya' and party_id = '".$_SESSION['party_id']."'";
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
	//Qlog::log($_REQUEST['goods_name'].'---'.$_REQUEST['outer_id'].'---'.$_REQUEST['application_nicks'].'---'.$_REQUEST['approve_status']);
	if (isset($_REQUEST['goods_name']) && trim($_REQUEST['goods_name']) != '') {
        $condition .= " AND ji.name like '%".mysql_escape_string(trim($_REQUEST['goods_name']))."%'";
    }
    if ( isset($_REQUEST['outer_id']) && trim($_REQUEST['outer_id']) != '') {
        $outer_id = trim($_REQUEST['outer_id']);
        //echo substr($outer_id,0,2);
        $condition .= " AND js.item_barcode='{$outer_id}' ";
        if( substr($outer_id,0,2)!="TC"){
	        $add_condition=get_code($outer_id);
	        if($add_condition){
	        	$condition=$condition."or js.item_barcode in ('".$add_condition."') ";
	        }
	        //print_r($condition);
        }
    }
	if ( isset($_REQUEST['application_nicks']) && trim($_REQUEST['application_nicks']) != 'ALL' ) {
		$condition .= " AND js.application_key = '".mysql_escape_string(trim($_REQUEST['application_nicks']))."'";
	}else {
		$condition .= " AND js.party_id = '".$_SESSION['party_id']."'";
	}
	if ( isset($_REQUEST['approve_status']) && trim($_REQUEST['approve_status']) != 'ALL' ) {
		$condition .= " ";
	}
	if ( isset($_REQUEST['is_sync_status']) && trim($_REQUEST['is_sync_status']) != 'ALL' ) {
		$condition .= " AND js.is_sync = '".mysql_escape_string(trim($_REQUEST['is_sync_status']))."'";
	}
	return $condition;
}

function get_code($outer_id){
	global $db;	
	$condition_to_get_groupid="";
	$sql="SELECT goods_id,style_id FROM ecshop.sync_miya_items_sku WHERE item_barcode='{$outer_id}'";
	$re= $db->getAll($sql);
	//var_dump($re);
	foreach($re as $key=> $value){
      if(is_array($value)){
          $re = $value;}
	}
	$condition_to_get_groupid="goods_id='".$re['goods_id']."' and style_id='".$re['style_id']."'";
	$sql2="SELECT distinct group_id FROM ecshop.distribution_group_goods_item WHERE $condition_to_get_groupid";
	$re2= $db->getCol($sql2);
	//var_dump($re2);	
	$condition_to_get_code = implode("','", $re2);
	//print_r($condition_to_get_code);
	$sql3="SELECT code FROM ecshop.distribution_group_goods WHERE group_id in ('{$condition_to_get_code}') ";
	$re3= $db->getCol($sql3);
	//var_dump($re3);
	$condition_to_get_info = implode("','", $re3);
	//print_r($condition_to_get_info);
	return $condition_to_get_info;

}

function get_shop_inventory_ratio(){
	global $db;
	$application_key = mysql_escape_string(trim($_REQUEST['application_nicks']));
	if($application_key=='ALL' || !isset($_REQUEST['application_nicks'])) return -1;
	$sql = "select inventory_ratio from ecshop.taobao_shop_conf where application_key = '{$application_key}' limit 1";
	$shop_inventory_ratio = $db->getOne($sql);
	return $shop_inventory_ratio;
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