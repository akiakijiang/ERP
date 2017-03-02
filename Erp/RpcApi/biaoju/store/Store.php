<?php
/*
* message definition for Store
* DO NOT CHANGE THIS FILE!
*/
class Store{
  public static $_package_ = "biaoju.store";
  public static $_attributes_ = array("storeId"=>"int","name"=>"string","telephone"=>"string","email"=>"string","mobile"=>"string","qq"=>"string","wangwang"=>"string","msn"=>"string","gtalk"=>"string","address"=>"string","promise"=>"string","notice"=>"string","introduction"=>"string","guarantee"=>"string","bidBalance"=>"double","bankBalance"=>"double","createdBy"=>"string","createdDatetime"=>"string","status"=>"string","adminUserId"=>"string","logo"=>"string","saleType"=>"string","errorCode"=>"int","domainName"=>"string");

  public $storeId;
  public $name;
  public $telephone;
  public $email;
  public $mobile;
  public $qq;
  public $wangwang;
  public $msn;
  public $gtalk;
  public $address;
  public $promise;
  public $notice;
  public $introduction;
  public $guarantee;
  public $bidBalance;
  public $bankBalance;
  public $createdBy;
  public $createdDatetime;
  public $status;
  public $adminUserId;
  public $logo;
  public $saleType;
  public $errorCode;
  public $domainName;
  function __construct() {}
  function __destruct() {}
  function setStoreId($storeId){$this->storeId = $storeId;}
  function getStoreId(){return $this->storeId;}
  function setName($name){$this->name = $name;}
  function getName(){return $this->name;}
  function setTelephone($telephone){$this->telephone = $telephone;}
  function getTelephone(){return $this->telephone;}
  function setEmail($email){$this->email = $email;}
  function getEmail(){return $this->email;}
  function setMobile($mobile){$this->mobile = $mobile;}
  function getMobile(){return $this->mobile;}
  function setQq($qq){$this->qq = $qq;}
  function getQq(){return $this->qq;}
  function setWangwang($wangwang){$this->wangwang = $wangwang;}
  function getWangwang(){return $this->wangwang;}
  function setMsn($msn){$this->msn = $msn;}
  function getMsn(){return $this->msn;}
  function setGtalk($gtalk){$this->gtalk = $gtalk;}
  function getGtalk(){return $this->gtalk;}
  function setAddress($address){$this->address = $address;}
  function getAddress(){return $this->address;}
  function setPromise($promise){$this->promise = $promise;}
  function getPromise(){return $this->promise;}
  function setNotice($notice){$this->notice = $notice;}
  function getNotice(){return $this->notice;}
  function setIntroduction($introduction){$this->introduction = $introduction;}
  function getIntroduction(){return $this->introduction;}
  function setGuarantee($guarantee){$this->guarantee = $guarantee;}
  function getGuarantee(){return $this->guarantee;}
  function setBidBalance($bidBalance){$this->bidBalance = $bidBalance;}
  function getBidBalance(){return $this->bidBalance;}
  function setBankBalance($bankBalance){$this->bankBalance = $bankBalance;}
  function getBankBalance(){return $this->bankBalance;}
  function setCreatedBy($createdBy){$this->createdBy = $createdBy;}
  function getCreatedBy(){return $this->createdBy;}
  function setCreatedDatetime($createdDatetime){$this->createdDatetime = $createdDatetime;}
  function getCreatedDatetime(){return $this->createdDatetime;}
  function setStatus($status){$this->status = $status;}
  function getStatus(){return $this->status;}
  function setAdminUserId($adminUserId){$this->adminUserId = $adminUserId;}
  function getAdminUserId(){return $this->adminUserId;}
  function setLogo($logo){$this->logo = $logo;}
  function getLogo(){return $this->logo;}
  function setSaleType($saleType){$this->saleType = $saleType;}
  function getSaleType(){return $this->saleType;}
  function setErrorCode($errorCode){$this->errorCode = $errorCode;}
  function getErrorCode(){return $this->errorCode;}
  function setDomainName($domainName){$this->domainName = $domainName;}
  function getDomainName(){return $this->domainName;}
}
?>