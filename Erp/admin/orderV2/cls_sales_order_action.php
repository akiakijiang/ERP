<?php 
abstract class ClsSalesOrderAction {
    function __construct($id_map) {
        $this->order_id_ = $id_map['order_id'];
        $this->party_id_ = $id_map['party_id'];
    }

    function GenerateSQL($data_map, & $error_info) {
        //
        //party_priv($order_party_id);

        //初始化插入记录
        global $db, $ecs;
        $sql = "SELECT order_status, pay_status, shipping_status, invoice_status, shortage_status "." FROM {$ecs->table('order_info')} WHERE order_id = {$this->order_id_} ";
        $row = $db->getRow($sql);
        if (empty($row)) {
            $error_info = array('err_no' => 3, 'message' => '订单不存在');
            return false;
        }

        $this->data_map_for_insert_order_action_['action_note'][] = '【'.ClsSalesOrderAction::GetActionNameByID($this->action_id_).'】';
        if ($data_map['note_content']) {
            if ($data_map['is_shipping_note']) {
                $this->data_map_for_insert_order_action_['note_type'] = 'SHIPPING';
            } else {
                $this->data_map_for_insert_order_action_['note_type'] = null;
            }
            $sql = "select 1 from ecshop.order_attribute where order_id = {$this->order_id_} and attr_name = 'TAOBAO_SELLER_MEMO' limit 1";
            $data = $db->getOne($sql);
            if (!$data) {
                $this->sqls_[] = "insert into ecshop.order_attribute (order_id, attr_name, attr_value) value ({$this->order_id_}, 'TAOBAO_SELLER_MEMO', '{$data_map['note_content']}')";
            } else {
                $this->sqls_[] = "update ecshop.order_attribute set attr_value = CONCAT(attr_value, ';' ,'{$data_map['note_content']}') 
                            where order_id = {$this->order_id_} and attr_name = 'TAOBAO_SELLER_MEMO'";
            }
            $this->data_map_for_insert_order_action_['action_note'][] = $data_map['note_content'];
        }
        return $this->ConcreteAction($data_map, $error_info);
    }
    abstract protected function ConcreteAction($data_map, & $error_info);
    abstract public function ModifyViaRomeo();

    static function GetActionNameByID($id){
        if (array_key_exists($id, ClsSalesOrderAction::$action_info_map)) {
            return ClsSalesOrderAction::$action_info_map[$id]['action_name'];
        } else {
            return '无效操作';
        }
    }
    static function GetActionAttrListForUpdateByID($id){
        if (array_key_exists($id, ClsSalesOrderAction::$action_info_map)) {
            $cls_name = ClsSalesOrderAction::$action_info_map[$id]['cls_name'];
            return eval('return $cls_name::GetAllAttrListForUpdate();');
        } else {
            return array();
        }
    }
    static function GenerateActionInsByID($id, $id_map, &$error_info){
        if (!array_key_exists($id, ClsSalesOrderAction::$action_info_map)) {
            $error_info = array('err_no' => 2, 'message' => '订单操作id'.$id.'不存在');
            return null;
        } else {
            return new ClsSalesOrderAction::$action_info_map[$id]['cls_name']($id_map);
        }
    }

    private static $action_info_map = array(
        'add_note' => array('action_name' => '添加备注', 'cls_name' => 'ClsSalesOrderActionAddNote'), 
        'order_confirm' => array('action_name' => '确认订单', 'cls_name' => 'ClsSalesOrderActionConfirm'), 
        'order_cancel' => array('action_name' => '取消订单', 'cls_name' => 'ClsSalesOrderActionCancel'), 
        'rec_confirm' => array('action_name' => '收货确认', 'cls_name' => 'ClsSalesOrderActionRecieveConfirm'), 
        'reject' => array('action_name' => '拒收', 'cls_name' => 'ClsSalesOrderActionReject'), 
        'order_recover' => array('action_name' => '恢复订单', 'cls_name' => 'ClsSalesOrderActionRecover'), 
        'order_recover_force' => array('action_name' => '强制恢复', 'cls_name' => 'ClsSalesOrderActionForceRecover'), 
        'order_split' => array('action_name' => '拆分订单', 'cls_name' => 'ClsSalesOrderActionSplit'), 
        'order_abandon' => array('action_name' => '作废订单', 'cls_name' => 'ClsSalesOrderActionAbandon'), 
        'order_revert' => array('action_name' => '重新编辑', 'cls_name' => 'ClsSalesOrderActionRevert'),
        'mark_taobao_order_sn_with_tail_x'=>array('action_name'=>'标记外部订单号作废','cls_name'=>'ClsSalesOrderActionMarkTaobaoOrderSNWithTailX'), 
    );

    var $prim_check_list_ = array();
    var $action_id_ = '操作代号';
    var $data_map_for_update_order_info_ = array();
    var $data_map_for_insert_order_action_ = array();
    var $data_map_for_insert_order_mixed_status_ = array();
    var $sqls_ = array();
}

