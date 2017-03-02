<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once ROOT_PATH . 'admin/config.vars.php';
/**
 * 多美滋相关
 */

class DumexCommand extends CConsoleCommand {
    /**
     * 多美滋组织
     */
    private $partyId = 65540;

    /**
     * 请把cron job 的执行时间设置到如下两个时间段
     *
     */
    public function actionIndex() {
        $g = date ( 'G' ); // 0-23


        // 只在凌晨执行，每天执行一次
        if ($g >= 0 && $g <= 3){
            $this->actionDailyMsgLog ();
        }
         

        // 只在早上执行，每天执行一次
        if ($g >= 9 && $g <= 10){
            $this->actionSendMsg ();
        }
    }

    /**
     * 每日发送给多美滋公司的报表
     */
    public function actionDailyReport() {

    }

    /**
     * 为第一次购买指定商品的用户发送短信，发送条件为：
     * 估计这个产品可以吃多久，为将吃完的用户发短信
     */
    public function actionSendMsg() {
        // 奶粉对应的可以吃的天数
        static $static = array ('35692_0' => '7', '35715_0' => '3', '35695_0' => '3', '35687_0' => '7',
                                '35710_0' => '3', '35688_0' => '7', '35689_0' => '7', '35712_0' => '3', 
                                '35711_0' => '3', '35691_0' => '7', '35714_0' => '3', '35690_0' => '7', 
                                '35713_0' => '3', '35694_0' => '3', '35718_0' => '7', '35705_0' => '3', 
                                '35717_0' => '7', '35704_0' => '3', '35716_0' => '7', '35703_0' => '3', 
                                '35693_0' => '3', '35894_0' => '3', '35724_0' => '10', '35723_0' => '7', 
                                '35709_0' => '3', '35920_0' => '3', '35725_0' => '7', '35707_0' => '3', 
                                '35721_0' => '7', '35722_0' => '10', '35706_0' => '3', '35720_0' => '10', 
                                '35895_0' => '3', '35719_0' => '7' );

        $db = Yii::app ()->getDb ();
        $reader = $db->createCommand ( "select * from ecs_msg_log where party_id=:party_id and send='N'" )->bindValue ( ':party_id', $this->partyId )->query ();
        // 对记录按用户分组，因为不管用户买了什么，同一个用户只发一次短信
        $list = array ();
        foreach ( $reader as $row ){
            $list [$row ['taobao_user_id']] [] = $row;
            if ($list === array ()){
                echo ("[" . date ( 'c' ) . "] 多美滋没有短信需要发送  \n");
            }
            else {
                echo ("[" . date ( 'c' ) . "] Dumex msg send start \n");
                foreach ( $list as $nick => $group ) {
                    $mobile = null; // 短信发送的手机号
                    $need = false; // 不需要发送短信（比如不在要发送短信的商品列表里）
                    $send = true; // 是等待发送还是立即发送


                    // 检查是否到达发送条件
                    foreach ( $group as $row ) {
                        $key = $row ['goods_id'] . '_' . $row ['style_id'];
                        if (isset ( $static [$key] )) {
                            if ($need === false)
                            $need = true;

                            if ($send === true) {
                                $day = $static [$key];
                                $timestamp = strtotime ( $row ['shipping_time'] );
                                if ($timestamp !== false && (time () - $timestamp) < 3600 * 24 * $day)
                                $send = false;
                            }
                        }
                        if (empty ( $mobile ) && ! empty ( $row ['mobile'] ))
                        $mobile = $row ['mobile'];
                    }
                    if (! $need) {
                        echo ("|  all the goods the user (" . $nick . ") bought not need send message \n");
                        // 更新状态为不需要发送
                        $command = $db->createCommand ( "update ecs_msg_log set send='-' where log_id=:log_id" );
                        foreach ( $group as $row )
                        $command->bindValue ( ':log_id', $row ['log_id'] )->execute ();
                    }
                    else if ($send) {
                        // 发送短信
                        $message = "亲爱的顾客:您好,感谢您对淘宝多美滋官方旗舰店的支持,您收到这条短信7天内,来我们店铺购买商品将享受免邮服务!";
                        if (! empty ( $mobile )) {
                            // TODO 使用多美滋的applictionKey, 这个需要在配置文件中配置
                            $success = Yii::app ()->getComponent ( 'msg' )->send ( $message, $mobile, "11b038f042054e27bbb427dfce973307" );
                            echo ("|  send message to user (" . $nick . ") with result " . var_export ( $success, true ) . " \n");

                            if ($success) {
                                // 更新状态为已发送
                                $command = $db->createCommand ( "update ecs_msg_log set send='Y' where log_id=:log_id" );
                                foreach ( $group as $row )
                                $command->bindValue ( ':log_id', $row ['log_id'] )->execute ();
                            }
                        }
                    }
                    else{
                        echo ("|  user (" . $nick . ") not need send message for the moment \n");
                    }
                }
            }
        }
    }

