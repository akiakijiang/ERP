<?php
/*
* service client for currency
*/
//require_once("RpcController.php");
class currencyClient{
  private $context;
  function __construct($context){
    $this->context = $context;
  }
  function __destruct(){
  }

  function userIntegral($userId){
    $values = array($userId);
    $types = array("string");
    return CallRemoteService($this->context, "currency.currency", "userIntegral", $values, $types);
  }
  function createPoint($oukuCurrencyCode, $siteId, $createIp, $userId){
    $values = array($oukuCurrencyCode,$siteId,$createIp,$userId);
    $types = array("string","int","string","string");
    return CallRemoteService($this->context, "currency.currency", "createPoint", $values, $types);
  }
  function editPoint($currencyValue, $siteid, $userId, $useType, $useMark, $userIp, $context){
    $values = array($currencyValue,$siteid,$userId,$useType,$useMark,$userIp,$context);
    $types = array("int","int","string","int","string","string","string");
    return CallRemoteService($this->context, "currency.currency", "editPoint", $values, $types);
  }
  function userCurrencyList($userId,$pageSize, $start = 0){
    $values = array($userId,$pageSize,$start);
    $types = array("string","int","int");
    return CallRemoteService($this->context, "currency.currency", "userCurrencyList", $values, $types);
  }
  function userCurrencyCount($userId){
    $values = array($userId);
    $types = array("string");
    return CallRemoteService($this->context, "currency.currency", "userCurrencyCount", $values, $types);
  }
}
?>