<?php
/*
* service client for MessageApplicationService
*/
require_once(ROOT_PATH . "admin/includes/rpc/RpcController.php");
class MessageApplicationServiceClient{
  private $context;
  function __construct($context){
    $this->context = $context;
  }
  function __destruct(){
  }

  function getRemainedAmount($userKey, $serverName){
    $values = array($userKey,$serverName);
    $types = array("string","string");
    return CallRemoteService($this->context, "message.application.MessageApplicationService", "getRemainedAmount", $values, $types);
  }
  function sendBatchMessage($userKey, $mobileList, $msgText, $serverName){
    $values = array($userKey,$mobileList,$msgText,$serverName);
    $types = array("string","list","string","string");
    return CallRemoteService($this->context, "message.application.MessageApplicationService", "sendBatchMessage", $values, $types);
  }
  function getMessageUser($userKey){
    $values = array($userKey);
    $types = array("string");
    return CallRemoteService($this->context, "message.application.MessageApplicationService", "getMessageUser", $values, $types);
  }
  function registEx($userKey, $serverName){
    $values = array($userKey,$serverName);
    $types = array("string","string");
    return CallRemoteService($this->context, "message.application.MessageApplicationService", "registEx", $values, $types);
  }
  function logout($userKey, $serverName){
    $values = array($userKey,$serverName);
    $types = array("string","string");
    return CallRemoteService($this->context, "message.application.MessageApplicationService", "logout", $values, $types);
  }
  function getMessageConfig($userKey, $type){
    $values = array($userKey,$type);
    $types = array("string","string");
    return CallRemoteService($this->context, "message.application.MessageApplicationService", "getMessageConfig", $values, $types);
  }
  function setMessageConfig($userKey, $type, $config){
    $values = array($userKey,$type,$config);
    $types = array("string","string","string");
    return CallRemoteService($this->context, "message.application.MessageApplicationService", "setMessageConfig", $values, $types);
  }
  function updateMessageQueue($userKey, $queueIds, $status){
    $values = array($userKey,$queueIds,$status);
    $types = array("string","list","string");
    return CallRemoteService($this->context, "message.application.MessageApplicationService", "updateMessageQueue", $values, $types);
  }
  function listMessageQueue($userKey, $status, $start, $size){
    $values = array($userKey,$status,$start,$size);
    $types = array("string","string","int","int");
    return CallRemoteService($this->context, "message.application.MessageApplicationService", "listMessageQueue", $values, $types);
  }
}
?>