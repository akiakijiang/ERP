<?php
/**
 * @package 
 * @author  
 * @version 0.5
 * @example 1:x:32:3:TestModel:intvalue:stringvalue:10:2:6:11:0:113:12:5:dGVzdA==
 */
require_once('RpcBase.php');
class RpcDecode extends RpcBase
{
	static	$arResquestChar			=	array(':'=>':','x'=>'x','.'=>'.','/'=>'/','set'=>'set','get'=>'get');
	static 	$NULL 					= 	"#";
	static	$arResquestKey			=	array('StartKey'=>3,'ListKey'=>6,'map'=>7,'MainKey'=>10);
	public	$arResquestMapping		=	array();
	private $sEnRequestString		=	'';

	function __construct($sRequestString)
	{
		$this->sEnRequestString	=	$sRequestString;
	}

	/**
	 * EncodeRequest
	 *
	 * @return Array/boolean
	 */
	function DecodeRequest()
	{
		$arOutString		=	array();

		if($this->sEnRequestString&&strpos($this->sEnRequestString,RpcDecode::$arResquestChar[':'])){

			$arRequestMessage		=	explode(RpcDecode::$arResquestChar[':'],$this->sEnRequestString);
			if($arRequestMessage[1]==RpcDecode::$arResquestChar['x']){
				$arRequestMessage[3]	=	intval($arRequestMessage[3]);

				for ($i=1;$i<=$arRequestMessage[3];$i++){
					$iKey	=	9;

					$this->arResquestMapping[$iKey+$i]	=	$arRequestMessage[3+$i];
				}
				$sLastString	=	implode(RpcDecode::$arResquestChar[':'],array_slice($arRequestMessage,$arRequestMessage[3]+1+RpcDecode::$arResquestKey['StartKey']));
				if($sLastString){
					return $this->Decode($sLastString);
				}
			}
			else
			{
				$this->sEnRequestString	=	parent::SplitNextString($this->sEnRequestString);

				$sResult	=	$this->Decode($this->sEnRequestString);
				return $sResult;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Decode
	 * 
	 * @param String $sRequestString
	 * @return Object
	 */
	function Decode($sRequestString)
	{

		$iType	=	intval(parent::SplitString($sRequestString));

		if (($iType>=0)&&($iType<=5)){
			$NextChar	= parent::SplitString(parent::SplitNextString($sRequestString));
			if($NextChar == RpcDecode::$NULL)
			return null;
		}
		if($iType==0){
			return (int)parent::SplitNextString($sRequestString);
		}elseif($iType==1){
			return (int)$NextChar;
		}elseif($iType==2){
			return (float)$NextChar;
		}elseif($iType==3){
			return (double)$NextChar;
		}elseif($iType==4){
			return (bool)$NextChar;
		}elseif($iType==5){
			return (string)base64_decode($NextChar);
		}elseif ($iType==9){
			return null;
		}elseif($iType==6){
			$arList			=	array();
			$iForCount		=	$NextChar;
			if($iForCount	==	RpcDecode::$NULL){
				return null;
			}
			$iForCount		=	(int)parent::SplitString(parent::SplitNextString($sRequestString));
			$sRequestString	=	parent::SplitNextString(parent::SplitNextString($sRequestString));
			for ($i=1;$i<=$iForCount;$i++){
				$iListLength		=	(int)parent::SplitString($sRequestString);
				$iLength			=	(int)strlen($iListLength.RpcDecode::$arResquestChar[':']);
				$sListLengthString	=	(string)substr($sRequestString,$iLength,$iListLength);
				$arList[$i]			=	$this->Decode($sListLengthString);
				$sRequestString		=	(string)substr($sRequestString,$iLength+$iListLength);
			}
			return $arList;
		}elseif($iType==7){
			$arList			=	array();
			$iForCount		=	$NextChar;
			if($iForCount	==	RpcDecode::$NULL){
				return null;
			}
			$iForCount		=	(int)parent::SplitString(parent::SplitNextString($sRequestString));
			$sRequestString	=	parent::SplitNextString(parent::SplitNextString($sRequestString));
			for ($i=1;$i<=$iForCount;$i++){
				$iListLength		=	(int)parent::SplitString($sRequestString);
				$iLength			=	(int)strlen($iListLength.RpcDecode::$arResquestChar[':']);
				$sListLengthString	=	(string)substr($sRequestString,$iLength,$iListLength);
				$sTempKey			=	$this->Decode($sListLengthString);
				$sRequestString		=	(string)substr($sRequestString,$iLength+$iListLength);

				$iListLength		=	(int)parent::SplitString($sRequestString);
				$iLength			=	(int)strlen($iListLength.RpcDecode::$arResquestChar[':']);
				$sListLengthString	=	(string)substr($sRequestString,$iLength,$iListLength);
				$arList[$sTempKey]	=	$this->Decode($sListLengthString);
				$sRequestString		=	(string)substr($sRequestString,$iLength+$iListLength);
			}
			return $arList;
		}elseif ($iType >= 10){
			$sRequestStrings	=	parent::SplitNextString($sRequestString);
			$objName		=	$this->arResquestMapping[$iType];
			$arUrl			=	parent::UrlParse($objName);
			$iSize			=	parent::SplitString($sRequestStrings);

			if($iSize	==	RpcDecode::$NULL){
				return null;
			}
			$iSize			=	(int)parent::SplitString($sRequestStrings);

			/*if(file_exists(ROOT_PATH.'RpcApi/'.$arUrl['sUrl'].'.php')){
				require_once(ROOT_PATH.'RpcApi/'.$arUrl['sUrl'].'.php');
				$objDecode	=	new	$arUrl['sClassName'];
			} else {
				print_r($this->arResquestMapping);
			}*/

			$arUrl['sUrl'] = str_replace(".", "/", $arUrl['sUrl']);

			if (defined('ROOT_PATH')) {
				if(file_exists(ROOT_PATH.'RpcApi/'.$arUrl['sUrl'].'.php')){
					@include_once(ROOT_PATH.'RpcApi/'.$arUrl['sUrl'].'.php');
				}
				@include_once($arUrl['sUrl'].'.php');
			} else {
				if (@include_once('RpcApi/'.$arUrl['sUrl'].'.php')){
				} elseif(@include_once($arUrl['sUrl'].'.php')){
				} else {
					@include_once($arUrl['sUrl'].'.php');
				}
			}
			
			$_start = strrpos($arUrl['sUrl'], '/');
			$_start = false === $_start ? 0 : $_start+1;
			$className = substr($arUrl['sUrl'], $_start);

			if (class_exists($className)) {
				$objDecode	=	new	$arUrl['sClassName'];
			}else{
				$objDecode	=	null;
			}

			$sRequestStringss	=	parent::SplitNextString($sRequestStrings);

			for ($i=1;$i<=$iSize;$i++){

				$iLength=	(int)parent::SplitString($sRequestStringss);

				$iStart	=	(int)strlen($iLength.RpcDecode::$arResquestChar[':']);

				$sMethodType	=	$this->arResquestMapping[parent::SplitString(parent::SplitNextString($sRequestStringss))];

				$sTypeMethod	=	ucfirst($sMethodType);

				if($sTypeMethod){
					$sType	=	RpcDecode::$arResquestChar['set'].$sTypeMethod;
				}

				$sValue	= $this->Decode(parent::SplitNextString(parent::SplitNextString(substr($sRequestStringss,0,$iStart+$iLength))));

				if(is_object($objDecode)&&$sType){
					$objDecode->$sType($sValue);
				}
				$sRequestStringss	=	substr($sRequestStringss,$iStart+$iLength);
			}
			return $objDecode;
		}
	}
	function __destruct(){

	}
}
?>