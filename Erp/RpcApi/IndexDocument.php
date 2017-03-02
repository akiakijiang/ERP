<?php
/*
* message definition for IndexDocument
* DO NOT CHANGE THIS FILE!
*/
class IndexDocument{
  public static $_package_ = "lucene.index";
  public static $_attributes_ = array("id"=>"string","text"=>"string","words"=>"list","labels"=>"list");

  public $id;
  public $text;
  public $words;
  public $labels;
  function __construct() {}
  function __destruct() {}
  function setId($id){$this->id = $id;}
  function getId(){return $this->id;}
  function setText($text){$this->text = $text;}
  function getText(){return $this->text;}
  function setWords($words){$this->words = $words;}
  function getWords(){return $this->words;}
  function setLabels($labels){$this->labels = $labels;}
  function getLabels(){return $this->labels;}
}
?>