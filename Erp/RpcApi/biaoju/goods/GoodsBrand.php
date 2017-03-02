<?php
/*
* message definition for GoodsBrand
* DO NOT CHANGE THIS FILE!
*/
class GoodsBrand{
  public static $_package_ = "biaoju.goods";
  public static $_attributes_ = array("brandId"=>"int","name"=>"string","goodsCount"=>"int");

  public $brandId;
  public $name;
  public $goodsCount;
  function __construct() {}
  function __destruct() {}
  function setBrandId($brandId){$this->brandId = $brandId;}
  function getBrandId(){return $this->brandId;}
  function setName($name){$this->name = $name;}
  function getName(){return $this->name;}
  function setGoodsCount($goodsCount){$this->goodsCount = $goodsCount;}
  function getGoodsCount(){return $this->goodsCount;}
}
?>