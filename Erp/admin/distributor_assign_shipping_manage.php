<?php

/**
 * 根据店铺组织 指定需要选用哪些快递
 */

 define('IN_ECS', true);
 require_once('includes/init.php');
 include_once('function.php');
 require_once(ROOT_PATH . 'includes/cls_json.php');
 admin_priv('party_assign_shipping','party_assign_shipping_edit');
 
 $party_id = $_SESSION['party_id'] ;

 if(!check_leaf_party($party_id)) {
 	echo '请切换到具体的组织再设置！';
 	die();
 }

 $distributors = get_party_distributors($party_id,$default_distributor_id);
 if(empty($distributors)) {
 	echo '该组织下没有店铺！';
 	die();
 }

 $distributor_id = isset($_REQUEST['distributor_id']) ? $_REQUEST['distributor_id']:$default_distributor_id;
 $act = $_REQUEST['act'] ;
 
 //  最优快递的仓库（默认仓库） ： 用来跑最优快递的仓库
if ('search_default_facility_list' == $act) {
     $default_faility_list = get_distributor_default_facility_list($distributor_id);
     
     $json = new JSON;
     print $json->encode($default_faility_list);
     
     exit ;
} 
elseif ('default_facility_assign_facility' == $act) {
 	$facility_id_list = $_POST['default_facility_chkbox'] ;
 	
 	// 先清除以前的记录 然后再重新添加记录。
 	$sql_d = "delete from ecshop.ecs_distributor_assign_facility where distributor_id = %d " ;
 	$sql_i = "insert into `ecshop`.`ecs_distributor_assign_facility` (`distributor_id`, `facility_id`, `action_user`, `created_stamp`, `last_updated_stamp`) VALUES " ;
 	$sql_v = "(%d, '%s', '%s', now(), now())" ;
 	
 	global $db ;
 	if (!empty($facility_id_list)) {
 		$sql = $sql_i ;
 		$list_cnts = count($facility_id_list) ;
 		$index = 1 ;
 		foreach ($facility_id_list as $item) {
 			if ($index == $list_cnts) {
 			    $sql = $sql . sprintf($sql_v, $distributor_id, $item, $_SESSION['admin_name']) . '; ' ;	
 			} else {
 				$sql = $sql . sprintf($sql_v, $distributor_id, $item, $_SESSION['admin_name']) . ', ' ;
 			}
 			$index ++ ;
 		}
 		
 		// 数据库操作
 		$result = $db->query(sprintf($sql_d, $distributor_id)) ;
 		if ($result > 0) {
 			$db->query($sql) ;
 		}
 	} else {
 		// 删除组织的默认仓库
 		$result = $db->query(sprintf($sql_d, $distributor_id)) ;
 	}
 	
 } 
 
// 区域默认仓库
elseif ('search_region_facility_list' == $act) {
	 $region_id = $_REQUEST['region_facility_region_id'] ;
	
     $region_facility_list = get_distributor_region_facility_list($distributor_id,$region_id);
     $json = new JSON;
     print $json->encode($region_facility_list);
     
     exit ;
} 
 
