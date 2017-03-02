<?php
/**
 * @package ���봫�ݹ�ȥ���ļ�
 * @author  С��
 * @version 0.5
 * @example 1:x:32:3:TestModel:intvalue:stringvalue:10:2:6:11:0:113:12:5:dGVzdA==
 */
require_once('RpcBase.php');
class RpcEncode extends RpcBase
{
	static 	$sSplit						=	':';
	static 	$sDou						=	'.';
	static 	$sNull						=	'#';
	static 	$sFirstChar					=	'1:x';
	static 	$iStartKey					=	10;
	static	$arResquestKeyWord			=	array("Value"=>"_attributes_","Object"=>"_package_",'get'=>'get');
	static	$arRequestType				=	array("int"=>"int","long"=>"long","float"=>"float","double"=>"double","boolean"=>"boolean","string"=>"string","list"=>"list",'object'=>'object');
	static	$arObjectMapping			=	array('simple'=>array('int'=>0,'integer'=>0,'long'=>1,'float'=>2,'double'=>3),'boolean'=>4,'string'=>5,'array'=>6,'list'=>6,'map'=>7,'null'=>9);

	private $sReturnString;
	private $arObject;
	private $objType;
	private $objName;
	private $arTmpMapping				=	array();

	function __construct($arObject, $objType)
	{
		$this->arObject	=	$arObject;
		$this->objType 	= 	$objType;
	}

	/**
	 * �������������
	 * EncodeRequest
	 * @return boolean
	 */
	function EncodeRequest()
	{

		if(array_key_exists($this->objType,RpcEncode::$arObjectMapping['simple'])){
			$this->sResquestString	=	(int)RpcEncode::$arObjectMapping['simple'][$this->objType].RpcEncode::$sSplit.$this->arObject;
		}

		elseif($this->objType	==	RpcEncode::$arObjectMapping['boolean'])
		$this->sResquestString	=	$this->Encode($this->arObject,$this->objType);
		
		elseif($this->objType	==	RpcEncode::$arObjectMapping['string']){
			$this->sResquestString	=	RpcEncode::$arObjectMapping['string'].RpcEncode::$sSplit.base64_encode($this->arObject);
		}

		elseif($this->objType	==	RpcEncode::$arObjectMapping['list'])
		$this->sResquestString	=	$this->Encode($this->arObject,$this->objType);

		
		elseif($this->objType	==	RpcEncode::$arObjectMapping['map'])
		$this->sResquestString	=	$this->Encode($this->arObject,$this->objType);

		else{
			$this->sResquestString	=	$this->Encode($this->arObject,$this->objType);
		}
		if($this->arTmpMapping){
			$iCount		=		intval(count($this->arTmpMapping));
			$iLength	=		intval(strlen($iCount.RpcEncode::$sSplit.implode(RpcEncode::$sSplit,$this->arTmpMapping)));
			$sOutString	=	 	RpcEncode::$sFirstChar.RpcEncode::$sSplit.$iLength.RpcEncode::$sSplit.$iCount.RpcEncode::$sSplit.implode(RpcEncode::$sSplit,$this->arTmpMapping);
		}else {
			$sOutString	=		'1';
		}
		
		return $sOutString.RpcEncode::$sSplit.$this->sResquestString;
	}


