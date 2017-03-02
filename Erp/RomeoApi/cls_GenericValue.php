<?php


class GenericValue {
  private $gv;
  private $numberValue;
  private $stringValue;
  
  public function __construct() {
    $this->gv = new stdClass();
  }
  
  public function setNumberValue($numberValue) {
    $this->gv->numberValue = $numberValue;
    return $this;
  }
  
  public function getNumberValue() {
    return $this->gv->numberValue;
  }
  
  public function setStringValue($stringValue) {
    $this->gv->stringValue = $stringValue;
    return $this;
  }

  public function getStringValue() {
    return $this->gv->stringValue;
  }
  
  public function setHashmap($hashmap) {
    $this->gv->hashmap = $hashmap;
    return $this;
  }
  
  public function getHashmap() {
    return $this->gv->hashmap;
  }
  
  public function setArrayList($al) {
    $this->gv->arrayList = $al;
    return $this;
  }
  
  public function getArrayList() {
    return $this->gv->arrayList;
  }
  
  public function getObject() {
    return $this->gv;
  }
  
  public function __destruct() {    
  }
}