elseif ('region_facility_assign_facility' == $act) {
	$region_id = $_POST['region_facility_region'] ;
 	$facility_id_list = $_POST['region_facility_chkbox'] ;

 	// 先清除以前的记录 然后再重新添加记录。
 	$sql_d = "delete from ecshop.ecs_distributor_region_assign_facility where distributor_id = %d and region_id = %d " ;
 	$sql_i = "insert into `ecshop`.`ecs_distributor_region_assign_facility` (`distributor_id`, `region_id`, `facility_id`, `action_user`, `created_stamp`, `last_updated_stamp`) VALUES " ;
 	$sql_v = "(%d, '%d', '%s', '%s', now(), now())" ;
 	
 	global $db ;
 	if (!empty($facility_id_list)) {
 		$sql = $sql_i ;
 		$list_cnts = count($facility_id_list) ;
 		$index = 1 ;
 		foreach ($facility_id_list as $item) {
 			if ($index == $list_cnts) {
 			    $sql = $sql . sprintf($sql_v, $distributor_id, $region_id, $item, $_SESSION['admin_name']) . '; ' ;	
 			} else {
 				$sql = $sql . sprintf($sql_v, $distributor_id, $region_id, $item, $_SESSION['admin_name']) . ', ' ;
 			}
 			$index ++ ;
 		}
 		
 		// 数据库操作
 		$result = $db->query(sprintf($sql_d, $distributor_id, $region_id)) ;
 		if ($result > 0) {
 			$db->query($sql) ;
 		}
 	} else {
 		// 删除指定区域的默认仓库
 		$result = $db->query(sprintf($sql_d, $distributor_id, $region_id)) ;
 	}
 	
 }   
 
  // 最优快递
 elseif ('search_best_shipping_list' == $act) {
 	 $facility_id = $_REQUEST['best_shipping_facility_id'] ;
     $assigned_shipping_list = get_distributor_shipping_by_facility($facility_id, $distributor_id);
     
     $json = new JSON;
     print $json->encode($assigned_shipping_list);
     
     exit ;
 } elseif ('best_shipping_assign_shipping' == $act) {
 	$facility_id = $_POST['best_shipping_facility'] ;
 	$shipping_id_list = $_POST['best_shipping_chkbox'] ;
 	
 	// 先清除以前的记录 然后再重新添加记录。
 	$sql_d = "delete from ecshop.ecs_distributor_assign_shipping where facility_id = '%s' and distributor_id = %d ;" ;
 	$sql_i = "insert into `ecshop`.`ecs_distributor_assign_shipping` (`distributor_id`, `facility_id`, `shipping_id`, `enabled`, `action_user`, `updated_time`, `created_user`, `created_time`) VALUES " ;
 	$sql_v = "(%d, '%s', %d, 1, '%s', UNIX_TIMESTAMP(), '%s', UNIX_TIMESTAMP())" ;
 	
 	global $db ;
 	if (!empty($shipping_id_list)) {
 		$sql = $sql_i ;
 		$list_cnts = count($shipping_id_list) ;
 		$index = 1 ;
 		foreach ($shipping_id_list as $item) {
 			if ($index == $list_cnts) {
 			    $sql = $sql . sprintf($sql_v, $distributor_id, $facility_id, $item, $_SESSION['admin_name'], $_SESSION['admin_name']) . '; ' ;	
 			} else {
 				$sql = $sql . sprintf($sql_v, $distributor_id, $facility_id, $item, $_SESSION['admin_name'], $_SESSION['admin_name']) . ', ' ;
 			}
 			$index ++ ;
 		}
 		
 		// 数据库操作
 		$result = $db->query(sprintf($sql_d, $facility_id, $distributor_id)) ;
 		if ($result > 0) {
 			$db->query($sql) ;
 		}
 	} else {
 		// 删除组织下某仓库的最优快递
 		$result = $db->query(sprintf($sql_d, $facility_id, $distributor_id)) ;
 	}
 	
 } 
 
 // 区域默认快递
elseif ('search_region_shipping_list' == $act) {
	 $region_id = $_REQUEST['region_shipping_region_id'] ;
	
     $region_shipping_list = get_distributor_region_shipping_list($distributor_id,$region_id);
     $json = new JSON;
     print $json->encode($region_shipping_list);
     
     exit ;
} 
elseif ('region_shipping_assign_shipping' == $act) {
	$region_id = $_POST['region_shipping_region'] ;
 	$shipping_id_list = $_POST['region_shipping_chkbox'] ;

 	// 先清除以前的记录 然后再重新添加记录。
 	$sql_d = "delete from ecshop.ecs_distributor_region_assign_shipping where distributor_id = %d and region_id = %d " ;
 	$sql_i = "insert into `ecshop`.`ecs_distributor_region_assign_shipping` (`distributor_id`, `region_id`, `shipping_id`, `action_user`, `created_stamp`, `last_updated_stamp`) VALUES " ;
 	$sql_v = "(%d, '%d', '%d', '%s', now(), now())" ;
 	
 	global $db ;
 	if (!empty($shipping_id_list)) {
 		$sql = $sql_i ;
 		$list_cnts = count($shipping_id_list) ;
 		$index = 1 ;
 		foreach ($shipping_id_list as $item) {
 			if ($index == $list_cnts) {
 			    $sql = $sql . sprintf($sql_v, $distributor_id, $region_id, $item, $_SESSION['admin_name']) . '; ' ;	
 			} else {
 				$sql = $sql . sprintf($sql_v, $distributor_id, $region_id, $item, $_SESSION['admin_name']) . ', ' ;
 			}
 			$index ++ ;
 		}
 		
 		// 数据库操作
 		$result = $db->query(sprintf($sql_d, $distributor_id, $region_id)) ;
 		if ($result > 0) {
 			$db->query($sql) ;
 		}
 	} else {
 		// 删除指定区域的默认快递
 		$result = $db->query(sprintf($sql_d, $distributor_id, $region_id)) ;
 	}
 	
 }   
 // 店铺默认快递
