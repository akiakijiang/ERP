<?php
define('IN_ECS', true);
require('includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . "includes/cls_json.php");
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
//查看权限
admin_priv('gymboree_sync_info');
if($_SESSION['party_id'] != 65574) {
	die('请选择金宝贝业务组进行订单同步查询');
}

$giessen_order_status_list = array(
	'ALL' => '所有',
	'ERP_WAIT_CONFIRMED' => 'ERP未确认',  
	'ERP_WAIT_RESERVED'  =>  'ERP已确认未预定',
	'ERP_WAIT_SUBMITTED' =>  'ERP等待提交发货申请',
	'ERP_SUBMITTED' =>  'ERP已提交发货申请',
	'WMS_SHIP' =>  'WMS已发货ERP未发货',
	'ERP_SHIP' =>  'ERP已发货',
	'ERP_CANCELED_FAIL' => 'WMS已取消ERP取消失败',
	'FAIL' =>  'ERP订单状态异常',
);


$giessen_order_status_field = "
	if(o.order_status=1 and bgco.cancel_info in ('T','F02'),'ERP_CANCELED_FAIL',
		if(o.order_status=0,'ERP_WAIT_CONFIRMED',
			if(o.shipping_status=15,'ERP_WAIT_RESERVED',
				if(bgo.Interface_Record_ID is null or bgo.transfer_status='NORMAL','ERP_WAIT_SUBMITTED',
					if(bgc.ShipmentID is null and bgo.transfer_status='SENDED','ERP_SUBMITTED',
						if(bgc.transfer_status='WMS_SHIP','WMS_SHIP',
							if(bgc.transfer_status='ERP_SHIP' and bgo.transfer_status='ERP_SHIP','ERP_SHIP','FAIL')
						)
					)
				)
			)
		) 
	)";
$order_id = mysql_real_escape_string(trim($_REQUEST['order_id']));
$order_sn = mysql_real_escape_string(trim($_REQUEST['order_sn']));
$taobao_order_sn = mysql_real_escape_string(trim($_REQUEST['taobao_order_sn']));
$end_order_time = mysql_real_escape_string(trim($_REQUEST['end_order_time']));
$start_order_time = ($_REQUEST['start_order_time']!='')?trim($_REQUEST['start_order_time']):
		(($end_order_time!='')?date("Y-m-d H:i:s",strtotime("-10 days",strtotime($end_order_time))):date("Y-m-d H:i:s",strtotime("-10 days",time())));	

$giessen_order_status = $_REQUEST['giessen_order_status'];
$start_apply_time = mysql_real_escape_string(trim($_REQUEST['start_apply_time']));
$end_apply_time = mysql_real_escape_string(trim($_REQUEST['end_apply_time']));

$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):'';

$size = 20;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;
if ($_REQUEST['csv'] == null) {
    $limit = " LIMIT $size";
    $offset = " OFFSET $start";
}

