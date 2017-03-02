<?php
/*
* service client for giftTicket
*/
//require_once("RpcController.php");
class giftTicketClient{
  private $context;
  function __construct($context){
    $this->context = $context;
  }
  function __destruct(){
  }

  function useGiftTicket($userId, $giftTicketCode, $iUseType, $squoteId, $UseIp){
    $values = array($userId,$giftTicketCode,$iUseType,$squoteId,$UseIp);
    $types = array("string","string","string","string","string");
    return CallRemoteService($this->context, "giftTicket.giftTicket", "useGiftTicket", $values, $types);
  }
  function textGiftTicket($giftTicketCode){
    $values = array($giftTicketCode);
    $types = array("string");
    return CallRemoteService($this->context, "giftTicket.giftTicket", "textGiftTicket", $values, $types);
  }
  function grantorGiftTicket($userId, $giftTicketCode){
    $values = array($userId,$giftTicketCode);
    $types = array("string","string");
    return CallRemoteService($this->context, "giftTicket.giftTicket", "grantorGiftTicket", $values, $types);
  }
  function userGiftTicketList($userId, $pageSize , $start=0){
    $values = array($userId,$pageSize,$start);
    $types = array("string","int","int");
    return CallRemoteService($this->context, "giftTicket.giftTicket", "userGiftTicketList", $values, $types);
  }

  function userGiftTicketCount($userId){
    $values = array($userId);
    $types = array("string");
    return CallRemoteService($this->context, "giftTicket.giftTicket", "userGiftTicketCount", $values, $types);
  }

  function getUnusedGiftTicketCode($gtc_id, $give_user, $give_comment, $num){
    $values = array($gtc_id, $give_user, $give_comment, $num);
    $types = array("int", "string", "string", "int");
    return CallRemoteService($this->context, "giftTicket.giftTicket", "getUnusedGiftTicketCode", $values, $types);
  }


//  function getAndGrantTicketCode($gtc_id, $user_id){
//    $values = array($gtc_id, $user_id);
//    $types = array("int", "string");
//    return CallRemoteService($this->context, "giftTicket.giftTicket", "getAndGrantTicketCode", $values, $types);
//  }

  function getAllGiftTicketConfig(){
    $values = array();
    $types = array();
    return CallRemoteService($this->context, "giftTicket.giftTicket", "getAllGiftTicketConfig", $values, $types);
  }

  function getGiftTicketConfig($gtc_id){
    $values = array($gtc_id);
    $types = array("int");
    return CallRemoteService($this->context, "giftTicket.giftTicket", "getGiftTicketConfig", $values, $types);
  }
}
?>