class ClsSalesOrderActionAddNote extends ClsSalesOrderAction {
    function __construct($data_map) {
        parent::__construct($data_map);
        $this->action_id_ = 'add_note';
    }
    protected function ConcreteAction($data_map, & $error_info) {
        $this->data_map_for_update_order_info_['handle_time'] = strtotime($data_map['add_time']);
        return true;
    }
    public function ModifyViaRomeo() {
        return true;
    }
}

class ClsSalesOrderActionConfirm extends ClsSalesOrderAction {
    function __construct($data_map) {
        parent::__construct($data_map);
        $this->action_id_ = 'order_confirm';
    }
    protected function ConcreteAction($data_map, & $error_info) {
        $this->data_map_for_insert_order_mixed_status_['order_status'] = 'confirmed';
        //订单确认后状态
        $this->data_map_for_update_order_info_['order_status'] = 1; //已确认
        //
        $this->data_map_for_update_order_info_['confirm_time'] = time(); //已确认

        $this->data_map_for_update_order_info_['handle_time'] = strtotime($data_map['add_time']);
        //
        global $db;
        $sql = "select province,city,address ,order_type_id, order_amount, bonus, facility_id,
                    goods_amount, shipping_fee, pack_fee,shipping_id
                from ecshop.ecs_order_info 
                where order_id = {$this->order_id_}";
        $order_info = $db->getRow($sql);

        //check
        // 检查收货省市信息
        if ($order_info['province'] == 0 || $order_info['city'] == 0) {
            $error_info = array('err_no' => 3, 'message' => '收货地址省市信息不全，无法确认订单');
            return false;
        }
        // 检查收货地址信息
        if (empty($order_info['address'])) {
            $error_info = array('err_no' => 3, 'message' => '收货地址信息不全，无法确认订单');
            return false;
        }

        require_once(ROOT_PATH."admin/function.php");
        // 违禁品不能发的快递
        $contraband_shipping = get_contraband_shipping();
        if (in_array($order_info['shipping_id'], $contraband_shipping)) {
            if (check_order_contraband($this->order_id_)) {
                $error_info = array('err_no' => 3, 'message' => '该订单有违禁品，无法确认订单，请改其他快递');
                return false;
            }
        }

        // 补寄订单情况 by Zandy at 2010.12
        if ($order_info['order_type_id'] == 'SHIP_ONLY' && ($order_info['order_amount'] > 0 || $order_info['bonus'] != 0 || $order_info['goods_amount'] > 0 || $order_info['shipping_fee'] > 0 || $order_info['pack_fee'] > 0)) {
            $error_info = array('err_no' => 3, 'message' => '补寄订单的订单总额、红包、商品总额、运费、包装费必须都为 0 ，请您按规矩办事儿，安全生产。');
            return false;
        }

        // get register source
        require_once(dirname(__FILE__).'/../includes/lib_order.php');
        $source_id = get_user_register_source_id('淘宝');

        $sql = "SELECT * FROM ecshop.ecs_order_info a 
                    LEFT JOIN ecshop.order_attribute b ON a.order_id = b.order_id 
                WHERE b.attr_name = 'TAOBAO_USER_ID' AND a.order_id = '{$this->order_id_}' ";
        $getAll = $db->getAll($sql);
        foreach($getAll as $_order_info) {
            sync_taobao_user_info($_order_info, $source_id);
        }

        return true;
    }
    public function ModifyViaRomeo() {
        return true;
    }
}

