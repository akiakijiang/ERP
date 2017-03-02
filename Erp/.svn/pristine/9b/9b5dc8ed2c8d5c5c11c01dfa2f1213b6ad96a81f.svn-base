<?php
/**
 * 根据快递公司 先将所有地区列表导入，再做编辑
 * 
 * 参数说明
 * shipping_id: 快递方式ID
 * done: 值为1时进行数据库操作，
 *
 */
define('IN_ECS', true);
require_once('../includes/init.php');
require_once(ROOT_PATH . 'admin/function.php');
if(!in_array($_SESSION['admin_name'],array('ychen','zwzheng','jrpei','hbai')))
{
	die('这个操作比较危险，需要ERP来操作。');
}
/**
 * 清除以前已经维护进去的表记录
 */
 function romove_before_import($shipping_id) {
    global $db;

    if (is_null($shipping_id)) {
    	return ;
    }
    
    $shipping_area_list = $db->getAll(sprintf("select * from ecshop.ecs_shipping_area where shipping_id = %d ", $shipping_id));
    foreach ($shipping_area_list as $shipping_area) {
    	// 先清除表ecshop.ecs_area_region中记录
    	$result = $db->query(sprintf("delete from ecshop.ecs_area_region where shipping_area_id = %d ", $shipping_area['shipping_area_id']));
    	if ($result > 0) {
    		$result = $db->query(sprintf("delete from ecshop.ecs_shipping_area where shipping_area_id = %d ", $shipping_area['shipping_area_id']));
    	}
    }

 }
    $shipping_id = trim($_REQUEST['shipping_id']);
    $done = trim($_REQUEST['done']);
 if ($done == 1 && !is_null($shipping_id)) {
 	// 先清除旧记录 
 	romove_before_import($shipping_id);
 	
 	$configure = "a:5:{i:0;a:2:{s:4:\"name\";s:9:\"basic_fee\";s:5:\"value\";s:1:\"0\";}i:1;a:2:{s:4:\"name\";s:12:\"basic_weight\";s:5:\"value\";s:1:\"0\";}i:2;a:2:{s:4:\"name\";s:12:\"extra_weight\";s:5:\"value\";s:1:\"0\";}i:3;a:2:{s:4:\"name\";s:9:\"extra_fee\";s:5:\"value\";s:1:\"0\";}i:4;a:2:{s:4:\"name\";s:13:\"delivery_time\";s:5:\"value\";s:1:\"0\";}}";
 	$subRegionSQL = "select region_id from ecshop.ecs_region where parent_id = %d ";
 	$shippingAreaSQL = "insert into `ecshop`.`ecs_shipping_area` (`shipping_area_name`, `shipping_id`, `configure`, `enabled`) VALUES ('%s', %d, '%s', 1) ";
 	$areaRegionSQL = "insert into ecshop.ecs_area_region (shipping_area_id, region_id) VALUES (%d, %d) ";
 	
 	$provinceList = $db->getAll("select region_id, region_name from ecshop.ecs_region where parent_id = 1 and region_type = 1") ;
 	foreach ($provinceList as $province) {
 		// 添加记录表ecshop.ecs_shipping_area
 		$result = $db->query(sprintf($shippingAreaSQL, $province['region_name'], $shipping_id, $configure));
 		$shippingAreaId = $db->getRow('SELECT LAST_INSERT_ID() AS shipping_area_id ; ');
 		
 		// 添加记录表ecshop.ecs_area_region
 		$result = $db->query(sprintf($areaRegionSQL, $shippingAreaId['shipping_area_id'], $province['region_id']));
 		
 		$cityList = $db->getAll(sprintf($subRegionSQL, $province['region_id']));
 		foreach ($cityList as $city) {
 			$result = $db->query(sprintf($areaRegionSQL, $shippingAreaId['shipping_area_id'], $city['region_id']));
 			
 			$districtList = $db->getAll(sprintf($subRegionSQL, $city['region_id']));
 			foreach ($districtList as $district) {
 				$result = $db->query(sprintf($areaRegionSQL, $shippingAreaId['shipping_area_id'], $district['region_id']));
 			}
 		}
 		
 	}
 	
 	echo('shipping_id == ' .$shipping_id .'已全部添加完毕。');
 }





