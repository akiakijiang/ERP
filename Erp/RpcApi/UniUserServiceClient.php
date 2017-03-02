<?php
/*
* service client for UniUserService
*/
require_once("RpcController.php");
class UniUserServiceClient{
  private $context;
  function __construct($context){
    $this->context = $context;
  }
  function __destruct(){
  }

  function createUser($user, $applicationKey){
    $values = array($user,$applicationKey);
    $types = array("OKUser","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "createUser", $values, $types);
  }
  function loginUser($loginUser, $applicationKey){
    $values = array($loginUser,$applicationKey);
    $types = array("OKLoginUser","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "loginUser", $values, $types);
  }
  function logoutUser($sessionKey, $applicationKey){
    $values = array($sessionKey,$applicationKey);
    $types = array("string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "logoutUser", $values, $types);
  }
  function changePassword($user, $newPassword, $applicationKey){
    $values = array($user,$newPassword,$applicationKey);
    $types = array("OKUser","string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "changePassword", $values, $types);
  }
  function changeUserInfo($user, $applicationKey){
    $values = array($user,$applicationKey);
    $types = array("OKUser","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "changeUserInfo", $values, $types);
  }
  function getUserContextBySessionKey($sessionKey, $applicationKey){
    $values = array($sessionKey,$applicationKey);
    $types = array("string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "getUserContextBySessionKey", $values, $types);
  }
  function resetPassword($userInput, $url, $applicationKey){
    $values = array($userInput,$url,$applicationKey);
    $types = array("string","string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "resetPassword", $values, $types);
  }
  function verifyPWDCode($code, $newPassword, $applicationKey){
    $values = array($code,$newPassword,$applicationKey);
    $types = array("string","string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "verifyPWDCode", $values, $types);
  }
  function pingSession($sessionKey, $applicationKey){
    $values = array($sessionKey,$applicationKey);
    $types = array("string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "pingSession", $values, $types);
  }
  function getUserById($userId, $applicationKey){
    $values = array($userId,$applicationKey);
    $types = array("string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "getUserById", $values, $types);
  }
  function getUserByName($name, $applicationKey){
    $values = array($name,$applicationKey);
    $types = array("string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "getUserByName", $values, $types);
  }
  function getUserByEmail($email, $applicationKey){
    $values = array($email,$applicationKey);
    $types = array("string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "getUserByEmail", $values, $types);
  }
  function verifyUserName($name, $applicationKey){
    $values = array($name,$applicationKey);
    $types = array("string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "verifyUserName", $values, $types);
  }
  function verifyPassword($password, $applicationKey){
    $values = array($password,$applicationKey);
    $types = array("string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "verifyPassword", $values, $types);
  }
  function verifyEmail($email, $applicationKey){
    $values = array($email,$applicationKey);
    $types = array("string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "verifyEmail", $values, $types);
  }
  function verifyMobile($mobile, $applicationKey){
    $values = array($mobile,$applicationKey);
    $types = array("string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "verifyMobile", $values, $types);
  }
  function verifyIdNo($idNo, $applicationKey){
    $values = array($idNo,$applicationKey);
    $types = array("string","string");
    return CallRemoteService($this->context, "universal.user.UniUserService", "verifyIdNo", $values, $types);
  }
}
?>