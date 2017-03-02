<?php

/**
 * $Id$
 * @author czhang<czhang@oukoo.com>
 *
 * @package PHP Rpc
 * @author
 * @version 0.5
 */

require_once(ROOT_PATH . 'admin/includes/rpc/RpcDecode.php');
require_once(ROOT_PATH . 'admin/includes/rpc/RpcEncode.php');

class RpcContext
{
	private $sHost = '';
	private $iPort = 80;
	private $sPath = '';

	function __construct($sHost, $sPath, $iPort)
	{
		$this->setHost($sHost);
		$this->setPath($sPath);
		$this->setPort($iPort);
	}

	public function setHost($sHost)
	{
		$this->sHost = strval($sHost);
	}

	public function setPort($iPort)
	{
		$this->iPort = intval($iPort);
	}

	public function setPath($sPath)
	{
		$this->sPath = strval($sPath);
	}

	public function getHost()
	{
		return $this->sHost;
	}

	public function getPort()
	{
		return $this->iPort;
	}

	public function getPath()
	{
		return $this->sPath;
	}
}

/**
 * CallRemoteService
 * @author czhang<czhang@oukoo.com>
 *
 * @param Context $objContext
 * @param string $sService
 * @param string $sMethod
 * @param array $arParam
 * @param array $arType
 * @return string
 */
function CallRemoteService($objContext, $sService, $sMethod, $arParam, $arType)
{
	if (is_array($arParam) || is_null($arParam))
	{
		$sReturnResult = '';
		$sHost = $objContext->getHost();
		$iPort = $objContext->getPort();
		$sPath = $objContext->getPath();
		//pp($arParam);
		$send = array();
		if (count($arType) == count($arParam))
		{
			for ($i = 0; $i < count($arType); $i++)
			{
				$objEncode = new RpcEncode($arParam[$i], $arType[$i]);
				$send[] = $objEncode->EncodeRequest();
			}
		}
		$sReturnResult = join("\r\n", $send);

		/*
		if (isset($_GET['testrpcsend']) && $_GET['testrpcsend'])
		{
			pp($sReturnResult);
		}*/
		
		//echo($sReturnResult);
		
		$sSockSendReturn = SockSend($sPath, $iPort, $sHost, $sService, $sMethod, $sReturnResult);
	//echo($sSockSendReturn);
		/*
		if (isset($_GET['testrpcreturn']) && $_GET['testrpcreturn'])
		{
			pp($sSockSendReturn);
		}*/

		$arSockReturn = array_slice(explode("\r\n\r\n", $sSockSendReturn), -1);
//		print_r($arSockReturn);
		$objDecode = new RpcDecode($arSockReturn[0]);
		return $objDecode->DecodeRequest();
	}
	return null;
}

/**
 * send socket
 * @author Zandy<yzhang@oukoo.com>
 *
 * @param string $service_path
 * @param int $service_port
 * @param string $address
 * @param string $sService
 * @param string $sMethod
 * @param string $sReturnResult
 * @param int $socket_timeout
 * @return mixed (string or boolean)
 */
function SockSend($service_path, $service_port, $address, $sService, $sMethod, $sReturnResult, $timeout = 20)
{
	#pp($sReturnResult);
	//echo($address."\n");
	//echo($service_port."\n");
	#echo($errno."\n");
	#echo($errstr."\n");
	#echo($timeout."\n");
	$fp = fsockopen($address, $service_port, $errno, $errstr, $timeout);
	// var_dump($fp);
	//exit;
	if (!$fp)
	{
		echo('当前服务器不可用！');#print($address);debug_print_backtrace();
		die();#show_message
		###return array('errno' => $errno, 'errstr' => $errstr);
	}

	$header = "GET $service_path?service=$sService&method=$sMethod HTTP/1.0\r\n";
	$header .= "Content-Type:application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length:".strlen($sReturnResult)."\r\n";
	$header .= "Connection: close\r\n";
	$header .= "\r\n";
	//echo($header);
	$in = $header.$sReturnResult;

	stream_set_timeout($fp, $timeout);
	//echo($in);
	fwrite($fp, $in);

	$outInfo = "";

	while (!feof($fp))
	{
		$outInfo .= fgets($fp, 8192);
	}

	$info = stream_get_meta_data($fp);

	fclose($fp);

	if ($info['timed_out'])
	{
		print('读写数据超时！');die();#show_message
		###return array('errno' => -1, 'errstr' => "读写数据超时");
	}
	else
	{
		return $outInfo;
	}

}




?>