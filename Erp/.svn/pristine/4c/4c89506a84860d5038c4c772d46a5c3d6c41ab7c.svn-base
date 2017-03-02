<?php

/**
 * 封装了采购入库的一些函数
 * ljzhou 2015-2-6
 */
require_once(ROOT_PATH.'admin/includes/lib_goods.php');
require_once(ROOT_PATH.'includes/lib_order.php');
 
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

//生成采购订单--事务在外面控制
function genereate_c()
{
    global $ecs, $db;
    
	//check
    $facility_id = $_POST['facility_id'];
    if (empty($facility_id)) {
        $result['message'] = "请选择收货仓库";
        return $result;
    }

    if (!party_explicit($_SESSION['party_id'])) {
        $result['message'] = "请选择具体的分公司后再下采购订单";
        return $result;
    }
    $provider_id = $_POST['provider_id'];
    $sql = "select provider_order_type from ecshop.ecs_provider where provider_id = '{$provider_id}' limit 1";
    $order_type = $db->getOne($sql);

    if (empty($provider_id) || !in_array($order_type,array('B2C','C2C','DX')) ) {
        $result['message'] = "供应商或者订单类型错误，请重新下单";
        return $result;
    }
    
    if ($_SESSION['party_id'] == PARTY_DRAGONFLY && $order_type != 'C2C') {
        $result['message'] = "DragonFly的订单必须是要c2c的";
        return $result;
    }

    $order_goods_id = intval($_POST['order_goods_id']);
    if($order_goods_id != 0){
        $result['message'] = "order_goods_id 已存在";
        return $result;
    }

    if($_SESSION['party_id'] == 65574){
    	$gymboree_vouch_file_name = $_POST['gymboree_file_name'];
		$gymboree_vouchID = $_POST['gymboree_vouchID'];
		
		if($gymboree_vouch_file_name != "-1"){
			$sql = "insert into ecshop.brand_gymboree_inoutvouch (fchrInOutVouchID,filename,is_send,create_timeStamp,upload_timeStamp)
				VALUES ('{$gymboree_vouchID}','{$gymboree_vouch_file_name}','false',NOW(),NOW());
			";
            if(!$db->query($sql))
            {
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
		}        		
    }
	
	$provider_order_sn = trim($_POST['provider_order_sn']) ;
	$provider_out_order_sn = trim($_POST['provider_out_order_sn']);
	$inventory_type = $_POST['inventory_type'] ? $_POST['inventory_type'] : '';
	$remark = $_POST['remark'] ? $_POST['remark'] : ''; 
	do {		
		$batch_order_sn = get_batch_order_sn(); //获取新订单号
        $sql = "INSERT INTO {$ecs->table('batch_order_info')}
                    (batch_order_sn, party_id, facility_id, order_time, purchase_user,in_storage_user,is_cancelled,is_in_storage,currency,
                    provider_id,purchaser,order_type,action_user,provider_order_sn, provider_out_order_sn, inventory_type, remark
                    )
                    VALUES('{$batch_order_sn}', '".$_SESSION['party_id']."', '{$facility_id}', NOW(),
                    '{$_SESSION['admin_name']}','','N','N','{$_POST['currency']}',
                    '{$_POST['provider_id']}','{$_POST['purchaser']}','{$order_type}','{$_SESSION['admin_name']}',
                    '{$provider_order_sn}' , '{$provider_out_order_sn}', '{$inventory_type}', '{$remark}'
                    )";	
		$db->query ( $sql, 'SILENT' );
		$error_no = $db->errno ();
		if ($error_no > 0 && $error_no != 1062) {
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

        $error_no = 0;
        do {
            $order_sn = get_order_sn() . "-c"; //获取新订单号
            $sql = "INSERT INTO {$ecs->table('order_info')}
                    (order_sn, order_time, order_status, pay_status, user_id, 
                    party_id, facility_id, currency, order_type_id)
                    VALUES('{$order_sn}', now(), 2, 2, '{$_SESSION['admin_id']}',                      
                    '".$_SESSION['party_id']."', '{$facility_id}', '{$_POST['currency']}', 'PURCHASE')";
            $db->query($sql, 'SILENT');
            $error_no = $db->errno();
            if ($error_no > 0 && $error_no != 1062) {
                $result['message'] = "采购订单生成失败，请重新下单";
                return $result;
            }
        } while ($error_no == 1062); //如果是订单号重复则重新提交数据
        $sqls[] = $sql;
        $order_id = $db->insert_id();
        
        
        //记录采购订单信息
        $is_serial = get_goods_item_type($goods_id) == "SERIALIZED" ? 'Y' : 'N';
        if($is_serial == 'Y' and $is_serial_in_batch == 'N'){
            $is_serial_in_batch = 'Y';
        }
        $sql = "INSERT INTO romeo.purchase_order_info
                    (order_id, purchase_paid_amount, purchaser, order_type, is_serial)
                    VALUES('{$order_id}', '{$purchase_paid_amount}', '{$_POST['purchaser']}', '{$order_type}', '{$is_serial}')";
        if(false == $db->query($sql, 'SILENT')){
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
        }
        $db->insert_id();
        
        //将采购订单号插入到此批次采购订单映射表中 
        $sql = "INSERT INTO {$ecs->table('batch_order_mapping')}
                    (batch_order_id, order_id)
                    VALUES('{$batch_order_id}', '{$order_id}')";
        if(false == $db->query($sql, 'SILENT')){
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
        }
        $batch_order_mapping_id = $db->insert_id();
                
        //金宝贝特殊处理
        if($_SESSION['party_id'] == 65574){
        	$sql = "insert into order_attribute (order_id,attr_name,attr_value) 
        			values ('{$order_id}','Gymboree_Warehouse_ID','{$_POST['gymboree_warehouse']}'),
        			('{$order_id}','gymboree_vouchID','{$gymboree_vouchID}'),
        			('{$order_id}','gymboree_vouch_detailID','{$_POST['gymboree_vouch_detailID'][$goods_id_key]}')";
            if(false == $db->query($sql)){
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
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
        }
        $order_goods_id = $db->insert_id();
        $sqls[] = $sql;

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
        $sql = "SELECT user_id FROM oukoo_universal.ok_user WHERE user_name = '{$_SESSION['admin_name']}'";
        $uuid = $db->getOne($sql);
        $sql = "SELECT goods_style_id FROM {$ecs->table('goods_style')} WHERE goods_id = '{$goods_id}' AND style_id = '{$style_id}' ";
        $goods_style_id = $db->getOne($sql);
        $sql = "INSERT INTO PRICE_TRACKER.PROVIDER_PRICE (GOODS_ID, goods_style_id, PROVIDER_ID, SUPPLY_PRICE, CREATED_BY, CREATED_DATETIME) VALUE ('$goods_id', '$goods_style_id', '{$_POST['provider_id']}', '$purchase_paid_amount', '$uuid', NOW())";
        if(false == $db->query($sql)){
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
        }
    }

    if($is_serial_in_batch == 'Y'){
        $sql = "UPDATE ecshop.ecs_batch_order_info set is_serial = 'Y'  where batch_order_id = '{$batch_order_id}'";
        if(false == $db->query($sql)){
            $result['message'] = "采购订单生成失败，请重新下单";
            return $result;
        }
    }
    $result['batch_order_id'] = $batch_order_id;

    return $result;
}

/**
 * 格式化中粮的采购订单数据
 */
function format_cofco_purchase_params($data) {
	$len = count($data['ret_goods_id']);
	for($i=0;$i<$len;$i++) {
		$_POST['rebate'][] = 0;
		$_POST['dispatchListId'][] = '';
		$_POST['customized'][] = '';
	}
	$_POST['goods_id'] = $data['ret_goods_id'];
	$_POST['style_id'] = $data['ret_style_id'];
	$_POST['goods_number'] = $data['ret_amount'];
	$_POST['purchase_paid_amount'] = $data['goods_price'];
	$_POST['purchase_added_fee'] = $data['goods_rate'];
	$_POST['order_type'] = '';
	$_POST['purchaser'] = $_SESSION['admin_name'];
	$_POST['provider_id'] = $data['change_provider_id'];
	$_POST['generate_type'] = 'c';
	$_POST['order_id'] = 0;
	$_POST['party_id'] = $_SESSION['party_id'];
	$_POST['facility_id'] = $data['change_facility_id'];
	$_POST['benefit'] = 0;
	$_POST['currency'] = 'RMB';
	$_POST['provider_order_sn'] = '';
	$_POST['provider_out_order_sn'] = '';
	$_POST['inventory_type'] = 'purchase';
	$_POST['back'] = 'generate_cofco_gt.php';
	$_POST['remark'] = '';
	$_POST['status_id'] = $data['ret_status_id'][0];//检查前端一致性
	
	return $_POST;
}
 
?>
