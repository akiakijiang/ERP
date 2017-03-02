<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'data/master_config.php';
require_once ROOT_PATH . 'admin/includes/cls_message_base.php';

/**
 * 短信发送类
 * @author yfhu
 *
 */
class YiduPostMessageClient extends MessageClientBase{
	
	/**
	 * 生成短信客户端
	 * @param unknown $url
	 * @param unknown $serialNumber
	 * @param unknown $password
	 * @param string $sessionKey
	 */
	function YiduPostMessageClient($url,$zh,$mm,$sms_type)
	{
		$this->url = $url;
		$this->zh = $zh;
		$this->mm = $mm;
		$this->sms_type = $sms_type;
		
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_HEADER, 0);//设置header
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
		curl_setopt($this->ch, CURLOPT_POST, 1);//post提交方式
					
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
		$curlPost = "zh={$this->zh}&mm={$this->mm}&sms_type={$this->sms_type}&hm={$mobiles}&nr=$content";
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $curlPost);
		$response = curl_exec($this->ch);
		if(strpos($response,"0:") === 0) {
			$response = 0;
		}
		return $response;
		
	}
	
	
	/**
	 * 获取剩余条数/10
	 * @return Ambigous <mixed, boolean, string, unknown>
	 */
	function getBalance()
	{
		//$params = array('zh'=>$this->serialNumber,'mm'=>$this->password,'dxlbid'=>$this->sessionKey);
		//$result = $this->soap->Balance($params);
		//return $result->BalanceResult;
		return 10000;
	}
		
}


