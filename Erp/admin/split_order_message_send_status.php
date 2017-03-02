<?php
/**
 * 合并发货查看
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
admin_priv('merge_order','order_edit');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');


// 分页
$page_size_list =
	array('20'=>'20','50'=>'50','100'=>'100');	

$act = isset($_REQUEST['act']) && !empty($_REQUEST['act'])
	? $_REQUEST['act']
	: 'show';
 
// 期初时间
$start = 
	isset($_REQUEST['start']) && !empty($_REQUEST['start']) && strtotime($_REQUEST['start'])!==false
	? $_REQUEST['start']
	: date('Y-m-d');

// 期末时间
$ended =
	isset($_REQUEST['ended']) && !empty($_REQUEST['ended']) && strtotime($_REQUEST['ended'])!==false
	? $_REQUEST['ended']
	: date('Y-m-d');

// 每页数据量
$page_size = 
    is_numeric($_REQUEST['size']) && in_array($_REQUEST['size'], $page_size_list)
    ? $_REQUEST['size']
    : 20;

// 页码
$page = 
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
if(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/",$start)){
	$start_datetime=$start.' 00:00:01';
}
else{
	$start_datetime=$start;
}
if(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/",$ended)){
	$ended_datetime=$ended.' 23:59:59';
}
else{
	$ended_datetime=$ended;
}
$msg_list = null; 
$total = 0; // 总记录数 
$order_sn = ''; 
// 查看短信发送列表 
if($act == 'search'){
	$order_sn =  isset($_REQUEST['order_sn']) && !empty($_REQUEST['order_sn'])  
	? $_REQUEST['order_sn']
	: "";
	$limit_offset = ($page - 1)*$page_size;
	$limit_size = $page_size; 
	$cond = ""; // 查询条件 
	if(!empty($order_sn)){ // order_sn 查询 
		$cond = " ( orr.root_order_sn = '{$order_sn}' or orr.order_sn = '{$order_sn}' ) "; 
	}else{ // 按时间查询 
		$party_id = $_SESSION['party_id']; 
		$cond = " oi.order_time >'{$start_datetime}' and oi.order_time <'{$ended_datetime}'  and oi.party_id = {$party_id} "; 
	}
	$sql = " SELECT oi.order_id 
			from ecshop.ecs_order_info oi
			INNER JOIN ecshop.order_relation orr ON orr.root_order_id = oi.order_id 
			AND orr.parent_order_sn != 'merge' 
			INNER JOIN ecshop.ecs_order_info oi2 ON oi2.order_id = orr.order_id AND oi2.order_status = 1 AND oi2.order_type_id ='SALE'
			where {$cond}
			AND oi.order_status = 2  GROUP BY oi.order_id limit $limit_offset,$limit_size ";
    $parent_order_ids = $db->getCol($sql);
    $this_total = count($parent_order_ids); 
    if($this_total < $page_size){
    	$total = $this_total; 
    }else{
    	$total = $db->getOne(" select count(1) from ( SELECT count(1)
			from ecshop.ecs_order_info oi
			INNER JOIN ecshop.order_relation orr ON orr.root_order_id = oi.order_id 
			AND orr.parent_order_sn != 'merge' 
			INNER JOIN ecshop.ecs_order_info oi2 ON oi2.order_id = orr.order_id AND oi2.order_status = 1 AND oi2.order_type_id ='SALE'
			where {$cond}
			AND oi.order_status = 2  GROUP BY oi.order_id ) as t "); 
    }
    // 根据 parent_order_id 获取订单的短信发送情况和拆分后的订单 
    if(!empty($parent_order_ids)){
    	$sql = " SELECT oi.order_id,oi.order_sn,oi.order_status,oi.shipping_status,
				       oi.mobile, oi.order_time , 
				       orr.parent_order_id , orr.parent_order_sn, 
				       GROUP_CONCAT(ot.attr_value) as attr_value 
				from ecshop.order_relation orr 
				INNER JOIN ecshop.ecs_order_info oi ON oi.order_id = orr.order_id 
				LEFT JOIN ecshop.order_attribute ot ON ot.order_id = orr.parent_order_id and ot.attr_name ='SPLIT_MSG_SEND' 
				WHERE  ".db_create_in($parent_order_ids,'orr.root_order_id ').
				" GROUP BY oi.order_id  "; 
		$order_info = $db->getAll($sql);
        $parent_child_order_info = array(); 
        
        // 组合订单数据  
        if(!empty($order_info)){
        	foreach ($order_info as $key => $order) {
        		$parent_order_id = $order['parent_order_id'];
        		if(isset($parent_child_order_info[$parent_order_id]['orders'])){
        			$parent_child_order_info[$parent_order_id]['orders'][] = $order; 
        		}else{
        			$parent_child_order_info[$parent_order_id]['order_sn'] = $order['parent_order_sn'];
        			$parent_child_order_info[$parent_order_id]['order_id'] = $order['parent_order_id'];
        			$parent_child_order_info[$parent_order_id]['order_time'] = $order['order_time']; 
        			$parent_child_order_info[$parent_order_id]['mobile'] = $order['mobile'];  
        			$parent_child_order_info[$parent_order_id]['orders'] = array();
        			$parent_child_order_info[$parent_order_id]['orders'][] = $order; 
        		}
        		// 短信发送标记 
        		$msg_send_flags = $order['attr_value'];  // 字符中含有 0 短信发送成功 全为1则 发送失败 
        		if(!isset($msg_send_flags)){
        			$parent_child_order_info[$parent_order_id]['msg_send'] = -1 ; 
        		}else{
        			if(strpos($msg_send_flags,'0') > -1 ){     // 短信发送成功 
        				$parent_child_order_info[$parent_order_id]['msg_send'] = 0 ; 
        			}else{
        				$parent_child_order_info[$parent_order_id]['msg_send'] = 1 ;
        			}
        		}

        	}
        } 
    }

}

//分页     @param $total 一共多少页   $page_size 一页显示多少行  $page 当前第几页
$Pager = Pager($total, $page_size, $page);
$page = ($page -1)*$page_size;
$smarty->assign("orders",$parent_child_order_info); 
$smarty->assign("page_size_list",$page_size_list);
$smarty->assign("Pager",$Pager);
$smarty->assign("start",$start);
$smarty->assign("ended",$ended);
$smarty->assign("order_sn",$order_sn); 


$smarty->display('split_order_message_send_status_list.htm');
