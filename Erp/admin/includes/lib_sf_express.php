<?php
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

/**
WEBSERVICE 地址 https://bsp-oisp.test.sf-express.com/bsp-oisp/ws/sfexpressService?wsdl
HTTP/POST 地址 https://bsp-oisp.test.sf-express.com/bsp-oisp/sfexpressService
开发环境接入编码:BSPdevelop
开发环境 Checkword:j8DzkIFgmlomPt0aLuwU
BSP 接口开发咨询 QQ 群号码:314535266
*/
class SFArataAgent
{
	private $account_id;
	private $checkword;
	private $monthAccount;
	private $http_url="https://bsp-oisp.test.sf-express.com/bsp-oisp/sfexpressService";

	function __construct($branch)
	{
		if($branch=='SHSG'){
		//水果上海仓
			$this->account_id='0219175065';//PROD
			$this->checkword='ra2wDNXBAhdXJXXQZNfwp0C5QcJePxwg';//PROD
			$this->http_url='http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService';
			$this->monthAccount='0219175065';
			// die('然而并没有什么顺丰上海');
		}elseif($branch=='SH'){
		//上海精品仓
			$this->account_id='0219175065';//PROD
			$this->checkword='ra2wDNXBAhdXJXXQZNfwp0C5QcJePxwg';//PROD
			$this->http_url='http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService';
			$this->monthAccount='0219175065';
			// die('然而并没有什么顺丰上海');
		}elseif($branch=='TEST'){
			$this->account_id='BSPdevelop';//TEST
			$this->checkword='j8DzkIFgmlomPt0aLuwU';//TEST
			$this->http_url='https://bsp-oisp.test.sf-express.com/bsp-oisp/sfexpressService';
			$this->monthAccount='0219175065';
		}elseif($branch=='SF_TEST'){
			$this->account_id='JXSSD';//TEST
			$this->checkword='t2ESxXzS05o0KFlO';//TEST
			$this->http_url='http://218.17.248.244:11080/bsp-oisp/sfexpressService';
			$this->monthAccount='0219175065';
		}elseif($branch=='DG'){
			// $this->account_id='0219175065';//PROD
			// $this->checkword='ra2wDNXBAhdXJXXQZNfwp0C5QcJePxwg';//PROD
			// $this->http_url='http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService';
			
			// 东莞方面来的邮件
			$this->account_id='sddzsm';//PROD
			$this->checkword='pzmcQe2g9HnbsCv0RhDBVBbNStTiqJO2';//PROD
			$this->http_url='http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService';

			// $this->account_id='BSPdevelop';//TEST
			// $this->checkword='j8DzkIFgmlomPt0aLuwU';//TEST 
			// $this->http_url='http://bspoisp.sit.sf-express.com:11080/bsp-oisp/sfexpressService';
			
			$this->monthAccount='7698041295';
		}elseif($branch=='SZSG'){
			//双11苏州水果仓-正常
			$this->account_id='0219175065';//PROD
			$this->checkword='ra2wDNXBAhdXJXXQZNfwp0C5QcJePxwg';//PROD
			$this->http_url='http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService';
			$this->monthAccount='0219175065';
			// die('然而并没有什么顺丰上海');
		}elseif($branch=='BJ' || $branch=='BJSG'){
			// USE 东莞
			$this->account_id='sddzsm';//PROD
			$this->checkword='pzmcQe2g9HnbsCv0RhDBVBbNStTiqJO2';//PROD
			$this->http_url='http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService';
			$this->monthAccount='7698041295';
		}elseif($branch=='JS'){
			// 嘉善仓 USE 东莞
			$this->account_id='sddzsm';//PROD
			$this->checkword='pzmcQe2g9HnbsCv0RhDBVBbNStTiqJO2';//PROD
			$this->http_url='http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService';
			$this->monthAccount='7698041295';
		}elseif($branch=='SGSZ'){
			// 水果深圳仓 USE 东莞
			$this->account_id='sddzsm';//PROD
			$this->checkword='pzmcQe2g9HnbsCv0RhDBVBbNStTiqJO2';//PROD
			$this->http_url='http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService';
			$this->monthAccount='7698041295';
		}
	}

