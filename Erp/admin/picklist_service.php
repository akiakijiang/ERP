<?php

/**
 * 批拣服务
 * 
 * @author yxiang@leqee.com
 * @copyright 2010 oleqee.com
 */

define('IN_ECS', true);
$_COOKIE['AUTH']['admin_id'] = '101';
$_COOKIE['AUTH']['admin_pass'] = 'a7b756ba17112ca29ef5cb190a5951b7';

require_once('includes/init.php');
require_once('function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

$ACT=isset($_REQUEST['act'])?$_REQUEST['act']:null;
switch($ACT)
{
    /**
     * 取得未完成的批拣任务列表及详细信息
     */
    case 'picklist.list.get':
    	// 批拣任务列表 
        $sql="select PICKLIST_ID,CREATED_STAMP from romeo.picklist where STATUS = 'PICKLIST_ASSIGNED' order by LAST_UPDATE_STAMP desc";
        $list=$db->getAllRefby($sql,array('PICKLIST_ID'),$ref_fields1,$ref_rowset1);
        if($list)
        {  
            // 取得批拣的Shipment列表
            $sql="select SHIPMENT_ID,PICKLIST_ID,TRACKING_NUMBER from romeo.shipment where shipping_category = 'SHIPPING_SEND' and PICKLIST_ID ".db_create_in($ref_fields1['PICKLIST_ID']);
            $ref_fields2=$ref_rowset2=array();
            $result=$db->getAllRefby($sql,array('PICKLIST_ID'),$ref_fields2,$ref_rowset2);
            foreach($list as $key => $item){
                if(isset($ref_rowset2['PICKLIST_ID'][$item['PICKLIST_ID']])){
                    $list[$key]['shipment']=$ref_rowset2['PICKLIST_ID'][$item['PICKLIST_ID']]; 
                }
            }

            // 取得批拣任务的Product列表
            $sql="
                select 
                    pi.PICKLIST_ID,pi.PRODUCT_ID,pi.ORDER_ID,pi.ORDER_ITEM_ID,pi.QUANTITY,pi.STATUS_ID,
                    s.SHIPMENT_ID,s.TRACKING_NUMBER,s.PARTY_ID,
                    pm.ECS_GOODS_ID,pm.ECS_STYLE_ID,'' as BARCODE,
                    IF(ISNULL(s.color),g.goods_name,CONCAT_WS(' ',g.goods_name,s.color)) as PRODUCT_NAME 
                from
                    romeo.picklist_item as pi 
                    left join romeo.order_shipment os on os.ORDER_ID=pi.ORDER_ID
                    left join romeo.shipment s on s.SHIPMENT_ID=os.SHIPMENT_ID
                    left join romeo.product_mapping as pm on pm.PRODUCT_ID=pi.PRODUCT_ID
                    left join ecshop.ecs_goods as g on g.goods_id=pm.ECS_GOODS_ID
                    left join ecshop.ecs_goods_style as gs on gs.goods_id=g.goods_id and gs.style_id=pm.ECS_STYLE_ID and gs.is_delete=0
                    left join ecshop.ecs_style as s on s.style_id=gs.style_id
                where s.shipping_category = 'SHIPPING_SEND' and
                    pi.PICKLIST_ID ".db_create_in($ref_fields1['PICKLIST_ID']);
            $ref_fields3=$ref_rowset3=array();
            $result3=$db->getAllRefby($sql,array('PICKLIST_ID'),$ref_fields3,$ref_rowset3);
            
            // 取得产品的barcode
            $barcode_static=array();
            $sql3="select IFNULL(gs.barcode,g.barcode) from ecs_goods g left join ecs_goods_style gs on gs.goods_id=g.goods_id and gs.style_id='%d' where g.goods_id='%d'";
            foreach($result3 as $k=>$v){
                $inventoryItemType=getInventoryItemType($v['ECS_GOODS_ID']);
                if($inventoryItemType=='NON-SERIALIZED'){
                    // 贝亲有条码
                    if($v['PARTY_ID']==65539){
                        $goods_style_id=$v['ECS_GOODS_ID'].'_'.$v['ECS_STYLE_ID'];
                        if(!isset($barcode_static[$goods_style_id])){
                            $barcode=$slave_db->getOne(sprintf($sql3,$v['ECS_STYLE_ID'],$v['ECS_GOODS_ID']));
                            if(!$barcode){
                                $barcode='未知';
                            }
                            $barcode_static[$goods_style_id]=$barcode;
                        }
                        $result3[$k]['BARCODE']=$barcode_static[$goods_style_id];
                    }
                    // 其他无串号控制商品
                    else{
                        $result3[$k]['BARCODE']=encode_goods_id($v['ECS_GOODS_ID'],$v['ECS_STYLE_ID']);    
                    }
                }
                $result3[$k]['INVENTORY_ITEM_TYPE_ID']=$inventoryItemType;
            }
            
            foreach($list as $key => $item){
                if(isset($ref_rowset3['PICKLIST_ID'][$item['PICKLIST_ID']])){
                    $list[$key]['picklist_item']=$ref_rowset3['PICKLIST_ID'][$item['PICKLIST_ID']]; 
                }
            }

            echo json_encode($list);
        }
        
        break;
    
    /**
     * 通过串号取得商品
     */
    case 'picklist.product.get':
        $serial_number=$_REQUEST['serial_number'];
        if($serial_number){
            $sql="
                select
                    PRODUCT_ID 
                from
                    romeo.inventory_item 
                where 
                    QUANTITY_ON_HAND_TOTAL>0 and INVENTORY_ITEM_TYPE_ID='SERIALIZED' and SERIAL_NUMBER='%s' and
                    STATUS_ID in ('INV_STTS_AVAILABLE','INV_STTS_USED')
            ";
            $productId=$db->getOne(sprintf($sql,$serial_number));
            if($productId){
                echo $productId;
            }
        }
        break;

    /**
     * 设置整个批拣任务完成
     */
    case 'picklist.list.picked':
        $picklist_id=$_REQUEST['picklist_id'];
        if($picklist_id){
            try{
                $handle=soap_get_client('PicklistService');
                $handle->setPicklistToPicked(array(
                    'picklistId'=>$picklist_id,
                    'lastModifiedByUserLogin'=>'Webservice',
                ));
                echo 1;                
            }
            catch (Exception $e){
                echo 0;
            }
        }
        break;
        
    /**
     * 设置一个批拣项目为完成
     */
    case 'picklist.item.complete':
        $picklist_item_id=$_REQUEST['picklist_item_id'];
        if($picklist_item_id){
            try{
                $handle=soap_get_client('PicklistService');
                $handle->updatePicklistItem(array(
                    'picklistItemId'=>$picklist_item_id,
                    'status'=>'PICKITEM_COMPLETED',
                ));
                echo 1;
            }
            catch (Exception $e) {
                echo 0;
            }
        }
        break;
    
    /**
     * 设置一个配送单元的状态为已拣货
     */
    case 'picklist.shipment.picked':
        $shipment_id=$_REQUEST['shipment_id'];
        if($shipment_id){
            try{
                $handle=soap_get_client('ShipmentService');
                $handle->updateShipment(array(
                    'shipmentId'=>$shipment_id,
                    'status'=>'SHIPMENT_PICKED',
                    'lastModifiedByUserLogin'=>'Webservice',
                ));
                echo 1;
            }
            catch(Exception $e){
                echo 0;
            }
        }
        break;
    
        
    default:
        exit(1);
}