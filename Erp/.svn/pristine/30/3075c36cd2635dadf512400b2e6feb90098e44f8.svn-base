<?php
/*
* service client for ICBCPaymentService
*/
require_once("RpcController.php");
class ICBCPaymentServiceClient{
  private $context;
  function __construct($context){
    $this->context = $context;
  }
  function __destruct(){
  }

  function sign($srcStr){
    $values = array($srcStr);
    $types = array("string");
    return CallRemoteService($this->context, "payment.icbc.ICBCPaymentService", "sign", $values, $types);
  }
  function verifySign($signStr, $srcStr){
    $values = array($signStr,$srcStr);
    $types = array("string","string");
    return CallRemoteService($this->context, "payment.icbc.ICBCPaymentService", "verifySign", $values, $types);
  }
  function getPublicCert(){
    return CallRemoteService($this->context, "payment.icbc.ICBCPaymentService", "getPublicCert", null, null);
  }
}
?>