class ClsSalesOrderActionCancel extends ClsSalesOrderAction {
    function __construct($data_map) {
        parent::__construct($data_map);
        $this->action_id_ = 'order_cancel';
    }
    protected function ConcreteAction($data_map, & $error_info) {
        if ($this->party_id_ == 65625) {
            $client = new SoapClient(null, array('location' => 'http://leqeeservice.leqee.com/best_express_cancel_order.php', 'uri' => 'http://test-uri/'));
            $result = $client->cancelOrder($this->order_id_);
            if ($result != 'success') {
                $error_info = array('err_no' => 3, 'message' => '取消失败,请10分钟后重试！');
                return false;
            }
        }
        global $db, $ecs;
        /** 取消不通知，防止后期恢复订单运单号不被认可的情况发生
    	//韵达热敏快递取消订单需调用韵达取消接口
    	$sql = "select oi.shipping_id,os.shipment_id,a.tracking_number " .
    		" from ecshop.ecs_order_info oi " .
    		" inner join romeo.facility_shipping fs on fs.facility_id = oi.facility_id and fs.shipping_id = oi.shipping_id " .
    		" inner join romeo.order_shipment os on os.order_id = convert(oi.order_id using utf8) " .
    		" inner join ecshop.ecs_order_yunda_mailno_apply a on a.shipment_id = os.shipment_id and a.shipping_id = oi.shipping_id and a.facility_id = oi.facility_id " .
    		" where oi.shipping_id = 100 and oi.order_id = {$this->order_id_} ";
    	$is_yunda_mail = $db->getRow($sql);
    	if(!empty($is_yunda_mail)){
			//非空，则调用取消接口
			require_once(ROOT_PATH . 'admin/includes/lib_yunda_express.php');
			$result = yunda_cancel_order($is_yunda_mail['shipping_id'],$is_yunda_mail['shipment_id'],$is_yunda_mail['tracking_number']);
			if($result != 'true'){
				$error_info = array('err_no' => 3, 'message' => $result);
				return false;
			}
    	}
    	//圆通热敏快递取消订单需调用圆通取消接口
    	$sql = "select oi.order_sn,s.tracking_number,oi.facility_id " .
    		" from ecshop.ecs_order_info oi " .
    		" inner join romeo.facility_shipping fs on fs.facility_id = oi.facility_id and fs.shipping_id = oi.shipping_id " .
    		" inner join romeo.order_shipment os on os.order_id = convert(oi.order_id USING utf8)
			INNER JOIN romeo.shipment s on s.SHIPMENT_ID = os.SHIPMENT_ID and s.tracking_number is not null and s.tracking_number != '' " .
    		" where oi.shipping_id = 85 and oi.order_id = {$this->order_id_} ";
    	$is_yto_mail = $db->getRow($sql);
    	if(!empty($is_yto_mail)){
			//非空，则调用取消接口
			require_once(ROOT_PATH . 'admin/includes/lib_express_arata.php');
			$branch=getLocalBranchWithFacilityId($is_yto_mail['facility_id']);
			$result = yto_cancel_order($branch,$is_yto_mail['order_sn'],$is_yto_mail['tracking_number']);
			if($result != 'true'){
				$error_info = array('err_no' => 3, 'message' => $result);
				return false;
			}
    	}
    	*/
        $this->data_map_for_insert_order_mixed_status_['order_status'] = 'canceled';
        //订单取消后状态
        $this->data_map_for_update_order_info_['order_status'] = 2; //已取消

        //

        //对是否能拒收进行判断 added by zwsun 2009-2-26 modified in 2009-6-19
        //本段代码与拒收相同
        $sql = "SELECT service_id FROM service WHERE order_id = '{$this->order_id_}' LIMIT 1 ";
        $is_apply_servie = $db->getOne($sql);
        if ($is_apply_servie) {
            $error_info = array('err_no' => 3, 'message' => '该订单已经申请过售后服务，无法取消');
            return false;
        }

        $sql = "select bonus, bonus_id ,shipping_status
                from ecshop.ecs_order_info 
                where order_id = {$this->order_id_}";
        $order_info = $db->getRow($sql);
        //退回红包 //die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
        // if(abs($order_info['bonus']) > 0 && strlen($order_info['bonus_id']) == 16) {
        //     $this->sqls_[] = "UPDATE {$ecs->table('user_bonus')} SET used_time = 0, order_id = 0 WHERE bonus_sn = '{$order_info['bonus_id']}' LIMIT 1 ";
        //     $this->sqls_[] = "UPDATE  membership.ok_gift_ticket SET gt_state = 2 WHERE `gt_code` = '{$order_info['bonus_id']}' AND `gt_state` = 4 LIMIT 1";
        // }


        //本段为取消专用
        // 判断是否是合并发货
        $is_merge_shipment = false;
        $handle = soap_get_client('ShipmentService');
        $response = $handle->getShipmentByPrimaryOrderId(array('primaryOrderId' => $this->order_id_));
        if(is_object($response->return)){
            $shipment=$response->return;
            if ($shipment->status == 'SHIPMENT_CANCELLED') {
                $is_merge_shipment = true;
            } else {
                $response2 = $handle->getOrderShipmentByShipmentId(array('shipmentId' => $shipment->shipmentId));
                if(is_array($response2->return->OrderShipment)){
                    $is_merge_shipment = true;
                }
            }
        }
        if ($is_merge_shipment) {
            $error_info = array('err_no' => 3, 'message' => '合并发货的订单不能取消，需要先拆分合并发货');
            return false;
        }


        // NB订单，需要先取消伊藤忠指令
        if ($_SESSION['party_id'] == 65585) {
            $sql = "select indicate_id, indicate_status from ecshop.ecs_indicate i where i.order_id = {$this->order_id_} and i.indicate_type = 'INVENTORY_OUT' limit 1 ";
            $indicate = $db->getRow($sql);
            if ($indicate && $indicate['indicate_status'] == 'SENDED') {
                $indicateService = soap_get_client('IndicateService');
                $result = $indicateService->orderCancel(array("indicateId" => $indicate['indicate_id']))->return;
                if (strstr($result, "T") == false) {
                    $msg = "订单取消失败，伊藤忠系统原因：{$result}";
                    $error_info = array('err_no' => 3, 'message' => $msg);
                    return false;
                }

                // 修改indicate状态
                $this->sqls_[] = "update ecshop.ecs_indicate set indicate_status = 'CANCEL', last_update_stamp = now() where indicate_id = {$indicate['indicate_id']} limit 1";
                $this->data_map_for_insert_order_action_['action_note'][] = 'NB指示取消';
            }

            if ($indicate && $indicate['indicate_status'] == 'INIT') {
                // 修改indicate状态
                $this->sqls_[] = "update ecshop.ecs_indicate set indicate_status = 'CANCEL', last_update_stamp = now() where indicate_id = {$indicate['indicate_id']} limit 1";
            }
        }

        // 如果处于批捡中的订单，并且没有出库的，则改状态为待配货，以免一直处于批捡中（对于一直没货的）
        if ($order_info['shipping_status'] == 13) {
            $sql = "select 1 from romeo.inventory_item_detail where order_id = '{$this->order_id_}' limit 1";
            $check_out = $db->getOne($sql);
            if (empty($check_out)) {
                // 删除批拣的相关信息
                $sql = "select shipment_id from romeo.order_shipment where order_id = '{$this->order_id_}' limit 1";
                $shipment_id = $db->getOne($sql);
                if (!empty($shipment_id)) {
                    $this->sqls_[] = "delete from romeo.inventory_location_reserve where shipment_id = '{$shipment_id}'";
                    $this->sqls_[] = "delete from romeo.batch_pick_mapping where shipment_id = '{$shipment_id}' limit 1";
                    $this->data_map_for_update_order_info_['shipping_status'] = 0;
                    $this->data_map_for_insert_order_action_['action_note'][] = '批捡中的发货状态回退到待配货';
                } else {
                    $error_info = array('err_no' => 3, 'message' => '找不到对应的shipment_id');
                    return false;
                }
            }

        }

        //还没有打面单，已完结订单取消则需要追回，不再打面单。删除批拣记录
        if ($order_info['shipping_status'] == 12) {
            // 删除批拣的相关信息
            $sql = "select shipment_id from romeo.order_shipment where order_id = '{$this->order_id_}' limit 1";
            $shipment_id = $db->getOne($sql);
            if (!empty($shipment_id)) {
                $this->sqls_[] = "delete from romeo.inventory_location_reserve where shipment_id = '{$shipment_id}'";
                $this->sqls_[] = "delete from romeo.batch_pick_mapping where shipment_id = '{$shipment_id}' limit 1";
                $this->data_map_for_insert_order_action_['action_note'][] = '订单被取消待追回';
            } else {
                $error_info = array('err_no' => 3, 'message' => '找不到对应的shipment_id');
                return false;
            }

        }

        return true;
    }
    public function ModifyViaRomeo() {
        try {
            $handle = soap_get_client("InventoryService");
            $handle->cancelOrderInventoryReservation(array('orderId' => $this->order_id_));
        } catch (Exception $e) {
            $error_info = array('err_no' => 3, 'message' => "romeo接口调用失败".$e->getMessage());
            return false;
        }
        return true;
    }
}

