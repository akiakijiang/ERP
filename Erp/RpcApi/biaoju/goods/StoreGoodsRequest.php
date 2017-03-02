<?php
/*
* message definition for StoreGoodsRequest
* DO NOT CHANGE THIS FILE!
*/
class StoreGoodsRequest{
  public static $_package_ = "biaoju.goods";
  public static $_attributes_ = array("storeGoodsRequestId"=>"int","storeGoodsId"=>"int","storeId"=>"int","goodsId"=>"int","status"=>"string","name"=>"string","brandId"=>"int","categoryId"=>"int","subtitle"=>"string","introduce"=>"string","price"=>"double","comment"=>"string","thumb"=>"string","thumbDesc"=>"string","thumbUrl"=>"string","isNotified"=>"int","requestedBy"=>"string","requestedDatetime"=>"long");

  public $storeGoodsRequestId;
  public $storeGoodsId;
  public $storeId;
  public $goodsId;
  public $status;
  public $name;
  public $brandId;
  public $categoryId;
  public $subtitle;
  public $introduce;
  public $price;
  public $comment;
  public $thumb;
  public $thumbDesc;
  public $thumbUrl;
  public $isNotified;
  public $requestedBy;
  public $requestedDatetime;
  function __construct() {}
  function __destruct() {}
  function setStoreGoodsRequestId($storeGoodsRequestId){$this->storeGoodsRequestId = $storeGoodsRequestId;}
  function getStoreGoodsRequestId(){return $this->storeGoodsRequestId;}
  function setStoreGoodsId($storeGoodsId){$this->storeGoodsId = $storeGoodsId;}
  function getStoreGoodsId(){return $this->storeGoodsId;}
  function setStoreId($storeId){$this->storeId = $storeId;}
  function getStoreId(){return $this->storeId;}
  function setGoodsId($goodsId){$this->goodsId = $goodsId;}
  function getGoodsId(){return $this->goodsId;}
  function setStatus($status){$this->status = $status;}
  function getStatus(){return $this->status;}
  function setName($name){$this->name = $name;}
  function getName(){return $this->name;}
  function setBrandId($brandId){$this->brandId = $brandId;}
  function getBrandId(){return $this->brandId;}
  function setCategoryId($categoryId){$this->categoryId = $categoryId;}
  function getCategoryId(){return $this->categoryId;}
  function setSubtitle($subtitle){$this->subtitle = $subtitle;}
  function getSubtitle(){return $this->subtitle;}
  function setIntroduce($introduce){$this->introduce = $introduce;}
  function getIntroduce(){return $this->introduce;}
  function setPrice($price){$this->price = $price;}
  function getPrice(){return $this->price;}
  function setComment($comment){$this->comment = $comment;}
  function getComment(){return $this->comment;}
  function setThumb($thumb){$this->thumb = $thumb;}
  function getThumb(){return $this->thumb;}
  function setThumbDesc($thumbDesc){$this->thumbDesc = $thumbDesc;}
  function getThumbDesc(){return $this->thumbDesc;}
  function setThumbUrl($thumbUrl){$this->thumbUrl = $thumbUrl;}
  function getThumbUrl(){return $this->thumbUrl;}
  function setIsNotified($isNotified){$this->isNotified = $isNotified;}
  function getIsNotified(){return $this->isNotified;}
  function setRequestedBy($requestedBy){$this->requestedBy = $requestedBy;}
  function getRequestedBy(){return $this->requestedBy;}
  function setRequestedDatetime($requestedDatetime){$this->requestedDatetime = $requestedDatetime;}
  function getRequestedDatetime(){return $this->requestedDatetime;}
}
?>