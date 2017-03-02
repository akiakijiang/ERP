<?php
 define('IN_ECS', true);

 require('../includes/init.php');
 require_once('../function.php');
 require_once(ROOT_PATH . 'includes/cls_json.php');
 require_once(ROOT_PATH . 'includes/lib_order.php');
 require_once(ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
 require_once(ROOT_PATH . 'includes/helper/array.php');
 require_once (ROOT_PATH . 'includes/debug/lib_log.php');
 admin_priv( 'sale_support_group_manage' );
 
 $request = //请求
     isset($_REQUEST['request']) &&
     in_array($_REQUEST['request'], array('ajax'))
     ? $_REQUEST['request']
     : null ;
 $act =     // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('add_member', 'add', 'update', 'search', 'check_unique')) 
    ? $_REQUEST['act'] 
    : null ;
$info =    // 返回的信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ;
$keyword = // 查询关键字
    isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']) 
    ? urldecode($_REQUEST['keyword']) 
    : '' ;

 if ($request == 'ajax')
 {
 	$json = new JSON();
 	switch ($act)
 	{
 		case 'add_member':
 			$sql = "select user_id,user_name,real_name from ecshop.ecs_admin_user where user_id = '{$_REQUEST['user_id']}' and status = 'OK'";
 			$members = $db -> getRow($sql);
 			if ($members){
 			   print $json->encode($members);
 			}else{ 
 			   print $json->encode(array('error' => '该成员不存在'));
 			}
 		break;
 			   
 		case 'check_unique':
 			$group_name = trim($_POST['group_name']);
 			$pre_group_name = trim($_POST['pre_group_name']);
 			$result = check_unique($group_name,$pre_group_name);
 			if($result){
 			   print $json -> encode(array('tip' => '该小组名不可使用','exist' => 'YES'));
 			}else{
 			   print $json -> encode(array('tip' => '该小组名可以使用','exist' => 'NO'));
 			}
 		break;
 	}
 	
 	exit;
 }
 
 /*
 * 处理post请求
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && $act) {
	
	switch ($act) {
		/* 生成小组 */
	    case 'add' :
	    	//检查是否选择了相关人员
	    	if(empty($_POST['members']) || !is_array($_POST['members'])) {
	    		$smarty->assign('message', '没有选择相关人员');
	    		break;
	    	}

	    	//先处理小组
	    	$now = date("Y-m-d H:i:s");
	    	$group = new stdClass();
	    	  $group -> group_name = trim($_POST['group_name']);
	    	  $group -> created_stamp = $now;
	    	  $group -> update_stamp = $now;
	    	  $group -> deleted_stamp = $now;
	    	  $group -> created_by = $_SESSION['admin_name'];
	    	  $group -> status = 'OK';
	    	
	    	$members_list = array();
	    	//检测下小组成员
			foreach($_POST['members'] as $member) {
	    		if(empty($member['user_id']) || empty($member['member_name'])){
	    			$smarty->assign('message','小组成员的信息不对');
	    			break;
	    		}
	    	}
	    	
	    	//存储小组
	    	$sql = "
	    	    insert into ecshop.sale_support_group (group_name, created_stamp, update_stamp, created_by, status) values
	    	    ('{$group -> group_name}', '{$group -> created_stamp}', '{$group -> update_stamp}', '{$group -> created_by}', '{$group -> status}')";
	    	$result = $db -> query($sql);
	    	$group_id = $db -> insert_id();
	    	
	    	// 保存小组成员
	    	$segment = array();
	    	foreach ($_POST['members'] as $member) {
	    		$segment[] = "('{$group_id}','{$member['user_id']}','{$member['user_name']}','{$member['real_name']}','{$now}','OK')";
	    	}
	    	
	    	$sql = "insert into ecshop.sale_support_group_member (group_id, user_id, member_name, real_name, created_stamp, status) values ". join(', ',$segment) ;
	    	$result = $db -> query($sql);
	    	
	    	header("Location: sale_support_group_manage.php?info=".urlencode('添加小组成功')); exit;
	    
	    break;
	    
	     /* 编辑小组 */
        case 'update' :
	    	$now = date("Y-m-d H:i:s");
	    	$group_id = $_POST['group_id'];
	    	$group_name = trim($_POST['group_name']);
	    	
	    	//检查是否选择了小组成员
	    	if(empty($_POST['members']) || !is_array($_POST['members'])){
	    		$smarty->assign('message','没有选择小组成员');
	    		break;
	    	}
	    	
	    	$segment = array();
	    	foreach($_POST['members'] as $member) {
	    		if(empty($member['user_id']) || empty($member['user_name'])){
	    			$smarty->assign('message','小组成员的信息不对');
	    			break;
	    		}
	    		
	    		$segment[] = "('{$group_id}','{$member['user_id']}','{$member['user_name']}','{$member['real_name']}','{$now}','OK')";
	    	}
	    	
	    	// 检测提交的小组是否存在
	    	$result = get_group_members($_POST['group_id']);
	    	if(!$result) {
	    		$smarty->assign('message', '要编辑的小组不存在');
	    		break;
	    	}
	    	
	    	//更新小组
	    	$db->query("UPDATE ecshop.sale_support_group SET group_name = '{$group_name}',update_stamp = '{$now}' where group_id = '{$_POST['group_id']}'");
	    	
	    	//更新套餐明细
	    	$db->query("DELETE FROM ecshop.sale_support_group_member WHERE group_id = '{$_POST['group_id']}'");
	    	
	    	$sql = "insert into ecshop.sale_support_group_member (group_id, user_id, member_name, real_name, created_stamp, status) values ". join(', ',$segment) ;
	    	$result = $db -> query($sql);
            
            header("Location: sale_support_group_manage.php?info=".urlencode('更新小组成功')); exit;
                        
        break;
	}	

	
}

