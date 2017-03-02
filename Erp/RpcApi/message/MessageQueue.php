<?php
/*
* message definition for MessageQueue
* DO NOT CHANGE THIS FILE!
*/
class MessageQueue{
  public static $_package_ = "message";
  public static $_attributes_ = array("id"=>"int","create"=>"long","release"=>"long","status"=>"string","content"=>"string","createBy"=>"string","releaseBy"=>"string","destMobile"=>"string","serverName"=>"string","type"=>"string","userKey"=>"string");

  public $id;
  public $create;
  public $release;
  public $status;
  public $content;
  public $createBy;
  public $releaseBy;
  public $destMobile;
  public $serverName;
  public $type;
  public $userKey;
  function __construct() {}
  function __destruct() {}
  function setId($id){$this->id = $id;}
  function getId(){return $this->id;}
  function setCreate($create){$this->create = $create;}
  function getCreate(){return $this->create;}
  function setRelease($release){$this->release = $release;}
  function getRelease(){return $this->release;}
  function setStatus($status){$this->status = $status;}
  function getStatus(){return $this->status;}
  function setContent($content){$this->content = $content;}
  function getContent(){return $this->content;}
  function setCreateBy($createBy){$this->createBy = $createBy;}
  function getCreateBy(){return $this->createBy;}
  function setReleaseBy($releaseBy){$this->releaseBy = $releaseBy;}
  function getReleaseBy(){return $this->releaseBy;}
  function setDestMobile($destMobile){$this->destMobile = $destMobile;}
  function getDestMobile(){return $this->destMobile;}
  function setServerName($serverName){$this->serverName = $serverName;}
  function getServerName(){return $this->serverName;}
  function setType($type){$this->type = $type;}
  function getType(){return $this->type;}
  function setUserKey($userKey){$this->userKey = $userKey;}
  function getUserKey(){return $this->userKey;}
}
?>