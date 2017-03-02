<?php
define('IN_ECS', true);
require ('includes/init.php');
require ('includes/lib_order.php');
require ('includes/lib_goods.php');
admin_priv('purchase_order');
require_once ("function.php");
require_once (ROOT_PATH . "includes/lib_order.php");
require_once(ROOT_PATH. 'includes/debug/lib_log.php');

$result = array('err_no' => '0', 'message' =>'');
if(in_array($_REQUEST['generate_type'], array('destroy_c','c','over_c'))){
    $result = $_REQUEST['generate_type'](date("Y-m-d H:i:s"));
}else{
    $result['err_no'] = '11';
    $result['message'] = "Invalid action[".$_REQUEST['generate_type']."]!";
}

if($result['err_no'] != '0')
    sys_msg("ERROR[".$result['err_no']."]:".$result['message']);
else{
    $back = $_REQUEST['back'] ? $_REQUEST['back']:'generate_c_orderV3.php';
    header("location: {$back}");
}


//废除采购订单
function destroy_c($action_time)
{
    $result = array('err_no' => 0, 'message' => '');
	global $db, $ecs;
    $db->start_transaction();
    $order_id = intval($_REQUEST['order_id']);
    // 修改批量采购订单的映射表 ljzhou
    $sql = "UPDATE {$ecs->table('batch_order_mapping')} SET is_cancelled = 'Y' where order_id = '{$order_id}' limit 1";
    if(!$db->query($sql)){
        $db->rollback();
        $result['err_no'] = 1;
        $result['message'] = "采购订单废除失败，请重新废除";
        return $result;
    }
    
    $sql = "UPDATE romeo.purchase_order_info SET cancel_time = now() where order_id = '{$order_id}' limit 1";
    if(!$db->query($sql)){
        $db->rollback();
        $result['err_no'] = 1;
        $result['message'] = "采购订单废除失败purchase_order_info，请重新废除";
        return $result;
    }
    
    // 判断某批量入库的采购订单是否全部被废除了
    $sql = "
        SELECT om.batch_order_id from {$ecs->table('batch_order_mapping')} om
        inner join {$ecs->table('batch_order_mapping')} om1 ON om.batch_order_id = om1.batch_order_id
        where om1.is_cancelled = 'N' and om.order_id = {$order_id}
        limit 1
       ";
   
    $batch_order_id = $db->getOne($sql);
    if(empty($batch_order_id)) {
    	$sql = "SELECT om.batch_order_id from {$ecs->table('batch_order_mapping')} om where om.order_id = {$order_id} limit 1";
    	$batch_order_id = $db->getOne($sql);
    	$sql = "UPDATE {$ecs->table('batch_order_info')} set is_cancelled = 'Y' where batch_order_id = {$batch_order_id} limit 1";
        if(!$db->query($sql)){
            $db->rollback();
            $result['err_no'] = 2;
            $result['message'] = "采购订单废除失败，请重新废除";
            return $result;
        }
    }
    // 判断某批量入库的采购订单经过这次废除是否没有商品可以入了
    $sql = "
        SELECT om.batch_order_id from {$ecs->table('batch_order_mapping')} om
        inner join {$ecs->table('batch_order_mapping')} om1 ON om.batch_order_id = om1.batch_order_id
        where om1.is_cancelled = 'N' and om1.is_over_c = 'N' and om1.is_in_storage = 'N' and om.order_id = {$order_id}
        limit 1
       ";
   
    $batch_order_id = $db->getOne($sql);
    if(empty($batch_order_id)) {
    	$sql = "SELECT om.batch_order_id from {$ecs->table('batch_order_mapping')} om where om.order_id = {$order_id} limit 1";
    	$batch_order_id = $db->getOne($sql);
    	$sql = "UPDATE {$ecs->table('batch_order_info')} set is_in_storage = 'Y' where batch_order_id = {$batch_order_id} limit 1";
        if(!$db->query($sql)){
            $db->rollback();
            $result['err_no'] = 3;
            $result['message'] = "采购订单废除失败，请重新废除";
            return $result;
        }
    }
    
    $db->commit();
    return $result;
}

