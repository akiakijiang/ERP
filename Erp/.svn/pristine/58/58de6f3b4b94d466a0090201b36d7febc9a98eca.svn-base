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
	'ERP_WAIT_CONFIRMED' => 'ERP未确认',
	'ERP_WAIT_RESERVED'  =>  'ERP已确认未预定',
	'ERP_WAIT_SUBMITTED' =>  'ERP等待提交发货申请',
	'ERP_SUBMITTED' =>  'ERP已提交发货申请',
	'WMS_WAIT_SENDED' =>  'WMS等待发货',
	'WMS_SENDED' =>  'WMS已发货ERP未发货',
	'ERP_SENDED' =>  'ERP已发货',
	'WMS_REJECT' => 'WMS已拒绝',
	'CANCELED' => 'WMS已取消',
	'CANCELEDFAIL' => 'WMS取消失败',
);

$order_type_list = array(
	'SALE' => '销售订单',
	'SUPPLIER_RETURN' => '供应商退货'
);

$best_order_status_field = "
	if(bi.order_id is null, 
		'ERP_WAIT_SUBMITTED',
		if(ba.order_id is null,
			'ERP_SUBMITTED',
			if(ba.order_status='WMS_ACCEPT',
				'WMS_WAIT_SENDED',
				if(ba.order_status='DELIVERED',
					if(o.actual_out_time>0,'ERP_SENDED','WMS_SENDED'),
					if(ba.order_status='WMS_REJECTED','WMS_REJECTED',ba.order_status)
				)
			)
		)
	) ";

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
			o.batch_gt_sn,
			o.created_stamp order_time,
			ifnull(ba.note,'') note,
			{$best_order_status_field} best_order_status,
			{$apply_time_field} apply_time,
			if(ba.order_status='DELIVERED',ba.last_updated_stamp,'') wms_send_time,
			if(o.actual_out_time=0,'',actual_out_time) erp_send_time
		from
			ecshop. ecs_batch_gt_info o 
			left join ecshop.express_best_indicate bi on o.batch_gt_sn=bi.order_id
			left join ecshop.express_best_actual  ba on o.batch_gt_sn=ba.order_id
		where
			o.party_id=65625 {$condition}
	";
	
	$sql1 = $sql.$limit.$offset;

	$taobao_order_list = $db->getAll($sql1);

	
	$sql2 = "SELECT COUNT(*) FROM(".$sql.") as countItems";
			
	$count = $db->getOne($sql2);
	$pager = setPager($count, $size, $page,"zhongliang_b2b_order_list.php?request=search".getReqString());
	

	$smarty->assign('request',$_REQUEST['request']);
	$smarty->assign('pager', $pager);
	$smarty->assign('taobao_order_list',$taobao_order_list);
	
} 

$smarty->assign('best_order_status_list',$best_order_status_list);
$smarty->assign('time_type_list',$time_type_list);

$smarty->display("taobao/zhongliang_b2b_order_list.htm");

/**
 * 获取参数字符串
 */
function getReqString() {
	$req = "";
	if (isset($_REQUEST['batch_order_id']) && trim($_REQUEST['batch_order_id']) != '') {
		$batch_order_id = mysql_real_escape_string(trim($_REQUEST['batch_order_id']));
        $req .= "&batch_order_id={$batch_order_id}";
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
    
	if (isset($_REQUEST['batch_gt_sn']) && trim($_REQUEST['batch_gt_sn']) != '') {
		$batch_gt_sn = mysql_real_escape_string(trim($_REQUEST['batch_gt_sn']));
        $condition .= " AND o.batch_gt_sn ='{$batch_gt_sn}'";
    }
    
	if (isset($_REQUEST['start_order_time']) && trim($_REQUEST['start_order_time']) != '') {
		$start_order_time = mysql_real_escape_string(trim($_REQUEST['start_order_time']));
        $condition .= " AND o.created_stamp >= '{$start_order_time}'";
    }
    
	if (isset($_REQUEST['end_order_time']) && trim($_REQUEST['end_order_time']) != '') {
		$end_order_time = mysql_real_escape_string(trim($_REQUEST['end_order_time']));
        $condition .= " AND o.created_stamp < '{$end_order_time}'";
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