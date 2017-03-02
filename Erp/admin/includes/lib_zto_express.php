<?php
/**
 * 中通接口对接
 * 对接文档: http://partner.zto.cn/partner/doc/
 */
//define('IN_ECS', true);
require_once('init.php');
require_once(ROOT_PATH . 'includes/HttpClient.php');

//上海中通使用新账号 对老账号下的数据进行清理
//define(ZTO_EXPRESS_USER_SH,'1000004013');
//define(ZTO_EXPRESS_PASSWORD_SH,'IQ1U46HKPU');

define(ZTO_EXPRESS_USER_SH,'1000139036');
define(ZTO_EXPRESS_PASSWORD_SH,'6KZBV7GC85');


define(ZTO_EXPRESS_USER_BJ,'1000012557');
define(ZTO_EXPRESS_PASSWORD_BJ,'JUK2B1CSKL');


define(ZTO_EXPRESS_USER_JX,'1000066185');//拼好货使用账户
define(ZTO_EXPRESS_PASSWORD_JX,'QVTP625PZ8');

define(ZTO_EXPRESS_USER_CD,'1000047713');
define(ZTO_EXPRESS_PASSWORD_CD,'PLKNHV6Q6H');


//上海水果
define(ZTO_EXPRESS_USER_SHSG,'1000171360');
define(ZTO_EXPRESS_PASSWORD_SHSG,'3MSXZX4B5U');
//北京水果
define(ZTO_EXPRESS_USER_BJSG,'1000175865');
define(ZTO_EXPRESS_PASSWORD_BJSG,'3KXTB4VLOF');
//成都水果
define(ZTO_EXPRESS_USER_CDSG,'1000174736');
define(ZTO_EXPRESS_PASSWORD_CDSG,'NHKNU4ZUGQ');
//深圳水果
define(ZTO_EXPRESS_USER_SHZSG,'1000007120');
define(ZTO_EXPRESS_PASSWORD_SHZSG,'N2ME5FO136');
//嘉兴水果
define(ZTO_EXPRESS_USER_JXSG,'1000174658');
define(ZTO_EXPRESS_PASSWORD_JXSG,'JZHN34F2QV');
//苏州水果
define(ZTO_EXPRESS_USER_SZSG,'1000176735');
define(ZTO_EXPRESS_PASSWORD_SZSG,'66UANJ975E');
//define(ZTO_EXPRESS_USER_SZSG,'1000499057');
//define(ZTO_EXPRESS_PASSWORD_SZSG,'8RDFBFA9Y4');
//武汉水果
define(ZTO_EXPRESS_USER_WHSG,'1000176764');
define(ZTO_EXPRESS_PASSWORD_WHSG,'CMXXMKG5D9');

define(ZTO_EXPRESS_URL,'http://partner.zto.cn/partner/interface.php');
define(ZTO_EXPRESS_CLIENT_URL,'http://partner.zto.cn/client/interface.php');


/*
 * style: 该参数指明请求和返回的数据格式，目前仅支持json，暂不支持xml或csv等其他格式。
 * func: 该参数指明需要请求的功能，具体请参考各项接口说明。
 * partner: 该参数指明合作方代码，由中通速递提供，一般为合作方官方网站域名，请合作方在申请时提供。
 * datetime: 请求接口时的时间，格式为：“2013-09-01 13:25:33”，中通接口程序会控制在2小时内有效，超过范围会被服务器拒绝。如果错误在可控范围内，将来可能会减少有效时间在半小时内，因此特别提醒合作商需要随时关注服务器时间，最好采取互联网同步时间的方式，减少与中通服务器的时间差异。
 * content: 数据内容，需要进行BASE64编码
 * verify: 数据效验码，用于效验数据是否正确完整，该码生成规则：md5(partner + date + content + pass)，其中pass在测试阶段为123456。开发时应将pass定义为可设置。
 * 
 * */
