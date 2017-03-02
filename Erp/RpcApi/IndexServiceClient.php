<?php
/*
* service client for IndexService
*/
require_once("RpcController.php");
class IndexServiceClient{
  private $context;
  function __construct($context){
    $this->context = $context;
  }
  function __destruct(){
  }

  function buildIndexMutilDocs($docPathes, $indexPath){
    $values = array($docPathes,$indexPath);
    $types = array("string","string");
    CallRemoteService($this->context, "lucene.index.IndexService", "buildIndexMutilDocs", $values, $types);
  }
  function buildAnchorDocIndex($database, $indexPath){
    $values = array($database,$indexPath);
    $types = array("DBConnection","string");
    CallRemoteService($this->context, "lucene.index.IndexService", "buildAnchorDocIndex", $values, $types);
  }
  function buildEcshopProductsIndex($name){
    $values = array($name);
    $types = array("string");
    CallRemoteService($this->context, "lucene.index.IndexService", "buildEcshopProductsIndex", $values, $types);
  }
}
?>