// 信息
if ($info) {
    $smarty->assign('message', $info);
}

// 编辑或者删除模式
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	if(empty($_GET['work'])){
	    $group = get_group_members($_GET['id']);
        if ($group) {
            $smarty->assign('update', $group);   
        } else {
            $smarty->assign('message', '选择的小组不存在');
        }
	}else if ($_GET['work']  == 'delete'){
		 $sql = "
		     update ecshop.sale_support_group 
		     set status = 'DELETE',deleted_stamp = NOW(),update_stamp = NOW(),deleted_by = '{$_SESSION['admin_name']}' 
			 where group_id = ".$_GET['id'];
		    
         $db->query($sql);            
            
         header("Location: sale_support_group_manage.php?info=". urlencode('小组删除成功')); exit;
	}
   
}

//查询条件
$conditions = null;
$extra_params = array();
$filter = array();
if($keyword) {
	$filter['keyword'] = $keyword;
	$extra_params['keyword'] = urlencode($keyword);
	$keyword = mysql_like_quote($keyword);
	$conditions = "inner join ecshop.sale_support_group_member gm on gm.group_id = g.group_id
				   where (g.group_id like '%{$keyword}%' or g.group_name like '%{$keyword}%' 
				          or gm.user_id like '%{$keyword}%' or gm.member_name like '%{$keyword}%' or gm.real_name like '%{$keyword}%')
				  ";
}

if($conditions == null){
	$sql = "select g.group_id,g.group_name,g.created_stamp,g.created_by from ecshop.sale_support_group g where g.status = 'OK' group by g.group_id";
	$group_list = $db->getAll($sql);
}else{
	$sql = "select g.group_id,g.group_name,g.created_stamp,g.created_by from ecshop.sale_support_group g {$conditions} and g.status = 'OK' group by g.group_id";
	$group_list = $db->getAll($sql);
}
 
$smarty->assign('group_list', $group_list);
$smarty->display('sale_support/sale_support_group_manage.htm');
 
/**
 * 取得小组及其明细
 * 
 * @param int $group_id 小组记录的主键
 */
function get_group_members($group_id)
{
    global $db, $ecs;
    
    if (is_null($group_id)){
    	sys_msg('小组编号为空。');
    }
    $group = $db->getRow("select * from ecshop.sale_support_group where group_id = ". intval($group_id), true);
   
    if ($group) {
        // 套餐商品类表
        $member_list = $db->getAll("
            select gm.*,u.status
			from ecshop.sale_support_group_member gm
			left join ecshop.ecs_admin_user u on u.user_id = gm.user_id
			where gm.group_id = '{$group_id}'
        ");
        
	    if($member_list){
	        $group['member_list'] = $member_list;	
	    }
    }
    
    return $group;
}

/**
 * 检测小组名的唯一性
 */
function check_unique($group_name, $pre_group_name = null){
	global $db;
	$conf = "";
	if(empty($group_name)){
		Sys_msg("小组名为空");
	}
	if(!empty($pre_group_name)){
		$conf = " and group_name <> '{$pre_group_name}'";
	}
	$sql = "select 1 from ecshop.sale_support_group where status = 'OK' and group_name = '{$group_name}'".$conf;
 	$result = $db -> getOne($sql);
 	
 	//不能与单个用户名相同
 	if(!$result){
 		$sql = "select 1 from ecshop.ecs_admin_user where status = 'OK' and user_name = '{$group_name}'";
 		$result = $db -> getOne($sql);
 	}
 	return $result;
}
 ?>