function get_zto_response($func,$content,$site='SH'){

	$user='';
	$pw='';
	if($func=='order.batch_submit'){
		$url=ZTO_EXPRESS_CLIENT_URL;
	}else{
		$url=ZTO_EXPRESS_URL;
	}
	
	if($site=='SH'){
		$user=ZTO_EXPRESS_USER_SH;
		$pw=ZTO_EXPRESS_PASSWORD_SH;
	}elseif ($site=='JX') {
		$user=ZTO_EXPRESS_USER_JX;
		$pw=ZTO_EXPRESS_PASSWORD_JX;
	}elseif ($site=='BJ') {
		$user=ZTO_EXPRESS_USER_BJ;
		$pw=ZTO_EXPRESS_PASSWORD_BJ;
	}elseif($site=='CD'){
		$user=ZTO_EXPRESS_USER_CD;
		$pw=ZTO_EXPRESS_PASSWORD_CD;
	}elseif ($site=='SHSG') {
		$user=ZTO_EXPRESS_USER_SHSG;
		$pw=ZTO_EXPRESS_PASSWORD_SHSG;
	}elseif ($site=='JXSG') {
		$user=ZTO_EXPRESS_USER_JXSG;
		$pw=ZTO_EXPRESS_PASSWORD_JXSG;
	}elseif ($site=='SZSG') {
		$user=ZTO_EXPRESS_USER_SZSG;
		$pw=ZTO_EXPRESS_PASSWORD_SZSG;
	}elseif ($site=='CDSG') {
		$user=ZTO_EXPRESS_USER_CDSG;
		$pw=ZTO_EXPRESS_PASSWORD_CDSG;
	}elseif ($site=='WHSG') {
		$user=ZTO_EXPRESS_USER_WHSG;
		$pw=ZTO_EXPRESS_PASSWORD_WHSG;
	}elseif ($site=='BJSG') {
		$user=ZTO_EXPRESS_USER_BJSG;
		$pw=ZTO_EXPRESS_PASSWORD_BJSG;
	}elseif ($site=='SHZSG') {
		$user=ZTO_EXPRESS_USER_SHZSG;
		$pw=ZTO_EXPRESS_PASSWORD_SHZSG;
	}elseif($site=='TEST'){
		$user='test';
		$pw='ZTO123';
		$url="http://testpartner.zto.cn/client/interface.php";
	}

	$data = array( 
	    'style'=>'json',
	    'func'=>$func, 
	    'partner'=>$user,
	    'datetime'=>date("Y-m-d H:i:s"), 
	    'verify'=>'', 
	    'content'=>base64_encode(json_encode($content))
	);
	$data['verify'] = md5($data['partner'].$data['datetime'].$data['content'].$pw);
	return HttpClient::quickPost($url,$data);
}

function zto_parser_response($result){
	$err_codes = array(
	    's00'    =>  '未知错误',
	    's01'    =>  '服务器异常',
	    's02'    =>  '非法的IP来源',
	    's03'    =>  '无效的指令操作',
	    's04'    =>  '非法的数据签名',
	    's05'    =>  '缺少完整的参数',
	    's06'    =>  '非法的数据格式',
	    's07'    =>  '数据内容不符合要求',
	    's08'    =>  '非法的账户信息',
	    's30'    =>  '图形验证码不正确',
	    's31'    =>  '短信验证码不正确',
		's50'    =>  '没有指定有效的查询条件',
	    's51'    =>  '没有找到请求的数据',
	    's52'    =>  '未从数据源中获取到有效的数据内容',
		's53'    =>  '无权操作指定的数据',
	    's60'    =>  '提交的数据与服务器中的数据一致，忽略更新',
	    's70'    =>  '数据保存失败',
		's90'    =>  '重复操作',
	    'e01'    =>  '缺少业务所必须的数据内容',
	    'e02'    =>  '业务所需要的数据内容不符合要求',
	);
	$result1 = json_decode($result);
	if($result1->result == 'true'){
		$result1->result = true;
		return $result1;
	}else{
		$result1->result = false;
		$err_code =  $result1->code;
		if(!empty($result1->remark)){
			$result1->remark .= $result1->code;
		}else{
			$result1->remark = $err_codes[$err_code];
		}
		return $result1;
	}
}
//var_dump(zto_mail_trace('100000000001'));
//快件追踪接口 mail.trace
function zto_mail_trace($mailno,$site='SH'){
	$result =  get_zto_response('mail.trace',array('mailno' => $mailno),$site);
	return zto_parser_response($result);
}

