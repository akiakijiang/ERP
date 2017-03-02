<?php
/**
 * 运单状态查询
 * @author jrpei
 *
 */

class UpdateshippingdataCommand extends CConsoleCommand{

    public function actionIndex(){
        $this->run(array('CatchOrderInfo'));
    }
    
    public function actionCatchOrderInfo(){ 
        // 找出需要的订单 
        // 目前主要是为了给多美滋货到付款使用
        $end_time = date('Y-m-d H:i:s',time()-3600*48);
        $sql = "select o.order_id, cb.bill_no, o.order_sn, cb.carrier_id, o.shipping_status
                from
                ecshop.ecs_order_info o
                left join ecshop.ecs_carrier_bill cb on o.carrier_bill_id = cb.bill_id
                where o.party_id != 65545 and
                o.party_id = 65540 and
                o.pay_id = 1 and
                o.shipping_status = 1 and
                o.order_status =1 and
                o.order_time <= '{$end_time}' and cb.carrier_id = 15
                order by o.order_time
                ";
        $db = Yii::app ()->getDb();
        $bill_lists = $db->createCommand ( $sql )->queryAll ();
        if(empty($bill_lists)){
            $this->log("没有需要更新的运单信息");
            exit();
        }
    
        //参照依据ecs_carrier
        static $carrier_id = array(
                    '3'  => 'yuantong',
                    '5'  => 'zhaijisong',
                    '9'  => 'ems',
                    '10' => 'shunfeng',
                    '13' => 'wanxiangwuliu',
                    '14' => 'ems',  
                    '15' => 'zhaijisong',  
                    '16' => 'wanxiangwuliu',             
                    '17' => 'shunfeng',
                    '18' => 'longbanwuliu',
                    '19' => 'longbanwuliu',  
                    '20' => 'shentong',
                    '21' => 'dhl',
                    '22' => 'ups',
                    '23' => 'tnt',
        			'24' => 'fedex'
                    );
       
        foreach ($bill_lists as $bill_list){
            $carrier_name = $carrier_id["{$bill_list['carrier_id']}"];
            $bill_no = $bill_list['bill_no'];
            $order_sn = $bill_list['order_sn'];
            $order_id = $bill_list['order_id'];
            //判断shipping_data中的查询次数
            $check = $this->shipping_data_select('times,details_format,details', $order_id);
            if($check['details_format'] == 'json'){
                $check['details'] = json_decode($check['details']);
                if($check['details']->message != 'ok' && $check['times'] >= 10) {
                    $this->log("订单号".$order_id."查询次数已经为十次");
                    continue;
                }
            }
            
            $this->UpdateOrderInfo($carrier_name ,$bill_no ,$order_sn ,$order_id);     
        }
    }
    
