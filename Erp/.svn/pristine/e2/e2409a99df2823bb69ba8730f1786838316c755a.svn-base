<?php

/**
 * web service 基础服务库， 所有基于web service的库文件请继承此文件
 * 
 * 目前我们的系统以灵活性函数驱动，所以尽量不要使用类
 * 为解决命名空间问题，所有的web service函数请用 “库名_方法名” 的命名规约
 * 
 * @copyright 2009 ouku.com
 * @author ouku ERP team
 * @category web service
 */


// 根目录
if (!defined('ROOT_PATH'))
    define('ROOT_PATH', realpath(dirname(__FILE__). '/../') );


/**
 * 根据service的服务名获取service的连接端
 *
 * @param string $service_name    服务名
 * @param string $service_type    目前的服务类型  ROMEO | REPORT
 * @param string $service_class   提供服务的对象， 默认为SoapClient, 也可以使用自定义的扩展类，比如Soap_Client,
 * @param array  $service_options 设置项，如果设置了将覆盖默认的设置项
 *   - soap_version         指定SOAP的版本
 *   - compression          是否压缩
 *   - encoding             编码
 *   - classmap 
 *   - trace                是否跟踪，设置为true后可以用SoapClient->__getLastRequest()等方法跟踪执行结果
 *   - exceptions           是否抛出异常
 *   - connection_timeout  （秒）链接超时时间。
 *                          并不是执行超时时间，设置socket的流的默认超时时间需要修改php.ini的default_socket_timeout             
 *   - login
 *   - password
 *   - proxy_host, proxy_port, proxy_login and proxy_password  代理相关
 * 
 * @return SoapClient
 */
function soap_get_client($service_name, $service_type = 'ROMEO', $service_class = 'SoapClient', $service_options = array())
{
    static $instance;

    $service_type = strtoupper($service_type);

    // 服务已初始化则返回
    if (isset($instance[$service_type][$service_name][$service_class]))
    {
        return $instance[$service_type][$service_name][$service_class];
    }

    // 初始化客户端服务句柄
    // 通过配置来取得服务地址，目前配置写在data/master_config.php中
    $options = array('soap_version' => SOAP_1_1, 'trace'=>true);
    if ($service_type == 'REPORT')
    {
        if (defined('REPORT_HTTP_USER') && REPORT_HTTP_USER)  $options['login']    = REPORT_HTTP_USER;
        if (defined('REPORT_HTTP_PASS') && REPORT_HTTP_PASS)  $options['password'] = REPORT_HTTP_PASS;
        $wsdl = REPORT_WEBSERVICE_URL . "{$service_name}?wsdl";
    }
    else if ($service_type == 'JJSHOUSE')
    {
        $wsdl = null;
        $options['uri'] = $service_name;
        $options['location'] = JJSHOUSE_WEBSERVICE_URL;
		if (defined('JJSHOUSE_HTTP_USER') && JJSHOUSE_HTTP_USER)  $options['login']    = JJSHOUSE_HTTP_USER;
        if (defined('JJSHOUSE_HTTP_PASS') && JJSHOUSE_HTTP_PASS)  $options['password'] = JJSHOUSE_HTTP_PASS;
    }
    else if ($service_type == 'JENJENHOUSE')
    {
        $wsdl = null;
        $options['uri'] = $service_name;
        $options['location'] = JENJENHOUSE_WEBSERVICE_URL;
		if (defined('JJSHOUSE_HTTP_USER') && JJSHOUSE_HTTP_USER)  $options['login']    = JJSHOUSE_HTTP_USER;
        if (defined('JJSHOUSE_HTTP_PASS') && JJSHOUSE_HTTP_PASS)  $options['password'] = JJSHOUSE_HTTP_PASS;
    }
    else if ($service_type == 'JENNYJOSEPH')
    {
        $wsdl = null;
        $options['uri'] = $service_name;
        $options['location'] = JENNYJOSEPH_WEBSERVICE_URL;
		if (defined('JJSHOUSE_HTTP_USER') && JJSHOUSE_HTTP_USER)  $options['login']    = JJSHOUSE_HTTP_USER;
        if (defined('JJSHOUSE_HTTP_PASS') && JJSHOUSE_HTTP_PASS)  $options['password'] = JJSHOUSE_HTTP_PASS;
    }
    else if ($service_type == 'DRESSDEPOT')
    {
    	$wsdl = null;
    	$options['uri'] = $service_name;
    	$options['location'] = DRESSDEPOT_WEBSERVICE_URL;
    	if (defined('JJSHOUSE_HTTP_USER') && JJSHOUSE_HTTP_USER)  $options['login']    = JJSHOUSE_HTTP_USER;
    	if (defined('JJSHOUSE_HTTP_PASS') && JJSHOUSE_HTTP_PASS)  $options['password'] = JJSHOUSE_HTTP_PASS;
    }
    else if ($service_type == 'DRESSFIRST')
    {
    	$wsdl = null;
    	$options['uri'] = $service_name;
    	$options['location'] = DRESSFIRST_WEBSERVICE_URL;
    	if (defined('JJSHOUSE_HTTP_USER') && JJSHOUSE_HTTP_USER)  $options['login']    = JJSHOUSE_HTTP_USER;
    	if (defined('JJSHOUSE_HTTP_PASS') && JJSHOUSE_HTTP_PASS)  $options['password'] = JJSHOUSE_HTTP_PASS;
    }
    else if ($service_type == 'AMORMODA')
    {
        $wsdl = null;
        $options['uri'] = $service_name;
        $options['location'] = AMORMODA_WEBSERVICE_URL;
		if (defined('JJSHOUSE_HTTP_USER') && JJSHOUSE_HTTP_USER)  $options['login']    = JJSHOUSE_HTTP_USER;
        if (defined('JJSHOUSE_HTTP_PASS') && JJSHOUSE_HTTP_PASS)  $options['password'] = JJSHOUSE_HTTP_PASS;
    }
    else if ($service_type == 'FAUCETLAND')
    {
        $wsdl = null;
        $options['uri'] = $service_name;
        $options['location'] = FAUCETLAND_WEBSERVICE_URL;
		if (defined('JJSHOUSE_HTTP_USER') && JJSHOUSE_HTTP_USER)  $options['login']    = JJSHOUSE_HTTP_USER;
        if (defined('JJSHOUSE_HTTP_PASS') && JJSHOUSE_HTTP_PASS)  $options['password'] = JJSHOUSE_HTTP_PASS;
    }
    else if ($service_type == 'ERPSYNC')
    {
		if (defined('JJSHOUSE_HTTP_USER') && JJSHOUSE_HTTP_USER)  $options['login']    = JJSHOUSE_HTTP_USER;
        if (defined('JJSHOUSE_HTTP_PASS') && JJSHOUSE_HTTP_PASS)  $options['password'] = JJSHOUSE_HTTP_PASS;
        $wsdl = ERPSYNC_WEBSERVICE_URL ."{$service_name}?wsdl" ;
    }
     else if ($service_type == 'ERPTAOBAOSYNC')
    {
		if (defined('JJSHOUSE_HTTP_USER') && JJSHOUSE_HTTP_USER)  $options['login']    = JJSHOUSE_HTTP_USER;
        if (defined('JJSHOUSE_HTTP_PASS') && JJSHOUSE_HTTP_PASS)  $options['password'] = JJSHOUSE_HTTP_PASS;
        $wsdl = SYNCJUSHITA_WEBSERVICE_URL ."{$service_name}?wsdl" ;
    }
    else 
    {
        if (defined('ROMEO_HTTP_USER') && ROMEO_HTTP_USER)  $options['login']    = ROMEO_HTTP_USER;
        if (defined('ROMEO_HTTP_PASS') && ROMEO_HTTP_PASS)  $options['password'] = ROMEO_HTTP_PASS;
        $wsdl = ROMEO_WEBSERVICE_URL . "{$service_name}?wsdl";
    }

    if (!is_null($service_options)) $options = array_merge($options, $service_options);

    $instance[$service_type][$service_name][$service_class] = new $service_class($wsdl, $options);
    return $instance[$service_type][$service_name][$service_class];
}