/*
 * 快件批量状态查询 (mail.status)
 * content: {"mailnos": ["100000000001","100000000002"]}
 * response:
	    {
		    "result": "true"
		    ,"remark": ""
		    ,"list":[
		        {
		             "mailno": "100000000001"
		            ,"status": "got"
		            ,"time": "2013-05-13 13:52:31"
		            ,"branch_id": "021056"
		            ,"branch_name": "上海闵行一部"
		            ,"remark": "进行揽收扫描"
		            ,"next_id": ""
		            ,"next_name": ""
		        }
		        ,{
		             "mailno": "100000000002"
		            ,"status": "running"
		            ,"time":"2013-05-13 16:20:08"
		            ,"branch_id":"021056"
		            ,"branch_name":"上海闵行一部"
		            ,"remark":"进行发出扫描"
		            ,"next_id":"021001"
		            ,"next_name":"上海分拨中心"
		        }
		    ]
		}
 * */
//var_dump(zto_mail_status(array('100000000001')));
function zto_mail_status($mailnos,$site='SH'){
	$result =  get_zto_response('mail.status',array('mailnos' => $mailnos),$site);
	return zto_parser_response($result);
}
/*
 * 电子运单号可用数量查询接口 (mail.counter)
 * content: {"lastno": "100000000016"} 已申请过的最后一个运单号码。如未提供该号码，接口将从最开始的一位进行统计。
 * response:
	 {
	    "result": "true",
	    "counter": {
	        "available": 10300
	    }
	 }
 * */

//var_dump(zto_mail_counter());
function zto_mail_counter($lastno=0,$site='SH'){
	$request  = array('mailno' => $lastno);
	$result =  get_zto_response('mail.counter',$request,$site);
	return zto_parser_response($result);
}
/*
 * 电子运单号申请接口 (mail.apply)
 * content: {"number": "2","lastno": "100000000016"}
 * response:
	{
	    "result": "true",
	    "remark": "",
	    "list": [
	        100000000023,
	        100000000036
	    ]
	}
 * */
//var_dump(zto_mail_apply(2));
function zto_mail_apply($num,$mailno=null,$site='SH'){
	$result =  get_zto_response('mail.apply',array('number' => $num,'lastno' => $mailno),$site);
	return zto_parser_response($result);
}
/*
 * 新增或修改订单 (order.submit)
 * content: {
			     "mailno": "1000000000016"
			     ,"sender": 
			    ,"receiver": {
			        ,"name": "杨逸嘉"
			        ,"mobile": "13687654321"
			        ,"phone": "010-22226789"
			        ,"city": "四川省,成都市,武侯区"
			        ,"address": "育德路497号"
			    }
			}
 * response:
	{
	    "result": "true"
		,"keys": {
	         "orderid": "130520142013234"
	        ,"mark": "四川成都"
		}
	}
 * */
 //to do

//$sender = array(
//			        "name"		=> "李琳"
//			        ,"mobile"	=> null
//			        ,"city"		=> "上海市,上海市,青浦区"
//			        ,"address"	=> "华新镇华志路123号"
//			    );
//$receiver = array(
//			        "name"		=> "杨逸嘉"
//			        ,"mobile"	=> null
//			        ,"city"		=>"四川省,成都市,武侯区"
//			        ,"address"	=>"育德路497号"
//			    );

