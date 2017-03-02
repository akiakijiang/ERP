<?php
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
function getCodOrder($parameter)
{
    $condition = '';
    if (isset($parameter['start_time']) && $parameter['start_time'] != null) {
        $condition .= " AND o.order_time >= '{$parameter['start_time']}'";
    }
    if (isset($parameter['end_time']) && $parameter['end_time'] != null) {
        $condition .= " AND o.order_time <= '{$parameter['end_time']}'";
    }
    if (isset($parameter['shipping_status']) && $parameter['shipping_status'] != -1) {
        $condition .= " AND o.shipping_status = '{$parameter['shipping_status']}'";
    }
    if (isset($parameter['order_sn']) && $parameter['order_sn'] != null) {
        $condition .= " AND o.order_sn = '{$parameter['order_sn']}'";
    }
    
    $sql = "
        select distinct o.inv_payee,o.order_id,o.order_sn,o.order_time,o.postscript,
        pr.region_name as province_name,cr.region_name as city_name,dr.region_name as district_name,
        o.consignee,o.province,o.city,o.district,o.address,o.order_amount  
        from `ecshop`.`ecs_order_info` as o 
        left join `ecshop`.`ecs_region` as pr on pr.region_id = o.province   
        left join `ecshop`.`ecs_region` as cr on cr.region_id = o.city 
        left join `ecshop`.`ecs_region` as dr on dr.region_id = o.district 
        where o.postscript like '%发票%' and o.postscript not like '%不%发票%' 
        and o.party_id = 65540 and o.pay_id = 1 {$condition} and 
        not exists (select osi.order_id from `romeo`.`order_shipping_invoice` as osi
        where osi.shipping_invoice <> '' and osi.order_id = o.order_id)
        and o.order_id not in (select order_id from `ecshop`.`ecs_invoice_addr`) and ". party_sql ('o.PARTY_ID')." 
        order by o.order_id desc ";
    
    $invoice_list = $GLOBALS ['db']->getAll($sql);
    return $invoice_list;
}
function deleteInvoice($id)
{
    $checkSql = "select count(*) as num from `romeo`.`shipment` where SHIPMENT_ID = (select shipment_id from `ecshop`.`ecs_invoice_addr` where id = $id) and TRACKING_NUMBER is null ";
    $result = mysql_fetch_array($GLOBALS ['db']->query ( $checkSql ));
    //return $result['num'];
    if($result['num'] > 0){
        $sql = "DELETE FROM `ecshop`.`ecs_invoice_addr` WHERE `ecs_invoice_addr`.`id` =  $id ";
        $res = $GLOBALS ['db']->query ( $sql );
        if($res){
            return 1;
        }
        else{
            return -2;
        }
    }
    else {
        return -1;
    }
}
function editInvoice($invoice)
{
	$id 		= $invoice['id'];
	$consignee 	= $invoice['consignee'];
	$province	= $invoice['province'];
	$city		= $invoice['city'];
	$district	= $invoice['district'];
	$addr		= $invoice['address'];
	$tel 		= $invoice['tel'];
	$zipcode	= $invoice['zipcode'];
	
	$sql = "update `ecshop`.`ecs_invoice_addr` set consignee ='$consignee',
		  	   	 province ='$province',
		  	   	 	 city ='$city',
				 district ='$district',
			      address ='$addr',
		  	          tel ='$tel',
			      zipcode ='$zipcode',
			      last_update_stamp = NOW() " . 
		 	   " where id ='$id'";
	
	$result = $GLOBALS ['db']->query ( $sql );
	return $result;
}
function getShipmentInfo($shipment_id)
{
	$sql="select SHIPMENT_TYPE_ID from `romeo`.`shipment` where SHIPMENT_ID = '$shipment_id'";
	$shipmentInfo=mysql_fetch_array($GLOBALS ['db']->query($sql));
	return $shipmentInfo;
}
function getShippingInfo($shippingId)
{
	$sql="select shipping_name,shipping_code,default_carrier_id from `ecshop`.`ecs_shipping` where shipping_id = '$shippingId'";
	$shippingInfo=mysql_fetch_array($GLOBALS ['db']->query($sql));
	return $shippingInfo;
}
function getInvoicePrint($itemId)
{
	$id = $itemId['id'];
	$sql = "SELECT 
		s.shipment_id,
		s.tracking_number
	from 
		ecshop.ecs_invoice_addr ia 
		left join romeo.shipment s on s.shipment_id = ia.shipment_id
	where ia.id ".db_create_in($id);
	$shipment_print = $GLOBALS ['db']->getAll($sql);
	$create_time = date("Y-m-d H:i:s");
	foreach ($shipment_print as $key => $item) {
		$shipmentSQL = "insert into romeo.shipment_print (shipment_id, tracking_number, create_time, print_user) values( '{$item['shipment_id']}','{$item['tracking_number']}','{$create_time}','{$itemId['admin_name']}')";
		$GLOBALS ['db']->query($shipmentSQL);
	}

	$sql="SELECT 
			a.consignee,a.shipment_id,a.province,a.city,
			a.district,a.address,a.tel,a.zipcode,o.party_id,
			s.tracking_number,o.order_id,o.order_sn
		from `ecshop`.`ecs_invoice_addr` a
		left join ecshop.ecs_order_info o on a.order_id = o.order_id
		left join romeo.shipment s on a.shipment_id=s.shipment_id
		where a.id ".db_create_in($id);
	$invoicePrint=$GLOBALS ['db']->getAll($sql);
	foreach ($invoicePrint as $key=>$item)
	{
		$shippingSQL = "select default_carrier_id from `ecshop`.`ecs_shipping` where shipping_id = (select SHIPMENT_TYPE_ID from `romeo`.`shipment` where SHIPMENT_ID = '{$item['shipment_id']}')";
		$shipping = mysql_fetch_array($GLOBALS ['db']->query($shippingSQL));
		$invoicePrint[$key]['carrier_id'] = $shipping['default_carrier_id'];
		
		$provinceSQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['province']}'";
	    $province = mysql_fetch_array($GLOBALS ['db']->query($provinceSQL));
		$invoicePrint[$key]['province_name'] = $province['region_name'];
		
		$citySQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['city']}'";
	    $city = mysql_fetch_array($GLOBALS ['db']->query($citySQL));
		$invoicePrint[$key]['city_name'] = $city['region_name'];
		
		$districtSQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['district']}'";
	    $district = mysql_fetch_array($GLOBALS ['db']->query($districtSQL));
		$invoicePrint[$key]['district_name'] = $district['region_name'];
	}
	return $invoicePrint;
}
function getInvoiceOrderIist($time)
{
    $sql="
    	select p.NAME,o.order_sn,s.TRACKING_NUMBER,s.LAST_UPDATE_STAMP,i.country,
		i.province,
		i.city,
		i.district,
		i.address,
		i.tel, 
		i.consignee,		
		sp.shipping_name,
		o.postscript
    	from `ecshop`.`ecs_invoice_addr` as i
    	left join `romeo`.`shipment` as s on s.SHIPMENT_ID = i.shipment_id
    	left join `ecshop`.`ecs_order_info` as o on o.order_id = i.order_id
    	left join `romeo`.`party` as p on o.PARTY_ID = p.PARTY_ID
    	left join `ecshop`.`ecs_shipping` as sp on sp.shipping_id=s.SHIPMENT_TYPE_ID
    	where s.TRACKING_NUMBER is not null and s.LAST_UPDATE_STAMP > '{$time['start']}' and s.LAST_UPDATE_STAMP < '{$time['end']}' and ". party_sql ( 'o.PARTY_ID' );
    
    $InvoiceOrderList=$GLOBALS ['db']->getAll($sql);
    foreach ($InvoiceOrderList as $key => $item) {

    	$countrySQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['country']}'";
	    $country = mysql_fetch_array($GLOBALS ['db']->query($countrySQL));
		$InvoiceOrderList[$key]['country_name'] = $country['region_name'];

		$provinceSQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['province']}'";
		$province = mysql_fetch_array($GLOBALS ['db']->query($provinceSQL));
		$InvoiceOrderList[$key]['province_name'] = $province['region_name'];

		$citySQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['city']}'";
	    $city = mysql_fetch_array($GLOBALS ['db']->query($citySQL));
		$InvoiceOrderList[$key]['city_name'] = $city['region_name'];
		
		$districtSQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['district']}'";
	    $district = mysql_fetch_array($GLOBALS ['db']->query($districtSQL));
		$InvoiceOrderList[$key]['district_name'] = $district['region_name'];

    	$printTimeSQL = "select create_time from romeo.shipment_print where tracking_number = '{$item['TRACKING_NUMBER']}' limit 1";
    	$printTime = mysql_fetch_array($GLOBALS ['db']->query($printTimeSQL));
    	$InvoiceOrderList[$key]['print_time'] = $printTime['create_time'];
    }
    return $InvoiceOrderList;
}
function getInvoiceEdit($id)
{
	//$sql="select * from `ecshop`.`ecs_invoice_addr` where id = '$id'";
	$sql = "select i.id,i.order_id,i.shipment_id,s.shipment_type_id,s.shipping_note,i.zipcode,i.tel,i.consignee,i.country,i.province,i.city,i.district,i.address from `ecshop`.`ecs_invoice_addr` as i ".
	" left join `romeo`.`shipment` as s on s.SHIPMENT_ID = i.shipment_id ".
	" where i.id = '$id'";
	$checkSql = " 
	select count(*) as num from `romeo`.`shipment` as s
	 left join `ecshop`.`ecs_invoice_addr` as i on s.SHIPMENT_ID = i.shipment_id 
	 where i.id = $id and TRACKING_NUMBER is not null
	";
	$res = mysql_fetch_array($GLOBALS ['db']->query($checkSql));
	$invoiceEdit=mysql_fetch_array($GLOBALS ['db']->query($sql));
	$invoiceEdit['num'] = $res['num'];
	return $invoiceEdit;
}
function getReceivingOrder($order_sn)
{	
	$sql="select o.order_id,o.order_sn,o.shipping_status,o.party_id,o.consignee,o.order_amount,o.inv_payee,o.country,o.province,o.city,o.district,o.address,o.tel,o.mobile,o.zipcode,COUNT(o.order_sn) as n 
	    from `ecshop`.`ecs_order_info` as o
	    where order_sn ='".$order_sn."' GROUP BY order_sn";
	$order_info=mysql_fetch_array($GLOBALS ['db']->query($sql));
	
	$checkOrder = "SELECT COUNT(*) as num  FROM `ecshop`.`ecs_order_info` as o where o.order_sn = '$order_sn' and " . party_sql ( 'o.PARTY_ID' );
	$res = mysql_fetch_array ( $GLOBALS ['db']->query ( $checkOrder ) );
	if ($res ['num'] == 0) {
		$order_info['error_party']=0;
	}else 
	{
		$order_info['error_party']=1;
	}
	
	$countrySQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$order_info['country']}'";
    $country = mysql_fetch_array($GLOBALS ['db']->query($countrySQL));
	$order_info['country_name'] = $country['region_name'];
	
	$provinceSQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$order_info['province']}'";
    $province = mysql_fetch_array($GLOBALS ['db']->query($provinceSQL));
	$order_info['province_name'] = $province['region_name'];
	
	$citySQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$order_info['city']}'";
    $city = mysql_fetch_array($GLOBALS ['db']->query($citySQL));
	$order_info['city_name'] = $city['region_name'];
	
	$districtSQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$order_info['district']}'";
    $district = mysql_fetch_array($GLOBALS ['db']->query($districtSQL));
	$order_info['district_name'] = $district['region_name'];
    
	return $order_info;
}
function addInvoiceInfo($invoice)
{
	$order_id    = $invoice['order_id'];
	$consignee   = $invoice['consignee'];
	$country     = $invoice['country'];
	$province    = $invoice['province'];
	$city        = $invoice['city'];
	$district    = $invoice['district'];
	$addr        = $invoice['address'];
	$tel         = $invoice['tel'];
	$carrier     = $invoice['carrier'];
	$zipcode     = $invoice['zipcode'];
	$shipment_id = $invoice['shipment_id'];
	$party_id    = $invoice['party_id'];
	
	$sql = "insert into `ecshop`.`ecs_invoice_addr` (order_id,consignee,shipment_id,country,province,city,district,address,zipcode,tel,party_id,created_stamp,last_update_stamp)" . 
	" values('$order_id','$consignee','$shipment_id','$country','$province','$city','$district','$addr','$zipcode','$tel','$party_id',NOW(),NOW())";
	$result = $GLOBALS ['db']->query ( $sql );
	
	return $result;
}
function getInvoiceCount($parameter)
{
	
	$where=" where s.TRACKING_NUMBER is null and " ;
	$where2 = "";
	$where_time = " and i.created_stamp >= '{$parameter['startTime']}' and i.created_stamp <='{$parameter['endTime']}' ";

/*	if($parameter['facility_id']) {
		$where2 .=" f.facility_id = " .$parameter['facility_id'] .' and ';
	}
*/
	if($parameter['shipping_id']) {
		$where2 .=" sp.shipping_id = " .$parameter['shipping_id'] .' and ';
	}
	
	$where = $where .$where2 .party_sql ('i.party_id') .$where_time;
	
	
	if($parameter['is_null']==0){
		$where='where s.TRACKING_NUMBER is null and ' .$where2.party_sql ( 'i.party_id' ) .$where_time;
	}
	if($parameter['not_null']==1){
		$where="where s.TRACKING_NUMBER is not null and " .$where2 .party_sql ( 'i.party_id' ) .$where_time;
	}
	if($parameter['is_all']==2){
		$where =" where " .$where2 .party_sql ( 'i.party_id' ) .$where_time;
	}

	$sql= "select count(i.id) as num  from `ecshop`.`ecs_invoice_addr` as i 
	left join `romeo`.`shipment` as s on s.SHIPMENT_ID=i.shipment_id
	$where";
	//QLog::log($sql);
	$num= mysql_fetch_array( $GLOBALS ['db']->query ( $sql ));
	return $num['num'];
}


