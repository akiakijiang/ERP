<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . "includes/cls_json.php");
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

//验证权限
admin_priv('taobao_fenxiao_items_update');

$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):'';
$tpl =  array ('批量导入库存比例' =>  array (
	        		'outer_id' => '商家编码', 
	                'reserve_number' => '预留库存数量', 
	                'reserve_ratio' => '预留库存比例' ) );
switch($act){
	case 'change' :
		$json = new JSON();
		$application_key = isset($_REQUEST['application_key'])?trim($_REQUEST['application_key']):'';
		$is_items_update = isset($_REQUEST['is_items_update'])?trim($_REQUEST['is_items_update']):'';
		$sql = "select * from ecshop.taobao_fenxiao_shop_conf where  is_stock_update = '$is_items_update' and application_key = '$application_key'";
		$result = $db->getRow($sql);
		if(!$result){
			$sql = "update ecshop.taobao_fenxiao_shop_conf set is_stock_update = '$is_items_update',update_user = '{$_SESSION['admin_name']}',update_time = now() where application_key = '$application_key'";
			$result = $db->query($sql);
			$record_sql = "INSERT INTO ecshop.risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
	            			"VALUES ('{$_SESSION['admin_name']}', 'update', NOW(), 'taobao_fenxiao_items_update.php', 'taobao_fenxiao_items_update', '".mysql_real_escape_string($sql)."', '{$_POST['comment']}')";
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
		$pid = isset($_REQUEST['pid'])?trim($_REQUEST['pid']):'';
		$sku_id = isset($_REQUEST['sku_id'])?trim($_REQUEST['sku_id']):'';
		$field = isset($_REQUEST['field'])?trim($_REQUEST['field']):'';
		$number = isset($_REQUEST['number'])?trim($_REQUEST['number']):'';
		$sql1 = "SELECT * FROM ecshop.sync_taobao_fenxiao_items_sku WHERE pid = '{$pid}' and sku_id = '{$sku_id}'";
		$sql2 = "SELECT * FROM ecshop.sync_taobao_fenxiao_items WHERE pid = '{$pid}'";
		$result = "数据更新成功";
		if( $sku_id != '' && $db->getRow($sql1) ){
			$sql = "UPDATE ecshop.sync_taobao_fenxiao_items_sku SET {$field} = $number WHERE pid = '{$pid}' and sku_id = '{$sku_id}'";
	//		Qlog::log($sql);
			if(!$db->query($sql)){
				$result = "数据更新失败";
			}
		}elseif( $sku_id == '' && $db->getRow($sql2) ){
			$sql = "UPDATE ecshop.sync_taobao_fenxiao_items SET {$field} = $number WHERE pid = '{$pid}'";
	//		Qlog::log($sql);
			if(!$db->query($sql)){
				$result = "数据更新失败";
			}
		}else{
			$result = "在数据表内找不到该商品";
		}
		print $json->encode($result);
		exit();
	case 'sync_update' :
		$is_sync = isset($_REQUEST['is_sync'])?trim($_REQUEST['is_sync']):false;
		$item_pid = isset($_REQUEST['item_pid'])?trim($_REQUEST['item_pid']):'';
		$sku_id = isset($_REQUEST['sku_id'])?trim($_REQUEST['sku_id']):'';
		
		$sql1 = "SELECT * FROM ecshop.sync_taobao_fenxiao_items_sku WHERE pid = '{$item_pid}' and sku_id = '{$sku_id}'";
		$sql2 = "SELECT * FROM ecshop.sync_taobao_fenxiao_items WHERE pid = '{$item_pid}'";
		$result = 'fail';
		if( $sku_id != '' && $db->getRow($sql1) ){
			$sql = "UPDATE ecshop.sync_taobao_fenxiao_items_sku SET is_sync = $is_sync WHERE pid = '{$item_pid}' and sku_id = '{$sku_id}'";
	//		Qlog::log($sql);
			$db->query($sql);
		}elseif( $sku_id == '' && $db->getRow($sql2) ){
			$sql = "UPDATE ecshop.sync_taobao_fenxiao_items SET is_sync = $is_sync WHERE pid = '{$item_pid}'";
	//		Qlog::log($sql);
			$db->query($sql);
		}
		break;
		
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
				//判断该商品编号是否存在于分销中
				$sql = "select 1 from ecshop.sync_taobao_fenxiao_items i left join 
						ecshop.sync_taobao_fenxiao_items_sku s on i.pid = s.pid 
						where i.party_id = ".$party_id." and  (i.outer_id ='".$outer_id."' or s.outer_id = '".$outer_id."')";
				if(!$db->getRow($sql)){
					$message .= "第".($key+1)."行的{$outer_id}商品不在分销商品中	<br/>";
					continue;	
				}
				if(!(is_numeric($reserve_number) && $reserve_number >= 0)){
					$message .= "第".($key+1)."行的{$outer_id}商品预留数量必须大于0 <br/>";
					continue;	
				} 
				if(!(is_numeric($reserve_ratio) && $reserve_ratio >= 0 && $reserve_ratio <= 1)){
					$message .= "第".($key+1)."行的{$outer_id}商品预留比例不在0到1之间 <br/>";
					continue;
				}
				$sql1 = "update ecshop.sync_taobao_fenxiao_items set reserve_number={$reserve_number},reserve_ratio = {$reserve_ratio} where outer_id='{$outer_id}'and party_id = ".$party_id;
				$sql2 = "update ecshop.sync_taobao_fenxiao_items_sku set reserve_number={$reserve_number},reserve_ratio = {$reserve_ratio} where outer_id='{$outer_id}'and party_id = ".$party_id;
				$db->query($sql1);
				$db->query($sql2);
			}
		}
		
		if($message){
			$smarty->assign('message',$message);
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
	'up'  =>  '上架',
	'down' =>  '下架',
));
$smarty->assign('is_sync_status_list',array(
		'ALL' => '不选',
		'0' => '不同步',
		'1' => '同步'
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
	$sql = "SELECT sc.nick, ti.title, ti.outer_id, ti.pid, ti.sku_id, ti.goods_id, ti.style_id,
				IF(dg.code IS NOT NULL,dg.name,IF(gs.style_id IS NULL,g.goods_name,CONCAT_WS(',',g.goods_name,s.color))) as gName, 
				IF(dg.code IS NOT NULL || (((ti.style_id=0 AND gs.style_id IS NULL) || ti.style_id = gs.style_id) and g.goods_name IS NOT NULL),'正确','错误') as outer_status, 
				ti.price, ti.retail_price_high, ti.retail_price_low, ti.quantity, ti.reserve_number, ti.reserve_ratio, ti.warn_number, IF(ti.is_sync = 0,'不同步','同步') as is_sync, ti.approve_status, ti.last_update_timestamp 
			FROM(SELECT i.application_key, i.pid,
					IF(tis.sku_id IS NULL, '', tis.sku_id) as sku_id, 
					IF(tis.goods_id IS NULL,i.name,CONCAT_WS(',',i.name,tis.properties)) as title, 
					IF(tis.goods_id IS NULL,i.goods_id,tis.goods_id) as goods_id, 
					IF(tis.goods_id IS NULL,i.style_id,tis.style_id) as style_id, 
					IF(tis.goods_id IS NULL,i.outer_id,tis.outer_id) as outer_id, 
					IF(tis.goods_id IS NULL,i.standard_retail_price,tis.standard_price) as price,
					i.retail_price_high,i.retail_price_low,
					IF(tis.goods_id IS NULL,i.quantity,tis.quantity) as quantity,
					IF(tis.goods_id IS NULL,i.reserve_number,tis.reserve_number) as reserve_number,
					IF(tis.goods_id IS NULL,i.reserve_ratio,tis.reserve_ratio) as reserve_ratio,
					IF(tis.goods_id IS NULL,i.warn_number,tis.warn_number) as warn_number,
					IF(tis.goods_id IS NULL,i.is_sync,tis.is_sync) as is_sync,
					IF(tis.goods_id IS NULL,i.last_update_timestamp,tis.last_update_timestamp) as last_update_timestamp,
					IF(i.status = 'up','上架','下架') as approve_status 
				FROM ecshop.sync_taobao_fenxiao_items i 
					LEFT JOIN ecshop.sync_taobao_fenxiao_items_sku tis ON i.pid = tis.pid and tis.last_update_timestamp>=date_add(now(),interval - 30 day)
				WHERE 1 ".$condition . $condition_id.
			") as ti 
			LEFT JOIN ecshop.ecs_goods_style gs ON ti.goods_id = gs.goods_id AND (ti.style_id=0 OR ti.style_id=gs.style_id) and gs.is_delete=0
			LEFT JOIN ecshop.ecs_goods g ON ti.goods_id = g.goods_id
			LEFT JOIN ecshop.ecs_style s ON gs.style_id = s.style_id 
			LEFT JOIN ecshop.distribution_group_goods dg ON ti.outer_id = dg.code 
			LEFT JOIN ecshop.taobao_shop_conf sc on ti.application_key = sc.application_key
			GROUP BY ti.pid,ti.sku_id".$outer_Conditon;
	
	$sql1 = $sql.$limit.$offset;
//	Qlog::log($sql1);
	//var_dump($sql1);
	$taobao_items_list = $db->getAll($sql1);
	$sql2 = "SELECT COUNT(*) FROM(".$sql.") as countItems";
			
	$count = $db->getOne($sql2);
	$pager = setPager($count, $size, $page,"taobao_fenxiao_items_update.php?request=search&goods_name=".trim($_REQUEST['goods_name'])."&outer_id=".$_REQUEST['outer_id'].
		"&application_nicks=".trim($_REQUEST['application_nicks'])."&outer_status=".$_REQUEST['outer_status']."&approve_status=".$_REQUEST['approve_status']."&approve_manage=".$_REQUEST['approve_manage']);
	$application_nicks = trim($_REQUEST['application_nicks']);
	
	$change_stock_update = '';
	if($application_nicks!='') {
		$sql = "select is_stock_update from ecshop.taobao_shop_conf where application_key = '$application_nicks'";
		$change_stock_update = $db->getOne($sql);
	}
	
	if($change_stock_update == "Y"){
		$change_stock_update = "停止同步";
	} else if($change_stock_update == "N"){
		$change_stock_update = "开启同步";
	}
	
	$application_key = isset($_REQUEST['application_nicks'])?trim($_REQUEST['application_nicks']):'';
	$sql = "select is_stock_update from ecshop.taobao_fenxiao_shop_conf where application_key = '$application_key'";
	$change_stock_update = $db->getOne($sql);
	if(!$change_stock_update){
		$change_stock_update = "未开启分销";
	} else if($change_stock_update == "OK"){
		$change_stock_update = "停止同步";
	} else if($change_stock_update == "DELETE"){
		$change_stock_update = "开启同步";
	}
	
	$smarty->assign('goods_name',trim($_REQUEST['goods_name']));
	$smarty->assign('outer_id',trim($_REQUEST['outer_id']));
	$smarty->assign('application_nicks',trim($_REQUEST['application_nicks']));
	$smarty->assign('approve_status',trim($_REQUEST['approve_status']));
	$smarty->assign('outer_status',trim($_REQUEST['outer_status']));
	$smarty->assign('change_stock_update',$change_stock_update);
	$smarty->assign('pager', $pager);
	$smarty->assign('taobao_items_list',$taobao_items_list);
}

$smarty->assign('change_stock_update', $change_stock_update);

$smarty->display("taobao/taobao_fenxiao_items_update.htm");

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
        $condition .= " AND i.name like '%".mysql_escape_string(trim($_REQUEST['goods_name']))."%'";
    }
    if ( isset($_REQUEST['outer_id']) && trim($_REQUEST['outer_id']) != '') {
        $outer_id = trim($_REQUEST['outer_id']);
        $condition .= " AND ( (tis.pid is null AND i.outer_id='{$outer_id}')  OR (tis.pid is not null AND tis.outer_id='{$outer_id}')  )";
    }
	if ( isset($_REQUEST['application_nicks']) && trim($_REQUEST['application_nicks']) != 'ALL' ) {
		$condition .= " AND i.application_key = '".mysql_escape_string(trim($_REQUEST['application_nicks']))."'";
	}else {
		$condition .= " AND i.party_id = '".$_SESSION['party_id']."'";
	}
	if ( isset($_REQUEST['approve_status']) && trim($_REQUEST['approve_status']) != 'ALL' ) {
		$condition .= " AND i.status = '".mysql_escape_string(trim($_REQUEST['approve_status']))."'";
	}
	if ( isset($_REQUEST['is_sync_status']) && trim($_REQUEST['is_sync_status']) != 'ALL' ) {
		$condition .= " AND if(tis.pid is null,i.is_sync,tis.is_sync) = '".mysql_escape_string(trim($_REQUEST['is_sync_status']))."'";
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