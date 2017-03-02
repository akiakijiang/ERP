<?php
/*
* message definition for GroupGoods
* DO NOT CHANGE THIS FILE!
*/
class GroupGoods{
  public static $_package_ = "biaoju.groupgoods";
  public static $_attributes_ = array("groupGoodsId"=>"int","parentId"=>"int","parentStoreId"=>"int","goodsId"=>"int","childStoreId"=>"int","createdBy"=>"string","createdDatetime"=>"long","price"=>"double","seq"=>"int");

  public $groupGoodsId;
  public $parentId;
  public $parentStoreId;
  public $goodsId;
  public $childStoreId;
  public $createdBy;
  public $createdDatetime;
  public $price;
  public $seq;
  function __construct() {}
  function __destruct() {}
  function setGroupGoodsId($groupGoodsId){$this->groupGoodsId = $groupGoodsId;}
  function getGroupGoodsId(){return $this->groupGoodsId;}
  function setParentId($parentId){$this->parentId = $parentId;}
  function getParentId(){return $this->parentId;}
  function setParentStoreId($parentStoreId){$this->parentStoreId = $parentStoreId;}
  function getParentStoreId(){return $this->parentStoreId;}
  function setGoodsId($goodsId){$this->goodsId = $goodsId;}
  function getGoodsId(){return $this->goodsId;}
  function setChildStoreId($childStoreId){$this->childStoreId = $childStoreId;}
  function getChildStoreId(){return $this->childStoreId;}
  function setCreatedBy($createdBy){$this->createdBy = $createdBy;}
  function getCreatedBy(){return $this->createdBy;}
  function setCreatedDatetime($createdDatetime){$this->createdDatetime = $createdDatetime;}
  function getCreatedDatetime(){return $this->createdDatetime;}
  function setPrice($price){$this->price = $price;}
  function getPrice(){return $this->price;}
  function setSeq($seq){$this->seq = $seq;}
  function getSeq(){return $this->seq;}
}
?>