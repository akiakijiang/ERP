<?php 

/**
 * 打印拣选的各种单据
 * 
 * @author yxiang@leqee.com
 * @copyright 2010 leqee.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');


// 请求
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('picking_list','express_list', 'deliver_list')) 
    ? $_REQUEST['act'] 
    : NULL ;
    
// 消息
$message =
    isset($_REQUEST['message']) && trim($_REQUEST['message'])
    ? $_REQUEST['message']
    : false;

if ($message) {
    $smarty->assign('message',$message);    
}


if (empty($_REQUEST['PICKLIST_ID'])) {
    die('没有批拣号');    
}

try {
    $handle=soap_get_client('PicklistService');
    $response=$handle->getPicklist(array('picklistId'=>$_REQUEST['PICKLIST_ID']));
    $picklist=$response->return;
    $smarty->assign('picklist',$picklist);
}
catch (Exception $e) {
    die('查询批拣错误：'. $e->getMessage());
}

// 动作
switch ($act) {
    /**
     * 打印拣货单
     */
    case 'picking_list' :
        $sql = "
            select
                p.PICKLIST_ID,
                p.FACILITY_ID,
                pi.PRODUCT_ID,
                pi.STATUS_ID,
                SUM(pi.QUANTITY) as QUANTITY,
                IF(ISNULL(s.color), g.goods_name, CONCAT_WS(' ', g.goods_name,s.color)) as PRODUCT_NAME,
                pm.ECS_GOODS_ID as GOODS_ID,
                pm.ECS_STYLE_ID as STYLE_ID
            from
                romeo.picklist p
                left join romeo.picklist_item pi on pi.PICKLIST_ID = p.PICKLIST_ID
                left join romeo.product pt on pt.PRODUCT_ID=pi.PRODUCT_ID 
                left join romeo.product_mapping pm on pm.PRODUCT_ID=pt.PRODUCT_ID
                left join ecshop.ecs_goods g on g.goods_id = pm.ECS_GOODS_ID
                left join ecshop.ecs_goods_style gs on gs.goods_id=g.goods_id and gs.style_id=pm.ECS_STYLE_ID and gs.is_delete=0
                left join ecshop.ecs_style s on s.style_id=gs.style_id
            where
                p.PICKLIST_ID = '%s'
            group by
                pi.PRODUCT_ID, pi.STATUS_ID
        ";
        $product_list = $db->getAll(sprintf($sql, $picklist->picklistId));
        $ref_fields=$ref_rowset=array();
        $list=$db->getAllRefby(sprintf($sql,$picklist->picklistId),array('PRODUCT_ID'),$ref_fields,$ref_rowset);
        if($list){
            // 取得产品的库位，并按库位排序
            $sql="
                select
                    pl.LOCATION_SEQ_ID,pl.PRODUCT_ID,
                    l.AREA_ID,l.AISLE_ID, l.SECTION_ID, l.LEVEL_ID, l.POSITION_ID
                from
                    romeo.product_facility_location pl
                    left join romeo.facility_location l on l.FACILITY_ID = pl.FACILITY_ID and l.LOCATION_SEQ_ID=pl.LOCATION_SEQ_ID
                where
                    pl.FACILITY_ID='{$picklist->facilityId}' and pl.PRODUCT_ID ".db_create_in($ref_fields['PRODUCT_ID'])."
            ";
            $ref_fields2=$ref_rowset2=array();
            $result2=$db->getAllRefby($sql,array('PRODUCT_ID'),$ref_fields2,$ref_rowset2);

            // 一个产品可能有多个库位
            // 按库位排序用
            $sort=array();  
            foreach($list as $key=>$item){
                $list[$key]['PRODUCT_CODE'] = encode_goods_id($item['GOODS_ID'],$item['STYLE_ID']);
                if(isset($ref_rowset2['PRODUCT_ID'][$item['PRODUCT_ID']])){
                    $first=reset($ref_rowset2['PRODUCT_ID'][$item['PRODUCT_ID']]);
                    $sort[$key]=$first['LOCATION_SEQ_ID'];
                    $list[$key]['LOCATION_SEQ_ID']=$first['LOCATION_SEQ_ID'];
                }
                else{
                    $sort[$key]=0;
                    $list[$key]['LOCATION_SEQ_ID']='';  
                }
            }

            array_multisort($sort,SORT_ASC,SORT_STRING,$list);
        }
        
        // 更新批拣任务为已打印
        if ($picklist->status=='PICKLIST_INPUT'){
            try {
                $handle=soap_get_client('PicklistService');
                $handle->updatePicklist(array(
                    'picklistId'=>$picklist->picklistId,
                    'status'=>'PICKLIST_PRINTED',
                    'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
                ));
            } catch (Exception $e) {
                
            }   
        }
        
        $smarty->assign('list', $list);
        $smarty->display('shipment/picklist_print_picking_list.htm');
        break;
    
    /**
     * 打印快递面单
     */ 
    case 'express_list' :
        $sql = "";
        break;
    
    /**
     * 打印发货单
     */
    case 'deliver_list' :
        $sql = "";
        break;
        
    default :
        die('没有指定打印单据类型');
}
