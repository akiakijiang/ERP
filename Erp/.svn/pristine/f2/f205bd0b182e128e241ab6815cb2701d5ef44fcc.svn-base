<?php
/**
 * 退换货收货、验货
 */
define('IN_ECS', true);

require('includes/init.php');
require('includes/lib_service.php');
require_once('config.vars.php');
require_once(ROOT_PATH."RomeoApi/lib_RMATrack.php");

require_once('includes/lib_postsale_cache.php');

admin_priv('ck_out_facility_back_goods');
require("function.php");
//查询仓库权限
check_user_in_facility();

$act = $_REQUEST['act'];
$datetime = date("Y-m-d H:i:s", time());
global $db;
$outFacility = $db->getCol("SELECT facility_id FROM romeo.facility WHERE IS_OUT_SHIP = 'Y' ");
$out_facility_list = implode("','",$outFacility);
if ($act == "update") {  // 验货入库、拒绝入库操作
	global $db;
	// 获取POST传递的参数值
	$service_id = intval($_POST['service_id']);
	// 验货入库、拒绝入库添加锁
	if(!lock_acquire('update_'.$service_id)) {
		sys_msg("对不起，验货入库或拒绝入库正在执行，稍后再试！", 1);
		exit;
	}
	
	//sleep(5);
	// 获取service记录
	$sql = "SELECT * FROM ecshop.service WHERE service_id = '{$service_id}' and facility_id in ('{$out_facility_list}') "; 
	$service = $db->getRow($sql);
	if(empty($service)){
		sys_msg("外包仓没有找到对应退换货订单！",1);
		exit;
	}
 	$inner_check = $_POST['inner_check'];
 	$check_result = $_POST['check_result'];
	
	if ($inner_check == "pass") { // 验货通过
		// 获取POST传递的参数值
	    $serialNums = $_POST['serialNum'];
	    $goodsType = $_POST['goodsType'];
	    
	    if ($service['service_status'] == SERVICE_STATUS_DENIED) {
	    	sys_msg("对不起，该售后服务申请审核未通过", 1);
	    } else if($service['inner_check_status'] == 33) {
	    	sys_msg("该订单已被拒绝入库", 1);
	    } else if($service['inner_check_status'] == 32) {
	    	sys_msg("该订单已成功入库", 1);    	
	    }
	    
	    // 验证提交的商品总数量是否正确
	    $countOfGoods = 0;
	    if(!empty($goodsType)){
	        foreach($goodsType as $key=>$goodsTypeItem){
	        	$countOfGoods += count($goodsTypeItem);
	        }
	    }
	    
	    $sql = "SELECT SUM(amount) FROM ecshop.service_order_goods WHERE service_id = '{$service_id}' AND is_approved = '1' ";
	    $serviceCount = $db->getOne($sql);
	    
	    if($serviceCount != $countOfGoods){
	    	sys_msg("对不起，提交的数据错误！请重新提交", 1);
	    }
	    
	    /* start of 废老库存 by zjli*/
	    // 验货入库（废弃老库存）
	    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    	QLog::log("验货入库开始 ");
    	QLog::log("service_id: ".$service_id);
    	QLog::log("serialNums: ".json_encode($serialNums));
    	QLog::log("goodsType: ".json_encode($goodsType));
	    $goodsNum = back_change_in_stock($service_id,$serialNums,$goodsType);
	    QLog::log("验货入库结束");
	    
	    if ($goodsNum > 0) {
            $log_note = $_SESSION['admin_name'] ." 于 {$datetime} 验货入库成功";
            $sql = " UPDATE ecshop.service SET back_shipping_status = 12,
            		    inner_check_status = 32,
		                service_status = '".SERVICE_STATUS_OK."', 
		                service_call_status = '".SERVICE_CALL_STATUS_NEEDCALL."',
		                check_result = '{$check_result}'
		                WHERE service_id = '{$service_id}' LIMIT 1 ";
			if(!$db->query($sql)){
	    		sys_msg("对不起，更新售后状态失败！请联系ERP组", 1);
			}
			
			$sql = "UPDATE ecshop.ecs_order_info SET shipping_time = UNIX_TIMESTAMP() WHERE order_id = {$service['back_order_id']} LIMIT 1 ";
			if(!$db->query($sql)){
	    		sys_msg("对不起，更新退货入库时间失败！请联系ERP组", 1);
			}
			
			//更新受理时间
			if(isRMATrackNeeded()){
				try{
					$result = getTrackByServiceId($service_id);
			        if ($result->total > 0) {
			            $tracks = wrap_object_to_array($result->resultList->Track);
			            foreach ($tracks as $track) {
			                $track->receivedDate = date("Y-m-d H:i:s");
			                $track->receivedUser = $_SESSION['admin_name'];
			                updateTrack($track);
			            }
			        }    
				}
				catch(Exception $e){
					sys_msg("对不起，更新受理时间失败！请联系ERP组", 1);
				}
			}

            //SINRI UPDATE POSTSALE CACHe
            POSTSALE_CACHE_updateService(null,180,$service_id);
        } else {
	    	sys_msg("对不起，入库操作失败！请联系ERP组", 1);
        }
	}else if($inner_check == "refused"){  // 拒绝入库
		if($service['inner_check_status'] == 33) {
	    	sys_msg("该订单已被拒绝入库", 1);
	    } else if($service['inner_check_status'] == 32) {
	    	sys_msg("该订单已成功入库", 1);    	
	    }
		
		$log_note = $_SESSION['admin_name'] ." 于 {$datetime} 验货未通过";
		
		// 更新售后服务service的状态
        $sql = " UPDATE service SET inner_check_status = 33,
	                    service_status = '".SERVICE_STATUS_DENIED."', 
	                    service_call_status = '".SERVICE_CALL_STATUS_NEEDCALL."',
	                    check_result = '{$check_result}'
	                    WHERE service_id = '{$service_id}' LIMIT 1 ";
	    if(!$db->query($sql)){
	    	sys_msg("对不起，更新售后状态失败！请联系ERP组", 1);
	    }
	    
	    $sql = "UPDATE ecshop.service_order_goods SET is_approved = 0, amount = 0 WHERE service_id = '{$service_id}'";
       	if(!$db->query($sql)){
       		sys_msg("对不起，更新售后服务商品失败！请联系ERP组", 1);
        }
        
        if(!empty($service['back_order_id']) && $service['back_order_id'] != 0){
        	$sql = "UPDATE ecshop.order_relation SET parent_order_id = 0, root_order_id = 0, parent_order_sn = '', root_order_sn = '' WHERE order_id = '{$service['back_order_id']}'";
            	if(!$db->query($sql)){
        		sys_msg("对不起，更新订单关系失败！请联系ERP组", 1);
            }
        }

        //SINRI UPDATE POSTSALE CACHe
        POSTSALE_CACHE_updateService(null,180,$service_id);
	}
	
	$service['log_note'] = $log_note;
	$service['log_type'] = 'LOGISTIC';
	if(!service_log($service)){
    	sys_msg("对不起，更新售后日志失败！请联系ERP组", 1);
	}else{
		// 指定返回跳转的页面
		$back = $_POST['back'] ? $_POST['back'] : "back_goodsV3.php";
		header("location:".$back);
	}
}
elseif ($act == 'edit_service_return') {
    $service_id  = intval($_POST['service_id']);
	$sql = "SELECT count(*) FROM ecshop.service WHERE service_id = '{$service_id}' and facility_id in ('{$out_facility_list}') "; 
	$service_count = $db->getOne($sql);
	if($service_count==0){
		sys_msg("外包仓没有找到对应退换货订单！",1);
		exit;
	}
		
    $sql = "UPDATE service SET backfee_paiedby = '{$_POST['backfee_paiedby']}' WHERE service_id = '{$service_id}' LIMIT 1 ";
    $db->query($sql);
    $service_return = array();
    foreach($service_return_key_mapping as $key => $val){
        if($_POST[$key]) {
            $service_return[] = "('{$service_id}', '{$key}', '$_POST[$key]','carrier_info')";
        }
    }
    if (count($service_return)) {
        $sql = "DELETE FROM service_return WHERE service_id = '{$service_id}' AND return_type = 'carrier_info' ";
        $db->query($sql);
        $sql = "INSERT INTO service_return( service_id, return_name, return_value, return_type) VALUES " . join(",", $service_return);
        $db->query($sql);
        $json_result = $service_return;

        //SINRI UPDATE POSTSALE CACHe
        POSTSALE_CACHE_updateService(null,180,$service_id);
        $back_url = $_POST['back_url'] ? $_POST['back_url'] : $_SERVER['HTTP_REFERER'];
        header("Location:".$back_url);
    }
}

