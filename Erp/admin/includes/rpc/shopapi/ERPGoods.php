<?php
/*
* message definition for ERPGoods
* DO NOT CHANGE THIS FILE!
*/
class ERPGoods{
  public static $_package_ = "shoprpcapi.order";
  public static $_attributes_ = array("erpId"=>"int","orderGoodsId"=>"int","isPurchasePaid"=>"String","purchasePaidType"=>"int","purchasePaidAmount"=>"double","purchasePaidTime"=>"long","cheque"=>"string","purchaseInvoice"=>"string","erpGoodsSn"=>"string","inSn"=>"string","outSn"=>"string","shippingInvoice"=>"string","shippingInvoiceStatus"=>"string","shippingInvoiceCarrierId"=>"int","shippingInvoiceBillNo"=>"string","actionUser"=>"string","inReason"=>"string","orderType"=>"string","note"=>"string","isFinancePaid"=>"string","providerId"=>"int","isReturned"=>"int","inTime"=>"long","lastUpdateTime"=>"long","billId"=>"int","isNew"=>"string");

  public $erpId;
  public $orderGoodsId;
  public $isPurchasePaid;
  public $purchasePaidType;
  public $purchasePaidAmount;
  public $purchasePaidTime;
  public $cheque;
  public $purchaseInvoice;
  public $erpGoodsSn;
  public $inSn;
  public $outSn;
  public $shippingInvoice;
  public $shippingInvoiceStatus;
  public $shippingInvoiceCarrierId;
  public $shippingInvoiceBillNo;
  public $actionUser;
  public $inReason;
  public $orderType;
  public $note;
  public $isFinancePaid;
  public $providerId;
  public $isReturned;
  public $inTime;
  public $lastUpdateTime;
  public $billId;
  public $isNew;
  function __construct() {}
  function __destruct() {}
  function setErpId($erpId){$this->erpId = $erpId;}
  function getErpId(){return $this->erpId;}
  function setOrderGoodsId($orderGoodsId){$this->orderGoodsId = $orderGoodsId;}
  function getOrderGoodsId(){return $this->orderGoodsId;}
  function setIsPurchasePaid($isPurchasePaid){$this->isPurchasePaid = $isPurchasePaid;}
  function getIsPurchasePaid(){return $this->isPurchasePaid;}
  function setPurchasePaidType($purchasePaidType){$this->purchasePaidType = $purchasePaidType;}
  function getPurchasePaidType(){return $this->purchasePaidType;}
  function setPurchasePaidAmount($purchasePaidAmount){$this->purchasePaidAmount = $purchasePaidAmount;}
  function getPurchasePaidAmount(){return $this->purchasePaidAmount;}
  function setPurchasePaidTime($purchasePaidTime){$this->purchasePaidTime = $purchasePaidTime;}
  function getPurchasePaidTime(){return $this->purchasePaidTime;}
  function setCheque($cheque){$this->cheque = $cheque;}
  function getCheque(){return $this->cheque;}
  function setPurchaseInvoice($purchaseInvoice){$this->purchaseInvoice = $purchaseInvoice;}
  function getPurchaseInvoice(){return $this->purchaseInvoice;}
  function setErpGoodsSn($erpGoodsSn){$this->erpGoodsSn = $erpGoodsSn;}
  function getErpGoodsSn(){return $this->erpGoodsSn;}
  function setInSn($inSn){$this->inSn = $inSn;}
  function getInSn(){return $this->inSn;}
  function setOutSn($outSn){$this->outSn = $outSn;}
  function getOutSn(){return $this->outSn;}
  function setShippingInvoice($shippingInvoice){$this->shippingInvoice = $shippingInvoice;}
  function getShippingInvoice(){return $this->shippingInvoice;}
  function setShippingInvoiceStatus($shippingInvoiceStatus){$this->shippingInvoiceStatus = $shippingInvoiceStatus;}
  function getShippingInvoiceStatus(){return $this->shippingInvoiceStatus;}
  function setShippingInvoiceCarrierId($shippingInvoiceCarrierId){$this->shippingInvoiceCarrierId = $shippingInvoiceCarrierId;}
  function getShippingInvoiceCarrierId(){return $this->shippingInvoiceCarrierId;}
  function setShippingInvoiceBillNo($shippingInvoiceBillNo){$this->shippingInvoiceBillNo = $shippingInvoiceBillNo;}
  function getShippingInvoiceBillNo(){return $this->shippingInvoiceBillNo;}
  function setActionUser($actionUser){$this->actionUser = $actionUser;}
  function getActionUser(){return $this->actionUser;}
  function setInReason($inReason){$this->inReason = $inReason;}
  function getInReason(){return $this->inReason;}
  function setOrderType($orderType){$this->orderType = $orderType;}
  function getOrderType(){return $this->orderType;}
  function setNote($note){$this->note = $note;}
  function getNote(){return $this->note;}
  function setIsFinancePaid($isFinancePaid){$this->isFinancePaid = $isFinancePaid;}
  function getIsFinancePaid(){return $this->isFinancePaid;}
  function setProviderId($providerId){$this->providerId = $providerId;}
  function getProviderId(){return $this->providerId;}
  function setIsReturned($isReturned){$this->isReturned = $isReturned;}
  function getIsReturned(){return $this->isReturned;}
  function setInTime($inTime){$this->inTime = $inTime;}
  function getInTime(){return $this->inTime;}
  function setLastUpdateTime($lastUpdateTime){$this->lastUpdateTime = $lastUpdateTime;}
  function getLastUpdateTime(){return $this->lastUpdateTime;}
  function setBillId($billId){$this->billId = $billId;}
  function getBillId(){return $this->billId;}
  function setIsNew($isNew){$this->isNew = $isNew;}
  function getIsNew(){return $this->isNew;}
}
?>