<?php
/*
 * Created on 2011-7-14
 * 赠品管理
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
 define('IN_ECS', true);
 require_once('../includes/init.php');
 // 添加权限
 admin_priv('gifts_manage');
 
 require_once(ROOT_PATH . 'admin/distribution.inc.php');
 require_once(ROOT_PATH . 'includes/cls_json.php');
 
 // 请求
 $request = isset($_REQUEST['request']) && 
    in_array($_REQUEST['request'], array('ajax')) 
    ? $_REQUEST['request'] 
    : null;
 $act = isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('search_goods', 'update', 'delete', 'insert')) 
    ? $_REQUEST['act'] 
    : null;
    
      
 if ($request == 'ajax'){
    $json = new JSON;
 	
    switch($act){
 		// 搜索商品
        case 'search_goods':
            $limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 40 ;
            print $json->encode(distribution_get_goods_list(null, null, $_POST['q'], $limit));  
        break;
 		
 	}
 	
 	exit;
 	
 }else if($_POST['act'] == 'insert'){
 
    $reqdata = new stdClass();
        $reqdata -> group_goods_id = intval($_POST['hidden_group_goods_id']);
        $reqdata -> parent_id = intval($_POST['hid_main_goods_id']) ;
        $reqdata -> parent_style_id = intval($_POST['hid_main_style_id']) ;
        $reqdata -> parent_cat_id = intval($_POST['hid_main_cat_id']) ;
        $reqdata -> goods_id = intval($_POST['hid_child_goods_id']) ;
        $reqdata -> style_id = intval($_POST['hid_child_style_id']) ;
        $reqdata -> name = $_POST['child_goods'] ; 
    
        $reqdata -> created_by = $_SESSION['admin_name'];    
        $reqdata -> goods_party_id = $_SESSION['party_id'];  
    
    // 更新表记录
    $result = _gifts_update($reqdata); 
    if($result){
    	$smarty->assign('message', '赠品 '.$reqdata -> name.' 已经添加好了 ，，，');
    }else{
    	$smarty->assign('message', '赠品 '.$reqdata -> name.' 添加失败--! ，，，');
    }
 }else if ($act == 'delete'){
 	$group_goods_id = intval($_REQUEST['group_goods_id']);
 	
 	$result = _delete_gifts_by_id($group_goods_id);
 	
 	if($result){
    	$smarty->assign('message', '赠品已经删除了 ，，，');
    }else{
    	$smarty->assign('message', '赠品删除失败--! ，，，');
    }
 }else if ($act == 'update'){
 	$group_goods_id = intval($_REQUEST['group_goods_id']);
 	
 	$gifts = _get_gifts_by_id($group_goods_id);
 }
 
 
 $smarty->assign('gifts', $gifts);
 
 
 $gifts_list = _get_all_gifts();
 $smarty->assign('gifts_list', $gifts_list);
 $smarty->display('gifts_manage/old_gifts_manage.htm');
 
 
 /**
  * 更新表
  */
 function _gifts_update($request = null){
 	
 	if(empty($request)){
 		return false;
 	}
    
    $goods_party_id = intval($_SESSION['party_id']);
 		// 检查主商品
 	if(!_check_goods_party($request -> parent_id, $goods_party_id)){
 		return false; 
 	}
 	// 检查赠品
 	if(!_check_goods_party($request -> goods_id, $goods_party_id)){
 		return false; 
 	}
 		
    global $db;
    
 	if(empty($request->group_goods_id)){
 		// insert
 		$sql = "insert into ecshop.ecs_group_goods (parent_id, parent_style_id, parent_cat_id, goods_id, style_id, created_by, created_datetime, name) 
 				  values (%d, %d, %d, %d, %d, '%s', NOW(), '%s') ";
        $result=$db->query(sprintf($sql,  $request -> parent_id, $request -> parent_style_id, $request -> parent_cat_id
                                           , $request -> goods_id, $request -> style_id, $request -> created_by, $request -> name));
 		
 		return $result ;
 	}else{
 		// update
 		$sql = "update ecshop.ecs_group_goods
 				       set parent_id = %d, parent_style_id = %d, parent_cat_id = %d, goods_id = %d, style_id = %d
 				         , created_by = '%s', created_datetime = NOW(), name = '%s'
 			      where group_goods_id = %d " ; 
 				  
        $result=$db->query(sprintf($sql, $request -> parent_id, $request -> parent_style_id, $request -> parent_cat_id
                                        , $request -> goods_id, $request -> style_id, $request -> created_by, $request -> name
                                        , $request -> group_goods_id));
                                      
        return $result ;
 	}
 
 }
 
 /**
  * 查询
  */
 function _get_all_gifts(){
 	global $db;
 	$party_id = intval($_SESSION['party_id']);
 	
 	$sql = "
      select gg.group_goods_id,
            concat_ws('#', gg.parent_id, if(gg.parent_style_id=0, null, gg.parent_style_id)) as parent_goods_style,  
            concat_ws(' ', pg.goods_name, if(pgs.goods_color = '', ps.color, pgs.goods_color)) as parent_goods_name,
            concat_ws('#', gg.goods_id, if(gg.style_id=0, null, gg.style_id)) as child_goods_style, 
            concat_ws(' ', cg.goods_name, if(cgs.goods_color = '', cs.color, cgs.goods_color)) as child_goods_name,
            gg.created_by, gg.created_datetime
      from ecshop.ecs_group_goods gg
           left join ecshop.ecs_goods pg on gg.parent_id = pg.goods_id
           left join ecshop.ecs_goods_style pgs on gg.parent_id = pgs.goods_id and gg.parent_style_id = pgs.style_id and pgs.is_delete=0
           left join ecshop.ecs_style ps on gg.parent_style_id = ps.style_id
           left join ecshop.ecs_goods cg on gg.goods_id = cg.goods_id
           left join ecshop.ecs_goods_style cgs on gg.goods_id = cgs.goods_id and gg.style_id = cgs.style_id and cgs.is_delete=0
           left join ecshop.ecs_style cs on gg.style_id = cs.style_id
     where pg.goods_party_id = %d 
 			";
 			
 	$result = $db->getAll(sprintf($sql, $party_id));		
    
    return $result ;
 }
 
 /**
  * 删除
  */
 function _delete_gifts_by_id($group_goods_id = null){
 	if(empty($group_goods_id)){
 		return false;
 	}
 	global $db;
 	
 	$sql = "delete from ecshop.ecs_group_goods where group_goods_id = %d ";
    $result = $db->query(sprintf($sql, $group_goods_id));
    
    return $result;
 			
 }
 
 /**
  * 根据ID查找
  */
 function _get_gifts_by_id($group_goods_id = null){
 	if(empty($group_goods_id)){
 		return null;
 	}
 	global $db;
 	
 	$sql = "
         select gg.group_goods_id, gg.parent_id, gg.parent_style_id, gg.parent_cat_id,
                concat_ws(' ', pg.goods_name, if(pgs.goods_color = '', ps.color, pgs.goods_color)) as parent_goods_name,
                gg.goods_id, gg.style_id,
                concat_ws(' ', cg.goods_name, if(cgs.goods_color = '', cs.color, cgs.goods_color)) as child_goods_name
           from ecshop.ecs_group_goods gg
                left join ecshop.ecs_goods pg on gg.parent_id = pg.goods_id
                left join ecshop.ecs_goods_style pgs on gg.parent_id = pgs.goods_id and gg.parent_style_id = pgs.style_id and pgs.is_delete=0
                left join ecshop.ecs_style ps on gg.parent_style_id = ps.style_id
                left join ecshop.ecs_goods cg on gg.goods_id = cg.goods_id
                left join ecshop.ecs_goods_style cgs on gg.goods_id = cgs.goods_id and gg.style_id = cgs.style_id and cgs.is_delete=0
                left join ecshop.ecs_style cs on gg.style_id = cs.style_id
          where gg.group_goods_id = %d
        ";
    
     $result = $db->getRow(sprintf($sql, $group_goods_id));
 	 
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
 
?>