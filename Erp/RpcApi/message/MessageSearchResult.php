<?php
/*
* message definition for MessageSearchResult
* DO NOT CHANGE THIS FILE!
*/
class MessageSearchResult{
  public static $_package_ = "message";
  public static $_attributes_ = array("totalCount"=>"int","result"=>"list");

  public $totalCount;
  public $result;
  function __construct() {}
  function __destruct() {}
  function setTotalCount($totalCount){$this->totalCount = $totalCount;}
  function getTotalCount(){return $this->totalCount;}
  function setResult($result){$this->result = $result;}
  function getResult(){return $this->result;}
}
?>