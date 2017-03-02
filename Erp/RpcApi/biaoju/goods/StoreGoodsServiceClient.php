<?php
/*
* service client for StoreGoodsService
*/
require_once("RpcController.php");
require_once("biaoju/store/StoreSearchResult.php");
class StoreGoodsServiceClient{
  private $context;
  function __construct($context){
    $this->context = $context;
  }
  function __destruct(){
  }

  function addCategory($storeCategory){
    $values = array($storeCategory);
    $types = array("StoreCategory");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "addCategory", $values, $types);
  }
  function changeCatStatus($storeCategory){
    $values = array($storeCategory);
    $types = array("StoreCategory");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "changeCatStatus", $values, $types);
  }
  function listCatByStoreId($storeId){
    $values = array($storeId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "listCatByStoreId", $values, $types);
  }
  function listCatHistory($storeCategoryId){
    $values = array($storeCategoryId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "listCatHistory", $values, $types);
  }
  function listCatByStatus($status, $start, $size){
    $values = array($status,$start,$size);
    $types = array("string","int","int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "listCatByStatus", $values, $types);
  }
  function listGoodsByStatus($storeId, $status, $topCatId, $brandId, $start, $size){
    $values = array($storeId,$status,$topCatId,$brandId,$start,$size);
    $types = array("int","string","int","int","int","int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "listGoodsByStatus", $values, $types);
  }
  function addStoreGoods($goods){
    $values = array($goods);
    $types = array("StoreGoods");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "addStoreGoods", $values, $types);
  }
  function updateStoreGoods($goods){
    $values = array($goods);
    $types = array("StoreGoods");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "updateStoreGoods", $values, $types);
  }
  function listGoodsHistory($storeGoodsId){
    $values = array($storeGoodsId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "listGoodsHistory", $values, $types);
  }
  function getGoodsById($storeGoodsId){
    $values = array($storeGoodsId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "getGoodsById", $values, $types);
  }
  function getStoreListById($goodsId){
    $values = array($goodsId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "getStoreListById", $values, $types);
  }
  function listGoodsBrands($storeId, $topCatId, $status){
    $values = array($storeId,$topCatId,$status);
    $types = array("int","int","string");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "listGoodsBrands", $values, $types);
  }
  function listStoreGoodsIds($storeId, $goodsId){
    $values = array($storeId,$goodsId);
    $types = array("int","int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "listStoreGoodsIds", $values, $types);
  }
  function addGoodsRequest($goodsRequest, $userId, $comment){
    $values = array($goodsRequest,$userId,$comment);
    $types = array("StoreGoodsRequest","string","string");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "addGoodsRequest", $values, $types);
  }
  function getGoodsRequestById($requestId){
    $values = array($requestId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "getGoodsRequestById", $values, $types);
  }
  function updateGoodsRequest($goodsRequest, $userId, $comment){
    $values = array($goodsRequest,$userId,$comment);
    $types = array("StoreGoodsRequest","string","string");
    CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "updateGoodsRequest", $values, $types);
  }
  function listGoodsRequestHistories($goodsRequestId){
    $values = array($goodsRequestId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "listGoodsRequestHistories", $values, $types);
  }
  function listGoodsRequest($storeId, $status, $isNotified, $start, $length){
    $values = array($storeId,$status,$isNotified,$start,$length);
    $types = array("int","string","int","int","int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "listGoodsRequest", $values, $types);
  }
  function saveRequestImage($requestId, $goodsId){
    $values = array($requestId,$goodsId);
    $types = array("int","int");
    CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "saveRequestImage", $values, $types);
  }
  function listRecommends($storeId){
    $values = array($storeId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "listRecommends", $values, $types);
  }
  function getRecommendById($recommendId){
    $values = array($recommendId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "getRecommendById", $values, $types);
  }
  function addRecommend($recommend){
    $values = array($recommend);
    $types = array("Recommend");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "addRecommend", $values, $types);
  }
  function updateRecommend($recommend){
    $values = array($recommend);
    $types = array("Recommend");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "updateRecommend", $values, $types);
  }
  function deleteRecommendByStoreGoodsId($storeGoodsId){
    $values = array($storeGoodsId);
    $types = array("int");
    CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "deleteRecommendByStoreGoodsId", $values, $types);
  }
  function deleteRecommend($recommendId){
    $values = array($recommendId);
    $types = array("int");
    CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "deleteRecommend", $values, $types);
  }
  function isGoodsRecommended($storeGoodsId){
    $values = array($storeGoodsId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.goods.StoreGoodsService", "isGoodsRecommended", $values, $types);
  }
}
?>