<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once('includes/lib_invoice.php');
require_once('function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
admin_priv('jjshouse_picking');
if ($_REQUEST ['act'] == 'list') {
    $page = is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) ? $_REQUEST['page'] : 1;

    $shipping_list = Helper_Array::toHashmap((array)getShippingTypes(), 'shipping_id', 'shipping_name');

    $total = countShipmentJJshouseList(); // 总记录数

    $page_size = 50;  // 每页数量
    $total_page = ceil($total/$page_size);  // 总页数
    if ($page > $total_page) $page = $total_page;
    if ($page < 1) $page = 1;
    $parameter['offset'] = ($page - 1) * $page_size;
    $parameter['limit'] = $page_size;

    // 获取列表数据
    $list=getShipmentJJshouseList($parameter);
    // 分页
    $pagination = new Pagination($total, $page_size, $page, 'page', $url = 'shipment_list_jjhouse.php?act=list', null, $extra_params);

    $smarty->assign('pagination', $pagination->get_simple_output());
    $smarty->assign ('shipping_list',$shipping_list);
    $smarty->assign ('jjshouse_list',$list);
    $smarty->display('shipment/shipment_list_jjshouse.htm');
}
if($_REQUEST ['act'] == 'edit'){
    $order_ids = $_REQUEST['id'];
    $shipping_id = $_REQUEST['shipping_id'];
    $arr = explode(",",$order_ids);
    updateShippingId($arr,$shipping_id);
    print "<script>window.location.href='shipment_list_jjhouse.php?act=list'</script>";
}
function updateShippingId($orders,$shipping_id){

    $party_id         = $_SESSION ['party_id'];
    $admin_name       = $_SESSION['admin_name'];
    $shippingInfo     = getShippingInfo($shipping_id);

    $shipping_name = $shippingInfo['shipping_name'];
    $carrier_id = $shippingInfo['default_carrier_id'];

    $handle = soap_get_client('ShipmentService');

    foreach ($orders as $order_id){
        $sqlCheckUpdate = "
            select count(order_id) as num from `ecshop`.`ecs_order_action`
            where action_note like 'jjhouse分拣%' and order_id = '$order_id' ";
        $check = mysql_fetch_array($GLOBALS ['db']->query($sqlCheckUpdate));
        if($check['num'] == 0){
            //调用接口,实现配送方式的更改
            $shipmentIdSql = "
                select shipment_id from `romeo`.`order_shipment` 
                where order_id = '$order_id' ";
            $shipments = mysql_fetch_array($GLOBALS ['db']->query($shipmentIdSql));
            $shipmentId = $shipments['shipment_id'];
            $shipment = array(
                'shipmentId'      => $shipmentId,
                'partyId'         => $party_id,
                'shipmentTypeId'  => $shipping_id,
                'carrierId'       => $shippingInfo['default_carrier_id'],
                'trackingNumber'  => null,
                'status'          => 'SHIPMENT_INPUT',
                'lastModifiedByUserLogin' => $admin_name,
            );
            $response = $handle->updateShipment($shipment);
             
            $orderInfoSql = "
                select order_id,order_status,shipping_status,pay_status,invoice_status,shortage_status,carrier_bill_id 
                from `ecshop`.`ecs_order_info` 
                where order_id = '$order_id' ";
            $orderInfo = mysql_fetch_array($GLOBALS ['db']->query($orderInfoSql));
            $time = date("Y-m-d H:i:s");
             
            //更新ecs_order_info表中配送方式
            $updateOrderSql = "
                update `ecshop`.`ecs_order_info` set 
                shipping_id = '$shipping_id',
                shipping_name = '$shipping_name'
                where order_id = '$order_id' "; 
            $result = $GLOBALS ['db']->query ( $updateOrderSql );

            //操作order_info要在ecs_order_action表留下此动作的记录
            $action_node = "jjhouse分拣_{$shipping_name}";
            $insertOrderAction = "
                insert into `ecshop`.`ecs_order_action` (order_id,action_user,order_status,shipping_status,
                pay_status,action_time,action_note,invoice_status,shortage_status) 
                values('$order_id','$admin_name','{$orderInfo['order_status']}','{$orderInfo['shipping_status']}',
                '{$orderInfo['pay_status']}','{$time}','$action_node','{$orderInfo['invoice_status']}','{$orderInfo['shortage_status']}') ";
            $res = $GLOBALS ['db']->query ( $insertOrderAction );

            //更新ecs_carrier_bill表中的carrier_id
            $updateBillSql = "
                update `ecshop`.`ecs_carrier_bill` set carrier_id = '$carrier_id' 
                where bill_id = '{$orderInfo['carrier_bill_id']}' "; 
            $result = $GLOBALS ['db']->query ( $updateBillSql );

            //操作ecs_carrier_bill要在ecs_carrier_bill_action表中留下此动作的记录
            $insertBillActionSql = "
                insert into `ecshop`.`ecs_carrier_bill_action` (bill_id,action_time,action_user) 
                values('{$orderInfo['carrier_bill_id']}','$time','$admin_name')";
            $res = $GLOBALS ['db']->query ( $insertBillActionSql );
        }
    }
}
function getShipmentJJshouseList($parameter){
	$sql = "
		SELECT 		o.order_id
		FROM		ecshop.ecs_order_info o
		LEFT JOIN 	romeo.order_inv_reserved r on r.ORDER_ID = o.ORDER_ID
		WHERE  		r.STATUS = 'Y' 
		AND 		o.order_status = 1 
		AND 		o.shipping_status = 0 
		AND 		o.pay_status = 2 
		AND 		".party_sql('o.PARTY_ID')."
        AND 		o.order_id NOT IN(select order_id from `ecshop`.`ecs_order_action` where action_note like 'jjhouse分拣%' )
	";
	$ref_fields=$ref_rowset=array();
	$list=$GLOBALS ['db']->getAllRefby($sql,array('order_id'),$ref_fields,$ref_rowset);
	
	$sql = "
		SELECT 		o.order_id,o.order_sn,o.taobao_order_sn,o.country,o.zipcode,o.shipping_name,m.shipment_id
		FROM 		romeo.order_shipment m
		LEFT JOIN 	ecshop.ecs_order_info as o on o.order_id = m.ORDER_ID
		WHERE 		o.order_id ".db_create_in($ref_fields['order_id'])."
		ORDER BY 	m.order_id DESC 
		LIMIT 		{$parameter['offset']},{$parameter['limit']}
	";
    $list = $GLOBALS ['db']->getAll($sql);
    foreach ($list as $key => $item){
        $countrySQL = "
            select region_name from `ecshop`.`ecs_region` 
            where region_id = '{$item['country']}'";
        $country = mysql_fetch_array($GLOBALS ['db']->query($countrySQL));
        $list[$key]['country_name'] = $country['region_name'];
        $importantDaySql = "
            select attr_value from ecshop.order_attribute 
            where attr_name = 'important_day' and order_id = {$item['order_id']}";
        $important_day = mysql_fetch_array($GLOBALS ['db']->query($importantDaySql));
        $list[$key]['important_day'] = $important_day['attr_value'];
    }
    return $list;
}
function countShipmentJJshouseList(){
	$sql = "
		SELECT 		o.order_id
		FROM		ecshop.ecs_order_info o
		LEFT JOIN 	romeo.order_inv_reserved r on r.ORDER_ID = o.ORDER_ID
		WHERE  		r.STATUS = 'Y' 
		AND 		o.order_status = 1 
		AND 		o.shipping_status = 0 
		AND 		o.pay_status = 2 
		AND 		".party_sql('o.PARTY_ID')."
        AND 		o.order_id NOT IN(select order_id from `ecshop`.`ecs_order_action` where action_note like 'jjhouse分拣%' )
	";
	$ref_fields=$ref_rowset=array();
	$list=$GLOBALS ['db']->getAllRefby($sql,array('order_id'),$ref_fields,$ref_rowset);
	
	$sql = "
		SELECT 		count(*) as num
		FROM 		romeo.order_shipment m
		LEFT JOIN 	ecshop.ecs_order_info as o on o.order_id = m.ORDER_ID
		WHERE 		o.order_id " . db_create_in($ref_fields['order_id'])
	;
    $res = mysql_fetch_array($GLOBALS ['db']->query($sql));
    return $res['num'];
}