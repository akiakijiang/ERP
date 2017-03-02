<?php
/**
 * *
 * @name 订单状态相关接口（php端实现）：
 * @author zwsun zwsun@oukoo.com 2009-6-17 11:51:17
 * 
 */
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

global  $order_mixed_status_mapping;
$order_mixed_status_mapping = array(
    'order_status' => array('unconfirmed' => '未确认', 'canceled' => '已取消', 'confirmed' => '已确认', 'rejected' => '已拒收'),
    'pay_status' => array('unpaid' => '未付款', 'paid' => '已付款', 'to-refund' => '待退款', 'refunded' => '已退款'),
    'warehouse_status' => array('delivered' => '已出库', 'not-picked' => '未配货', 'picked' => '已配货', 
                                'to-return' => '取消货物待入库', 'returned' => '取消货物已入库', 're-picked' => '已重新配货', 'out-of-stock' => '缺货'),
    'shipping_status' => array('not-shipped' => '未发货', 'shipped' => '已发货', 'received' => '已收货', 'rejected' => '已拒收'),
    'invoice_status' => array('not-printed' => '未打印', 'printed' => '已打印', 'to-discard' => '待作废', 'write-off' => '已红冲', 'discarded' => '已作废', 're-printed' => '已重新打印', ),
    'pick_list_status' => array('not-printed' => '未打印', 'printed' => '已打印', 're-printed' => '已重新打印', 'to-discard' => '待作废', 'discarded' => '已作废'),
    'shipping_bill_status' => array('not-printed' => '未打印', 'printed' => '已打印', 're-printed' => '已重新打印',
                                    'to-discard' => '待作废', 'discarded' => '已作废', 're-printed' => '已重新打印', ),
    'package_status' => array('not-packed' => '未装箱打包', 'packed' => '已装箱打包', 'to-unpack' => '待拆包', 'unpacked' => '已拆包', 'repacked' => '已重新打包'),
    'adjustment_status' => array('not-adjusted' => '未修改', 'applied' => '申请修改',  'adjusted' => '已修改'),
);

global $order_mixed_status_complete_status;
$order_mixed_status_complete_status = array(
    'order_status' => array('confirmed'),
    'pay_status' => array('paid'),
    'warehouse_status' => array('delivered'),
    'shipping_status' => array('received'),
    'invoice_status' => array('printed', 're-printed'),
    'pick_list_status' => array('printed', 're-printed'),
    'shipping_bill_status' => array('printed', 're-printed'),
    'package_status' => array('packed'),
    'adjustment_status' => array('not-adjusted', 'adjusted'),
);
 
//订单混合状态相关接口
/**
 *  在订单状态历史表中插入初始记录
 *
 * @param ints $order_id
 * @param array $status
 * @param string $created_by_user_class
 */
function insert_order_mixed_status($order_id, $order_type, $created_by_user_class = 'user') {
    global $db;
    $status['order_id'] = $order_id;
    $status['order_type'] = $order_type;
    
    $status['pay_status'] = 'unpaid';
    $status['adjustment_status'] = 'not-adjusted';
    $status['order_status'] = 'unconfirmed';
    $status['warehouse_status'] = 'not-picked';
    $status['shipping_status'] = 'not-shipped';
    $status['invoice_status'] = 'not-printed';
    $status['pick_list_status'] = 'not-printed';
    $status['shipping_bill_status'] = 'not-printed';
    $status['package_status'] = 'not-packed';
    
    $status['is_current'] = 'Y';
    
    $status['created_by_user_class'] = $created_by_user_class;
    $status['created_by_user_login'] = $_SESSION['admin_name'];
    $status['created_stamp'] = date("Y-m-d H:i:s");
    $status['last_updated_stamp'] = date("Y-m-d H:i:s");
    if(!$db->autoExecute('order_mixed_status_history', $status, 'INSERT', '', 'SILENT')){
    	return false;
    }else{
    	return true;
    }
}

/**
 * 更新订单状态
 * 找出该订单原有的订单状态相关信息，更新$status中指定的部分，得到新的status
 * 插入order_mixed_status_history
 * 如果有note，将niote插入到order_mixed_status_note
 * 更新order_mixed_status中的order_mixed_status_type_id为指定的值
 *
 * @param int $order_id 订单id
 * @param array $status 要更新的状态
 * @param string $created_by_user_class 用户的类别
 * @param string $note 备注
 * @param string $note_type 备注类型
 */
