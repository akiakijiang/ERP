<?php
/**
 * 分销快递公式选择
 */
 define('IN_ECS', true);
 require_once('includes/init.php');
 include_once('function.php');
 require_once(ROOT_PATH . 'includes/cls_json.php');
 require_once (ROOT_PATH . 'includes/debug/lib_log.php');
 
 admin_priv('distribution_shipping','distribution_shipping_edit');
 
 $party_id = $_SESSION['party_id'];
 $act = $_REQUEST['act'] ;
 if(!check_leaf_party($party_id)) {
 	echo '请切换到具体的组织再设置！';
 	die();
 }
 //区域转仓
 if('search_region_facility_list' == $act){
 	 $region_id = $_REQUEST['region_facility_region_id'] ;
     $region_facility_list = get_dtb_region_facility_list($party_id,$region_id);
     $json = new JSON;
     print $json->encode($region_facility_list);
     
     exit ;
 }elseif('region_facility_assign_facility' == $act){
 	$region_id = $_POST['region_facility_region'] ;
 	$facility_id_list = $_POST['region_facility_chkbox'] ;
 	// 先清除以前的记录 然后再重新添加记录。
 	$sql_d = "delete from ecshop.ecs_distribution_facility where party_id = %d and region_id = %d  and store_type = 'taobao' and type = 'fenxiao' " ;
 	$sql_i = "insert into `ecshop`.`ecs_distribution_facility` (`party_id`, `region_id`, `facility_id`, `action_user`, `created_stamp`, `last_updated_stamp`,`store_type`,`type`) VALUES " ;
 	$sql_v = "(%d, '%d', '%s', '%s', now(), now(),'%s','%s')" ;
 	
 	global $db ;
 	if (!empty($facility_id_list)) {
 		$sql = $sql_i ;
 		$list_cnts = count($facility_id_list) ;
 		$index = 1 ;
 		foreach ($facility_id_list as $item) {
 			if ($index == $list_cnts) {
 			    $sql = $sql . sprintf($sql_v, $party_id, $region_id, $item, $_SESSION['admin_name'],'taobao','fenxiao') . '; ' ;
 			} else {
 				$sql = $sql . sprintf($sql_v, $party_id, $region_id, $item, $_SESSION['admin_name'],'taobao','fenxiao') . ', ' ;
 			}
 			$index ++ ;
 		}
 		
 		// 数据库操作
 		$result = $db->query(sprintf($sql_d, $party_id, $region_id)) ;
 		if ($result > 0) {
 			$db->query($sql) ;
 		}
 	} else {
 		// 删除指定区域的默认仓库
 		$result = $db->query(sprintf($sql_d, $party_id, $region_id)) ;
 	}
 //区域快递
 }elseif('search_region_shipping_list' == $act){
 	 $region_id = $_REQUEST['region_shipping_region_id'] ;
     $region_shipping_list = get_dtb_region_shipping_list($party_id,$region_id);
     Qlog::log('$region_shipping_list:'.implode(',',$region_shipping_list));
     $json = new JSON;
     print $json->encode($region_shipping_list);
     
     exit ;
 }elseif('region_shipping_assign_shipping' == $act){
 	$region_id = $_POST['region_shipping_region'] ;
 	$shipping_id_list = $_POST['region_shipping_chkbox'] ;
 	// 先清除以前的记录 然后再重新添加记录。
 	$sql_d = "delete from ecshop.ecs_distribution_shipping where party_id = %d and region_id = %d and store_type = 'taobao' and type = 'fenxiao' " ;
 	$sql_i = "insert into `ecshop`.`ecs_distribution_shipping` (`party_id`, `region_id`, `shipping_id`, `action_user`, `created_stamp`, `last_updated_stamp`,`store_type`,`type`) VALUES " ;
 	$sql_v = "(%d, '%d', '%d', '%s', now(), now(),'%s','%s')" ;
 	
 	global $db ;
 	if (!empty($shipping_id_list)) {
 		$sql = $sql_i ;
 		$list_cnts = count($shipping_id_list) ;
 		$index = 1 ;
 		foreach ($shipping_id_list as $item) {
 			if ($index == $list_cnts) {
 			    $sql = $sql . sprintf($sql_v, $party_id, $region_id, $item, $_SESSION['admin_name'],'taobao','fenxiao') . '; ' ;	
 			} else {
 				$sql = $sql . sprintf($sql_v, $party_id, $region_id, $item, $_SESSION['admin_name'],'taobao','fenxiao') . ', ' ;
 			}
 			$index ++ ;
 		}
 		
 		// 数据库操作
 		$result = $db->query(sprintf($sql_d, $party_id, $region_id)) ;
 		if ($result > 0) {
 			$db->query($sql) ;
 		}
 	} else {
 		// 删除指定区域的默认快递
 		$result = $db->query(sprintf($sql_d, $party_id, $region_id)) ;
 	}
 //淘宝配送方式
 }elseif('search_pat_shipping_list' == $act){
 	 $pat_id = $_REQUEST['pat_shipping_id'] ;
     $pat_shipping_list = get_dtb_pat_shipping_list($party_id,$pat_id);
     Qlog::log('$pat_shipping_list:'.implode(',',$pat_shipping_list));
     $json = new JSON;
     print $json->encode($pat_shipping_list);
     
     exit ;
 }elseif('pat_shipping_distribution_shipping' == $act){
 	$pat_id = $_POST['taobao_pat_shipping'] ;
 	$pat_shipping_id_list = $_POST['pat_shipping_chkbox'] ;
 	// 先清除以前的记录 然后再重新添加记录。
 	$sql_d = "delete from ecshop.ecs_store_pat_shipping where party_id = %d and pat_id = %d and store_type = 'taobao' and type = 'fenxiao' " ;
 	$sql_i = "insert into `ecshop`.`ecs_store_pat_shipping` (`party_id`, `pat_id`, `shipping_id`,`action_user`, `created_stamp`, `last_updated_stamp`,`store_type`,`type`) VALUES " ;
 	$sql_v = "(%d, '%d', '%d','%s', now(), now(),'%s','%s')" ;
 	
 	global $db ;
 	if (!empty($pat_shipping_id_list)) {
 		$sql = $sql_i ;
 		$list_cnts = count($pat_shipping_id_list) ;
 		$index = 1 ;
 		foreach ($pat_shipping_id_list as $item) {
 			if ($index == $list_cnts) {
 			    $sql = $sql . sprintf($sql_v, $party_id, $pat_id, $item, $_SESSION['admin_name'],'taobao','fenxiao') . '; ' ;	
 			} else {
 				$sql = $sql . sprintf($sql_v, $party_id, $pat_id, $item, $_SESSION['admin_name'],'taobao','fenxiao') . ', ' ;
 			}
 			$index ++ ;
 		}
 		
 		// 数据库操作
 		$result = $db->query(sprintf($sql_d, $party_id, $pat_id)) ;
 		if ($result > 0) {
 			$db->query($sql) ;
 		}
 	} else {
 		// 删除指定区域的默认快递
 		$result = $db->query(sprintf($sql_d, $party_id, $pat_id)) ;
 	}
 }
 /*
 * 检索正在使用的所有的快递 根据COD、与非COD区分
 */ 
 function get_shipping_list () {
 	global $db ;
 	
 	$sql = "select shipping_id, shipping_name from ecshop.ecs_shipping where support_no_cod = 1 and support_cod = 0 and enabled = 1 
             UNION ALL
             select shipping_id, shipping_name from ecshop.ecs_shipping where support_no_cod = 0 and support_cod = 1 and enabled = 1  ";
 	
 	$shipping_list = $db->getAll($sql) ;
 	
 	return $shipping_list ;
 }
 /*
 * 得到组织的仓库列表 
 */ 
 function get_facility_list ($available_facilitys) {
 	$facility_lists = array();
 	$facility_name = array();
 	foreach ($available_facilitys as $key => $available_facility) {
 		$facility_name['facility_id'] = $key;
 		$facility_name['facility_name'] = $available_facility;
 		$facility_lists[] = $facility_name;
 	}
 	return $facility_lists ;
 }
 
 if(check_admin_priv('distribution_shipping_edit')){
    $can_edit='1';
 }else{
    $can_edit='0';
 }
 $smarty->assign('can_edit',$can_edit);

 $available_facility_list = get_available_facility();
 $facility_lists = get_facility_list($available_facility_list);
  
 $smarty->assign('facility_lists', $facility_lists);
 $smarty->assign('act_id', $act);
 $smarty->assign('region_list', get_region_list());//所有区域
 $smarty->assign('shipping_list', get_shipping_list());//所有快递方式
 $smarty->assign('taobao_pat_shipping_list', get_pat_shipping_list());//所有拍下方式
 $smarty->assign('distribution_default_shipping_list', get_distribution_shipping_list($party_id));//获取特殊地区快递
 $smarty->assign('distribution_facility_list', get_distribution_facility_list($party_id));//获取特殊地区仓库
 $smarty->assign('distribution_pat_shipping_list', get_distribution_pat_shipping_list($party_id));//获取淘宝拍下在ERP转换的快递
 
 
 $smarty->display('distribution_shipping_manage.htm') ;

?>
