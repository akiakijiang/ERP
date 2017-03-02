<?php
/*
* message definition for StoreGoodsRequestHistory
* DO NOT CHANGE THIS FILE!
*/
class StoreGoodsRequestHistory{
  public static $_package_ = "biaoju.goods";
  public static $_attributes_ = array("storeGoodsRequestHistoryId"=>"int","storeGoodsRequestId"=>"int","status"=>"string","operator"=>"string","operateDatetime"=>"long","comment"=>"string");

  public $storeGoodsRequestHistoryId;
  public $storeGoodsRequestId;
  public $status;
  public $operator;
  public $operateDatetime;
  public $comment;
  function __construct() {}
  function __destruct() {}
  function setStoreGoodsRequestHistoryId($storeGoodsRequestHistoryId){$this->storeGoodsRequestHistoryId = $storeGoodsRequestHistoryId;}
  function getStoreGoodsRequestHistoryId(){return $this->storeGoodsRequestHistoryId;}
  function setStoreGoodsRequestId($storeGoodsRequestId){$this->storeGoodsRequestId = $storeGoodsRequestId;}
  function getStoreGoodsRequestId(){return $this->storeGoodsRequestId;}
  function setStatus($status){$this->status = $status;}
  function getStatus(){return $this->status;}
  function setOperator($operator){$this->operator = $operator;}
  function getOperator(){return $this->operator;}
  function setOperateDatetime($operateDatetime){$this->operateDatetime = $operateDatetime;}
  function getOperateDatetime(){return $this->operateDatetime;}
  function setComment($comment){$this->comment = $comment;}
  function getComment(){return $this->comment;}
}
?>