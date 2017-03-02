<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';

class WarninventoryCommand extends CConsoleCommand{
    public function actionIndex(){
        $this->run(array('InventoryAlertReport'));
        $this->run(array('UpdateTaobaoGoodsReserveQuantity'));
    }
    public function actionInventoryAlertReport(){
        $dj_sql = "     select 
                            party.party_id, ifnull(p.product_name, g.goods_name) as name, isum.AVAILABLE_TO_RESERVED,  party.name as party_name, g.warn_number, f.facility_name
                        from
                            romeo.inventory_summary isum
                        left join 
                            romeo.product_mapping pm on isum.product_id = pm.product_id
                        left join
                            romeo.product p on pm.product_id  = p.product_id
                        left join
                        	ecshop.ecs_goods g on g.goods_id = pm.ecs_goods_id
                        left join
                        	romeo.party on party.party_id = cast(g.goods_party_id as char)
                        left join 
                            romeo.facility f on f.facility_id = isum.facility_id
                        where
                            isum.status_id = 'INV_STTS_AVAILABLE' and
                            g.`is_on_sale` = '1' and
                            g.sale_status = 'normal' and
                            g.warn_number > 0 and 
                            g.goods_party_id  in ('16','65547','65550','65539', '65553', '65558') and
                            isum.AVAILABLE_TO_RESERVED <= g.warn_number
                        group by isum.product_id ,isum.facility_id
                    ";
        $warning_lists = Yii::app ()->getComponent('slave')->createCommand ( $dj_sql )->queryAll ();
        $html = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            </head>
            <body style="font-size:12px;color:#000;">
            <div style="margin:auto;width:800px;">
            <h3>库存警报（'. date('Y-m-d H:i') .'）</h3>
            <table width="100%" border="1px;">
                    <tr>
                        <th>No.</th>
                        <th>商品名称</th>
                        <th>所属业务</th>
                        <th>警报数量</th>  
                        <th>仓库/库存数量</th>
                    </tr>   
            ';
        $dj_html = $jqs_html = $fg_html = $bq_html = $qc_html = $jbl_html = "";
        foreach ($warning_lists as $warning_list) {
            if ($warning_list['party_id'] == '16') {
                $dj_lists[] = $warning_list;
            }
            if($warning_list['party_id'] == '65547'){
                $jqs_lists[] = $warning_list;
            }
            if($warning_list['party_id'] == '65550'){
                $fg_lists[] = $warning_list;
            }
            if($warning_list['party_id'] == '65539'){
                $bq_lists[] = $warning_list;
            }
            if($warning_list['party_id'] == '65553'){
                $qc_lists[] = $warning_list;
            }
            if($warning_list['party_id'] == '65558'){
                $jbl_lists[] = $warning_list;
            }
        }
        if(!empty($dj_lists)){
            $dj_html = $this->GetHtml($dj_lists);
        }
        if(!empty($jqs_lists)){
            $jqs_html = $this->GetHtml($jqs_lists);
        }
        if(!empty($fg_lists)){
            $fg_html = $this->GetHtml($fg_lists);
        }
        if(!empty($bq_lists)){
            $bq_html = $this->GetHtml($bq_lists);
        }
        if(!empty($qc_lists)){
            $qc_html = $this->GetHtml($qc_lists);
        }
        if(!empty($jbl_lists)){
            $jbl_html = $this->GetHtml($jbl_lists);
        }
       try {
               $mail = Helper_Mail::smtp();
               $mail->CharSet='UTF-8';
               $mail->Subject="库存警报";
               $mail->SetFrom('erp@leqee.com', 'ERP定时库存警报任务');
               $mail->IsHTML(true);
               
               $dj_toEmail = array(
                              array('email' => 'bbgfx@i9i8.com', 'name' => '步步高电教分销组') ,
                            );
               $this->SendEmail($html, $dj_html, $dj_toEmail, '电教', $mail);
               
               $jqs_toEmail = array(
                                 array('email' => 'lwlei@leqee.com', 'name' => '雷林伟') ,
                                 array('email' => 'csong@i9i8.com', 'name' => '宋驰') ,
                            );
               $this->SendEmail($html, $jqs_html, $jqs_toEmail, '金奇仕', $mail);
              
               $fg_toEmail = array(
                                array('email' => 'xtlai@leqee.com', 'name' => '来秀婷') ,
                            );
               $this->SendEmail($html, $fg_html, $fg_toEmail, '方广', $mail);
               
               $bq_toEmail = array(
                               array('email' => 'jtguo@i9i8.com', 'name' => '郭俊廷' ) ,
                               array('email' => 'lwlei@leqee.com', 'name' => '雷林伟') ,
                           );
               $this->SendEmail($html, $bq_html, $bq_toEmail, '贝亲', $mail);
               
               $qc_toEmail = array(
                               array('email' => 'jding@leqee.com', 'name' => '丁静') ,
                               array('email' => 'llin@leqee.com', 'name' => '林琳') ,
                               array('email' => 'ymzheng@i9i8.com', 'name' => '郑雅敏') ,
                           );
               $this->SendEmail($html, $qc_html, $qc_toEmail, '雀巢', $mail);
               
               $jbl_toEmail = array(
                               array('email' => 'xrlao@i9i8.com', 'name' => '劳祥睿') ,
                               array('email' => 'zhyan@i9i8.com', 'name' => '闫泽红') ,
                               array('email' => 'yfzhu@i9i8.com', 'name' => '朱一飞') ,
                               array('email' => 'jxiao@leqee.com', 'name' => '肖均匀') ,
                               array('email' => 'jdchen@i9i8.com', 'name' => '陈继丁') ,
                           );
               $this->SendEmail($html, $jbl_html, $jbl_toEmail, '金佰利', $mail);
       }
       catch (phpmailerException $e) {
           Yii::log('邮件发送失败，'.$e->getMessage(),CLogger::LEVEL_ERROR,'app.commands.'.$this->getName());
       }
    }
    private function log($m) {
        print date('Y-m-d H:i:s')."　" . $m . "\r\n";
    }
    /**
     * html模板
     * @param array $lists
     */
    private function GetHtml( $lists ){
        $get_html = null;
        if(!empty($lists)){
            $i = 1;
            foreach ($lists as $list) {
                 $get_html .=  '<tr>
                    <td>'.$i.'</td>
                	<td>'.$list['name'].'</td>
                    <td>'.$list['party_name'].'</td>
                    <td>'.$list['warn_number'].'</td>
                    <td>'.$list['facility_name'].'/'.$list['AVAILABLE_TO_RESERVED'].'</td>
                    </tr>';
                $i++;
            }
            $get_html .= '</table></div></body></html>';
        }
        return $get_html;
    }
    /**
     * 邮件发送
     * @param string $html
     * @param string $_html
     * @param array $to (二维数组 email name)
     * @param string $party_name
     * @param 邮件的句柄  $mail
     */
    private function SendEmail($html ,$_html, $to, $party_name, $mail){
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
            } else {
                $this->log("send email to {$party_name} failed");
            }  
        }else {
            $this->log("{$party_name}没有库存警报");
        }
    }
    /**
     * 根据商品每月的销量，计算出商品平均每日销量，再计算商品的预留库存数量
     * 需要每天执行
     * todo 
     */
    public function actionUpdateTaobaoGoodsReserveQuantity() {
        $db = Yii::app ()->getDb();
        $start_time = date("Y-m-d H:i:s", time() - 30*24*3600);
        $end_time = date("Y-m-d H:i:s", time());
        $sql_goods = "
            select * from ecshop.ecs_taobao_goods where is_auto_reserve = 1;
        ";
        $taobao_goods_list = Yii::app()->getComponent('slave')->createCommand ($sql_goods)->queryAll();
        foreach ($taobao_goods_list as $key => $taobao_goods) {
            $sql = "
                select sum(og.goods_number) / 30
                from ecshop.ecs_order_info o
                left join ecshop.ecs_order_goods og on o.order_id = og.order_id
                where o.order_time >= '{$start_time}' and o.order_time < '{$end_time}' 
                    and og.goods_id = '{$taobao_goods['goods_id']}' and og.style_id = '{$taobao_goods['style_id']}'
                    and o.order_status <> 2 and o.order_type_id = 'sale'
                ";
            $reserve_quantity =  Yii::app()->getComponent('slave')->createCommand ($sql)->queryScalar();
            //每天的预留量
            $reserve_quantity = max(1, ceil($reserve_quantity * 2));
            if ($reserve_quantity != $taobao_goods['reserve_quantity']) {
                $update_goods = "update ecshop.ecs_taobao_goods set reserve_quantity = '{$reserve_quantity}' where taobao_goods_id = :taobao_goods_id;";
                $update=$db->createCommand($update_goods)->bindValue(':taobao_goods_id', $taobao_goods['taobao_goods_id'])->execute();
                $this->log("taobao_goods_id：" .  $taobao_goods['taobao_goods_id'] . " old_reserve_quantity：" . $taobao_goods['reserve_quantity']
                . " new_reserve_quantity：" . $reserve_quantity );
            } else {
                $this->log("taobao_goods_id：" .  $taobao_goods['taobao_goods_id'] . " not need update.");
            }
        }
    }
    /**
     * 商品库存小于预留库存数量时进行邮件报警
     * 
     */
    public function actionWarnGoodsDaySales() {
        $html = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            </head>
            <body style="font-size:12px;color:#000;">
            <div style="margin:auto;width:800px;">
            <h3>库存警报（'. date('Y-m-d H:i') .'）</h3>
            <table width="100%" border="1px;">
            ';
        $dj_html = $jqs_html = $fg_html = $bq_html = $qc_html = $jbl_html = "";
        $sql = "
            select CONCAT(g.goods_name,IFNULL(tg.style_id,'')) as name, sum(ris.available_to_reserved) as number, tg.reserve_quantity, g.goods_party_id
            from romeo.inventory_summary ris 
            left join romeo.product_mapping  pm on ris.product_id = pm.product_id 
            left join ecshop.ecs_taobao_goods tg on tg.goods_id = pm.ecs_goods_id and tg.style_id = pm.ecs_style_id 
            left join ecshop.ecs_goods g on g.goods_id = tg.goods_id 
            left join ecshop.ecs_style s on s.style_id = tg.style_id
            where g.`is_on_sale` = '1' 
                and g.sale_status = 'normal' 
                and ris.status_id = 'INV_STTS_AVAILABLE'
            group by pm.product_id
            having sum(ris.available_to_reserved) <= tg.reserve_quantity 
        ";
        $warning_lists = Yii::app ()->getComponent('slave')->createCommand($sql)->queryAll();
        foreach ($warning_lists as $item) {
            if ($item['goods_party_id'] == '16') {
                $item['goods_party_id'] = '乐其电教';
                $dj_lists[] = $item;
            }
            if($item['goods_party_id'] == '65547'){
                $item['goods_party_id'] = '金奇仕';
                $jqs_lists[] = $item;
            }
            if($item['goods_party_id'] == '65550'){
                $item['goods_party_id'] = '方广';
                $fg_lists[] = $item;
            }
            if($item['goods_party_id'] == '65539'){
                $item['goods_party_id'] = '贝亲';
                $bq_lists[] = $item;
            }
            if($item['goods_party_id'] == '65553'){
                $item['goods_party_id'] = '雀巢';
                $qc_lists[] = $item;
            }
            if($item['goods_party_id'] == '65558'){
                $item['goods_party_id'] = '金佰利';
                $jbl_lists[] = $item;
            }
        }
        $arr = array('序号', '商品名字', '实际库存', '预留库存数量','组织');
        if(!empty($dj_lists)){
            $dj_html = $this->GetGoodsHtml($arr, $dj_lists);
        }
        if(!empty($jqs_lists)){
            $jqs_html = $this->GetGoodsHtml($arr, $jqs_lists);
        }
        if(!empty($fg_lists)){
            $fg_html = $this->GetGoodsHtml($arr, $fg_lists);
        }
        if(!empty($bq_lists)){
            $bq_html = $this->GetGoodsHtml($arr, $bq_lists);
        }
        if(!empty($qc_lists)){
            $qc_html = $this->GetGoodsHtml($arr, $qc_lists);
        }
        if(!empty($jbl_lists)){
            $jbl_html = $this->GetGoodsHtml($arr, $jbl_lists);
        }
        try {
            $mail = Helper_Mail::smtp();
            $mail->CharSet='UTF-8';
            $mail->Subject="预留库存警报";
            $mail->SetFrom('erp@leqee.com', 'ERP定时预留库存警报任务');
            $mail->IsHTML(true);
             
            $dj_toEmail = array(
            array('email' => 'jrpei@i9i8.com', 'name' => '裴君蕊'),
            );
            $this->SendEmail($html, $dj_html, $dj_toEmail, '电教', $mail);
             
            $jqs_toEmail = array(
            array('email' => 'jrpei@i9i8.com', 'name' => '裴君蕊'),
            );
            $this->SendEmail($html, $jqs_html, $jqs_toEmail, '金奇仕', $mail);

            $fg_toEmail = array(
            array('email' => 'jrpei@i9i8.com', 'name' => '裴君蕊'),
            );
            $this->SendEmail($html, $fg_html, $fg_toEmail, '方广', $mail);
             
            $bq_toEmail = array(
            array('email' => 'jrpei@i9i8.com', 'name' => '裴君蕊'),
            );
            $this->SendEmail($html, $bq_html, $bq_toEmail, '贝亲', $mail);
             
            $qc_toEmail = array(
            array('email' => 'jrpei@i9i8.com', 'name' => '裴君蕊'),
            );
            $this->SendEmail($html, $qc_html, $qc_toEmail, '雀巢', $mail);
             
            $jbl_toEmail = array(
            array('email' => 'jrpei@i9i8.com', 'name' => '裴君蕊'),
            );
            $this->SendEmail($html, $jbl_html, $jbl_toEmail, '金佰利', $mail);
        }
        catch (phpmailerException $e) {
            Yii::log('邮件发送失败，'.$e->getMessage(),CLogger::LEVEL_ERROR,'app.commands.'.$this->getName());
        }
    }

    
    /**
     * html模板
     * @param array $arr
     * @param array $lists
     */
    private function GetGoodsHtml($arr, $lists){
        $get_html = null;
        if(!empty($lists)){
            $i = 1;
            $get_html .=  '<tr>';
            foreach ($arr as $item) {
                $get_html .=  '<th>'. $item .'</th>';
            }
            $get_html .=  '</tr>';
            foreach ($lists as $list) {
                $get_html .=  '<tr><td>'.$i.'</td>';
                foreach ($list as $item) {
                    $get_html .=  '
                     <td>'. $item .'</td>';
                }
                $get_html .= '</tr>';
                $i++;
            }
            $get_html .= '</table></div></body></html>';
        }
        return $get_html;
    }
}










