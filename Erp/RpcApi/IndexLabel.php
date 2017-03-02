<?php
/*
* message definition for IndexLabel
* DO NOT CHANGE THIS FILE!
*/
class IndexLabel{
  public static $_package_ = "lucene.index";
  public static $_attributes_ = array("name"=>"string","value"=>"string","segmentedValue"=>"list");

  public $name;
  public $value;
  public $segmentedValue;
  function __construct() {}
  function __destruct() {}
  function setName($name){$this->name = $name;}
  function getName(){return $this->name;}
  function setValue($value){$this->value = $value;}
  function getValue(){return $this->value;}
  function setSegmentedValue($segmentedValue){$this->segmentedValue = $segmentedValue;}
  function getSegmentedValue(){return $this->segmentedValue;}
}
?>