<?php


class HashMap {
  private $hasharray;
  private $hasharray_mapping;
  private $hs;
  
  public function __construct() {
    $this->hasharray = array();
    $this->hasharray_mapping = array();
    $this->hs = new stdClass();
  }
  
  public function put($key, $value) {
    $hashmap = new stdClass();
    $hashmap->key = $key;
    $hashmap->value = $value;
    $this->hasharray_mapping[$key] = $value;
    $this->hasharray[] = $hashmap;
  }
  
  public function get($key) {
    return $this->hasharray_mapping[$key];
  }
  
  public function setObject($o) {
    $this->hs->entry = $o->entry;
    if(!is_array($o->entry)) { $o->entry = array($o->entry); }
    foreach ($o->entry as $entry_item) {
      $key = $entry_item->key;
      $value = $entry_item->value;
      $this->hasharray_mapping[$key] = $value;
    }
  }
  
  public function getObject() {
    $this->hs->entry = $this->hasharray;
    return $this->hs;
  }
  
  public function __destruct() {    
  }
}