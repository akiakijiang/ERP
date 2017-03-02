<?php
/*
* service client for GroupGoodsService
*/
require_once("RpcController.php");
class GroupGoodsServiceClient{
  private $context;
  function __construct($context){
    $this->context = $context;
  }
  function __destruct(){
  }

  function listGroupGoods($parentId){
    $values = array($parentId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.groupgoods.GroupGoodsService", "listGroupGoods", $values, $types);
  }
  function listFittings($storeId, $query){
    $values = array($storeId,$query);
    $types = array("int","string");
    return CallRemoteService($this->context, "biaoju.groupgoods.GroupGoodsService", "listFittings", $values, $types);
  }
  function addGroupGoods($groupGoods){
    $values = array($groupGoods);
    $types = array("GroupGoods");
    return CallRemoteService($this->context, "biaoju.groupgoods.GroupGoodsService", "addGroupGoods", $values, $types);
  }
  function deleteFitting($goodsId, $parentId){
    $values = array($goodsId,$parentId);
    $types = array("int","int");
    CallRemoteService($this->context, "biaoju.groupgoods.GroupGoodsService", "deleteFitting", $values, $types);
  }
  function getMinPrice($storeGoodsId){
    $values = array($storeGoodsId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.groupgoods.GroupGoodsService", "getMinPrice", $values, $types);
  }
}
?>