    /**
     * 更新多美滋上的购买历史, 每天执行一次
     *
     * 根据所查询的出库记录，写入log中，同一淘宝用户购买的同一商品，只有一条记录
     */
    public function actionDailyMsgLog() {
        // 查询多美滋当天的出库记录
        $ended_time = date ( 'Y-m-d H:i:s' );
        $start_time = date ( 'Y-m-d H:i:s', time () - 24 * 3600 );
        echo ("[" . date ( 'c' ) . "] Dumex update msg log, search inventory history between " . $start_time . " and " . $ended_time . "\n");

        $db = Yii::app ()->getDb ();
        $sql = "
            select 
                o.order_id, o.mobile, o.order_sn, o.order_type_id, og.goods_id, og.style_id, iid.CREATED_STAMP
            from
                ecshop.ecs_order_info as o
                left join ecshop.ecs_order_goods as og on og.order_id=o.order_id
                left join romeo.inventory_item_detail as iid on cast(iid.ORDER_ID as unsigned) = o.order_id
                left join romeo.inventory_item as ii on ii.INVENTORY_ITEM_ID=iid.INVENTORY_ITEM_ID
            where
                o.order_type_id='SALE' and o.party_id='" . $this->partyId . "' and
                ii.STATUS_ID IN ('INV_STTS_AVAILABLE', 'INV_STTS_USED', 'INV_STTS_DEFECTIVE') and
                iid.QUANTITY_ON_HAND_DIFF < 0 and iid.CANCELLATION_FLAG != 'Y' and
                iid.CREATED_STAMP between '" . $start_time . "' and '" . $ended_time . "'
            group by
                o.order_id, og.goods_id, og.style_id
        ";
        $list = $db->createCommand ( $sql )->queryAll ();
        if ($list !== array ()) {
            $table = $db->getSchema ()->getTable ( 'ecs_msg_log' );
            $builder = $db->getCommandBuilder ();
            $static = array ();
            foreach ( $list as $key => $row ) {
                // 取得订单的淘宝用户
                if (isset ( $static [$row ['order_id']] ))
                $taobaoUserId = $static [$row ['order_id']];
                else {
                    $taobaoUserId = $db->createCommand ( "select attr_value from order_attribute where attr_name='TAOBAO_USER_ID' and order_id =:order_id" )->bindValue ( ':order_id', $row ['order_id'], PDO::PARAM_INT )->queryScalar ();
                    $static [$row ['order_id']] = $taobaoUserId;
                }
                if ($taobaoUserId === false)
                continue;

                // 查询是否要创建
                // 买过这个商品的同一个用户不需要创建新纪录
                $exists = $db->createCommand ( "select * from ecs_msg_log where party_id=:party_id and goods_id=:goods_id and style_id=:style_id and taobao_user_id=:taobao_user_id" )->bindValue ( ':party_id', $this->partyId )->bindValue ( ':goods_id', $row ['goods_id'] )->bindValue ( ':style_id', $row ['style_id'] )->bindValue ( ':taobao_user_id', $taobaoUserId )->queryRow ();
                if ($exists === false) {
                    $data = array ('party_id' => $this->partyId, 'goods_id' => $row ['goods_id'], 'style_id' => $row ['style_id'], 'mobile' => $row ['mobile'], 'taobao_user_id' => $taobaoUserId, 'shipping_time' => $row ['CREATED_STAMP'], 'send' => 'N' );
                    $success = ( boolean ) $builder->createInsertCommand ( $table, $data )->execute ();
                }
            }
        }
    }
    
    /**
     * $start_time默认值为null,表示从前一天00:00:00开始,也可以自定开始时间,格式如 2011-10-26 15:08:23
     * hours表示从前一天00:00:00开始,多少小时的订单(操作此订单)的更新到外部分系统的情况,默认是24小时
     * $start_date表示检查多少天前下的订单(下订单时间)更新到外部系统的情况,默认是14天前的00:00:00开始的,$dates表示检查多少天的订单更新情况,默认1天
     * $toEmail表示订单更新到外部系统出错,发邮件的电子邮箱
     * */
    
