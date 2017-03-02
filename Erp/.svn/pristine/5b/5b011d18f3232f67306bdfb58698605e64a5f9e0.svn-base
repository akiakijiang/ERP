<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . "includes/cls_json.php");
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
//验证权限
admin_priv('taobao_items_update');
$tpl =  array ('批量导入库存比例' =>  array (
	'outer_id' => '商家编码', 
	'reserve_number' => '预留库存数量', 
	'reserve_ratio' => '预留库存比例' ) );
$can_change_inventory_ratio = false;
if( check_admin_priv ( 'can_change_inventory_ratio' )) {
	$can_change_inventory_ratio = true;
	$smarty->assign('can_change_inventory_ratio',$can_change_inventory_ratio);
}

$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):'';
switch($act){


	case 'change' :
	admin_priv ( 'can_change_inventory_ratio' );
	$json = new JSON();
	$application_key = isset($_REQUEST['application_key'])?trim($_REQUEST['application_key']):'';
	$is_items_update = isset($_REQUEST['is_items_update'])?trim($_REQUEST['is_items_update']):'';
	$sql = "select * from ecshop.taobao_shop_conf where  is_stock_update = '$is_items_update' and application_key = '$application_key'";
	$result = $db->getRow($sql);
	if(!$result){
		$sql = "update ecshop.taobao_shop_conf set is_stock_update = '$is_items_update' where application_key = '$application_key'";
		$result = $db->query($sql);
		$record_sql = "INSERT INTO ecshop.risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
		"VALUES ('{$_SESSION['admin_name']}', 'is_stock_update', NOW(), 'taobao_items_update.php', 'taobao_items_update', '".mysql_real_escape_string($sql)."', '{$_POST['comment']}')";
		$db->query($record_sql);
		$result = "Success";
	}else{
		$result = "FAIL";
	}
	print $json->encode($result);
	exit();


	case "query_application_status" :
	$json = new JSON();
	$application_key = isset($_REQUEST['application_key'])?trim($_REQUEST['application_key']):'';
	$sql = "select is_stock_update from ecshop.taobao_fenxiao_shop_conf where application_key = '$application_key'";
	$result = $db->getOne($sql);
	if(!$result){
		$result = "NONE";
	}
	print $json->encode($result);
	exit();


	case 'number_update' :
	$json = new JSON();
	$num_iid = isset($_REQUEST['num_iid'])?trim($_REQUEST['num_iid']):'';
	$sku_id = isset($_REQUEST['sku_id'])?trim($_REQUEST['sku_id']):'';
	$field = isset($_REQUEST['field'])?trim($_REQUEST['field']):'';
	$number = isset($_REQUEST['number'])?trim($_REQUEST['number']):'';
	$sql1 = "SELECT * FROM ecshop.sync_taobao_items_sku WHERE num_iid = '{$num_iid}' and sku_id = '{$sku_id}'";
	$sql2 = "SELECT * FROM ecshop.sync_taobao_items WHERE num_iid = '{$num_iid}'";
	$result = "数据更新成功";
	if( $sku_id != '' && $db->getRow($sql1) ){
		$sql = "UPDATE ecshop.sync_taobao_items_sku SET {$field} = '{$number}' WHERE num_iid = '{$num_iid}' and sku_id = '{$sku_id}'";
			//		Qlog::log($sql);
		if(!$db->query($sql)){
			$result = "数据更新失败";
		}
	}elseif( $sku_id == '' && $db->getRow($sql2) ){
		$sql = "UPDATE ecshop.sync_taobao_items SET {$field} = '{$number}' WHERE num_iid = '{$num_iid}'";
			//		Qlog::log($sql);
		if(!$db->query($sql)){
			$result = "数据更新失败";
		}
	}else{
		$result = "在数据表内找不到该商品";
	}
	print $json->encode($result);
	exit();


	case 'sync_update':
	$is_sync = isset($_REQUEST['is_sync'])?trim($_REQUEST['is_sync']):false;
	$item_num_iid = isset($_REQUEST['item_num_iid'])?trim($_REQUEST['item_num_iid']):'';
	$sku_id = isset($_REQUEST['sku_id'])?trim($_REQUEST['sku_id']):'';

	$sql1 = "SELECT * FROM ecshop.sync_taobao_items_sku WHERE num_iid = '{$item_num_iid}' and sku_id = '{$sku_id}'";
	$sql2 = "SELECT * FROM ecshop.sync_taobao_items WHERE num_iid = '{$item_num_iid}'";
	$result = 'fail';
	if($sku_id != '' && $db->getRow($sql1)){
		$sql = "UPDATE ecshop.sync_taobao_items_sku SET is_sync = $is_sync WHERE num_iid = '{$item_num_iid}'and sku_id = '{$sku_id}'";
			//		Qlog::log($sql);
		$db->query($sql);
	}elseif($sku_id == '' && $db->getRow($sql2)){
		$sql = "UPDATE ecshop.sync_taobao_items SET is_sync = $is_sync WHERE num_iid = '{$item_num_iid}'";
			//		Qlog::log($sql);
		$db->query($sql);
	}
	break;


	case 'shop_update':
	$json = new JSON();
	$application_key = isset($_REQUEST['application_key'])?trim($_REQUEST['application_key']):'';
	$field = isset($_REQUEST['field'])?trim($_REQUEST['field']):'';
	if($field=='shop_inventory_ratio') $field='inventory_ratio';
	$number = isset($_REQUEST['number'])?trim($_REQUEST['number']):'';
	$sql1 = "SELECT * FROM ecshop.taobao_shop_conf WHERE application_key = '{$application_key}'";
	$result = "数据更新成功";
	if( $application_key != '' && $db->getRow($sql1) ){
		$sql = "update ecshop.taobao_shop_conf set {$field}={$number} where application_key='{$application_key}'";
		if(!$db->query($sql)){
			$result = "数据更新失败";
		}
	} else {
		$result = "找不到该店铺";
	}
	print $json->encode($result);
	exit();

	case 'download_ratio_template':
	export_excel_template($tpl);
	break;

	case 'upload_ratio':
	$party_id = $_SESSION['party_id'];
	$result = before_upload_exam($tpl);
	$message = "";
	if($result['message']){
		$message = $result['message'];
	}
	$rowset = $result['result']['批量导入库存比例'];
	if($rowset){
		foreach($rowset as $key => $row) {
			$outer_id = $row['outer_id'];
			$reserve_number = $row['reserve_number'];
			$reserve_ratio = $row['reserve_ratio'];

			$sql = "select 1 from ecshop.sync_taobao_items i left join
			ecshop.sync_taobao_items_sku s on i.num_iid = s.num_iid 
			where i.party_id = ".$party_id." and  (i.outer_id ='{$outer_id}' or s.outer_id = '{$outer_id}')";
			if(!$db->getRow($sql)){
				$message .= "第".($key+1)."行的{$outer_id}商品不在直销商品中	<br/>";
				continue;
			}
			if(!(is_numeric($reserve_number) && $reserve_number>=0)){
				$message .= "第".($key+1)."行的{$outer_id}商品预留数量必须大于0 <br/>";
				continue;
			}
			if(!(is_numeric($reserve_ratio) && $reserve_ratio >= 0 && $reserve_ratio<=1)){
				$message .= "第".($key+1)."行的{$outer_id}商品预留比例不在0到1之间 <br/>";
				continue;
			}


			$sql1 = "update ecshop.sync_taobao_items set reserve_number={$reserve_number},reserve_ratio = {$reserve_ratio} where outer_id='{$outer_id}' and party_id = {$party_id}";
			$sql2 = "update ecshop.sync_taobao_items_sku set reserve_number={$reserve_number},reserve_ratio = {$reserve_ratio} where outer_id='{$outer_id}' and party_id = {$party_id}";
			$db->query($sql1);
			$db->query($sql2);
		}
	}
	if($message){
		$smarty ->assign("message",$message);
	}

	break;

}