//获取补寄发票的列表数据
function getInvoiceList($parameter,$detail_info=true)
{
	$where=" where s.TRACKING_NUMBER is null and " ;
	$where2 = "";
	$where_time = " and i.created_stamp >= '{$parameter['startTime']}' and i.created_stamp <='{$parameter['endTime']}' ";
	
/*
	if($parameter['facility_id']) {
		$where2 .=" f.facility_id = " .$parameter['facility_id'] .' and ';
	}
*/
	if($parameter['shipping_id']) {
		$where2 .=" sp.shipping_id = " .$parameter['shipping_id'] .' and ';
	}
	
	$where = $where .$where2 .party_sql ('i.party_id') .$where_time;
	
	if($parameter['is_null']==0){
		$where=" where s.TRACKING_NUMBER is null and " .$where2 .party_sql ('i.party_id') .$where_time ."ORDER BY i.id";
		if($parameter['orderSn']) {
			$where=" where s.TRACKING_NUMBER is null and o.order_sn like '".$parameter['orderSn']."%' and " .$where2 .party_sql ('i.party_id') .$where_time ."ORDER BY i.id";;
		}
	}
	if($parameter['not_null']==1){
		$where=" where s.TRACKING_NUMBER is not null and " .$where2 . party_sql ('i.party_id') .$where_time ."ORDER BY s.last_update_stamp DESC";
		if($parameter['orderSn']) {
			$where=" where s.TRACKING_NUMBER is not null and o.order_sn like '" .$parameter['orderSn']."%' and "  .$where2 . party_sql ('i.party_id') .$where_time ."ORDER BY s.last_update_stamp DESC ";
		}
	}
	if($parameter['is_all']==2){
		$where = ' where ' .$where2 .party_sql ('i.party_id') .$where_time ."ORDER BY i.id DESC";
		if($parameter['orderSn']) {
			$where=" where o.order_sn like '" .$parameter['orderSn']."%' and " .$where2 . party_sql ('i.party_id') .$where_time ."ORDER BY i.id DESC";
		}
	}

	$sql= "select i.id,i.order_id,s.LAST_UPDATE_STAMP,sp.shipping_id,sp.default_carrier_id,sp.shipping_name,i.shipment_id,s.shipping_note,i.consignee,i.country,
	    i.province,i.city,i.district,i.address,o.order_amount,o.order_sn,o.inv_payee,FROM_UNIXTIME(o.shipping_time) as shipping_time,s.TRACKING_NUMBER, p.pay_name, f.facility_name, f.facility_id
	from `ecshop`.`ecs_invoice_addr` as i 
	inner join `romeo`.`shipment` as s on s.SHIPMENT_ID=i.shipment_id
	inner join `ecshop`.`ecs_order_info` as o on o.order_id=i.order_id
	left join `ecshop`.`ecs_payment` as p on o.pay_id = p.pay_id
	left join `romeo`.`facility` as f on f.facility_id = o.facility_id
	left join `ecshop`.`ecs_shipping` as sp on sp.shipping_id=s.SHIPMENT_TYPE_ID
	$where LIMIT ".$parameter['offset'].",".$parameter['limit']."";
	QLog::log($sql);
	$invoiceList=$GLOBALS ['db']->getAll($sql);
	
	// 如果需要查询更多内容
	if($detail_info)  {
		foreach ($invoiceList as $key=>$item)
		{	
			$countrySQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['country']}'";
		    $country = mysql_fetch_array($GLOBALS ['db']->query($countrySQL));
			$invoiceList[$key]['country_name'] = $country['region_name'];
			
			$provinceSQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['province']}'";
		    $province = mysql_fetch_array($GLOBALS ['db']->query($provinceSQL));
			$invoiceList[$key]['province_name'] = $province['region_name'];
			
			$citySQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['city']}'";
		    $city = mysql_fetch_array($GLOBALS ['db']->query($citySQL));
			$invoiceList[$key]['city_name'] = $city['region_name'];
			
			$districtSQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['district']}'";
		    $district = mysql_fetch_array($GLOBALS ['db']->query($districtSQL));
			$invoiceList[$key]['district_name'] = $district['region_name'];
	
			$printTimeSQL = "select create_time from `romeo`.`shipment_print` where shipment_id = '{$item['shipment_id']}' limit 1";
			$printTime = mysql_fetch_array($GLOBALS ['db']->query($printTimeSQL));
			$invoiceList[$key]['print_time'] = $printTime['create_time'];
		}
			
	}

	return $invoiceList;
}

