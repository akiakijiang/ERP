<?php
define ( 'IN_ECS', true );
require_once ('../includes/init.php');
require_once ('../includes/lib_invoice.php');
require_once ('../distribution.inc.php');
include_once(ROOT_PATH . 'admin/function.php'); 
include_once(ROOT_PATH . 'admin/config.vars.php'); 
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once('../includes/cls_pagination.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
admin_priv ( 'invoice_add' );

$invoiced_array = array('no' => '未开票', 'yes' => '已开票','ALL' => '全部' );
$smarty->assign('invoiced_array', $invoiced_array);
$invoiced = $_REQUEST['invoiced'];
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$size = 20;
$start = ($page - 1) * $size;
$party_id = $_SESSION ['party_id'];
    //搜索

    $order_search = trim($_POST ['order_sn']);

    $distributor_list = (array)$slave_db->getCol("SELECT d.distributor_id FROM ecshop.distributor d LEFT JOIN main_distributor m ON m.main_distributor_id = d.main_distributor_id WHERE m.type = 'zhixiao' 
        and m.party_id = {$party_id}       
    ".' -- invoice_add '.__LINE__.PHP_EOL);
    array_push($distributor_list, 0);
    $condition = "";
    $start_time = $_REQUEST['start_time'];
    $end_time = $_REQUEST['end_time'];
    $invoiced = $_REQUEST['invoiced'];

    // 开始时间默认为当天
    if (strtotime($start_time) == 0) {
        $start_time = date('Y-m-d',strtotime("-30days"));
        $_REQUEST['start_time'] = $start_time;
    }   
    if (strtotime($start_time) > 0) {
        $condition .= " AND o.order_time >= '{$start_time}' ";
    }
 
    // 结束时间默认为当天
    if (strtotime($end_time) == 0) {
        $end_time = date('Y-m-d');
        $_REQUEST['end_time'] = $end_time;
    }
    if (strtotime($end_time) > 0) {
       $end_time = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end_time)));
       $condition .= " AND o.order_time <= '{$end_time}' ";
    }
    if($order_search != ''){
        $condition .= " AND o.order_sn = '{$order_search}'";
    }
    if($invoiced == 'yes'){
        $condition .= " AND osi.shipping_invoice is not null ";
    }else if($invoiced == 'no'){
    $condition .= " AND (osi.shipping_invoice is null or osi.shipping_invoice = '') ";
    }
    
