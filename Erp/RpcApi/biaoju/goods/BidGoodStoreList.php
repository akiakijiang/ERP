<?php
/*
* message definition for BidGoodStoreList
* DO NOT CHANGE THIS FILE!
*/
class BidGoodStoreList{
  public static $_package_ = "biaoju.goods";
  public static $_attributes_ = array("totalCount"=>"int","highPrice"=>"double","lowPrice"=>"double","storeList"=>"list");

  public $totalCount;
  public $highPrice;
  public $lowPrice;
  public $storeList;
  function __construct() {}
  function __destruct() {}
  function setTotalCount($totalCount){$this->totalCount = $totalCount;}
  function getTotalCount(){return $this->totalCount;}
  function setHighPrice($highPrice){$this->highPrice = $highPrice;}
  function getHighPrice(){return $this->highPrice;}
  function setLowPrice($lowPrice){$this->lowPrice = $lowPrice;}
  function getLowPrice(){return $this->lowPrice;}
  function setStoreList($storeList){$this->storeList = $storeList;}
  function getStoreList(){return $this->storeList;}
}
?>