//var_dump(_zto_order_submit('742200035427340','100000000009',$sender,$receiver));
function _zto_order_submit($shipmentId,$mailno,$sender,$receiver){
	$site=getSiteWithShipmentId($shipmentId);

	$result =  get_zto_response('order.submit',array('id' 		=> $shipmentId,
													 'mailno' 	=> $mailno,
													 'sender' 	=> $sender,
													 'receiver' => $receiver),
	$site
	);
	return zto_parser_response($result);
}

/*
 * 查询订单详情 (order.query)
 * content: {
 * 				 "id"    : 订单号或者shipmentId
			     "mailno": "1000000000016" 运单号。当同时也指定了订单号进行查询时，以订单号为首要查询条件
			}
 * response:
	{
	     "result": "true"
	    ,"data": {
	         "id": "ZTO-130520142013234"
	        ,"mailno": "100000000016"
	        ,"status": "got"
	        ,"time": "2013-05-20 16:09:12"
	        ,"steps":[step1,step2
	        ]
	    }
	}
 * step :{
         "time":"2013-05-13 19:26:23"
        ,"branch_id":"021001"
        ,"branch_name":"上海分拨中心"
        ,"type":"in"/get/out
        ,"remark":"快件进行入站扫描"
        ,"next_id":""
        ,"next_name":""
    }
 * */
//var_dump(zto_order_query('742200035427340'));
function zto_order_query($shipmentId,$mailno = null){
	$site=getSiteWithShipmentId($shipmentId);
	$result =  get_zto_response('order.query',array('id' => $shipmentId,'mailno' => $mailno),$site);
	return zto_parser_response($result);
}

function zto_order_submit_simple($shipment_id){
	global $db;
	$sql="SELECT
			s.SHIPMENT_ID,
			s.TRACKING_NUMBER mailno,
			o.consignee,
			er_p.region_name province,
			er_c.region_name city,
			er_d.region_name region,
			o.address address
		FROM
			romeo.shipment s
		LEFT JOIN ecshop.ecs_order_info o ON s.primary_order_id = o.order_id
		LEFT JOIN ecshop.ecs_region er_p ON o.province = er_p.region_id
		LEFT JOIN ecshop.ecs_region er_c ON o.city = er_c.region_id
		LEFT JOIN ecshop.ecs_region er_d ON o.district = er_d.region_id
		WHERE
			s.SHIPMENT_ID = '{$shipment_id}'
		LIMIT 1";
	$bill_info=$db->getRow($sql);

	$sender_name =  "乐其";
	$sender_city = "上海市,上海市,青浦区";
	$sender_address = "青浦城区已验视";

	$receiver_name = $bill_info['consignee'];
	$receiver_city = $bill_info['province'].','.$bill_info['city'].','.$bill_info['region'];
	$receiver_address = $bill_info['address'];

	return zto_order_submit($bill_info['shipment_id'],$bill_info['mailno'],
		$sender_name,$sender_city,$sender_address,$receiver_name,$receiver_city,$receiver_address
	);
}

//var_dump(zto_order_submit('742200035427340','100000000009',"李琳","上海市,上海市,青浦区","华新镇华志路123号","杨逸嘉","四川省,成都市,武侯区","育德路497号"));
function zto_order_submit($shipmentId,$mailno,$sender_name,$sender_city,$sender_address,$receiver_name,$receiver_city,$receiver_address){
	$sender = array(
			        "name"		=> $sender_name
			        ,"mobile"	=> null
			        ,"city"		=> $sender_city
			        ,"address"	=> $sender_address
			    );
	$receiver = array(
			        "name"		=> $receiver_name
			        ,"mobile"	=> null
			        ,"city"		=> $receiver_city
			        ,"address"	=> $receiver_address
			    );
	return _zto_order_submit($shipmentId,$mailno,$sender,$receiver);
}