if($_REQUEST['request'] == 'search' || $_REQUEST['request'] == 'download'){
	$condition = get_condition();
	
	$sql = "
		select
			o.order_id,
			o.order_sn,
			ifnull(o.taobao_order_sn,'') taobao_order_sn,
			o.order_time,
			ifnull(oa.attr_value,'') as wangwang,
			o.tel,
			o.mobile,
			o.consignee as receiver_name,
			ifnull(bgc.transfer_note,'') note,
			ifnull(d.name,'') distributor_name,
			{$giessen_order_status_field} giessen_order_status,
			if(bgo.transfer_status!='NORMAL' and bgo.transfer_status!='FAIL',bgo.created_time,'')  apply_time,
			bgc.created_time wms_send_time,
			if(o.shipping_time=0,'',FROM_UNIXTIME(o.shipping_time, '%Y-%m-%d %H:%i:%S')) erp_send_time
		from
			ecshop.ecs_order_info o
			left join ecshop.brand_gymboree_sales_order_info bgo on o.order_id=bgo.ShipmentID
			left join ecshop.brand_gymboree_sales_order_confirm  bgc on o.order_id=bgc.ShipmentID
			left join ecshop.brand_gymboree_cancel_order bgco on o.order_id = bgco.erp_order_id
			left join ecshop.distributor d on o.distributor_id=d.distributor_id
			left join ecshop.order_attribute oa on o.order_id=oa.order_id and oa.attr_name = 'taobao_user_id'
		where
			o.party_id='65574' and o.order_type_id='SALE' and o.order_status<>2 and o.pay_status=2 {$condition} and o.facility_id='147470808'
	
	";
	if($_REQUEST['request'] == 'download') {
		
		$taobao_order_list = $db->getAll($sql);
		$title = array(0=>array('订单id','订单号','卖家销售订单号','店铺名称','订单状态',
								'下单时间','ERP申请发货时间','WMS发货时间','ERP发货时间','备注信息',
								'收件人','旺旺','固定电话','移动电话'	));
		$data = array();
		foreach($taobao_order_list as $v) {
			$order_status = $v['giessen_order_status'];
			if(isset($giessen_order_status_list[$v['giessen_order_status']])) {
				$order_status = $giessen_order_status_list[$v['giessen_order_status']];
			}
			$data[] = array($v['order_id'],$v['order_sn'],$v['taobao_order_sn'],$v['distributor_name'],$order_status,
							$v['order_time'],$v['apply_time'],$v['wms_send_time'],$v['erp_send_time'],$v['note'],
							$v['receiver_name'],$v['wangwang'],$v['tel'],$v['mobile'] );
		}
		
		$file_name = '金宝贝销售订单同步.xlsx';
		$type = array();
		for($i=0;$i<count($data[0]);$i++) {
			$type[] = 'string';
		}
		excel_export_model($title,$file_name,$data,$type,'金宝贝销售订单同步');


		exit();
		
		
	} else {
		$sql1 = $sql.$limit.$offset;
		$taobao_order_list = $db->getAll($sql1);
		$sql2 = "SELECT COUNT(*) FROM(".$sql.") as countItems";
		$count = $db->getOne($sql2);
		$pager = setPager($count, $size, $page,"gymboree_order_list.php?request=search".getReqString());
		
		$smarty->assign('request',$_REQUEST['request']);
		$smarty->assign('pager', $pager);
		$smarty->assign('taobao_order_list',$taobao_order_list);
	}

} 
$smarty->assign('giessen_order_status_list',$giessen_order_status_list);
$smarty->assign('distributor_list',get_distributor_list());
$smarty->assign('start_order_time',$start_order_time);
$smarty->assign('end_order_time',$end_order_time);
$smarty->assign('start_apply_time',$start_apply_time);
$smarty->assign('end_apply_time',$end_apply_time);
$smarty->assign('order_id',$order_id);
$smarty->assign('order_sn',$order_sn);
$smarty->assign('taobao_order_sn',$taobao_order_sn);



$smarty->display("oukooext/gymboree_order_list.htm");


function get_distributor_list() {
	global $db;
	$sql = "select distributor_id,`name` from ecshop.distributor where party_id=65574";
	$result = $db->getAll($sql);
	
	$distributors = array();
	foreach($result as $v) {
		$distributors[$v["distributor_id"]] = $v["name"]; 
	}
	
	return 	$distributors;
	
}


/**
 * 获得条件
 *
 */
function get_condition(){
	global $db;
	global $giessen_order_status_field;
	extract($_REQUEST);
	$condition = "";
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
        $condition .= " AND o.taobao_order_sn like '{$taobao_order_sn}%'";
    }
    
	if (isset($_REQUEST['distributor_id']) && trim($_REQUEST['distributor_id']) != '' && trim($_REQUEST['distributor_id'])!='ALL') {
		$distributor_id = mysql_real_escape_string(trim($_REQUEST['distributor_id']));
        $condition .= " AND o.distributor_id ='{$distributor_id}'";
    }
    
    if (isset($_REQUEST['start_order_time']) && trim($_REQUEST['start_order_time']) != '') {
		$start_order_time = mysql_real_escape_string(trim($_REQUEST['start_order_time']));
		$condition .= " AND o.order_time >= '{$start_order_time}'";
    }else if (isset($_REQUEST['end_order_time']) && trim($_REQUEST['end_order_time']) != '') {
		$end_order_time = mysql_real_escape_string(trim($_REQUEST['end_order_time']));
        $start_order_time = date("Y-m-d H:i:s",strtotime("-10 days",strtotime($end_order_time)));
        $condition .= " AND o.order_time >= '{$start_order_time}' AND o.order_time < '{$end_order_time}'";
    }else{
    	$start_order_time = date("Y-m-d H:i:s",strtotime("-10 days",time()));
    	$condition .= " AND o.order_time >= '{$start_order_time}'";
    }
	if ( isset($_REQUEST['giessen_order_status']) && $_REQUEST['giessen_order_status'] != 'ALL' ) {
			$giessen_order_status = $_REQUEST['giessen_order_status'];
			$condition .= " and {$giessen_order_status_field} = '{$giessen_order_status}'";
	}
	if ( isset($_REQUEST['start_apply_time']) && $_REQUEST['start_apply_time'] != '' ) {
			$start_apply_time = mysql_real_escape_string(trim($_REQUEST['start_apply_time']));
        	$condition .= " AND bgc.created_time >= '{$start_apply_time}'";
	}
	if ( isset($_REQUEST['end_apply_time']) && $_REQUEST['end_apply_time'] != '' ) {
			$end_apply_time = mysql_real_escape_string(trim($_REQUEST['end_apply_time']));
        	$condition .= " AND bgc.created_time < '{$end_apply_time}'";
	}

	return $condition;
}

