<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'data/master_config.php';

/**
 * 短信发送类
 * @author yfhu
 *
 */
class Client{
	
	/**
	 * 生成短信客户端
	 * @param unknown $url
	 * @param unknown $serialNumber
	 * @param unknown $password
	 * @param string $sessionKey
	 */
	function Client($url,$serialNumber,$password,$sessionKey)
	{
		$this->url = $url;
		$this->serialNumber = $serialNumber;
		$this->password = $password;
		$this->sessionKey = $sessionKey;
			
		$this->soap = new SoapClient ( $url );		
					
	}

	
	/**
	 * 发端短信
	 * @param unknown $mobiles
	 * @param unknown $content
	 * @param string $sendTime
	 * @return Ambigous <mixed, boolean, string, unknown>
	 */
	function sendSMS($mobiles=array(),$content,$sendTime='')
	{
		$mobiles = implode(',',$mobiles);
		
		$params = array('zh'=>$this->serialNumber,'mm'=>$this->password,
			'nr'=>$content,'hm'=>$mobiles, 'dxlbid'=>$this->sessionKey
			);
			
		$result = $this->soap->sendsms($params);
		return $result->sendsmsResult;
		
	}
	
	
	/**
	 * 获取剩余条数/10
	 * @return Ambigous <mixed, boolean, string, unknown>
	 */
	function getBalance()
	{
		$params = array('zh'=>$this->serialNumber,'mm'=>$this->password,'dxlbid'=>$this->sessionKey);
		$result = $this->soap->Balance($params);
		return $result->BalanceResult;
		
	}
		
}