class ClsSalesOrderActionRecieveConfirm extends ClsSalesOrderAction {
    function __construct($data_map) {
        parent::__construct($data_map);
        $this->action_id_ = 'rec_confirm';
    }
    protected function ConcreteAction($data_map, & $error_info) {
        //订单收货确认后状态
        $this->data_map_for_update_order_info_['order_status'] = 1; //已确认
        $this->data_map_for_update_order_info_['shortage_status'] = 0;
        $this->data_map_for_update_order_info_['shipping_status'] = 2; //已收货
        //
        $this->data_map_for_insert_order_mixed_status_['shipping_status'] = 'received';

        return true;
    }
    public function ModifyViaRomeo() {
        return true;
    }
}

class ClsSalesOrderActionReject extends ClsSalesOrderAction {
    function __construct($data_map) {
        parent::__construct($data_map);
        $this->action_id_ = 'reject';
    }
    protected function ConcreteAction($data_map, & $error_info) {
        //拒收后状态
        $this->data_map_for_update_order_info_['order_status'] = 4; //拒收
        $this->data_map_for_update_order_info_['shortage_status'] = 0;
        $this->data_map_for_update_order_info_['shipping_status'] = 3; //拒收
        //
        $this->data_map_for_insert_order_mixed_status_['shipping_status'] = 'rejected';


        //对是否能拒收进行判断 added by zwsun 2009-2-26 modified in 2009-6-19
        global $db, $ecs;
        $sql = "SELECT service_id FROM service WHERE order_id = $this->order_id_ LIMIT 1 ";
        $is_apply_servie = $db->getOne($sql);
        if ($is_apply_servie) {
            $error_info = array('err_no' => 3, 'message' => '该订单已经申请过售后服务，无法拒收');
            return false;
        }

        $sql = "select bonus, bonus_id 
                from ecshop.ecs_order_info 
                where order_id = {$this->order_id_}";
        $order_info = $db->getRow($sql);
        //退回红包 //die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
        // if(abs($order_info['bonus']) > 0 && strlen($order_info['bonus_id']) == 16) {
        //     $this->sqls_[] = "UPDATE {$ecs->table('user_bonus')} SET used_time = 0, order_id = 0 WHERE bonus_sn = '{$order_info['bonus_id']}' LIMIT 1 ";
        //     $this->sqls_[] = "UPDATE  membership.ok_gift_ticket SET gt_state = 2 WHERE `gt_code` = '{$order_info['bonus_id']}' AND `gt_state` = 4 LIMIT 1";
        // }

        return true;
    }
    public function ModifyViaRomeo() {
        return true;
    }
}