/**
 * 获取参数字符串
 */
function getReqString() {
	$req = "";
	if (isset($_REQUEST['order_id']) && trim($_REQUEST['order_id']) != '') {
		$order_id = mysql_real_escape_string(trim($_REQUEST['order_id']));
        $req .= "&order_id={$order_id}";
    }
    
	if (isset($_REQUEST['order_sn']) && trim($_REQUEST['order_sn']) != '') {
		$order_sn = mysql_real_escape_string(trim($_REQUEST['order_sn']));
        $req .= "&order_sn={$order_sn}";
    }
    
	if (isset($_REQUEST['taobao_order_sn']) && trim($_REQUEST['taobao_order_sn']) != '') {
		$taobao_order_sn = mysql_real_escape_string(trim($_REQUEST['taobao_order_sn']));
        $req .= "&taobao_order_sn={$taobao_order_sn}";
    }
    
	if (isset($_REQUEST['distributor_id']) && trim($_REQUEST['distributor_id']) != '' && trim($_REQUEST['distributor_id'])!='ALL') {
		$distributor_id = mysql_real_escape_string(trim($_REQUEST['distributor_id']));
        $req .= "&distributor_id={$distributor_id}";
    }
    
    if (isset($_REQUEST['start_order_time']) && trim($_REQUEST['start_order_time']) != '') {
		$start_order_time = mysql_real_escape_string(trim($_REQUEST['start_order_time']));
		$req .= "&start_order_time={$start_order_time}";
    }else if (isset($_REQUEST['end_order_time']) && trim($_REQUEST['end_order_time']) != '') {
    	$end_order_time = mysql_real_escape_string(trim($_REQUEST['end_order_time']));
        $start_order_time = date("Y-m-d H:i:s",strtotime("-10 days",strtotime($end_order_time)));
        $req .= "&end_order_time={$end_order_time}&start_order_time={$start_order_time}";
    }else{
    	$start_order_time = date("Y-m-d H:i:s",strtotime("-10 days",time()));
    	$req .= "&start_order_time={$start_order_time}";
    }	
	if ( isset($_REQUEST['start_apply_time']) && $_REQUEST['start_apply_time'] != '' ) {
		$start_apply_time = mysql_real_escape_string(trim($_REQUEST['start_apply_time']));
        $req .= "&start_apply_time={$start_apply_time}";
	}
	if ( isset($_REQUEST['end_apply_time']) && $_REQUEST['end_apply_time'] != '' ) {
		$end_apply_time = mysql_real_escape_string(trim($_REQUEST['end_apply_time']));
        $req .= "&end_apply_time={$end_apply_time}";
	}
	if (isset($_REQUEST['giessen_order_status']) && $_REQUEST['giessen_order_status'] != 'ALL' ) {
		$giessen_order_status = $_REQUEST['giessen_order_status'];
		$req .= "&giessen_order_status={$giessen_order_status}";
	}
	return $req;
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
            if ($page == $i) {
                $ppp .= $i;
            } else {
                $ppp .= ' <a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'" class="Pager">['.$i.']</a> ';
            }
        }
    }
    $ppp .= ' <a href="'.$url.$label.'='.$pages.'" target="" title="Last Page '.$pages.'" class="Pager">[尾页]</a>';
    $ppp .= ' <input type="text" class="pagerInput" name="page" value="'.$page.'" size="5" onFocus="this.select();" onBlur="if(this.value != '.$page.' && this.value >= 1 && this.value <= '.$pages.'){location.href=\''.$url.$label.'=\' + this.value;}else{this.value = '.$page.';}" title=" 跳转 ">';
    $ppp .= ' ( 页数/记录数 :  '.$pages.'/'.$total.')';
    return $ppp;
}
?>