	function request($serviceName,$head,$body_xml){
		$request_xml="<?xml version='1.0' encoding='UTF-8'?> 
			<Request service=\"".$serviceName."\" lang=\"zh-CN\">
			<Head>".$head."</Head> 
			<Body>".$body_xml."</Body>
		</Request>";
		$checksum=base64_encode(md5($request_xml.$this->checkword,true));
		return $this->post($this->http_url,$request_xml,$checksum);
	}

	function post($url,$xml,$verifyCode){
		$POST_DATA = array(
		    'xml' => $xml,
		    'verifyCode'=>$verifyCode,
		);
		$curl=curl_init($url);
		curl_setopt($curl,CURLOPT_POST, TRUE);
		// ↓はmultipartリクエストを許可していないサーバの場合はダメっぽいです
		// @DrunkenDad_KOBAさん、Thanks
		//curl_setopt($curl,CURLOPT_POSTFIELDS, $POST_DATA);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($POST_DATA));
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, FALSE);  // オレオレ証明書対策
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, FALSE);  // 
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, TRUE);
		// curl_setopt($curl,CURLOPT_COOKIEJAR,      'cookie');
		// curl_setopt($curl,CURLOPT_COOKIEFILE,     'tmp');
		curl_setopt($curl,CURLOPT_FOLLOWLOCATION, TRUE); // Locationヘッダを追跡
		//curl_setopt($curl,CURLOPT_REFERER,        "REFERER");
		//curl_setopt($curl,CURLOPT_USERAGENT,      "USER_AGENT"); 

		$output= curl_exec($curl);

		//echo "SFArataAgent::POST url=$url, xml=$xml, verifyCode=$verifyCode, output=$output".PHP_EOL;
		QLog::log("SFArataAgent::POST url=$url, xml=$xml, verifyCode=$verifyCode, output=$output");

		return $output;
	}

	/////

	/**
	* @param tracking_number 
	* @param tracking_type 1:根据顺丰运单号查询, order 节点中 tracking_number 将被当作顺丰运单号处理 2:根据客户订单号查询, order 节点中 tracking_number 将被当作客户订单号处理
	* @param method_type 1:标准路由查询 2:定制路由查询
	*/
	public function RouteService($tracking_number,$tracking_type='1',$method_type='1'){
		$output=$this->request(
			'RouteService',
			$this->account_id,
			"<RouteRequest tracking_type='{$tracking_type}' method_type='{$method_type}' tracking_number='{$tracking_number}'/>"
		);
		// var_dump($output);
		$xml_obj=new SimpleXMLElement($output);
		// var_dump($xml_obj);
		$head=$xml_obj->Head."";
		if($head=='OK'){
			$nodes=array();
			foreach ($xml_obj->Body->RouteResponse->Route as $route) {
				$node=array();
				$node['remark']=$route['remark'].'';
				$node['accept_time']=$route['accept_time'].'';
				$node['accept_address']=$route['accept_address'].'';
				$node['opcode']=$route['opcode'].'';
				// var_dump($node);
				$nodes[]=$node;
			}
			// var_dump($routes);
			return $nodes;
		}else{
			$error=$xml_obj->Error.'';
			// var_dump($error);
			return false;
		}
	}

	/**
	* @param order the Order Object as array
	* @param cod empty or cod
	* @param insure 0 for need not and bigger for insurance
	*/
	public function OrderService($order,$cod='',$insure=0){
		$condition = '';
		if($cod =='cod'){
			// 代收货款 COD
			// value 为货款,以原寄地所在区域币种为准,如中国大陆为人民币,香港为港币,保留 3 位小数。
			// value1 为代收货款卡号
			$express_type= 2;
			$condition .=' <AddedService name="COD" value="2000" value1="0213892391" /> ';
		}else{
			$express_type= 2;
		}
		if($insure>0){
			$condition.='<AddedService name="INSURE" value="'.$insure.'" />';
		}
		// sendstarttime="" 
		// order_source="'.$order['order_source'].'"
		$xml = '<Order orderid="'.$order['order_id'].'"
				   j_company="'.$order['j_company'].'"
	               j_contact="'.$order['j_contact'].'"
	               j_tel="'.$order['j_tel'].'" 
	               j_mobile="'.$order['j_mobile'].'" 
	               j_province="'.$order['j_province'].'"
	               j_city="'.$order['j_city'].'" 
	               j_county="'.$order['j_county'].'" 
	               j_address="'.$order['j_address'].'" 
	               d_company="顺丰速运"
	               d_contact="'.htmlspecialchars($order['d_contact'], ENT_QUOTES).'" 
	               d_tel="'.$order['d_tel'].'"
	               d_mobile="'.$order['d_mobile'].'" 
	               d_province="'.$order['d_province'].'"
	               d_city="'.$order['d_city'].'" 
	               d_county="'.$order['d_county'].'" 
	               d_address="'.htmlspecialchars($order['d_address'], ENT_QUOTES).'"
	               express_type="'.$express_type.'"
	               pay_method="'.$order['pay_method'].'" 
	               custid="'.$this->monthAccount.'" 
	               parcel_quantity="'.$order['parcel_quantity'].'"
	               remark="">'.
	               '<Cargo name="'.$order['cargo_name'].'"></Cargo>'.
	               $condition.
	               '</Order>';
		$output=$this->request(
			'OrderService',
			$this->account_id,
			$xml
		);
		// var_dump($output);
		$xml_obj=new SimpleXMLElement($output);
		// var_dump($xml_obj);
		$head=$xml_obj->Head."";
		if($head=='OK'){
			$res=array();
			$res['orderid']=$xml_obj->Body->OrderResponse['orderid'].'';
			$res['mailno']=$xml_obj->Body->OrderResponse['mailno'].'';
			$res['origincode']=$xml_obj->Body->OrderResponse['origincode'].'';
			$res['destcode']=$xml_obj->Body->OrderResponse['destcode'].'';
			$res['filter_result']=$xml_obj->Body->OrderResponse['filter_result'].'';
			$body = $xml_obj->Body."";
			QLog::log("sf_add_mailno_ok : ".$res['orderid']." mailnos :".$res['mailno']);
			return $res;
		}else{
			$error=$xml_obj->ERROR.'';
			QLog::log("sf_add_mailno_error : ".$order['order_id']." errorInfo :".$error);
			var_dump($order['order_id']." error :".$error);
			return false;
		}
	}

	/**
	* @param order_id the shipment id
	*/
	public function OrderSearchService($order_id){
		$xml="<OrderSearch orderid='{$order_id}'/>";
		$output=$this->request(
			'OrderSearchService',
			$this->account_id,
			$xml
		);
		// var_dump($output);
		$xml_obj=new SimpleXMLElement($output);
		// var_dump($xml_obj);
		$head=$xml_obj->Head."";
		if($head=='OK'){
			$res=array();
			$res['orderid']=$xml_obj->Body->OrderResponse['orderid'].'';
			$res['mailno']=$xml_obj->Body->OrderResponse['mailno'].'';
			$res['origincode']=$xml_obj->Body->OrderResponse['origincode'].'';
			$res['destcode']=$xml_obj->Body->OrderResponse['destcode'].'';
			$res['filter_result']=$xml_obj->Body->OrderResponse['filter_result'].'';

			return $res;
		}else{
			$error=$xml_obj->Error.'';
			// var_dump($error);
			return false;
		}
	}

	/**
	* @param order_id shipment_id
	* @param tracking_number
	* @param $is_cancel default as false
	*/
	public function OrderConfirmService($order_id,$tracking_number,$is_cancel=false){
		if($is_cancel){
			$xml="<OrderConfirm orderid='{$order_id}' mailno='{$tracking_number}' dealtype='2' />";
		}else{
			$xml="<OrderConfirm orderid='{$order_id}' mailno='{$tracking_number}' dealtype='1' />";
				// ."<OrderConfirmOption weight='3.56' volume='33,33,33'/>"
				// ."</OrderConfirm>";
		}
		$output=$this->request(
			'OrderConfirmService',
			$this->account_id,
			$xml
		);
		// var_dump($output);
		$xml_obj=new SimpleXMLElement($output);
		// var_dump($xml_obj);
		$head=$xml_obj->Head."";
		if($head=='OK'){
			$res=array();
			$res['orderid']=$xml_obj->Body->OrderConfirmResponse['orderid'].'';
			$res['mailno']=$xml_obj->Body->OrderConfirmResponse['mailno'].'';
			$res['res_status']=$xml_obj->Body->OrderConfirmResponse['res_status'].'';

			return $res;
		}else{
			$error=$xml_obj->Error.'';
			// var_dump($error);
			return false;
		}
	}
}
?>