class ClsSalesOrderActionRecover extends ClsSalesOrderAction {
    function __construct($data_map) {
        parent::__construct($data_map);
        $this->action_id_ = 'order_recover';
    }
    protected function ConcreteAction($data_map, & $error_info) {
        //订单取消后状态
        $this->data_map_for_update_order_info_['order_status'] = 0; //未确认
        //
        $this->data_map_for_insert_order_mixed_status_['order_status'] = 'unconfirmed';

        //伊藤忠仓库？用的指令，如果指示已经为取消则无法恢复订单
        $sql = "select indicate_id, indicate_status from ecshop.ecs_indicate i where i.order_id = {$this->order_id_} and i.indicate_type = 'INVENTORY_OUT' and i.indicate_status = 'CANCEL' limit 1 ";
        global $db, $ecs;
        $indicate = $db->getRow($sql);
        if ($indicate) {
            $error_info = array('err_no' => 3, 'message' => '订单指示已经取消，无法恢复订单！');
            return false;
        }
        /*===测试环境====*/
        // $sql = "select * from ecshop.ecs_order_info eoi
        // 		INNER JOIN ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn)))=ebi.out_biz_code
        // 		where eoi.order_id = {$this->order_id_} and ebi.indicate_status in ('推送成功后取消成功','推送成功后转回ERP发货') and eoi.facility_id in ('224734292','144624934','144624935','144624936','144624937','144676339')";
        /*===正式环境===*/
        $sql = "select * from ecshop.ecs_order_info eoi
				INNER JOIN ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn)))=ebi.out_biz_code
				where eoi.order_id = {$this->order_id_} and ebi.indicate_status in ('推送成功后取消成功','推送成功后转回ERP发货') and eoi.facility_id in ('224734292','149849263','149849264','149849265','149849266','173433261','173433262','173433263','173433264','173433265')";
        $bird_status = $db->getRow($sql);
        if (!empty($bird_status)) {
            $error_info = array('err_no' => 3, 'message' => '已经推送至菜鸟仓的取消订单恢复订单将不会推送至菜鸟仓，请修改配送仓！');
            //return false;
        }

        $sql = "select bonus, bonus_id 
                from ecshop.ecs_order_info 
                where order_id = {$this->order_id_}";
        $order_info = $db->getRow($sql);

        //收回红包 //die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
        // if(abs($order_info['bonus']) > 0 && strlen($order_info['bonus_id']) == 16) {
        //     $sql = "SELECT gtc_value, gt_state FROM membership.ok_gift_ticket WHERE gt_code = '{$order_info['bonus_id']}' LIMIT 1 ";
        //     $gt = $db->getRow($sql);
        //     if ($gt) {
        //         if ($gt['gt_state'] == 4) {
        //             $error_info = array('err_no' => 3, 'message' => '订单中的红包已使用，此订单无法恢复，请重新下单！！！');
        //             return false;
        //         }
        //         if ($gt['gtc_value'] > 0 && abs($gt['gtc_value']) < abs($order_info['bonus'])) {
        //             $this->data_map_for_update_order_info_['bonus'] = 0;
        //             $error_info['message'] .= "订单中红包金额超过红包实际的金额，请确定订单红包金额。 {$gt['gtc_value']} {$order_info['bonus']}";
        //         }
        //     }

        //     $this->sqls_[] = "UPDATE {$ecs->table('user_bonus')} SET used_time = UNIX_TIMESTAMP(), order_id = '{$this->order_id_}' WHERE bonus_sn = '{$order_info['bonus_id']}' LIMIT 1 ";
        //     $this->sqls_[] = "UPDATE  membership.ok_gift_ticket SET gt_state = 4 WHERE `gt_code` = '{$order_info['bonus_id']}' AND `gt_state` = 2 LIMIT 1";
        // }
        return true;
    }
    public function ModifyViaRomeo() {
        try {
            $handle = soap_get_client("InventoryService");
            $handle->cancelOrderInventoryReservation(array('orderId' => $this->order_id_));
        } catch (Exception $e) {
            $error_info = array('err_no' => 3, 'message' => "romeo接口调用失败".$e->getMessage());
            return false;
        }
        return true;
    }
}
class ClsSalesOrderActionForceRecover extends ClsSalesOrderAction {
    function __construct($data_map) {
        parent::__construct($data_map);
        $this->action_id_ = 'order_recover_force';
    }
    protected function ConcreteAction($data_map, & $error_info) {
        $this->data_map_for_insert_order_mixed_status_['order_status'] = 'unconfirmed';
        return false;
    }
    public function ModifyViaRomeo() {
        try {
            $handle = soap_get_client("InventoryService");
            $handle->cancelOrderInventoryReservation(array('orderId' => $this->order_id_));
        } catch (Exception $e) {
            $error_info = array('err_no' => 3, 'message' => "romeo接口调用失败".$e->getMessage());
            return false;
        }
        return true;
    }
}

