<?php
define('IN_ECS', true);
require('includes/init.php');
require("function.php");

$type = $_REQUEST['type'];
$start_date = $_REQUEST['start'];
$end_date = $_REQUEST['end'];

$size = $_REQUEST['size'] ? $_REQUEST['size'] : 15;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;

$start = ($page - 1) * $size;
$limit = "LIMIT $size";
$offset = "OFFSET $start";

if ($type === null || $start_date === null || $end_date === null) {
	die('需要足够的数据');
}

$title = "";
?>