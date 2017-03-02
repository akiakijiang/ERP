<?php
/**
 * 申通接口对接
 */
//define('IN_ECS', true);
require_once('init.php');
require_once(ROOT_PATH . 'includes/HttpClient.php');
require_once (ROOT_PATH . "admin/PartyXTelephone.php");

define(STO_EXPRESS_BASE_PATH, 'http://vip.sto.cn');
define(STO_EXPRESS_DATA_DIGEST, '721ed5587c278029099a33ce60e64f85');

define(STO_EXPRESS_CUSTOMER_NAME_BJ, '金百合在线');//10005510029
define(STO_EXPRESS_CUSTOMER_PASSWORD_BJ, '1029');

define(STO_EXPRESS_FACILITY_BEIJING_TEL, '010-57739408');
define(STO_EXPRESS_CUSTOMER_SITE_BJ, '北京南部公司');

define(STO_EXPRESS_CUSTOMER_NAME_DG, '乐其');//'51170000023'
define(STO_EXPRESS_CUSTOMER_PASSWORD_DG, 'leqee');

define(STO_EXPRESS_FACILITY_DONGGUAN_TEL, '0769-85305856');
define(STO_EXPRESS_CUSTOMER_SITE_DG, '广东东莞公司');

function sto_debug_info($msg,$show=false){
	if($show){
		print_r($msg);
		echo " <br> \n ";
	}
}

//Apply 500 tracking number
function apply_sto_arata_tracking_number($branch,$once=500){
	global $db;
	if($branch=='BJ'){
		$cusite=STO_EXPRESS_CUSTOMER_SITE_BJ;
	}elseif($branch=='DG'){
		$cusite=STO_EXPRESS_CUSTOMER_SITE_DG;
	}else{
		//log error branch
		sto_debug_info('error branch',true);
		return false;
	}
	$res=_sto_book_tracking_number($branch,$once);
	if($res){
		sto_debug_info($res);
		if($res['error'] && $res['error']!=''){
			//log error
			sto_debug_info($res['error'],true);
			return false;
		}else{
			sto_debug_info($res['data']);
			$tn_array=explode(',',$res['data']);
			sto_debug_info($tn_array);
			foreach($tn_array as $key=>$tracking_number){
				sto_debug_info($tracking_number);
				$sql = "INSERT into ecshop.thermal_express_mailnos  (tracking_number,shipping_id,branch,create_time,update_time) values ('{$tracking_number}',89,'{$branch}',now(),now()) ";
				$db->query($sql);
			}
			return true;
		}
	}else{
		//log get null
		sto_debug_info('申通请求面单没有获取回应',true);
		return false;
	}
}
function sto_mail_applys($branch,$p_dates='2014-10-01',$p_datee=''){
	$count=0;
	$added=false;
	while (true) {
		if($count>20)break;
		$r=check_sto_arata_using_fact($branch,$p_dates,$p_datee);
		if($r['error'] && $r['error']!=''){
			break;
		}else{
			if($r['surplusnum']>0){
				$addddd=apply_sto_arata_tracking_number($branch);
				$added = $added || $addddd;
				$count+=1;
			}else{
				break;
			}
		}
	}
	return $added;
}

function check_sto_arata_using_fact($branch,$p_dates='2014-10-01',$p_datee=''){
	if($branch=='BJ'){
		$cusite=STO_EXPRESS_CUSTOMER_SITE_BJ;
	}elseif ($branch == 'DG') {
		$cusite=STO_EXPRESS_CUSTOMER_SITE_DG;
	}
	else{
		//log error branch
		return array('error'=>'not a right branch');
	}
	if(!$p_dates || $p_dates==''){
		$p_dates='2014-10-01';
	}
	if(!$p_datee || $p_datee==''){
		$p_datee=date("Y-m-d",time()+3600*24);
				//	date("Y-m-d");
	}
	$data=_sto_get_cusumable_status(								
								$p_dates,
								$p_datee,
								$branch,
								''								
	);
	if($data){
		sto_debug_info($data,true);
		if(is_array($data) && is_object($data[count($data)-1])){
			$data=(array)($data[count($data)-1]);
		}
		sto_debug_info($data,true);
		//$data=json_decode($data);
		/*
		startno
		endno
		grantnum
		printnum
		alreadynum
		surplusnum
		retrievenum
		grantsite
		grantdata
		cusname
		*/
		return $data;
	}else{
		//log no data received
		return array('error'=>'no data received');
	}
}

