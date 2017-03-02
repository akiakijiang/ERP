<?php
define('IN_ECS', true);
define("PATH", dirname(__FILE__) . '/../../');
require_once PATH . 'admin/includes/cls_message.php';

class GetMessageReplyCommand extends CConsoleCommand{
    public function actionIndex(){
        $this->run(array('GetMessage'));

    }
    public function actionGetMessage(){
        $db = Yii::app ()->getDb ();
        $builder = $db->getCommandBuilder ();
        $message_url = trim(MESSAGE_URL);
        $message_serialnumber = trim(MESSAGE_SERIALNUMBER);
        $message_password = trim(MESSAGE_PASSWORD);
        $message_sessionkey = trim(MESSAGE_SESSIONKEY);
        $MessageReplyClient = new Client($message_url,$message_serialnumber,$message_password,$message_sessionkey);
        $MessageReplyClient->setOutgoingEncoding("UTF-8");
        $message_data = $MessageReplyClient->getMO();
        if (!empty($message_data)) {
            foreach ($message_data as $item){
                //1065502182062  1065502182062
                //if ($item->getChannelnumber() == '1065505724') {
                $data['history_id'] = null;
                $data['result'] = 0;
                $data['type'] = 'REPLY';
                $data['send_time'] = $item->getSentTime();
                $mobile = $item->getMobileNumber();
                if (substr($mobile, 0, 2) == "86") {
                	$mobile = substr($mobile, 2);
                }
                $data['dest_mobile'] = $mobile;
                $data['content'] = $item->getSmsContent();
                $data['user_id'] = '1';
                $data['server_name'] = 'emay';
                $table = $db->getSchema()->getTable('message.message_history');
                $builder->createInsertCommand($table,$data)->execute();
                $message_data = "短信回复";
                if ($mobile) {
                    $sql = "SELECT order_id, order_status FROM ecshop.ecs_order_info WHERE mobile = '{$mobile}' and party_id = '65540'
                        and pay_id = '1' and distributor_id = '164' ORDER BY order_time DESC limit 1";
                    $order = $db->createCommand($sql)->queryRow();
                    //更改订单状态时先判断订单是未确认，否则不更改订单状态
                    $order_status = array('order_status'=> $order['order_status']);
                    if ($order && $order['order_status'] == 0 && strcasecmp($item->getSmsContent(),'N') == 0) {
                        //更新订单状态
                        $table = $db->getSchema()->getTable('ecshop.ecs_order_info');
                        $data_update = array('order_id' =>$order['order_id'] ,'order_status' => 2);
                        $criteria=$builder->createColumnCriteria($table,array('order_id'=>$order['order_id']));
                        $result = $builder->createUpdateCommand($table,$data_update,$criteria)->execute();
                        $order_status = array('order_status'=>'2');
                        $message_data = '短信回复取消订单';
                    }
                    $this->updateOrderAction($order['order_id'],array('action_note'=>"{$item->getSmsContent()}",'note_type'=>'message'));
                    $this->updateOrderMixedStatus($order['order_id'],$order_status,'system',$message_data);
                }
                // }
            }
        }
    }

    /**
     * 更新order_action表
     * @param int $order_id
     */
    private function updateOrderAction($order_id,$data){
        $db = Yii::app ()->getDb ();
        $builder = $db->getCommandBuilder ();
        $oi_sql = "select
                   order_id, order_status, pay_status, shipping_status
                   from ecshop.ecs_order_info
                   where order_id = '{$order_id}'";
        $oi_data = $db->createCommand ( $oi_sql )->queryRow ();
        $oa_data = array(
                            'order_id' => $oi_data['order_id'],
                            'action_user' => 'system',
                            'shipping_status' => $oi_data['shipping_status'],
                            'order_status' =>  $oi_data['order_status'],
                            'pay_status' => $oi_data['pay_status'],
                            'action_time' => date("Y-m-d H:i:s"),
                            'invoice_status' => 0,
                            'action_note' => $data['action_note'],
                            'note_type' => $data['note_type']
        );
        $oa_table = $db->getSchema()->getTable('ecs_order_action');
        $result_oa = $builder->createInsertCommand($oa_table,$oa_data)->execute();
        return $result_oa;
    }

    /**
     * @param int $order_id 订单id
     * @param array $status 要更新的状态
     * @param string $created_by_user_class 用户的类别
     * @param string $note 备注
     * @param string $note_type 备注类型
     *
     */
    private function updateOrderMixedStatus($order_id ,$status ,$created_by_user_class ,$note = '' ,$note_type=null){
        $db = Yii::app ()->getDb ();
        $builder = $db->getCommandBuilder ();
        //获得当前订单状态及描述
        $sql = "SELECT * FROM order_mixed_status WHERE order_id = '{$order_id}' ";
        $oms_data = $db->createCommand ($sql)->queryRow ();
   
        if(empty($oms_data)){
            $this->log("order_mixed_status没有相关记录");
            return false;
        }
        $new_status = array_merge($oms_data, $status);
        unset($new_status['order_mixed_status_history_id']);
        unset($new_status['description']);
        $new_status['order_id'] = $order_id;
        $new_status['is_current'] = 'Y';
        $new_status['created_by_user_class'] = $created_by_user_class;
        $new_status['created_by_user_login'] = 'webService';
        $new_status['created_stamp'] = date("Y-m-d H:i:s");
        $new_status['last_updated_stamp'] = date("Y-m-d H:i:s");
        $table = $db->getSchema()->getTable('order_mixed_status_history');
        //先更新后插入
        $data =  array('order_id' =>$order_id ,'is_current' => 'N');
        $criteria=$builder->createColumnCriteria($table,array('order_id'=>$order_id));
        $result_update = $builder->createUpdateCommand($table,$data,$criteria)->execute();
        $result = $builder->createInsertCommand($table,$new_status)->execute();
        if ($note) {
            $msn_result =$this->updateOrderMixedStatusNote($order_id, $created_by_user_class, $note, $note_type);
            if( !$msn_result){
                $this->log("order_mixed_status_note中订单号".$order_id."插入失败");
            }
        }
        return $result;
    }

    private function updateOrderMixedStatusNote( $order_id, $created_by_user_class, $note, $note_type = null){
        $db = Yii::app ()->getDb ();
        $builder = $db->getCommandBuilder ();

        $sql = "select order_mixed_status_history_id ".
               " from order_mixed_status ".
               " where order_id = '{$order_id}' and is_current = 'Y' ";
        $order_mixed_status_history_id = $db->createCommand($sql)->queryRow();
        $order_mixed_status_note = array();
        $order_mixed_status_note['note'] = $note;
        $order_mixed_status_note['note_type'] = $note_type;
        $order_mixed_status_note['order_id'] = $order_id;
        $order_mixed_status_note['order_mixed_status_history_id'] = $order_mixed_status_history_id;
        $order_mixed_status_note['created_by_user_class'] = $created_by_user_class;
        $order_mixed_status_note['created_by_user_login'] = 'webService';
        $order_mixed_status_note['created_stamp'] = date("Y-m-d H:i:s");

        $table = $db->getSchema()->getTable('order_mixed_status_note');
        $result = $builder->createInsertCommand($table,$order_mixed_status_note)->execute();
        return $result;
    }
    private function log($m)
    {
        print date('Y-m-d H:i:s')."　" . $m . "\r\n";
    }


}

?>