elseif ('search_default_shipping_list' == $act) {
	
     $default_shipping_list = get_distributor_default_shipping_list($distributor_id);
     $json = new JSON;
     print $json->encode($default_shipping_list);
     
     exit ;
} 
elseif ('default_shipping_assign_shipping' == $act) {
	
 	$shipping_id_list = $_POST['default_shipping_chkbox'] ;

 	// 先清除以前的记录 然后再重新添加记录。
 	$sql_u = "update  ecshop.taobao_shop_conf set shipping_id = %d   where distributor_id = %d  " ;
 	
 	global $db ;
 	if (!empty($shipping_id_list) && count($shipping_id_list) == 1  ) {
		$sql = sprintf($sql_u, $shipping_id_list[0], $distributor_id,   $_SESSION['admin_name']) . '; ' ;
 		// 数据库操作
 		$result = $db->query($sql) ;
 	}
 	
 }   
 // 店铺默认仓库
elseif ('search_distributor_default_facility_list' == $act) {
	
     $distributor_default_facility_list = get_distributor_default_facility_list2($distributor_id);
     $json = new JSON;
     print $json->encode($distributor_default_facility_list);
     
     exit ;
} 
elseif ('distributor_default_facility_assign_facility' == $act) {
	
 	$facility_id_list = $_POST['distributor_default_facility_chkbox'] ;

 	// 先清除以前的记录 然后再重新添加记录。
 	$sql_u = "update  ecshop.taobao_shop_conf set facility_id = %d   where distributor_id = %d  " ;
 	
 	global $db ;
 	if (!empty($facility_id_list) && count($facility_id_list) == 1  ) {
		$sql = sprintf($sql_u, $facility_id_list[0], $distributor_id,   $_SESSION['admin_name']) . '; ' ;
 		// 数据库操作
 		$result = $db->query($sql) ;
 	}
 	
 }   

 
 // 检索对应party下可用仓库
 if (!function_exists('get_available_facility')) {
     require_once("admin/includes/lib_main.php");	
 }

 if(check_admin_priv('party_assign_shipping_edit')){
    $can_edit='1';
 }else{
    $can_edit='0';
 }
 $smarty->assign('can_edit',$can_edit);
 $smarty->assign('province_list', get_regions(1, $GLOBALS['_CFG']['shop_country']));  // 省份列表

 $available_facility_list = get_available_facility();
 $facility_lists = get_facility_list($available_facility_list);
 
 $smarty->assign('available_facility_list', $available_facility_list);
 $smarty->assign('distributors', $distributors);
 $smarty->assign('current_distributor_id', $distributor_id);
// var_dump($distributor_id);
 $smarty->assign('facility_lists', $facility_lists);
 $smarty->assign('shipping_list', get_shipping_list());
 $smarty->assign('region_list', get_region_list());
 $smarty->assign('shop_default_facility_shipping', get_shop_default_facility_shipping($party_id));
 $smarty->assign('distributor_region_assign_shipping_list', get_distributor_region_assign_shipping_list($distributor_id));
 $smarty->assign('distributor_region_assign_facility_list', get_distributor_region_assign_facility_list($distributor_id));
 $smarty->assign('distributor_default_assign_shipping_list', get_distributor_default_assign_shipping_list($distributor_id));
 $smarty->assign('distributor_default_assign_facility_list', get_distributor_default_assign_facility_list($distributor_id));
 $smarty->assign('act_id', $act);
 
 $smarty->display('distributor_assign_shipping_manage.htm') ;

/*
 * 根据业务distributorId、仓库ID 检索其对应的快递
 */
 function get_distributor_shipping_by_facility ($facilityId, $distributorId) {
 	global $db;
 	if (empty($distributorId)) {
 		return null;
 	}
 	
 	$sql = "select distinct ass.shipping_id 
              from ecshop.ecs_distributor_assign_shipping ass 
                  left join ecshop.ecs_shipping s on ass.shipping_id = s.shipping_id
             where ass.distributor_id = %d and ass.facility_id = '%s' and ass.enabled = 1 ";
             
     $shipping_list = $db->getCol(sprintf($sql, $distributorId, $facilityId));
     
     return $shipping_list ;
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

?>