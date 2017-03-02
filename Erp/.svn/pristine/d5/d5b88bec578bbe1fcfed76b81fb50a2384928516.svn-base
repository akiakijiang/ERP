<?php

define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';

/**
 * 待审核退款申请中的email发送
 * @author jrpei
 *
 */

class RefundapplicationCommand extends CConsoleCommand{
     
    public function actionIndex(){
        $this->run(array('GetRefundList'));
    }
    public function actionGetRefundList(){
        //审核       currentchecker =2 物流                    currentchecker=3 财务
        $wl_conditions = array('limit' => 10, 'offset' => 0, 'partyId' => '0','status' =>'RFND_STTS_IN_CHECK', 'currentchecker' => '2');
        $cw_conditions = array('limit' => 10, 'offset' => 0, 'partyId' => '0','status' =>'RFND_STTS_IN_CHECK', 'currentchecker' => '3');
        $refundService = Yii::app()->getComponent('romeo')->RefundService;
        $wl_results = $refundService->getRefundByCondition($wl_conditions);
        $cw_results = $refundService->getRefundByCondition($cw_conditions);
        $html = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            </head>
            <body style="font-size:12px;color:#000;">
            <div style="margin:auto;width:800px;">
            <h3>待审核退款申请（'. date('Y-m-d H:i') .'）</h3>
            ';
         $html .= '
                <table width="100%" border="1px;">
                    <tr>
                        <th>No.</th>
                        <th>订单号</th>
                        <th>所属业务</th>
                        <th>客服审核人</th>
                        <th>客服审核时间</th>                   
                            ';
        if ($cw_results->return->count !== 0) {
            $cw_html = $html . '
                      <th>物流审核人</th>
                      <th>物流审核时间</th>
                      </tr>
                     ';
        } else {
            $html .= '</tr>';
        }
        $db = Yii::app ()->getDb ();
        //获取物流待审核列表
        if ($wl_results->return->count !== 0) {
            $i = 1;
            foreach ($wl_results->return->result->Refund as $wl_refund){
                $wl_orderId[] = $wl_refund->orderId;
            }
            $wl_orderId = join(',' , array_unique($wl_orderId));
            $sql = "select o.order_id,o.order_sn,p.name from ecshop.ecs_order_info o 
    				left join romeo.party p on p.party_id = o.party_id
    				where o.order_id IN (" . $wl_orderId . ")";
            $wl_order_infos = $db->createCommand ( $sql )->queryAll ();
            
            foreach ($wl_order_infos as $wl_order_info){
                $wl_order_sn[$wl_order_info["order_id"]] = $wl_order_info["order_sn"];
                $wl_party_name[$wl_order_info["order_id"]] = $wl_order_info["name"];
            }
            foreach ($wl_results->return->result->Refund as $wl_result){
                if (!array_key_exists($wl_result->orderId, $wl_order_sn)) {
                    continue;
                }
                $html .= '
                        <tr align="center">
                            <td>'. $i .'</td>
                            <td>'. $wl_order_sn["{$wl_result->orderId}"] .'</td>
                            <td>'. $wl_party_name["{$wl_result->orderId}"] .'</td>
                            <td>'. $wl_result->checkUserLogin1  .'</td>
                            <td>'. date("Y-m-d H:i:s",strtotime($wl_result->checkDate1)) .'</td>
                            </tr>
                          ';
                $i++;
            }
        }
        //获取财务待审核列表
        if ($cw_results->return->count !== 0) {
            $j = 1;
            foreach ($cw_results->return->result->Refund as $cw_refund){
                $cw_orderId[] = $cw_refund->orderId;
            }
            $cw_orderId = join(',' , array_unique($cw_orderId));
            $sql = "select o.order_id,o.order_sn,p.name from ecshop.ecs_order_info o 
    				left join romeo.party p on p.party_id = o.party_id
    				where o.order_id IN (" . $cw_orderId . ")";
            $cw_order_infos = $db->createCommand ( $sql )->queryAll ();
            
            foreach ($cw_order_infos as $cw_order_info){
                $cw_order_sn[$cw_order_info["order_id"]] = $cw_order_info["order_sn"];
                $cw_party_name[$cw_order_info["order_id"]] = $cw_order_info["name"];
            }
            foreach ($cw_results->return->result->Refund as $cw_result){
                if (!array_key_exists($cw_result->orderId, $cw_order_sn)) {
                    continue;
                }
                $cw_html .= '
                        <tr align="center">
                            <td>'. $j .'</td>
                            <td>'. $cw_order_sn["{$cw_result->orderId}"] .'</td>
                            <td>'. $cw_party_name["{$cw_result->orderId}"] .'</td>
                            <td>'. $cw_result->checkUserLogin1  .'</td>
                            <td>'. date("Y-m-d H:i:s",strtotime($cw_result->checkDate1)) .'</td>
                            <td>'. $cw_result->checkUserLogin2  .'</td>
                            <td>'. date("Y-m-d H:i:s",strtotime($cw_result->checkDate2)) .'</td>
                            </tr>
                          ';
                $j++;
            }
        }
       $html .= '</table></div></body></html>';
       $cw_html .= '</table></div></body></html>';
       try {
           //Helper_Mail::send($name, $email, $subject, $content);
               $mail = Helper_Mail::smtp();
               $mail->CharSet='UTF-8';
               $mail->Subject="待审核退款申请";
               $mail->SetFrom('erp@leqee.com', 'ERP定时任务');
               $mail->IsHTML(true);
               if ($cw_results->return->count !== 0) {
                   $mail->AddAddress('caiwu@i9i8.com',   '财务组');
                   $mail->Body=$cw_html;
                   $mail->send();
               }
               if ($wl_results->return->count !== 0) {
                   $mail->ClearAddresses();
                   $mail->AddAddress('hzwl@i9i8.com',   '物流组');
                   $mail->Body=$html;
                   $mail->send();
                   
               } 
       }
       catch (phpmailerException $e) {
           Yii::log('邮件发送失败，'.$e->getMessage(),CLogger::LEVEL_ERROR,'app.commands.'.$this->getName());
       }
    }

}