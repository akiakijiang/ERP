<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . "includes/cls_json.php");
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');

if($_SESSION['party_id'] != 65625) {
	die('非中粮高端人士不得擅入');
}

$best_order_status_list = array(
	'ALL' => '所有',
	'ERP_CANCELED' => 'ERP已取消',
	'ERP_WAIT_SUBMITTED' =>  'ERP等待提交收货申请',
	'ERP_SUBMITTED' =>  'ERP已提交收货申请',
	'WMS_WAIT_FULFILLED' =>  'WMS等待收货',
	'WMS_FULFILLED' =>  'WMS已收货ERP未收货',
	'ERP_FULFILLED' =>  'ERP已收货',
	'WMS_REJECT' => 'WMS已拒绝',
	'CANCELED' => 'WMS已取消',
	'CANCELEDFAIL' => 'WMS取消失败',
);

$best_order_status_field = "
	if(bi.order_id is null,
		'ERP_WAIT_SUBMITTED',
		if(ba.order_id is null,
			'ERP_SUBMITTED',
			if(ba.order_status='WMS_ACCEPT',
				'WMS_WAIT_SENDED',
				if(ba.rma_status='FULFILLED',
					if(bi.indicate_status='FINISHED','ERP_FULFILLED','WMS_FULFILLED'),
					if(ba.rma_status='WMS_REJECT','WMS_REJECT',ba.rma_status)
				)
			)
		)
	)   ";

$apply_time_field = "ifnull(bi.created_stamp,'')";


$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):'';

$size = 20;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;
if ($_REQUEST['csv'] == null) {
    $limit = " LIMIT $size";
    $offset = " OFFSET $start";
}

if($_REQUEST['request'] == 'search'){

	$condition = get_condition();
	
	$sql = "
		select
			o.order_id,
			o.order_sn,
			o.order_time,
			ifnull(ba.note,'') note,
			{$best_order_status_field} best_order_status,
			{$apply_time_field} apply_time,
			if(ba.rma_status='FULFILLED',ba.last_updated_stamp,'') wms_fulfill_time,
			if(bi.indicate_status='FINISHED',bi.last_updated_stamp,'') erp_fulfill_time
		from
			ecshop.service s 
			inner join ecshop.ecs_order_info o ON s.back_order_id = o.order_id
			left join ecshop.express_best_indicate bi on o.order_id = bi.order_id
			left join ecshop.express_best_actual  ba on bi.order_id = ba.order_id
		where
			o.party_id=65625 and s.service_type in (1, 2) and  bi.order_type_id='INVENTORY_RETURN' {$condition}
	";
	
	
	$sql1 = $sql.$limit.$offset;

	$taobao_order_list = $db->getAll($sql1);

	
	$sql2 = "SELECT COUNT(*) FROM(".$sql.") as countItems";
			
	$count = $db->getOne($sql2);
	$pager = setPager($count, $size, $page,"zhongliang_return_order_list.php?request=search".getReqString());
	

	$smarty->assign('request',$_REQUEST['request']);
	$smarty->assign('pager', $pager);
	$smarty->assign('taobao_order_list',$taobao_order_list);
	
} 

$smarty->assign('best_order_status_list',$best_order_status_list);
$smarty->assign('time_type_list',$time_type_list);

$smarty->display("taobao/zhongliang_return_order_list.htm");


/**
 * 获取参数字符串
 */
function getReqString() {
	$req = "";
	if (isset($_REQUEST['order_id']) && trim($_REQUEST['order_id']) != '') {
		$order_id = mysql_real_escape_string(trim($_REQUEST['order_id']));
        $req .= "&order_id={$batch_order_id}";
    }
	if (isset($_REQUEST['start_order_time']) && trim($_REQUEST['start_order_time']) != '') {
		$start_order_time = mysql_real_escape_string(trim($_REQUEST['start_order_time']));
        $req .= "&start_order_time={$start_order_time}";
    }
	if (isset($_REQUEST['end_order_time']) && trim($_REQUEST['end_order_time']) != '') {
		$end_order_time = mysql_real_escape_string(trim($_REQUEST['end_order_time']));
        $req .= "&end_order_time={$end_order_time}";
    }
	if ( isset($_REQUEST['start_apply_time']) && $_REQUEST['start_apply_time'] != '' ) {
		$start_apply_time = mysql_real_escape_string(trim($_REQUEST['start_apply_time']));
        $req .= "&start_apply_time={$start_apply_time}";
	}
	if ( isset($_REQUEST['end_apply_time']) && $_REQUEST['end_apply_time'] != '' ) {
		$end_apply_time = mysql_real_escape_string(trim($_REQUEST['end_apply_time']));
        $req .= "&end_apply_time={$end_apply_time}";
	}
	if (isset($_REQUEST['best_order_status']) && $_REQUEST['best_order_status'] != 'ALL' ) {
		$best_order_status = $_REQUEST['best_order_status'];
		$req .= "&best_order_status={$best_order_status}";
	}
	return $req;
}


/**fdc
 * 获得条件
 *
 */
function get_condition(){
	global $db;
	global $best_order_status_field;
	global $apply_time_field;
	extract($_REQUEST);
	$condition = "";
	//Qlog::log($_REQUEST['goods_name'].'---'.$_REQUEST['outer_id'].'---'.$_REQUEST['application_nicks'].'---'.$_REQUEST['approve_status']);
	if (isset($_REQUEST['order_id']) && trim($_REQUEST['order_id']) != '') {
		$order_id = mysql_real_escape_string(trim($_REQUEST['order_id']));
        $condition .= " AND o.order_id ={$order_id}";
    }
    
	if (isset($_REQUEST['order_sn']) && trim($_REQUEST['order_sn']) != '') {
		$order_sn = mysql_real_escape_string(trim($_REQUEST['order_sn']));
        $condition .= " AND o.order_sn ='{$order_sn}'";
    }
    
	if (isset($_REQUEST['taobao_order_sn']) && trim($_REQUEST['taobao_order_sn']) != '') {
		$taobao_order_sn = mysql_real_escape_string(trim($_REQUEST['taobao_order_sn']));
        $condition .= " AND o.taobao_order_sn ='{$taobao_order_sn}'";
    }
    
	if (isset($_REQUEST['start_order_time']) && trim($_REQUEST['start_order_time']) != '') {
		$start_order_time = mysql_real_escape_string(trim($_REQUEST['start_order_time']));
        $condition .= " AND o.order_time >= '{$start_order_time}'";
    }
    
	if (isset($_REQUEST['end_order_time']) && trim($_REQUEST['end_order_time']) != '') {
		$end_order_time = mysql_real_escape_string(trim($_REQUEST['end_order_time']));
        $condition .= " AND o.order_time < '{$end_order_time}'";
    }
	if ( isset($_REQUEST['best_order_status']) && $_REQUEST['best_order_status'] != 'ALL' ) {
			$best_order_status = $_REQUEST['best_order_status'];
			$condition .= " and {$best_order_status_field} = '{$best_order_status}'";
	}
	if ( isset($_REQUEST['start_apply_time']) && $_REQUEST['start_apply_time'] != '' ) {
			$start_apply_time = mysql_real_escape_string(trim($_REQUEST['start_apply_time']));
        	$condition .= " AND {$apply_time_field} >= '{$start_apply_time}'";
	}
	if ( isset($_REQUEST['end_apply_time']) && $_REQUEST['end_apply_time'] != '' ) {
			$end_apply_time = mysql_real_escape_string(trim($_REQUEST['end_apply_time']));
        	$condition .= " AND {$apply_time_field} < '{$end_apply_time}'";
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