if ($_REQUEST ['act'] == 'search') {
    $sql = "SELECT o.order_id, o.order_sn, o.consignee, o.inv_payee, o.shipping_time,o.order_amount,
        o.shipping_fee, o.shipping_name, o.pay_name, o.distributor_id, o.province,o.zipcode, 
        o.tel, o.mobile, o.country,o.province,o.city,o.district,o.address, o.handle_time,
        s.TRACKING_NUMBER,s.created_stamp,osi.shipping_invoice
        from ecshop.ecs_order_info o force index(order_info_multi_index,order_sn) 
        left join ecshop.ecs_invoice_addr as i on i.order_id = CONVERT(o.order_id USING utf8)
        left join romeo.shipment as s on s.shipment_id = i.shipment_id
        left join romeo.order_shipping_invoice osi on o.order_id = osi.order_id
        WHERE
        o.order_type_id = 'SALE' AND
        ". db_create_in($distributor_list, 'o.distributor_id') ." AND -- 直销的订单
        ". party_sql('o.party_id') ."{$condition}
        order by o.order_id ASC
        LIMIT {$size} OFFSET {$start} 
        ".' -- invoice_add '.__LINE__.PHP_EOL;
    $sql_c = "SELECT COUNT(1)
        from ecshop.ecs_order_info o force index(order_info_multi_index,order_sn) 
        left join ecshop.ecs_invoice_addr as i on i.order_id = CONVERT(o.order_id USING utf8)
        left join romeo.shipment as s on s.shipment_id = i.shipment_id
        left join romeo.order_shipping_invoice osi on o.order_id = osi.order_id
        WHERE
        o.order_type_id = 'SALE' AND
        ". db_create_in($distributor_list, 'o.distributor_id') ." AND -- 直销的订单
        ". party_sql('o.party_id') ."{$condition} 
         order by o.order_id ASC
        ".' -- invoice_add '.__LINE__.PHP_EOL;    
        $invoiceList = $GLOBALS['db']->getAll($sql);
       // QLog::log($sql);
//         var_dump(date('Y-m-d H:i:s', time()));
        
       $count = $slave_db->getOne($sql_c);
       $pager = Pager($count, $size, $page);
       $new = explode('page=',$pager);
       for($i = 0;$i < count($new)-1;$i++){
           $new[$i] = $new[$i]."start_time=".$start_time."&end_time=".$end_time."&page=";
       }
       $pagers = implode($new);
       $smarty->assign('pager', $pagers);
       $smarty->assign('count',$count);
       $party_id = $_SESSION ['party_id'];
        if($order_search != ''){
                $order_info = getReceivingOrder($order_search);
                $praty_id = $_SESSION ['party_id'];
                if($party_id != $order_info['party_id']){
                    die("请选择正确的业务组织！<a href=\"javascript:history.go(-1)\">返回</a>");
                }
                if($order_info['province_name'] == null){
                    die("请输入正确的订单号！<a href=\"javascript:history.go(-1)\">返回</a>");
                }
                $invoiceList = array();
                $invoiceList = getInvoiceInfo($order_info['order_id']);
                $province_list = Helper_Array::toHashmap((array)get_regions(1, $GLOBALS['_CFG']['shop_country']), 'region_id', 'region_name');
                $shipping_list = Helper_Array::toHashmap((array)getShippingTypes(), 'shipping_id', 'shipping_name');
                $province_id = array_search($invoiceList[0]['province_name'],$province_list);
                //获取省下面的市
                $sql1 = "SELECT region_id,region_name
                FROM ecshop.ecs_region
                WHERE parent_id = '{$province_id}'";
                $list1 = $GLOBALS['db']->getAll($sql1);
                $city_list = Helper_Array::toHashmap((array)$list1,'region_id','region_name');
                $city_id = array_search($invoiceList[0]['city_name'],$city_list);
                //获取市下面的县、区
                $sql2 = "SELECT region_id,region_name
                FROM ecshop.ecs_region
                WHERE parent_id = '{$city_id}'".' -- invoice_add '.__LINE__.PHP_EOL;
                $list2 = $GLOBALS['db']->getAll($sql2);
                $district_list = Helper_Array::toHashmap((array)$list2,'region_id','region_name');
                $district_id = array_search($invoiceList[0]['district_name'],$district_list);
                /*
                added by wliu 2016.1.7 for limit status
                */
                $if_ship_sql = "SELECT o.shipping_status FROM ecshop.ecs_order_info o WHERE o.order_sn = '{$order_search}'";
        		$if_ship = $GLOBALS['db']->getOne($if_ship_sql);
        		if(in_array($party_id,array(65587,65593))){
        			$if_ship = 1;
        		}
        }
          
       $smarty->assign('province_id',$province_id);
       $smarty->assign('party_id', $party_id);
	   $smarty->assign('city_list',$city_list);
	   $smarty->assign('city_id',$city_id);
	   $smarty->assign('district_list',$district_list);
	   $smarty->assign('district_id',$district_id);
	   $smarty->assign('party_list',array(65616,65621,65622,65539,65574)); // 大王,贝亲,百吉福,Babynes,金宝贝  业务组默认寄圆通
	   $smarty->assign ('message', $message);
	   $smarty->assign ('province_list', $province_list);
	   $smarty->assign ('invoiceList', $invoiceList);
	   $smarty->assign ('shipping_list',$shipping_list);
	   $smarty->assign ('order_sn',$order_search);
	   $smarty->assign ('order_info',$order_info);
	   $smarty->assign ('if_ship',$if_ship);
}
//导出csv列表
if($_REQUEST['csv'] == '1'){
	    $party_id = $_SESSION ['party_id'];
	    $invoiced_name = $invoiced_array[$invoiced];
	    $sql = "select name
                from romeo.party 
                where party_id = '{$party_id}'";
	    $result = $GLOBALS['db']->getAll($sql);
	    $party_name = $result[0]['name'];
	    $smarty->assign('party_name',$party_name);
	    
	    $sql1 = "SELECT o.order_id, o.order_sn, o.consignee, o.inv_payee, o.shipping_time,o.order_amount,
        o.shipping_fee, o.shipping_name, o.pay_name, o.distributor_id, o.province,o.zipcode, 
        o.tel, o.mobile, o.country,o.province,o.city,o.district,o.address, o.handle_time,
        s.TRACKING_NUMBER,s.created_stamp,osi.shipping_invoice
        from ecshop.ecs_order_info o force index(order_info_multi_index,order_sn)
        left join ecshop.ecs_invoice_addr as i on i.order_id = CONVERT(o.order_id USING utf8)
        left join romeo.shipment as s on s.shipment_id = i.shipment_id
        left join romeo.order_shipping_invoice osi on o.order_id = osi.order_id
        WHERE
        o.order_type_id = 'SALE' AND
        ". db_create_in($distributor_list, 'o.distributor_id') ." AND -- 直销的订单
        ". party_sql('o.party_id') ."{$condition} 
	     order by o.order_id ASC
	    ".' -- invoice_add '.__LINE__.PHP_EOL;
	    $invoice_cvs = $GLOBALS['db']->getAll($sql1);
	    $smarty->assign('invoice_cvs',$invoice_cvs);
	    header("Content-type:application/vnd.ms-excel");
	    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","补寄发票列表(".$invoiced_name."|".$party_name.")") . ".csv");
	    $out = $smarty->fetch('invoice_manage/invoice_add_csv.htm');
	    echo iconv("UTF-8","GB18030", $out);
	    exit();
}


