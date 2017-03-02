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
 require_once(ROOT_PATH . 'includes/helper/array.php');

 global $db;
  
 // 请求
 $request = isset($_REQUEST['request']) && 
    in_array($_REQUEST['request'], array('ajax')) 
    ? $_REQUEST['request'] 
    : null;
 $act = isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('search_goods','search_goods_and_taocan', 'update', 'delete', 'insert', 'insert_remind','update_remind','delete_remind','view_remind','edit_remind')) 
    ? $_REQUEST['act'] 
    : null;
    
    
 $type = isset($_REQUEST['type']) && 
     in_array($_REQUEST['type'], array('BIND', 'FULL_AMOUNT'))
     ? $_REQUEST['type']
     : null;
 if ($request == 'ajax'){
    $json = new JSON;
 	$limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 30 ;
    switch($act){
 		// 搜索商品
        case 'search_goods':
            print $json->encode(distribution_get_goods_list(null, null, $_POST['q'], $limit)); 
            break;
        case 'search_goods_and_taocan':
        	print $json->encode(get_goods_and_taocan_list($_POST['q'], $limit));
        	break;
 		
 	}
 	
 	exit;
 	
 }else if($_POST['act'] == 'insert'){
    $start_time=$_REQUEST['start'];
 	$end_time=$_REQUEST['end'];

 	do {
 		
 		if ($type == 'BIND') {
 		 	
	 		$party_edit = $_REQUEST['part_edit'];
		 	//部分编辑的时候（只能编辑商品和时间）去掉时间判断
		 	if(!(isset($party_edit) && $party_edit == 'Y')){
		 		$check_param = _check_param($start_time, $end_time);
				if (!$check_param->is_success) {
					$tip = $check_param->msg;
					break;
				}
		 	}
 			
 			if(null != $_POST['main_goods_amount'] && "" != $_POST['main_goods_amount'] && ! is_numeric($_POST['main_goods_amount'])) {
 				$tip = "主商品数量不是数字";
 				break;
 			}
 			
 		 	if(null != $_POST['child_goods_amount'] && "" != $_POST['child_goods_amount'] &&! is_numeric($_POST['child_goods_amount'])) {
 				$tip = "赠品(单品)数量不是数字";
 				break;
 			}
 			
 			if(!(isset($party_edit) && $party_edit == 'Y')){
 				$distributorIds=$_POST['distributorsId'];
 				$distributorids=implode(",",$distributorIds);
 				$facilityIds=$_POST['facilitiesId'];
 				if( $facilityIds && $facilityIds !=""){
 					$facilityIds=implode(",",$facilityIds);
 				}
 				$is_overlay = $_POST['is_overlay'];
 				$active_name = $_POST['active_name'];
 				$is_limit = $_POST['is_limit'];
 				$limit_goods_number = $_POST['limit_goods_number'];
 			}else{
 				$distributorids = $_POST['part_distributorids'];
 				$facilityIds = $_POST['part_facilityids']; 
 				$is_overlay = $_POST['part_is_overlay_value'];
 				$start_time = $_POST['part_start_value'];
 				$active_name = $_POST['part_active_name'];
 				$is_limit = $_POST['part_is_limit_value'];
 				$limit_goods_number = $_POST['part_limit_goods_number_value'];
 			}
 			
		    $reqdata = new stdClass();
		        $reqdata -> group_goods_id = intval($_POST['hidden_group_goods_id']);
		        $reqdata -> parent_id = intval($_POST['hid_main_goods_id']) ;
		        $reqdata -> parent_style_id = intval($_POST['hid_main_style_id']) ;
		        $reqdata -> parent_cat_id = intval($_POST['hid_main_cat_id']) ;
		        $reqdata -> parent_tc_code = $_POST['hid_main_tc_code'];
		        $reqdata -> child_tc_code = $_POST['hid_child_tc_code'];
		        $reqdata -> goods_id = intval($_POST['hid_child_goods_id']) ;
		        $reqdata -> style_id = intval($_POST['hid_child_style_id']) ;
		        $reqdata -> name = $_POST['child_goods'] ; 
		        $reqdata -> parent_amount = intval($_POST['main_goods_amount']);
				$reqdata -> goods_amount = intval($_POST['child_goods_amount']);		      
		        $reqdata -> distributor_ids = $distributorids;
		        $reqdata -> facility_ids = $facilityIds; 
		        $reqdata -> is_overlay = $is_overlay;
				$reqdata -> start_time = $start_time;
				$reqdata -> end_time = $end_time;
				$reqdata -> type = $type;
				
		        $reqdata -> created_by = $_SESSION['admin_name'];    
		        $reqdata -> goods_party_id = $_SESSION['party_id']; 
		        $reqdata -> active_name = $active_name; 
		        $reqdata -> code = uniqid(true);
		        $reqdata -> exclude_ids = "";
		        $reqdata -> exclude_cat_ids = "";
		        $reqdata -> is_limit = $is_limit;
		        $reqdata -> limit_goods_number = $limit_goods_number;
		        
 		} elseif ($type == 'FULL_AMOUNT') {
 			$part_full_edit = $_REQUEST['part_full_edit'];
 			$exclude_goods = $_REQUEST['exclude_goods'];
 			
 			$exclude_cat_ids = $_REQUEST['category_ids'];
 			if($exclude_cat_ids) {
 				$exclude_cat_ids = implode(",",$exclude_cat_ids);
 			}
 			
 			if($exclude_goods) {
 				$exclude_goods = implode(',',$exclude_goods);
 			}
 						
		 	//部分编辑的时候（只能编辑商品和时间）去掉时间判断
		 	if(!(isset($part_full_edit) && $part_full_edit == 'Y')){
		 		$check_param = _check_param($start_time, $end_time);
				if (!$check_param->is_success) {
					$tip = $check_param->msg;
					break;
				}
		 	}
		 			 	
 			if(!(isset($part_full_edit) && $part_full_edit == 'Y')){
 				$full_amount = $_POST['full_amount'];
 				$distributorIds=$_REQUEST['distributorsId_full_amount'];

 				if($distributorIds) {
 					$distributorids=implode(",",$distributorIds);
 				}
 				$facilityIds = $_REQUEST['facilitiesId_full_amount'];
 				if($facilityIds && $facilityIds !="") {
 					$facilityIds=implode(",",$facilityIds);
 				}
				$excludeCatIds=$_REQUEST['category_ids'];
 				if($excludeCatIds) {
 					$excludecatids=implode(",",$excludeCatIds);
 				}
				$is_overlay = $_POST['full_amount_is_overlay'];
				$active_name = trim($_POST['active_name']);
				$is_limit = $_POST['full_amount_is_limit'];
				$limit_goods_number = $_POST['full_amount_limit_goods_number'];
 			}else{
 				$full_amount = $_POST['part_full_amount'];
 				$distributorids = $_POST['part_distributorids_full_amount'];
 				$facilityIds = $_POST['part_facilityids_full_amount'];
 				$is_overlay = $_POST['part_full_is_overlay_value'];
 				$start_time = $_POST['part_full_start_value'];
 				$active_name = trim($_POST['part_full_active_name']);
 				$is_limit = $_POST['part_full_is_limit_value'];
 				$limit_goods_number = $_POST['part_full_limit_goods_number_value'];
 			}
 			
 		 	if(!is_numeric(intval($_POST['child_goods_amount']))) {
 				$tip = "赠品数量不是数字";
 				break;
 			}

 			if (!is_numeric($full_amount)) {
 				$tip = "金额不是数字";
 				break;
 			}
 			
 			if($full_amount == 0 && $is_overlay == 'Y') {
 				$tip = "当金额为0时，自身活动不能叠加";
 				break;
 			}
 			
		    $reqdata = new stdClass();
		        $reqdata -> group_goods_id = intval($_POST['hidden_group_goods_id']);
		        $reqdata -> goods_id = intval($_POST['hid_child_goods_id']) ;
		        $reqdata -> style_id = intval($_POST['hid_child_style_id']) ;
		        $reqdata -> goods_amount = intval($_POST['child_goods_amount']);
		        $reqdata -> distributor_ids = $distributorids;
		        $reqdata -> facility_ids = $facilityIds; 
		        $reqdata -> is_overlay = $is_overlay;
				$reqdata -> start_time = $start_time;
				$reqdata -> end_time = $end_time;
				$reqdata -> full_amount = $full_amount;
				$reqdata -> type = $type;
				$reqdata -> child_tc_code = $_POST['hid_child_tc_code'];
		        $reqdata -> created_by = $_SESSION['admin_name'];    
		        $reqdata -> goods_party_id = $_SESSION['party_id']; 
		        $reqdata -> active_name = $active_name; 
		        $reqdata -> code = uniqid(true);
		        $reqdata -> exclude_ids = $exclude_goods;
		        $reqdata -> name = $_POST['child_goods'] ; 
		        $reqdata -> goods_amount = intval($_POST['child_goods_amount']);
		        $reqdata -> exclude_cat_ids = $exclude_cat_ids;
				$reqdata -> is_limit = $is_limit;
				$reqdata -> limit_goods_number = $limit_goods_number;
 		}
	    // 更新表记录
	    $result = _gifts_update($reqdata); 
	    if($type == 'BIND'){
		    if($result){
		    	$smarty->assign('message', '赠品 '.$reqdata -> name.' 已经添加好了 ，，，');
		    }else{
		    	$smarty->assign('message', '赠品 '.$reqdata -> name.' 添加失败--! ，，，');
		    }
	    }else{
	    	$position = $_REQUEST['full_position'];
	    	$smarty -> assign('position',$position);
	        if($result){
		    	$smarty->assign('messageV2', '赠品 '.$reqdata -> name.' 已经添加好了 ，，，');
		    }else{
		    	$smarty->assign('messageV2', '赠品 '.$reqdata -> name.' 添加失败--! ，，，');
		    }
	    }
 	} while(false);
 }else if ($act == 'delete'){
 	$group_goods_id = intval($_REQUEST['group_goods_id']);
 	$result = _delete_gifts_by_id($group_goods_id);
 	
 	if($result){
 		$tip = "赠品已经删除了 ，，，";
    	$smarty->assign('tip', $tip);
    }else{
    	$tip = "赠品删除失败--! ，，，";
    	$smarty->assign('tip', $tip);
    }
 }else if ($act == 'update'){
 	$group_goods_id = intval($_REQUEST['group_goods_id']);
 	$gifts = _get_gifts_by_id($group_goods_id);
 	$exclude_ids = explode(',',$gifts['exclude_ids']);
 	$exclude_goods_list = get_exclude_goods_info($exclude_ids);
 	$exclude_goods_array = array();
 	foreach($exclude_goods_list as $k => $v) {
 		$exclude_goods_array[] = $v['outer_id'];
 	}
 	
 	$exclude_goods_str = implode($exclude_goods_array,',');
 	$sql = "select s.style_id,s.color " .
 			"from ecshop.ecs_goods_style gs inner join ecshop.ecs_style s on gs.style_id=s.style_id  " .
 			"where gs.goods_id= '{$gifts['parent_id']}' and gs.is_delete=0 ";
 	$style_list = $db -> getAll($sql);
 	$style_list = Helper_Array::toHashmap($style_list,'style_id','color');
 	$smarty->assign('style_list',$style_list);
 	$smarty->assign('view_only',$_REQUEST['view_only']);
 	$smarty->assign('edit_gifts',true); 
 	//同页跳转定位用
 	$smarty->assign('position',$_REQUEST['position']);
 	$smarty->assign('show',$_REQUEST['show']);
 	$smarty->assign('exclude_goods_list',$exclude_goods_list);
 	$smarty->assign('exclude_goods_str',$exclude_goods_str);
 	$smarty->assign('exclude_goods');
 }else if($act == 'insert_remind'){
 	$remind_name=$_REQUEST['remind_name'];
 	$create_time=date("Y-m-d H:i:m");
 	$start_time=$_REQUEST['start'];
 	$end_time=$_REQUEST['end'];
 	$update_time=date("Y-m-d H:i:m");
 	$status="OK";
 	$distributorIds=$_REQUEST['distributorsId_remind'];
 	$party_id=$_SESSION['party_id'];
 	$notice=trim($_REQUEST['notice']);
 	$created_by=$_SESSION['admin_name'];
 	do{
 		$check_param = _check_param($start_time, $end_time);
 		if (!$check_param->is_success) {
 			$tip = $check_param->msg;
 			break;
 		}
 		
 		if(!empty($distributorIds)){
			$distributorids=implode(",",$distributorIds);
 		}
		
		$remind=new stdClass();
		    $remind->remind_name=$remind_name;
		    $remind->create_time=$create_time;
		    $remind->start_time=$start_time;
		    $remind->end_time=$end_time;
		    $remind->update_time=$update_time;
		    $remind->status=$status;
		    $remind->distributorids=$distributorids;
		    $remind->party_id=$party_id;
		    $remind->notice=$notice;  
		    $remind->created_by=$created_by;
	 	$result=_remind_update($remind);
	 	if($result){
	 		$tip='添加成功';
	 		break;
	 	}
 	}
 	while(false);
 }else if($act=='view_remind'){
 	//不可编辑的查看,如果遇到活动时间已经开始的改变活动的状态
 	if(empty($_REQUEST['remind_code'])){
 		return false;
 	}
 	$edit_remind=_get_remind($_REQUEST['remind_code']);
	$smarty->assign('edit_remind',$edit_remind);
	$smarty->assign('show',$_REQUEST['show']);
 }else if($act=='update_remind'){
 	$remind_code=$_REQUEST['edit_remind_code'];
 	$remind_name=$_REQUEST['remind_name'];
 	$create_time=date("Y-m-d H:i:m");
 	$start_time=$_REQUEST['start'];
 	$end_time=$_REQUEST['end'];
 	$update_time=date("Y-m-d H:i:m");
 	$status="OK";
 	$distributorIds=$_REQUEST['distributorsId_remind'];
 	$party_id=$_SESSION['party_id'];
 	$notice=trim($_REQUEST['notice']);
 	$created_by=$_SESSION['admin_name'];
 	do{
 		$check_param = _check_param($start_time, $end_time);
 		if (!$check_param->is_success) {
 			$tip_remind = $check_param->msg;
 			break;
 		}
 		
 		if(!empty($distributorIds)){
			$distributorids=implode(",",$distributorIds);
 		}
		
		$remind=new stdClass();
		    $remind->remind_code=$remind_code;
		    $remind->remind_name=$remind_name;
		    $remind->create_time=$create_time;
		    $remind->start_time=$start_time;
		    $remind->end_time=$end_time;
		    $remind->update_time=$update_time;
		    $remind->status=$status;
		    $remind->distributorids=$distributorids;
		    $remind->party_id=$party_id;
		    $remind->notice=$notice;  
		    $remind->created_by=$created_by;
	 	$result=_remind_update($remind);
	 	if($result){
	 		$tip_remind='编辑成功';
	 		break;
	 	}
 	}
 	while(false);
 }else if($act=='delete_remind'){
 	if(empty($_REQUEST['remind_code'])){
 		$tip='未找到活动编码';
 		return false;
 	}
 	$result=_delete_remind($_REQUEST['remind_code']);
 	if($result){
 		$tip='删除成功';
 	}
 }
 if ($type == "BIND") {
 	$smarty->assign('gifts', $gifts);
 } elseif ($type == "FULL_AMOUNT") {
 	$smarty->assign('full_amount_gifts', $gifts);
 }
 $now_time = date("Y-m-d H:i:s");
 $smarty->assign('now_time',$now_time);
 $gifts_list = _get_all_gifts();
 $smarty->assign('gifts_list', $gifts_list);
 $distributors = _get_all_distributor();
 $facilities = _get_all_facility();
 $categories = _get_all_category();
 $smarty->assign('distributors',$distributors);
 $smarty->assign('facilities',$facilities); 
 $smarty->assign('categories',$categories);
 $smarty->assign('tip',$tip);
 $smarty->assign('tip_remind',$tip_remind);
 $smarty->assign('gifts_list_V2',_get_all_gifts_V2());
 $smarty->assign('reminds',_get_all_reminds());
 $smarty->display('gifts_manage/gifts_manage.htm');

 
 /**
  * 更新表
  */
 function _gifts_update($request = null){
 	if(empty($request)){
 		return false;
 	}
    $goods_party_id = intval($_SESSION['party_id']);
    if ($request -> type == 'BIND') {
		//检查主商品
		if(!_check_goods_party($request -> parent_id, $goods_party_id) && !_check_taocan_party($request -> parent_tc_code, $goods_party_id)){
		 	return false; 
		}
    }
	// 检查赠品
	if(!_check_goods_party($request -> goods_id, $goods_party_id) && !_check_taocan_party($request -> child_tc_code, $goods_party_id)){
		return false; 
	}
	
 		
    global $db;
    if ($request -> type == 'BIND') {
	 	if(empty($request->group_goods_id)){
	 		// insert
	 		$sql = "insert into ecshop.ecs_group_gift_goods (parent_id, parent_style_id, parent_cat_id, parent_tc_code, tc_code, exclude_ids, exclude_cat_ids, goods_id, 
	 		                       style_id, created_by, created_datetime,update_time, name, parent_amount, goods_amount,
	 		                       distributor_ids,facility_ids, is_overlay, start_time, end_time, status, active_name, code, type, is_limit, limit_goods_number) 
	 				  values (%d, %d, %d,'%s','%s','%s','%s', %d, %d, '%s', NOW(), NOW(), '%s', %d, %d, '%s','%s','%s', '%s', '%s', 'OK', '%s', '%s', '%s', '%s', %d) ";
	 		$sql = sprintf($sql, $request -> parent_id, $request -> parent_style_id, $request -> parent_cat_id, $request -> parent_tc_code, $request -> child_tc_code
	                                           , $request -> exclude_ids, $request -> exclude_cat_ids, $request -> goods_id, $request -> style_id, $request -> created_by, $request -> name
	                                           , $request -> parent_amount, $request -> goods_amount, $request -> distributor_ids
	                                           , $request -> facility_ids 
	                                           , $request -> is_overlay, $request -> start_time, $request -> end_time, $request->active_name
	                                           , strval($request -> code), $request -> type, $request -> is_limit, $request -> limit_goods_number);
	        
	        $result = $db->query($sql);
	        $group_goods_id = $db->insert_id();
	 		
	 	}else{
	 		// update
	 		$sql = "update ecshop.ecs_group_gift_goods
	 				       set parent_id = %d, parent_style_id = %d, parent_cat_id = %d, parent_tc_code='%s', tc_code='%s', exclude_ids = '%s', exclude_cat_ids = '%s', goods_id = %d, style_id = %d
	 				         , created_by = '%s', update_time = NOW(), name = '%s', parent_amount = %d
	 				         , goods_amount = %d, distributor_ids = '%s', facility_ids = '%s',is_overlay = '%s', start_time = '%s', end_time = '%s'
	 				         , active_name = '%s', is_limit = '%s', limit_goods_number = %d
	 			      where group_goods_id = %d " ; 
	 				  
	        $sql = sprintf($sql, $request -> parent_id, $request -> parent_style_id, $request -> parent_cat_id, $request -> parent_tc_code, $request -> child_tc_code
	                                        , $request -> exclude_ids , $request -> exclude_cat_ids,  $request -> goods_id, $request -> style_id, $request -> created_by, $request -> name
	                                        , $request -> parent_amount, $request -> goods_amount, $request -> distributor_ids
	                                        , $request -> facility_ids 
	                                        , $request -> is_overlay,  $request -> start_time, $request -> end_time
	                                        , $request -> active_name, $request -> is_limit, $request -> limit_goods_number, $request -> group_goods_id );
	        $result=$db->query($sql);
	        $group_goods_id = $request -> group_goods_id;
	                                      
	 	}
    } elseif ($request -> type == 'FULL_AMOUNT') {
    	if(empty($request->group_goods_id)){
	 		// insert
	 		$sql = "insert into ecshop.ecs_group_gift_goods (goods_id, style_id, tc_code, exclude_ids,exclude_cat_ids, created_by, created_datetime,update_time, 
	 		                       name, goods_amount,distributor_ids,facility_ids, is_overlay, start_time, 
	 		                       end_time, status, active_name, code, full_amount, type, is_limit, limit_goods_number) 
	 				  values ( %d, %d,'%s','%s','%s','%s', NOW(), NOW(), '%s', %d, '%s','%s','%s', '%s', '%s', 'OK', '%s', '%s', %lf, '%s', '%s', %d) ";
	        $sql = sprintf($sql, $request -> goods_id, $request -> style_id, $request -> child_tc_code, $request -> exclude_ids, $request -> exclude_cat_ids, $request -> created_by, $request -> name
	                                           , $request -> goods_amount, $request -> distributor_ids
	                                           , $request -> facility_ids 
	                                           , $request -> is_overlay, $request -> start_time, $request -> end_time
	                                           , $request -> active_name, strval($request -> code), $request -> full_amount, $request -> type
	                                           , $request -> is_limit, $request -> limit_goods_number);
	        $result=$db->query($sql);
	        $group_goods_id = $db->insert_id();
	 		
	 	}else{
	 		// update
	 		$sql = "update ecshop.ecs_group_gift_goods
	 				       set  goods_id = %d, style_id = %d, tc_code = '%s', exclude_ids = '%s', exclude_cat_ids = '%s'
	 				         , created_by = '%s', update_time = NOW(), name = '%s', parent_amount = %d
	 				         , goods_amount = %d, distributor_ids = '%s',facility_ids = '%s', is_overlay = '%s', start_time = '%s',
	 				          end_time = '%s',active_name = '%s', full_amount = %lf, is_limit = '%s', limit_goods_number = %d
	 			      where group_goods_id = %d " ; 
	 				  
	        $sql = sprintf($sql, $request -> goods_id, $request -> style_id, $request -> child_tc_code, $request -> exclude_ids, $request -> exclude_cat_ids, $request -> created_by, $request -> name,
	                                         $request -> parent_amount, $request -> goods_amount, $request -> distributor_ids
	                                         , $request -> facility_ids 
	                                        , $request -> is_overlay,  $request -> start_time, $request -> end_time
	                                        , $request -> active_name, $request -> full_amount, $request -> is_limit
	                                        , $request -> limit_goods_number, $request -> group_goods_id );
	        $result=$db->query($sql);
	        $group_goods_id = $request -> group_goods_id;                              
	 	}
    }

    addGiftAction($sql,$group_goods_id);

    return $result;
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
            gg.created_by, gg.created_datetime, gg.parent_amount, gg.goods_amount, gg.distributor_ids, gg.is_overlay,
            gg.facility_ids , 
            gg.start_time, gg.end_time, if(now() > gg.start_time, 'N', 'Y') as can_update
      from ecshop.ecs_group_gift_goods gg
           left join ecshop.ecs_goods pg on gg.parent_id = pg.goods_id
           left join ecshop.ecs_goods_style pgs on gg.parent_id = pgs.goods_id and gg.parent_style_id = pgs.style_id and pgs.is_delete=0
           left join ecshop.ecs_style ps on gg.parent_style_id = ps.style_id
           left join ecshop.ecs_goods cg on gg.goods_id = cg.goods_id
           left join ecshop.ecs_goods_style cgs on gg.goods_id = cgs.goods_id and gg.style_id = cgs.style_id and cgs.is_delete=0
           left join ecshop.ecs_style cs on gg.style_id = cs.style_id
     where pg.goods_party_id = %d and gg.status = 'OK'
 			";
 			
 	$result = $db->getAll(sprintf($sql, $party_id));		
    
    return $result ;
 }
 
 function _get_all_gifts_V2(){
 	global $db;
 	$party_id = intval($_SESSION['party_id']);
 	
 	$sql = "
 	   select gg.code, start_time, end_time, active_name,
 	          is_overlay, created_by, update_time, group_goods_id,
 	          full_amount, type, if(now() > start_time, 'N', 'Y') as can_update, is_limit, limit_goods_number
 	   from ecshop.ecs_group_gift_goods gg 
 	   left join ecshop.ecs_goods g on gg.goods_id = g.goods_id
 	   left join ecshop.distribution_group_goods dg on gg.tc_code=dg.code
	   where (g.goods_party_id = {$party_id} or dg.party_id={$party_id}) and gg.status='OK'
 	   order by update_time desc";
 	
 	$result=$db->getAll($sql);
 	return $result;
 }
 
 /**
  * 删除
  */
 function _delete_gifts_by_id($group_goods_id = null){
 	if(empty($group_goods_id)){
 		return false;
 	}
 	global $db;
 	
// 	$sql = "delete from ecshop.ecs_group_gift_goods where group_goods_id = %d ";
    $sql = "update ecshop.ecs_group_gift_goods set status = 'DELETED', deleted_by = '{$_SESSION['admin_name']}' where group_goods_id = %d ";
    $result = $db->query(sprintf($sql, $group_goods_id));
    
    $sql = sprintf($sql, $group_goods_id);
    
    addGiftAction($sql,$group_goods_id);
    
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
                concat_ws('',if(dg.code is null,concat_ws(' ', pg.goods_name, if(pgs.goods_color = '', ps.color, pgs.goods_color)),dg.name),'(',if(dg.code is null,concat_ws('',pg.goods_id,'_',ifnull(pgs.style_id,0)),dg.code),')' ) as goods_name,
		        gg.goods_id, gg.style_id, gg.active_name,
                concat_ws('',if(dg1.code is null,concat_ws(' ', cg.goods_name, if(cgs.goods_color = '', cs.color, cgs.goods_color)),dg1.name),'(',if(dg1.code is null, concat_ws('',cg.goods_id,'_',ifnull(cgs.style_id,0)), dg1.code),')' )  as child_goods_name, 
                ifnull(dg.code,'') parent_tc_code,
                ifnull(dg1.code,'') tc_code,
                gg.parent_amount,
                gg.goods_amount, 
                gg.start_time, gg.end_time, gg.distributor_ids, gg.is_overlay, 
                gg.facility_ids, 
                gg.full_amount, gg.type, if(now() > gg.start_time, 'N', 'Y') as can_update,
                gg.exclude_ids,
                gg.exclude_cat_ids,
                gg.is_limit, 
                gg.limit_goods_number
           from ecshop.ecs_group_gift_goods gg
                left join ecshop.ecs_goods pg on gg.parent_id = pg.goods_id
                left join ecshop.ecs_goods_style pgs on gg.parent_id = pgs.goods_id and gg.parent_style_id = pgs.style_id and pgs.is_delete=0
                left join ecshop.ecs_style ps on gg.parent_style_id = ps.style_id
                left join ecshop.ecs_goods cg on gg.goods_id = cg.goods_id
                left join ecshop.ecs_goods_style cgs on gg.goods_id = cgs.goods_id and gg.style_id = cgs.style_id and cgs.is_delete=0
                left join ecshop.ecs_style cs on gg.style_id = cs.style_id
                left join ecshop.distribution_group_goods dg on gg.parent_tc_code = dg.code
                left join ecshop.distribution_group_goods dg1 on gg.tc_code = dg1.code
          where gg.group_goods_id = %d and gg.status = 'OK'
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
  
  /**
   * 更新活动提醒表
   */
  function _remind_update($request=null){
  	if(empty($request)){
  		return false;
  	}
  	global $db;
  	//以当前时间微秒作为活动编码
  	if(empty($request->remind_code)){
	  	$remind_code = uniqid(true);
	  	$sql= " insert into ecshop.ecs_group_goods_remind
		 	      (create_time,start_time,end_time,update_time,status,distributor_ids,party_id,notice,created_by,remind_name,remind_code) 
		 	      values('{$request->create_time}','{$request->start_time}','{$request->end_time}','{$request->update_time}','{$request->status}',
		 	      '{$request->distributorids}','{$request->party_id}','{$request->notice}','{$request->created_by}','{$request->remind_name}','{$remind_code}')";
		$result=$db->query($sql);
  	}
  	else{
  		$sql="update ecshop.ecs_group_goods_remind set 
  		start_time='{$request->start_time}',end_time='{$request->end_time}',update_time='{$request->update_time}',
  		status='{$request->status}',distributor_ids='{$request->distributorids}',notice='{$request->notice}',
  		created_by='{$request->created_by}',remind_name='{$request->remind_name}'
  		where remind_code ='{$request->remind_code}'";
  		$result=$db->query($sql);
  	}
	return $result;
  }
  
  /**
   * 获得所有分销商，按类型排序
   */
  
  function _get_all_distributor($party_id = null) {
  	if (empty($party_id)) {
  		$party_id = $_SESSION['party_id'];
  	}
  	global  $db;
 	$sql="select distinct d.distributor_id, d.name
           from ecshop.distributor d 
               left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id
           where d.party_id = {$party_id} and d.status = 'NORMAL' 
           order by md.type desc";
 	$distributors=$db->getAll($sql); 
  	return $distributors;
  }

  /**
   *  获取当前业务组织的 仓库 
   */
  function _get_all_facility($party_id = null){
  	if (!function_exists('get_available_facility')) {
	    require_once("admin/includes/lib_main.php");
 	}
 	return  get_available_facility( ); 
  }
  
  /**
  * 获得所有类目
  */
  function _get_all_category($party_id = null) {
  	if (empty($party_id)) {
  		$party_id = $_SESSION['party_id'];
  	}
  	global  $db;
  	$sql = "
		select
			c1.cat_id,
			concat_ws('',c3.cat_name,'/',c1.cat_name) cat_name
		from
			ecshop.ecs_category c1
			inner join ecshop.ecs_category c3 on c1.parent_id=c3.cat_id
			left join ecshop.ecs_category c2 on c1.cat_id = c2.parent_id
		where
			c2.cat_id is null and c1.party_id={$party_id}  order by cat_name";
  	//and c1.party_id<>16
  	
 	$categories = $db->getAll($sql); 
  	return $categories;
  }
  
  /**
   * 获得所有的可用赠品活动提醒
   */
  function _get_all_reminds($party_id=null){
  	if(empty($party_id)){
  		$party_id=$_SESSION['party_id'];
  	}
  	global $db;
  	$sql="select remind_code,start_time,end_time,remind_name,created_by,update_time,status,if(now() > start_time, 'N', 'Y') as can_update
  	      from ecshop.ecs_group_goods_remind 
  	      where party_id = '{$party_id}'
  	      and status!='DELETED'
  	      order by update_time desc";
  	$reminds=$db->getAll($sql);
  	return $reminds;
  }
  
  /**
   * 根据remind_code获得remind
   */
  function _get_remind($remind_code){
  	if(empty($remind_code)){
  		return false;
  	}
  	global  $db;
  	$sql="select * ,if(now() > start_time, 'N', 'Y') as can_update
  	      from ecshop.ecs_group_goods_remind
  	      where remind_code ='{$remind_code}'";
  	$remind=$db->getRow($sql);
  	return $remind;
  }
  
  /**
   * 检查参数是否合法
   */
  function _check_param($start_time, $end_time) {
  	$result = new stdClass();
   	if($start_time<date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s"))+60*5)){
   		$result->is_success = false;
		$result->msg = '活动信息必须提前5分钟录入';
		return $result;
	}
	if($start_time>$end_time){
		$result->is_success = false;
		$result->msg = '开始时间大于结束时间';
		return $result;
	}
  	
	$result->is_success = true;
	return $result;
  }
  
  /**
   * 检测是否为edit_able的状态，如果活动已经开始，则改变成edit_disable的状态
   */
  function _is_edit_able($request){
  	global $db;
  	if($request['status']!="edit_able"){
  		return false;
  	}
  	if($request['start_time']<date("Y-m-d H:i:s")){
  		_change_remind_status($request);
  		return false;
  	}
  	return true;
  }
  
  function _change_remind_status($request){
  	global $db;
  	$sql="update ecshop.ecs_group_goods_remind
  	      set status='edit_disable'
  	      where remind_code='{$request['remind_code']}'";
  	$result=$db->query($sql);
  	return $result;
  }
  
  /**
  * 根据活动编码删除赠品活动提醒
  * 这里做个伪删除，数据还是在的
  */
  function _delete_remind($remind_code){
  	global $db;
  	$sql="update ecshop.ecs_group_goods_remind
  		    set status='DELETED',deleted_by='{$_SESSION['admin_name']}'
  		    where remind_code='{$remind_code}'";
    $result=$db->query($sql);
  	return $result;
  }
  
  //增加赠品操作记录
  function addGiftAction($sqls,$group_goods_id){
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
    $excute = "insert into ecshop.ecs_group_gift_goods_action 
    		   (`group_goods_id`,`sql`,`user`,`time`)
    		   values
    		   ('{$group_goods_id}','{$sql}','{$user_name}','{$time}')";
    $db -> query($excute);
  }
  
  function get_goods_and_taocan_list($keyword = '', $limit = 100) {
  	$conditions_item = '';
  	$conditions_taocan = '';
  	if (trim($keyword)) {
        $keyword = mysql_like_quote($keyword);
        $conditions_item .= " AND g.goods_name LIKE '%{$keyword}%'  AND g.goods_party_id={$_SESSION['party_id']}"; 
        $conditions_taocan .= " AND (dg.code like '%{$keyword}%' or dg.name like '%{$keyword}%') AND dg.party_id={$_SESSION['party_id']} ";
    }
    
    $sql = "
	    select
			*
		from
			(
				SELECT	 
		            g.goods_id as goods_id,
					ifnull(gs.style_id,0) style_id,
					g.cat_id cat_id,
					CONCAT_WS('',g.goods_id,'_',ifnull(gs.style_id,'0')) as outer_id,
		            CONCAT_WS(' ', g.goods_name, IF( gs.goods_color = '', s.color, gs.goods_color), '(',g.goods_id,'_',ifnull(gs.style_id,'0'),')' ) as goods_name,
					'item' as type
		        FROM 
		            ecshop.ecs_goods AS g 
		            LEFT JOIN ecshop.ecs_goods_style AS gs ON gs.goods_id = g.goods_id and gs.is_delete=0
		            left join ecshop.ecs_style as s on gs.style_id = s.style_id
		        WHERE 
		            ( g.is_on_sale = 1 AND g.is_delete = 0 ) {$conditions_item}
		
				union all
		
				select
					0 as goods_id,
					0 as style_id,
					0 as cat_id,
					`code` as outer_id,
					concat_ws(' ',`name`,'(',`code`,')') as goods_name,
					'taocan' as type
				from
					ecshop.distribution_group_goods dg
				where 1 {$conditions_taocan} limit {$limit}
			) t
    ";
    
    return $GLOBALS['db']->getAll($sql);
    
  }
  
  function get_exclude_goods_info($exclude_ids) {
  	if($exclude_ids==null || count($exclude_ids)==0) {
  		return array();
  	}
  	$exclude_goods_list = "(";
  	foreach($exclude_ids as $k=>$v) {
  		$exclude_id = "'{$v}'";
  		$exclude_goods_list .= $exclude_id;
  		if($k != count($exclude_ids) - 1) {
  			$exclude_goods_list .= ",";
  		}
  	}
  	$exclude_goods_list .= ")";
  	
  	$sql = "
	  	select 
			*
		from
		(
			select
				CONCAT_WS('',g.goods_id,'_',ifnull(gs.style_id,'0')) as outer_id,
				CONCAT_WS(' ', g.goods_name, IF( gs.goods_color = '', s.color, gs.goods_color),'(',g.goods_id,'_',ifnull(gs.style_id,'0'),')') as goods_name
			from
				ecshop.ecs_goods g
			    left join ecshop.ecs_goods_style gs on g.goods_id=gs.goods_id and gs.is_delete=0
				left join ecshop.ecs_style s on gs.style_id=s.style_id
			where
				( g.is_on_sale = 1 AND g.is_delete = 0 ) and  g.goods_party_id={$_SESSION['party_id']} and CONCAT_WS('',g.goods_id,'_',ifnull(gs.style_id,'0')) in {$exclude_goods_list}
			
			union all 
			
			select
				dg.code as outer_id,
				dg.name as goods_name
			from
				ecshop.distribution_group_goods dg
			where
				dg.party_id={$_SESSION['party_id']} and dg.code in {$exclude_goods_list}
		) t
  	";
  	
  	return $GLOBALS['db']->getAll($sql);
  	
  }
  
?>