function update_order_mixed_status(
    $order_id, $status, $created_by_user_class, $note = '', $note_type = null
) {
    global $db;

    $old_status = get_order_mixed_status($order_id);
    //如果没有之前insert 的记录，如果是-h，-b订单，则插入一条数据，为了解决打印拣货单无限打印bug ljzhou 2012.11.30
    if (!$old_status) {
        $sql = "select order_type_id from ecshop.ecs_order_info where order_id = {$order_id} limit 1";
    	$order_type_id = $db->getOne ( $sql );
		$allow_type = array ('RMA_EXCHANGE', 'SHIP_ONLY' );
		if (in_array ( $order_type_id, $allow_type )) {
			$status ['order_id'] = $order_id;
			$status ['is_current'] = 'Y';
			$status ['created_by_user_class'] = $created_by_user_class;
			$status ['created_by_user_login'] = ($_SESSION ['admin_name'] == '') ? 'system' : $_SESSION ['admin_name'];
			$status ['created_stamp'] = date ( "Y-m-d H:i:s" );
			$status ['last_updated_stamp'] = date ( "Y-m-d H:i:s" );
			$db->autoExecute ( 'order_mixed_status_history', $status, 'INSERT', '', 'SILENT');
			$new_status = $status;
    	} else {
    		return false;
    	}
    } else {
        $new_status = array_merge($old_status, $status);
    }
    
    unset($new_status['order_mixed_status_history_id']);
    unset($new_status['description']);
    $new_status['order_id'] = $order_id;
    $new_status['is_current'] = 'Y';
    $new_status['created_by_user_class'] = $created_by_user_class;
    $new_status['created_by_user_login'] = ($_SESSION ['admin_name'] == '') ? 'system' : $_SESSION ['admin_name'];
    $new_status['created_stamp'] = date("Y-m-d H:i:s");
    $new_status['last_updated_stamp'] = date("Y-m-d H:i:s");
    $db->autoExecute('order_mixed_status_history', array('is_current' => 'N'), 'UPDATE', " order_id = '{$order_id}' ", 'SILENT');
    $db->autoExecute('order_mixed_status_history', $new_status, 'INSERT', '', 'SILENT');
    $order_mixed_status_history_id = $db->insert_id();
    if ($note) {
        update_order_mixed_status_note($order_id, $created_by_user_class, $note, $note_type);
    }
    
    return $order_mixed_status_history_id;

}
    
/**
 * 各个部门对订单进行备注
 *
 * @param int $order_id 订单id
 * @param string $created_by_user_class 用户类型
 * @param string $note 备注
 * @param string $note_type 备注类型
 */
function update_order_mixed_status_note(
    $order_id, $created_by_user_class, $note, $note_type = null
) {
    global $db;
    
    $sql = "select order_mixed_status_history_id ".
           " from order_mixed_status ".
           " where order_id = '$order_id' and is_current = 'Y' ";
    $order_mixed_status_history_id = $db->getOne($sql);
    
    $order_mixed_status_note = array();
    $order_mixed_status_note['note'] = mysql_escape_string($note);
    $order_mixed_status_note['note_type'] = $note_type;    
    $order_mixed_status_note['order_id'] = $order_id;
    $order_mixed_status_note['order_mixed_status_history_id'] = $order_mixed_status_history_id;
    
    $order_mixed_status_note['created_by_user_class'] = $created_by_user_class;
    $order_mixed_status_note['created_by_user_login'] = ($_SESSION ['admin_name'] == '') ? 'system' : $_SESSION ['admin_name'];
    $order_mixed_status_note['created_stamp'] = date("Y-m-d H:i:s");
    return $db->autoExecute('order_mixed_status_note', $order_mixed_status_note, 'INSERT', '', 'SILENT');
}


/**
 *  获得当前订单状态及描述
 *
 * @param int $order_id
 */
function get_order_mixed_status($order_id) {
    global $db;
    $sql = "SELECT * FROM order_mixed_status WHERE order_id = '$order_id' ";
    $status = $db->getRow($sql);
    if ($status) {
        $status['description'] = get_order_mixed_status_description($status); 
    }
    return $status;
}
    
/**
 * 获得$order_id对应的订单状态历史及描述信息
 *
 * @param unknown_type $order_id
 */
function get_order_mixed_status_history($order_id) {
    global $db, $order_mixed_status_mapping;
    $sql = "SELECT  * FROM order_mixed_status_history  WHERE order_id = '{$order_id}'  ";
    
    $history_status = $db->getAllRefby($sql, array('order_mixed_status_history_id'), $refs_value, $refs, false);
    if (!$history_status) return null;
//    die();
    $sql = "SELECT * FROM order_mixed_status_note WHERE  ".db_create_in($refs_value['order_mixed_status_history_id'], 'order_mixed_status_history_id');

    $status_note = $db->getAllRefby($sql, array('order_mixed_status_history_id'), $refs_value2, $refs2, false);

    foreach ($refs['order_mixed_status_history_id'] as $order_mixed_status_history_id => $ref) {
        foreach ($ref as $key => $r) {
            $refs['order_mixed_status_history_id'][$order_mixed_status_history_id][$key]['note'] = $refs2['order_mixed_status_history_id'][$order_mixed_status_history_id];
            $note_number = count($refs2['order_mixed_status_history_id'][$order_mixed_status_history_id]);
            $refs['order_mixed_status_history_id'][$order_mixed_status_history_id][$key]['note_number'] = $note_number > 0 ? $note_number : 1;
        }
    }
    $order_mixed_status_type = array(
        'order_status',
        'pay_status',
        'warehouse_status',
        'shipping_status' ,
        'invoice_status',
        'pick_list_status',
        'shipping_bill_status',
        'package_status',
        'adjustment_status',
    );
    foreach ($history_status as $key => $status) {
        $history_status[$key]['description'] = get_order_mixed_status_description($status);
        foreach ($order_mixed_status_type as $type) {
            $history_status[$key]["{$type}_description"] = $order_mixed_status_mapping[$type][$status[$type]];
        }
    }
//    pp($history_status);
    return $history_status;
}
    
