<?php
/*
* message definition for OrderGoods
* DO NOT CHANGE THIS FILE!
*/
class OrderGoods{
  public static $_package_ = "shoprpcapi.order";
  public static $_attributes_ = array("orderGoodsId"=>"int","orderId"=>"int","goodsId"=>"int","goodsName"=>"string","goodsSn"=>"string","goodsNumber"=>"int","marketPrice"=>"double","goodsPrice"=>"double","goodsAttr"=>"string","sendNumber"=>"int","isReal"=>"int","extensionCode"=>"string","parentId"=>"int","isGift"=>"int","goodsStatus"=>"int","actionAmt"=>"double","actionReasonCat"=>"int","actionNote"=>"string","billId"=>"int","providerId"=>"int","invoiceNum"=>"string","returnPoints"=>"int","subtitle"=>"string","addtionalShippingFee"=>"int","styleId"=>"int","actionUser"=>"string","actionTime"=>"long","actionId"=>"int","biaojuStoreGoodsId"=>"int","customized"=>"string");

  public $orderGoodsId;
  public $orderId;
  public $goodsId;
  public $goodsName;
  public $goodsSn;
  public $goodsNumber;
  public $marketPrice;
  public $goodsPrice;
  public $goodsAttr;
  public $sendNumber;
  public $isReal;
  public $extensionCode;
  public $parentId;
  public $isGift;
  public $goodsStatus;
  public $actionAmt;
  public $actionReasonCat;
  public $actionNote;
  public $billId;
  public $providerId;
  public $invoiceNum;
  public $returnPoints;
  public $subtitle;
  public $addtionalShippingFee;
  public $styleId;
  public $actionUser;
  public $actionTime;
  public $actionId;
  public $biaojuStoreGoodsId;
  public $customized;
  function __construct() {}
  function __destruct() {}
  function setOrderGoodsId($orderGoodsId){$this->orderGoodsId = $orderGoodsId;}
  function getOrderGoodsId(){return $this->orderGoodsId;}
  function setOrderId($orderId){$this->orderId = $orderId;}
  function getOrderId(){return $this->orderId;}
  function setGoodsId($goodsId){$this->goodsId = $goodsId;}
  function getGoodsId(){return $this->goodsId;}
  function setGoodsName($goodsName){$this->goodsName = $goodsName;}
  function getGoodsName(){return $this->goodsName;}
  function setGoodsSn($goodsSn){$this->goodsSn = $goodsSn;}
  function getGoodsSn(){return $this->goodsSn;}
  function setGoodsNumber($goodsNumber){$this->goodsNumber = $goodsNumber;}
  function getGoodsNumber(){return $this->goodsNumber;}
  function setMarketPrice($marketPrice){$this->marketPrice = $marketPrice;}
  function getMarketPrice(){return $this->marketPrice;}
  function setGoodsPrice($goodsPrice){$this->goodsPrice = $goodsPrice;}
  function getGoodsPrice(){return $this->goodsPrice;}
  function setGoodsAttr($goodsAttr){$this->goodsAttr = $goodsAttr;}
  function getGoodsAttr(){return $this->goodsAttr;}
  function setSendNumber($sendNumber){$this->sendNumber = $sendNumber;}
  function getSendNumber(){return $this->sendNumber;}
  function setIsReal($isReal){$this->isReal = $isReal;}
  function getIsReal(){return $this->isReal;}
  function setExtensionCode($extensionCode){$this->extensionCode = $extensionCode;}
  function getExtensionCode(){return $this->extensionCode;}
  function setParentId($parentId){$this->parentId = $parentId;}
  function getParentId(){return $this->parentId;}
  function setIsGift($isGift){$this->isGift = $isGift;}
  function getIsGift(){return $this->isGift;}
  function setGoodsStatus($goodsStatus){$this->goodsStatus = $goodsStatus;}
  function getGoodsStatus(){return $this->goodsStatus;}
  function setActionAmt($actionAmt){$this->actionAmt = $actionAmt;}
  function getActionAmt(){return $this->actionAmt;}
  function setActionReasonCat($actionReasonCat){$this->actionReasonCat = $actionReasonCat;}
  function getActionReasonCat(){return $this->actionReasonCat;}
  function setActionNote($actionNote){$this->actionNote = $actionNote;}
  function getActionNote(){return $this->actionNote;}
  function setBillId($billId){$this->billId = $billId;}
  function getBillId(){return $this->billId;}
  function setProviderId($providerId){$this->providerId = $providerId;}
  function getProviderId(){return $this->providerId;}
  function setInvoiceNum($invoiceNum){$this->invoiceNum = $invoiceNum;}
  function getInvoiceNum(){return $this->invoiceNum;}
  function setReturnPoints($returnPoints){$this->returnPoints = $returnPoints;}
  function getReturnPoints(){return $this->returnPoints;}
  function setSubtitle($subtitle){$this->subtitle = $subtitle;}
  function getSubtitle(){return $this->subtitle;}
  function setAddtionalShippingFee($addtionalShippingFee){$this->addtionalShippingFee = $addtionalShippingFee;}
  function getAddtionalShippingFee(){return $this->addtionalShippingFee;}
  function setStyleId($styleId){$this->styleId = $styleId;}
  function getStyleId(){return $this->styleId;}
  function setActionUser($actionUser){$this->actionUser = $actionUser;}
  function getActionUser(){return $this->actionUser;}
  function setActionTime($actionTime){$this->actionTime = $actionTime;}
  function getActionTime(){return $this->actionTime;}
  function setActionId($actionId){$this->actionId = $actionId;}
  function getActionId(){return $this->actionId;}
  function setBiaojuStoreGoodsId($biaojuStoreGoodsId){$this->biaojuStoreGoodsId = $biaojuStoreGoodsId;}
  function getBiaojuStoreGoodsId(){return $this->biaojuStoreGoodsId;}
  function setCustomized($customized){$this->customized = $customized;}
  function getCustomized(){return $this->customized;}
}
?>