//完结采购订单
function over_c($action_time)
{
    $result = array('err_no' => 0, 'message' => '');
	global $db, $ecs;
    $db->start_transaction();
   	$order_id = intval($_REQUEST['order_id']);
	
	 $sql = "UPDATE {$ecs->table('order_info')}
		SET order_status = '5' 
		WHERE order_id = '{$order_id}'";
    if(!$db->query($sql)){
        $db->rollback();
        $result['err_no'] = 1;
        $result['message'] = "采购订单完结失败，请重新完结";
        return $result;
    }

    // 修改批量采购订单的映射表 
    $sql = "UPDATE {$ecs->table('batch_order_mapping')} SET is_over_c = 'Y' where order_id = '{$order_id}' limit 1";
    $db->query($sql);
    if(!$db->query($sql)){
        $db->rollback();
        $result['err_no'] = 2;
        $result['message'] = "采购订单完结失败，请重新完结";
        return $result;
    }
    
    $sql = "UPDATE romeo.purchase_order_info SET over_time = now() where order_id = '{$order_id}' limit 1";
    if(!$db->query($sql)){
        $db->rollback();
        $result['err_no'] = 1;
        $result['message'] = "采购订单完结失败purchase_order_info，请重新废除";
        return $result;
    }

    // 判断某批量入库的采购订单是否全部被完结了
    $sql = "
        SELECT om.batch_order_id from {$ecs->table('batch_order_mapping')} om
        inner join {$ecs->table('batch_order_mapping')} om1 ON om.batch_order_id = om1.batch_order_id
        where om1.is_over_c = 'N' and om.order_id = {$order_id}
        limit 1
       ";
    $batch_order_id = $db->getOne($sql);
    if(empty($batch_order_id)) {
    	$sql = "SELECT om.batch_order_id from {$ecs->table('batch_order_mapping')} om where om.order_id = {$order_id} limit 1";
    	$batch_order_id = $db->getOne($sql);
    	$sql = "UPDATE {$ecs->table('batch_order_info')} set is_over_c = 'Y' where batch_order_id = {$batch_order_id} limit 1";
        if(!$db->query($sql)){
            $db->rollback();
            $result['err_no'] = 3;
            $result['message'] = "采购订单完结失败，请重新完结";
            return $result;
        }
    }
    // 判断某批量入库的采购订单经过这次完结是否没有商品可以入了
    $sql = "
        SELECT om.batch_order_id from {$ecs->table('batch_order_mapping')} om
        inner join {$ecs->table('batch_order_mapping')} om1 ON om.batch_order_id = om1.batch_order_id
        where om1.is_cancelled = 'N' and om1.is_over_c = 'N' and om1.is_in_storage = 'N' and om.order_id = {$order_id}
        limit 1
       ";
   
    $batch_order_id = $db->getOne($sql);
    if(empty($batch_order_id)) {
    	$sql = "SELECT om.batch_order_id from {$ecs->table('batch_order_mapping')} om where om.order_id = {$order_id} limit 1";
    	$batch_order_id = $db->getOne($sql);
    	$sql = "UPDATE {$ecs->table('batch_order_info')} set is_in_storage = 'Y' where batch_order_id = {$batch_order_id} limit 1";
        if(!$db->query($sql)){
            $db->rollback();
            $result['err_no'] = 4;
            $result['message'] = "采购订单完结失败，请重新完结";
            return $result;
        }
    }
    $db->commit();
    return $result;
}


