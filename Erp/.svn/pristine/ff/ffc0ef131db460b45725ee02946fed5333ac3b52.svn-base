<?php
/**
 * @package ����ݹ�4���ַ�
 * @author  С��
 * @version 0.5
 * @example 1:x:32:3:TestModel:intvalue:stringvalue:10:2:6:11:0:113:12:5:dGVzdA==
 */
class RpcBase
{
	static  $sResquestString ;
	
	/**
	 * ���캯��
	 * @param String $sRequestString
	 */
	function __construct($sRequestString){
		$this->sResquestString	=	strval($sRequestString);
	}

	
	/**
	 * ȡ:ǰ�ķ��
	 * SplitString
	 * @param String $sEncodeString
	 * @return String
	 */
	function SplitString($sDecodeString,$sResquestChar=':'){
		return str_replace(strstr($sDecodeString,$sResquestChar),"",$sDecodeString);
		//return substr($sDecodeString,0,strpos($sDecodeString,$sResquestChar));
	}
	/**
	 * Url���ʽ���
	 *
	 * @param String $sUrl
	 * @return String
	 */
	function UrlParse($sUrl,$sResquestUrlChar='.',$sResquestUrlReChar='/'){
		$sClassName	=	array_slice(explode($sResquestUrlChar,$sUrl),-1);
		$sUrl		=	str_replace($sResquestUrlChar,$sResquestUrlReChar,strval($sUrl));
		$arUrl		=	array('sUrl'=>$sUrl,'sClassName'=>$sClassName[0]);
		return $arUrl;
	}

	/**
	 * ȡ:����Ķ���
	 * SplitNextString
	 * @param String $sEncodeString
	 * @return String
	 */
	function SplitNextString($sDecodeString,$sResquestChar=':'){
		$sDecodeString			=	strval($sDecodeString);
		return $sDecodeString	=	substr(strstr($sDecodeString,$sResquestChar),1);
	}
}


?>