/**
 * 记录log
 */
function soap_log($script, $message, $level = 'Exception')
{
    $script  = $GLOBALS['db']->escape_string($script);
    $message = $GLOBALS['db']->escape_string($message);
    
    $GLOBALS['db']->query
    ("
        INSERT INTO `romeo`.`romeo_execute_log` (`script`, `message`, `message_type`, `datetime`) VALUES 
        ('{$script}', '{$message}', '{$level}', NOW())
    ");
}

/**
 * 由于soap的解析问题，当返回的数组只有一个元素时，将不会返回数组而是返回数组中的那个元素，该方法是为了弥补那个缺陷。
 *
 * @param object $object 数组中的元素
 * @return array 包装的数组
 */
function wrap_object_to_array($object) {
    if (!is_array($object) && is_object($object)) {
        $object = array($object);
    }
    return $object;
}


if (extension_loaded('soap')) {
	
/**
 * Soap_Client类，提供比原生的SoapClient更好用的调用方式
 * 
 * 使用原生态SoapClient的代码：
 * @code
 * $handle = new SoapClient();
 * $handle->foo(array('arg0' => $arg1, 'arg1' => $arg2));
 * @endcode
 * 
 * 使用Soap_Client的代码：
 * @code
 * $handle = new Soap_Client();
 * $handle->foo($arg1, $arg2);
 * @endcode 
 * 
 * 而且可以根据需要来扩展Soap_Client，比如需要将返回的结果格式化为数组，那么写一个toArray方法来处理结果集
 * @code
 * $handle = new Soap_Client();
 * $handle->foo($arg1, $arg2)->toArray();
 * @endcode
 * 
 * @author yxiang@oukoo.com
 * @copyright ouku.com
 */
class Soap_Client extends SoapClient
{
    /**
     * 最近一次请求的方法
     *
     * @var string
     */
    protected $_lastMethod = '';
    
    /**
     * 构造函数
     *
     * @param string $wsdl
     * @param array $options
     */
    function __construct($wsdl = null, $options = array())
    {
        parent::__construct($wsdl, $options);
    }

    /**
     * 找回最近一次请求的方法名
     *
     * @return string
     */
    public function getLastMethod()
    {
        return $this->_lastMethod;
    }
    
    /**
     * 执行一个SOAP请求
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {   
   	    $this->_lastMethod = $name;
   	    $arguments = $this->_preProcessArguments($arguments);
        $result = parent::__soapCall($name, $arguments);
        return $this->_preProcessResult($result);
    }
	
    /**
     * 执行参数预加工
     *
     * @param array $arguments
     */	
    protected function _preProcessArguments($arguments)
    {
        $args = array();
        $i = 0;
        foreach ($arguments as $value)
        {
            $sign = 'arg'.$i;
            $args[$sign] = $value;
            $i++;
        }
        return array($args);
    }
	
    /**
     * 执行对返回结果预加工
     *
     * @param array $arguments
     */
    protected function _preProcessResult($result)
    {
        if (!is_soap_fault($result) && isset($result->return))
        {
            return $result->return;
        }

        return $result;
    }	
}

}