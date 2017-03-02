<?php
/**
 * karicare  相关脚本
 *
 */


class KaricareCommand extends CConsoleCommand
{

    /**
     * 将我们的这里的订单同步到多美滋
     **/
    public function actionSyncOrderToKaricare($date = null)
    {
        $date = $date ? $date : date("Y-m-d", strtotime('-1 day'));
        define('PARTY_KARICARE', 65548);
        $wsdl_address = "http://221.133.247.221:669/CustomServices/Ecommerce/Ecommerce_OrderService.svc?wsdl";
        $db = Yii::app()->getDb();
                
        // 先找出最近改变的订单
        $order_ids = array();
        $sql = "
        select distinct(o.order_id)
        from ecshop.ecs_order_action oa
            inner join ecshop.ecs_order_info o on oa.order_id = o.order_id
            left join ecshop.ecs_order_goods og on o.order_id = og.order_id
        where
            o.party_id = ".PARTY_KARICARE." and
            o.distributor_id = 172 and
            o.order_type_id = 'SALE' and
            og.order_id is not null and
            oa.action_time >= '{$date}'
        ";
        $order_ids = $db->createCommand($sql)->queryColumn();
        
        if (empty($order_ids)) 
        {
            $this->log(" no orders update");
            return;
        }
        
        $order_ids_str = join(',', $order_ids);
        // 获得要同步的订单
        $sql = "select 
        o.order_id,
        o.taobao_order_sn, 
        (select attr_value 
         from ecshop.order_attribute oa 
         where oa.order_id = o.order_id and oa.attr_name = 'TAOBAO_USER_ID' limit 1) as taobao_user_name,
        o.order_status,
        concat_ws(' ', province.region_name, city.region_name, district.region_name, o.address) as address,  
        o.zipcode,
        o.tel,
        o.email, 
        o.order_amount,
        o.shipping_fee,
        o.consignee,
        s.TRACKING_NUMBER,
        es.shipping_name as carrier_name,
        o.order_time, 
        o.bonus, 
        if( exists(select 1 from ecshop.service s where s.order_id = o.order_id) , '1', '0') as after_sale_flag
        from ecshop.ecs_order_info o
        left join ecshop.ecs_region province on province.region_id = o.province
        left join ecshop.ecs_region city on city.region_id = o.city
        left join ecshop.ecs_region district on district.region_id = o.district
        left join romeo.order_shipment os on os.order_id = o.order_id
        left join romeo.shipment s on s.shipment_id = os.shipment_id
        left join ecshop.ecs_shipping es on o.shipping_id = es.shipping_id
        where
        o.order_id in ({$order_ids_str})";
        
//        --left join ecshop.ecs_carrier_bill cb on o.carrier_bill_id = cb.bill_id
//        --left join ecshop.ecs_carrier c on cb.carrier_id = c.carrier_id
        $orders = Yii::app()->getDb()->createCommand($sql)->queryAll();
        if (empty($orders))
        {
            $this->log("hvae order_ids , no order");
            return;
        }
        
        // 选择出 order_item
        // 如果一个 order_item 中有多条出库记录，只能选择order_item里面的一条出库记录里的 track_number 和 production_date  
        $sql = "select og.*, g.sku, st.track_number, st.production_date
        from ecshop.ecs_order_goods og
        left join ecshop.ecs_goods g on og.goods_id = g.goods_id
        left join romeo.inventory_item_detail id on id.order_goods_id = cast(og.rec_id as char) and id.quantity_on_hand_diff < 0
        left join romeo.inventory_item ii on id.inventory_item_id = ii.inventory_item_id
        left join ecshop.serialnum_track_item as sti on sti.serial_number=ii.SERIAL_NUMBER
        left join ecshop.serialnum_track as st on st.serialnum_track_id=sti.serialnum_track_id and st.goods_id=og.goods_id
        where og.order_id in ({$order_ids_str}) 
        group by og.rec_id ";
        $_order_items = Yii::app()->getDb()->createCommand($sql)->queryAll();
        
        $order_items = array();   
        foreach ($_order_items as $order_item)
        {
            $order_items[$order_item['order_id']][] = $order_item;
        }
        
        $client = new SoapClient($wsdl_address);
        $orderStatusMapping = array(
            0 => '未确认',
            1 => '已确认',
            2 => '取消',
            4 => '拒收',
        );
        
        // 不断调用接口
        foreach ($orders as $order) 
        {
            //----------------------------------------*实体赋值给数组*----------------------------------------//
            //创建stdClass()
            $pm = new stdClass();
            //给token赋值
            $pm->token = "a6d31a2a-050a-4842-ac26-8c8a8d5755ef";

            //给Order的属性赋值
            $pm->orderExtend->TaobaoOrderSn = $order['taobao_order_sn'];
            $pm->orderExtend->TaobaoUserName = $order['taobao_user_name'] ? $order['taobao_user_name'] : "unkown";
            $pm->orderExtend->UserName = $order['consignee'];
            $pm->orderExtend->OrderStatusName = $orderStatusMapping[$order['order_status']];
            $pm->orderExtend->Address = $order['address'];
            $pm->orderExtend->Zipcode = $order['zipcode'];          
            
            // 电话号码必须要求数字,要么为空了
            $pm->orderExtend->Tel = $order['tel'];
            
            $pm->orderExtend->Email = $order['email'];
            $pm->orderExtend->OrderAmount = (float) $order['order_amount'];
            $pm->orderExtend->ShippingFee = (float) $order['shipping_fee'];
            $pm->orderExtend->CarrierNumber = $order['bill_no'] ? $order['bill_no'] : "unkown number";
            $pm->orderExtend->CarrierName = $order['carrier_name'];
            $pm->orderExtend->OrderTime = $order['order_time'];

            // possible values of role : Mother , Father , Grandmother, Grandfather, Other
            $pm->orderExtend->Role = 'Mother';
            $pm->orderExtend->Bonus = (float) $order['bonus'];
            $pm->orderExtend->AfterSaleFlag = (String ) $order['after_sale_flag'];
            $pm->orderExtend->ParentId = 0;

            //给OrderItem的属性赋值  
            $i = 0;
            foreach ($order_items[$order['order_id']] as $order_item) 
            {
                $pm->orderExtend->OrderItem[$i]->ID = $i;
                $pm->orderExtend->OrderItem[$i]->TaotaoOrderSn = $pm->orderExtend->TaobaoOrderSn;
                // goods_name 有长度限制 
                $pm->orderExtend->OrderItem[$i]->GoodsName = $order_item['goods_name'];
                $pm->orderExtend->OrderItem[$i]->GoodsNumber = $order_item['goods_number'];
                $pm->orderExtend->OrderItem[$i]->GoodsPrice =(float) $order_item['goods_price'];
                
                // sku  不能为空
                $pm->orderExtend->OrderItem[$i]->Sku = $order_item['sku'];
                
                // productionTime要合法
                $pm->orderExtend->OrderItem[$i]->ProductionTime = $order_item['production_date'];
                
                $pm->orderExtend->OrderItem[$i]->TrackNumber = $order_item['track_number'];
                $pm->orderExtend->OrderItem[$i]->PromotionName = $order_item['subtitle'];
                
                $i++;
            }
            
            try 
            {
                //----------------------------------------*调用WCF接口*----------------------------------------//
                //调用WCF接口中的synchronizeOrder()方法操作数据库,保存Order,OrderItem的信息.
                $webServiceOrder = $client->synchronizeOrder($pm);
                //返回结果
                $wsResultOrder = $webServiceOrder->synchronizeOrderResult;
                //打印token验证是否成功的信息OK或者是Failed.
                $this->log( $order['order_id'] . " {$pm->orderExtend->TaobaoOrderSn} update result ". $wsResultOrder );
            }
            catch(Exception $e) 
            {
                var_dump($pm);
                $this->log( $order['order_id'] . " exception " . $e->getMessage() );
            }
        }
    }
    
    
    private function log($m)
    {
        print date('c') . " " . $m . "\r\n";
    }
	

}