$exclude = array(1 ,119 ,166 ,179 ,260, 336, 341, 613, 616, 615, 414, 597, 1071, 825, 837, 979, 1073, 1158, 1159, 1498, 1515, 1516, 2329);
$sql = "SELECT c.cat_id, if(pa.cat_name != p.name,CONCAT_WS('_',pa.cat_name,c.cat_name),c.cat_name) as name, c.parent_id,  COUNT(s.cat_id) AS has_children " .
		 'FROM ' . $GLOBALS ['ecs']->table ( 'category' ) . " AS c " . 
		 "LEFT JOIN " . $GLOBALS ['ecs']->table ( 'category' ) . " AS s ON s.parent_id=c.cat_id " . 
		 "LEFT JOIN " . $GLOBALS ['ecs']->table ( 'category' ) . " AS pa ON c.parent_id=pa.cat_id " . 
		 "LEFT JOIN romeo.party p on p.party_id = c.party_id " .
		"where c.is_delete = 0 " . 
		"and c.party_id = ".$_SESSION['party_id']." and c.parent_id not in ('2245','0') " . 
		" GROUP BY c.cat_id HAVING has_children =0  " . 'ORDER BY c.parent_id,c.sort_order ASC ';	
$cat_list = $db->getAll($sql);

$cat = array("ALL"=>"不选");
foreach ($cat_list as $key=>$value) {
	if(in_array($value['cat_id'],$exclude)){
		unset($cat_list[$key]);
	}else{
		$cat[$value['cat_id']] = $value['name'];
	}
}