function getInvoiceInfo($order_id)
{
	$sql="select i.id,i.order_id,s.TRACKING_NUMBER,i.shipment_id,s.shipping_note,s.last_modified_by_user_login as action_user,s.created_stamp,i.zipcode,i.tel,i.consignee,i.country,i.province,i.city,i.district,i.address,o.order_amount,o.order_sn,o.shipping_time  from `ecshop`.`ecs_invoice_addr` as i ".
	"left join `ecshop`.`ecs_order_info` as o on o.order_id=i.order_id ".
	"left join `romeo`.`shipment` as s on s.SHIPMENT_ID=i.shipment_id".
	" where i.order_id=$order_id and " . party_sql ('o.PARTY_ID');
	$invoice=$GLOBALS ['db']->getAll($sql);
	foreach ($invoice as $key=>$item)
	{
		$shippingSQL = "select shipping_name from `ecshop`.`ecs_shipping` where shipping_id = (select SHIPMENT_TYPE_ID from `romeo`.`shipment` where SHIPMENT_ID = '{$item['shipment_id']}')";
		$shipping = mysql_fetch_array($GLOBALS ['db']->query($shippingSQL));
		$invoice[$key]['shipping_name'] = $shipping['shipping_name'];
		
		$orderSQL = "select order_amount from `ecshop`.`ecs_order_info` where order_id = '{$item['order_id']}'";
		$order = mysql_fetch_array($GLOBALS ['db']->query($orderSQL));
		$invoice[$key]['order_amount'] = $order['order_amount'];
		
		$countrySQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['country']}'";
	    $country = mysql_fetch_array($GLOBALS ['db']->query($countrySQL));
		$invoice[$key]['country_name'] = $country['region_name'];
		
		$provinceSQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['province']}'";
	    $province = mysql_fetch_array($GLOBALS ['db']->query($provinceSQL));
		$invoice[$key]['province_name'] = $province['region_name'];
		
		$citySQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['city']}'";
	    $city = mysql_fetch_array($GLOBALS ['db']->query($citySQL));
		$invoice[$key]['city_name'] = $city['region_name'];
		
		$districtSQL = "select region_name from `ecshop`.`ecs_region` where region_id = '{$item['district']}'";
	    $district = mysql_fetch_array($GLOBALS ['db']->query($districtSQL));
		$invoice[$key]['district_name'] = $district['region_name'];
	}
	return $invoice;
}