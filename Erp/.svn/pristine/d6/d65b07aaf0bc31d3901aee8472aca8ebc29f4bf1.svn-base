<?php 
/**
 * 淘宝外包发货管理
 * 
 */
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');


$request = // 请求 
    isset($_REQUEST['request']) ? trim($_REQUEST['request']) : null ;
$act =     // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('add', 'update','check')) 
    ? $_REQUEST['act'] 
    : null ;
$page = 
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
/*
 * 处理ajax请求
 */
 
if ($request == 'ajax')
{
    switch ($act) 
    {
       
        case 'check':
        	 $outer_id = $_REQUEST['outer_id'];
			 $create_user = $_SESSION['admin_name'];
             $update_user = $_SESSION['admin_name'];
             $party_id = $_SESSION['party_id'];
             $sql ="INSERT INTO tag_category(tag_category_name,party_id,creator,create_time,updater,update_time)
             VALUES('$outer_id',$party_id,'$create_user',NOW(),'$update_user',NOW())";
           
             $db->query($sql);
        break;
       
        case 'update':
             
        	 $outer_id = $_REQUEST['outer_id'];
        	 $old_name = $_REQUEST['old_name'];
        	 
             $update_user = $_SESSION['admin_name'];           
             $sql ="update tag_category set tag_category_name = '$old_name'" .
             ",updater='$update_user',update_time=NOW() WHERE tag_category_id='".$outer_id."'";            
             $db->query($sql);
            
        break;
    }
    	
    
    exit;
}


if (isset($_GET['id']) && is_numeric($_GET['id']) && 'findone'==$_GET['act']) {
	$update =  $db->getRow("SELECT * FROM ecshop.tag_category WHERE tag_category_id = ".$_GET['id']);
	$smarty->assign('tag_category_id', $_GET['id']);
  	$smarty->assign('update', $update);
}

//用于模板<a>删除记录
if (isset($_GET['id']) && isset($_GET['act']) && 'delete'==$_GET['act']) {
	
	$sql_delete = "delete from ecshop.tag_category where tag_category_id=".$_GET['id'];
	$sql_update = "update ecshop.tags set tag_category_id=0 where tag_category_id=".$_GET['id'];
	$db->query($sql_delete);
	$db->query($sql_update);
}

//获取列表
function get_group_list($offset=0,$limit=0) {
	global  $db;
	$party_id= $_SESSION['party_id'];
	$sql = "SELECT tag_category_id,tag_category_name,name,creator,create_time,updater,update_time " .
			"from ecshop.tag_category tc, romeo.party p " .
			"WHERE tc.party_id = p.PARTY_ID AND tc.party_id=".$party_id." limit ".$offset.",".$limit;	
	//select ecshop.tags.tag_name,romeo.party.name from ecshop.tags, romeo.party WHERE ecshop.tags.party_id =  romeo.party.PARTY_ID
	$group_list = $db->getAll($sql);
	return $db->getAll($sql);
}

function get_group_total() {
	global  $db;
    $party_id= $_SESSION['party_id'];
	$sql = "select count(*) from ecshop.tag_category where party_id=".$party_id ;
	return $db->getOne($sql);
}


//总记录数
$total = get_group_total();
// 分页 
$page_size = 30;  // 每页数量
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size;
//商品列表

$group_list = get_group_list($offset,$limit);
$pagination = new Pagination($total, $page_size, $page, 'page', $url = 'tag_category.php', null);
$smarty->assign('pagination', $pagination->get_simple_output());  
$smarty->assign('group_list', $group_list);  
$smarty->display("taobao/tag_category.htm");
?>