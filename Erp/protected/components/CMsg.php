<?php

/**
 * 短信服务
 * 
 * @auth yxiang@leqee.com
 */
class CMsg extends CComponent implements IApplicationComponent
{
	public $host;
	public $port;
	public $path;
	
	private $client;
	private $serviceName='message.application.MessageApplicationService';
	
	/**
	 * @var array the behaviors that should be attached to this component.
	 * The behaviors will be attached to the component when {@link init} is called.
	 * Please refer to {@link CModel::behaviors} on how to specify the value of this property.
	 * @since 1.0.2
	 */
	public $behaviors=array();

	private $_initialized=false;

	/**
	 * Initializes the application component.
	 * This method is required by {@link IApplicationComponent} and is invoked by application.
	 * If you override this method, make sure to call the parent implementation
	 * so that the application component can be marked as initialized.
	 */
	public function init()
	{
		Yii::import('CRpcClient',true);
		$this->client= new CRpcClient();
		$this->client->hostname=$this->host;
		$this->client->port=$this->port;
		$this->client->servicePath=$this->path;
		$this->client->serviceName=$this->serviceName;
		
		$this->attachBehaviors($this->behaviors);
		$this->_initialized=true;
	}

	/**
	 * Checks if this application component bas been initialized.
	 * @return boolean whether this application component has been initialized (ie, {@link init()} is invoked).
	 */
	public function getIsInitialized()
	{
		return $this->_initialized;
	}
	
	/**
	 * 短信发送, 支持批量发送
	 *
	 * @param string $message
	 * @param array $phone
	 * @param string $userKey
	 * @param string $provider 服务提供商，默认用亿美
	 * 
	 * @return boolean
	 */
	public function send($message,$phone,$userKey,$provider='emay')
	{
		if(empty($phone))
			return false;

		if(is_string($phone))
			$phone=preg_split('/\s*,\s*/',$phone,-1,PREG_SPLIT_NO_EMPTY);
			
		try
		{
			$result=$this->client->call('sendBatchMessage',array($userKey,$phone,$message,$provider),array("string","list","string","string"));
			return isset($result)&&($provider=='emay'&&$result===0);
		}
		catch(Exception $e)
		{
			Yii::log("短信发送异常，".$e->getMessage(),CLogger::LEVEL_WARNING);
			return false;
		}
	}
}
