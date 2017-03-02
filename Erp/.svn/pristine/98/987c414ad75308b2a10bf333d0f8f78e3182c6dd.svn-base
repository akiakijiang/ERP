<?php

/**
 * 上架工具页面
 */
define('IN_ECS', true);
set_time_limit(3600);
require_once('includes/init.php');
require_once (ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
admin_priv ( 'double_eleven_shelves' );

require_once(ROOT_PATH . 'includes/lib_common.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'admin/includes/lib_taobao.php');
include_once(ROOT_PATH . 'includes/lib_passport.php');

$sql = "select IN_STORAGE_MODE FROM romeo.party where party_id = '{$_SESSION['party_id']}' limit 1 ";
$IN_STORAGE_MODE = $db->getOne($sql);
if($IN_STORAGE_MODE==3){
	die("该业务组是生产日期/批次维护，不能通过上架工具操作！");
}
// 去掉空白
if(!empty($_POST))
	Helper_Array::removeEmpty($_POST);

if($_REQUEST ['act'] == 'shelve_goods'){
	$shelve_goods_msg = "";
	$party_id = isset($_REQUEST['party_id'])?$_REQUEST['party_id']:false;
	$facility_id = isset($_REQUEST['facility_id'])?$_REQUEST['facility_id']:false;
	$barcode = isset($_REQUEST['barcode'])?$_REQUEST['barcode']:false;
	$location_barcode = isset($_REQUEST['location_barcode'])?$_REQUEST['location_barcode']:false;
	
	if($party_id && $facility_id && $barcode && $location_barcode){
		$sql = "select product_id
			from ecshop.ecs_goods_style egs
			inner join ecshop.ecs_goods eg on eg.goods_id = egs.goods_id
			inner join romeo.product_mapping pm on pm.ecs_goods_id = egs.goods_id and pm.ecs_style_id = egs.style_id
			where egs.barcode = '{$barcode}' and egs.is_delete=0
			and eg.goods_party_id = '{$party_id}'";
		$product_id = $db->getOne($sql);
		if(!$product_id){
			$sql = "select product_id
				from ecshop.ecs_goods eg 
				inner join romeo.product_mapping pm on pm.ecs_goods_id = eg.goods_id 
				where eg.barcode = '{$barcode}'
				and eg.goods_party_id = '{$party_id}'		
			";
			$product_id = $db->getOne($sql);
		}
		
		if($product_id){
			$location_id = $db->getOne("select location_id from romeo.location where location_barcode = '{$location_barcode}'");
			if($location_id){
				$sql = "
					insert into romeo.inventory_location (location_barcode,is_serial,goods_barcode,product_id,goods_number,available_to_reserved,validity,party_id,facility_id,status_id,action_user,created_stamp,last_updated_stamp,location_id)" .
							" values('{$location_barcode}','0','{$barcode}','{$product_id}',10000,10000,'1970-01-01','{$party_id}','{$facility_id}','INV_STTS_AVAILABLE','{$_SESSION['admin_user']}',now(),now(),'{$location_id}');
				";
//				var_dump($sql);
				$result = $db->query($sql);
				if($result){
					$shelve_goods_msg = "数据库执行成功,已经成功的上架";
					$smarty->assign('party_id',$party_id);
					$smarty->assign('facility_id',$facility_id);
				}else{
					$shelve_goods_msg = "数据库执行不成功";
				}			
			}else{
				$shelve_goods_msg = "根据barcode为：".$location_barcode ."找不到记录";			
			}
		}else{
			$shelve_goods_msg = "根据location_barcode为：".$barcode ."在业务组：".$party_id."找不到商品";
		}
	}else{
		$shelve_goods_msg = '没有输入对用的party_id或者facility_id或者barcode或者location_barcode';
	}	
	$smarty->assign('shelve_goods_msg',$shelve_goods_msg);
 }
 

$user = $db->getRow('SELECT user_id, nav_list FROM ' . $ecs->table('admin_user') . " WHERE user_id = '" . $_SESSION['admin_id'] . "'");
$party_id = party_get_user_party_new($user['user_id']);
$party_list = party_get_all_list();
$list = array();
if ($party_list) {
	if (is_null($party_id)) {
		foreach ($party_list as $obj) {
			$sql = "select party_id from romeo.party where IS_LEAF='Y' and party_id = '{$obj->partyId}'";
	        $user_party_id = $db->getOne($sql);
	        if(!is_null($user_party_id)) $list[$obj->partyId] = $obj->name;
	    }
    }
    else {
       if (!is_array($party_id)) {
            	$party_id = array_filter(array_map('trim', explode(',', $party_id)), 'strlen');
            }
            foreach ($party_list as $obj) {
            	$sql = "select party_id from romeo.party where IS_LEAF='Y' and party_id = '{$obj->partyId}'";
	        	$user_party_id = $db->getOne($sql);
            	if (in_array($obj->partyId, $party_id) && !is_null($user_party_id)) {
                    $list[$obj->partyId] = $obj->name;            		
            	}
            }
    	}
}
$smarty->assign('partys', $list);    
$smarty->assign('facilitys', get_available_facility());
$smarty->display('double_eleven_shelves.htm');

?>