    public function actionCheckOutSystem($start_time = null, $hours = 24, $start_date = null, $days = 1, $toEmail = null){
        
        global $_CFG;
        $wsdl = trim(DUMEX_SYNC_URL);
        
        if($start_time == null){
            $start_times = date ( "Y-m-d ", time ()- 3600 * 24 );
        }
        else{
            $start_times = $start_time;
        }
        
        $end_time = date ( "Y-m-d H:i:s", strtotime($start_times) + 3600 * $hours);
        
        if ($start_date == null) {
            $start_time_ago = date("Y-m-d ", time() - 3600 * 24 * 14);
        } else {
            $start_time_ago = $start_date;
        }
        $end_time_ago = date("Y-m-d H:i:s", strtotime($start_time_ago) + 3600 * 24 * $days);
        
        $message = '';
        $db = Yii::app ()->getDb ();
        $client = new SoapClient ( $wsdl );
        $pm = new stdClass ();
        $pm->Token = "ADA84AC5-F58F-4858-8387-79E83555B6C0";
        
        $action_sql = "
             SELECT DISTINCT o.taobao_order_sn,o.order_time,o.order_status,o.shipping_status FROM `ecshop`.`ecs_order_action` as a
             LEFT JOIN `ecshop`.`ecs_order_info` as o ON o.order_id = a.order_id
             WHERE o.party_id = 65540 AND o.pay_id = 1 AND o.distributor_id = 164 AND o.taobao_order_sn NOT LIKE '%-F' 
             AND a.action_time > '$start_times' AND a.action_time < '$end_time' AND o.order_type_id = 'SALE' 
             AND (a.shipping_status IN (2,3) OR a.order_status = 2 OR (a.order_status = 1 AND a.shipping_status = 1)) 
             AND (o.shipping_status IN (2,3) OR o.order_status = 2 OR (o.order_status = 1 AND o.shipping_status = 1))
             AND NOT EXISTS (select 1 from `ecshop`.`order_attribute` oa where oa.order_id = o.order_id and oa.attr_name = 'Dumex_Website')";
        
        $order_sql = "
             SELECT DISTINCT o.taobao_order_sn,o.order_time,o.order_status,o.shipping_status FROM `ecshop`.`ecs_order_info` as o
             WHERE o.party_id = 65540 AND o.pay_id = 1 AND o.distributor_id = 164 AND o.taobao_order_sn NOT LIKE '%-F' 
             AND o.order_time  > '$start_time_ago' AND o.order_time < '$end_time_ago' AND o.order_type_id = 'SALE' 
             AND (o.shipping_status IN (2,3) OR o.order_status = 2 OR (o.order_status = 1 AND o.shipping_status = 1))
             AND NOT EXISTS (select 1 from `ecshop`.`order_attribute` oa where oa.order_id = o.order_id and oa.attr_name = 'Dumex_Website') ";
        $action_res = $db->createCommand ( $action_sql )->queryAll ();
        $orders_res = $db->createCommand ( $order_sql )->queryAll ();
        $shipping_status = "";
        $order_status = "";
        $dumex_status = "";
        foreach ( $action_res as $order ) {

            $pm->ORD_ID = $order['taobao_order_sn'];
            $result = $client->QueryOrderStatus($pm);
            
            //根据调用查询接口的返回值来与当前订单在ecs_order_action中的订单状态对比,如果外部多美滋系统中的订单状态与Erp系统中订单状态不一致的话,会发邮件通知ERP组
            //接口返回值06表示订单已取消,12表示配送成功,14表示配送失败,order_status的状态中2,4均表示订单取消,shipping_status的状态中2表示收货确认,3表示拒收退回
            if(($result->QueryOrderStatusResult == 06 && ($order['order_status'] == 2 || $order['shipping_status'] == 3 )) ||
            ($result->QueryOrderStatusResult == 12 && ($order['shipping_status'] == 2 || $order['shipping_status'] == 1)) ||
            ($result->QueryOrderStatusResult == 14 && $order['shipping_status'] == 3 ) ||
            ($result->QueryOrderStatusResult == 10 && $order['shipping_status'] == 1 && $order['order_status'] == 1)){}
            else{
                $shipping = $order['shipping_status'];
                $orders = $order['order_status'];
                $dumex = $result->QueryOrderStatusResult;
                if (array_key_exists($shipping, $_CFG['adminvars']['shipping_status'])) {
                    $shipping_status = $_CFG['adminvars']['shipping_status'][$shipping];
                }
                else{
                    $shipping_status = $shipping;
                }
                if (array_key_exists($orders, $_CFG['adminvars']['order_status'])){
                    $order_status = $_CFG['adminvars']['order_status'][$orders];
                }
                else {
                    $order_status = $orders;
                }
                if (array_key_exists($dumex, $_CFG['adminvars']['dumex_order_status'])){
                    $dumex_status = $_CFG['adminvars']['dumex_order_status'][$dumex];
                }
                else {
                    $dumex_status = $dumex;
                }
                $message .= "{$order['taobao_order_sn']},{$order['order_time']},{$order_status},{$shipping_status},{$dumex_status}|";
            }
        }
        foreach ( $orders_res as $item ) {
            
            $pm->ORD_ID = $item['taobao_order_sn'];
            $result = $client->QueryOrderStatus($pm);
            
            //根据调用查询接口的返回值来与当前订单在ecs_order_action中的订单状态对比,如果外部多美滋系统中的订单状态与Erp系统中订单状态不一致的话,会发邮件通知ERP组
            //接口返回值06表示订单已取消,12表示配送成功,14表示配送失败,order_status的状态中2,4均表示订单取消,shipping_status的状态中2表示收货确认,3表示拒收退回
            if(($result->QueryOrderStatusResult == 06 && ($item['order_status'] == 2 || $item['shipping_status'] == 3)) ||
            ($result->QueryOrderStatusResult == 12 && ($item['shipping_status'] == 2 || $item['shipping_status'] == 1)) ||
            ($result->QueryOrderStatusResult == 14 && $item['shipping_status'] == 3 ) ||
            ($result->QueryOrderStatusResult == 10 && $item['shipping_status'] == 1 && $item['order_status'] == 1)){}
            else{
                $shipping = $item['shipping_status'];
                $orders = $item['order_status'];
                $dumex = $result->QueryOrderStatusResult;
                if (array_key_exists($shipping, $_CFG['adminvars']['shipping_status'])) {
                    $shipping_status = $_CFG['adminvars']['shipping_status'][$shipping];
                }
                else{
                    $shipping_status = $shipping;
                }
                if (array_key_exists($orders, $_CFG['adminvars']['order_status'])){
                    $order_status = $_CFG['adminvars']['order_status'][$orders];
                }
                else {
                    $order_status = $orders;
                }
                if (array_key_exists($dumex, $_CFG['adminvars']['dumex_order_status'])){
                    $dumex_status = $_CFG['adminvars']['dumex_order_status'][$dumex];
                }
                else {
                    $dumex_status = $dumex;
                }
                $message .= "{$item['taobao_order_sn']},{$item['order_time']},{$order_status},{$shipping_status},{$dumex_status}|";
            }
         }
        
        if($message != ''){
            
            if (empty($toEmail)) {
                $toEmail = array(
                    array('email' => 'ychen@i9i8.com', 'name' => '陈翼'),
                    array('email' => 'zwzheng@leqee.com', 'name' => '郑臻炜'),
                    array('email' => 'jrpei@i9i8.com', 'name' => '裴君蕊'),
                    array('email' => 'jwang@i9i8.com', 'name' => '王健'),
                );
            }
            $this->checkOutErrorEmail($message,$toEmail);
        }
    }
    public function checkOutErrorEmail($message,$toEmail){
        
        $arr = explode("|",$message);
        foreach ($arr as $key => $item){
            $order_list[$key] = explode(",",$item);
        }
        $html = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            </head>
            <body style="font-size:12px;color:#000;">
            <div style="margin:auto;width:800px;">
            <h3>未更新到外部多美滋系统的订单（'.date('Y-m-d H:i').'）</h3>
            <table width="100%" border="1px;">
                    <tr>
                        <th>订单号</th>
                        <th>订单时间</th>
                        <th>ERP订单状态</th>
                        <th>ERP配送状态</th>
                        <th>外部订单状态</th>
                    </tr> 
            ';
        $dumex_list_html = '';
        foreach ($order_list as $order){
            if($order[0]!=''){
                $dumex_list_html .= '<tr>';
                foreach ($order as $item){
                    $dumex_list_html .= '<td>'.$item.'</td> ';
                }
                $dumex_list_html .= '</tr>';
            }
        }
        $dumex_html = $dumex_list_html.' </table></div></body></html> ';
        
        try {
            $mail = Helper_Mail::smtp();
            $mail->CharSet='UTF-8';
            $mail->Subject = "订单更新到外部多美滋系统出错报告".date('Y-m-d H:i');
            $mail->SetFrom('erp@leqee.com', 'ERP定时更新到外部多美滋系统出错任务');
            $mail->IsHTML(true);

            $this->sendEmailCod($html, $dumex_html, $toEmail, '多美滋', $mail);
        }
        catch (phpmailerException $e) {
            Yii::log('邮件发送失败，'.$e->getMessage(),CLogger::LEVEL_ERROR,'app.commands.'.$this->getName());
        }
    }
    private function sendEmailCod($html ,$_html, $to, $party_name, $mail){
        $result_html = null;
        if (!empty($_html)) {
            $result_html = $html . $_html ;
            $mail->ClearAddresses();
            foreach ($to as $v) {
                $mail->AddAddress("{$v['email']}",   "{$v['name']}");
            }
            $mail->Body=$result_html;
            if ($mail->send()){
                $this->log("send email to {$party_name} successful");
            }
            else {
                $this->log("send email to {$party_name} failed");
            }
        }
        else {
            $this->log("{$party_name}没有订单异常");
        }
    }
    private function log($m) {
        print date('Y-m-d H:i:s')."　" . $m . "\r\n";
    }
     /**
     * shipping_status状态值2表示收货确认
     * shipping_status状态值3表示拒收退回
     * $start_time表示更新ERP操作的订单开始时间,默认值为null,表示前一天00:00:00开始的订单,也可以自定开始时间,格式如 2011-10-26 15:08:23
     * $hours表示更新从开始到结束的时间,默认是24小时
     * */
        