if ($_REQUEST ['act'] == 'searchEdit') {
	$id	= $_POST ['id'];
	$invoiceEdit=getInvoiceEdit($id);
	print_r ( urldecode(json_encode ( $invoiceEdit )) );
	exit();
}
if ($_REQUEST ['act'] == 'delete') {
	$id	= $_POST ['id'];
	$invoiceInfo = getInvoiceEdit($id);
	$handle = soap_get_client('ShipmentService');
	$shipment_id = $invoiceInfo['shipment_id'];
	$shipment = array('shipmentId'=>$shipment_id);
	$res = deleteInvoice($id);
	$order_id = $invoiceInfo['order_id'];
	$sql = "delete from ecshop.ecs_order_action where order_id = '{$order_id}' and action_note = '添加补寄发票'";
	$GLOBALS['db']->query($sql);
	if($res==1){
	    $response = $handle->deleteShipmentByShipmentId($shipment);
	   // $result = $response->return;
	}
	print_r ( urldecode(json_encode ( $res )) );
	//print_r ( urldecode(json_encode ( $result )) );
	exit();
}

$smarty->display('invoice_manage/invoice_add.htm');

if ($_REQUEST ['act'] == 'edit') {
	
	$order_Sn   = trim($_POST ['orderSn2']);
	$invoiceId  = trim($_POST ['invoiceId']);
	$consignee  = trim($_POST ['consigneesEdit']);
	$city 	    = $_POST ['invoiceEdit'];
	$addr       = trim($_POST ['addrEdit']);
	$tel        = trim($_POST ['telEdit']);
	$zipcode    = $_POST ['zipcodeEdit'];
	$party_id         = $_SESSION ['party_id'];
	$admin_name       = $_SESSION['admin_name'];
	$shippingNoteEdit = trim($_POST ['shippingNoteEdit']);//修改备注
	$shipment_idEdit  = trim($_POST ['shipment_idEdit']);
	$shippingId       = $_POST ['shippingEdit'];//调用接口使用,在romeo数据库中更新记录
	$shippingInfo     = getShippingInfo($shippingId);
	$inv_payee = trim($_POST['inv_payee']);
    //修改发票抬头
	$sql = "update ecshop.ecs_order_info 
	        set inv_payee = '{$inv_payee}'
            where order_sn = '{$order_Sn}' and party_id = '{$party_id}'";
	$GLOBALS['db']->query($sql);
	$shipment=array('shipmentId'=>$shipment_idEdit,
					   'partyId'=>$party_id,
				'shipmentTypeId'=>$shippingId,
					 'carrierId'=>$shippingInfo['default_carrier_id'],
				'trackingNumber'=>null,
					    'status'=>null,
	   'lastModifiedByUserLogin'=>$admin_name,
	);
	
	$handle=soap_get_client('ShipmentService');
	$response=$handle->updateShipment($shipment);
	
	
	$invoiceInfo=getInvoiceEdit($invoiceId);
	
	$invoice['id']=$invoiceId;
	$invoice['consignee']=$consignee;
	$invoice['carrier']=$carrier;
	$invoice['zipcode']=$zipcode;
	$invoice['tel']=$tel;
	$invoice['address']=$addr;
	
	if($city['province']==0)
	{
		$invoice['province']=$invoiceInfo['province'];
		$invoice['city']=$invoiceInfo['city'];
		$invoice['district']=$invoiceInfo['district'];
	}else 
	{
		$invoice['province']=$city['province'];
		$invoice['city']=$city['city'];
		$invoice['district']=$city['district'];
	}

	$res = editInvoice($invoice);
	
}
if ($_REQUEST ['act'] == 'add') {
	$admin_name= $_SESSION['admin_name'];
	$party_id  = $_SESSION ['party_id'];
	$order_sn  = trim($_POST ['orderSn']);
	$consignee = trim($_POST ['consignee']);
	$city 	   = $_POST ['order'];
	$addr 	   = trim($_POST ['addr']);
	$tel 	   = trim($_POST ['tel']);
	$zipcode   = trim($_POST ['zipcode']);
	$shippingNote= trim($_POST ['shippingNote']);
	
	$shippingId= $_POST ['shippingAdd'];//调用接口使用，在romeo数据库中添加记录
	$shippingInfo=getShippingInfo($shippingId);
	
	$order_info=getReceivingOrder($order_sn);
	$invoice['consignee']=$consignee;
	$invoice['zipcode']=$zipcode;
	$invoice['country']=$order_info['country'];
	$invoice['order_id']=$order_info['order_id'];
	$invoice['party_id']=$party_id;
	
	if($city['province']==0)
	{
		$invoice['province']=$order_info['province'];
		$invoice['city']=$order_info['city'];
		$invoice['district']=$order_info['district'];
	}else 
	{
		$invoice['province']=$city['province'];
		$invoice['city']=$city['city'];
		$invoice['district']=$city['district'];
	}
	if($addr=='')
	{
		$invoice['address']=$order_info['address'];
	}else 
	{
		$invoice['address']=$addr;
	}
	$invoice['tel']=$tel;
	$shipment=array('orderId'=>$invoice['order_id'],
					'partyId'=>$party_id,
			 'shipmentTypeId'=>$shippingId,
				  'carrierId'=>$shippingInfo['default_carrier_id'],
			 'trackingNumber'=>null,
		   'shippingCategory'=>'SHIPPING_INVOICE',
			   'shippingCost'=>'0',
			   'shippingNote'=>$shippingNote,
		 'createdByUserLogin'=>$admin_name,
	);
	$handle=soap_get_client('ShipmentService');
	$response1=$handle->createShipment_v2($shipment);
	$invoice['shipment_id']=$response1->return;
	
	$sql_c = "
	    select * from ecshop.ecs_invoice_addr where order_id ='{$invoice['order_id']}'
	    ";
    $confirm = $GLOBALS['db']->getAll($sql_c);
	if($confirm != null){
	    die("已添加补寄发票，请不要重复添加！<a href=\"javascript:history.go(-1)\">返回</a>");
	}
    
	$res=addInvoiceInfo($invoice);
	
	//往ecs_order_action表里插入一条"添加补寄发票"的记录
	$sql = "select  order_id,order_status,shipping_status,pay_status,invoice_status,shortage_status
            from ecshop.ecs_order_info 
            where  order_sn = '{$order_sn}'".' -- invoice_add '.__LINE__.PHP_EOL;
	$result = $GLOBALS['db']->getAll($sql);
    $info['order_id'] = $result[0]['order_id'];
    $info['order_status'] = $result[0]['order_status'];
    $info['shipping_status'] = $result[0]['shipping_status'];
    $info['pay_status'] = $result[0]['pay_status'];
    $info['invoice_status'] = $result[0]['invoice_status'];
    $info['shortage_status'] = $result[0]['shortage_status'];
    $info['action_note'] = "添加补寄发票";
    $action_id = orderActionLog($info);
} 
?>