	function Encode($Obj,$type=false)
	{
		$sOutString	=	'';
		/*����*/
		if(!$type){
			$sType	=	gettype($Obj);
		}else{
			$sType	=	$type;
		}

		if(array_key_exists($sType,RpcEncode::$arObjectMapping['simple'])){
			if ($sType == "long")
				settype($Obj, "int");
			else
				settype($Obj, $sType);
			
			$sOutString		=	intval(RpcEncode::$arObjectMapping['simple'][$sType]).RpcEncode::$sSplit.$Obj;
			return $sOutString;

		}elseif ($sType	==	RpcEncode::$arRequestType['string']){
			if($Obj == null){
				$sOutString	=	intval(RpcEncode::$arObjectMapping[$sType]).RpcEncode::$sSplit.RpcEncode::$sNull;
				return $sOutString;
			}
			$sOutString		=	RpcEncode::$arObjectMapping[$sType].RpcEncode::$sSplit.base64_encode($Obj);
			return $sOutString;
		}elseif ($sType	==	RpcEncode::$arRequestType['boolean']){
			$sOutString		=	intval(RpcEncode::$arObjectMapping[$sType]).RpcEncode::$sSplit.(bool)$Obj;
			return $sOutString;
		}elseif (($sType	=='list')||($sType	=='array')){
			if($Obj == null){
				$sOutString	=	intval(RpcEncode::$arObjectMapping['null']).RpcEncode::$sSplit.RpcEncode::$sNull;
				return $sOutString;
			}
			$sOutString		=	RpcEncode::$arObjectMapping['list'].RpcEncode::$sSplit.count($Obj).RpcEncode::$sSplit;
			foreach ($Obj	as $key =>$value){
				$sTmpString	=	$this->Encode($value);
				$sOutString	.=	strlen($sTmpString).RpcEncode::$sSplit.$sTmpString;
			}
			return $sOutString;
			/*map*/
		}elseif ($sType	==	'map'){
			$sOutString		=	RpcEncode::$arObjectMapping['map'].RpcEncode::$sSplit.count($Obj).RpcEncode::$sSplit;
			foreach ($Obj	as $key =>$value){
				$sTmpKeyString		=	$this->Encode($key);
				$sTmpValueString	=	$this->Encode($value);
				$sOutString			.=	strlen($sTmpKeyString).RpcEncode::$sSplit.$sTmpKeyString.strlen($sTmpValueString).RpcEncode::$sSplit.$sTmpValueString;
			}
			return $sOutString;
		}else{
			$sOutString			=	'';
			$arClassVar			=	get_class_vars(get_class($Obj));
			$sClassName			=	$arClassVar[RpcEncode::$arResquestKeyWord['Object']].RpcEncode::$sDou.get_class($Obj);

			$arClassTypeValue	=	$arClassVar[RpcEncode::$arResquestKeyWord['Value']];

			if(array_search($sClassName,$this->arTmpMapping)===false){
				if(!count($this->arTmpMapping)){
					$this->arTmpMapping[RpcEncode::$iStartKey]	=	$sClassName;
					$iKey					=	array_search($sClassName,$this->arTmpMapping);
					$sOutString				.=	$iKey.RpcEncode::$sSplit.count($arClassTypeValue).RpcEncode::$sSplit;
				}else{
					$this->arTmpMapping[]	=	$sClassName;
					$iKey					=	array_search($sClassName,$this->arTmpMapping);
					$sOutString				.=	$iKey.RpcEncode::$sSplit.count($arClassTypeValue).RpcEncode::$sSplit;
				}
			}else {
				$iKey						=	array_search($sClassName,$this->arTmpMapping);
				$sOutString					.=	$iKey.RpcEncode::$sSplit.count($arClassTypeValue).RpcEncode::$sSplit;
			}

			if(is_array($arClassTypeValue)){
				foreach ($arClassTypeValue	as $key =>$value){
					$sOutInfo				=	$this->Encode($Obj->$key,$value);

					if(array_search($key,$this->arTmpMapping)===false){
						$this->arTmpMapping[]	=	$key;
						$iKey					=	array_search($key,$this->arTmpMapping);
						$sOutString				.=	strlen($iKey.RpcEncode::$sSplit.$sOutInfo).RpcEncode::$sSplit.$iKey.RpcEncode::$sSplit.$sOutInfo;
					}else{
						$iKey					=	array_search($key,$this->arTmpMapping);
						$sOutString				.=	strlen($iKey.RpcEncode::$sSplit.$sOutInfo).RpcEncode::$sSplit.$iKey.RpcEncode::$sSplit.$sOutInfo;
					}
				}
			}
			return $sOutString;
		}
	}
}
?>