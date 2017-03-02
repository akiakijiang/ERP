<?php
/*
* message definition for StoreFinance
* DO NOT CHANGE THIS FILE!
*/
class StoreFinance{
  public static $_package_ = "biaoju.store";
  public static $_attributes_ = array("storeFinanceId"=>"int","storeId"=>"int","orderId"=>"int","orderSn"=>"string","bankAmount"=>"double","bidAmount"=>"double","operator"=>"string","operatorDatetime"=>"long","comment"=>"string","errorCode"=>"int");

  public $storeFinanceId;
  public $storeId;
  public $orderId;
  public $orderSn;
  public $bankAmount;
  public $bidAmount;
  public $operator;
  public $operatorDatetime;
  public $comment;
  public $errorCode;
  function __construct() {}
  function __destruct() {}
  function setStoreFinanceId($storeFinanceId){$this->storeFinanceId = $storeFinanceId;}
  function getStoreFinanceId(){return $this->storeFinanceId;}
  function setStoreId($storeId){$this->storeId = $storeId;}
  function getStoreId(){return $this->storeId;}
  function setOrderId($orderId){$this->orderId = $orderId;}
  function getOrderId(){return $this->orderId;}
  function setOrderSn($orderSn){$this->orderSn = $orderSn;}
  function getOrderSn(){return $this->orderSn;}
  function setBankAmount($bankAmount){$this->bankAmount = $bankAmount;}
  function getBankAmount(){return $this->bankAmount;}
  function setBidAmount($bidAmount){$this->bidAmount = $bidAmount;}
  function getBidAmount(){return $this->bidAmount;}
  function setOperator($operator){$this->operator = $operator;}
  function getOperator(){return $this->operator;}
  function setOperatorDatetime($operatorDatetime){$this->operatorDatetime = $operatorDatetime;}
  function getOperatorDatetime(){return $this->operatorDatetime;}
  function setComment($comment){$this->comment = $comment;}
  function getComment(){return $this->comment;}
  function setErrorCode($errorCode){$this->errorCode = $errorCode;}
  function getErrorCode(){return $this->errorCode;}
}
?>