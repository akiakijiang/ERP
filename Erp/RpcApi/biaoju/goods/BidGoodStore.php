<?php
/*
* message definition for BidGoodStore
* DO NOT CHANGE THIS FILE!
*/
class BidGoodStore{
  public static $_package_ = "biaoju.goods";
  public static $_attributes_ = array("storeId"=>"int","storeGoodsId"=>"int","storeName"=>"string","price"=>"double","goodsDesc"=>"string","subtitle"=>"string","operateDatetime"=>"long");

  public $storeId;
  public $storeGoodsId;
  public $storeName;
  public $price;
  public $goodsDesc;
  public $subtitle;
  public $operateDatetime;
  function __construct() {}
  function __destruct() {}
  function setStoreId($storeId){$this->storeId = $storeId;}
  function getStoreId(){return $this->storeId;}
  function setStoreGoodsId($storeGoodsId){$this->storeGoodsId = $storeGoodsId;}
  function getStoreGoodsId(){return $this->storeGoodsId;}
  function setStoreName($storeName){$this->storeName = $storeName;}
  function getStoreName(){return $this->storeName;}
  function setPrice($price){$this->price = $price;}
  function getPrice(){return $this->price;}
  function setGoodsDesc($goodsDesc){$this->goodsDesc = $goodsDesc;}
  function getGoodsDesc(){return $this->goodsDesc;}
  function setSubtitle($subtitle){$this->subtitle = $subtitle;}
  function getSubtitle(){return $this->subtitle;}
  function setOperateDatetime($operateDatetime){$this->operateDatetime = $operateDatetime;}
  function getOperateDatetime(){return $this->operateDatetime;}
}
?>