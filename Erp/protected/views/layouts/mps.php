<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php
if (!Yii::app()->user->isGuest) {
    echo "(". Yii::app()->user->name . ") " ;
} 
echo CHtml::encode($this->pageTitle); 
?></title>
<style type="text/css">
.login-user {
line-height:25px;
margin:0;
padding:2px 10px 2px 20px;
background-color: #EFEBDE;
border-bottom: 1px solid #D6CCA9;
font-size:12px;
}
.container {width:98%; margin:10px auto;font-size:13px;}
body {margin:0;padding:0;}
</style>
</head>

<body>
<?php 
if (!Yii::app()->user->isGuest) 
{
    echo 
    '<div class="login-user">' . 
    Yii::app()->user->name . 
    ' <a href="'. $this->createUrl('/mps/index') .'">系统首页</a>'.
    (Yii::app()->user->isSuperuser 
    ? ' <a href="' . $this->createUrl('/rights/assignment') . '">权限管理</a>' .
      ' <a href="' . $this->createUrl('/mpsUser') . '">用户管理</a>'
    : ''
    ) .
    '<span style="float:right;">
     <a href="'. $this->createUrl('/mpsUser/updatepassword') .'">修改密码</a> 
     <a href="'. $this->createUrl('/mps/logout') .'">退出</a> 
     </span></div>'; 
}
?>
<div class="container" id="page">
<?php print $content; ?>
</div>
</body>
</html>