function report_sto_arata_used_shipment($shipment_id){
	return _sto_upload_tracking_information($shipment_id);
}

/*
VIP0009 热敏面单号获取
# POST #
## common ##
data_digest
cuspwd
## special ##
code
cusname
cusite
len
# response #
success:'true'?t:f
message
data
**/
function _sto_book_tracking_number($site,$len){
	if($site=='BJ'){
		$cusite=STO_EXPRESS_CUSTOMER_SITE_BJ;
		$cuname=STO_EXPRESS_CUSTOMER_NAME_BJ;
		$pw=STO_EXPRESS_CUSTOMER_PASSWORD_BJ;
	}elseif($site=='DG'){
		$cusite=STO_EXPRESS_CUSTOMER_SITE_DG;
		$cuname=STO_EXPRESS_CUSTOMER_NAME_DG;
		$pw=STO_EXPRESS_CUSTOMER_PASSWORD_DG;
	}
	$special=array(
		"code"=>'vip0009',
		'data_digest'=>STO_EXPRESS_DATA_DIGEST,
	    'cuspwd'=>$pw,
		"cusname"=>$cuname,
		"cusite"=>$cusite,
		"len"=>$len,
	);
	$action='PreviewInterfaceAction.action';
	$method='POST';
	$url=STO_EXPRESS_BASE_PATH.'/'.$action;

	$data=$special;
		sto_debug_info($url);
		sto_debug_info($data);
	$response=HttpClient::quickPost($url,$data);
	if($response){
		sto_debug_info($response);
		$res_obj=json_decode($response);
		if($res_obj->success===true || $res_obj->success=='true'){
			if($res_obj->message == '103'){
				//log ok
				return array('data'=>$res_obj->data);
			}else{
				//log special error no
				sto_debug_info($res_obj);
				return array('error'=>'Responsed Code '.$res_obj->message. ' and its meaning.');
			}
		}else{
			//log failed querying
			sto_debug_info($res_obj);
			return array('error'=>'sto querying error.'.$res_obj->message." data=".$res_obj->data); 
		}
	}else{
		//log not reveive response
		sto_debug_info($res_obj);
		sto_debug_info('no response');
		return array('error'=>'sto request failed');
	}
}
/*
VIP0007 信息上传
# POST #
## common ##
data_digest
cuspwd
## special ##
code
data
**/
function _sto_upload_tracking_information($shipment_id){
	global $db;
	$sql="SELECT
			s.SHIPMENT_ID,
			s.TRACKING_NUMBER billno,
			o.shipping_time senddate,
			o.facility_id,
			o.party_id,
			o.order_sn orderno,
			o.consignee receiveperson,
			o.mobile receivetel,
			o.tel receivetel2,
			er_p.region_name receiveprovince,
			er_c.region_name receivecity,
			er_d.region_name receivearea,
			o.address receiveaddress
		FROM
			romeo.shipment s
		LEFT JOIN ecshop.ecs_order_info o ON s.primary_order_id = o.order_id
		LEFT JOIN ecshop.ecs_region er_p ON o.province = er_p.region_id
		LEFT JOIN ecshop.ecs_region er_c ON o.city = er_c.region_id
		LEFT JOIN ecshop.ecs_region er_d ON o.city = er_d.region_id
		WHERE
			s.SHIPMENT_ID = '{$shipment_id}'
		LIMIT 1";
	$bill_info=$db->getRow($sql);
	$bill_info['senddate'] = date("Y-m-d",$bill_info['senddate']);
	if(getLocalBranchWithFacilityId($bill_info['facility_id'])=='BJ'){
		$bill_info['sendsite']=STO_EXPRESS_CUSTOMER_SITE_BJ;
		$bill_info['sendcus']=STO_EXPRESS_CUSTOMER_NAME_BJ;
		$bill_info['sendcuspw']=STO_EXPRESS_CUSTOMER_PASSWORD_BJ;
		$bill_info['sendperson']='金百合在线';
		$bill_info['sendtel']=STO_EXPRESS_FACILITY_BEIJING_TEL;
		$bill_info['sendprovince']='北京市';
		$bill_info['sendcity']='北京市';
		$bill_info['sendarea']='通州';
		$bill_info['sendaddress']='马驹桥镇杨秀店168号';
	}elseif(getLocalBranchWithFacilityId($bill_info['facility_id'])=='DG'){
		$bill_info['sendsite']=STO_EXPRESS_CUSTOMER_SITE_DG;
		$bill_info['sendcus']=STO_EXPRESS_CUSTOMER_NAME_DG;
		$bill_info['sendcuspw']=STO_EXPRESS_CUSTOMER_PASSWORD_DG;
		$bill_info['sendperson']='乐其';
		$bill_info['sendtel']=STO_EXPRESS_FACILITY_DONGGUAN_TEL;
		$bill_info['sendprovince']='北京市';
		$bill_info['sendcity']='北京市';
		$bill_info['sendarea']='通州';
		$bill_info['sendaddress']='马驹桥镇杨秀店168号';
	}
	$bill_info['goodsname']=sinri_get_goods_type_for_party_to_print($bill_info['party_id']);//getTypeByPartyId($bill_info['party_id']);
	$bill_info['inputdate']=date("Y-m-d");
	$bill_info['inputperson']='';
	$bill_info['inputsite']='';

	if(empty($bill_info['receivetel'])){
		$bill_info['receivetel']=$bill_info['receivetel2'];
	}

	$special=array(
		"code"=>'vip0007',
		'data_digest'=>STO_EXPRESS_DATA_DIGEST,
	    'cuspwd'=>$bill_info['sendcuspw'],
		'data'=>"[".json_encode(
			array(
				'billno'=>$bill_info['billno'],//20
				'senddate'=>$bill_info['senddate'],//yyyy-mm-dd
				'sendsite'=>$bill_info['sendsite'],//20
				'sendcus'=>$bill_info['sendcus'],//60
				'sendperson'=>$bill_info['sendperson'],//60
				'sendtel'=>$bill_info['sendtel'],//60
				'receivecus'=>'',//60
				'receiveperson'=>$bill_info['receiveperson'],//60
				'receivetel'=>$bill_info['receivetel'],//60
				'goodsname'=>$bill_info['goodsname'],//500
				'inputdate'=>$bill_info['inputdate'],//yyyy-mm-dd
				'inputperson'=>$bill_info['inputperson'],//20,
				'inputsite'=>$bill_info['inputsite'],//20
				'lasteditdate'=>'',
				'lasteditperson'=>'',
				'lasteditsite'=>'',
				'remark'=>'',
				'receiveprovince'=>$bill_info['receiveprovince'],
				'receivecity'=>$bill_info['receivecity'],
				'receivearea'=>$bill_info['receivearea'],
				'receiveaddress'=>$bill_info['receiveaddress'],
				'sendprovince'=>$bill_info['sendprovince'],
				'sendcity'=>$bill_info['sendcity'],
				'sendarea'=>$bill_info['sendarea'],
				'sendaddress'=>$bill_info['sendaddress'],
				'weight'=>'',
				'productcode'=>'',
				'sendpcode'=>'',
				'sendccode'=>'',
				'sendacode'=>'',
				'receivepcode'=>'',
				'receiveccode'=>'',
				'receiveacode'=>'',
				'bigchar'=>$bill_info['receiveprovince'].$bill_info['receivecity'],
				'orderno'=>$bill_info['orderno'],
			)
		)."]",
	);
	$action='PreviewInterfaceAction.action';
	$method='POST';
	$url=STO_EXPRESS_BASE_PATH.'/'.$action;
	$data=$special;
	// $data='';
	// $count=0;
	// foreach ($special as $key => $value) {
	// 	if($count==0){
	// 		$data.=urlencode($key)."=".urlencode($value);
	// 	}else{
	// 		$data.='&'.urlencode($key)."=".urlencode($value);
	// 	}
	// }
	sto_debug_info($url);
	sto_debug_info($data);
	$response=HttpClient::quickPost($url,$data);
	if($response){
		sto_debug_info($response);
		$res_obj=json_decode($response);
		sto_debug_info($res_obj);
		if($res_obj->success===true || $res_obj->success=='true'){
			if($res_obj->message == '103'){
				//log ok
				//return $res_obj->data;
				return true;
			}else{
				//log special error no
				sto_debug_info($response,true);
				sto_debug_info($res_obj,true);
				//return array('error'=>'Responsed Code '.$res_obj->message. ' and its meaning.');
				return false;
			}
		}else{
			//log failed querying
			sto_debug_info($response,true);
			sto_debug_info($res_obj,true);
			//return array('error'=>'sto querying failed'); 
			return false;
		}
	}else{
		//log not reveive response
		sto_debug_info($response,true);
		//return array('error'=>'sto request failed');
		return false;
	}
}
/*
VIP0001
# GET #
## common ##
data_digest
cuspwd
## special ##
code
params
**/
function _sto_get_cusumable_status(								
								$p_dates,
								$p_datee,
								$site,
								$p_billno=''								
){
	if($site=='BJ'){
		$pw=STO_EXPRESS_CUSTOMER_PASSWORD_BJ;
		$p_cusite=STO_EXPRESS_CUSTOMER_SITE_BJ;
		$p_custname=STO_EXPRESS_CUSTOMER_NAME_BJ;
	}elseif($site=='DG'){
		$pw=STO_EXPRESS_CUSTOMER_PASSWORD_DG;
		$p_cusite=STO_EXPRESS_CUSTOMER_SITE_DG;
		$p_custname=STO_EXPRESS_CUSTOMER_NAME_DG;
	}
	$special=array(
		"code"=>'vip0001',
		'data_digest'=>STO_EXPRESS_DATA_DIGEST,
	    'cuspwd'=>$pw,
	    'params'=>"{".
	    	"'p_billno':'".$p_billno."',".
	    	"'p_dates':'".$p_dates."',".
			"'p_datee':'".$p_datee."',".
			"'p_custname':'".$p_custname."',".
			"'p_cusite':'".$p_cusite."'".
			"}"
	);
	$action='STOinterfaceAction.action';
	$method='GET';
	$data=$special;
	$json=json_encode($data);
	$url=STO_EXPRESS_BASE_PATH.'/'.$action."?data=".$json;
	sto_debug_info($url);
	$response=HttpClient::quickGet($url);
	if($response){
		sto_debug_info($response);
		$res_obj=json_decode($response);
		if($res_obj->success===true || $res_obj->success=='true'){
			if($res_obj->message == '103'){
				//log ok
				return $res_obj->data;
			}else{
				//log special error no
				sto_debug_info($res_obj);
				return array('error'=>'Responsed Code '.$res_obj->message. ' and its meaning.');
			}
		}else{
			//log failed querying
			sto_debug_info($res_obj);
			return array('error'=>'sto querying failed'); 
		}
	}else{
		//log not reveive response
		sto_debug_info($res_obj);
		return array('error'=>'sto request failed');
	}

}

?>