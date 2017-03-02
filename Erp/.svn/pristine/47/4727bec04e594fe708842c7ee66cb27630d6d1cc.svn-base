<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('distribution.inc.php');

if($_REQUEST['act']=='export')
{
	
	$defalut=str_replace("\n", ",",trim($_POST['bill_num']));
	$arr=explode(",",$defalut);
	
	$bill_number="'".str_replace("\n", "','",trim($_POST['bill_num']))."'";
    $bill      = preg_replace("/\s/","",$bill_number);
    
	$res= getAllBill($bill);
	$res_length=count($res)+1;
	
	
	foreach ($arr as $key=>$num)
	{
		$bool=false;
		foreach ($res as $list)
		{
			if(trim($num)==trim($list['tracking_number']))
			{
				$bool=true;
				break;
			}
		}
		$keys=$key+$res_length;
		if($bool==false){
			$res[$keys]=array('tracking_number'=>$num,'address'=>'此运单号无相关信息');
		}
	}
	$smarty->assign('res',$res);
	
	header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "快递面单" ) . ".csv" );
	$out = $smarty->fetch ( 'bill_list.dwt' );
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
}
$smarty->display ( 'bill_list.htm' );

function getAllBill($bill_num)
{
	$Bill_list = getBillInfo($bill_num);
	foreach ($Bill_list as $key=>$list)
	{
		$add='';
		if($list['city']!=0)
		{
			$aa = array_reverse(getCity($list['city']));
			foreach ($aa as $b)
			{
				$add.=$b['region_name'];
			}
			$Bill_list[$key]['add2']=$add;
		}
	}
	return $Bill_list;
}
function getBillInfo($tracking_number)
{	
	$sql= "select sp.tracking_number,o.tel,o.zipcode,o.mobile,o.consignee,o.city,o.address,o.order_amount from" .
	 $GLOBALS ['ecs']->table ( 'order_info' ) . " AS o " . 
	 " LEFT JOIN " ."`romeo`.`order_shipment` ". " AS s on CAST( s.ORDER_ID AS UNSIGNED ) = o.ORDER_ID" . 
	 " LEFT JOIN " ."`romeo`.`shipment` "." AS sp on sp.SHIPMENT_ID=s.SHIPMENT_ID" . 
	 " where sp.tracking_number in ($tracking_number) group by sp.tracking_number";

	$res = $GLOBALS ['db']->getAll($sql);
	return $res;
}
function getCity($city)
{
	$parent_id ="select parent_id,region_name from" . $GLOBALS ['ecs']->table ( 'region' ) . "where region_id='$city'";
	$r_id = mysql_fetch_array ( $GLOBALS ['db']->query ( $parent_id ) );
	$num=0;
	$res[3]['region_name']=$r_id['region_name'];
	while (true)
	{

		$sql = "select region_name,parent_id from " . $GLOBALS ['ecs']->table ( 'region' ) . " where region_id= ".$r_id['parent_id']; 
		$r_id = mysql_fetch_array ( $GLOBALS ['db']->query ( $sql ) );
		$res[$num]['region_name']=$r_id['region_name'];
		$num++;
		if($r_id['parent_id']==1)
		{
			return $res;
		}
	}
}


























?>