class ClsSalesOrderActionSplit extends ClsSalesOrderAction {
    function __construct($data_map) {
        parent::__construct($data_map);
        $this->action_id_ = 'order_split';
    }
    protected function ConcreteAction($data_map, & $error_info) {
        //拆分订单后状态
        $this->data_map_for_update_order_info_['order_status'] = 0; //未确认

        return true;
    }
    public function ModifyViaRomeo() {
        return true;
    }
}

class ClsSalesOrderActionAbandon extends ClsSalesOrderAction {
    function __construct($data_map) {
        parent::__construct($data_map);
        $this->action_id_ = 'order_abandon';
    }
    protected function ConcreteAction($data_map, & $error_info) {
        global $db, $ecs;

        //作废订单后状态
        $this->data_map_for_update_order_info_['order_status'] = 2; //已取消
        $this->data_map_for_insert_order_mixed_status_['order_status'] = 'canceled';

        if ($this->party_id_ == 65625) {
            $client = new SoapClient(null, array('location' => 'http://leqeeservice.leqee.com/best_express_cancel_order.php', 'uri' => 'http://test-uri/'));
            $result = $client->cancelOrder($this->order_id_);
            if ($result != 'success') {
                $error_info = array('err_no' => 3, 'message' => '取消失败,请10分钟后重试！');
                return false;
            }
        }

        //对是否能拒收进行判断 added by zwsun 2009-2-26 modified in 2009-6-19
        //本段代码与拒收相同
        $sql = "SELECT service_id FROM service WHERE order_id = '{$this->order_id_}' LIMIT 1 ";
        $is_apply_servie = $db->getOne($sql);
        if ($is_apply_servie) {
            $error_info = array('err_no' => 3, 'message' => '该订单已经申请过售后服务，无法取消');
            return false;
        }

        $sql = "select shipping_status
                from ecshop.ecs_order_info 
                where order_id = {$this->order_id_}";
        $order_info = $db->getRow($sql);

        //本段为取消专用
        // 判断是否是合并发货
        $is_merge_shipment = false;
        $handle = soap_get_client('ShipmentService');
        $response = $handle->getShipmentByPrimaryOrderId(array('primaryOrderId' => $this->order_id_));
        if (is_object($response ->
        return)) {
            $shipment = $response ->
            return;
            if ($shipment->status == 'SHIPMENT_CANCELLED') {
                $is_merge_shipment = true;
            } else {
                $response2 = $handle->getOrderShipmentByShipmentId(array('shipmentId' => $shipment->shipmentId));
                if (is_array($response2 ->
                return->OrderShipment)) {
                    $is_merge_shipment = true;
                }
            }
        }
        if ($is_merge_shipment) {
            $error_info = array('err_no' => 3, 'message' => '合并发货的订单不能取消，需要先拆分合并发货');
            return false;
        }

        // 各种不能爽快取消的
        require_once(__DIR__.'/../omsserver/ApiDelegate.php');
        $facility_type=OMSSERVER_API_DELEGATE::getErpOrderFacilityType($this->order_id_);
        if($facility_type=='LEQEE_WMS'){
            $omsserver_api=new OMSSERVER_API_DELEGATE();
            $order_sn_=OMSSERVER_API_DELEGATE::getErpOrderSnWithId($this->order_id_);
            $omsserver_api_cancel_done=$omsserver_api->order_cancel($order_sn_,$omsserver_api_msg);
            if(!$omsserver_api_cancel_done){
                $this->data_map_for_insert_order_action_['action_note'][] = 'OMSSERVER订单取消失败';
                $error_info = array('err_no' => 3, 'message' => '推送撤销接口调用有问题。['.order_sn_.']'.$omsserver_api_msg);
                return false;
            }else{
                $this->data_map_for_insert_order_action_['action_note'][] = 'OMSSERVER订单取消成功';
            }
        }
        else if($facility_type=='CAINIAO_QIMEN_WMS'){
        	 $omsserver_api=new OMSSERVER_API_DELEGATE();
        	 $order_sn_=OMSSERVER_API_DELEGATE::getErpOrderSnWithId($this->order_id_);
           $omsserver_api_cancel_done=$omsserver_api->order_cainiao_cancel($order_sn_,$omsserver_api_msg);
            if(!$omsserver_api_cancel_done){
                $this->data_map_for_insert_order_action_['action_note'][] = 'OMSSERVERCAINIAO订单取消失败';
                $error_info = array('err_no' => 3, 'message' => '推送撤销接口调用有问题。['.order_sn_.']'.$omsserver_api_msg);
                return false;
            }else{
                $this->data_map_for_insert_order_action_['action_note'][] = 'OMSSERVERCAINIAO订单取消成功';
            }
        }
        else{
            // 这里是OWMS发货流程的
            // 如果处于批捡中的订单，并且没有出库的，则改状态为待配货，以免一直处于批捡中（对于一直没货的）
            if ($order_info['shipping_status'] == 13) {
                $sql = "select 1 from romeo.inventory_item_detail where order_id = '{$this->order_id_}' limit 1";
                $check_out = $db->getOne($sql);
                if (empty($check_out)) {
                    // 删除批拣的相关信息
                    $sql = "select shipment_id from romeo.order_shipment where order_id = '{$this->order_id_}' limit 1";
                    $shipment_id = $db->getOne($sql);
                    if (!empty($shipment_id)) {
                        $this->sqls_[] = "delete from romeo.inventory_location_reserve where shipment_id = '{$shipment_id}'";
                        $this->sqls_[] = "delete from romeo.batch_pick_mapping where shipment_id = '{$shipment_id}' limit 1";
                        $this->data_map_for_update_order_info_['shipping_status'] = 0;
                        $this->data_map_for_insert_order_action_['action_note'][] = '批捡中的发货状态回退到待配货';
                    } else {
                        $error_info = array('err_no' => 3, 'message' => '找不到对应的shipment_id');
                        return false;
                    }
                }
            }

            //还没有打面单，已完结订单取消则需要追回，不再打面单。删除批拣记录
            if ($order_info['shipping_status'] == 12) {
                // 删除批拣的相关信息
                $sql = "select shipment_id from romeo.order_shipment where order_id = '{$this->order_id_}' limit 1";
                $shipment_id = $db->getOne($sql);
                if (!empty($shipment_id)) {
                    $this->sqls_[] = "delete from romeo.inventory_location_reserve where shipment_id = '{$shipment_id}'";
                    $this->sqls_[] = "delete from romeo.batch_pick_mapping where shipment_id = '{$shipment_id}' limit 1";
                    $this->data_map_for_insert_order_action_['action_note'][] = '订单被取消待追回';
                } else {
                    $error_info = array('err_no' => 3, 'message' => '找不到对应的shipment_id');
                    return false;
                }
            }
        }

        return true;
    }
    public function ModifyViaRomeo() {
        try {
            $handle = soap_get_client("InventoryService");
            $handle->cancelOrderInventoryReservation(array('orderId' => $this->order_id_));
        } catch (Exception $e) {
            $error_info = array('err_no' => 3, 'message' => "romeo接口调用失败".$e->getMessage());
            return false;
        }
        return true;
    }
}

