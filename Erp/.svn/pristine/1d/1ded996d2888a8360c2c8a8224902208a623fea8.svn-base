<?php 


/**
 * 淘宝对外部提供的REST API客户端
 * 
 * @author yxiang@leqee.com
 * @version 2010-11-02
 */
class TaobaoClient
{
	private $isSandbox=false;
	private $appKey;
	private $appSecret;
	private $sessionKey;
	
	public function __construct($appKey,$appSecret,$sessionKey,$isSandbox=false)
	{
		$this->isSandbox=$isSandbox;
		$this->appKey=$appKey;
		$this->appSecret=$appSecret;
		$this->sessionKey=$sessionKey;
	}
	
	/**
	 * 设置是否是沙盒环境
	 *
	 * @param boolean $isSandbox
	 */
	public function setIsSandbox($isSandbox)
	{
		$this->isSandbox=(boolean)$isSandbox;
	}
	
	/**
	 * 是否是沙盒环境
	 *
	 * @return boolean
	 */
	public function getIsSandbox()
	{
		return $this->isSandbox;
	}
	
	/**
	 * 完成一次taobao web service请求，并返回请求结果
	 *
	 * @param string $method   请求方法
	 * @param array  $request  请求参数
	 * 
	 * @return TaobaoResponse
	 * @throws Exception
	 */
	public function execute($method,$request=array(),$sessionKey=null)
	{
		$params=array
		(
			'timestamp'=>date('Y-m-d H:i:s'),
			'format'=>'xml',
			'v'=>'2.0',
			'app_key'=>$this->appKey,
			'session'=>$this->sessionKey,
			'method'=>$method,
		);
		// session
		$params['session']=!is_null($sessionKey)?$sessionKey:$this->sessionKey;

		// 请求参数
		if ($request!==array())
			$params=array_merge($request,$params);
		// 参数签名
		$params['sign']=$this->createSign($params,$this->appSecret);

		// 远程服务地址 
		$url=$this->isSandbox?'http://gw.api.tbsandbox.com/router/rest':'http://gw.api.taobao.com/router/rest';
    	
		// 超时
		$timeout=30;
    	
		if(function_exists('curl_init'))
		{
			$opts=array(
				CURLOPT_URL=>$url,
				CURLOPT_POST=>TRUE,
				CURLOPT_POSTFIELDS=>$params,
				CURLOPT_SSL_VERIFYPEER=>FALSE,
				CURLOPT_SSL_VERIFYHOST=>TRUE,
				CURLOPT_FOLLOWLOCATION=>TRUE,
				CURLOPT_AUTOREFERER=>TRUE,
				CURLOPT_TIMEOUT=>$timeout,
				CURLOPT_HEADER=>FALSE,
				CURLOPT_RETURNTRANSFER=>TRUE,
			);
			$curl=curl_init();
			curl_setopt_array($curl,$opts);   
			$result=curl_exec($curl);
			$errno=curl_errno($curl);
			if($errno)
				$message=curl_error($curl);
			else
			{
				$info=curl_getinfo($curl);
				if($info['http_code']!==200)
				{
					$errno=$info['http_code'];
					$message=strip_tags($result);
				}
			}
			curl_close($curl);

			if($errno)
				throw new Exception("curl_exec failed to open stream: ". $message,$errno);
		}
		else
		{
			$opts=array('http'=>
				array(
					'method'=>'POST',
					'header'=>'Content-type: application/x-www-form-urlencoded',
					'content'=>http_build_query($params),
					'timeout'=>$timeout,
				)
			);
			$context=stream_context_create($opts);
			$result=@file_get_contents($url,false,$context);  // 禁止输出Error,统一做异常处理
		}

		if($result===FALSE)
			throw new Exception("file_get_contents failed to open stream: HTTP request failed!");
		else
			return new TaobaoResponse($result,$params['format'],$params['method']);	
	}

	// 创建签名函数 
	protected function createSign($paramArr, $appSecret)
	{
		$sign=$appSecret; 
		ksort($paramArr); 
		foreach($paramArr as $key => $val)
		{ 
			if($key!='')
				$sign .= $key.$val;
		} 
		$sign=strtoupper(md5($sign));  //Hmac方式
		//$sign = strtoupper(md5($sign.$appSecret)); //Md5方式    
		return $sign; 
	}
}

/**
 * 请求返回的结果
 * 
 * @author yxiang@leqee.com
 */
class TaobaoResponse extends CComponent
{	
	private $response;  // object
	
	public function __construct($response_str='',$response_format='xml',$method='')
	{
		if(!empty($response_str))
		{
			// 解析数据
			// 注意php5.2.3之前用json格式可能会存在bug
			switch($response_format)
			{
				case 'json':
					$json=CJavaScript::jsonDecode($response_str,false);
					if($json!==false && is_object($json))
					{
						$search_propertys=array(str_replace('.','_',substr($method,7).'_response'),'error_response','error_rsp');
						foreach($search_propertys as $property)
						{
							if(property_exists($json,$property))
							{
								$this->response=$json->{$property};
								break 2;
							}
						}
						throw new Exception("decode json response error, not find property in (". implode(",",$search_propertys).")");
					}
					break;
				case 'xml':
					$xml=simplexml_load_string($response_str);
					$this->response=$this->get_object_vars_final($xml);
					break;
				default:
					throw new Exception("not assigned format");
			}
		}

		if(!$this->response || !is_object($this->response))
			throw new Exception("parse response error!");
	}
	
	protected function get_object_vars_final($xml)
	{
		if($xml instanceof SimpleXMLElement)
		{
			// 取得属性
			$attr_keys=$attr_vals=$attrs=array();
			$attrs=get_object_vars($xml->attributes());
			if(!empty($attrs))
			{
				foreach($attrs as $attr_key=>$attr_val)
				{
					$attr_keys[]=$attr_key;
					$attr_vals=array_merge($attr_vals,$attr_val);
				}
			}

			$xml=get_object_vars($xml);
    		
			// 去掉特殊属性 如 @attributes
			foreach($attr_keys as $key)
				unset($xml[$key]);
    			
			// 指定了list属性则格式化为list
			if(isset($attr_vals['list']) && $attr_vals['list'] && (reset($xml) instanceof SimpleXMLElement))  // list
				$xml[key($xml)]=array(reset($xml));
    		
			foreach($xml as $key=>$obj)
				$xml[$key]=$this->get_object_vars_final($obj);

			$xml=(object)$xml;
		}
		else if(is_array($xml))
		{
			foreach($xml as $key=>$obj)
				$xml[$key]=$this->get_object_vars_final($obj);    		
		}
    	
		return $xml;
	}
    
	/**
	 * 取得某个属性
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if(property_exists($this->response,$name))
			return $this->response->{$name};
		else
			parent::__get($name);
	}
    
	/**
	 * @return boolean
	 */
	public function __isset($name)
	{
		if(property_exists($this->response,$name))
			return true;
		else
			parent::__isset($name);
	}
	
	public function getCode()
	{
		return property_exists($this->response,'code')?$this->response->code:null;
	}
	
	public function getMsg()
	{
		return property_exists($this->response,'msg')?$this->response->msg:null;
	}
	
	public function getSubCode()
	{
		return property_exists($this->response,'sub_code')?$this->response->sub_code:null;
	}
	
	public function getSubMsg()
	{
		return property_exists($this->response,'sub_msg')?$this->response->sub_msg:null;
	}
	
	public function isSuccess()
	{
		return is_object($this->response) && !isset($this->response->code);
	}
	
	public function isError()
	{
		return isset($this->response->code);
	}
}