elseif ($act == 'remark') {
    extract($_POST);
    $sql = "SELECT * FROM service WHERE service_id = '{$service_id}' and facility_id in ('{$out_facility_list}') ";
    $service = $db->getRow($sql);
    if(empty($service)){
		sys_msg("外包仓没有找到对应退换货订单！",1);
		exit;
	}
	
    $service['log_note'] = $remark;
    $service['log_type'] = 'LOGISTIC';
    $service['is_remark'] = 1;
    service_log($service);
    make_json_response('', 0, '提交成功',$service);
    die();
}
else
{

    $csv = $_REQUEST['csv'];

    $size = 5;
    $page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
    $start = ($page - 1) * $size;

    if ($csv == null) {
        $limit = "LIMIT $size";
        $offset = "OFFSET $start";
    }

    $condition = getCondition();

    $sql = " SELECT *, s.service_id, s.order_id as service_order_id, s.back_order_id FROM service s
         INNER JOIN {$ecs->table('order_info')} o ON s.order_id = o.order_id
         WHERE s.service_type IN ('" .SERVICE_TYPE_CHANGE . "', '" . SERVICE_TYPE_BACK .
         	"') and s.facility_id in ('{$out_facility_list}')
         AND ( s.service_status = '".SERVICE_STATUS_REVIEWING."' OR ( (s.service_status = '".SERVICE_STATUS_OK."' OR s.service_status = '".SERVICE_STATUS_DENIED."') AND s.back_shipping_status = 12 ) )
         $condition 
         $limit $offset ";

    $sql_c = " SELECT COUNT(*) FROM service s
           INNER JOIN {$ecs->table('order_info')} o ON s.order_id = o.order_id
           WHERE s.service_type IN ('" .SERVICE_TYPE_CHANGE . "', '" . SERVICE_TYPE_BACK . 
           	  "') and s.facility_id in ('{$out_facility_list}')
           AND ( s.service_status = '".SERVICE_STATUS_REVIEWING."' OR ( (s.service_status = '".SERVICE_STATUS_OK."' OR s.service_status = '".SERVICE_STATUS_DENIED."') AND s.back_shipping_status = 12 ) )
           $condition 
           ";
    $back_goods_services = $db->getAllRefby($sql, array('service_id'), $service_ids, $service_id_ref);
    
    // 查询售后档案相关数据(费了很多时间)
    if(isRMATrackNeeded()){
	    $rma_tracks = array();
	    if(!empty($service_ids['service_id'])){
	        $rma_tracks = getTrackByServiceIdArray($service_ids['service_id']);
	    }
	}
    
    $count = $db->getOne($sql_c);
    
    // 获取待处理的售后服务数量
    $sql_c_pending = " SELECT COUNT(*) FROM service s
           INNER JOIN ecshop.ecs_order_info o ON s.order_id = o.order_id
           WHERE s.service_type IN ('" .SERVICE_TYPE_CHANGE . "', '" . SERVICE_TYPE_BACK ."')
           		 AND s.service_status = '".SERVICE_STATUS_REVIEWING."'
           		 AND ". party_sql('s.party_id') . " AND ".facility_sql("s.facility_id")." AND s.facility_id != '" . ECCO_O2O_FACILITY_ID . "'
            	 AND s.back_shipping_status IN (0,5)  
            	 and s.facility_id in ('{$out_facility_list}')
           ";
	
    $count_pending = $db->getOne($sql_c_pending);
    $count_var['back_shipping_status_0'] = $count_pending;
    $smarty->assign('count_var', $count_var);

    $back = $_SERVER['REQUEST_URI'];
    $back = remove_param_in_url($back, 'info');
    $pager = Pager($count, $size, $page, $back);

    foreach ($back_goods_services as $service_key => $service) {
        //售后的状态
        $back_goods_services[$service_key]['status_name'] = service_status($service);
        //把应该退回的货物列出来给他们看
		$sql = "SELECT * FROM {$ecs->table('order_goods')} where order_id = '{$service['service_order_id']}'";
        $back_goods_list = $db->getAll($sql);
        
        // 获取退货订单信息
        if(!empty($service['back_order_id']) && $service['back_order_id'] > 0){
        	$sql = "SELECT order_sn FROM ecshop.ecs_order_info WHERE order_id = '{$service['back_order_id']}' LIMIT 1";
        	$back_order_sn = $db->getOne($sql);
        	if(!empty($back_order_sn)){
        		$back_goods_services[$service_key]['back_order_info']['order_sn'] = $back_order_sn;
        		$back_goods_services[$service_key]['back_order_info']['order_id'] = $service['back_order_id'];
        		
        		// 获取退货入库的商品新、老情况信息
        		$sql = "SELECT ii.PRODUCT_ID, ii.serial_number, ii.STATUS_ID, ii.INVENTORY_ITEM_TYPE_ID, iid.QUANTITY_ON_HAND_DIFF, og.goods_id, og.style_id FROM ecshop.ecs_order_goods og
							INNER JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id AND iid.quantity_on_hand_diff > 0
							INNER JOIN romeo.inventory_item ii ON ii.inventory_item_id = iid.inventory_item_id
							WHERE og.order_id = {$service['back_order_id']}";
        		$in_stocks = $db->getAll($sql);
        	}
        }
        
        require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
        foreach($back_goods_list as $goods_key => $goods){
        	// 此售后服务申请退换货的商品数量
	        $sql = "SELECT IFNULL(SUM(amount), 0) AS sumAmount FROM ecshop.service_order_goods sog WHERE sog.is_approved = 1 AND sog.order_id = '{$service['service_order_id']}' AND sog.service_id = '{$service['service_id']}' AND sog.order_goods_id = '{$goods['rec_id']}'";
			$service_amount = $db->getOne($sql);
			$back_goods_list[$goods_key]['amount'] = $service_amount;
        	
        	// 记录每一件商品是否串号控制
        	$is_serial = getInventoryItemType($goods['goods_id']);
        	
        	// 统计入库商品的新旧情况
        	$goods_number = $service_amount;   // 该SKU商品的数量
        	$new_old_status = "";   // 初始化“商品新旧情况汇总”
        	if($is_serial == 'SERIALIZED'){  // 串号控制商品需要根据不同串号进行统计
        		$back_goods_list[$goods_key]['is_serial'] = true;	// 记录每一件商品是否串号控制
        		
        		// 统计串号控制商品的新、旧情况
        		if(!empty($in_stocks)){
        			foreach($in_stocks as $stock_key => $stock){
	        			if($goods_number <= 0){   // 该种商品统计完毕
	        				break;
	        			}elseif($stock['QUANTITY_ON_HAND_DIFF'] <= 0){	// 该库存记录已经被统计过了
	        				continue;
	        			}elseif($stock['goods_id'] == $goods['goods_id'] && $stock['style_id'] == $goods['style_id']){
	        				$new_old_status .= $stock['serial_number'];
	        				if($stock['STATUS_ID'] == 'INV_STTS_AVAILABLE'){
	        					$new_old_status .= "<strong>(全新)</strong><br/>";
	        				}elseif($stock['STATUS_ID'] == 'INV_STTS_USED'){
	        					$new_old_status .= "<strong>(二手)</strong><br/>";
	        				}elseif($stock['STATUS_ID'] == 'INV_STTS_DEFECTIVE'){
	        					$new_old_status .= "<strong>(废弃)</strong><br/>";
	        				}else{
	        					$new_old_status .= "<strong>(未知)</strong><br/>";
	        				}
	        				$in_stocks[$stock_key]['QUANTITY_ON_HAND_DIFF'] = 0;
	        				$goods_number--;
	        			}
	        		}
        		}
        		if($new_old_status == ""){
        			$new_old_status = "<strong>未入库</strong>";
        		}
        	}else{   // 非串号控制商品根据商品数量进行统计
        		$back_goods_list[$goods_key]['is_serial'] = false;	// 记录每一件商品是否串号控制
        		
        		// 统计非串号控制商品的新、旧情况
        		$new_number = 0;         // 全新数量
        		$old_number = 0;		 // 二手数量
        		$defective_number = 0;	 // 废弃数量
        		$unknown_number = 0;     // 未知状态数量
        		if(!empty($in_stocks)){
        			foreach($in_stocks as $stock_key => $stock){
	        			if($goods_number <= 0){
	        				break;
	        			}elseif($stock['QUANTITY_ON_HAND_DIFF'] <= 0){
	        				continue;
	        			}elseif($stock['goods_id'] == $goods['goods_id'] && $stock['style_id'] == $goods['style_id']){
	        				if($stock['QUANTITY_ON_HAND_DIFF'] >= $goods_number ){
	        					if($stock['STATUS_ID'] == 'INV_STTS_AVAILABLE'){
		        					$new_number += $goods_number;
		        				}elseif($stock['STATUS_ID'] == 'INV_STTS_USED'){
		        					$old_number += $goods_number;
		        				}elseif($stock['STATUS_ID'] == 'INV_STTS_DEFECTIVE'){
		        					$defective_number += $goods_number;
		        				}else{
		        					$unknown_number += $goods_number;
		        				}
		        				$in_stocks[$stock_key]['QUANTITY_ON_HAND_DIFF'] = $in_stocks[$stock_key]['QUANTITY_ON_HAND_DIFF'] - $goods_number;
		        				$goods_number = 0;
	        				}else{
	        					if($stock['STATUS_ID'] == 'INV_STTS_AVAILABLE'){
		        					$new_number += $stock['QUANTITY_ON_HAND_DIFF'];
		        				}elseif($stock['STATUS_ID'] == 'INV_STTS_USED'){
		        					$old_number += $stock['QUANTITY_ON_HAND_DIFF'];
		        				}elseif($stock['STATUS_ID'] == 'INV_STTS_DEFECTIVE'){
		        					$defective_number += $stock['QUANTITY_ON_HAND_DIFF'];
		        				}else{
		        					$unknown_number += $stock['QUANTITY_ON_HAND_DIFF'];
		        				}
	        					$in_stocks[$stock_key]['QUANTITY_ON_HAND_DIFF'] = 0;
	        					$goods_number -= $stock['QUANTITY_ON_HAND_DIFF'];
	        				}
	        			}
	        		}
        		}
        		$new_old_status .= ("<strong>全新:</strong>".$new_number."个,<strong>二手:</strong>".$old_number."个,<strong>废弃:</strong>".$defective_number."个,<strong>未知:</strong>".$unknown_number."个");
        	}
        	$back_goods_list[$goods_key]['new_old_status'] = $new_old_status;
        }
        
        $back_goods_services[$service_key]['back_goods_list'] = $back_goods_list;
        //用户返回的信息
        $back_goods_services[$service_key]['return_info'] = get_servicereturn($service['service_id']);
        //售后日志
        $back_goods_services[$service_key]['service_log'] = get_servicelog($service['service_id']);
        
        // 是否可以操作入库 
        $back_goods_services[$service_key]['can_in_storage'] = true;
        // 售后档案相关
        if(isRMATrackNeeded()){
	        if (count($rma_tracks) > 0) {
	            foreach ($rma_tracks as $track) {
	                //找到当前售后的档案
	                if ($track->serviceId == $service['service_id']) {
	                    // 获得相关属性
	                    $track->trackAttribute = getTrackAttributeArrayByTrackId($track->trackId);
	                    $back_goods_services[$service_key]['rma_tracks'][] = $track;
	                    if ($track->trackAttribute == null)  {
	                        $back_goods_services[$service_key]['can_in_storage'] = true;
	                    }
	                }
	            }
	        }
	    }
        
    }

    $smarty->assign('back_goods_services', $back_goods_services);
    $smarty->assign('pager', $pager);
    $smarty->assign('back', $back);  // updated by zjli at 2013.12.25: 返回的url已经经过处理：去除了mark参数
    $smarty->assign('can_edit_track_attribute', true);
    $smarty->assign('can_edit_track', false);

    $smarty->assign('types_array', getTrackAttributeTypeOptions());
    $facilities = explode(",", facility_mapping($_SESSION['facility_id']));
    $smarty->assign('facility_name', $facilities);
    $smarty->display('v3/back_goods.dwt');

}



function getCondition() {
     global $ecs;
    $search_text = trim($_REQUEST['search_text']);
    $search_type = trim($_REQUEST['search_type']);
    $back_shipping_status = isset($_REQUEST['back_shipping_status']) ? intval($_REQUEST['back_shipping_status']) : -1;
    $service_type = intval($_REQUEST['service_type']);

    $condition = "";
    if ($back_shipping_status != -1) {
        if($back_shipping_status == 0){
        	$condition .= " AND s.back_shipping_status IN(0,5) ";
        }else{
        	$condition .= " AND s.back_shipping_status = '{$back_shipping_status}' ";
        }
    }

    if ($service_type) {
        $condition .= " AND s.service_type = '{$service_type}' ";
    }

    if ($_REQUEST['act'] == 'search') {
    	if(in_array($search_type,array('order_sn','taobao_order_sn','consignee'))){
    		$condition .= " AND $search_type = '{$search_text}' ";
    	}elseif($search_type == 'tel'){
    		$condition .= " AND (tel = '{$search_text}' OR mobile = '{$search_text}') ";
    	}
    	elseif($search_type == 'deliver_number'){
    		$condition .= " AND s.service_id IN ( SELECT service_id FROM service_return
		                                     WHERE return_name = 'deliver_number' AND return_value = '{$search_text}' )";
    	}
    }

    # 添加party条件判断 2009/08/06 yxiang
	$condition .= ' AND '.party_sql('s.party_id') . " AND ".facility_sql("s.facility_id") ." AND s.facility_id != '" . ECCO_O2O_FACILITY_ID . "' ";
	
    return $condition;
}


