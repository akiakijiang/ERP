<?php
/*
 * Created on 2014-10-13
 * 回赠白名单管理
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
define('IN_ECS', true);
require_once('../includes/init.php');
// 添加权限
admin_priv('gifts_manage');
 
require_once(ROOT_PATH . 'admin/distribution.inc.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . "admin/function.php");
require_once (ROOT_PATH . 'includes/helper/uploader.php');

global $db;

$tpl = 
array ('白名单用户列表' => 
         array ('nick' => 'nick', 
                'num' => 'num', 
            ) );

$act = $_REQUEST['act'];

if($act == 'insert'){
	do {
		/* 文件上传并读取 */
		@set_time_limit ( 300 );
		$uploader = new Helper_Uploader ();
		$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
		
		$whitelist_id =  isset($_REQUEST['whitelist_id']) ? intval($_REQUEST['whitelist_id']) : intval($_POST['hidden_group_goods_id']);
		
		$rowset = null;
		if ($uploader->existsFile ( 'excel' )) {
			// 取得要上传的文件句柄
			$file = $uploader->file ( 'excel' );
			// 检查上传文件
			if (! $file->isValid ( 'xls, xlsx', $max_size )) {
				$smarty->assign ( 'message', '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB' );
				break;
			}
			// 读取excel
			$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
			
			if (! empty ( $failed )) {
				$smarty->assign ( 'message', reset ( $failed ) );
				break;
			}
			
			//插入用户列表
			$rowset = $result ['白名单用户列表'];
		} else if ($whitelist_id == 0){
			$smarty->assign ( 'message', '没有选择上传文件，或者文件上传失败' );
			break;
		}
		
	    $start_time=$_REQUEST['start'];
	 	$end_time=$_REQUEST['end'];
	 	
	 	$reqdata = new stdClass();
	    $reqdata -> whitelist_id = $whitelist_id;
	    $reqdata -> whitelist_name = $active_name = $_POST['active_name'];
	    $reqdata -> tc_code = $_POST['hid_child_tc_code'];
	    $reqdata -> goods_id = intval($_POST['hid_child_goods_id']);
	    $reqdata -> style_id = intval($_POST['hid_child_style_id']);
	    $reqdata -> name = $_POST['child_goods'] ; 
	    $reqdata -> quantity = intval($_POST['child_goods_amount']);
	    $reqdata -> start_time = $start_time;
		$reqdata -> end_time = $end_time;
		$reqdata -> created_by =  $_SESSION['admin_name']; 
		$reqdata -> detail = $rowset;
	    
	    $result = _gifts_update($reqdata); 
	    
	    if($result) {
	    	$smarty->assign ( 'message', '添加成功！' );
	    } else {
	    	$smarty->assign ( 'message', '添加失败！' );
	    }
	    
	} while(false);
    	
} else if($act == "download_list_template") {
	
	$title = array(0=>array('nick','num'));
	$file_name = "批量导入白名单用户列表";
	$data = array();
	$type = array();
	$sheetname = "白名单用户列表";
	excel_export_model($title,$file_name,$data,$type,$sheetname);
	exit();
	
} else if ($act == 'delete'){
 	$whitelist_id = intval($_REQUEST['whitelist_id']);
 	$result = _delete_gifts_by_id($whitelist_id);
 	
 	if($result){
 		$tip = "白名单已经删除了 ，，，";
    	$smarty->assign('tip', $tip);
    }else{
    	$tip = "白名单删除失败--! ，，，";
    	$smarty->assign('tip', $tip);
    }
 } else if ($act == 'update'){
 	$whitelist_id = intval($_REQUEST['whitelist_id']);
 	$gifts = _get_gifts_by_id($whitelist_id); 
 	$users = _get_users_by_whitelist_id($whitelist_id);	
 	$sql = "select s.style_id,s.color " .
 			"from ecshop.ecs_goods_style gs inner join ecshop.ecs_style s on gs.style_id=s.style_id  " .
 			"where gs.goods_id= '{$gifts['parent_id']}' and gs.is_delete=0";
 	$style_list = $db -> getAll($sql);
 	$style_list = Helper_Array::toHashmap($style_list,'style_id','color');
 	$smarty->assign('style_list',$style_list);
 	$smarty->assign('view_only',$_REQUEST['view_only']);
 	//同页跳转定位用
 	$smarty->assign('position',$_REQUEST['position']);
 	$smarty->assign('show',$_REQUEST['show']);
 	$smarty->assign('gifts', $gifts);
 	$smarty->assign('users', $users);
 } else if($act == "delete_detail") {
 	$json = new JSON;
 	$detail_id = intval($_REQUEST['detail_id']);
 	$result = _delete_detail_by_id($detail_id);
 	if ($result) {
 		print $json->encode($result);
 	} else{
 		print $json->encode(array('error' => '删除过程出错'));
 	}
 	exit();
 }
 
 
  /**
  * 删除writelist_detail
  */
 function _delete_detail_by_id($detail_id = null){
 	
 	
 	if(empty($detail_id)){
 		return false;
 	}
 	global $db;
 	
// 	$sql = "delete from ecshop.ecs_group_gift_goods where group_goods_id = %d ";
    $sql = "update ecshop.ecs_whitelist_gift_goods_detail set status = 'DELETED', deleted_by = '{$_SESSION['admin_name']}' where detail_id = %d ";
    
    $result = $db->query(sprintf($sql, $detail_id));
    
	$sql = sprintf($sql, $detail_id);
	addGiftAction($sql,$detail_id);
    
    return $result;
 			
 }
 
  /**
  * 删除writelist
  */
 function _delete_gifts_by_id($whitelist_id = null){
 	
 	
 	if(empty($whitelist_id)){
 		return false;
 	}
 	global $db;
 	
// 	$sql = "delete from ecshop.ecs_group_gift_goods where group_goods_id = %d ";
    $sql = "update ecshop.ecs_whitelist_gift_goods set status = 'DELETED', deleted_by = '{$_SESSION['admin_name']}' where whitelist_id = %d ";
   
    
    $result = $db->query(sprintf($sql, $whitelist_id));
	$sql = sprintf($sql, $whitelist_id);
	addGiftAction($sql,$whitelist_id);
    
    return $result;
 			
 }
 
 function _get_users_by_whitelist_id($whitelist_id = null) {
 	if(empty($whitelist_id)){
 		return null;
 	}
 	global $db;
 	$sql = "select * from ecshop.ecs_whitelist_gift_goods_detail where whitelist_id={$whitelist_id} and status='OK'";
 	$result = $db->getAll($sql);
 	return $result;
 }

 /**
  * 更新表
  */
 function _gifts_update($request = null){
 	if(empty($request)){
 		return false;
 	}
    $goods_party_id = intval($_SESSION['party_id']);
	
	global $db;
	if(empty($request->whitelist_id)) {
		//insert
		$sql = "insert into ecs_whitelist_gift_goods 
			   (whitelist_id,whitelist_name,tc_code,goods_id,style_id,name,quantity,start_time,end_time,status,
			    party_id,created_by,created_time,update_time) 
				values (%d,'%s','%s',%d,%d,'%s',%d,'%s','%s','OK',{$goods_party_id},'%s',now(),now())";
		$sql = sprintf($sql,$request->whitelist_id,$request->whitelist_name,$request->tc_code,$request->goods_id,$request->style_id,
							$request->name,$request->quantity,$request->start_time,$request->end_time,$request->created_by);
		$result = $db->query($sql);
		$whitelist_id = $db->insert_id();
	} else {
		//update
		$sql = "update ecs_whitelist_gift_goods set whitelist_name='%s', tc_code='%s', goods_id=%d,
				style_id=%d, name='%s', quantity=%d, start_time='%s',end_time='%s'
		        where whitelist_id=%d ";
		$sql = sprintf($sql,$request->whitelist_name,$request->tc_code,$request->goods_id,
					   $request->style_id,$request->name,$request->quantity,$request->start_time,$request->end_time,$request->whitelist_id);
		$result = $db->query($sql);
		$whitelist_id = $request->whitelist_id;
	}
	
	if(!empty($whitelist_id) && !empty($request->detail)) {
		foreach($request->detail as $detail) {
			$sql = "insert into ecs_whitelist_gift_goods_detail (whitelist_id,nick,num,status,created_by,create_time,update_time)
						values (%d,'%s',%d,'OK','%s',now(),now()) ";
			$sql = sprintf($sql,$whitelist_id,trim($detail['nick']),trim($detail['num']),$request->created_by);
			$result = $db->query($sql);
		}
	}
	
    return $result;
 }
 
 
 /**
 * 检查商品
 */
 function _check_goods_party($goods_id, $goods_party_id){
 	if(empty($goods_party_id)){
 		$goods_party_id = intval($_SESSION['party_id']);
 	}
 	 
 	if(empty($goods_id)){
 		return false ;
 	}
 	global $db;
 	 
 	$sql = "select count(*) as cnts from ecshop.ecs_goods where goods_id = %d and goods_party_id = %d ";
 	$result = $db->getRow(sprintf($sql, $goods_id, $goods_party_id));

 	if(intval($result['cnts']) > 0){
 		return true;
 	}else{
 		return false;
 	}

 }
 
 /**
  * 检查套餐
  */
  function _check_taocan_party($tc_code, $goods_party_id){
  	 if(empty($goods_party_id)){
  	 	$goods_party_id = intval($_SESSION['party_id']);
  	 }
  	 if(empty($tc_code)){
  	 	return false ;
  	 }
  	 global $db;
  	 
  	 $sql = "select count(*) as cnts from ecshop.distribution_group_goods where code = '%s' and party_id = %d ";
  	   	 
  	 $result = $db->getRow(sprintf($sql, $tc_code, $goods_party_id));
  	
  	 if(intval($result['cnts']) > 0){
  	 	return true;
  	 }else{
  	 	return false;
  	 }
  	
  }
  
  
  function _get_all_gifts_V2(){
  	global $db;
  	$party_id = intval($_SESSION['party_id']);

  	$sql = "
 	   	select 
 	   		wg.whitelist_id,
			wg.whitelist_name,
			wg.start_time,
			wg.end_time,
			wg.created_by, wg.update_time,
			if(now() > wg.start_time, 'N', 'Y') as can_update
		from 
			ecshop.ecs_whitelist_gift_goods wg
			left join ecshop.ecs_goods g on wg.goods_id = g.goods_id
		 	left join ecshop.distribution_group_goods dg on wg.tc_code=dg.code
		where (g.goods_party_id = {$party_id} or dg.party_id={$party_id}) and wg.status='OK';";

  	$result=$db->getAll($sql);
  	return $result;
  }
  
  /**
   * 根据ID查找
   */
  function _get_gifts_by_id($whitelist_id = null){
  	if(empty($whitelist_id)){
  		return null;
  	}
  	global $db;

  	$sql = "
		   select
				wg.whitelist_id,
				wg.goods_id,
				wg.style_id,
				wg.whitelist_name,
				concat_ws('',if(dg1.code is null,concat_ws(' ', cg.goods_name, if(cgs.goods_color = '', cs.color, cgs.goods_color)),dg1.name),'(',if(dg1.code is null, concat_ws('',cg.goods_id,'_',ifnull(cgs.style_id,0)), dg1.code),')' )  as child_goods_name,
				ifnull(dg1.code,'') tc_code,
				wg.quantity,
				wg.start_time, wg.end_time
				
           from ecshop.ecs_whitelist_gift_goods wg
                left join ecshop.ecs_goods cg on wg.goods_id = cg.goods_id
                left join ecshop.ecs_goods_style cgs on wg.goods_id = cgs.goods_id and wg.style_id = cgs.style_id and cgs.is_delete=0
                left join ecshop.ecs_style cs on wg.style_id = cs.style_id
                left join ecshop.distribution_group_goods dg1 on wg.tc_code = dg1.code
           where wg.whitelist_id = %d and wg.status = 'OK'
        ";

  	$result = $db->getRow(sprintf($sql, $whitelist_id));
 	 return $result;
  }
  
    //增加赠品操作记录
  function addGiftAction($sqls,$whitelist_id){
  	global $db;
  	if (empty($sqls)) {
        return;
    }
    if (is_array($sqls)) {
        $sql = mysql_real_escape_string(join(";", $sqls));
    } else {
        $sql = mysql_real_escape_string($sqls);
    }
    $time = date("Y-m-d H:i:s");
    $user_name = $_SESSION['admin_name'];
    if (empty($user_name)) {
    	$user_name = 'system';
    }
    $excute = "insert into ecshop.ecs_whitelist_gift_goods_action 
    		   (`whitelist_id`,`sql`,`user`,`time`)
    		   values
    		   ('{$whitelist_id}','{$sql}','{$user_name}','{$time}')";
    $db -> query($excute);
  }

$smarty->assign('gifts_list_V2',_get_all_gifts_V2());
$smarty->display('gifts_manage/whitelist_gifts_manage.htm');
 
 

 