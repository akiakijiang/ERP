<?php

/**
 * RPC请求组件
 * 
 * @author yxiang@leqee.com
 */
class CRpcClient
{
	public $hostname;
	public $port='80';
	public $serviceName;
	public $servicePath;
	
	/**
	 * 调用某个方法 
	 *
	 * @param string $function_name
	 * @param array $arguments
	 */
	public function __call($function_name, $arguments=array())
	{
		if($arguments===null||$arguments===array())
			$types=array();
		else if(is_array($arguments))
		{
			$types=array();
			foreach($arguments as $argument)
			{
				$type=gettype($argument);
				$types[]=$type=='array'?'list':$type;
			}
		}
		
		$this->call($function_name,$arguments,$types);
	}
	
	/**
	 * 请求某个方法
	 *
	 */
	public function call($function_name,$arguments=array(),$types=array())
	{
		$message=array();
		if(count($types)==count($arguments))
		{
			for($i=0; $i<count($types); $i++)
			{
				$encode=new RpcEncode($arguments[$i],$types[$i]);
				$message[]=$encode->EncodeRequest();
			}
		}
		$message=join("\r\n",$message);
		
		$timeout=20;
		$fp=@fsockopen($this->hostname,$this->port,$errno,$errstr,$timeout);
		if($fp)
		{
			$header="GET $this->servicePath?service=$this->serviceName&method=$function_name HTTP/1.0\r\n";
			$header.="Content-Type:application/x-www-form-urlencoded\r\n";
			$header.="Content-Length:".strlen($message)."\r\n";
			$header.="Connection: close\r\n";
			$header.="\r\n".$message;
			stream_set_timeout($fp,$timeout);
			fwrite($fp,$header);
	
			$response='';
			while(!feof($fp))
				$response.=fgets($fp,8192);
			$info=stream_get_meta_data($fp);
			fclose($fp);
			
			if($info['timed_out'])
				throw new Exception("Read and write data timeout!");
				
			$result=array_slice(explode("\r\n\r\n",$response), -1);
			$decode=new RpcDecode($result[0]);
			return $decode->DecodeRequest();
		}
		else
			throw new Exception("RPC server is not available, ". $errstr);
	}
}

class RpcDecode extends RpcBase
{
	static	$arResquestChar			=	array(':'=>':','x'=>'x','.'=>'.','/'=>'/','set'=>'set','get'=>'get');
	static 	$NULL 					= 	"#";
	static	$arResquestKey			=	array('StartKey'=>3,'ListKey'=>6,'map'=>7,'MainKey'=>10);
	public	$arResquestMapping		=	array();
	private $sEnRequestString		=	'';

	function __construct($sRequestString)
	{
		$this->sEnRequestString	= $sRequestString;
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

class RpcBase
{
	static $sResquestString ;
	
	function __construct($sRequestString){
		$this->sResquestString = strval($sRequestString);
	}

	function SplitString($sDecodeString,$sResquestChar=':'){
		return str_replace(strstr($sDecodeString,$sResquestChar),"",$sDecodeString);
	}

	function UrlParse($sUrl,$sResquestUrlChar='.',$sResquestUrlReChar='/'){
		$sClassName=array_slice(explode($sResquestUrlChar,$sUrl),-1);
		$sUrl=str_replace($sResquestUrlChar,$sResquestUrlReChar,strval($sUrl));
		$arUrl=array('sUrl'=>$sUrl,'sClassName'=>$sClassName[0]);
		return $arUrl;
	}

	function SplitNextString($sDecodeString,$sResquestChar=':'){
		$sDecodeString=strval($sDecodeString);
		return $sDecodeString=substr(strstr($sDecodeString,$sResquestChar),1);
	}
}