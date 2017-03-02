<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
admin_priv('taobao_items_list'); ////////////加权限

$smarty->assign('status_list',array(
	'ALL' => '不选',
	'TCUnequal' => '套餐价格不相等',
	'true'  =>  '编码正确',
	'false' =>  '编码错误',
));
$smarty->assign('approve_status_list',array(
	'ALL' => '不选',
	'onsale'  =>  '在售',
	'instock' =>  '库中',
));

$application_list = get_taobao_shop_nicks();
$smarty->assign('application_list', $application_list);

$condition = get_condition();
$outer_Conditon = "";
if ( $_REQUEST['outer_status'] != 'ALL' ) {
	if ( $_REQUEST['outer_status'] == 'TCUnequal' ) {
		$outer_Conditon .= " AND outer_status = '套餐价格不相等'";
	}else if ( $_REQUEST['outer_status'] == 'true' ) {
		$outer_Conditon .= " AND outer_status = '编码正确'";
	}else{
		$outer_Conditon .= " AND outer_status = '编码错误'";
	}
}
if (trim($_REQUEST['goods_name']) != '') {
        $outer_Conditon .= "AND (title like '%".mysql_escape_string(trim($_REQUEST['goods_name']))."%' or gName like '%".mysql_escape_string(trim($_REQUEST['goods_name']))."%')";
}

	
$sql1 = "select nick, title,  outer_id,  goods_id,  style_id, gName, outer_status, price,  quantity,  approve_status,  last_update_timestamp from(
		SELECT ti.nick,ti.num_iid,ti.sku_id, ti.title, ti.outer_id, ti.goods_id, ti.style_id, 
				IF(dg.code IS NOT NULL,dg.name,IF(gs.style_id IS NULL,g.goods_name,CONCAT_WS(',',g.goods_name,s.color))) as gName, 
				IF(dg.code IS NOT NULL AND dg.amount != ti.price,'套餐价格不相等',if((dg.code IS NOT NULL AND dg.amount = ti.price) || ((ti.style_id=0 AND gs.style_id IS NULL) || ti.style_id = gs.style_id)and g.goods_name IS NOT NULL,'编码正确','编码错误'))as outer_status, 
				ti.price, ti.quantity, ti.approve_status, ti.last_update_timestamp 
		FROM(SELECT i.nick,i.num_iid,tis.sku_id, 
				IF(tis.goods_id IS NULL,i.title,CONCAT_WS(',',i.title,tis.properties_name)) as title, 
				IF(tis.goods_id IS NULL,i.goods_id,tis.goods_id) as goods_id, 
				IF(tis.goods_id IS NULL,i.style_id,tis.style_id) as style_id, 
				IF(tis.goods_id IS NULL,i.outer_id,tis.outer_id) as outer_id, 
				IF(tis.goods_id IS NULL,i.price,tis.price) as price, 
				IF(tis.goods_id IS NULL,i.num,tis.quantity) as quantity,
				IF(tis.goods_id IS NULL,i.last_update_timestamp,tis.last_update_timestamp) as last_update_timestamp,
				IF(i.approve_status = 'onsale','在售','库中') as approve_status 
			FROM ecshop.sync_taobao_items i 
			LEFT JOIN ecshop.sync_taobao_items_sku tis ON i.num_iid = tis.num_iid 
			WHERE 1 ".$condition."
		) as ti 
		LEFT JOIN ecshop.ecs_goods_style gs ON ti.goods_id = gs.goods_id AND (ti.style_id=0 OR ti.style_id=gs.style_id) and gs.is_delete=0
		LEFT JOIN ecshop.ecs_goods g ON ti.goods_id = g.goods_id 
		LEFT JOIN ecshop.ecs_style s ON gs.style_id = s.style_id 
		LEFT JOIN ecshop.distribution_group_goods dg ON ti.outer_id = dg.code	
	)as tii where 1 ".$outer_Conditon."
	GROUP BY  num_iid, sku_id ";

