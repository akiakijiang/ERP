<?php
/**
 * 借机动作部分
 */
define('IN_ECS', true);

require('includes/init.php');
require('includes/lib_order.php');

admin_priv('purchase_order');
require_once("function.php");
require_once(ROOT_PATH.'includes/lib_order.php');

$back = $_REQUEST['back'];
$goods_id = trim($_REQUEST['goods_id']);
$style_id = trim($_REQUEST['style_id']);
$serial_number   = $_REQUEST['serial_number'];
$type     = $_REQUEST['type'];
$goods_number = trim($_REQUEST['goods_number']);
$party_id = trim($_REQUEST['party_id']);
$facility_id = trim($_REQUEST['facility_id']);
$currency = "RMB";
$order_type = trim($_REQUEST['order_type']);
$status_id   = trim($_REQUEST['status_id']);
$barcode = trim($_REQUEST['barcode']);
$order_id = $_REQUEST['order_id'];
$act = $_REQUEST['act'];

//pp($_REQUEST);die();
if (!$goods_id || !$goods = $db->getRow("SELECT `goods_id`, `goods_name` FROM {$ecs->table('goods')} WHERE `goods_id` = '{$goods_id}'"))
{
    sys_msg('没有此类商品！');
}

Qlog::log(' $act:'.$act);

