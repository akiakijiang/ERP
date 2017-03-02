<?php

class CSoapClient extends CApplicationComponent 
{
	/**
	 * Soap版本
	 *
	 * @var int
	 */
	public $soapVersion=SOAP_1_1;
	/**
	 * 登录名
	 *
	 * @var string
	 */
	public $login;
	/**
	 * 登录密码
	 *
	 * @var string
	 */
	public $password;

	private $_wsdlBaseUrl;     
	private $_soapClient=array();
    
	/**
	 * 取得服务
	 *
     * @param string $serviceName
	 */
	public function __get($name)
	{
		if($this->hasService($name))
			return $this->getService($name);
		else
			return parent::__get($name);
	}
    
    public function __isset($name)
    {
        if($this->hasService($name))
            return $this->getComponent($name)!==null;
        else
            return parent::__isset($name);
    }
    
    public function getService($name)
    {
    	if(isset($this->_soapClient[$name]))
        	return $this->_soapClient[$name];
        else
        {
            $options=array(
                'soap_version'=>$this->soapVersion,
                'login'=>$this->login,
                'password'=>$this->password,
                'exceptions'=>true,  // 抛出异常，而不是返回异常对象
                'cache_wsdl'=>WSDL_CACHE_MEMORY,  // WSDL_CACHE_NONE, WSDL_CACHE_DISK, WSDL_CACHE_MEMORY or WSDL_CACHE_BOTH
            	'compression'=>SOAP_COMPRESSION_ACCEPT|SOAP_COMPRESSION_GZIP,
            );
            return $this->_soapClient[$name]=new SoapClient($this->getWsdlBaseUrl()."/$name?wsdl",$options);
        }
    }
    
    /**
     * 服务
     *
     * @param unknown_type $name
     */
    public function hasService($name) 
    {
    	return true;
    }
    
    /**
     * 超时时间
     *
     * @return integer
     */
    public function getTimeout()
    {
    	return ini_get('default_socket_timeout'); 
    }

    /**
     * 设置Wsdl文件的根地址
     *
     * @param unknown_type $path
     * @return unknown
     */
    public function setWsdlBaseUrl($path)
    {
        $this->_wsdlBaseUrl=rtrim($path,"/");
    }
    
    /**
     * 取得wsdl根地址
     *
     * @return string
     */
    public function getWsdlBaseUrl()
    {
        return $this->_wsdlBaseUrl;
    }
}