    public function actionUpdateOutSystemOrderStatus($start_time = 'null', $hours = 24) {
        $wsdl = trim(DUMEX_SYNC_URL);
        if($start_time == 'null'){
            $start_times = date ( "Y-m-d ", time ()- 3600 * 24 );
        }
        else{
            $start_times = $start_time;
        }
        
        $end_time = date ( "Y-m-d H:i:s", strtotime($start_times) + 3600 * $hours);        
        
        $sql = "
             SELECT  DISTINCT o.taobao_order_sn,o.shipping_status,o.order_id,o.order_status,o.distributor_id 
             FROM `ecshop`.`ecs_order_action` as a ,`ecshop`.`ecs_order_info` as o 
             WHERE a.order_id = o.order_id AND o.party_id = 65540 AND o.pay_id = 1 AND distributor_id = 164 
             AND a.action_time > '$start_times' AND a.action_time < '$end_time' AND order_type_id = 'SALE' 
             AND (a.shipping_status IN (2,3) OR a.order_status = 2 OR (a.order_status = 1 AND a.shipping_status = 1)) AND o.taobao_order_sn NOT LIKE '%-F'   
             AND (o.shipping_status IN (2,3) OR o.order_status = 2 OR (o.order_status = 1 AND o.shipping_status = 1)) 
             AND NOT EXISTS (select 1 from `ecshop`.`order_attribute` oa where oa.order_id = o.order_id and oa.attr_name = 'Dumex_Website')";
        $db = Yii::app ()->getDb ();
        $res = $db->createCommand ( $sql )->queryAll ();
        $client = new SoapClient ( $wsdl );
        $pm = new stdClass ();
        $pm->Token = "ADA84AC5-F58F-4858-8387-79E83555B6C0";
        foreach ( $res as $taobao_order_sn ) {
            if ($taobao_order_sn ['taobao_order_sn'] != '') {
                unset($pm->ORD_ID);
                unset($pm->ORD_STATUS);
                unset($pm->ORD_FAIL_REASON);
                unset($pm->ORD_DELIVERY_NO);
                $is_null = true;
                $pm->ORD_ID = $taobao_order_sn ['taobao_order_sn'];
                $sql = "SELECT s.tracking_number FROM ecshop.ecs_order_info oi
                        LEFT JOIN romeo.order_shipment os ON convert(oi.order_id using utf8) = os.order_id 
                        LEFT JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
                        WHERE oi.order_id = '{$taobao_order_sn ['order_id']}'  
                            AND s.shipping_category = 'SHIPPING_SEND' AND s.status = 'SHIPMENT_SHIPPED'
                        ORDER BY s.shipment_id DESC LIMIT 1";
                if ($taobao_order_sn ['shipping_status'] == 2) {
                    $pm->ORD_STATUS = "配送成功";
                    $tracking_number = $db->createCommand ($sql)->queryScalar();
                    $pm->ORD_DELIVERY_NO = "{$tracking_number}";
                    $is_null = false;
                } elseif ($taobao_order_sn ['shipping_status'] == 3) {
                    $pm->ORD_STATUS = '配送失败';
                    $node_sql = "
                          SELECT action_note 
                          FROM `ecshop`.`ecs_order_action`
                          WHERE order_id = '{$taobao_order_sn ['order_id']}' AND shipping_status = 3 
                          ORDER BY action_time limit 1 ";
                    $nodes = $db->createCommand ( $node_sql )->queryScalar ();
                    $status = stripos ( $nodes ['action_note'], '无人接收' );
                    $status2 = stripos ( $nodes ['action_note'], '拒收-货品不对' );
                    $status3 = stripos ( $nodes ['action_note'], '拒收-货品损坏' );
                    $status4 = stripos ( $nodes ['action_note'], '拒收-取消' );
                    if ($status !== false) {
                        $pm->ORD_FAIL_REASON = "无人接收";
                    }
                    if ($status2 !== false) {
                        $pm->ORD_FAIL_REASON = "拒收-货品不对";
                    }
                    if ($status3 !== false) {
                        $pm->ORD_FAIL_REASON = "拒收-货品损坏";
                    }
                    if ($status4 !== false) {
                        $pm->ORD_FAIL_REASON = "拒收-取消";
                    } else {
                        $pm->ORD_FAIL_REASON = "其它";
                    }
                    $tracking_number = $db->createCommand ($sql)->queryScalar();
                    $pm->ORD_DELIVERY_NO = "{$tracking_number}";
                    $is_null = false;
                } elseif ($taobao_order_sn ['order_status'] == 2) {
                    $pm->ORD_STATUS = "订单已取消";
                    $pm->ORD_DELIVERY_NO = '';
                    $is_null = false;
                } elseif ($taobao_order_sn ['order_status'] == 1 && $taobao_order_sn ['shipping_status'] == 1) {
                    //判断多美滋系统订单状态是否为已发货
                    $dumex_order_status = $client->QueryOrderStatus($pm);
                    $dumex_order_status_result = $dumex_order_status->QueryOrderStatusResult;
                    if ($dumex_order_status_result == 10) {
                        continue;
                    } else {
                        $tracking_number = $db->createCommand ($sql)->queryScalar();
                        $pm->ORD_STATUS = "已发货";
                        $pm->ORD_DELIVERY_NO = "{$tracking_number}";
                        $is_null = false;
                    }
                }
                if (! $is_null) {
                    try {
                        $result = $client->OrderStatusUpdate ( $pm );
                        $orderStatusUpdateResult = $result->OrderStatusUpdateResult;
                        $this->log($taobao_order_sn['taobao_order_sn']. " shipping_status: " . $taobao_order_sn['shipping_status']. " order_status: " . $taobao_order_sn['order_status'] . " result: " . $orderStatusUpdateResult);
                    } catch ( Exception $e ) {
                        $this->log($taobao_order_sn['taobao_order_sn']. " error: " . $e -> getMessage());
                    }
                }
            }
        }
    }
    /**
     * 推送多美滋快递单号
     */
    public function actionDumexUpdateTrackingNumber () {
    $wsdl = trim(DUMEX_SYNC_URL);
        $start_time = '2011-12-10 00:00:00';
        $sql = "
            SELECT DISTINCT oi.taobao_order_sn,oi.shipping_status,oi.order_id,oi.order_status,oi.distributor_id 
            FROM ecshop.ecs_order_info oi
            LEFT JOIN ecshop.ecs_order_action oa ON oi.order_id = oa.order_id
            WHERE oi.party_id = 65540 AND oi.pay_id = 1 AND oi.distributor_id = 164 AND oi.order_type_id = 'SALE' AND 
            oi.order_time >= '{$start_time}' AND oa.action_time > '{$start_time}' AND (oa.shipping_status IN (2,3) OR 
            oa.order_status = 2 OR (oa.order_status = 1 AND oa.shipping_status = 1)) AND oi.taobao_order_sn NOT LIKE '%-F' AND 
            (oi.shipping_status IN (2,3) OR oi.order_status = 2 OR (oi.order_status = 1 AND oi.shipping_status = 1))
        ";
        $db = Yii::app ()->getDb ();
        $order_list = $db->createCommand ($sql)->queryAll ();
        $client = new SoapClient ($wsdl);
        $pm = new stdClass ();
        $pm->Token = "ADA84AC5-F58F-4858-8387-79E83555B6C0";
        foreach ($order_list as $order) {
            if ($order['taobao_order_sn']) {
                unset($pm->ORD_ID);
                unset($pm->ORD_STATUS);
                unset($pm->ORD_FAIL_REASON);
                unset($pm->ORD_DELIVERY_NO);
                $pm->ORD_ID = $order['taobao_order_sn'];
                $is_null = true;
                $tracking_number_sql = "
                    SELECT s.tracking_number FROM ecshop.ecs_order_info oi
                    LEFT JOIN romeo.order_shipment os ON convert(oi.order_id using utf8) = os.order_id 
                    LEFT JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
                    WHERE oi.order_id = '{$order['order_id']}' AND s.last_update_tx_stamp > '{$start_time}' 
                        AND s.shipping_category = 'SHIPPING_SEND' AND s.status = 'SHIPMENT_SHIPPED'
                    ORDER BY s.shipment_id DESC LIMIT 1
                ";
                if ($order['shipping_status'] == 2) {
                    $tracking_number = $db->createCommand ($tracking_number_sql)->queryScalar();
                    $pm->ORD_DELIVERY_NO = "{$tracking_number}";
                    $pm->ORD_STATUS = "配送成功";
                    $is_null = false;
                } elseif ($order['shipping_status'] == 3) {
                    $tracking_number = $db->createCommand ($tracking_number_sql)->queryScalar();
                    $pm->ORD_DELIVERY_NO = "{$tracking_number}";
                    $pm->ORD_STATUS = '配送失败';
                    $action_note_sql = "
                        SELECT action_note 
                        FROM `ecshop`.`ecs_order_action`
                        WHERE order_id = '{$order['order_id']}' AND shipping_status = 3 
                        ORDER BY action_time limit 1
                    ";
                    $nodes = $db->createCommand ($action_note_sql)->queryScalar ();
                    $status = stripos($nodes ['action_note'], '无人接收' );
                    $status2 = stripos($nodes ['action_note'], '拒收-货品不对');
                    $status3 = stripos($nodes ['action_note'], '拒收-货品损坏');
                    $status4 = stripos($nodes ['action_note'], '拒收-取消');
                    if ($status !== false) {
                        $pm->ORD_FAIL_REASON = "无人接收";
                    }
                    if ($status2 !== false) {
                        $pm->ORD_FAIL_REASON = "拒收-货品不对";
                    }
                    if ($status3 !== false) {
                        $pm->ORD_FAIL_REASON = "拒收-货品损坏";
                    }
                    if ($status4 !== false) {
                        $pm->ORD_FAIL_REASON = "拒收-取消";
                    } else {
                        $pm->ORD_FAIL_REASON = "其它";
                    }
                    $is_null = false;
                } elseif ($order['order_status'] == 2) {
                    $pm->ORD_DELIVERY_NO = '';
                    $pm->ORD_STATUS = "订单已取消";
                    $is_null = false;
                } elseif ($order['order_status'] == 1 && $order['shipping_status'] == 1) {
                    $dumex_order_status = $client->QueryOrderStatus($pm);
                    $dumex_order_status_result = $dumex_order_status->QueryOrderStatusResult;
                    if ($dumex_order_status_result == 10) {
                        continue;
                    } else {
                        $tracking_number = $db->createCommand ($tracking_number_sql)->queryScalar();
                        $pm->ORD_DELIVERY_NO = "{$tracking_number}";
                        $pm->ORD_STATUS = "已发货";
                        $is_null = false;
                    }
                }
                if (! $is_null) {
                    try {
                        $result = $client->OrderStatusUpdate ( $pm );
                        //var_dump($result);
                    } catch ( Exception $e ) {
                        //print $e->getMessage();
                    }
                }
            }
        }
    }
    