if ($act == 'act_borrow')
{
	if (!empty($serial_number) && (!checkHasSerialNumber($serial_number)))
    {
        sys_msg('该串号已经不在库存里！');
    }
    
	// 数量的检查
	$stock_quantity = get_product_stock_quantity($goods_id,$style_id,$serial_number,$facility_id,$status_id,$order_type);
	Qlog::log('h_borrow $stock_quanity:'.$stock_quantity.' $goods_number:'.$goods_number);
	if($stock_quantity < $goods_number) {
	    Qlog::log('本次借机'.$goods_number.'个， 大于该商品的库存数：'.$stock_quantity.' 商品信息：order_type:'.$order_type.' status_id:'.$status_id.' goods_id:'.$goods_id.' style_id:'.$style_id.' facility_id:'.$facility_id.' serial_number:'.$serial_number);
		sys_msg('本次借机'.$goods_number.'个， 大于该商品的库存数：'.$stock_quantity.' 请重新编辑数量，商品信息：order_type:'.$order_type.' status_id:'.$status_id.' goods_id:'.$goods_id.' style_id:'.$style_id.' facility_id:'.$facility_id.' serial_number:'.$serial_number);
	}
	
	$result = array();
	try{
		do {
			// 加锁
            $lock_name = "out_sn_{$barcode}";
            $lock_file_name = get_file_lock_path($lock_name, 'delivery');
            $lock_file_point = fopen($lock_file_name, "w+");
            $max_sleep = 5;
            $has_outsn_lock = false;
            $wouldblock = false;
            while ($max_sleep > 0) {
                if (flock($lock_file_point, LOCK_EX|LOCK_NB, $wouldblock)) {
                    touch($lock_file_name);
                    $has_outsn_lock = true;
                    break;
                }
                sleep(1);
                $max_sleep--;
            }
            if (!$has_outsn_lock) {
                $result['error'] = '其他人正在操作出库动作，请稍后再试 barcode:'.$barcode;
                break;
            }
	            
		    $goods_name = $goods['goods_name'];
		    $added_fee = floatval($_REQUEST['added_fee']);
		    $provider_id = (int)$_REQUEST['provider_id'];
		    $consignee = $_REQUEST['p_name'];
		
		    // 备注
		    $postscript = $_REQUEST['postscript'];
	        $postscript = "S内部人员借机 || ".$postscript;
	        $order_type_id = 'BORROW';
		
		    if($style_id) {
		        $color  = $db->getOne("SELECT IF(gs.goods_color = '' OR gs.goods_color IS NULL, s.color, gs.goods_color) AS color FROM {$ecs->table('style')} s
				 LEFT JOIN {$ecs->table('goods_style')} gs ON gs.goods_id = '{$goods_id}' AND gs.style_id = s.style_id
				 WHERE s.style_id = '$style_id' LIMIT 1");
		        if($color) $goods_name = $goods['goods_name']." {$color}";
		    }
		    
		    //往order_info插入一条记录
		    $error_no = 0;
		    $now = date("Y-m-d H:i:s");
		    do {
		        $order_sn = get_order_sn() . "-gh"; //获取新订单号
		        $sql = "
					INSERT INTO {$ecs->table('order_info')}
					(order_sn, order_time, consignee, shipping_time, user_id, postscript, 
					order_status, party_id, facility_id, currency, order_type_id) 
					VALUES 
					('{$order_sn}', '{$now}', '{$consignee}', UNIX_TIMESTAMP(NOW()), '{$provider_id}', 
					'{$postscript}', 1, '{$party_id}', '{$facility_id}', '{$currency}', '{$order_type_id}')
				";
		        $db->query($sql, 'SILENT');
		        $error_no = $db->errno();
		        if ($error_no > 0 && $error_no != 1062) {
		            $result['error'] = $db->errorMsg();
		            break 2;
		        }
		    } while ($error_no == 1062); //如果是订单号重复则重新提交数据
		    $order_id = $db->insert_id();
		
		    //往order_goods插入一条记录
		    $goods_name = addslashes($goods_name);
		    $sql = "
				INSERT INTO {$ecs->table('order_goods')} 
				(order_id, goods_id, style_id, goods_name, goods_number, goods_price, added_fee) 
				VALUES 
				('{$order_id}', '{$goods_id}', '{$style_id}', '{$goods_name}', '{$goods_number}', '{$order_amount}', '{$added_fee}')
			";
		    $db->query($sql);
		    $order_goods_id = $db->insert_id();
		
	    	$predict_return_time = $_REQUEST['date'];
	    	//往ecshop.ecs_borrow_history中添加一条记录
	    	$sql = "
	    	     INSERT INTO ecshop.ecs_borrow_history 
	    	     (order_id, operate_time, predict_return_time, operator)
	    	     VALUES
	    	     ('{$order_id}', '{$now}', '{$predict_return_time}', '{$_SESSION['admin_name']}')";
	    	$db->query($sql);
		    
		    // romeo code:
		    // 出库
		    $fromStatusId = $status_id;
		    include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
		
	        $res = createDeliverInventoryTransaction('ITT_BORROW', 
	                                          array('goods_id'=>$goods_id, 'style_id'=>$style_id), 
	                                          $goods_number, $serial_number, $order_type, null, $order_id, 
	                                          $fromStatusId, '', $order_goods_id, $facility_id);
		} while(false);
	}catch(Exception $e) {
		$result['error'] .= $e->getMessage();
	}
	
    flock($lock_file_point, LOCK_UN);
    fclose($lock_file_point);
    
	if(empty($result['error'])) {
		$result['info'] = '该货已经借机给内部员工，等待归还的货物入仓库';
	}
}
else if($act == 'act_renew'){
	$renew_order_id = $_REQUEST['renew_order_id'];
	$renew_time = $_REQUEST['date'];
	$renew_time .= ' 00:00:00';
	$renew_user = $_SESSION['admin_name'];
	$now = date("Y-m-d H:i:s");
	
	$sql = "
	    SELECT 1
	    FROM ecshop.ecs_borrow_history
	    WHERE order_id = '{$renew_order_id}' and predict_return_time = '{$renew_time}' limit 1";
	$res = $db -> getOne($sql);
	
	//若表中无数据，则添加。
	if(!$res){
		$sql = "
	        INSERT INTO ecshop.ecs_borrow_history
	        (order_id,operate_time,predict_return_time,operator)
	        values('{$renew_order_id}','{$now}','{$renew_time}','{$renew_user}')
	    ";
	    $res = $db -> query($sql);
	} else {
		sys_msg('订单该还机时间已经存在，请填写其他时间！');
	}
	
	$result['info'] = '续借成功';
}

$smarty->assign('party_id', $party_id);
$smarty->assign('facility_id', $facility_id);
$smarty->assign('order_type', $order_type);
$smarty->assign('status_id', $status_id);
$smarty->assign('barcode', $barcode);
$smarty->assign('order_id', $order_id);
$smarty->assign('back', $back);
$smarty->assign('type', $type);
$smarty->assign('result', $result);
$smarty->assign('goods_number', $goods_number);
$smarty->assign('serial_number', $serial_number);
$smarty->assign('goods', $goods);
$smarty->assign('currencys', get_currency_style('RMB')); //币种数组
$smarty->display('oukooext/h_goods_gys.htm');

?>