class ClsSalesOrderActionRevert extends ClsSalesOrderAction {
    function __construct($data_map) {
        parent::__construct($data_map);
        $this->action_id_ = 'order_revert';
    }
    protected function ConcreteAction($data_map, & $error_info) {
        global $db, $ecs;
        
        //去重新编辑订单后状态
        $this->data_map_for_update_order_info_['order_status'] = 0; //未确认
        $this->data_map_for_update_order_info_['confirm_time'] = 0; //无确认时间
         $this->data_map_for_update_order_info_['reserved_time'] = 0; //无确认时间
        $this->data_map_for_insert_order_mixed_status_['order_status'] = 'unconfirmed';

        if ($this->party_id_ == 65625) {
            $client = new SoapClient(null, array('location' => 'http://leqeeservice.leqee.com/best_express_cancel_order.php', 'uri' => 'http://test-uri/'));
            $result = $client->cancelOrder($this->order_id_);
            if ($result != 'success') {
                $error_info = array('err_no' => 3, 'message' => '取消失败,请10分钟后重试！');
                return false;
            }
        }

        //对是否能拒收进行判断 added by zwsun 2009-2-26 modified in 2009-6-19
        //本段代码与拒收相同
        $sql = "SELECT service_id FROM service WHERE order_id = '{$this->order_id_}' LIMIT 1 ";
        $is_apply_servie = $db->getOne($sql);
        if ($is_apply_servie) {
            $error_info = array('err_no' => 3, 'message' => '该订单已经申请过售后服务，无法取消');
            return false;
        }

        $sql = "select shipping_status
                from ecshop.ecs_order_info 
                where order_id = {$this->order_id_}";
        $order_info = $db->getRow($sql);

        //本段为取消专用
        // 判断是否是合并发货
        $is_merge_shipment = false;
        $handle = soap_get_client('ShipmentService');
        $response = $handle->getShipmentByPrimaryOrderId(array('primaryOrderId' => $this->order_id_));
        if (is_object($response ->
        return)) {
            $shipment = $response ->
            return;
            if ($shipment->status == 'SHIPMENT_CANCELLED') {
                $is_merge_shipment = true;
            } else {
                $response2 = $handle->getOrderShipmentByShipmentId(array('shipmentId' => $shipment->shipmentId));
                if (is_array($response2 ->
                return->OrderShipment)) {
                    $is_merge_shipment = true;
                }
            }
        }
        if ($is_merge_shipment) {
            $error_info = array('err_no' => 3, 'message' => '合并发货的订单不能取消，需要先拆分合并发货');
            return false;
        }

        // NB订单，需要先取消伊藤忠指令 这部分因为新百伦已经死了所以不管了。

        // 如果处于批捡中的订单，并且没有出库的，则改状态为待配货，以免一直处于批捡中（对于一直没货的）
        if ($order_info['shipping_status'] == 13) {
            $sql = "select 1 from romeo.inventory_item_detail where order_id = '{$this->order_id_}' limit 1";
            $check_out = $db->getOne($sql);
            if (empty($check_out)) {
                // 删除批拣的相关信息
                $sql = "select shipment_id from romeo.order_shipment where order_id = '{$this->order_id_}' limit 1";
                $shipment_id = $db->getOne($sql);
                if (!empty($shipment_id)) {
                    $this->sqls_[] = "delete from romeo.inventory_location_reserve where shipment_id = '{$shipment_id}'";
                    $this->sqls_[] = "delete from romeo.batch_pick_mapping where shipment_id = '{$shipment_id}' limit 1";
                    $this->data_map_for_update_order_info_['shipping_status'] = 0;
                    $this->data_map_for_insert_order_action_['action_note'][] = '批捡中的发货状态回退到待配货';
                } else {
                    $error_info = array('err_no' => 3, 'message' => '找不到对应的shipment_id');
                    return false;
                }
            }
        }

        //还没有打面单，已完结订单取消则需要追回，不再打面单。删除批拣记录
        if ($order_info['shipping_status'] == 12) {
            // 删除批拣的相关信息
            $sql = "select shipment_id from romeo.order_shipment where order_id = '{$this->order_id_}' limit 1";
            $shipment_id = $db->getOne($sql);
            if (!empty($shipment_id)) {
                $this->sqls_[] = "delete from romeo.inventory_location_reserve where shipment_id = '{$shipment_id}'";
                $this->sqls_[] = "delete from romeo.batch_pick_mapping where shipment_id = '{$shipment_id}' limit 1";
                $this->data_map_for_insert_order_action_['action_note'][] = '订单被取消待追回';
            } else {
                $error_info = array('err_no' => 3, 'message' => '找不到对应的shipment_id');
                return false;
            }
        }

        //已经推送到WMS，不能追回
        if ($order_info['shipping_status'] == 16) {
            $error_info = array('err_no' => 3, 'message' => '已推送到WMS的订单不能重新编辑只能作废。');
            return false;
        }

        /// ABOVE ARE CANCEL
        /// BENEATH ARE RECOVER

        /*===测试环境====*/
        // $sql = "select * from ecshop.ecs_order_info eoi
        // 		INNER JOIN ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn)))=ebi.out_biz_code
        // 		where eoi.order_id = {$this->order_id_} and ebi.indicate_status in ('推送成功后取消成功','推送成功后转回ERP发货') and eoi.facility_id in ('224734292','144624934','144624935','144624936','144624937','144676339')";
        /*===正式环境===*/
        $sql = "select * from ecshop.ecs_order_info eoi
				INNER JOIN ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn)))=ebi.out_biz_code
				where eoi.order_id = {$this->order_id_} and ebi.indicate_status in ('推送成功后取消成功','推送成功后转回ERP发货') and eoi.facility_id in ('224734292','149849263','149849264','149849265','149849266','173433261','173433262','173433263','173433264','173433265')";
        $bird_status = $db->getRow($sql);
        if (!empty($bird_status)) {
            $error_info = array('err_no' => 3, 'message' => '已经推送至菜鸟仓的取消订单恢复订单将不会推送至菜鸟仓，请修改配送仓！');
            // return false;
        }

        return true;
    }
    public function ModifyViaRomeo() {
        try {
            $handle = soap_get_client("InventoryService");
            $handle->cancelOrderInventoryReservation(array('orderId' => $this->order_id_));
        } catch (Exception $e) {
            $error_info = array('err_no' => 3, 'message' => "romeo接口调用失败".$e->getMessage());
            return false;
        }
        return true;
    }
}

class ClsSalesOrderActionMarkTaobaoOrderSNWithTailX extends ClsSalesOrderAction {
    function __construct($data_map) {
        parent::__construct($data_map);
        $this->action_id_ = 'mark_taobao_order_sn_with_tail_x';
    }
    protected function ConcreteAction($data_map, & $error_info) {
        global $db;
        
        $sql="UPDATE ecshop.ecs_order_info 
            SET taobao_order_sn=concat(taobao_order_sn,'-x') 
            WHERE order_id={$this->order_id_} 
        ";
        $afx=$db->exec($sql);
        if($afx!=1)return false;

        return true;
    }
    public function ModifyViaRomeo() {
        return true;
    }
}