$act = $_REQUEST['act'];
if ( $act == 'search' || empty($act) ) {
	$size = 20;
	$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
	//echo "page=".$_REQUEST['page'];
	$start = ($page - 1) * $size;
	if ($csv == null) {
	    $limit = " LIMIT $size";
	    $offset = " OFFSET $start";
	}

	$sql2 = $sql1.$limit.$offset;
//	var_dump($sql2);
	$taobao_items_list = $slave_db->getAll($sql2);
	
	$sql2 = "SELECT count(1) FROM (".$sql1.") AS countItems";
	//var_dump($sql2);
	$count = $slave_db->getOne($sql2);
	$pager = setPager($count, $size, $page,"taobao_items_list.php?goods_name=".trim($_REQUEST['goods_name'])."&outer_id=".$_REQUEST['outer_id'].
			"&application_nicks=".trim($_REQUEST['application_nicks'])."&outer_status=".$_REQUEST['outer_status']."&approve_status=".$_REQUEST['approve_status']);
	$smarty->assign('goods_name',trim($_REQUEST['goods_name']));
	$smarty->assign('outer_id',trim($_REQUEST['outer_id']));
	$smarty->assign('application_nicks',trim($_REQUEST['application_nicks']));
	
	$smarty->assign('pager', $pager);
	//$smarty->assign('page', $getPage);
	$smarty->assign('taobao_items_list',$taobao_items_list);
	$smarty->display("taobao/taobao_items_list.htm");
	
}else if( $act == 'csv' ) {
	
	ini_set('memory_limit','2048M');
	
	//var_dump($sql1);
	$csv_taobao_items = $slave_db->getAll($sql1);
	//$smarty->display("taobao/taobao_items_list.htm");
	
	set_include_path(get_include_path() . PATH_SEPARATOR . '../includes/Classes/');
    require 'PHPExcel.php';
    require 'PHPExcel/IOFactory.php';
    
	$title = array(0 => array(
        '商店名', '淘宝后台商品名称', '商家编码', 'ERP商品名称', 
        '价格', '数量', '商品上传状态','商家编码状态', '最后更新时间'
    ));
    $type = array(0 => 'string',);
    
    $filename = "";
    if( !empty($_REQUESt['goods_name']) ){
    	$filename .= $_REQUESt['goods_name']."_";
    }
    if ( !empty($_REQUEST['outer_id']) ) {
		$filename .= "商家编码(".$_REQUEST['outer_id'].")_";
	}
	$filename .= "淘宝直销商品_".date("Y-m-d").".xlsx";
    
    $excel = new PHPExcel();
    $sheet = $excel->getActiveSheet();
    $sheet->fromArray($title);
    $csv_taobao_items = array_map('array_values', $csv_taobao_items);
    if (!empty($csv_taobao_items)) {
        $sheet->fromArray($csv_taobao_items, null, 1, $type);
    }
    
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $output->setOffice2003Compatibility(true);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $output->save('php://output');
    
    exit();
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

/**
 * 获得条件
 *
 */
function get_condition(){
	$condition = "";
	
    if (trim($_REQUEST['outer_id']) != '') {
        $condition .= " AND (i.outer_id = '".mysql_real_escape_string(trim($_REQUEST['outer_id']))."' OR tis.outer_id = '".mysql_escape_string(trim(trim($_REQUEST['outer_id'])))."')";
    }
	if ( trim($_REQUEST['application_nicks']) != 'ALL' ) {
		$condition .= " AND i.application_key = '".mysql_real_escape_string(trim($_REQUEST['application_nicks']))."'";
	}else {
		$condition .= " AND i.party_id = '".$_SESSION['party_id']."'";
	}
	if ( trim($_REQUEST['approve_status']) != 'ALL' ) {
		$condition .= " AND i.approve_status = '".mysql_real_escape_string(trim($_REQUEST['approve_status']))."'";
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
