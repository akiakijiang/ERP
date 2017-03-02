<?php

/**
 * 应用引导程序 
 * 
 * @author yxiang@leqee.com
 * @since 2010-09-10 17:51
 */
header("Location: ./admin/indexV2.php");
exit;

$yii=dirname(__FILE__).'/../framework/yii.php';
$config=require_once(dirname(__FILE__).'/protected/config/main.php');
require_once($yii);

Yii::createWebApplication($config)->run();