function zto_mail_applys($site='SH',$once=500){
	global $db;
	$count=0;
	while(true){
		$shipping_id = 115;//中通 ecshop.ecs_shipping中主键
		$sql = "select max(tracking_number) from ecshop.thermal_express_mailnos where shipping_id = {$shipping_id} and branch = '{$site}' ";
		$tracking_number = $db->getOne($sql);
		$apply_result = zto_mail_apply($once,$tracking_number,$site);

		// print_r($apply_result);
		
		if($apply_result->result){
			foreach($apply_result->list as $key=>$tracking_number){
				$sql = "insert into ecshop.thermal_express_mailnos " .
							"(tracking_number,shipping_id,branch,create_time,update_time) values " .
							"('{$tracking_number}',{$shipping_id},'{$site}',now(),now()) ";
				$db->query($sql);
				$count+=1;
			}
		}else{
			break;
		}
   }
   return $count;
}



function zto_post_data($url,$data){
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_URL, $url );
	curl_setopt ( $ch, CURLOPT_POST, 1 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_POSTFIELDS,  http_build_query($data) );
	$res = curl_exec ( $ch );  
	curl_close ( $ch );
	return $res;
}


/**
 * 中通接口调用（新）
 * 目前仅用于 大头笔获取
 */
function get_zto_bigPen($order){//硬代码，待后续移至数据库查询
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
	$user = ZTO_EXPRESS_USER_SH;
	$pwd = ZTO_EXPRESS_PASSWORD_SH;
	if($order['branch']=='SH'){
		$order['send_province'] = '上海';
		$order['send_city'] = '上海市';
		$order['send_district'] = '青浦区';
	}
	$string = "{
	    'send_province': '".$order['send_province']."',
	    'send_city': '".$order['send_city']."',
	    'send_district': '".$order['send_district']."',
	    'receive_province': '".$order['province_name']."',
	    'receive_city': '".$order['city_name']."',
	    'receive_district': '".$order['district_name']."',
	    'receive_address': '".$order['address']."'
	}";
	$data_digest= base64_encode(Bytes::toStr(Bytes::getBytes(md5($string.$pwd,true))));
	$url = "http://japi.zto.cn/zto/api_utf8/mark" ;

	$data=array();
	$data['data']=$string;
	$data['msg_type']='GETMARK';
	$data['company_id']=$user;
	$data['data_digest']=$data_digest;
	
	$response = zto_post_data($url,$data);
//	var_dump($response);
	$response_arr = json_decode($response);
	if($response_arr->status == 'true'){
		$bigPen = $response_arr->result->print_mark;
		return $bigPen;
	}else{
		return 'false';
	}

}

