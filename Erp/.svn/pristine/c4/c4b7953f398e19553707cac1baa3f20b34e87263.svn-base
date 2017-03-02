<?php
require_once(__DIR__.'/../includes/init.php');

/**
* OMSSERVER API DELEGATE
* You need add the next line alike api root definition in master_config.php
* `define('OMSSERVER_API_ROOT','http://127.0.0.1:38888/omsserver/');`
*/
class OMSSERVER_API_DELEGATE
{
	
	function __construct()
	{
		# code...
	}

	public static function getErpOrderFacilityType($order_id){
		global $db;
		$sql="SELECT
			facility_type
		FROM
			ecshop.ecs_order_info oi
		LEFT JOIN romeo.facility f ON oi.facility_id = f.FACILITY_ID
		WHERE
			oi.order_id = {$order_id}
		LIMIT 1
		";
		$facility_type=$db->getOne($sql);
		return $facility_type;
	}

	public static function getErpOrderSnWithId($order_id){
		global $db;
		$order_sn=$db->getOne("SELECT order_sn FROM ecshop.ecs_order_info WHERE order_id=".$order_id." LIMIT 1");
		return $order_sn;
	}

	private function post($sub_url='',$POST_DATA=array()){
		$curl=curl_init(OMSSERVER_API_ROOT.$sub_url);
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
		return $output;
	}

	/**
	 * 传说中的取消订单，销售采购可用
	 * SUB URL:cancelIndicate/cancel
	 * 成功返回，{ code:00000, msg:XXXXX }
	 * 失败返回，{ code:00001, msg:XXXXX }
	 * 异常失败返回，{ code:00002, msg:XXXXX }
	 */
	public function order_cancel($order_sn,&$msg=null){
		try{
			$output=$this->post('cancelIndicate/cancel',array('omsOrderSn'=>$order_sn));
			if(empty($output)){
				throw new Exception("OMSSERVER Cancel Api Error: EMPTY OUTPUT for #".$order_sn, -1);
			}
			$json=json_decode($output);
			if($json===NULL || $json===false){
				throw new Exception("OMSSERVER Cancel Api Error: Strange OUTPUT [".$output."] for #".$order_sn, -1);
			}
			if(isset($json->msg)){
				$msg=$json->msg;
			}else{
				$msg='OMSSERVER Cancel Api Note: Did not return [msg]';
			}
			if($json->code=='00000'){
				return true;
			}else{
				return false;
			}
		}catch(Exception $e){
			$msg=$e->getMessage();
			return false;
		}
	}
	
	/** 菜鸟销售订单取消接口
	 * * SUB URL:cancelIndicate/cancel
	 * 成功返回，{ code:00000, msg:XXXXX }
	 * 失败返回，{ code:00001, msg:XXXXX }
	 * 异常失败返回，{ code:00002, msg:XXXXX }
	 */
	public function order_cainiao_cancel($order_sn,&$msg=null){
		try{
			$output=$this->post('CainiaoOrderCancel/cancel',array('omsOrderSn'=>$order_sn));
			if(empty($output)){
				throw new Exception("OMSSERVER Cancel Api Error: EMPTY OUTPUT for #".$order_sn, -1);
			}
			$json=json_decode($output);
			if($json===NULL || $json===false){
				throw new Exception("OMSSERVER Cancel Api Error: Strange OUTPUT [".$output."] for #".$order_sn, -1);
			}
			if(isset($json->msg)){
				$msg=$json->msg;
			}else{
				$msg='OMSSERVER Cancel Api Note: Did not return [msg]'; 
			}
			if($json->code=='00000'){
				return true;
			}else{
				return false;
			}
		}catch(Exception $e){
			$msg=$e->getMessage();
			return false;
		}
	}
}
