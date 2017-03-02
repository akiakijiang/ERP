<?php

/**
 * 查看拣选任务
 * 
 * @author yxiang@leqee.com
 * @copyright 2010 leqee.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
admin_priv('inventory_picklist');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
    
// 操作
$act = isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('input_tracking_number')) 
    ? $_REQUEST['act'] 
    : null;
    
// 查询批拣信息
if(isset($_REQUEST['picklist_id'])){
    try{
        $handle=soap_get_client('PicklistService');
        $response=$handle->getPicklist(array('picklistId'=>$_REQUEST['picklist_id']));
        $picklist=is_object($response->return)?$response->return:null;        
    }
    catch(Exception $e){
        $smarty->assign('message',$e->getMessage());   
    }
    
    // 取得配送
    $sql="select * from romeo.shipment where PICKLIST_ID = '%s'";
    $shipment_list=$db->getAll(sprintf($sql,$picklist->picklistId));
}
else {
    die('请输入参数');
}


if ($act=='input_tracking_number') {
    do
    {
        if($picklist->status!='PICKLIST_PRINTED') {
            $smarty->assign('message', "该批拣任务（picklistId:". $picklist->picklistId."）的状态不是已打印状态");
            break;
        }
        
        foreach($shipment_list as $shipment_key=>$shipment_item){
            $assigned=true;
            if(isset($_POST['item_list'][$shipment_item['SHIPMENT_ID']])){
                $trackingNumber=$_POST['item_list'][$shipment_item['SHIPMENT_ID']]['tracking_number'];
                try {
                    $handle=soap_get_client('ShipmentService');
                    $handle->updateShipment(array(
                        'shipmentId'=>$shipment_item['SHIPMENT_ID'],
                        'trackingNumber'=>$trackingNumber,
                        'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
                    ));
                    $shipment_list[$shipment_key]['TRACKING_NUMBER']=$trackingNumber;
                }
                catch (Exception $e) {
                    $smarty->assign('message', $e->getMessage());
                    $assigned=false;            
                }
            }
            else if($assigned){
                $assigned=false;
            }
        }
        
        if($assigned){
            try {
                $handle=soap_get_client('PicklistService');
                $handle->updatePicklist(array(
                    'picklistId'=>$picklist->picklistId,
                    'status'=>'PICKLIST_ASSIGNED',
                    'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
                ));
                $smarty->assign('message', '操作完成');
            }
            catch (Exception $e) {
                $smarty->assign('message', $e->getMessage());
            }
        }
    }while(false);
}
    
$smarty->assign('shipment_list',$shipment_list);
$smarty->assign('picklist',$picklist);
$smarty->display('shipment/picklist_assign.htm');
