<?php
/*
* message definition for StoreContract
* DO NOT CHANGE THIS FILE!
*/
class StoreContract{
  public static $_package_ = "biaoju.store";
  public static $_attributes_ = array("storeContractId"=>"int","storeId"=>"int","type"=>"string","name"=>"string","mobile"=>"string","telephone"=>"string","fax"=>"string","qq"=>"string","email"=>"string","msn"=>"string","storeAddressId"=>"int","comment"=>"string","errorCode"=>"int");

  public $storeContractId;
  public $storeId;
  public $type;
  public $name;
  public $mobile;
  public $telephone;
  public $fax;
  public $qq;
  public $email;
  public $msn;
  public $storeAddressId;
  public $comment;
  public $errorCode;
  function __construct() {}
  function __destruct() {}
  function setStoreContractId($storeContractId){$this->storeContractId = $storeContractId;}
  function getStoreContractId(){return $this->storeContractId;}
  function setStoreId($storeId){$this->storeId = $storeId;}
  function getStoreId(){return $this->storeId;}
  function setType($type){$this->type = $type;}
  function getType(){return $this->type;}
  function setName($name){$this->name = $name;}
  function getName(){return $this->name;}
  function setMobile($mobile){$this->mobile = $mobile;}
  function getMobile(){return $this->mobile;}
  function setTelephone($telephone){$this->telephone = $telephone;}
  function getTelephone(){return $this->telephone;}
  function setFax($fax){$this->fax = $fax;}
  function getFax(){return $this->fax;}
  function setQq($qq){$this->qq = $qq;}
  function getQq(){return $this->qq;}
  function setEmail($email){$this->email = $email;}
  function getEmail(){return $this->email;}
  function setMsn($msn){$this->msn = $msn;}
  function getMsn(){return $this->msn;}
  function setStoreAddressId($storeAddressId){$this->storeAddressId = $storeAddressId;}
  function getStoreAddressId(){return $this->storeAddressId;}
  function setComment($comment){$this->comment = $comment;}
  function getComment(){return $this->comment;}
  function setErrorCode($errorCode){$this->errorCode = $errorCode;}
  function getErrorCode(){return $this->errorCode;}
}
?>