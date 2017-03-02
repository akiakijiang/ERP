<?php
/*
* service client for StoreService
*/
require_once("RpcController.php");
class StoreServiceClient{
  private $context;
  function __construct($context){
    $this->context = $context;
  }
  function __destruct(){
  }

  function addStore($store){
    $values = array($store);
    $types = array("Store");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "addStore", $values, $types);
  }
  function updateStore($store){
    $values = array($store);
    $types = array("Store");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "updateStore", $values, $types);
  }
  function changeStoreStatus($storeId, $status){
    $values = array($storeId,$status);
    $types = array("int","string");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "changeStoreStatus", $values, $types);
  }
  function listStores($status, $start, $size){
    $values = array($status,$start,$size);
    $types = array("string","int","int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listStores", $values, $types);
  }
  function searchStores($query, $start, $size){
    $values = array($query,$start,$size);
    $types = array("string","int","int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "searchStores", $values, $types);
  }
  function getStoreById($storeId){
    $values = array($storeId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "getStoreById", $values, $types);
  }
  function getAdminByStoreId($storeId){
    $values = array($storeId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "getAdminByStoreId", $values, $types);
  }
  function listStoreByAdminUserId($userId){
    $values = array($userId);
    $types = array("string");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listStoreByAdminUserId", $values, $types);
  }
  function addAddress($address){
    $values = array($address);
    $types = array("StoreAddress");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "addAddress", $values, $types);
  }
  function updateAddress($address){
    $values = array($address);
    $types = array("StoreAddress");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "updateAddress", $values, $types);
  }
  function deleteAddress($storeAddressId){
    $values = array($storeAddressId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "deleteAddress", $values, $types);
  }
  function listAddresses($storeId){
    $values = array($storeId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listAddresses", $values, $types);
  }
  function addContract($contract){
    $values = array($contract);
    $types = array("StoreContract");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "addContract", $values, $types);
  }
  function updateContract($contract){
    $values = array($contract);
    $types = array("StoreContract");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "updateContract", $values, $types);
  }
  function deleteContract($storeContractId){
    $values = array($storeContractId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "deleteContract", $values, $types);
  }
  function listContracts($storeId){
    $values = array($storeId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listContracts", $values, $types);
  }
  function listContractsByAddressId($storeAddressId){
    $values = array($storeAddressId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listContractsByAddressId", $values, $types);
  }
  function addFinanceRecord($storeFinance){
    $values = array($storeFinance);
    $types = array("StoreFinance");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "addFinanceRecord", $values, $types);
  }
  function listFinanceRecord($storeId, $status){
    $values = array($storeId,$status);
    $types = array("int","int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listFinanceRecord", $values, $types);
  }
  function addCashRequest($cashRequest){
    $values = array($cashRequest);
    $types = array("StoreCashRequest");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "addCashRequest", $values, $types);
  }
  function addStoreBank($bank){
    $values = array($bank);
    $types = array("StoreBank");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "addStoreBank", $values, $types);
  }
  function updateBank($bank){
    $values = array($bank);
    $types = array("StoreBank");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "updateBank", $values, $types);
  }
  function listStoreBank($storeId){
    $values = array($storeId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listStoreBank", $values, $types);
  }
  function addStoreComment($storeComment){
    $values = array($storeComment);
    $types = array("StoreComment");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "addStoreComment", $values, $types);
  }
  function updateStoreComment($storeComment){
    $values = array($storeComment);
    $types = array("StoreComment");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "updateStoreComment", $values, $types);
  }
  function listAllCommentsByStoreId($storeId, $status, $start, $size){
    $values = array($storeId,$status,$start,$size);
    $types = array("int","string","int","int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listAllCommentsByStoreId", $values, $types);
  }
  function listStoreCommentsByStoreId($storeId, $status, $start, $size){
    $values = array($storeId,$status,$start,$size);
    $types = array("int","string","int","int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listStoreCommentsByStoreId", $values, $types);
  }
  function listAllCommentsByUserId($userId, $status, $start, $size){
    $values = array($userId,$status,$start,$size);
    $types = array("string","string","int","int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listAllCommentsByUserId", $values, $types);
  }
  function listStoreCommentsByStoreGoodsId($storeGoodsId, $status, $start, $size){
    $values = array($storeGoodsId,$status,$start,$size);
    $types = array("int","string","int","int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listStoreCommentsByStoreGoodsId", $values, $types);
  }
  function listRepliedComments($storeId, $storeGoodsId, $userId, $start, $size){
    $values = array($storeId,$storeGoodsId,$userId,$start,$size);
    $types = array("int","int","string","int","int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listRepliedComments", $values, $types);
  }
  function listFaq($storeId, $storeGoodsId){
    $values = array($storeId,$storeGoodsId);
    $types = array("int","int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listFaq", $values, $types);
  }
  function getStoreCommentById($commentId){
    $values = array($commentId);
    $types = array("int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "getStoreCommentById", $values, $types);
  }
  function listOukuComments($status, $start, $size){
    $values = array($status,$start,$size);
    $types = array("string","int","int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listOukuComments", $values, $types);
  }
  function listOukuCommentsByGoodsId($goodsId, $status, $start, $size){
    $values = array($goodsId,$status,$start,$size);
    $types = array("int","string","int","int");
    return CallRemoteService($this->context, "biaoju.store.StoreService", "listOukuCommentsByGoodsId", $values, $types);
  }
}
?>