    public function UpdateOrderInfo($carrier_name ,$bill_no ,$order_sn ,$order_id){
         if(empty($carrier_name)){
            $this->log("快递公司名字为空");
            exit();
         }
         if(empty($bill_no)){
            $this->log("运单号为空");
            exit();
         }
        //查询运单的状态
        $Content_json = $this->actionGetOrderStatus($carrier_name, $bill_no);
        $Content = json_decode($Content_json);
        
        if(empty($Content)){
           $this->log("没有运单号".$bill_no."的相关信息");
        }else{
            $error = '';
            // 判断查询是否成功
            if($Content->message == 'ok'){
                $last_modified_success = 1;          
            }else{
                $last_modified_success = 0;
                //获取错误信息     如果取到错误信息，则需要记录错误信息
                $error = $Content->message; 
            }
            
            // 如果没有取到信息
            $context = null;
            $last_modified = date('Y-m-d H:i:s'); 
            $shipping_time = null;
            $db = Yii::app ()->getDb ();
            $builder = $db->getCommandBuilder ();
            
            $state = "ONWAY";
            if ($Content->status == 2) {
                $state = "FAIL";
            } elseif (is_array($Content->data)) {
                foreach ($Content->data as $line) {
                    if (strpos($line->context, '返货') !== false || strpos($line->context, '未妥投') !== false) {
                        $state = "REJECT";
                        break;
                    }
                }
                if ($state == "ONWAY") {
                    foreach ($Content->data as $line) {
                        if (strpos($line->context, '签收') !== false) {
                            $state = "SIGNIN";
                            $shipping_time = $line->time;
                            $context = array_pop(preg_split ("/:|\//",$line->context));
                            break;
                        }
                    }
                }
            }
           //先查看数据库中是否存在记录，如果没有记录，插入记录。如果有数据，更新记录
            $result = $this->shipping_data_select('id', $order_id);
            
			$shipping_id = null;
			$data = array(
			            'id' => $shipping_id,
			            'order_sn' => $order_sn,
			            'order_id' => $order_id,  
			            'bill_no' => $bill_no,
			            'shipping_code' => $carrier_name,
			            'context' => $context,
			            'last_modified' => $last_modified,
			            'shipping_status' => $state,
			            'last_modified_success' => $last_modified_success,
			            'last_modified_result' => $error,
			            'details' => $Content_json,
			            'shipping_time' => $shipping_time,
			            'details_format' => 'json'
			          );
			$table=$db->getSchema()->getTable('ecs_shipping_data');
			
            if($result === false){
                $times = array('times'=>1);
                $data = array_merge($data,$times);   
                $id = $builder->createInsertCommand($table,$data)->execute();
                $this->log($bill_no . "运单信息插入成功");
            }else{
                $shipping_id = $result['id']; 
                //该运单的查询次数
                $times = $this->shipping_data_select('times', $order_id);
                
                $times['times']++;
                $data = array_merge($data,$times);       
                $criteria=$builder->createColumnCriteria($table,array('id'=>$result['id']));
                $id = $builder->createUpdateCommand($table,$data,$criteria)->execute();
                $this->log($bill_no . "运单信息更新成功"); 
            }
            
       //     if ($state == "SIGNIN") {
       //         $table_update = $db->getSchema()->getTable('ecs_order_info');
       //         $criteria_update = $builder->createColumnCriteria($table_update,array('order_id'=>$order_id));
       //         $data_update = array('order_id' =>$order_id ,'shipping_status' => '2');
       //         $result_update = $builder->createUpdateCommand($table_update,$data_update,$criteria_update)->execute();
       //         if($result_update == 0){
       //             $this->log("ecs_order_info中订单号".$order_id."更新运单状态失败");
       //         }
       //         //更新order_action
       //         $result_action = $this->UpdateOrderAction($order_id);
       //         if(empty($result_action)){
       //             $this->log("order_action中订单号".$order_id."插入失败");
       //         }
       //         $result_oms=$this->update_order_mixed_status($order_id ,array('shipping_status' => 'received'), 'system', '用户签收');
       //         if( !$result_oms ){
       //             $this->log("order_mixed_status表订单号".$order_id."插入失败");
       //         }
       //     }
            
           
        }
     }
     public function actionGetOrderStatus($carrier_name, $bill_no){
         $AppKey='e8c78160a9f0e53e';
         $url ='http://api.kuaidi100.com/api?id='.$AppKey.'&com='.$carrier_name.'&nu='.$bill_no.'&show=0&muti=1&order=asc';
         if (function_exists('curl_init') == 1){
            $curl = curl_init();
            ///$_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:5.0) Gecko/20100101 Firefox/5.0";
            curl_setopt ($curl, CURLOPT_URL, $url);
            curl_setopt ($curl, CURLOPT_HEADER,0);
            curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
            //curl_setopt ($curl, CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
            curl_setopt ($curl, CURLOPT_TIMEOUT,5);
            $get_content = curl_exec($curl);
            curl_close ($curl);
          }
          return $get_content;
    } 
    
    private function log($m)
    {
        print date('Y-m-d H:i:s')."　" . $m . "\r\n";
    }
    //查询运单信息
    private function shipping_data_select($Column,$order_id){
        $sql = "SELECT ".$Column." FROM ecshop.ecs_shipping_data WHERE order_id=:order_id ";
        $db = Yii::app ()->getDb ();
        $result = $db->createCommand($sql)
                     ->bindValue(':order_id',$order_id)
                     ->queryRow();
        return $result;
    }
    /**
     * 更新order_action表
     * @param int $order_id
     */
    public function UpdateOrderAction($order_id){
        $db = Yii::app ()->getDb ();
        $builder = $db->getCommandBuilder ();
        $oi_sql = "select
                   order_id, order_status, pay_status
                   from ecshop.ecs_order_info
                   where order_id = ".$order_id;
        $oi_data = $db->createCommand ( $oi_sql )->queryRow ();
        $oa_data = array(
                            'order_id' => $oi_data['order_id'],
                            'action_user' => 'webService',
                            'shipping_status' => 2,
                            'order_status' =>  $oi_data['order_status'],
                            'pay_status' => $oi_data['pay_status'],
                            'action_time' => date("Y-m-d H:i:s"),
                            'action_note' => '用户签收'
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
    public function update_order_mixed_status($order_id ,$status ,$created_by_user_class ,$note = '' ,$note_type=null){
        $db = Yii::app ()->getDb ();
        $builder = $db->getCommandBuilder ();
        //获得当前订单状态及描述
        $sql = "SELECT * FROM order_mixed_status WHERE order_id = :order_id ";
        $oms_data = $db->createCommand($sql)
                       ->bindValue(':order_id',$order_id)
                       ->queryRow();          
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
            $msn_result =$this->update_order_mixed_status_note($order_id, $created_by_user_class, $note, $note_type);
            if( !$msn_result){
                $this->log("order_mixed_status_note中订单号".$order_id."插入失败");
            }
        }
        return $result;
    }
    
    public function update_order_mixed_status_note( $order_id, $created_by_user_class, $note, $note_type = null){
        $db = Yii::app ()->getDb ();
        $builder = $db->getCommandBuilder ();
        
         $sql = "select order_mixed_status_history_id ".
               " from order_mixed_status ".
               " where order_id = :order_id and is_current = 'Y' ";
         $order_mixed_status_history_id = $db->createCommand($sql)
                                             ->bindValue(':order_id',$order_id)
                                             ->queryRow();
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
}