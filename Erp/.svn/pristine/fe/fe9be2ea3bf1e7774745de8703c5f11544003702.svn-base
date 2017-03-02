<?php
/*
* service client for SearchService
*/
//require("RpcController.php");
class SearchServiceClient{
	private $context;
	function __construct($context){
		$this->context = $context;
	}
	function __destruct(){
	}

	function searchForPages($searchDefInput){
		$values = array($searchDefInput);
	    $types = array("SearchDefInput");
	    return CallRemoteService($this->context, "lucene.serve.SearchService", "searchForPages", $values, $types);
	}	
/*
	function searchForProducts($query, $start, $count){
	$values = array($query,$start,$count);
	$types = array("string","int","int");
	return CallRemoteService($this->context, "lucene.serve.SearchService", "searchForProducts", $values, $types);
	}
*/
	function searchForProducts($searchDefInput){
		#pp($searchDefInput);
		$values = array($searchDefInput);
		$types = array("SearchDefInput");
		return CallRemoteService($this->context, "lucene.serve.SearchService", "searchForProducts", $values, $types);
	}
	function searchForProductsAdvance($input, $start, $count){
		$values = array($input,$start,$count);
		$types = array("list","int","int");
		return CallRemoteService($this->context, "lucene.serve.SearchService", "searchForProductsAdvance", $values, $types);
	}
    function search($search_name, $searchDefInput){
        $values = array($search_name,$searchDefInput);
        $types = array("string","SearchDefInput");
        return CallRemoteService($this->context, "lucene.serve.SearchService", "search", $values, $types);
    }
    function searchForBiaoju($searchDefInput){
        $values = array($searchDefInput);
        $types = array("SearchDefInput");
        return CallRemoteService($this->context, "lucene.serve.SearchService", "searchForBiaoju", $values, $types);
    }
}
?>