<?php
/*
* service client for SearchService
*/
require_once("RpcController.php");
class SearchServiceClient{
  private $context;
  function __construct($context){
    $this->context = $context;
  }
  function __destruct(){
  }

  function searchForPages($query, $start, $count){
    $values = array($query,$start,$count);
    $types = array("string","int","int");
    return CallRemoteService($this->context, "lucene.serve.SearchService", "searchForPages", $values, $types);
  }
  function searchForPages($searchDefInput){
    $values = array($searchDefInput);
    $types = array("SearchDefInput");
    return CallRemoteService($this->context, "lucene.serve.SearchService", "searchForPages", $values, $types);
  }
  function searchForPagesAbs($query, $count, $flag){
    $values = array($query,$count,$flag);
    $types = array("string","int","int");
    return CallRemoteService($this->context, "lucene.serve.SearchService", "searchForPagesAbs", $values, $types);
  }
  function searchForProducts($query, $start, $count){
    $values = array($query,$start,$count);
    $types = array("string","int","int");
    return CallRemoteService($this->context, "lucene.serve.SearchService", "searchForProducts", $values, $types);
  }
  function searchForProducts($searchDefInput){
    $values = array($searchDefInput);
    $types = array("SearchDefInput");
    return CallRemoteService($this->context, "lucene.serve.SearchService", "searchForProducts", $values, $types);
  }
  function searchForProductsAdvance($input, $start, $count){
    $values = array($input,$start,$count);
    $types = array("list","int","int");
    return CallRemoteService($this->context, "lucene.serve.SearchService", "searchForProductsAdvance", $values, $types);
  }
  function searchForBiaoju($searchDefInput){
    $values = array($searchDefInput);
    $types = array("SearchDefInput");
    return CallRemoteService($this->context, "lucene.serve.SearchService", "searchForBiaoju", $values, $types);
  }
  function search($search_name, $searchDefInput){
    $values = array($search_name,$searchDefInput);
    $types = array("string","SearchDefInput");
    return CallRemoteService($this->context, "lucene.serve.SearchService", "search", $values, $types);
  }
  function clearSearchers(){
    CallRemoteService($this->context, "lucene.serve.SearchService", "clearSearchers", null, null);
  }
}
?>