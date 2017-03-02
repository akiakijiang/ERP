<?php
/**
 * 显示消息，并且报告错误，并且跳转到特定页面(back参数)
 * 页面参数：
 * info: 报告消息
 * back: 回跳页面
 */
define('IN_ECS', true);
require('includes/init.php');


$smarty->display('oukooext/show_message.htm');
?>