function get_zto_mark_single_order($orderSingle){
	$order_id = $orderSingle['order_id'];
	print_r(date('y-m-d h:i:s',time())."orderId:".$order_id." begin_apply_zto_mark! \n");
	$facility_id = $orderSingle['facility_id'];
	$receivercity = $orderSingle['province_name'].",".$orderSingle['city_name'].",".$orderSingle['district_name'];
	$receiveraddress = $orderSingle['address'];
	$sendcity = "上海,上海市,青浦区";
	$sendaddress = "";
	if(in_array($facility_id,array('120801050','137059426','24196974'))){//OR市场物资仓,上海精品仓,贝亲青浦仓
		$sendcity = "上海,上海市,青浦区";
		$sendaddress = "新丹路359号";
	}elseif(in_array($facility_id,array('137059428'))){//电商服务成都仓
		$sendcity="四川省,成都市,双流县";
		$sendaddress= "双华路三段458号";
	}elseif(in_array($facility_id,array('149849256'))){//哥伦布嘉兴仓
	}elseif(in_array($facility_id,array('194788297','19568549','81569822'))){//电商服务嘉善仓,电商服务上海仓,分销嘉善仓
		$sendcity = "浙江省,嘉兴市,嘉善县区";
		$sendaddress = "惠民街道松海路88号";
	}elseif(in_array($facility_id,array('185963137','185963138'))){//电商服务苏州仓-外包,电商服务苏州仓
		$sendcity = "江苏省,苏州市,高新区";
		$sendaddress = "浒墅关工业园青花路128号";
	}elseif(in_array($facility_id,array('185963146','185963147'))){//双11深圳水果仓-外包,电商服务深圳仓
		$sendcity = "广东省,深圳市,光明新区";
		$sendaddress = "塘家大道6号";
	}elseif(in_array($facility_id,array('22143846','22143847'))){//乐其上海仓_2（原乐其杭州仓）,电商服务上海仓_2（原电商服务杭州仓）
	}
	
	$string = array('sendcity'=>$sendcity,'sendaddress'=>$sendaddress,'receivercity'=>$receivercity,'receiveraddress'=>htmlspecialchars($receiveraddress, ENT_QUOTES));
//	print_r($string);
	$response_arr = json_decode(get_zto_response('order.marke',$string));
//	print_r($response_arr);
	$mark = "";
	global $db;
	if(!empty($response_arr->marke)){
		$mark = $response_arr->marke;
	}elseif(empty($response_arr->marke) && !empty($response_arr->mark)){
		$mark = $response_arr->mark;
	}else{
		QLog::log("test_tn_mark: ".date('y-m-d h:i:s',time())."orderId:".$order_id." apply_zto_failed! \n");
		if(!empty($orderSingle['district_name'])){
			$cont =" and rm.district_id = oi.district  "; 
		}else{
			$cont =" and district_id is null ";
		}
		$sql = "select zto_mark_name from ecshop.ecs_region_mark rm 
			inner join ecshop.ecs_order_info oi on oi.province = rm.province_id and oi.city = rm.city_id {$cont}
			 where oi.order_id = {$order_id} and oi.shipping_id = 115 ";
		$mark = $db->getOne($sql);
		if(empty($mark)){
			$mark = $orderSingle['province_name'].$orderSingle['city_name'];
		}
	
	}
	QLog::log("orderId:".$order_id." apply_zto_mark :".$mark." \n");
	$sql = "insert into ecshop.order_attribute(order_id,attr_name,attr_value) values('{$order_id}','ztoBigPen','{$mark}') ";
	$db->query($sql);
}

function shipOrder_zto($site,$shipmentId,$record, $order){
	$string = array(array('id'=>$order['order_id'].$shipmentId,
	'type'=>'','tradeid'=>'','mailno'=>'','seller'=>'','buyer'=>'',
	'sender'=>array('id'=>'','name'=>'乐其','company'=>iconv('utf-8','utf-8','乐其'),'mobile'=>'','phone'=>$record['sender_phone'],'area'=>'','city'=>iconv('utf-8','utf-8','上海青浦区'),'address'=>iconv('utf-8','utf-8','上海青浦区'),'zipcode'=>'','email'=>'','im'=>'','starttime'=>'','endtime'=>''),
	'receiver'=>array('id'=>'','name'=>htmlspecialchars($order['consignee'], ENT_QUOTES),'company'=>'','mobile'=>$order['mobile'],'phone'=>$order['tel'],'area'=>'','city'=>$order['province_name'].$order['city_name'],'address'=>htmlspecialchars($order['address'], ENT_QUOTES),'zipcode'=>'','email'=>'','im'=>''),
	'items'=>array(array('name'=> '母婴用品','quantity'=> '1','remark'=> '')),
	'weight'=> '','size'=> '','quantity'=> '1','price'=> '','freight'=> '','premium'=> '','pack_charges'=> '','other_charges'=> '0.00','order_sum'=> '','collect_moneytype'=> 'CNY','collect_sum'=> '','remark'=> ''
	));

	$response_arr = json_decode(get_zto_response('order.batch_submit',$string,$site));
	if($response_arr->result == 'true' && !empty($response_arr->keys)){
		foreach($response_arr->keys as $value){
			if($value->result=='true'){
				$return['flag'] = true;
				$return['tracking_number'] = $value->mailno;
				$return['bigPen'] = $value->mark;
			}else{
				$return['flag'] = false;
				$return['mes'] = $value->code.$value->keys.$value->remark;
			}
		}
	}else{
		$return['flag'] = false;
		$return['mes'] = $response_arr->result.$response_arr->keys.$response_arr->code.$response_arr->remark;
	}
	return $return;
	
}


