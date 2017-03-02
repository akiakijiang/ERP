<?php
/*
* service client for OrderService
*/
require_once(ROOT_PATH . "admin/includes/rpc/RpcController.php");
require_once(ROOT_PATH . "admin/includes/rpc/shopapi/ERPGoods.php");
require_once(ROOT_PATH . "admin/includes/rpc/shopapi/OrderGoods.php");
require_once(ROOT_PATH . "admin/includes/rpc/shopapi/OrderModel.php");


class OrderServiceClient{
  private $context;
  function __construct($context){
    $this->context = $context;
  }
  function __destruct(){
  }

  function listOrder($storeId, $orderPattern, $start, $count){
    $values = array($storeId,$orderPattern,$start,$count);
    $types = array("int","OrderModel","int","int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "listOrder", $values, $types);
  }
  function listNeedShippingOrder($storeId, $start, $count){
    $values = array($storeId,$start,$count);
    $types = array("int","int","int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "listNeedShippingOrder", $values, $types);
  }
  function getOrderById($orderId){
    $values = array($orderId);
    $types = array("int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "getOrderById", $values, $types);
  }
  function updateOrder($order){
    $values = array($order);
    $types = array("OrderModel");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "updateOrder", $values, $types);
  }
  function listOrderByUserId($userId){
    $values = array($userId);
    $types = array("string");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "listOrderByUserId", $values, $types);
  }
  function listOrderActionByOrderId($orderId){
    $values = array($orderId);
    $types = array("int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "listOrderActionByOrderId", $values, $types);
  }
  function searchOrders($searchCriteria){
    $values = array($searchCriteria);
    $types = array("OrderSearchCriteria");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "searchOrders", $values, $types);
  }
  function getShippingList(){
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "getShippingList", null, null);
  }
  function getPaymentList(){
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "getPaymentList", null, null);
  }
  function getRegionList($parentId){
    $values = array($parentId);
    $types = array("int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "getRegionList", $values, $types);
  }
  function updateOrderCarrier($carrier){
    $values = array($carrier);
    $types = array("CarrierModel");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "updateOrderCarrier", $values, $types);
  }
  function listOrderCarrier(){
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "listOrderCarrier", null, null);
  }
  function getCarrierById($carrierId){
    $values = array($carrierId);
    $types = array("int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "getCarrierById", $values, $types);
  }
  function getCarrierBillById($billId){
    $values = array($billId);
    $types = array("int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "getCarrierBillById", $values, $types);
  }
  function insertCarrierBill($carrierBill){
    $values = array($carrierBill);
    $types = array("CarrierBill");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "insertCarrierBill", $values, $types);
  }
  function updateOrderGoods($orderGoods){
    $values = array($orderGoods);
    $types = array("OrderGoods");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "updateOrderGoods", $values, $types);
  }
  function addOrderGoods($orderGoods){
    $values = array($orderGoods);
    $types = array("OrderGoods");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "addOrderGoods", $values, $types);
  }
  function deleteOrderGoods($orderGoods){
    $values = array($orderGoods);
    $types = array("OrderGoods");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "deleteOrderGoods", $values, $types);
  }
  function listOrderGoodsById($orderId){
    $values = array($orderId);
    $types = array("int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "listOrderGoodsById", $values, $types);
  }
  function getOrderGoodsById($orderGoodsId){
    $values = array($orderGoodsId);
    $types = array("int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "getOrderGoodsById", $values, $types);
  }
  function listERPGoodsById($orderGoodsId){
    $values = array($orderGoodsId);
    $types = array("int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "listERPGoodsById", $values, $types);
  }
  function getERPGoods($erpId){
    $values = array($erpId);
    $types = array("int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "getERPGoods", $values, $types);
  }
  function addOrderAction($orderId, $userId, $comment){
    $values = array($orderId,$userId,$comment);
    $types = array("int","String","String");
    CallRemoteService($this->context, "shoprpcapi.order.OrderService", "addOrderAction", $values, $types);
  }
  function getOrderCommentById($orderCommentId){
    $values = array($orderCommentId);
    $types = array("int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "getOrderCommentById", $values, $types);
  }
  function updateOrderComment($comment){
    $values = array($comment);
    $types = array("OrderComment");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "updateOrderComment", $values, $types);
  }
  function listOrderCommentsByOrderId($orderId){
    $values = array($orderId);
    $types = array("int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "listOrderCommentsByOrderId", $values, $types);
  }
  function listOrderCommentsByStoreId($storeId, $start, $count, $status){
    $values = array($storeId,$start,$count,$status);
    $types = array("int","int","int","string");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "listOrderCommentsByStoreId", $values, $types);
  }
  function getOrderSnByOrderId($orderId){
    $values = array($orderId);
    $types = array("int");
    return CallRemoteService($this->context, "shoprpcapi.order.OrderService", "getOrderSnByOrderId", $values, $types);
  }
}
?>