    public function actionUpdateDumexWebsiteOrderStatus($start_time = null, $minute = 10) {
        $wsdl = trim(DUMEX_WEBSITE_SYNC_URL);
        if($start_time == null){
            $start_times = date ( "Y-m-d H:i:s", time ()- 60 * $minute );
        }
        else{
            $start_times = $start_time;
        }
        
        
        $end_time = date ( "Y-m-d H:i:s", strtotime($start_times) + 60 * $minute);        
        
        $shipping_mapping = array ("11" => "6", "36" => "7", "102" => "4");
        $sql = "
             SELECT  DISTINCT o.taobao_order_sn,o.shipping_status,o.order_id,o.order_status,o.distributor_id, o.mobile, o.shipping_id 
             FROM `ecshop`.`ecs_order_action` as a ,`ecshop`.`ecs_order_info` as o 
             WHERE a.order_id = o.order_id AND o.party_id = 65540 AND o.pay_id = 1 AND distributor_id = 164 
             AND a.action_time > '$start_times' AND a.action_time < '$end_time' AND order_type_id = 'SALE' 
             AND (a.shipping_status IN (2,3) OR a.order_status = 2 OR (a.order_status = 1 AND a.shipping_status = 1)) AND o.taobao_order_sn NOT LIKE '%-F'   
             AND (o.shipping_status IN (2,3) OR o.order_status = 2 OR (o.order_status = 1 AND o.shipping_status = 1)) 
             AND EXISTS (select 1 from `ecshop`.`order_attribute` oa where oa.order_id = o.order_id and oa.attr_name = 'Dumex_Website')";
        $db = Yii::app ()->getDb ();
        $res = $db->createCommand ( $sql )->queryAll ();
        $client = new SoapClient ( $wsdl );
        $pm = new stdClass ();
        //$token = "AllyesTestToken";  //测试链接
        $token = "ead7248efb38a9217755ea138cee6ff2";
        foreach ( $res as $taobao_order_sn ) {
            if ($taobao_order_sn ['taobao_order_sn'] != '') {
                unset($pm->orderID);
                unset($pm->mobile);
                unset($pm->orderStatus);
                unset($pm->shippingCompany);
                unset($pm->shippingSN);
                $pm->orderID = $taobao_order_sn ['taobao_order_sn'];
                $pm->shippingCompany = $shipping_mapping[$taobao_order_sn['shipping_id']];
                $pm->mobile = $taobao_order_sn['mobile'];
                
                $is_null = true;
                $sql = "SELECT s.tracking_number FROM ecshop.ecs_order_info oi
                        LEFT JOIN romeo.order_shipment os ON convert(oi.order_id using utf8) = os.order_id 
                        LEFT JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
                        WHERE oi.order_id = '{$taobao_order_sn ['order_id']}'  
                            AND s.shipping_category = 'SHIPPING_SEND' AND s.status = 'SHIPMENT_SHIPPED'
                        ORDER BY s.shipment_id DESC LIMIT 1";
                if ($taobao_order_sn ['shipping_status'] == 2) {
                    $pm->orderStatus = "12";
                    $tracking_number = $db->createCommand ($sql)->queryScalar();
                    $pm->shippingSN = "{$tracking_number}";
                    $is_null = false;
                } elseif ($taobao_order_sn ['shipping_status'] == 3) {
                    $pm->orderStatus = '14';
                    $tracking_number = $db->createCommand ($sql)->queryScalar();
                    $pm->shippingSN = "{$tracking_number}";
                    $is_null = false;
                } elseif ($taobao_order_sn ['order_status'] == 2) {
                    $pm->orderStatus = "06";
                    $pm->shippingSN = '';
                    $is_null = false;
                } elseif ($taobao_order_sn ['order_status'] == 1 && $taobao_order_sn ['shipping_status'] == 1) {
                        $tracking_number = $db->createCommand ($sql)->queryScalar();
                        $pm->orderStatus = "10";
                        $pm->shippingSN = "{$tracking_number}";
                        $is_null = false;
                }
                if (! $is_null) {
                    try {
                        $result = $client->setStatus ( $token,$pm );
                        $this->log($taobao_order_sn['taobao_order_sn']. " shipping_status: " . $taobao_order_sn['shipping_status']. " order_status: " . $taobao_order_sn['order_status'] . " result: " . $result . " website");
                    } catch ( Exception $e ) {
                        $this->log($taobao_order_sn['taobao_order_sn']. " error: " . $e -> getMessage());
                    }
                }
            }
        }
    }
}
