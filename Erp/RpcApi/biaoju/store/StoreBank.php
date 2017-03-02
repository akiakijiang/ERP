<?php
/*
* message definition for StoreBank
* DO NOT CHANGE THIS FILE!
*/
class StoreBank{
  public static $_package_ = "biaoju.store";
  public static $_attributes_ = array("storeBankId"=>"int","storeId"=>"int","bankSn"=>"string","bankName"=>"string","bankAddress"=>"string","bankPerson"=>"string");

  public $storeBankId;
  public $storeId;
  public $bankSn;
  public $bankName;
  public $bankAddress;
  public $bankPerson;
  function __construct() {}
  function __destruct() {}
  function setStoreBankId($storeBankId){$this->storeBankId = $storeBankId;}
  function getStoreBankId(){return $this->storeBankId;}
  function setStoreId($storeId){$this->storeId = $storeId;}
  function getStoreId(){return $this->storeId;}
  function setBankSn($bankSn){$this->bankSn = $bankSn;}
  function getBankSn(){return $this->bankSn;}
  function setBankName($bankName){$this->bankName = $bankName;}
  function getBankName(){return $this->bankName;}
  function setBankAddress($bankAddress){$this->bankAddress = $bankAddress;}
  function getBankAddress(){return $this->bankAddress;}
  function setBankPerson($bankPerson){$this->bankPerson = $bankPerson;}
  function getBankPerson(){return $this->bankPerson;}
}
?>