$condition_id = " ";
if( isset($_REQUEST['approve_manage']) && trim($_REQUEST['approve_manage']) != 'ALL'){
	$condition_id = " AND if(tis.goods_id is null,i.goods_id,tis.goods_id) in (select goods_id from ecs_goods where cat_id = '".$_REQUEST['approve_manage']."')";

}

$smarty->assign('cat',$cat);

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
$smarty->assign('tc_price_agreed_list',array(
	'ALL' => '不选',
	'false' => '不一致',	
	'true' => '一致'
	));

$application_list = get_taobao_shop_nicks();
$smarty->assign('application_list', $application_list);

$shop_inventory_ratio = get_shop_inventory_ratio();
$smarty->assign('shop_inventory_ratio',$shop_inventory_ratio);

$size = 20;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;
if ($_REQUEST['csv'] == null) {
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
			$outer_Conditon .= " AND outer_status = '正确'";
		}else{
			$outer_Conditon .= " AND outer_status = '错误'";
		}
	}
	if ( isset($_REQUEST['tc_price_agreed']) && trim($_REQUEST['tc_price_agreed']) != 'ALL' ) {
		if ( $_REQUEST['tc_price_agreed'] == 'true' ) {
			$outer_Conditon .= " AND tc_price_agreed = '一致'";
		}else{
			$outer_Conditon .= " AND tc_price_agreed = '不一致'";
		}
	}
	
	$sql = "SELECT sc.nick, ti.title, ti.outer_id, ti.num_iid, ti.sku_id, ti.goods_id, ti.style_id,ti.taobao_goods_id,
	IF(dg.code IS NOT NULL,dg.name,IF(gs.style_id IS NULL,g.goods_name,CONCAT_WS(',',g.goods_name,s.color))) as gName, 
	IF(dg.code IS NOT NULL || (((ti.style_id=0 AND gs.style_id IS NULL) || ti.style_id = gs.style_id) and g.goods_name IS NOT NULL),'正确','错误') as outer_status, 
	ti.quantity, ti.reserve_number, ti.reserve_ratio,ti.inventory_ratio, ti.warn_number, IF(ti.is_sync = 0,'不同步','同步') as is_sync, ti.approve_status, ti.last_update_timestamp,
	taobao_price , dg.amount as erp_price  , 
	IF(dg.amount IS NOT NULL ,IF( dg.amount = taobao_price ,'一致','不一致'),'') as tc_price_agreed
	FROM(SELECT i.application_key, i.num_iid, 
		IF(tis.sku_id IS NULL, '', tis.sku_id) as sku_id, 
		IF(tis.goods_id IS NULL,i.title,CONCAT_WS(',',i.title,tis.properties)) as title, 
		ifnull(tis.sku_id,i.num_iid) taobao_goods_id,
		IF(tis.goods_id IS NULL,i.goods_id,tis.goods_id) as goods_id, 
		IF(tis.goods_id IS NULL,i.style_id,tis.style_id) as style_id, 
		IF(tis.goods_id IS NULL,i.outer_id,tis.outer_id) as outer_id,
		IF(tis.goods_id IS NULL,i.num,tis.quantity) as quantity,
		IF(tis.goods_id IS NULL,i.reserve_number,tis.reserve_number) as reserve_number,
		IF(tis.goods_id IS NULL,i.reserve_ratio,tis.reserve_ratio) as reserve_ratio,
		IF(tis.goods_id IS NULL,i.inventory_ratio,tis.inventory_ratio) as inventory_ratio,
		IF(tis.goods_id IS NULL,i.warn_number,tis.warn_number) as warn_number,
		IF(tis.goods_id IS NULL,i.is_sync,tis.is_sync) as is_sync,
		IF(tis.goods_id IS NULL,i.last_update_timestamp,tis.last_update_timestamp) as last_update_timestamp,
		if(i.approve_status='onsale','上架','下架') as approve_status ,
		IF(tis.price IS NULL,i.price,tis.price) as taobao_price		
			FROM ecshop.sync_taobao_items i 
		LEFT JOIN ecshop.sync_taobao_items_sku tis ON i.num_iid = tis.num_iid and tis.last_update_timestamp>=date_add(now(),interval - 30 day)
		WHERE 1 ".$condition .$condition_id.
		") as ti 
LEFT JOIN ecshop.ecs_goods_style gs ON ti.goods_id = gs.goods_id AND (ti.style_id=0 OR ti.style_id=gs.style_id) 
LEFT JOIN ecshop.ecs_goods g ON ti.goods_id = g.goods_id
LEFT JOIN ecshop.ecs_style s ON gs.style_id = s.style_id 
LEFT JOIN ecshop.distribution_group_goods dg ON ti.outer_id = dg.code 
LEFT JOIN ecshop.taobao_shop_conf sc on ti.application_key = sc.application_key
GROUP BY ti.num_iid,ti.sku_id having 1=1 ".$outer_Conditon;

$sql1 = $sql.$limit.$offset;
	//	Qlog::log($sql1);
	//var_dump($sql1);
$taobao_items_list = $db->getAll($sql1);
$sql2 = "SELECT COUNT(*) FROM(".$sql.") as countItems";

$count = $db->getOne($sql2);
$pager = setPager($count, $size, $page,"taobao_items_update.php?request=search&goods_name=".trim($_REQUEST['goods_name'])."&outer_id=".$_REQUEST['outer_id'].
	"&application_nicks=".trim($_REQUEST['application_nicks'])."&outer_status=".$_REQUEST['outer_status']."&approve_status=".$_REQUEST['approve_status']
	."&approve_manage=".$_REQUEST['approve_manage']."&tc_price_agreed=".$_REQUEST['tc_price_agreed']);

$application_nicks = trim($_REQUEST['application_nicks']);
$sql = "select is_stock_update from ecshop.taobao_shop_conf where application_key = '$application_nicks'";
$change_stock_update = $db->getOne($sql);
if($change_stock_update == "Y"){
	$change_stock_update = "停止同步";
} else if($change_stock_update == "N"){
	$change_stock_update = "开启同步";
}
$smarty->assign('request',$_REQUEST['request']);
$smarty->assign('goods_name',trim($_REQUEST['goods_name']));
$smarty->assign('outer_id',trim($_REQUEST['outer_id']));
$smarty->assign('application_nicks',trim($_REQUEST['application_nicks']));
$smarty->assign('approve_status',trim($_REQUEST['approve_status']));
$smarty->assign('outer_status',trim($_REQUEST['outer_status']));
$smarty->assign('tc_price_agreed',trim($_REQUEST['tc_price_agreed']));
$smarty->assign('change_stock_update',$change_stock_update);
$smarty->assign('pager', $pager);
$smarty->assign('taobao_items_list',$taobao_items_list);
}
if($_REQUEST['csv']){
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=".iconv("UTF-8","GB18030","淘宝直销报表") . ".csv");
	$out = $smarty->fetch("oukooext/taobao_items_update_csv.htm");
	echo iconv("UTF-8","GB18030",$out);
	exit();
}
else{
	$smarty->display("taobao/taobao_items_update.htm");
}

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
	$sql = "SELECT nick,application_key FROM ecshop.taobao_shop_conf WHERE shop_type = 'taobao' and party_id = '".$_SESSION['party_id']."'";
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
		$condition .= " AND i.title like '%".mysql_escape_string(trim($_REQUEST['goods_name']))."%'";
	}
	if ( isset($_REQUEST['outer_id']) && trim($_REQUEST['outer_id']) != '') {
		$outer_id = trim($_REQUEST['outer_id']);
		$condition .= " AND ( (tis.num_iid is null AND i.outer_id='{$outer_id}')  OR (tis.num_iid is not null AND tis.outer_id='{$outer_id}')  )";
	}
	if ( isset($_REQUEST['application_nicks']) && trim($_REQUEST['application_nicks']) != 'ALL' ) {
		$condition .= " AND i.application_key = '".mysql_escape_string(trim($_REQUEST['application_nicks']))."'";
	}else {
		$condition .= " AND i.party_id = '".$_SESSION['party_id']."'";
	}
	if ( isset($_REQUEST['approve_status']) && trim($_REQUEST['approve_status']) != 'ALL' ) {
		$condition .= " AND i.approve_status = '".mysql_escape_string(trim($_REQUEST['approve_status']))."'";
	}
	if ( isset($_REQUEST['is_sync_status']) && trim($_REQUEST['is_sync_status']) != 'ALL' ) {
		$condition .= " AND if(tis.num_iid is null,i.is_sync,tis.is_sync) = '".mysql_escape_string(trim($_REQUEST['is_sync_status']))."'";
	}
	
	return $condition;
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