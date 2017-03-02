<?php
/**
 * karicare  相关脚本
 *
 */


class AutoCommand extends CConsoleCommand
{

    /**
     * 订单出库操作
     **/
    public function actionDeliver($orderId = null)
    {
        
        $db = Yii::app()->getDb();
                
        // 先找出最近改变的订单
        $orderList = array();
        $add_orderId = '';
        $sql = "select oi.* from ecshop.ecs_order_info oi 
                   INNER JOIN romeo.order_inv_reserved r on oi.order_id = r.order_id 
                 where oi.order_status = 1 and oi.shipping_status = 0 and oi.order_type_id in ('SALE', 'RMA_EXCHANGE', 'SHIP_ONLY')
                   and r.status = 'Y' %s ";
        if (!is_null($orderId)) {
            $add_orderId = "and oi.order_id = " . $orderId ;
        }

        $orderList = $db->createCommand(sprintf($sql, $add_orderId))->queryAll();
        $orderActionSQL = "INSERT INTO `ecshop`.`ecs_order_action` (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES (%d, %d, %d, %d, NOW(), '%s', 'system-command');" ;
        $carrierBillSQL = "update ecshop.ecs_carrier_bill set bill_no = '%s' where bill_id = '%d' LIMIT 1 ;" ;
        $shipmentSQL = "update romeo.order_shipment os, romeo.shipment s set s.tracking_number = '%s',  status = 'SHIPMENT_SHIPPED' where os.shipment_id = s.shipment_id and os.order_id = '%s'" ;
        foreach ($orderList as $order) {
            try{
            // 旧库存出库
            $orderItemList = array();
            $sql = "select * from ecshop.ecs_order_goods where order_id = %d " ;
            
            $orderItemList = $db->createCommand(sprintf($sql, $order['order_id']))->queryAll(); 
            foreach ($orderItemList as $orderItem) {
                $erpList = array(); 
                $erpSQL = "select * from ecshop.ecs_oukoo_erp where order_goods_id = %d " ;
                
                $erpList = $db->createCommand(sprintf($erpSQL, $orderItem['rec_id']))->queryAll();
            
                // 发货
                foreach ($erpList as $erp) {
                    $storagesSQL = "select e.facility_id, e.purchase_paid_amount, e.erp_goods_sn, e.in_sn, e.order_type, e.provider_id, e.is_new
                                       from ecshop.ecs_oukoo_erp e 
                                            left join ecshop.ecs_oukoo_erp as oute on e.in_sn = oute.out_sn
                                            left join ecshop.ecs_order_goods as og on e.order_goods_id = og.rec_id 
                                       where e.in_sn != '' and e.out_sn = '' 
                                         and e.facility_id = '%s' and e.is_new = 'NEW'
                                         and og.goods_id = '%d' and og.style_id = '%d'  
                                         and oute.out_sn is null 
                                       limit 1 ;";
                                       
                    $storages = $db->createCommand(sprintf($storagesSQL, $order['facility_id'], $orderItem['goods_id'], $orderItem['style_id']))->queryRow();
                    
                    // 旧库存出库
                    $oldUpdate = "update ecshop.ecs_oukoo_erp set facility_id = '%s', purchase_paid_amount = '%s'
                                        , erp_goods_sn = '%s', out_sn = '%s', action_user = '%s', order_type = '%s', provider_id = '%d', in_time = NOW()
                                        , last_update_time = NOW(), is_new = '%s' 
                                    where erp_id = '%d'" ;
                    
                    $result = $db->createCommand(sprintf($oldUpdate, $order['facility_id'], $storages['purchase_paid_amount']
                                                  , $storages['erp_goods_sn'], $storages['in_sn'], 'system-command', $storages['order_type']
                                                  , $storages['provider_id'], $storages['is_new'], $erp['erp_id']))->execute();
                    
                    // 新库存出库
                    $sql = "select product_id from romeo.product_mapping where ecs_goods_id = %d and ecs_style_id = %d limit 1 ;" ;
                    $productId = $db->createCommand(sprintf($sql, $orderItem['goods_id'], $orderItem['style_id']))->queryScalar();
                    $next_id = $db->createCommand("select max(next_hi) * 101 as next_hi from romeo.hibernate_unique_key")->queryScalar();
                    $inventoryItemSQL = "select * from romeo.inventory_item where product_id = '%s' and facility_id = '%s' and status_id = 'INV_STTS_AVAILABLE' and quantity_on_hand > 0 limit 1 ;";
                    
                    $inventoryItem = $db->createCommand(sprintf($inventoryItemSQL, $productId, $order['facility_id']))->queryRow();
                    
                    $inventoryTransactionSQL = "INSERT INTO `romeo`.`inventory_transaction` (`INVENTORY_TRANSACTION_ID`, `INVENTORY_TRANSACTION_TYPE_ID`, `AVAILABLE_TO_PROMISE`, `QUANTITY_ON_HAND`
                                         , `CREATED_STAMP`, `LAST_UPDATED_STAMP`, `LAST_UPDATED_TX_STAMP`, `CREATED_TX_STAMP`, `CREATED_BY_USER_LOGIN`, `LOT_ID`, `FROM_INVENTORY_ITEM_ID`
                                         , `TO_INVENTORY_ITEM_ID`, `ACCTG_TRANS_ENTRY_ID`, `ACCTG_TRANS_ID`, `FROM_FACILITY_ID`, `FROM_CONTAINER_ID`, `FROM_STATUS_ID`, `TO_FACILITY_ID`
                                         , `TO_CONTAINER_ID`, `TO_STATUS_ID`, `CANCELLATION_FLAG`) VALUES
                               ('%s', 'ITT_SALE', 1, 1, NOW(), NOW(), NULL, NULL, 'system-command', NULL, '%s', NULL, NULL, NULL, '%s', '%s', 'INV_STTS_AVAILABLE', NULL, NULL, NULL, NULL);" ;
                    
                    $next_id = $next_id + 1 ;
                    $result = $db->createCommand(sprintf($inventoryTransactionSQL, $next_id, $inventoryItem['INVENTORY_ITEM_ID']
                                                                                      , $inventoryItem['FACILITY_ID'], $inventoryItem['CONTAINER_ID']))->execute();
                                                                                      
                                                                                      
                    $detailSQL = "INSERT INTO `romeo`.`inventory_item_detail` (`INVENTORY_ITEM_DETAIL_ID`, `DESCRIPTION`, `INVENTORY_ITEM_ID`, `PHYSICAL_INVENTORY_ID`, `CREATED_STAMP`
                                           , `LAST_UPDATED_STAMP`, `LAST_UPDATED_TX_STAMP`, `CREATED_TX_STAMP`, `QUANTITY_ON_HAND_DIFF`, `AVAILABLE_TO_PROMISE_DIFF`, `ORDER_ID`
                                           , `ORDER_ITEM_SEQ_ID`, `SHIP_GROUP_SEQ_ID`, `SHIPMENT_ID`, `SHIPMENT_ITEM_SEQ_ID`, `WORK_EFFORT_ID`, `FIXED_ASSET_ID`, `MAINT_HIST_SEQ_ID`
                                           , `ITEM_ISSUANCE_ID`, `RECEIPT_ID`, `REASON_ENUM_ID`, `CANCELLATION_FLAG`, `INVENTORY_TRANSACTION_ID`, `ORDER_GOODS_ID`) VALUES
                                 ('%s', NULL, '%s', NULL, NOW(), NOW(), NULL, NULL, -1, -1, '%s', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N', '%s', '%s');" ; 
                    
                    $result = $db->createCommand(sprintf($detailSQL, ($next_id + 1), $inventoryItem['INVENTORY_ITEM_ID']
                                                                                      , $order['order_id'], $next_id, $orderItem['rec_id']))->execute();
                    
                    $result = $db->createCommand(sprintf("update romeo.inventory_item set quantity_on_hand = quantity_on_hand - 1, quantity_on_hand_total = quantity_on_hand_total - 1 where inventory_item_id = '%s' limit 1 ;"
                                                                                      , $inventoryItem['INVENTORY_ITEM_ID'] ))->execute();
                                                                                      
                    $result = $db->createCommand(sprintf("update romeo.inventory_summary set stock_quantity = stock_quantity -1 where product_id = '%s' and facility_id = '%s' and status_id = 'INV_STTS_AVAILABLE' and stock_quantity > 0 limit 1 ;"
                                                                                      , $productId, $order['facility_id']))->execute();
                
                    $result = $db->createCommand("update romeo.hibernate_unique_key set next_hi = next_hi + 1")->execute();
                    
                }
            }
            
            // 更新订单状态 添加备注
            $result = $db->createCommand(sprintf("UPDATE `ecshop`.`ecs_order_info` SET shipping_status = 9 WHERE order_id = %d LIMIT 1;", $order['order_id']))->execute();
            $result = $db->createCommand(sprintf($orderActionSQL, $order['order_id'], $order['order_status'], 9, $order['pay_status'], '配货出库'))->execute();
            
            // 打印面单
            $result = $db->createCommand(sprintf($carrierBillSQL, $next_id, $order['carrier_bill_id']))->execute();
            $result = $db->createCommand(sprintf($shipmentSQL, $next_id, $order['order_id']))->execute();
            
            $result = $db->createCommand(sprintf("UPDATE `ecshop`.`ecs_order_info` SET shipping_status = 8, shipping_time=UNIX_TIMESTAMP() WHERE order_id = %d LIMIT 1;", $order['order_id']))->execute();
            $result = $db->createCommand(sprintf($orderActionSQL, $order['order_id'], $order['order_status'], 8, $order['pay_status'], '扫描快递面单, 面单号为：'.$next_id))->execute();
            
            $result = $db->createCommand(sprintf("UPDATE `ecshop`.`ecs_order_info` SET shipping_time = UNIX_TIMESTAMP(), shipping_status = 1 WHERE order_id = %d LIMIT 1;", $order['order_id']))->execute();
            $result = $db->createCommand(sprintf($orderActionSQL, $order['order_id'], $order['order_status'], 1, $order['pay_status'], '物流操作发货'))->execute();
            
            if ($result > 0) {
                $db->createCommand(sprintf("update romeo.order_inv_reserved set status = 'F' where order_id = %d ;", $order['order_id']))->execute();
                $db->createCommand(sprintf("update romeo.order_inv_reserved_detail set status = 'F' where order_id = %d ;", $order['order_id']))->execute();
            }
            var_dump("订单：" . $order['order_id'] . " 已经操作出库了。");
            
            } catch (Exception $e) {}
            
        }
        
    }
    
    public function actionRejectedOrder ($orderId = null) {
        	// 拒收订单检索
        	$appoint_order = '' ;
        	$sql = "SELECT o.* FROM `ecshop`.`ecs_order_info` o 
                      WHERE (o.order_status = '4' OR o.shipping_status = '3') %s 
                        AND EXISTS ( SELECT 1 FROM `ecshop`.`ecs_order_goods` og 
                                        INNER JOIN `ecshop`.`ecs_oukoo_erp` e ON og.rec_id = e.order_goods_id 
                                      WHERE o.order_id = og.order_id AND e.out_sn != '' and e.is_returned = 0) ";
                                      
            if (!is_null($orderId)) {
            	$appoint_order = "and o.order_id = " . $orderId ;
         	}
         	 
         	$orderList = $db->createCommand(sprintf($sql, $appoint_order))->queryAll();
         	foreach ($orderList as $order) {
         		try {
         		  	// 初始化数据	
         			$sql = "";
         			
         			
         			
         		} catch (Exception $e) {}
         		
         		
         	}
        	
   }
    

}