/**
 * 获得某个订单混合状态接下来的最有可能的状态及描述信息
 *
 * a 先款后货的决策过程：
 
        修改状态》付款状态》订单状态》配货单状态》仓库状态》快递面单状态》发票状态》包裹状态》物流状态
        adjustment_status > pay_status > order_status > pick_list_status > warehouse_status > shipping_bill_status > invoice_status >package_status > shipping_status
  
     b 货到付款的决策过程：
 
        修改状态》             订单状态》配货单状态》仓库状态》快递面单状态》发票状态》包裹状态》物流状态
        adjustment_status > order_status > pick_list_status > warehouse_status > shipping_bill_status > invoice_status >package_status > shipping_status
 * @param string $order_mixed_status_type_id
 */
function get_order_mixed_status_next($status) {
//    if ()
    
}

/**
 * 根据当前状态显示前台描述信息的策略：
 *
首先根据订单状态分成3类：
1 未确认订单和已确认订单：
    按以下顺序获取当前的各个状态，如果该状态还未结束（各个状态的结束请参考订单状态表），那么就显示该状态对应的描述信息
     a 先款后货的决策过程：
        修改状态》付款状态》订单状态》配货单状态》仓库状态》快递面单状态》发票状态》包裹状态》物流状态
        adjustment_status > pay_status > order_status > pick_list_status > warehouse_status > shipping_bill_status > invoice_status >package_status > shipping_status
     b 货到付款的决策过程：
        修改状态》             订单状态》配货单状态》仓库状态》快递面单状态》发票状态》包裹状态》物流状态
        adjustment_status > order_status > pick_list_status > warehouse_status > shipping_bill_status > invoice_status >package_status > shipping_status
2 取消订单
     a 先款后货
        根据是否付款(pay_status)分两种情况(unpaid,paid)显示不同的取消订单信息
     b 货到付款
        在此情形下无需判断，显示取消订单的相关描述
3 拒收订单
     a 先款后货
        根据仓库状态(warehouse_status)判断：
        退货未入库(delivered)，显示 用户拒收，待商品退回
        退货已入库(returned)显示退款状态相关状态信息
     b 货到付款
        在此情形下无需判断，显示拒收相关描述
 * @param array $status
 */
function get_order_mixed_status_description($status) {
    $description = '';
    if ($status['order_status'] == 'unconfirmed' || $status['order_status'] == 'confirmed') {
        if ($status['order_type'] == 'NON-COD') {
            $status_types = array('adjustment_status', 'pay_status', 'order_status', 'pick_list_status', 'warehouse_status', 'shipping_bill_status', 'invoice_status' , 'package_status', 'shipping_status');
        } else {
            $status_types = array('adjustment_status', 'order_status', 'pick_list_status', 'warehouse_status', 'shipping_bill_status', 'invoice_status' , 'package_status', 'shipping_status');
        }
        foreach ($status_types as $status_type) {
            if (!order_mixed_status_complete($status, $status_type)) { //如果状态没有完成的话，那么就显示这个状态对应的说明信息
                break;
            }
        }
        $description = order_mixed_status_mapping($status, $status_type);
    } elseif ($status['order_status'] == 'canceled') {
        if ($status['order_type'] == 'NON-COD') {  //按订单类型区分
            if ($status['pay_status'] == 'unpaid') {
                $description  = ''; //订单取消
            } elseif ($status['pay_status'] == 'paid') {
                $description  = ''; //订单取消，待退款
            }
        } elseif ($status['order_type'] == 'COD') {
            $description  = ''; //订单取消
        }
    } elseif ($status['order_status'] == 'rejected') {
        if ($status['order_type'] == 'NON-COD') {
            if ($status['warehouse_status'] == 'delivered') {
                $description = ''; //用户拒收，待商品退回
            } elseif ($status['warehouse_status'] == 'returned') {
                $description = ''; //退款状态相关状态信息
            }
        } elseif ($status['order_type'] == 'COD') {
            $description  = ''; //订单拒收
        }
    }
    return $description;
}

function order_mixed_status_complete($status, $status_type) {
    global $order_mixed_status_complete_status;
    if (in_array($status[$status_type], $order_mixed_status_complete_status[$status_type])) {
        return true;
    } else {
    	return false;
    }
}

function order_mixed_status_mapping($status, $status_type) {
    
    $description = $order_mixed_status_mapping[$status['order_type']][$status[$status_type]];
    return $description;
}


