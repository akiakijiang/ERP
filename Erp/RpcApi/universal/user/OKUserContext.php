<?php
/*
* message definition for OKUserContext
* DO NOT CHANGE THIS FILE!
*/
class OKUserContext{
  public static $_package_ = "universal.user";
  public static $_attributes_ = array("userId"=>"string","userName"=>"string","email"=>"string","mobile"=>"string","idNo"=>"string","sessionKey"=>"string","historyId"=>"string","errorCode"=>"int","timeOut"=>"int");

  public $userId;
  public $userName;
  public $email;
  public $mobile;
  public $idNo;
  public $sessionKey;
  public $historyId;
  public $errorCode;
  public $timeOut;
  function __construct() {}
  function __destruct() {}
  function setUserId($userId){$this->userId = $userId;}
  function getUserId(){return $this->userId;}
  function setUserName($userName){$this->userName = $userName;}
  function getUserName(){return $this->userName;}
  function setEmail($email){$this->email = $email;}
  function getEmail(){return $this->email;}
  function setMobile($mobile){$this->mobile = $mobile;}
  function getMobile(){return $this->mobile;}
  function setIdNo($idNo){$this->idNo = $idNo;}
  function getIdNo(){return $this->idNo;}
  function setSessionKey($sessionKey){$this->sessionKey = $sessionKey;}
  function getSessionKey(){return $this->sessionKey;}
  function setHistoryId($historyId){$this->historyId = $historyId;}
  function getHistoryId(){return $this->historyId;}
  function setErrorCode($errorCode){$this->errorCode = $errorCode;}
  function getErrorCode(){return $this->errorCode;}
  function setTimeOut($timeOut){$this->timeOut = $timeOut;}
  function getTimeOut(){return $this->timeOut;}
}
?>