//jwang抄袭了此函数，如有改动，请告知他，或者你也可以帮他一起改掉，function.php generate_order()，如果能把两个代码合并掉，那就再好不过了 O(∩_∩)
//生成采购订单
function c($action_time)
{
    global $ecs, $db;
    
    $result = array('err_no' => 0, 'message' => '');
    $is_debug = true;
	//check
    $facility_id = $_POST['facility_id'];
    if (empty($facility_id)) {
        $result['err_no'] = 1;
        $result['message'] = "请选择收货仓库";
        return $result;
    }

    if (!party_explicit($_SESSION['party_id'])) {
        $result['err_no'] = 1;
        $result['message'] = "请选择具体的分公司后再下采购订单";
        return $result;
    }
    $provider_id = $_POST['provider_id'];
    $sql = "select provider_order_type from ecshop.ecs_provider where provider_id = '{$provider_id}' limit 1";
    $order_type = $db->getOne($sql);

    if (empty($provider_id) || !in_array($order_type,array('B2C','C2C','DX')) ) {
    	$result['err_no'] = 1;
        $result['message'] = "供应商或者订单类型错误，请重新下单";
        return $result;
    }
    
    if ($_SESSION['party_id'] == PARTY_DRAGONFLY && $order_type != 'C2C') {
        $result['err_no'] = 1;
        $result['message'] = "DragonFly的订单必须是要c2c的";
        return $result;
    }

    $order_goods_id = intval($_REQUEST['order_goods_id']);
    if($order_goods_id != 0){
        $result['err_no'] = 1;
        $result['message'] = "order_goods_id 已存在";
        return $result;
    }

    $db->start_transaction();        //开始事务

    if($_SESSION['party_id'] == 65574){
    	$gymboree_vouch_file_name = $_POST['gymboree_file_name'];
		$gymboree_vouchID = $_POST['gymboree_vouchID'];
		
		if($gymboree_vouch_file_name != "-1"){
			$sql = "insert into ecshop.brand_gymboree_inoutvouch (fchrInOutVouchID,filename,is_send,create_timeStamp,upload_timeStamp)
				VALUES ('{$gymboree_vouchID}','{$gymboree_vouch_file_name}','false',NOW(),NOW());
			";
            if(!$db->query($sql))
            {
                $db->rollback();
                $result['err_no'] = 2;
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
		}        		
    }
	
	$provider_order_sn = trim($_POST['provider_order_sn']) ;
	$provider_out_order_sn = trim($_POST['provider_out_order_sn']);
	$inventory_type = $_POST['inventory_type'] ? $_POST['inventory_type'] : '';
	$remark = $_POST['remark'] ? $_POST['remark'] : ''; 
    // 生成批次采购订单信息 ljzhou 2012.11.29
//   修改采购的逻辑，一个订单对应多个商品 2015-12-23
	do {
	 $batch_order_sn = get_batch_order_sn(); //获取新订单号
      $sql = "INSERT INTO {$ecs->table('batch_order_info')}
                    (batch_order_sn, party_id, facility_id, arrive_time,order_time, purchase_user,in_storage_user,is_cancelled,is_in_storage,currency,
                    provider_id,purchaser,order_type,action_user,provider_order_sn, provider_out_order_sn, inventory_type, remark
                    )
                    VALUES('{$batch_order_sn}', '".$_SESSION['party_id']."', '{$facility_id}', '{$_REQUEST['arrive_time_sn']}' ,NOW(),
                    '{$_SESSION['admin_name']}','','N','N','{$_REQUEST['currency']}',
                    '{$_POST['provider_id']}','{$_POST['purchaser']}','{$order_type}','{$_SESSION['admin_name']}',
                    '{$provider_order_sn}' , '{$provider_out_order_sn}', '{$inventory_type}', '{$remark}'
                    )";
		$db->query ( $sql, 'SILENT' );
		$error_no = $db->errno ();
		if ($error_no > 0 && $error_no != 1062) {
            $db->rollback();
            $result['err_no'] = 3;
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
		}
	} while ( $error_no == 1062 ); //如果是订单号重复则重新提交数据	
    $batch_order_id = $db->insert_id();
    
    
    $total_pay = 0;    // 计算总采购费
    $rebate_strategy_data = array();    // 用于返利策略分配的数据
    $is_serial_in_batch = 'N';
    
    // 支持2手
    if(isset($_POST['status_id']) && $_POST['status_id'] == 'INV_STTS_USED') {
    	$status_id = 'INV_STTS_USED';
    } else {
    	$status_id = 'INV_STTS_AVAILABLE';
    }
    
     $error_no = 0;
        do {
            $order_sn = get_order_sn() . "-c"; //获取新订单号
            $sql = "INSERT INTO {$ecs->table('order_info')}
                    (order_sn, order_time, order_status, pay_status, user_id, 
                    party_id, facility_id, currency, order_type_id)
                    VALUES('{$order_sn}', '$action_time', 2, 2, '{$_SESSION['admin_id']}',                      
                    '".$_SESSION['party_id']."', '{$facility_id}', '{$_REQUEST['currency']}', 'PURCHASE')";
            $db->query($sql, 'SILENT');
            $error_no = $db->errno();
            if ($error_no > 0 && $error_no != 1062) {
                $db->rollback();
                $result['err_no'] = 4;
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
        } while ($error_no == 1062); //如果是订单号重复则重新提交数据
        $sqls[] = $sql;
        $order_id = $db->insert_id();
        
         //将采购订单号插入到此批次采购订单映射表中 
        $sql = "INSERT INTO {$ecs->table('batch_order_mapping')}
                    (batch_order_id, order_id)
                    VALUES('{$batch_order_id}', '{$order_id}')";
        if(false == $db->query($sql, 'SILENT')){
            $db->rollback();
            $result['err_no'] = 6;
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
        }
        $batch_order_mapping_id = $db->insert_id();
        
    
//-----------------------------------------------------------------------------------------------------------    
    foreach ($_POST['goods_id'] as $goods_id_key => $goods_id) {
        //分别添加不同代码
        $goods_id = intval($_POST['goods_id'][$goods_id_key]);
        $style_id = intval($_POST['style_id'][$goods_id_key]);
        $goods_number = intval($_POST['goods_number'][$goods_id_key]);
        $customized = $_POST['customized'][$goods_id_key];
        $purchase_paid_amount = $_POST['purchase_paid_amount'][$goods_id_key];
        $purchase_added_fee = $_POST['purchase_added_fee'][$goods_id_key];
        $rebate = $_POST['rebate'][$goods_id_key];

        if (!$goods_id || !$goods_number) { continue; }
        if ($rebate > ($purchase_paid_amount*$goods_number)) continue;
        
        $pay = $purchase_paid_amount*$goods_number;
        $total_pay += $pay;
        
        //记录采购订单信息
        $is_serial = get_goods_item_type($goods_id) == "SERIALIZED" ? 'Y' : 'N';
        if($is_serial == 'Y' and $is_serial_in_batch == 'N'){
            $is_serial_in_batch = 'Y';
        }
               
       
        
       
                
        //金宝贝特殊处理
        if($_SESSION['party_id'] == 65574){
        	$sql = "insert into order_attribute (order_id,attr_name,attr_value) 
        			values ('{$order_id}','Gymboree_Warehouse_ID','{$_POST['gymboree_warehouse']}'),
        			('{$order_id}','gymboree_vouchID','{$gymboree_vouchID}'),
        			('{$order_id}','gymboree_vouch_detailID','{$_POST['gymboree_vouch_detailID'][$goods_id_key]}')";
            if(false == $db->query($sql)){
                $db->rollback();
                $result['err_no'] = 7;
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
        }
        //康贝特殊处理
        if($_SESSION['party_id'] == 65586){
        	$combi_production_date = trim($_POST['combi_production_date'][$goods_id_key]);
        	$combi_batch_sn = trim($_POST['combi_batch_sn'][$goods_id_key]);
        	$sql = "insert into order_attribute (order_id,attr_name,attr_value) 
        			values 
        			('{$order_id}','combi_production_date','{$combi_production_date}'),
        			('{$order_id}','combi_batch_sn','{$combi_batch_sn}')";
            if(false == $db->query($sql)){
                $db->rollback();
                $result['err_no'] = 8;
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
        }
        

        // 返利策略数据
        $rebate_strategy_data[] = array('order_id' => $order_id, 'pay' => $pay);

        $sql = "SELECT * FROM {$ecs->table('goods')} WHERE goods_id = '$goods_id'";
        $goods = $db->getRow($sql);

        if ($style_id > 0) {
            $sql = "SELECT *, IF (gs.goods_color = '', s.color, gs.goods_color) AS color FROM {$ecs->table('goods_style')} gs, {$ecs->table('style')} s WHERE gs.goods_id = '{$goods['goods_id']}' AND gs.style_id = s.style_id AND s.style_id = '{$style_id}'";
            $style = $db->getRow($sql);
            $goods['goods_name'] .= " {$style['color']}";
            $goods['shop_price'] = $style['style_price'];
        }

        //对order_goods表数据进行修改
        $goods_name = addslashes($goods['goods_name']);
        $sql = "INSERT INTO {$ecs->table('order_goods')} (order_id, goods_id, goods_name, goods_number, goods_price, style_id, customized, added_fee, status_id) 
        					VALUES('{$order_id}', '{$goods_id}', '{$goods_name}', '$goods_number', '{$goods['shop_price']}', {$style_id}, '{$customized}', '{$purchase_added_fee}', '{$status_id}')";
        if(false == $db->query($sql)){
            $db->rollback();
            $result['err_no'] = 9;
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
        }
        $order_goods_id = $db->insert_id();
        $sqls[] = $sql;



       $sql = "INSERT INTO romeo.purchase_order_info
                    (order_id, purchase_paid_amount, purchaser, order_type, is_serial,order_goods_id)
                    VALUES('{$order_id}', '{$purchase_paid_amount}', '{$_POST['purchaser']}', '{$order_type}', '{$is_serial}','{$order_goods_id}')";
       if(false == $db->query($sql, 'SILENT')){
            $db->rollback();
            $result['err_no'] = 5;
            $result['message'] = "采购订单生成失败5，请重新下单";
            return $result;
       } 
       $db->insert_id();
        
        //插入返利
        /*$sql  = "INSERT INTO `purchase_order_applied_rebate` (`order_id`, `applied_rebate`) VALUES ('$order_id', '$rebate')";
        if(false == $db->query($sql)){
            $db->rollback();
            $result['err_no'] = 10;
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
        }
        $sqls[] = $sql;*/

        // 把供价记录到价格跟踪系统中去
        /*$sql = "SELECT user_id FROM oukoo_universal.ok_user WHERE user_name = '{$_SESSION['admin_name']}'";
        $uuid = $db->getOne($sql);
        $sql = "SELECT goods_style_id FROM {$ecs->table('goods_style')} WHERE goods_id = '{$goods_id}' AND style_id = '{$style_id}' ";
        $goods_style_id = $db->getOne($sql);
        $sql = "INSERT INTO PRICE_TRACKER.PROVIDER_PRICE (GOODS_ID, goods_style_id, PROVIDER_ID, SUPPLY_PRICE, CREATED_BY, CREATED_DATETIME) VALUE ('$goods_id', '$goods_style_id', '{$_POST['provider_id']}', '$purchase_paid_amount', '$uuid', NOW())";
        if(false == $db->query($sql)){
            $db->rollback();
            $result['err_no'] = 11;
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
        }*/
        
    }
//-----------------------------------------------------------------------------------------------------------
    if($is_serial_in_batch == 'Y'){
        $sql = "UPDATE ecshop.ecs_batch_order_info set is_serial = 'Y'  where batch_order_id = '{$batch_order_id}'";
        if(false == $db->query($sql)){
            $db->rollback();
            $result['err_no'] = 12;
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
        }
    }

    $db->commit();
    return $result;
}
?>