<?php
/*
* message definition for MatchedDocument
* DO NOT CHANGE THIS FILE!
*/
class MatchedDocument{
  public static $_package_ = "lucene.serve";
  public static $_attributes_ = array("id"=>"string","name"=>"string","snippet"=>"string","attributes"=>"map");

  public $id;
  public $name;
  public $snippet;
  public $attributes;
  function __construct() {}
  function __destruct() {}
  function setId($id){$this->id = $id;}
  function getId(){return $this->id;}
  function setName($name){$this->name = $name;}
  function getName(){return $this->name;}
  function setSnippet($snippet){$this->snippet = $snippet;}
  function getSnippet(){return $this->snippet;}
  function setAttributes($attributes){$this->attributes = $attributes;}
  function getAttributes(){return $this->attributes;}
}
?>